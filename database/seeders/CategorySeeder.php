<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $expenseCategories = [
            ['name' => 'طعام وشراب', 'name_en' => 'Food & Drinks', 'icon' => 'utensils', 'color' => '#ef4444', 'type' => 'expense'],
            ['name' => 'مواصلات', 'name_en' => 'Transportation', 'icon' => 'car', 'color' => '#f97316', 'type' => 'expense'],
            ['name' => 'فواتير', 'name_en' => 'Bills', 'icon' => 'receipt', 'color' => '#eab308', 'type' => 'expense'],
            ['name' => 'ترفيه', 'name_en' => 'Entertainment', 'icon' => 'gamepad-2', 'color' => '#a855f7', 'type' => 'expense'],
            ['name' => 'صحة', 'name_en' => 'Health', 'icon' => 'heart-pulse', 'color' => '#22c55e', 'type' => 'expense'],
            ['name' => 'تعليم', 'name_en' => 'Education', 'icon' => 'book-open', 'color' => '#3b82f6', 'type' => 'expense'],
            ['name' => 'سكن وإيجارات', 'name_en' => 'Housing & Rent', 'icon' => 'house', 'color' => '#8b5cf6', 'type' => 'expense'],
            ['name' => 'أخرى', 'name_en' => 'Other', 'icon' => 'ellipsis', 'color' => '#6b7280', 'type' => 'expense'],
        ];

        $incomeCategories = [
            ['name' => 'راتب', 'name_en' => 'Salary', 'icon' => 'briefcase', 'color' => '#22c55e', 'type' => 'income'],
            ['name' => 'عمل حر', 'name_en' => 'Freelance', 'icon' => 'laptop', 'color' => '#3b82f6', 'type' => 'income'],
            ['name' => 'استثمارات', 'name_en' => 'Investments', 'icon' => 'trending-up', 'color' => '#8b5cf6', 'type' => 'income'],
            ['name' => 'هدايا', 'name_en' => 'Gifts', 'icon' => 'gift', 'color' => '#f97316', 'type' => 'income'],
            ['name' => 'مبيعات', 'name_en' => 'Sales', 'icon' => 'shopping-bag', 'color' => '#eab308', 'type' => 'income'],
            ['name' => 'أخرى', 'name_en' => 'Other Income', 'icon' => 'ellipsis', 'color' => '#a855f7', 'type' => 'income'],
        ];

        foreach ($expenseCategories as $category) {
            Category::create($category);
        }
        foreach ($incomeCategories as $category) {
            Category::create($category);
        }
    }
}
