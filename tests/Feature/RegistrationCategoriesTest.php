<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\DefaultCategories;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_users_receive_default_categories(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame(count(DefaultCategories::all()), $user->categories()->count());

        foreach (DefaultCategories::all() as $default) {
            $this->assertDatabaseHas('categories', [
                'user_id' => $user->id,
                'name' => $default['name'],
                'type' => $default['type'],
            ]);
        }
    }

    public function test_user_without_custom_categories_sees_the_global_defaults(): void
    {
        $this->seed(CategorySeeder::class);

        $user = User::factory()->create();
        $this->actingAs($user);

        $categories = Category::availableFor($user)->where('type', 'expense')->get();

        $this->assertCount(8, $categories);
        $this->assertTrue($categories->every(fn ($c) => $c->user_id === null));
    }

    public function test_user_with_own_categories_does_not_see_global_defaults(): void
    {
        $this->seed(CategorySeeder::class);

        $user = User::factory()->create();
        $user->categories()->create([
            'name' => 'مخصصة',
            'name_en' => 'Custom',
            'icon' => 'star',
            'color' => '#000000',
            'type' => 'expense',
        ]);

        $categories = Category::availableFor($user)->get();

        $this->assertCount(1, $categories);
        $this->assertSame('مخصصة', $categories->first()->name);
    }
}
