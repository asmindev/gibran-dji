<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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
    protected $description = 'Manage queue worker process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

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

        // Start worker in background
        $command = 'php ' . base_path('artisan') . ' queue:work --queue=model-training --timeout=600 --sleep=3 --daemon > /dev/null 2>&1 &';
        exec($command);

        sleep(2); // Wait a moment

        if ($this->isWorkerRunning()) {
            $this->info('✅ Queue worker started successfully');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to start queue worker');
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

        // Kill all queue:work processes
        exec('pkill -f "queue:work"');

        sleep(2); // Wait a moment

        if (!$this->isWorkerRunning()) {
            $this->info('✅ Queue worker stopped successfully');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to stop queue worker');
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
        if ($this->isWorkerRunning()) {
            $processes = $this->getWorkerProcesses();
            $this->info('✅ Queue worker is running');

            if (!empty($processes)) {
                $this->table(['PID', 'Command'], $processes);
            }
        } else {
            $this->warn('❌ Queue worker is not running');
        }

        // Also check queue size
        $queueSize = \Illuminate\Support\Facades\Queue::size('model-training');
        $this->info("Jobs in queue: {$queueSize}");

        return Command::SUCCESS;
    }

    private function isWorkerRunning()
    {
        $processes = $this->getWorkerProcesses();
        return !empty($processes);
    }

    private function getWorkerProcesses()
    {
        $output = [];
        exec('ps aux | grep -E "queue:work.*model-training" | grep -v grep', $output);

        $processes = [];
        foreach ($output as $line) {
            if (preg_match('/(\d+).*?(php.*queue:work.*)/', $line, $matches)) {
                $processes[] = [$matches[1], $matches[2]];
            }
        }

        return $processes;
    }
}
