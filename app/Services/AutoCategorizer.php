<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class AutoCategorizer
{
    private array $expenseKeywords = [
        'طعام وشراب' => ['اكل', 'أكل', 'طعام', 'غدا', 'غداء', 'عشا', 'عشاء', 'فطور', 'مطعم', 'بوفيه', 'قهوة', 'قهوه', 'شاي', 'عصير', 'بقالة', 'بقاله', 'سوبر', 'تموينات', 'حلويات', 'بيتزا', 'برجر', 'مقاضي', 'كوفي', 'ستاربكس', 'دجاج', 'لحم', 'خضار', 'فواكه', 'food', 'restaurant', 'dinner', 'lunch', 'breakfast', 'coffee', 'tea', 'grocery', 'supermarket', 'pizza', 'burger'],
        'مواصلات' => ['بنزين', 'ديزل', 'غاز', 'وقود', 'سيارة', 'سياره', 'تصليح', 'تأمين', 'تامين', 'أوبر', 'اوبر', 'كريم', 'تكسي', 'تاكسي', 'موقف', 'مواقف', 'باص', 'قطار', 'طيران', 'fuel', 'gas', 'uber', 'careem', 'taxi', 'bus', 'train', 'parking', 'car'],
        'فواتير' => ['كهرباء', 'كهربا', 'ماء', 'موية', 'جوال', 'اتصالات', 'نت', 'انترنت', 'فاتورة', 'فاتوره', 'اشتراك', 'electricity', 'water', 'phone', 'internet', 'bill', 'subscription', 'utility'],
        'ترفيه' => ['سينما', 'فلم', 'فيلم', 'لعبة', 'لعبه', 'قيم', 'نت فلكس', 'نتفلكس', 'شاهد', 'ترفيه', 'العاب', 'حديقة', 'حديقه', 'سفر', 'سياحة', 'سياحه', 'فندق', 'شاليه', 'تذكرة سينما', 'تذكره سينما', 'movie', 'cinema', 'game', 'netflix', 'entertainment', 'travel', 'hotel'],
        'صحة' => ['دكتور', 'مستشفى', 'عيادة', 'عياده', 'صيدلية', 'صيدليه', 'دواء', 'علاج', 'اسنان', 'فحص', 'تحليل', 'رياضة', 'رياضه', 'نادي', 'doctor', 'hospital', 'pharmacy', 'medicine', 'health', 'gym', 'dental'],
        'تعليم' => ['كتاب', 'دورة', 'دوره', 'كورس', 'جامعة', 'جامعه', 'مدرسة', 'مدرسه', 'تعليم', 'تدريب', 'شهادة', 'شهاده', 'محاضرة', 'محاضره', 'book', 'course', 'university', 'school', 'education', 'training'],
        'سكن وإيجارات' => ['ايجار', 'إيجار', 'سكن', 'شقة', 'شقه', 'بيت', 'فيلا', 'عمارة', 'عماره', 'صيانة', 'سباك', 'كهربائي', 'rent', 'housing', 'apartment', 'maintenance'],
    ];

    private array $incomeKeywords = [
        'راتب' => ['راتب', 'مرتب', 'بدل', 'بونص', 'مكافأة', 'مكافاه', 'salary', 'bonus', 'paycheck'],
        'عمل حر' => ['فريلانس', 'عمل حر', 'مشروع', 'تصميم', 'برمجة', 'استشارة', 'استشاره', 'freelance', 'project', 'design', 'freelancer', 'consulting'],
        'استثمارات' => ['اسهم', 'أسهم', 'توزيعات', 'ارباح', 'أرباح', 'عقار', 'صندوق', 'تداول', 'investment', 'stocks', 'dividend', 'real estate', 'trading'],
        'هدايا' => ['هدية', 'هديه', 'هدايا', 'منحة', 'منحه', 'gift'],
        'مبيعات' => ['بيع', 'مبيعات', 'تجارة', 'تجاره', 'سلعة', 'سلعه', 'sale', 'sales'],
    ];

    public function categorize(string $description, string $type = 'expense'): ?int
    {
        $desc = Str::lower(trim($description));
        $keywords = $type === 'income' ? $this->incomeKeywords : $this->expenseKeywords;

        $bestScore = 0;
        $bestCategory = null;

        foreach ($keywords as $categoryName => $terms) {
            $score = 0;
            foreach ($terms as $term) {
                if (str_contains($desc, $term)) {
                    $score += mb_strlen($term);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $categoryName;
            }
        }

        if ($bestCategory === null) {
            $fallback = Category::where('type', $type)
                ->whereNull('user_id')
                ->where('name', 'أخرى')
                ->first();

            return $fallback?->id;
        }

        $category = Category::where('type', $type)
            ->whereNull('user_id')
            ->where('name', $bestCategory)
            ->first();

        return $category?->id;
    }
}
