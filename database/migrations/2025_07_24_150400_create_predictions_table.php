<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->integer('predicted_demand');
            $table->decimal('prediction_confidence', 5, 2); // 0-100%
            $table->date('prediction_period_start');
            $table->date('prediction_period_end');
            $table->json('feature_importance')->nullable(); // Random Forest feature importance
            $table->boolean('is_active')->default(true);
            $table->datetime('analyzed_at');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['item_id', 'is_active']);
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_predictions');
    }
};
