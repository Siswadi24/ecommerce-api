<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'icons' => 'category/Elektronik.webp',
                'childs' => ['Microwave', 'TV']
            ],
            [
                'name' => 'Fashion Pria',
                'icons' => 'category/Fashion-Pria.webp',
                'childs' => ['Baju', 'Sepatu']
            ],
            [
                'name' => 'Fashion Wanita',
                'icons' => 'category/Fashion-Wanita.webp',
                'childs' => ['Dress', 'Kerudung']
            ],
            [
                'name' => 'Handphone',
                'icons' => 'category/Handphone.webp',
                'childs' => ['Battery', 'Anti Gores']
            ],
            [
                'name' => 'Komputer & Laptop',
                'icons' => 'category/Komputer-Laptop.webp',
                'childs' => ['Keyboard', 'Mouse']
            ],
            [
                'name' => 'Makanan & Minuman',
                'icons' => 'category/Makanan-Minuman.webp',
                'childs' => ['Mie', 'Minuman']
            ]
        ];

        foreach ($categories as $categoryPayload) {
            $category = \App\Models\Category::create([
                'slug' => Str::slug($categoryPayload['name']),
                'name' => $categoryPayload['name'],
                'icons' => $categoryPayload['icons'],
            ]);

            foreach ($categoryPayload['childs'] as $child) {
                $category->childs()->create([
                    'slug' => Str::slug($child),
                    'name' => $child,
                ]);
            }
        }
    }
}
