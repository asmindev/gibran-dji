<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'item_code' => 'LAPTOP001',
                'item_name' => 'Dell Laptop XPS 13',
                'category_id' => 1, // Electronics
                'stock' => 25,
                'purchase_price' => 800.00,
                'selling_price' => 1200.00,
                'description' => 'High-performance laptop for business use'
            ],
            [
                'item_code' => 'DESK001',
                'item_name' => 'Office Desk Standing',
                'category_id' => 2, // Furniture
                'stock' => 15,
                'purchase_price' => 150.00,
                'selling_price' => 250.00,
                'description' => 'Adjustable standing desk'
            ],
            [
                'item_code' => 'PEN001',
                'item_name' => 'Ballpoint Pens Set',
                'category_id' => 3, // Stationery
                'stock' => 5, // Low stock to test alert
                'purchase_price' => 2.50,
                'selling_price' => 5.00,
                'description' => 'Pack of 10 blue ballpoint pens'
            ],
            [
                'item_code' => 'DRILL001',
                'item_name' => 'Electric Drill',
                'category_id' => 4, // Tools
                'stock' => 12,
                'purchase_price' => 45.00,
                'selling_price' => 75.00,
                'description' => 'Cordless electric drill with bits'
            ],
            [
                'item_code' => 'BOOK001',
                'item_name' => 'Laravel Programming Guide',
                'category_id' => 5, // Books
                'stock' => 8, // Low stock
                'purchase_price' => 25.00,
                'selling_price' => 45.00,
                'description' => 'Complete guide to Laravel development'
            ],
            [
                'item_code' => 'MOUSE001',
                'item_name' => 'Wireless Mouse',
                'category_id' => 1, // Electronics
                'stock' => 50,
                'purchase_price' => 15.00,
                'selling_price' => 25.00,
                'description' => 'Ergonomic wireless mouse'
            ]
        ];

        foreach ($items as $item) {
            \App\Models\Item::create($item);
        }
    }
}
