<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Services\AutoCategorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCategorizerTest extends TestCase
{
    use RefreshDatabase;

    private AutoCategorizer $categorizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->categorizer = new AutoCategorizer;
    }

    public function test_categorizes_food_expense(): void
    {
        $id = $this->categorizer->categorize('45 ريال قهوة', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('طعام وشراب', $cat->name);
    }

    public function test_categorizes_transport_expense(): void
    {
        $id = $this->categorizer->categorize('100 ريال بنزين', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('مواصلات', $cat->name);
    }

    public function test_categorizes_health_expense(): void
    {
        $id = $this->categorizer->categorize('200 ريال دكتور', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('صحة', $cat->name);
    }

    public function test_categorizes_bills_expense(): void
    {
        $id = $this->categorizer->categorize('فاتورة الكهرباء', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('فواتير', $cat->name);
    }

    public function test_categorizes_entertainment_expense(): void
    {
        $id = $this->categorizer->categorize('تذكرة سينما', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('ترفيه', $cat->name);
    }

    public function test_categorizes_salary_income(): void
    {
        $id = $this->categorizer->categorize('5000 ريال راتب', 'income');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('راتب', $cat->name);
    }

    public function test_categorizes_freelance_income(): void
    {
        $id = $this->categorizer->categorize('مشروع تصميم', 'income');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('عمل حر', $cat->name);
    }

    public function test_falls_back_to_other_when_unknown(): void
    {
        $id = $this->categorizer->categorize('شيء غير معروف تماما', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('أخرى', $cat->name);
    }

    public function test_categorizes_english_food(): void
    {
        $id = $this->categorizer->categorize('coffee and croissant', 'expense');
        $this->assertNotNull($id);
        $cat = Category::find($id);
        $this->assertEquals('طعام وشراب', $cat->name);
    }
}
