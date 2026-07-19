<script lang="ts">
    import { Line } from 'svelte-chartjs';
    import {
        Chart as ChartJS,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Tooltip,
        Legend,
        Filler,
    } from 'chart.js';
    import { t, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler);

    let {
        labels = [] as string[],
        expenseValues = [] as number[],
        incomeValues = [] as number[],
        expenseLabel = 'مصروفات',
        incomeLabel = 'دخل',
    } = $props();

    const data = $derived({
        labels,
        datasets: [
            {
                label: incomeLabel,
                data: incomeValues,
                borderColor: '#00ed64',
                backgroundColor: '#00ed6420',
                fill: 'origin',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                order: 2,
            },
            {
                label: expenseLabel,
                data: expenseValues,
                borderColor: '#e04444',
                backgroundColor: '#e0444420',
                fill: 'origin',
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                order: 1,
            },
        ],
    });

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index' as const,
        },
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 12,
                    usePointStyle: true,
                    font: { size: 11 },
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx: any) => {
                        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
                        return `${ctx.dataset.label}: ${ctx.raw.toLocaleString(numLocale)} ${t('common.sar')}`;
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 10 } },
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
</script>

<div class="size-full min-w-0 max-w-full overflow-hidden">
    {#if labels.length > 0}
        <Line {data} {options} />
    {:else}
        <div class="flex size-full items-center justify-center">
            <p class="text-sm text-muted-foreground">{t('dashboard.no_chart_data')}</p>
        </div>
    {/if}
</div>
