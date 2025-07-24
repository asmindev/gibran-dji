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
        Schema::create('inventory_recommendations', function (Blueprint $table) {
            $table->id();
            $table->json('antecedent_items'); // JSON array of item codes
            $table->json('consequent_items'); // JSON array of item codes
            $table->decimal('support', 8, 4);
            $table->decimal('confidence', 8, 4);
            $table->decimal('lift', 8, 4);
            $table->string('rule_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('analyzed_at');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['is_active', 'confidence']);
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_recommendations');
    }
};
