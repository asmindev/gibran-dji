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
            $table->integer('prediction'); // Hasil prediksi
            $table->integer('actual')->nullable(); // Actual terjual (diisi kemudian)
            $table->string('product'); // Nama produk
            $table->date('month'); // Bulan prediksi (format: YYYY-MM-01)
            $table->foreignId('item_id')->constrained()->onDelete('cascade'); // Foreign key ke tabel items
            $table->enum('prediction_type', ['sales', 'restock']); // Tipe prediksi (sales atau restock)
            $table->timestamps();

            // Index untuk query yang sering digunakan
            $table->index(['product', 'month']);
            $table->index(['month']);
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
