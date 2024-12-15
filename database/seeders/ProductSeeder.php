<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            for ($productCount = 1; $productCount <= 100; $productCount++) {
                $payload = [
                    'name' => 'Product ' . $productCount,
                    'slug' => 'product-' . $productCount,
                    'seller_id' => User::inRandomOrder()->first()->id,
                    'category_id' => Category::whereNotNull('parent_id')->inRandomOrder()->first()->id,
                    'description' => 'Description ' . $productCount,
                    'stock' => rand(1, 100),
                    'weight' => rand(1, 100),
                    'length' => rand(1, 100),
                    'width' => rand(1, 100),
                    'height' => rand(1, 100),
                    'video' => 'attachment.mp4',
                    'price' => rand(10000, 100000),
                    'images' => [
                        'attachment1.jpg',
                        'attachment2.jpg',
                        'attachment3.jpg',
                        'attachment4.jpg'
                    ],
                    'variations' => [
                        [
                            'name' => 'Warna',
                            'values' => ['Hitam', 'Putih', 'Merah', 'Kuning', 'Hijau']
                        ],
                        [
                            'name' => 'Ukuran',
                            'values' => ['S', 'M', 'L', 'XL', 'XXL']
                        ]
                    ],
                    'reviews' => [
                        [
                            'user_id' => User::inRandomOrder()->first()->id,
                            'star_seller' => rand(1, 5),
                            'star_courier' => rand(1, 5),
                            'variations' => 'Warna: Hitam, Ukuran: S',
                            'description' => 'Produk Bagus',
                            'attachments' => [
                                'attachment1.jpg',
                                'attachment2.jpg',
                                'attachment3.jpg',
                                'attachment4.jpg'
                            ],
                            'show_username' => rand(0, 1)
                        ],
                        [
                            'user_id' => User::inRandomOrder()->first()->id,
                            'star_seller' => rand(1, 5),
                            'star_courier' => rand(1, 5),
                            'variations' => 'Warna: Hitam, Ukuran: S',
                            'description' => 'Produk Bagus',
                            'attachments' => [
                                'attachment1.jpg',
                                'attachment2.jpg',
                                'attachment3.jpg',
                                'attachment4.jpg'
                            ],
                            'show_username' => rand(0, 1)
                        ]
                    ],
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $product = \App\Models\Product\Products::create([
                    'name' => $payload['name'],
                    'slug' => $payload['slug'],
                    'seller_id' => $payload['seller_id'],
                    'category_id' => $payload['category_id'],
                    'description' => $payload['description'],
                    'stock' => $payload['stock'],
                    'weight' => $payload['weight'],
                    'length' => $payload['length'],
                    'width' => $payload['width'],
                    'height' => $payload['height'],
                    'video' => $payload['video'],
                    'price' => $payload['price'],
                    'created_at' => $payload['created_at'],
                    'updated_at' => $payload['updated_at']
                ]);

                shuffle($payload['images']);
                foreach ($payload['images'] as $image) {
                    $product->images()->create([
                        // 'product_id' => $product->id,
                        'image' => $image,
                    ]);
                }

                shuffle($payload['variations']);
                foreach ($payload['variations'] as $variations) {
                    $product->variations()->create($variations);
                }

                shuffle($payload['reviews']);
                foreach ($payload['reviews'] as $review) {
                    $product->reviews()->create($review);
                }
            }
        });
    }
}
