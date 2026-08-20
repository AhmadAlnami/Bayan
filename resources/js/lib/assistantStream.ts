export type AssistantEvent =
    | { type: 'text'; delta: string }
    | {
          type: 'tool_call';
          id: string;
          name: string;
          arguments: Record<string, unknown>;
      }
    | {
          type: 'tool_result';
          id: string;
          name: string;
          summary: string;
          ok: boolean;
          data?: Record<string, unknown>;
      }
    | { type: 'error'; error_code?: string; message: string }
    | { type: 'done' };

export type AssistantCallbacks = {
    onText?: (delta: string) => void;
    onToolCall?: (
        id: string,
        name: string,
        args: Record<string, unknown>,
    ) => void;
    onToolResult?: (
        id: string,
        name: string,
        summary: string,
        ok: boolean,
        data?: Record<string, unknown>,
    ) => void;
    onError?: (message: string, errorCode?: string) => void;
    onDone?: () => void;
};

/**
 * Connect to the assistant streaming endpoint
 */
export function connectAssistantStream(
    message: string,
    history: Array<{ role: 'user' | 'assistant'; content: string }>,
    callbacks: AssistantCallbacks,
    retryContext?: string | null,
): { abort: () => void } {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => {
        controller.abort();
        callbacks.onError?.('Request timed out after 300 seconds');
    }, 300_000);

    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    fetch('/assistant/stream', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'text/event-stream',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            message,
            history,
            retry_context: retryContext ?? undefined,
        }),
        signal: controller.signal,
    })
        .then(async (response) => {
            clearTimeout(timeoutId);

            if (!response.ok) {
                const errorText = await response.text();

                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const reader = response.body?.getReader();

            if (!reader) {
                throw new Error('No response body');
            }

            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                const lines = buffer.split('\n');
                buffer = lines.pop() ?? '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) {
                        continue;
                    }

                    try {
                        const event = JSON.parse(
                            line.slice(6),
                        ) as AssistantEvent;

                        switch (event.type) {
                            case 'text':
                                callbacks.onText?.(event.delta);
                                break;
                            case 'tool_call':
                                callbacks.onToolCall?.(
                                    event.id,
                                    event.name,
                                    event.arguments,
                                );
                                break;
                            case 'tool_result':
                                callbacks.onToolResult?.(
                                    event.id,
                                    event.name,
                                    event.summary,
                                    event.ok,
                                    event.data,
                                );
                                break;
                            case 'error':
                                callbacks.onError?.(
                                    event.message,
                                    event.error_code,
                                );
                                break;
                            case 'done':
                                callbacks.onDone?.();
                                break;
                        }
                    } catch {
                        // Ignore parse errors for incomplete chunks
                    }
                }
            }
        })
        .catch((error) => {
            clearTimeout(timeoutId);

            if (error.name !== 'AbortError') {
                callbacks.onError?.(error.message ?? 'Connection failed');
            }
        });

    return {
        abort: () => {
            clearTimeout(timeoutId);
            controller.abort();
        },
    };
}
