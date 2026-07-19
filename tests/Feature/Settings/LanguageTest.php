<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('language.edit'));

        $response->assertOk();
    }

    public function test_locale_can_be_updated_to_english(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);

        $response = $this
            ->actingAs($user)
            ->patch(route('language.update'), [
                'locale' => 'en',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('en', $user->locale);
    }

    public function test_locale_can_be_updated_to_arabic(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this
            ->actingAs($user)
            ->patch(route('language.update'), [
                'locale' => 'ar',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('ar', $user->locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create(['locale' => 'ar']);

        $response = $this
            ->actingAs($user)
            ->from(route('language.edit'))
            ->patch(route('language.update'), [
                'locale' => 'fr',
            ]);

        $response
            ->assertSessionHasErrors('locale')
            ->assertRedirect(route('language.edit'));

        $this->assertSame('ar', $user->fresh()->locale);
    }

    public function test_unauthenticated_users_cannot_access_language_settings(): void
    {
        $response = $this->get(route('language.edit'));

        $response->assertRedirect();
    }
}
