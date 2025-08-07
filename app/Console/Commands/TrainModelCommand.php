<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TrainStockPredictionModel;
use App\Services\PlatformCompatibilityService;

class TrainModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:train {--force : Force training even if already in progress} {--info : Show system compatibility information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train the stock prediction model (Windows and Unix compatible)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Show system information if requested
        if ($this->option('info')) {
            $this->showSystemInfo();
            return Command::SUCCESS;
        }

        $this->info('Starting stock prediction model training...');

        // Display system compatibility information
        $systemInfo = PlatformCompatibilityService::getSystemInfo();
        $this->line("Platform: {$systemInfo['os_family']} ({$systemInfo['php_os']})");
        $this->line("Python command: {$systemInfo['python_command']}");

        // Check if training is forced
        $force = $this->option('force');

        if (!$force) {
            $currentStatus = \Illuminate\Support\Facades\Cache::get('model_training_status');

            if ($currentStatus === 'in_progress') {
                $this->warn('Model training is already in progress. Use --force to override.');
                return Command::FAILURE;
            }
        }

        // Check Python availability
        if (!$this->checkPythonAvailability()) {
            return Command::FAILURE;
        }

        // Dispatch the training job
        TrainStockPredictionModel::dispatch();

        $this->info('Model training job dispatched successfully!');
        $this->line('You can monitor the progress in the logs or via the web interface.');

        // Show monitoring suggestions based on platform
        if (PlatformCompatibilityService::isWindows()) {
            $this->line('Monitor with: Get-Content storage\\logs\\laravel.log -Tail 20 -Wait');
        } else {
            $this->line('Monitor with: tail -f storage/logs/laravel.log');
        }

        return Command::SUCCESS;
    }

    /**
     * Show detailed system compatibility information
     */
    private function showSystemInfo()
    {
        $this->info('=== SYSTEM COMPATIBILITY INFORMATION ===');

        $systemInfo = PlatformCompatibilityService::getSystemInfo();

        $this->table(
            ['Property', 'Value'],
            [
                ['Operating System Family', $systemInfo['os_family']],
                ['PHP OS', $systemInfo['php_os']],
                ['Is Windows', $systemInfo['is_windows'] ? 'Yes' : 'No'],
                ['Is Unix-like', $systemInfo['is_unix'] ? 'Yes' : 'No'],
                ['Directory Separator', $systemInfo['directory_separator']],
                ['Path Separator', $systemInfo['path_separator']],
                ['Python Command', $systemInfo['python_command']],
                ['PHP Version', $systemInfo['php_version']],
                ['Current Directory', $systemInfo['current_directory']],
            ]
        );

        // Check for Python availability
        $this->info('\n=== PYTHON ENVIRONMENT CHECK ===');
        $this->checkPythonAvailability(true);

        // Check scripts directory
        $this->info('\n=== SCRIPTS DIRECTORY CHECK ===');
        $scriptsPath = PlatformCompatibilityService::buildPath(base_path(), 'scripts');
        $this->line("Scripts path: {$scriptsPath}");
        $this->line("Scripts exists: " . (is_dir($scriptsPath) ? 'Yes' : 'No'));

        if (is_dir($scriptsPath)) {
            $stockPredictorPath = PlatformCompatibilityService::buildPath($scriptsPath, 'stock_predictor.py');
            $this->line("stock_predictor.py exists: " . (file_exists($stockPredictorPath) ? 'Yes' : 'No'));

            // Check for virtual environment
            $venvPath = PlatformCompatibilityService::buildPath($scriptsPath, '.venv');
            $this->line("Virtual environment exists: " . (is_dir($venvPath) ? 'Yes' : 'No'));

            if (is_dir($venvPath)) {
                $venvPythonPath = PlatformCompatibilityService::isWindows()
                    ? PlatformCompatibilityService::buildPath($venvPath, 'Scripts', 'python.exe')
                    : PlatformCompatibilityService::buildPath($venvPath, 'bin', 'python');

                $this->line("Virtual environment Python: " . (file_exists($venvPythonPath) ? 'Yes' : 'No'));
            }
        }
    }

    /**
     * Check if Python is available and can be executed
     *
     * @param bool $verbose
     * @return bool
     */
    private function checkPythonAvailability(bool $verbose = false): bool
    {
        $scriptsPath = PlatformCompatibilityService::buildPath(base_path(), 'scripts');

        if (!is_dir($scriptsPath)) {
            $this->error("Scripts directory not found: {$scriptsPath}");
            return false;
        }

        $stockPredictorPath = PlatformCompatibilityService::buildPath($scriptsPath, 'stock_predictor.py');

        if (!file_exists($stockPredictorPath)) {
            $this->error("stock_predictor.py not found: {$stockPredictorPath}");
            return false;
        }

        // Test Python command
        $commandInfo = PlatformCompatibilityService::buildPythonCommand(
            $scriptsPath,
            'stock_predictor.py',
            ['--help']
        );

        if ($verbose) {
            $this->line("Command to test: {$commandInfo['command']}");
            $this->line("Working directory: {$commandInfo['workingDirectory']}");
            $this->line("Virtual environment used: " . ($commandInfo['venvUsed'] ? 'Yes' : 'No'));
        }

        // Test the command (with a quick timeout)
        $result = PlatformCompatibilityService::executeCommand(
            $commandInfo['command'],
            $commandInfo['workingDirectory']
        );

        if ($result['success'] || strpos($result['outputString'], 'usage:') !== false) {
            if ($verbose) {
                $this->info("✓ Python script is accessible and executable");
            }
            return true;
        } else {
            $this->error("✗ Python script test failed");
            if ($verbose) {
                $this->line("Error output: " . $result['outputString']);
            }
            return false;
        }
    }
}
