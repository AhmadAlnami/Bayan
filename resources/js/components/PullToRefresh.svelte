<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import RefreshCw from 'lucide-svelte/icons/refresh-cw';
    import type { Snippet } from 'svelte';
    import { t } from '@/lib/locale.svelte';

    let {
        children,
    }: {
        children: Snippet;
    } = $props();

    let pulling = $state(false);
    let refreshing = $state(false);
    let pullDistance = $state(0);
    let startY = $state(0);
    const threshold = 80;

    function onTouchStart(e: TouchEvent) {
        if (window.scrollY > 0) {
return;
}

        startY = e.touches[0].clientY;
        pulling = true;
    }

    function onTouchMove(e: TouchEvent) {
        if (!pulling) {
return;
}

        pullDistance = Math.max(0, (e.touches[0].clientY - startY) * 0.5);
    }

    function onTouchEnd() {
        finishPull();
    }

    function finishPull() {
        if (pullDistance >= threshold) {
            doRefresh();
        } else {
            pullDistance = 0;
        }

        pulling = false;
    }

    function doRefresh() {
        refreshing = true;
        router.reload({
            onFinish: () => {
                refreshing = false;
                pullDistance = 0;
            },
        });
    }

    const rotate = $derived(refreshing ? 'animate-spin' : '');
    const opacity = $derived(Math.min(1, pullDistance / threshold));
    const translate = $derived(pullDistance);
</script>

<div
    class="relative touch-pan-y"
    role="region"
    aria-label="Pull to refresh"
    ontouchstart={onTouchStart}
    ontouchmove={onTouchMove}
    ontouchend={onTouchEnd}
>
    <div
        class="flex items-center justify-center overflow-hidden transition-all duration-200"
        style="height: {translate}px; opacity: {opacity};"
    >
        <div class="flex items-center gap-2 text-xs text-muted-foreground">
            <RefreshCw class="size-4 {rotate}" />
            <span>{refreshing ? t('pull_to_refresh.refreshing') : translate >= threshold ? t('pull_to_refresh.release') : t('pull_to_refresh.pull')}</span>
        </div>
    </div>

    {@render children()}
</div>
