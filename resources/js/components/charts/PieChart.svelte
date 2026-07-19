<script lang="ts">
    import { Pie } from 'svelte-chartjs';
    import {
        Chart as ChartJS,
        ArcElement,
        Tooltip,
        Legend,
    } from 'chart.js';
    import { t, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    ChartJS.register(ArcElement, Tooltip, Legend);

    let {
        labels = [] as string[],
        values = [] as number[],
        colors = [] as string[],
        title = '',
    } = $props();

    const data = $derived({
        labels,
        datasets: [{
            data: values,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#ffffff',
        }],
    });

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 8,
                    usePointStyle: true,
                    font: { size: 10 },
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx: any) => {
                        const total = ctx.dataset.data.reduce((a: number, b: number) => a + b, 0);
                        const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : '0';
                        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
                        return `${ctx.label}: ${ctx.raw.toLocaleString(numLocale)} ${t('common.sar')} (${pct}%)`;
                    },
                },
            },
        },
    };
</script>

<div class="h-full w-full min-w-0 max-w-full overflow-hidden">
    {#if values.length > 0}
        <Pie {data} {options} />
    {:else}
        <p class="py-8 text-center text-sm text-muted-foreground">{t('dashboard.no_chart_data')}</p>
    {/if}
</div>
