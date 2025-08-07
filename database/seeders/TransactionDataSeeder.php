<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\OutgoingItem;
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

        // 2. Buat 8 Item Olahraga
        $items = [
            [
                'item_code' => 'SPB-001',
                'item_name' => 'Sepatu Bola Ortus',
                'category_id' => $sportsCategory->id,
                'stock' => 200, // Stock yang lebih besar untuk simulasi
                'minimum_stock' => 10,
                'purchase_price' => 180000,
                'selling_price' => 250000,
                'description' => 'Sepatu bola berkualitas tinggi merk Ortus'
            ],
            [
                'item_code' => 'KK-002',
                'item_name' => 'Kaos Kaki Avo',
                'category_id' => $sportsCategory->id,
                'stock' => 200,
                'minimum_stock' => 20,
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'description' => 'Kaos kaki olahraga merk Avo'
            ],
            [
                'item_code' => 'JER-003',
                'item_name' => 'Jersey Mills',
                'category_id' => $sportsCategory->id,
                'stock' => 150,
                'minimum_stock' => 15,
                'purchase_price' => 80000,
                'selling_price' => 120000,
                'description' => 'Jersey olahraga merk Mills'
            ],
            [
                'item_code' => 'TS-004',
                'item_name' => 'Tali Sepatu Kipzkapz',
                'category_id' => $sportsCategory->id,
                'stock' => 200,
                'minimum_stock' => 50,
                'purchase_price' => 8000,
                'selling_price' => 15000,
                'description' => 'Tali sepatu berkualitas merk Kipzkapz'
            ],
            [
                'item_code' => 'PL-005',
                'item_name' => 'Piala',
                'category_id' => $sportsCategory->id,
                'stock' => 150,
                'minimum_stock' => 5,
                'purchase_price' => 45000,
                'selling_price' => 75000,
                'description' => 'Piala untuk kejuaraan olahraga'
            ],
            [
                'item_code' => 'SPF-006',
                'item_name' => 'Sepatu Futsal',
                'category_id' => $sportsCategory->id,
                'stock' => 150,
                'minimum_stock' => 8,
                'purchase_price' => 150000,
                'selling_price' => 220000,
                'description' => 'Sepatu futsal untuk indoor'
            ],
            [
                'item_code' => 'BB-007',
                'item_name' => 'Bola Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 200,
                'minimum_stock' => 5,
                'purchase_price' => 120000,
                'selling_price' => 180000,
                'description' => 'Bola basket standar kompetisi'
            ],
            [
                'item_code' => 'JB-008',
                'item_name' => 'Jersey Basket',
                'category_id' => $sportsCategory->id,
                'stock' => 150,
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

        for ($i = 1; $i <= 1000; $i++) {
            // Tanggal random antara 1 Januari 2025 - 1 Agustus 2025 (8 bulan)
            $startDate = Carbon::create(2025, 1, 1);
            $endDate = Carbon::create(2025, 8, 7);
            $daysDiff = $startDate->diffInDays($endDate);
            $transactionDate = $startDate->addDays($faker->numberBetween(0, $daysDiff));

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

            // Buat outgoing items untuk setiap item dalam transaksi
            foreach ($selectedItems as $itemId) {
                $item = $allItems->find($itemId);

                OutgoingItem::create([
                    'outgoing_date' => $transactionDate,
                    'item_id' => $itemId,
                    'quantity' => $faker->numberBetween(5, 8),
                    'unit_price' => $item->selling_price,
                    'transaction_id' => 'TRX-' . strtoupper($faker->unique()->lexify('??????')),
                    'notes' => "Transaksi simulasi tanggal " . $transactionDate->format('d-m-Y')
                ]);

                // Update stock item
                // Pastikan stock tidak negatif

                $item->update([
                    "stock" => max(0, $item->stock - 1) // Kurangi stock tapi tidak boleh negatif
                ]);
            }
        }

        $this->command->info('✅ Berhasil membuat:');
        $this->command->info('   - 1 Kategori: Peralatan Olahraga');
        $this->command->info('   - 8 Item olahraga');
        $this->command->info('   - 1000 transaksi simulasi (Mei-Juli 2025)');
        $this->command->info('   - Data mencakup 3 bulan untuk training ML');
        $this->command->info('   - Stock item telah disesuaikan');
    }
}
