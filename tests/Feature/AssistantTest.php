<?php

namespace Tests\Feature;

use App\Ai\Agents\FinanceAssistant;
use App\Ai\Tools\CreateTransactions;
use App\Ai\Tools\ListTransactions;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_assistant_page()
    {
        $response = $this->get(route('assistant'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_assistant_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('assistant'));
        $response->assertOk();
    }

    public function test_guests_cannot_stream_to_the_assistant()
    {
        $response = $this->postJson(route('assistant.stream'), [
            'message' => 'كم صرفت هذا الشهر؟',
        ]);

        $response->assertUnauthorized();
    }

    public function test_stream_endpoint_requires_a_message()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('assistant.stream'), []);
        $response->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_stream_endpoint_validates_history()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('assistant.stream'), [
            'message' => 'مرحبا',
            'history' => [
                ['role' => 'admin', 'content' => 'bad role'],
            ],
        ]);
        $response->assertUnprocessable()->assertJsonValidationErrors('history.0.role');
    }

    public function test_stream_endpoint_returns_server_sent_events()
    {
        FinanceAssistant::fake(function (string $prompt): string {
            return 'رد تجريبي يشرح لك المصاريف.';
        });

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withHeader('Accept', 'text/event-stream')
            ->post(route('assistant.stream'), [
                'message' => 'كم صرفت هذا الشهر؟',
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

        $content = $this->decodeServerSentEvents($response->streamedContent());

        $this->assertTrue(
            $content->contains(fn (array $event): bool => $event['type'] === 'text'),
            'Expected a text event in the stream.'
        );
        $this->assertTrue(
            $content->contains(fn (array $event): bool => $event['type'] === 'done'),
            'Expected a done event at the end of the stream.'
        );
    }

    public function test_stream_endpoint_emits_tool_call_and_result_events()
    {
        FinanceAssistant::fake([
            new ToolCall(
                id: 'call_1',
                name: 'list_transactions',
                arguments: ['limit' => 3, 'type' => 'expense'],
            ),
            function (string $prompt): string {
                return 'عرضت لك آخر العمليات في الجدول.';
            },
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withHeader('Accept', 'text/event-stream')
            ->post(route('assistant.stream'), [
                'message' => 'اعرض آخر ثلاث عمليات',
            ]);

        $response->assertOk();

        $content = $this->decodeServerSentEvents($response->streamedContent());

        $this->assertTrue(
            $content->contains(fn (array $event): bool => $event['type'] === 'tool_call'),
            'Expected a tool_call event in the stream.'
        );

        $toolResult = $content->firstWhere('type', 'tool_result');
        $this->assertNotNull($toolResult, 'Expected a tool_result event in the stream.');
        $this->assertEquals('عرض العمليات', $toolResult['name']);
        $this->assertTrue($toolResult['ok']);
        $this->assertArrayHasKey('data', $toolResult);
    }

    public function test_stream_endpoint_retries_transient_provider_errors()
    {
        FinanceAssistant::fake(function (string $prompt): string {
            throw new RateLimitedException('rate limited');
        });

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withHeader('Accept', 'text/event-stream')
            ->post(route('assistant.stream'), [
                'message' => 'مرحبا',
            ]);

        $response->assertOk();

        $start = microtime(true);
        $content = $this->decodeServerSentEvents($response->streamedContent());
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(4, $elapsed, 'Expected retries to sleep between attempts.');

        $content = $this->decodeServerSentEvents($response->streamedContent());
        $error = $content->firstWhere('type', 'error');
        $this->assertNotNull($error);
        $this->assertEquals('rate_limited', $error['error_code']);
    }

    public function test_list_transactions_returns_transactions_for_today()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['type' => 'expense', 'user_id' => null]);

        $user->transactions()->create([
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 25.5,
            'description' => 'غداء',
            'transaction_date' => now()->toDateString(),
        ]);

        $tool = new ListTransactions($user);

        $result = json_decode((string) $tool->handle(new Request([
            'type' => 'expense',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ])), true);

        $this->assertTrue($result['ok']);
        $this->assertEquals(1, $result['data']['total_count']);
        $this->assertEquals(1, count($result['data']['transactions']));
        $this->assertEquals(now()->toDateString(), $result['data']['transactions'][0]['date']);
        $this->assertEquals(25.5, (float) $result['data']['transactions'][0]['amount']);
    }

    public function test_create_transactions_accepts_english_category_names()
    {
        $user = User::factory()->create();
        Category::factory()->create([
            'name' => 'طعام وشراب',
            'name_en' => 'Food & Drinks',
            'type' => 'expense',
            'user_id' => null,
        ]);

        $tool = new CreateTransactions($user);

        $result = json_decode((string) $tool->handle(new Request([
            'transactions' => [
                [
                    'type' => 'expense',
                    'amount' => 50,
                    'description' => 'Coffee',
                    'transaction_date' => now()->subDay()->toDateString(),
                    'category' => 'Food & Drinks',
                ],
            ],
        ])), true);

        $this->assertTrue($result['ok'], $result['summary'] ?? '');
        $this->assertEquals(1, count($result['data']['created_ids']));

        $transaction = $user->transactions()->with('category')->first();
        $this->assertEquals('طعام وشراب', $transaction->category->name);

        $listResult = json_decode((string) (new ListTransactions($user))->handle(new Request([
            'category' => 'Food & Drinks',
            'type' => 'expense',
        ])), true);
        $this->assertTrue($listResult['ok']);
        $this->assertEquals(1, $listResult['data']['total_count']);
    }

    public function test_stream_endpoint_reports_action_done_when_rate_limited_after_tool()
    {
        FinanceAssistant::fake([
            new ToolCall(
                id: 'call_1',
                name: 'delete_transactions',
                arguments: ['ids' => [148]],
            ),
            function (string $prompt): string {
                throw new RateLimitedException('rate limited after tool');
            },
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withHeader('Accept', 'text/event-stream')
            ->post(route('assistant.stream'), [
                'message' => 'احذف العملية 148',
            ]);

        $response->assertOk();

        $content = $this->decodeServerSentEvents($response->streamedContent());
        $error = $content->firstWhere('type', 'error');
        $this->assertNotNull($error);
        $this->assertEquals('action_done', $error['error_code']);
        $this->assertStringContainsString('data was saved successfully', $error['message']);
        $this->assertStringContainsString('Ask again', $error['message']);
    }

    /**
     * Decode the raw SSE payload into an array of event payloads.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function decodeServerSentEvents(string $content)
    {
        return collect(explode("\n\n", $content))
            ->map(fn (string $block): ?array => preg_match('/^data: (.+)$/m', $block, $matches) ? json_decode($matches[1], true) : null)
            ->filter()
            ->values();
    }
}
