<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the column to accommodate 'sales' and 'restock'
        DB::statement("ALTER TABLE stock_predictions MODIFY COLUMN prediction_type VARCHAR(20) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original constraint if needed
        DB::statement("ALTER TABLE stock_predictions MODIFY COLUMN prediction_type ENUM('sales', 'restock') NOT NULL");
    }
};
