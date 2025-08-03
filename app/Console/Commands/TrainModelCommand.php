<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TrainStockPredictionModel;

class TrainModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model:train {--force : Force training even if already in progress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train the stock prediction model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock prediction model training...');

        // Check if training is forced
        $force = $this->option('force');

        if (!$force) {
            $currentStatus = \Illuminate\Support\Facades\Cache::get('model_training_status');

            if ($currentStatus === 'in_progress') {
                $this->warn('Model training is already in progress. Use --force to override.');
                return Command::FAILURE;
            }
        }

        // Dispatch the training job
        TrainStockPredictionModel::dispatch();

        $this->info('Model training job dispatched successfully!');
        $this->line('You can monitor the progress in the logs or via the web interface.');

        return Command::SUCCESS;
    }
}
