<?php

namespace Database\Seeders;

use App\Models\DefaultCategory;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Shopping',
            'Groceries',
            'Restaurants',
            'Transportation',
            'Health',
            'Entertainment',
            'Bills',
            'Transfers',
            'Misc',
        ];

        foreach ($categories as $category) {
            DefaultCategory::firstOrCreate(['name' => $category]);
        }
    }
}
