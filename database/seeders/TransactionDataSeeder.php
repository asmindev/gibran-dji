<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\OutgoingItem;
use App\Models\IncomingItem;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TransactionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // 1. Buat Kategori Olahraga
        $sportsCategory = Category::create([
            'name' => 'Peralatan Olahraga',
            'description' => 'Kategori untuk semua peralatan dan perlengkapan olahraga'
        ]);

        // 2. Buat 20 Item Olahraga dengan minimum_stock
        $items = [
            [
                'item_name' => 'Sepatu Bola Ortus',
                'category_id' => $sportsCategory->id,
                'stock' => 0, // Initial stock 0, akan di-restock
                'minimum_stock' => 20,
                'purchase_price' => 180000,
                'selling_price' => 250000,
                'description' => 'Sepatu bola berkualitas tinggi merk Ortus'
            ],
            [
                'item_name' => 'Kaos Kaki Avo',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 50,
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'description' => 'Kaos kaki olahraga merk Avo'
            ],
            [
                'item_name' => 'Jersey Mills',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 15,
                'purchase_price' => 80000,
                'selling_price' => 120000,
                'description' => 'Jersey olahraga merk Mills'
            ],
            [
                'item_name' => 'Tali Sepatu Kipzkapz',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 100,
                'purchase_price' => 8000,
                'selling_price' => 15000,
                'description' => 'Tali sepatu berkualitas merk Kipzkapz'
            ],
            [
                'item_name' => 'Piala',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 5,
                'purchase_price' => 45000,
                'selling_price' => 75000,
                'description' => 'Piala untuk kejuaraan olahraga'
            ],
            [
                'item_name' => 'Sepatu Futsal',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 25,
                'purchase_price' => 150000,
                'selling_price' => 220000,
                'description' => 'Sepatu futsal untuk indoor'
            ],
            [
                'item_name' => 'Bola Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 10,
                'purchase_price' => 120000,
                'selling_price' => 180000,
                'description' => 'Bola basket standar kompetisi'
            ],
            [
                'item_name' => 'Jersey Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 12,
                'purchase_price' => 70000,
                'selling_price' => 110000,
                'description' => 'Jersey basket untuk latihan dan pertandingan'
            ],
            [
                'item_name' => 'Bola Sepak',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 15,
                'purchase_price' => 100000,
                'selling_price' => 150000,
                'description' => 'Bola sepak standar FIFA'
            ],
            [
                'item_name' => 'Raket Badminton',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 8,
                'purchase_price' => 200000,
                'selling_price' => 300000,
                'description' => 'Raket badminton profesional'
            ],
            [
                'item_name' => 'Shuttlecock',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 100,
                'purchase_price' => 2000,
                'selling_price' => 3500,
                'description' => 'Shuttlecock bulu angsa'
            ],
            [
                'item_name' => 'Raket Tenis',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 6,
                'purchase_price' => 250000,
                'selling_price' => 380000,
                'description' => 'Raket tenis carbon fiber'
            ],
            [
                'item_name' => 'Bola Tenis',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 30,
                'purchase_price' => 8000,
                'selling_price' => 12000,
                'description' => 'Bola tenis ITF approved'
            ],
            [
                'item_name' => 'Sepatu Lari Nike',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 18,
                'purchase_price' => 300000,
                'selling_price' => 450000,
                'description' => 'Sepatu lari Nike Air Max'
            ],
            [
                'item_name' => 'Celana Training',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 25,
                'purchase_price' => 60000,
                'selling_price' => 95000,
                'description' => 'Celana training polyester'
            ],
            [
                'item_name' => 'Jaket Olahraga',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 20,
                'purchase_price' => 120000,
                'selling_price' => 185000,
                'description' => 'Jaket olahraga windbreaker'
            ],
            [
                'item_name' => 'Sarung Tangan Kiper',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 10,
                'purchase_price' => 85000,
                'selling_price' => 130000,
                'description' => 'Sarung tangan kiper latex grip'
            ],
            [
                'item_name' => 'Matras Yoga',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 15,
                'purchase_price' => 75000,
                'selling_price' => 120000,
                'description' => 'Matras yoga anti slip 6mm'
            ],
            [
                'item_name' => 'Dumbbell 5kg',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 8,
                'purchase_price' => 150000,
                'selling_price' => 220000,
                'description' => 'Dumbbell 5kg iron cast'
            ],
            [
                'item_name' => 'Botol Minum Olahraga',
                'category_id' => $sportsCategory->id,
                'stock' => 0,
                'minimum_stock' => 40,
                'purchase_price' => 25000,
                'selling_price' => 45000,
                'description' => 'Botol minum sport 750ml BPA free'
            ]
        ];

        foreach ($items as $itemData) {
            Item::create($itemData);
        }

        // 3. Ambil semua item yang baru dibuat
        $allItems = Item::all();

        // 4. Sistem transaksi baru: Restocking mingguan + Penjualan harian
        $customers = [
            'Tim Sepak Bola Garuda FC',
            'Sekolah SMA Negeri 1',
            'Club Futsal Bintang',
            'Tim Basket Warriors',
            'Universitas Indonesia',
            'Club Olahraga Persada',
            'Sekolah SMP 5',
            'Tim Volley Eagles',
            'Akademi Olahraga Prima',
            'Club Sepak Bola Junior',
            'Sekolah SMA Negeri 3',
            'Club Badminton Prestasi',
            'Tim Tenis Muda',
            'Gym Fitness Center',
            'Yoga Studio Sehat'
        ];

        // Periode transaksi: Februari 2025 hingga sekarang
        $startDate = Carbon::create(2025, 2, 1);
        $endDate = Carbon::today();

        $currentDate = $startDate->copy();
        $totalTransactions = 0;
        $totalIncomingItems = 0;
        $totalOutgoingItems = 0;

        // Track penjualan per minggu per item untuk restocking
        $weeklySales = [];

        while ($currentDate->lte($endDate)) {
            $weekOfYear = $currentDate->format('Y-W');
            $isMonday = $currentDate->dayOfWeek === Carbon::MONDAY;

            // Inisialisasi tracking penjualan mingguan
            if (!isset($weeklySales[$weekOfYear])) {
                $weeklySales[$weekOfYear] = array_fill_keys($allItems->pluck('id')->toArray(), 0);
            }

            // RESTOCKING SETIAP HARI SENIN (awal minggu)
            if ($isMonday) {
                $this->command->info("Restocking untuk minggu {$weekOfYear}...");

                foreach ($allItems as $item) {
                    $currentStock = $item->fresh()->stock;

                    // Perkiraan penjualan per minggu: minimal 10 + random 5-15
                    $estimatedWeeklySales = $faker->numberBetween(15, 25);

                    // Restock quantity = estimasi penjualan + buffer 20-50%
                    $restockQuantity = intval($estimatedWeeklySales * $faker->numberBetween(120, 150) / 100);

                    // Minimal restock 20 unit
                    $restockQuantity = max(20, $restockQuantity);

                    IncomingItem::create([
                        'transaction_id' => 'IN-' . $weekOfYear . '-' . strtoupper($faker->lexify('???')),
                        'incoming_date' => $currentDate->copy()->addHours($faker->numberBetween(7, 9)),
                        'item_id' => $item->id,
                        'quantity' => $restockQuantity,
                        'unit_cost' => $item->purchase_price,
                        'notes' => "Restocking mingguan {$weekOfYear} - estimasi penjualan: {$estimatedWeeklySales}"
                    ]);

                    // Update stock
                    $item->update(['stock' => $currentStock + $restockQuantity]);
                    $totalIncomingItems++;
                }
            }

            // PENJUALAN HARIAN
            // Pastikan setiap produk terjual minimal 2 kali per minggu (untuk mencapai minimal 10 qty/minggu)
            $dailySalesTarget = $faker->numberBetween(8, 15); // Total transaksi customer per hari

            for ($i = 0; $i < $dailySalesTarget; $i++) {
                $transactionTime = $currentDate->copy()
                    ->addHours($faker->numberBetween(8, 20))
                    ->addMinutes($faker->numberBetween(0, 59));

                $sessionTransactionId = 'TRX-' . strtoupper($faker->unique()->lexify('??????'));
                $customer = $faker->randomElement($customers);

                // Jumlah item per transaksi (1-4 item)
                $itemCount = $faker->numberBetween(1, 4);

                // Pilih items random
                $selectedItems = $faker->randomElements($allItems->pluck('id')->toArray(), $itemCount);
                $sessionSuccessful = false;

                foreach ($selectedItems as $itemId) {
                    $item = $allItems->find($itemId);
                    $currentStock = $item->fresh()->stock;

                    // Quantity berdasarkan hari dalam minggu untuk memastikan minimal 10/minggu
                    $dayOfWeek = $currentDate->dayOfWeek;
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Senin-Jumat lebih aktif
                        $baseQuantity = $faker->numberBetween(2, 5);
                    } else { // Weekend
                        $baseQuantity = $faker->numberBetween(1, 3);
                    }

                    $quantity = min($baseQuantity, intval($currentStock / 2)); // Maksimal setengah stock
                    $quantity = max(1, $quantity); // Minimal 1

                    if ($currentStock >= $quantity && $quantity > 0) {
                        OutgoingItem::create([
                            'outgoing_date' => $transactionTime,
                            'item_id' => $itemId,
                            'quantity' => $quantity,
                            'unit_price' => $item->selling_price,
                            'transaction_id' => $sessionTransactionId,
                            'notes' => "Penjualan {$transactionTime->format('d-m-Y')} - {$customer}"
                        ]);

                        // Update stock
                        $item->update(['stock' => $currentStock - $quantity]);

                        // Track penjualan mingguan
                        $weeklySales[$weekOfYear][$itemId] += $quantity;

                        $sessionSuccessful = true;
                        $totalOutgoingItems++;
                    }
                }

                if ($sessionSuccessful) {
                    $totalTransactions++;
                }
            }

            // TAMBAHAN PENJUALAN untuk produk yang belum mencapai target 10/minggu
            if ($currentDate->dayOfWeek === Carbon::SUNDAY) { // Akhir minggu, cek target
                foreach ($allItems as $item) {
                    $soldThisWeek = $weeklySales[$weekOfYear][$item->id] ?? 0;

                    if ($soldThisWeek < 10) {
                        $needed = 10 - $soldThisWeek;
                        $currentStock = $item->fresh()->stock;

                        if ($currentStock >= $needed) {
                            $boostTransactionId = 'BOOST-' . strtoupper($faker->lexify('????'));
                            $boostTime = $currentDate->copy()
                                ->addHours($faker->numberBetween(10, 18))
                                ->addMinutes($faker->numberBetween(0, 59));

                            OutgoingItem::create([
                                'outgoing_date' => $boostTime,
                                'item_id' => $item->id,
                                'quantity' => $needed,
                                'unit_price' => $item->selling_price,
                                'transaction_id' => $boostTransactionId,
                                'notes' => "Penjualan tambahan untuk mencapai target mingguan - {$needed} unit"
                            ]);

                            $item->update(['stock' => $currentStock - $needed]);
                            $weeklySales[$weekOfYear][$item->id] += $needed;
                            $totalOutgoingItems++;
                            $totalTransactions++;
                        }
                    }
                }
            }

            $currentDate->addDay();
        }

        // Hitung statistik
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalWeeks = intval($totalDays / 7);

        $this->command->info('✅ Berhasil membuat sistem transaksi baru:');
        $this->command->info('   - 1 Kategori: Peralatan Olahraga');
        $this->command->info("   - 20 Item olahraga dengan minimum_stock");
        $this->command->info("   - {$totalIncomingItems} transaksi restocking (setiap Senin)");
        $this->command->info("   - {$totalOutgoingItems} line items penjualan total");
        $this->command->info("   - {$totalTransactions} customer sessions");
        $this->command->info("   - {$totalDays} hari (Februari - September 2025)");
        $this->command->info("   - {$totalWeeks} minggu dengan restocking sistematis");
        $this->command->info('   - Rata-rata ' . round($totalTransactions / $totalDays, 1) . ' transaksi per hari');
        $this->command->info('   - Setiap produk minimal 10 unit terjual per minggu');
        $this->command->info('   - Stock balance terjaga (restocking > penjualan)');
        $this->command->info('   - Sistem restocking: estimasi penjualan + buffer 20-50%');
    }
}
