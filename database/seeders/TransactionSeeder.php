<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OutgoingItem;
use App\Models\IncomingItem;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate transaksi dari September - Oktober 2025
     * Minimal 10 transaksi per hari, setiap transaksi minimal 10 item
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai seeding transaksi...');

        // Get all available items
        $items = Item::all();

        if ($items->count() < 10) {
            $this->command->error('❌ Tidak cukup item! Butuh minimal 10 item di database.');
            return;
        }

        $this->command->info("📦 Total items tersedia: {$items->count()}");

        // Clear existing transactions (optional - uncomment jika ingin clear dulu)
        // $this->command->warn('⚠️  Menghapus transaksi lama...');
        // OutgoingItem::truncate();
        // IncomingItem::truncate();

        // Define date range: 1 September - 31 Oktober 2025
        $startDate = Carbon::create(2025, 9, 1); // 1 September 2025
        $endDate = Carbon::create(2025, 10, 31);  // 31 Oktober 2025

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $this->command->info("📅 Periode: {$startDate->format('d M Y')} - {$endDate->format('d M Y')} ({$totalDays} hari)");

        $totalIncomingTransactions = 0;
        $totalOutgoingTransactions = 0;
        $totalIncomingItems = 0;
        $totalOutgoingItems = 0;

        $currentDate = $startDate->copy();

        // Progress bar
        $bar = $this->command->getOutput()->createProgressBar($totalDays);
        $bar->start();

        while ($currentDate <= $endDate) {
            // Generate 10-15 INCOMING transactions per day (restock)
            $incomingTransactionsPerDay = rand(5, 8);

            for ($i = 0; $i < $incomingTransactionsPerDay; $i++) {
                $transactionId = 'IN-' . $currentDate->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

                // Each transaction has 10-20 items
                $itemsInTransaction = rand(10, 20);
                $selectedItems = $items->random(min($itemsInTransaction, $items->count()));

                foreach ($selectedItems as $item) {
                    IncomingItem::create([
                        'item_id' => $item->id,
                        'quantity' => rand(20, 100), // Restock 20-100 unit
                        'incoming_date' => $currentDate->format('Y-m-d'),
                        'transaction_id' => $transactionId,
                        'unit_cost' => rand(50000, 500000), // Cost 50k - 500k
                        'notes' => 'Supplier: ' . $this->getRandomSupplier()
                    ]);
                    $totalIncomingItems++;
                }

                $totalIncomingTransactions++;
            }

            // Generate 10-20 OUTGOING transactions per day (sales)
            $outgoingTransactionsPerDay = rand(10, 20);

            for ($i = 0; $i < $outgoingTransactionsPerDay; $i++) {
                $transactionId = 'OUT-' . $currentDate->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

                // Each transaction has 10-15 items (realistic shopping cart)
                $itemsInTransaction = rand(10, 15);
                $selectedItems = $items->random(min($itemsInTransaction, $items->count()));

                foreach ($selectedItems as $item) {
                    OutgoingItem::create([
                        'item_id' => $item->id,
                        'quantity' => rand(1, 10), // Sales 1-10 unit per item
                        'outgoing_date' => $currentDate->format('Y-m-d'),
                        'transaction_id' => $transactionId,
                        'unit_price' => rand(60000, 600000), // Price 60k - 600k
                        'customer' => $this->getRandomCustomer(),
                        'notes' => 'Seeded transaction'
                    ]);
                    $totalOutgoingItems++;
                }

                $totalOutgoingTransactions++;
            }

            $currentDate->addDay();
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        // Summary
        $this->command->info('✅ Seeding selesai!');
        $this->command->newLine();
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Hari', $totalDays],
                ['Incoming Transactions', $totalIncomingTransactions],
                ['Incoming Items Records', $totalIncomingItems],
                ['Outgoing Transactions', $totalOutgoingTransactions],
                ['Outgoing Items Records', $totalOutgoingItems],
                ['Total Transactions', $totalIncomingTransactions + $totalOutgoingTransactions],
                ['Total Records', $totalIncomingItems + $totalOutgoingItems],
                ['Avg Transactions/Day', round(($totalIncomingTransactions + $totalOutgoingTransactions) / $totalDays, 1)],
            ]
        );
    }

    /**
     * Get random supplier name
     */
    private function getRandomSupplier(): string
    {
        $suppliers = [
            'PT Sportindo Jaya',
            'CV Olahraga Sejahtera',
            'PT Athletic Gear Indonesia',
            'Toko Sport Center',
            'PT Prima Sport Equipment',
            'CV Maju Sport',
            'PT Nusantara Sportwear',
            'Distributor Sport Global',
        ];

        return $suppliers[array_rand($suppliers)];
    }

    /**
     * Get random customer name
     */
    private function getRandomCustomer(): string
    {
        $customers = [
            'Andi Wijaya',
            'Budi Santoso',
            'Citra Dewi',
            'Dedi Kurniawan',
            'Eka Putri',
            'Fajar Ramadhan',
            'Gita Sari',
            'Hendra Gunawan',
            'Indah Permata',
            'Joko Susilo',
            'Kartika Sari',
            'Lestari Wati',
            'Made Wirawan',
            'Nina Amalia',
            'Oscar Pratama',
            'Putri Anggraini',
            'Qori Hidayat',
            'Rini Setiawati',
            'Sandi Wijaya',
            'Tono Sukirman',
            'Udin Petot',
            'Vina Melati',
            'Wawan Setiawan',
            'Yuni Kartika',
            'Zaki Maulana',
        ];

        return $customers[array_rand($customers)];
    }
}
