<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\DefaultCategories;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (DefaultCategories::all() as $category) {
            Category::create($category);
        }
    }
}
