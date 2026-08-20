<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Assistant' },
        ],
    };
</script>

<script lang="ts">
    import {
        Check,
        X,
        Loader2,
        MessageSquare,
        ChevronDown,
        ChevronUp,
        Trash2,
        Plus,
        XCircle,
        RefreshCw,
    } from 'lucide-svelte';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PullToRefresh from '@/components/PullToRefresh.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { ScrollArea } from '@/components/ui/scroll-area';
    import { Separator } from '@/components/ui/separator';
    import { Spinner } from '@/components/ui/spinner';
    import { connectAssistantStream } from '@/lib/assistantStream';
    import { t, localeState } from '@/lib/locale.svelte';
    import { parseMarkdownStreaming } from '@/lib/markdown';

    const { locale } = localeState();

    type Message = {
        id: string;
        role: 'user' | 'assistant';
        content: string;
        html?: string;
        toolCalls?: ToolCall[];
        isStreaming?: boolean;
    };

    type ToolCall = {
        id: string;
        name: string;
        arguments: Record<string, unknown>;
        result?: {
            summary: string;
            ok: boolean;
            data?: Record<string, unknown>;
        };
        status: 'running' | 'completed' | 'failed';
        expanded: boolean;
    };

    let messages = $state<Message[]>([]);
    let input = $state('');
    let isStreaming = $state(false);
    let abortController: { abort: () => void } | null = null;
    let messagesEndRef: HTMLDivElement | null = $state(null);
    let retryInfo = $state<{
        text: string;
        context: string;
        userMessageId: string;
        failedMessageId: string;
    } | null>(null);

    const examplePrompts = $derived([
        { ar: 'كم صرفت هذا الشهر؟', en: 'How much did I spend this month?' },
        {
            ar: 'أضف مصروف ٥٠ ريال قهوة أمس',
            en: 'Add expense 50 SAR coffee yesterday',
        },
        { ar: 'اعرض أكبر ٥ مصروفات', en: 'Show top 5 expenses' },
        { ar: 'احذف آخر عملية', en: 'Delete last transaction' },
    ]);

    function scrollToBottom(): void {
        if (messagesEndRef) {
            messagesEndRef.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function sendMessage(): void {
        const text = input.trim();

        if (!text || isStreaming) {
            return;
        }

        const history = messages.map((m) => ({
            role: m.role,
            content: m.content,
        }));

        const userMessage: Message = {
            id: crypto.randomUUID(),
            role: 'user',
            content: text,
        };
        messages = [...messages, userMessage];
        input = '';
        retryInfo = null;

        startStream(text, history, null);
    }

    function startStream(
        text: string,
        history: Array<{ role: 'user' | 'assistant'; content: string }>,
        retryContext: string | null,
    ): void {
        isStreaming = true;

        const assistantMessage: Message = {
            id: crypto.randomUUID(),
            role: 'assistant',
            content: '',
            html: '',
            toolCalls: [],
            isStreaming: true,
        };
        messages = [...messages, assistantMessage];

        abortController = connectAssistantStream(text, history, {
            onText: (delta) => {
                const idx = messages.findIndex(
                    (m) => m.id === assistantMessage.id,
                );

                if (idx >= 0) {
                    messages[idx].content += delta;
                    messages[idx].html = parseMarkdownStreaming(
                        messages[idx].content,
                    );
                    messages = [...messages];
                }
            },
            onToolCall: (id, name, args) => {
                const idx = messages.findIndex(
                    (m) => m.id === assistantMessage.id,
                );

                if (idx >= 0) {
                    const toolCall: ToolCall = {
                        id,
                        name,
                        arguments: args,
                        status: 'running',
                        expanded: false,
                    };
                    messages[idx].toolCalls = [
                        ...(messages[idx].toolCalls ?? []),
                        toolCall,
                    ];
                    messages = [...messages];
                }
            },
            onToolResult: (id, name, summary, ok, data) => {
                const idx = messages.findIndex(
                    (m) => m.id === assistantMessage.id,
                );
                const toolCalls = messages[idx]?.toolCalls;

                if (toolCalls) {
                    const tcIdx = toolCalls.findIndex((tc) => tc.id === id);

                    if (tcIdx >= 0) {
                        toolCalls[tcIdx].status = ok ? 'completed' : 'failed';
                        toolCalls[tcIdx].result = { summary, ok, data };
                        messages = [...messages];
                    }
                }
            },
            onError: (error, errorCode) => {
                const idx = messages.findIndex(
                    (m) => m.id === assistantMessage.id,
                );

                if (idx >= 0) {
                    const message =
                        errorCode === 'rate_limited'
                            ? t('assistant.error_rate_limited')
                            : errorCode === 'action_done'
                              ? t('assistant.error_action_done')
                              : errorCode === 'ai_error'
                                ? t('assistant.error_ai')
                                : error;

                    messages[idx].content += `\n\n⚠️ ${message}`;
                    messages[idx].html = parseMarkdownStreaming(
                        messages[idx].content,
                    );
                    messages[idx].isStreaming = false;
                    isStreaming = false;
                    messages = [...messages];

                    if (errorCode === 'action_done') {
                        const toolCalls = messages[idx]?.toolCalls ?? [];
                        const userMsg = messages[idx - 1];
                        const failedMsg = messages[idx];

                        if (toolCalls.length > 0 && userMsg && failedMsg) {
                            const context = toolCalls
                                .map(
                                    (tc) =>
                                        `${tc.name}: ${
                                            tc.result?.ok
                                                ? tc.result.summary
                                                : t('assistant.tool_failed')
                                        }`,
                                )
                                .join('\n');

                            retryInfo = {
                                text: userMsg.content,
                                context,
                                userMessageId: userMsg.id,
                                failedMessageId: failedMsg.id,
                            };
                        }
                    }
                }
            },
            onDone: () => {
                const idx = messages.findIndex(
                    (m) => m.id === assistantMessage.id,
                );

                if (idx >= 0) {
                    messages[idx].isStreaming = false;
                    messages[idx].html = parseMarkdownStreaming(
                        messages[idx].content,
                    );
                    isStreaming = false;
                    messages = [...messages];
                }
            },
        }, retryContext);

        scrollToBottom();
    }

    function retryLastMessage(): void {
        if (!retryInfo || isStreaming) {
            return;
        }

        const info = retryInfo;
        retryInfo = null;

        const userIdx = messages.findIndex((m) => m.id === info.userMessageId);
        const history = messages
            .slice(0, userIdx + 1)
            .map((m) => ({ role: m.role, content: m.content }));

        messages = messages.filter((m) => m.id !== info.failedMessageId);

        startStream(info.text, history, info.context);
    }

    function stopStreaming(): void {
        if (abortController) {
            abortController.abort();
            abortController = null;
        }

        const idx = messages.findIndex((m) => m.isStreaming);

        if (idx >= 0) {
            messages[idx].isStreaming = false;
            messages[idx].html = parseMarkdownStreaming(messages[idx].content);
            messages = [...messages];
        }

        isStreaming = false;
    }

    function newChat(): void {
        stopStreaming();
        messages = [];
    }

    function useExample(prompt: { ar: string; en: string }): void {
        input = locale.value === 'ar' ? prompt.ar : prompt.en;
        sendMessage();
    }

    function toggleToolCall(messageId: string, toolCallId: string): void {
        const msgIdx = messages.findIndex((m) => m.id === messageId);
        const toolCalls = messages[msgIdx]?.toolCalls;

        if (toolCalls) {
            const tcIdx = toolCalls.findIndex((tc) => tc.id === toolCallId);

            if (tcIdx >= 0) {
                toolCalls[tcIdx].expanded = !toolCalls[tcIdx].expanded;
                messages = [...messages];
            }
        }
    }

    function formatArgs(args: Record<string, unknown>): string {
        return JSON.stringify(args, null, 2);
    }

    onMount(() => {
        scrollToBottom();
    });

    $effect(() => {
        scrollToBottom();
    });
</script>

<AppHead title={t('assistant.title')} />

<PullToRefresh>
    <div class="min-w-0 flex flex-col h-[calc(100vh-4rem)] p-4 sm:p-6">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl font-semibold sm:text-2xl">
                {t('assistant.title')}
            </h1>
            <p class="text-sm text-muted-foreground">
                {t('assistant.subtitle')}
            </p>
        </div>

        <div
            class="flex-1 flex flex-col min-h-0 rounded-xl border border-hairline bg-card dark:bg-card overflow-hidden"
        >
            {#if messages.length === 0}
                <div
                    class="flex-1 flex flex-col items-center justify-center p-8 text-center"
                >
                    <MessageSquare
                        class="size-12 text-muted-foreground/50 mb-4"
                    />
                    <h3 class="text-lg font-medium mb-2">
                        {t('assistant.empty_title')}
                    </h3>
                    <p class="text-sm text-muted-foreground mb-6 max-w-xs">
                        {t('assistant.empty_desc')}
                    </p>
                    <div class="flex flex-wrap gap-2 justify-center">
                        {#each examplePrompts as prompt (prompt.ar)}
                            <Button
                                variant="outline"
                                size="sm"
                                class="text-xs whitespace-nowrap"
                                onclick={() => useExample(prompt)}
                            >
                                {locale.value === 'ar' ? prompt.ar : prompt.en}
                            </Button>
                        {/each}
                    </div>
                </div>
            {:else}
                <ScrollArea class="flex-1 min-h-0">
                    <div
                        class="p-4 space-y-4"
                        role="log"
                        aria-live="polite"
                        aria-atomic="false"
                    >
                        {#each messages as message (message.id)}
                            <div
                                class="flex {message.role === 'user'
                                    ? 'justify-end'
                                    : 'justify-start'} animate-fade-in-up"
                            >
                                <div class="max-w-[85%] sm:max-w-[80%]">
                                    <div
                                        class="{message.role === 'user'
                                            ? 'bg-brand-green text-brand-teal-deep rounded-2xl'
                                            : 'bg-muted rounded-2xl'} px-4 py-2.5 sm:px-5 sm:py-3"
                                    >
                                        {#if message.role === 'user'}
                                            <p
                                                class="text-sm sm:text-base whitespace-pre-wrap"
                                            >
                                                {message.content}
                                            </p>
                                        {:else}
                                            <div class="md-content">
                                                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                                {@html message.html ?? ''}
                                            </div>
                                        {/if}
                                    </div>

                                    {#if message.toolCalls && message.toolCalls.length > 0}
                                        <div class="mt-2 space-y-2">
                                            {#each message.toolCalls as toolCall (toolCall.id)}
                                                <div
                                                    class="rounded-xl border border-hairline bg-background dark:bg-card p-3 animate-slide-in"
                                                >
                                                    <div
                                                        class="flex items-center gap-2 cursor-pointer"
                                                        role="button"
                                                        tabindex="0"
                                                        onclick={() =>
                                                            toggleToolCall(
                                                                message.id,
                                                                toolCall.id,
                                                            )}
                                                        onkeydown={(e) => {
                                                            if (
                                                                e.key ===
                                                                    'Enter' ||
                                                                e.key === ' '
                                                            ) {
                                                                e.preventDefault();
                                                                toggleToolCall(
                                                                    message.id,
                                                                    toolCall.id,
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        <div
                                                            class="flex size-8 items-center justify-center rounded-lg bg-muted"
                                                        >
                                                            {#if toolCall.name === 'list_transactions'}
                                                                <MessageSquare
                                                                    class="size-4 text-accent-blue"
                                                                />
                                                            {:else if toolCall.name === 'create_transactions'}
                                                                <Plus
                                                                    class="size-4 text-brand-green-dark dark:text-brand-green"
                                                                />
                                                            {:else if toolCall.name === 'update_transactions'}
                                                                <Loader2
                                                                    class="size-4 text-accent-orange"
                                                                />
                                                            {:else if toolCall.name === 'delete_transactions'}
                                                                <Trash2
                                                                    class="size-4 text-destructive"
                                                                />
                                                            {:else}
                                                                <MessageSquare
                                                                    class="size-4 text-muted-foreground"
                                                                />
                                                            {/if}
                                                        </div>
                                                        <div
                                                            class="flex-1 min-w-0"
                                                        >
                                                            <p
                                                                class="text-sm font-medium truncate"
                                                            >
                                                                {toolCall.name}
                                                            </p>
                                                            <p
                                                                class="text-xs text-muted-foreground truncate"
                                                            >
                                                                {toolCall.status ===
                                                                'running'
                                                                    ? t(
                                                                          'assistant.tool_running',
                                                                      )
                                                                    : toolCall.status ===
                                                                        'completed'
                                                                      ? t(
                                                                            'assistant.tool_completed',
                                                                        )
                                                                      : t(
                                                                            'assistant.tool_failed',
                                                                        )}
                                                            </p>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-1"
                                                        >
                                                            {#if toolCall.status === 'running'}
                                                                <Spinner
                                                                    class="size-4"
                                                                />
                                                            {:else if toolCall.status === 'completed'}
                                                                <Check
                                                                    class="size-4 text-brand-green-dark dark:text-brand-green"
                                                                />
                                                            {:else if toolCall.status === 'failed'}
                                                                <XCircle
                                                                    class="size-4 text-destructive"
                                                                />
                                                            {/if}
                                                            {#if toolCall.result}
                                                                <button
                                                                    class="p-1 rounded hover:bg-muted transition-colors"
                                                                    onclick={(
                                                                        e,
                                                                    ) => {
                                                                        e.stopPropagation();
                                                                        toggleToolCall(
                                                                            message.id,
                                                                            toolCall.id,
                                                                        );
                                                                    }}
                                                                    aria-label={toolCall.expanded
                                                                        ? t(
                                                                              'assistant.collapse',
                                                                          )
                                                                        : t(
                                                                              'assistant.expand',
                                                                          )}
                                                                >
                                                                    {#if toolCall.expanded}
                                                                        <ChevronUp
                                                                            class="size-4"
                                                                        />
                                                                    {:else}
                                                                        <ChevronDown
                                                                            class="size-4"
                                                                        />
                                                                    {/if}
                                                                </button>
                                                            {/if}
                                                        </div>
                                                    </div>

                                                    {#if toolCall.expanded && toolCall.result}
                                                        <Separator
                                                            class="my-2"
                                                        />
                                                        <details
                                                            class="text-xs"
                                                        >
                                                            <summary
                                                                class="cursor-pointer text-muted-foreground mb-1"
                                                                >{t(
                                                                    'assistant.tool_arguments',
                                                                )}</summary
                                                            >
                                                            <pre
                                                                class="rounded bg-muted p-2 overflow-x-auto whitespace-pre-wrap">{formatArgs(
                                                                    toolCall.arguments,
                                                                )}</pre>
                                                        </details>
                                                        <details
                                                            class="text-xs mt-2"
                                                        >
                                                            <summary
                                                                class="cursor-pointer text-muted-foreground mb-1"
                                                                >{t(
                                                                    'assistant.tool_result',
                                                                )}</summary
                                                            >
                                                            <pre
                                                                class="rounded bg-muted p-2 overflow-x-auto whitespace-pre-wrap">{formatArgs(
                                                                    toolCall
                                                                        .result
                                                                        .data ??
                                                                        {},
                                                                )}</pre>
                                                        </details>
                                                    {/if}
                                                </div>
                                            {/each}
                                        </div>
                                    {/if}

                                    {#if retryInfo && retryInfo.failedMessageId === message.id}
                                        <div class="mt-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="text-xs"
                                                onclick={retryLastMessage}
                                            >
                                                <RefreshCw class="size-3.5" />
                                                {t('assistant.retry')}
                                            </Button>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                        <div id="messages-end" bind:this={messagesEndRef}></div>
                    </div>
                </ScrollArea>
            {/if}

            <div class="border-t border-hairline p-3 sm:p-4">
                <form
                    onsubmit={(e) => {
                        e.preventDefault();
                        sendMessage();
                    }}
                    class="flex gap-2"
                >
                    <div class="flex-1 relative">
                        <Input
                            placeholder={t('assistant.placeholder')}
                            bind:value={input}
                            disabled={isStreaming}
                            class="pr-12"
                            onkeydown={(e) => {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    sendMessage();
                                }
                            }}
                        />
                        {#if isStreaming}
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-destructive hover:bg-destructive/10"
                                onclick={stopStreaming}
                                aria-label={t('assistant.stop')}
                            >
                                <X class="size-4" />
                            </Button>
                        {/if}
                    </div>
                    <Button
                        type="submit"
                        size="icon"
                        class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                        disabled={!input.trim() || isStreaming}
                        aria-label={t('assistant.send')}
                    >
                        {#if isStreaming}
                            <Spinner class="size-4" />
                        {:else}
                            <MessageSquare class="size-4" />
                        {/if}
                    </Button>
                    {#if messages.length > 0}
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="rounded-full"
                            onclick={newChat}
                            aria-label={t('assistant.new_chat')}
                        >
                            <Plus class="size-4" />
                        </Button>
                    {/if}
                </form>
            </div>
        </div>
    </div>
</PullToRefresh>

<style>
    .md-content :global(p) {
        margin-block: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.625;
    }

    .md-content :global(p:first-child) {
        margin-top: 0;
    }

    .md-content :global(p:last-child) {
        margin-bottom: 0;
    }

    .md-content :global(strong) {
        font-weight: 600;
    }

    .md-content :global(ul),
    .md-content :global(ol) {
        margin-block: 0.5rem;
        padding-inline-start: 1.25rem;
        font-size: 0.875rem;
        line-height: 1.625;
    }

    .md-content :global(li) {
        margin-block: 0.25rem;
    }

    .md-content :global(h1),
    .md-content :global(h2),
    .md-content :global(h3),
    .md-content :global(h4) {
        margin-block: 0.75rem 0.5rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .md-content :global(h1) {
        font-size: 1.125rem;
    }

    .md-content :global(h2) {
        font-size: 1rem;
    }

    .md-content :global(h3) {
        font-size: 0.9375rem;
    }

    .md-content :global(h4) {
        font-size: 0.875rem;
    }

    .md-content :global(code) {
        padding: 0.125rem 0.375rem;
        border-radius: 0.375rem;
        background: var(--color-muted);
        font-size: 0.8125rem;
        direction: ltr;
        unicode-bidi: embed;
    }

    .md-content :global(pre) {
        margin-block: 0.5rem;
        padding: 0.75rem;
        overflow-x: auto;
        border-radius: 0.5rem;
        background: var(--color-muted);
    }

    .md-content :global(pre code) {
        padding: 0;
        background: transparent;
    }

    .md-content :global(blockquote) {
        margin-block: 0.5rem;
        padding-inline-start: 0.75rem;
        border-inline-start: 2px solid var(--color-border);
        color: var(--color-muted-foreground);
    }

    .md-content :global(table) {
        width: 100%;
        margin-block: 0.75rem;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }

    .md-content :global(th),
    .md-content :global(td) {
        padding: 0.5rem 0.625rem;
        text-align: start;
        border: 1px solid var(--color-border);
    }

    .md-content :global(th) {
        background: var(--color-muted);
        font-weight: 600;
        white-space: nowrap;
    }

    .md-content :global(tbody tr:nth-child(even)) {
        background: color-mix(in srgb, var(--color-muted) 45%, transparent);
    }

    .md-content :global(hr) {
        margin-block: 0.75rem;
        border: 0;
        border-top: 1px solid var(--color-border);
    }

    .md-content :global(a) {
        color: var(--color-primary);
        text-decoration: underline;
    }
</style>
