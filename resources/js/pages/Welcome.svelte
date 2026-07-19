<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';
    import { t } from '@/lib/locale.svelte';
    import Wallet from 'lucide-svelte/icons/wallet';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import MessageCircle from 'lucide-svelte/icons/message-circle';
    import Shield from 'lucide-svelte/icons/shield';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';

    const auth = $derived(page.props.auth);
</script>

<AppHead title={t('welcome.headline')}>
</AppHead>

<div class="min-h-screen bg-background">
    <nav class="sticky top-0 z-40 border-b bg-white/80 backdrop-blur-md dark:bg-brand-teal-deep/90">
        <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between px-8">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-green">
                    <Wallet class="size-5 text-brand-teal-deep" />
                </div>
                <span class="text-lg font-semibold text-ink dark:text-on-dark">{t('app.name')}</span>
            </div>
            <div class="flex items-center gap-3">
                {#if auth.user}
                    <Link
                        href={toUrl(dashboard())}
                        class="inline-flex items-center rounded-full bg-brand-green px-5 py-2.5 text-sm font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        {t('nav.dashboard')}
                    </Link>
                {:else}
                    <Link
                        href={toUrl(login())}
                        class="inline-flex items-center rounded-full border border-hairline-strong px-5 py-2.5 text-sm font-semibold text-ink transition-colors dark:border-hairline-dark dark:text-on-dark"
                    >
                        {t('welcome.login')}
                    </Link>
                    <Link
                        href={toUrl(register())}
                        class="inline-flex items-center rounded-full bg-brand-green px-5 py-2.5 text-sm font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        {t('welcome.cta_free')}
                    </Link>
                {/if}
            </div>
        </div>
    </nav>

    <section class="animate-fade-in bg-brand-teal-deep px-8 py-20 md:py-28 lg:py-32">
        <div class="mx-auto max-w-[1280px] text-center">
            <h1 class="mb-4 text-4xl font-medium leading-tight tracking-tight text-on-dark md:text-5xl lg:text-6xl">
                {t('welcome.headline')}
            </h1>
            <p class="mx-auto mb-8 max-w-2xl text-lg text-on-dark-muted">
                {t('welcome.subtitle')}
            </p>
            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                {#if !auth.user}
                    <Link
                        href={toUrl(register())}
                        class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        {t('welcome.cta')}
                        <ArrowLeft class="size-4" />
                    </Link>
                    <Link
                        href={toUrl(login())}
                        class="inline-flex items-center gap-2 rounded-full border border-hairline-dark px-6 py-3 text-base font-semibold text-on-dark transition-colors"
                    >
                        {t('welcome.login')}
                    </Link>
                {:else}
                    <Link
                        href={toUrl(dashboard())}
                        class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        {t('nav.dashboard')}
                        <ArrowLeft class="size-4" />
                    </Link>
                {/if}
            </div>
        </div>
    </section>

    <section class="bg-white px-8 py-16 md:py-24 dark:bg-background">
        <div class="mx-auto max-w-[1280px]">
            <h2 class="mb-12 text-center text-3xl font-medium text-ink dark:text-foreground md:text-4xl">
                {t('features.title')}
            </h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="animate-fade-in-up animate-stagger-1 rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-soft">
                        <Wallet class="size-6 text-brand-green-dark" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">{t('features.tracking')}</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        {t('features.tracking_desc')}
                    </p>
                </div>

                <div class="animate-fade-in-up animate-stagger-2 rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent-blue/10">
                        <BarChart3 class="size-6 text-accent-blue" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">{t('features.charts')}</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        {t('features.charts_desc')}
                    </p>
                </div>

                <div class="animate-fade-in-up animate-stagger-3 rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent-purple/10">
                        <MessageCircle class="size-6 text-accent-purple" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">{t('features.ai')}</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        {t('features.ai_desc')}
                    </p>
                </div>

                <div class="animate-fade-in-up animate-stagger-4 rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-soft">
                        <Shield class="size-6 text-brand-green-dark" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">{t('features.sync')}</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        {t('features.sync_desc')}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="animate-fade-in-up bg-surface px-8 py-16 md:py-24 dark:bg-accent">
        <div class="mx-auto max-w-[1280px] text-center">
            <h2 class="mb-4 text-3xl font-medium text-ink dark:text-accent-foreground md:text-4xl">
                {t('welcome.ready')}
            </h2>
            <p class="mb-8 text-steel dark:text-muted-foreground">
                {t('welcome.ready_subtitle')}
            </p>
            {#if !auth.user}
                <Link
                    href={toUrl(register())}
                    class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                >
                    {t('welcome.cta_free')}
                    <ArrowLeft class="size-4" />
                </Link>
            {/if}
        </div>
    </section>

    <footer class="bg-brand-teal-deep px-8 py-12">
        <div class="mx-auto max-w-[1280px] text-center">
            <div class="mb-4 flex items-center justify-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-green">
                    <Wallet class="size-4 text-brand-teal-deep" />
                </div>
                <span class="font-semibold text-on-dark">{t('app.name')}</span>
            </div>
            <p class="text-sm text-on-dark-muted">
                {t('welcome.footer_desc')}
            </p>
        </div>
    </footer>
</div>
