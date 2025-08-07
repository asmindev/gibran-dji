<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;
use App\Services\PlatformCompatibilityService;

class QueueWorkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:manage {action : start|stop|status|restart}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage queue worker process (Windows and Unix compatible)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        // Show platform info for debugging
        $systemInfo = PlatformCompatibilityService::getSystemInfo();
        $this->line("Platform: {$systemInfo['os_family']}");

        switch ($action) {
            case 'start':
                return $this->startWorker();
            case 'stop':
                return $this->stopWorker();
            case 'status':
                return $this->checkStatus();
            case 'restart':
                return $this->restartWorker();
            default:
                $this->error('Invalid action. Use: start, stop, status, or restart');
                return Command::FAILURE;
        }
    }

    private function startWorker()
    {
        if ($this->isWorkerRunning()) {
            $this->warn('Queue worker is already running');
            return Command::SUCCESS;
        }

        $this->info('Starting queue worker...');

        // Build cross-platform command
        $artisanPath = base_path('artisan');
        $isWindows = PlatformCompatibilityService::isWindows();

        if ($isWindows) {
            // Windows: Use start command to run in background
            $command = "start /B php \"{$artisanPath}\" queue:work --queue=model-training --timeout=600 --sleep=3 --daemon > nul 2>&1";
        } else {
            // Unix: Use traditional background process
            $command = "php \"{$artisanPath}\" queue:work --queue=model-training --timeout=600 --sleep=3 --daemon > /dev/null 2>&1 &";
        }

        $result = PlatformCompatibilityService::executeCommand($command);

        sleep(2); // Wait a moment

        if ($this->isWorkerRunning()) {
            $this->info('✅ Queue worker started successfully');
            $this->line("Platform: " . (PlatformCompatibilityService::isWindows() ? 'Windows' : 'Unix'));
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to start queue worker');
            $this->line("Command attempted: {$command}");
            if (!empty($result['outputString'])) {
                $this->line("Output: " . $result['outputString']);
            }
            return Command::FAILURE;
        }
    }

    private function stopWorker()
    {
        if (!$this->isWorkerRunning()) {
            $this->warn('Queue worker is not running');
            return Command::SUCCESS;
        }

        $this->info('Stopping queue worker...');

        // Use cross-platform process killing
        $result = PlatformCompatibilityService::killProcess('queue:work', false);

        if (!$result['success'] && PlatformCompatibilityService::isWindows()) {
            // Fallback for Windows: try killing php processes with queue:work
            $fallbackResult = PlatformCompatibilityService::killProcess('php.exe', false);
            $this->line('Used fallback method for Windows');
        }

        sleep(2); // Wait a moment

        if (!$this->isWorkerRunning()) {
            $this->info('✅ Queue worker stopped successfully');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to stop queue worker');
            $this->line('You may need to manually stop the process');

            // Show running processes for manual intervention
            $processes = $this->getWorkerProcesses();
            if (!empty($processes)) {
                $this->table(['PID', 'Command'], $processes);
                if (PlatformCompatibilityService::isWindows()) {
                    $this->line('Manual stop: taskkill /PID <PID> /F');
                } else {
                    $this->line('Manual stop: kill -9 <PID>');
                }
            }

            return Command::FAILURE;
        }
    }

    private function restartWorker()
    {
        $this->info('Restarting queue worker...');
        $this->stopWorker();
        sleep(1);
        return $this->startWorker();
    }

    private function checkStatus()
    {
        $systemInfo = PlatformCompatibilityService::getSystemInfo();
        $this->line("Checking queue worker status on {$systemInfo['os_family']}...");

        if ($this->isWorkerRunning()) {
            $processes = $this->getWorkerProcesses();
            $this->info('✅ Queue worker is running');

            if (!empty($processes)) {
                $this->table(['PID', 'Command'], $processes);
            } else {
                $this->warn('Worker appears to be running but process details not found');
            }
        } else {
            $this->warn('❌ Queue worker is not running');

            // Provide platform-specific start instructions
            if (PlatformCompatibilityService::isWindows()) {
                $this->line('To start: php artisan queue:manage start');
                $this->line('Alternative: php artisan queue:work --queue=model-training');
            } else {
                $this->line('To start: php artisan queue:manage start');
                $this->line('Alternative: php artisan queue:work --queue=model-training --daemon');
            }
        }

        // Also check queue size
        try {
            $queueSize = \Illuminate\Support\Facades\Queue::size('model-training');
            $this->info("Jobs in model-training queue: {$queueSize}");

            // Check if there are any failed jobs
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $this->warn("Failed jobs: {$failedJobs} (check with: php artisan queue:failed)");
            }
        } catch (\Exception $e) {
            $this->warn("Could not check queue size: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }

    private function isWorkerRunning()
    {
        $processes = $this->getWorkerProcesses();
        return !empty($processes);
    }

    private function getWorkerProcesses()
    {
        $processes = [];

        if (PlatformCompatibilityService::isWindows()) {
            // Windows: Use tasklist to find PHP processes with queue:work
            $result = PlatformCompatibilityService::executeCommand('tasklist /FI "IMAGENAME eq php.exe" /FO CSV');

            if ($result['success']) {
                foreach ($result['output'] as $line) {
                    // Skip header line and empty lines
                    if (strpos($line, 'php.exe') !== false) {
                        // Parse CSV output: "Image Name","PID","Session Name","Session#","Mem Usage"
                        $parts = str_getcsv($line);
                        if (count($parts) >= 2) {
                            $pid = $parts[1];

                            // Check if this specific process is running queue:work
                            $cmdResult = PlatformCompatibilityService::executeCommand("wmic process where \"ProcessId={$pid}\" get CommandLine /format:list");

                            if ($cmdResult['success']) {
                                $commandLine = implode(' ', $cmdResult['output']);
                                if (strpos($commandLine, 'queue:work') !== false && strpos($commandLine, 'model-training') !== false) {
                                    // Extract relevant part of command line
                                    $cleanCommand = trim(str_replace(['CommandLine=', '\r', '\n'], '', $commandLine));
                                    if (!empty($cleanCommand) && $cleanCommand !== 'CommandLine=') {
                                        $processes[] = [$pid, $cleanCommand];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Unix/Linux: Use ps and grep
            $result = PlatformCompatibilityService::executeCommand('ps aux | grep -E "queue:work.*model-training" | grep -v grep');

            if ($result['success']) {
                foreach ($result['output'] as $line) {
                    if (preg_match('/(\d+).*?(php.*queue:work.*)/', $line, $matches)) {
                        $processes[] = [$matches[1], $matches[2]];
                    }
                }
            }
        }

        return $processes;
    }
}
