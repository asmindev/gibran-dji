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

        // 2. Buat 8 Item Olahraga dengan minimum_stock
        $items = [
            [
                'item_name' => 'Sepatu Bola Ortus',
                'category_id' => $sportsCategory->id,
                'stock' => 500, // Stock yang lebih besar untuk simulasi penjualan tinggi
                'minimum_stock' => 20,
                'purchase_price' => 180000,
                'selling_price' => 250000,
                'description' => 'Sepatu bola berkualitas tinggi merk Ortus'
            ],
            [
                'item_name' => 'Kaos Kaki Avo',
                'category_id' => $sportsCategory->id,
                'stock' => 800,
                'minimum_stock' => 50,
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'description' => 'Kaos kaki olahraga merk Avo'
            ],
            [
                'item_name' => 'Jersey Mills',
                'category_id' => $sportsCategory->id,
                'stock' => 300,
                'minimum_stock' => 15,
                'purchase_price' => 80000,
                'selling_price' => 120000,
                'description' => 'Jersey olahraga merk Mills'
            ],
            [
                'item_name' => 'Tali Sepatu Kipzkapz',
                'category_id' => $sportsCategory->id,
                'stock' => 1000,
                'minimum_stock' => 100,
                'purchase_price' => 8000,
                'selling_price' => 15000,
                'description' => 'Tali sepatu berkualitas merk Kipzkapz'
            ],
            [
                'item_name' => 'Piala',
                'category_id' => $sportsCategory->id,
                'stock' => 200,
                'minimum_stock' => 5,
                'purchase_price' => 45000,
                'selling_price' => 75000,
                'description' => 'Piala untuk kejuaraan olahraga'
            ],
            [
                'item_name' => 'Sepatu Futsal',
                'category_id' => $sportsCategory->id,
                'stock' => 400,
                'minimum_stock' => 25,
                'purchase_price' => 150000,
                'selling_price' => 220000,
                'description' => 'Sepatu futsal untuk indoor'
            ],
            [
                'item_name' => 'Bola Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 300,
                'minimum_stock' => 10,
                'purchase_price' => 120000,
                'selling_price' => 180000,
                'description' => 'Bola basket standar kompetisi'
            ],
            [
                'item_name' => 'Jersey Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 250,
                'minimum_stock' => 12,
                'purchase_price' => 70000,
                'selling_price' => 110000,
                'description' => 'Jersey basket untuk latihan dan pertandingan'
            ]
        ];

        foreach ($items as $itemData) {
            Item::create($itemData);
        }

        // 3. Ambil semua item yang baru dibuat
        $allItems = Item::all();
        $sepatuBolaOrtus = $allItems->where('item_name', 'Sepatu Bola Ortus')->first();
        $kaosKakiAvo = $allItems->where('item_name', 'Kaos Kaki Avo')->first();
        $jerseyMills = $allItems->where('item_name', 'Jersey Mills')->first();
        $taliSepatu = $allItems->where('item_name', 'Tali Sepatu Kipzkapz')->first();
        $piala = $allItems->where('item_name', 'Piala')->first();
        $sepatuFutsal = $allItems->where('item_name', 'Sepatu Futsal')->first();
        $bolaBasket = $allItems->where('item_name', 'Bola Basket')->first();
        $jerseyBasket = $allItems->where('item_name', 'Jersey Basket')->first();

        // 4. Buat 100 transaksi simulasi (OutgoingItems)
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
            'Club Sepak Bola Junior'
        ];

        // Pattern untuk menciptakan asosiasi yang kuat
        $strongAssociations = [
            // Sepatu Bola Ortus & Kaos Kaki Avo (70% kemungkinan muncul bersama)
            [$sepatuBolaOrtus->id, $kaosKakiAvo->id],
            // Jersey Mills & Sepatu Bola Ortus (60% kemungkinan muncul bersama)
            [$jerseyMills->id, $sepatuBolaOrtus->id],
            // Sepatu Futsal & Jersey Mills (50% kemungkinan)
            [$sepatuFutsal->id, $jerseyMills->id],
            // Bola Basket & Jersey Basket (80% kemungkinan)
            [$bolaBasket->id, $jerseyBasket->id]
        ];

        // Buat transaksi per hari dengan minimal 8 transaksi per hari
        $startDate = Carbon::create(2025, 5, 1);
        $endDate = Carbon::today();

        $currentDate = $startDate->copy();
        $totalTransactions = 0;
        $totalIncomingItems = 0;

        while ($currentDate->lte($endDate)) {
            // Cek apakah perlu restocking (setiap 3-5 hari sekali atau jika ada item stock rendah)
            $needsRestocking = ($currentDate->day % $faker->numberBetween(3, 5) == 0);

            // Atau jika ada item yang stocknya sangat rendah (di bawah 20% dari minimum stock)
            $hasLowStockItems = $allItems->some(function ($item) {
                return $item->fresh()->stock <= ($item->minimum_stock * 0.2);
            });

            if ($needsRestocking || $hasLowStockItems) {
                // Lakukan restocking untuk item yang stocknya rendah
                foreach ($allItems as $item) {
                    $currentStock = $item->fresh()->stock; // Ambil stock terkini
                    $minimumStock = $item->minimum_stock;

                    // Jika stock di bawah atau mendekati minimum stock (dengan buffer 50%)
                    if ($currentStock <= ($minimumStock * 1.5)) {
                        // Restock untuk mencapai 3-5x minimum stock
                        $targetStock = $minimumStock * $faker->numberBetween(3, 5);
                        $restockQuantity = max(50, $targetStock - $currentStock);

                        IncomingItem::create([
                            'transaction_id' => 'IN-' . strtoupper($faker->unique()->lexify('??????')),
                            'incoming_date' => $currentDate->copy()->addHours($faker->numberBetween(6, 8)), // Pagi hari untuk restocking
                            'item_id' => $item->id,
                            'quantity' => $restockQuantity,
                            'unit_cost' => $item->purchase_price,
                            'notes' => "Restocking otomatis (stock: {$currentStock}, min: {$minimumStock}) tanggal " . $currentDate->format('d-m-Y')
                        ]);

                        // Update stock item
                        $item->update([
                            'stock' => $currentStock + $restockQuantity
                        ]);

                        $totalIncomingItems++;
                    }
                }
            }            // Minimal 8 transaksi per hari, maksimal 15 transaksi per hari
            $dailyTransactions = $faker->numberBetween(8, 15);

            for ($i = 0; $i < $dailyTransactions; $i++) {
                // Waktu transaksi dalam hari (jam operasional 8:00 - 20:00)
                $transactionTime = $currentDate->copy()->addHours($faker->numberBetween(8, 20))->addMinutes($faker->numberBetween(0, 59));

                // Buat transaction_id yang sama untuk semua items dalam 1 customer session
                $sessionTransactionId = 'TRX-' . strtoupper($faker->unique()->lexify('??????'));

                // Pilih customer random
                $customer = $faker->randomElement($customers);

                // Tentukan jumlah item per transaksi (2-4 item)
                $itemCount = $faker->numberBetween(2, 4);

                $selectedItems = [];

                // 30% kemungkinan gunakan strong association
                if ($faker->numberBetween(1, 100) <= 30) {
                    $association = $faker->randomElement($strongAssociations);
                    $selectedItems = $association;

                    // Jika masih perlu item tambahan
                    if ($itemCount > 2) {
                        $remainingCount = $itemCount - 2;
                        $availableItems = $allItems->whereNotIn('id', $selectedItems)->pluck('id')->toArray();
                        $additionalItems = $faker->randomElements($availableItems, $remainingCount);
                        $selectedItems = array_merge($selectedItems, $additionalItems);
                    }
                } else {
                    // Pilih item secara random
                    $selectedItems = $faker->randomElements($allItems->pluck('id')->toArray(), $itemCount);
                }

                // Track apakah session ini berhasil membuat minimal 1 item
                $sessionSuccessful = false;

                // Buat outgoing items untuk setiap item dalam transaksi
                foreach ($selectedItems as $itemId) {
                    $item = $allItems->find($itemId);
                    $currentStock = $item->fresh()->stock; // Ambil stock terkini

                    // Tentukan quantity yang realistis berdasarkan stock yang tersedia
                    $maxQuantity = min(5, max(1, intval($currentStock / 10))); // Maksimal 5 atau 10% dari stock
                    $quantity = $faker->numberBetween(1, $maxQuantity);

                    // Hanya buat transaksi jika stock mencukupi
                    if ($currentStock >= $quantity) {
                        OutgoingItem::create([
                            'outgoing_date' => $transactionTime,
                            'item_id' => $itemId,
                            'quantity' => $quantity,
                            'unit_price' => $item->selling_price,
                            'transaction_id' => $sessionTransactionId, // Gunakan transaction_id yang sama
                            'notes' => "Transaksi simulasi tanggal " . $transactionTime->format('d-m-Y H:i')
                        ]);

                        // Update stock item dengan quantity yang benar
                        $item->update([
                            "stock" => max(0, $currentStock - $quantity)
                        ]);

                        $sessionSuccessful = true;
                    }
                }

                // Hitung hanya jika session berhasil membuat minimal 1 item
                if ($sessionSuccessful) {
                    $totalTransactions++;
                }
            }            // Pindah ke hari berikutnya
            $currentDate->addDay();
        }

        // Hitung total hari dan rata-rata transaksi per hari
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalLineItems = OutgoingItem::count();

        $this->command->info('✅ Berhasil membuat:');
        $this->command->info('   - 1 Kategori: Peralatan Olahraga');
        $this->command->info('   - 8 Item olahraga dengan minimum_stock');
        $this->command->info("   - {$totalTransactions} transaksi customer sessions");
        $this->command->info("   - {$totalLineItems} line items penjualan total");
        $this->command->info("   - {$totalIncomingItems} transaksi pembelian/restocking (incoming)");
        $this->command->info("   - {$totalDays} hari dengan minimal 8 transaksi per hari");
        $this->command->info('   - Rata-rata ' . round($totalTransactions / $totalDays, 1) . ' customer sessions per hari');
        $this->command->info('   - Rata-rata ' . round($totalLineItems / $totalDays, 1) . ' items terjual per hari');
        $this->command->info('   - Restocking otomatis setiap 3-5 hari atau saat stock rendah');
        $this->command->info('   - Target restocking: 3-5x minimum stock per item');
        $this->command->info('   - Data optimal untuk Apriori analysis (transaction grouping)');
        $this->command->info('   - Stock item terjaga dengan sistem restocking cerdas');
    }
}
