<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalarySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_page_lists_the_users_income_categories(): void
    {
        $user = User::factory()->create();
        $user->categories()->createMany([
            ['name' => 'راتب', 'name_en' => 'Salary', 'icon' => 'briefcase', 'color' => '#22c55e', 'type' => 'income'],
            ['name' => 'عمل حر', 'name_en' => 'Freelance', 'icon' => 'laptop', 'color' => '#3b82f6', 'type' => 'income'],
        ]);

        $this->actingAs($user);

        $response = $this->get(route('salary.edit'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Salary')
                ->has('incomeCategories', 2)
                ->where('incomeCategories.0.name', 'راتب')
                ->where('setting.amount', 0));
    }

    public function test_salary_settings_can_be_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patch(route('salary.update'), [
            'amount' => 5000,
            'day_of_month' => 25,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('salary_settings', [
            'user_id' => $user->id,
            'amount' => 5000,
            'day_of_month' => 25,
            'is_active' => true,
        ]);
    }
}