<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, login, register } from '@/routes';
    import Wallet from 'lucide-svelte/icons/wallet';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import MessageCircle from 'lucide-svelte/icons/message-circle';
    import Shield from 'lucide-svelte/icons/shield';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';

    const auth = $derived(page.props.auth);
</script>

<AppHead title="الرئيسية" />

<div class="min-h-screen bg-background" dir="rtl">
    <nav class="sticky top-0 z-40 border-b bg-white/80 backdrop-blur-md dark:bg-brand-teal-deep/90">
        <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between px-8">
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-green">
                    <Wallet class="size-5 text-brand-teal-deep" />
                </div>
                <span class="text-lg font-semibold text-ink dark:text-on-dark">بيان</span>
            </div>
            <div class="flex items-center gap-3">
                {#if auth.user}
                    <Link
                        href={toUrl(dashboard())}
                        class="inline-flex items-center rounded-full bg-brand-green px-5 py-2.5 text-sm font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        لوحة التحكم
                    </Link>
                {:else}
                    <Link
                        href={toUrl(login())}
                        class="inline-flex items-center rounded-full border border-hairline-strong px-5 py-2.5 text-sm font-semibold text-ink transition-colors dark:border-hairline-dark dark:text-on-dark"
                    >
                        دخول
                    </Link>
                    <Link
                        href={toUrl(register())}
                        class="inline-flex items-center rounded-full bg-brand-green px-5 py-2.5 text-sm font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        ابدأ مجاناً
                    </Link>
                {/if}
            </div>
        </div>
    </nav>

    <section class="bg-brand-teal-deep px-8 py-20 md:py-28 lg:py-32">
        <div class="mx-auto max-w-[1280px] text-center">
            <h1 class="mb-4 text-4xl font-medium leading-tight tracking-tight text-on-dark md:text-5xl lg:text-6xl">
                منصة واحدة لإدارة مصاريفك
            </h1>
            <p class="mx-auto mb-8 max-w-2xl text-lg text-on-dark-muted">
                تتبع مصاريفك، حلل أنماط صرفك، واحصل على توصيات ذكية من مساعدك المالي الشخصي.
            </p>
            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                {#if !auth.user}
                    <Link
                        href={toUrl(register())}
                        class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        ابدأ الآن
                        <ArrowLeft class="size-4" />
                    </Link>
                    <Link
                        href={toUrl(login())}
                        class="inline-flex items-center gap-2 rounded-full border border-hairline-dark px-6 py-3 text-base font-semibold text-on-dark transition-colors"
                    >
                        تسجيل الدخول
                    </Link>
                {:else}
                    <Link
                        href={toUrl(dashboard())}
                        class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                    >
                        لوحة التحكم
                        <ArrowLeft class="size-4" />
                    </Link>
                {/if}
            </div>
        </div>
    </section>

    <section class="bg-white px-8 py-16 md:py-24 dark:bg-background">
        <div class="mx-auto max-w-[1280px]">
            <h2 class="mb-12 text-center text-3xl font-medium text-ink dark:text-foreground md:text-4xl">
                كل ما تحتاجه لإدارة أموالك
            </h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-soft">
                        <Wallet class="size-6 text-brand-green-dark" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">تتبع المصاريف</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        سجّل مصاريفك ودخلك بسهولة مع تصنيفات ذكية تنظم لك كل شيء.
                    </p>
                </div>

                <div class="rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent-blue/10">
                        <BarChart3 class="size-6 text-accent-blue" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">تحليلات بصرية</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        رسوم بيانية تفاعلية توضح لك أين تذهب أموالك كل شهر.
                    </p>
                </div>

                <div class="rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent-purple/10">
                        <MessageCircle class="size-6 text-accent-purple" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">مساعد ذكي</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        اسأل مساعدك المالي عن مصاريفك واحصل على نصائح مخصصة لتقليل الصرف.
                    </p>
                </div>

                <div class="rounded-xl border border-hairline bg-white p-6 dark:bg-card">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-green-soft">
                        <Shield class="size-6 text-brand-green-dark" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-ink dark:text-card-foreground">مزامنة سحابية</h3>
                    <p class="text-sm leading-relaxed text-steel dark:text-muted-foreground">
                        بياناتك محفوظة بأمان في السحابة ومتاحة من أي جهاز.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-surface px-8 py-16 md:py-24 dark:bg-accent">
        <div class="mx-auto max-w-[1280px] text-center">
            <h2 class="mb-4 text-3xl font-medium text-ink dark:text-accent-foreground md:text-4xl">
                جاهز تبدأ؟
            </h2>
            <p class="mb-8 text-steel dark:text-muted-foreground">
                أنشئ حسابك المجاني وابدأ بمتابعة مصاريفك اليوم.
            </p>
            {#if !auth.user}
                <Link
                    href={toUrl(register())}
                    class="inline-flex items-center gap-2 rounded-full bg-brand-green px-6 py-3 text-base font-semibold text-brand-teal-deep transition-colors hover:bg-brand-green/90"
                >
                    ابدأ مجاناً
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
                <span class="font-semibold text-on-dark">بيان</span>
            </div>
            <p class="text-sm text-on-dark-muted">
                تطبيق تتبع المصاريف الشخصية المدعوم بالذكاء الاصطناعي
            </p>
        </div>
    </footer>
</div>
