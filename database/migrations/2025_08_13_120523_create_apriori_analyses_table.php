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
        Schema::create('apriori_analyses', function (Blueprint $table) {
            $table->id();

            // Data association rule
            $table->json('rules'); // Array berisi ["Jersey Mills", "Sepatu Bola Ortus"]
            $table->decimal('confidence', 5, 2);
            $table->decimal('support', 5, 2);
            $table->date('transaction_date');
            $table->text('description'); // "Jika membeli Jersey Mills maka membeli Sepatu Bola Ortus"

            $table->timestamps();

            // Indexes
            $table->index('transaction_date');
            $table->index('confidence');
            $table->index('support');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apriori_analyses');
    }
};
