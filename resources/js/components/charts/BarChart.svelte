<script lang="ts">
    import { Bar } from 'svelte-chartjs';
    import {
        Chart as ChartJS,
        CategoryScale,
        LinearScale,
        BarElement,
        Tooltip,
        Filler,
    } from 'chart.js';
    import { t, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Filler);

    let {
        labels = [] as string[],
        values = [] as number[],
        label = '',
        color = '#00ed64',
        values2 = [] as number[],
        label2 = '',
        color2 = '',
    } = $props();

    const hasDual = $derived(values2.length > 0 && color2 !== '');

    const data = $derived({
        labels,
        datasets: [
            {
                label,
                data: values,
                backgroundColor: color + '80',
                borderColor: color,
                borderWidth: 1,
                borderRadius: 3,
            },
            ...(hasDual ? [{
                label: label2,
                data: values2,
                backgroundColor: color2 + '80',
                borderColor: color2,
                borderWidth: 1,
                borderRadius: 3,
            }] : []),
        ],
    });

    let options = $derived.by(() => {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: hasDual, position: 'bottom' as const },
                tooltip: {
                    callbacks: {
                        label: (ctx: any) => {
                            const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
                            return `${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${ctx.raw.toLocaleString(numLocale)} ${t('common.sar')}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 9 },
                        maxTicksLimit: 15,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#e8edeb' },
                    ticks: {
                        font: { size: 10 },
                        callback: (v: any) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v,
                    },
                },
            },
        };
    });
</script>

<div class="h-full w-full min-w-0 max-w-full overflow-hidden">
    {#if values.length > 0}
        <Bar {data} {options} />
    {:else}
        <p class="py-8 text-center text-sm text-muted-foreground">{t('dashboard.no_chart_data')}</p>
    {/if}
</div>
