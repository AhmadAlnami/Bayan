<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Languages from 'lucide-svelte/icons/languages';
    import { localeState, updateLocale, type Locale } from '@/lib/locale.svelte';

    let { currentLocale = 'ar' }: { currentLocale?: Locale } = $props();

    const { locale } = localeState();

    $effect(() => {
        if (typeof window !== 'undefined' && !localStorage.getItem('locale')) {
            locale.value = currentLocale;
        }
    });

    type LocaleOption = {
        value: Locale;
        label: string;
        nativeLabel: string;
    };

    const options: LocaleOption[] = [
        { value: 'ar', label: 'Arabic', nativeLabel: 'العربية' },
        { value: 'en', label: 'English', nativeLabel: 'English' },
    ];

    function handleLocaleChange(value: Locale) {
        updateLocale(value);
        router.patch('/settings/language', { locale: value });
    }
</script>

<div class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800">
    {#each options as { value, label, nativeLabel } (value)}
        <button
            onclick={() => handleLocaleChange(value)}
            class="flex items-center rounded-md px-3.5 py-1.5 transition-colors {locale.value === value
                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60'}"
        >
            <Languages class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{nativeLabel}</span>
        </button>
    {/each}
</div>
