<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_page_renders_the_report(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create([
            'name' => 'طعام وشراب',
            'name_en' => 'Food & Drinks',
            'icon' => 'utensils',
            'color' => '#ef4444',
            'type' => 'expense',
        ]);

        $user->transactions()->create([
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 120.50,
            'description' => 'قهوة',
            'transaction_date' => today()->format('Y-m-d'),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('reports.print', ['period' => 'monthly', 'date' => today()->format('Y-m-d')]));

        $response->assertOk();
        $response->assertSee('120.50');
        $response->assertSee('قهوة');
        $response->assertSee('data-label=');
    }
}