<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Matikan pemeriksaan Foreign Key sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Kosongkan tabel terkait (Order Items & Products)
        DB::table('order_items')->truncate();
        DB::table('products')->truncate();

        // 3. Hidupkan kembali pemeriksaan Foreign Key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 4. Masukkan data produk baru
        $products = [
            [
                'name' => 'Ayam Penyet',
                'category' => 'makanan',
                'price' => 12000,
                'description' => 'Ayam gurih dengan sambal khas Warung Mak Bos.',
                'image' => 'products/ayam-penyet.jpg',
            ],
            [
                'name' => 'Ayam Geprek',
                'category' => 'makanan',
                'price' => 12000,
                'description' => 'Ayam crispy dengan sambal pedas yang menggugah selera.',
                'image' => 'products/ayam-geprek.jpg',
            ],
            [
                'name' => 'Es Teh Manis',
                'category' => 'minuman',
                'price' => 5000,
                'description' => 'Es teh manis segar pelepas dahaga.',
                'image' => 'products/es-teh.jpg',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}