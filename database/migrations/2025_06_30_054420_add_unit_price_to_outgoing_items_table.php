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
        Schema::table('outgoing_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
            $table->string('customer')->nullable()->after('unit_price');
            $table->string('purpose')->nullable()->after('customer');
            $table->text('notes')->nullable()->after('purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'customer', 'purpose', 'notes']);
        });
    }
};
