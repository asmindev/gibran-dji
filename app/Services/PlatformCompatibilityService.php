<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PlatformCompatibilityService
{
    /**
     * Get the current operating system family
     *
     * @return string
     */
    public static function getOSFamily(): string
    {
        return PHP_OS_FAMILY;
    }

    /**
     * Check if the current OS is Windows
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Check if the current OS is Unix-like (Linux, macOS)
     *
     * @return bool
     */
    public static function isUnix(): bool
    {
        return in_array(PHP_OS_FAMILY, ['Linux', 'Darwin']);
    }

    /**
     * Get the appropriate Python command for the current platform
     *
     * @return string
     */
    public static function getPythonCommand(): string
    {
        return self::isWindows() ? 'python' : 'python3';
    }

    /**
     * Build a cross-platform file path
     *
     * @param string ...$parts
     * @return string
     */
    public static function buildPath(string ...$parts): string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Normalize a file path for the current platform
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Execute a command with proper platform-specific handling
     *
     * @param string $command
     * @param string|null $workingDirectory
     * @return array ['output' => array, 'returnCode' => int, 'success' => bool]
     */
    public static function executeCommand(string $command, ?string $workingDirectory = null): array
    {
        $output = [];
        $returnCode = 0;

        $originalDir = null;
        if ($workingDirectory && is_dir($workingDirectory)) {
            $originalDir = getcwd();
            chdir($workingDirectory);
        }

        try {
            exec($command . ' 2>&1', $output, $returnCode);

            $result = [
                'output' => $output,
                'returnCode' => $returnCode,
                'success' => $returnCode === 0,
                'outputString' => implode("\n", $output),
                'command' => $command,
                'workingDirectory' => $workingDirectory
            ];

            Log::info('Command executed', $result);

            return $result;
        } finally {
            if ($originalDir) {
                chdir($originalDir);
            }
        }
    }

    /**
     * Build a Python execution command with virtual environment support
     *
     * @param string $scriptsPath
     * @param string $scriptName
     * @param array $args
     * @return array ['command' => string, 'workingDirectory' => string, 'venvUsed' => bool]
     */
    public static function buildPythonCommand(string $scriptsPath, string $scriptName, array $args = []): array
    {
        $scriptsPath = self::normalizePath($scriptsPath);
        $isWindows = self::isWindows();

        // Check for virtual environment
        $venvPythonPath = $isWindows
            ? self::buildPath($scriptsPath, '.venv', 'Scripts', 'python.exe')
            : self::buildPath($scriptsPath, '.venv', 'bin', 'python');

        $venvActivationPath = $isWindows
            ? self::buildPath($scriptsPath, '.venv', 'Scripts', 'activate.bat')
            : self::buildPath($scriptsPath, '.venv', 'bin', 'activate');

        $scriptPath = self::buildPath($scriptsPath, $scriptName);
        $argsString = implode(' ', array_map('escapeshellarg', $args));

        $command = '';
        $venvUsed = false;

        if (file_exists($venvPythonPath)) {
            // Use virtual environment python directly
            $command = "\"{$venvPythonPath}\" \"{$scriptPath}\" {$argsString}";
            $venvUsed = true;
            Log::info('Using virtual environment python directly');
        } elseif (file_exists($venvActivationPath)) {
            // Use virtual environment with activation
            if ($isWindows) {
                $command = "\"{$venvActivationPath}\" && python \"{$scriptPath}\" {$argsString}";
            } else {
                $command = "source \"{$venvActivationPath}\" && python \"{$scriptPath}\" {$argsString}";
            }
            $venvUsed = true;
            Log::info('Using virtual environment with activation');
        } else {
            // Use system python
            $pythonCmd = self::getPythonCommand();
            $command = "{$pythonCmd} \"{$scriptPath}\" {$argsString}";
            Log::info('Using system python');
        }

        return [
            'command' => $command,
            'workingDirectory' => $scriptsPath,
            'venvUsed' => $venvUsed,
            'platform' => self::getOSFamily()
        ];
    }

    /**
     * Check if a process is running (cross-platform)
     *
     * @param string $processName
     * @return bool
     */
    public static function isProcessRunning(string $processName): bool
    {
        if (self::isWindows()) {
            $result = self::executeCommand("tasklist /FI \"IMAGENAME eq {$processName}\" /NH");
            return stripos($result['outputString'], $processName) !== false;
        } else {
            $result = self::executeCommand("pgrep -f \"{$processName}\"");
            return $result['success'] && !empty($result['output']);
        }
    }

    /**
     * Kill a process (cross-platform)
     *
     * @param string $processIdentifier (PID or process name)
     * @param bool $isProcessId
     * @return array
     */
    public static function killProcess(string $processIdentifier, bool $isProcessId = false): array
    {
        if (self::isWindows()) {
            if ($isProcessId) {
                $command = "taskkill /PID {$processIdentifier} /F";
            } else {
                $command = "taskkill /IM \"{$processIdentifier}\" /F";
            }
        } else {
            if ($isProcessId) {
                $command = "kill -9 {$processIdentifier}";
            } else {
                $command = "pkill -f \"{$processIdentifier}\"";
            }
        }

        return self::executeCommand($command);
    }

    /**
     * Get system information
     *
     * @return array
     */
    public static function getSystemInfo(): array
    {
        return [
            'os_family' => self::getOSFamily(),
            'php_os' => PHP_OS,
            'is_windows' => self::isWindows(),
            'is_unix' => self::isUnix(),
            'directory_separator' => DIRECTORY_SEPARATOR,
            'path_separator' => PATH_SEPARATOR,
            'python_command' => self::getPythonCommand(),
            'php_version' => PHP_VERSION,
            'current_directory' => getcwd(),
        ];
    }

    /**
     * Create directory with proper permissions (cross-platform)
     *
     * @param string $path
     * @param int $permissions
     * @param bool $recursive
     * @return bool
     */
    public static function createDirectory(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        if (file_exists($path)) {
            return is_dir($path);
        }

        // On Windows, ignore permissions
        if (self::isWindows()) {
            return mkdir($path, 0777, $recursive);
        } else {
            return mkdir($path, $permissions, $recursive);
        }
    }

    /**
     * Get file tail (cross-platform equivalent of 'tail -n')
     *
     * @param string $filepath
     * @param int $lines
     * @return array
     */
    public static function getFileTail(string $filepath, int $lines = 20): array
    {
        if (!file_exists($filepath)) {
            return [];
        }

        if (self::isWindows()) {
            // Use PowerShell Get-Content for Windows
            $command = "powershell \"Get-Content '{$filepath}' -Tail {$lines}\"";
        } else {
            // Use standard tail command for Unix-like systems
            $command = "tail -n {$lines} \"{$filepath}\"";
        }

        $result = self::executeCommand($command);
        return $result['success'] ? $result['output'] : [];
    }
}
