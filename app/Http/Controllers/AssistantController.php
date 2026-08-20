<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FinanceAssistant;
use Illuminate\Http\Request;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssistantController extends Controller
{
    public function stream(Request $request): Response
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        $request->validate([
            'message' => 'required|string|max:4000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:4000',
            'retry_context' => 'nullable|string|max:2000',
        ]);

        $message = $request->string('message')->toString();
        $history = $request->input('history', []);
        $retryContext = $request->string('retry_context')->trim()->toString();

        if ($retryContext !== '') {
            $message = $this->retryPrompt($retryContext);
        }

        $agent = new FinanceAssistant($request->user(), $history);

        $streamedResponse = new StreamedResponse(function () use ($agent, $message): void {
            $toolExecuted = false;

            try {
                foreach ($this->streamEvents($agent, $message) as $event) {
                    if (connection_aborted()) {
                        break;
                    }

                    if ($event instanceof TextDelta) {
                        $this->send(['type' => 'text', 'delta' => $event->delta]);
                    } elseif ($event instanceof ToolCallEvent) {
                        $toolExecuted = true;

                        $this->send([
                            'type' => 'tool_call',
                            'id' => $event->toolCall->id,
                            'name' => $this->toolDisplayName($event->toolCall->name),
                            'arguments' => $event->toolCall->arguments,
                        ]);
                    } elseif ($event instanceof ToolResultEvent) {
                        $payload = $this->toolResultPayload($event);
                        $this->send($payload);
                    } elseif ($event instanceof Error) {
                        $this->send([
                            'type' => 'error',
                            'error_code' => 'ai_error',
                            'message' => $event->message,
                        ]);
                    }
                }
            } catch (RateLimitedException $e) {
                report($e);

                $this->send([
                    'type' => 'error',
                    'error_code' => $toolExecuted ? 'action_done' : 'rate_limited',
                    'message' => $toolExecuted
                        ? __('Your data was saved successfully. Only the AI text reply failed because the provider is busy — use "Ask again" to get your answer.')
                        : __('The AI provider is busy right now. Please wait a moment and try again.'),
                ]);
            } catch (\Throwable $e) {
                report($e);

                $this->send([
                    'type' => 'error',
                    'error_code' => 'generic',
                    'message' => __('An error occurred while processing your request. Please try again.'),
                ]);
            }

            $this->send(['type' => 'done']);
        });

        $streamedResponse->headers->set('Content-Type', 'text/event-stream');
        $streamedResponse->headers->set('Cache-Control', 'no-cache');
        $streamedResponse->headers->set('X-Accel-Buffering', 'no');
        $streamedResponse->headers->set('Connection', 'keep-alive');

        return $streamedResponse;
    }

    /**
     * Stream events from the agent, retrying transient provider errors.
     *
     * @return \IteratorAggregate<Laravel\Ai\Streaming\Events\StreamEvent>|iterable
     */
    protected function streamEvents(FinanceAssistant $agent, string $message, int $attempts = 3): iterable
    {
        $attempt = 0;
        $yielded = false;

        do {
            try {
                foreach ($agent->stream($message) as $event) {
                    $yielded = true;

                    yield $event;
                }

                return;
            } catch (RateLimitedException|ProviderOverloadedException $e) {
                if ($yielded || ++$attempt >= $attempts) {
                    throw $e;
                }

                sleep($attempt * 2);
            }
        } while (true);
    }

    /**
     * Decode a tool result event into the frontend payload shape.
     */
    protected function toolResultPayload(ToolResultEvent $event): array
    {
        $raw = $event->toolResult->result;

        $decoded = is_string($raw)
            ? json_decode($raw, true)
            : null;

        if (is_array($decoded) && isset($decoded['ok'], $decoded['summary'])) {
            return [
                'type' => 'tool_result',
                'id' => $event->toolResult->id,
                'name' => $this->toolDisplayName($event->toolResult->name),
                'summary' => (string) $decoded['summary'],
                'ok' => (bool) $decoded['ok'],
                'data' => $decoded['data'] ?? [],
            ];
        }

        return [
            'type' => 'tool_result',
            'id' => $event->toolResult->id,
            'name' => $this->toolDisplayName($event->toolResult->name),
            'summary' => is_string($raw) ? $raw : (string) json_encode($raw),
            'ok' => $event->successful,
            'data' => [],
        ];
    }

    protected function toolDisplayName(string $name): string
    {
        return match ($name) {
            'list_transactions' => 'عرض العمليات',
            'create_transactions' => 'إضافة عمليات',
            'update_transactions' => 'تعديل عمليات',
            'delete_transactions' => 'حذف عمليات',
            default => $name,
        };
    }

    /**
     * Build the prompt for a retry after a successful tool run whose final
     * text reply failed. The operation is already saved, so the agent must
     * NOT call any tools again — it only needs to produce the final answer.
     */
    protected function retryPrompt(string $retryContext): string
    {
        return <<<PROMPT
System note: the previous operation already completed successfully (data is saved). Results summary:
{$retryContext}
Do NOT call any tools — the operation is done. Only produce the final reply text for the user, in the same language they have been using (Arabic if the user writes Arabic, English otherwise), summarizing what was completed.
PROMPT;
    }

    protected function send(array $payload): void
    {
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
        flush();
    }
}
