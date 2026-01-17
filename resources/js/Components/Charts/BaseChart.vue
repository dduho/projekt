<template>
    <div class="w-full">
        <apexchart
            v-if="options"
            :type="type"
            :height="height"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'line',
        validator: (value) => ['line', 'area', 'bar', 'pie', 'donut', 'radialBar', 'scatter', 'bubble', 'heatmap', 'candlestick', 'boxPlot', 'radar', 'polarArea', 'rangeBar', 'rangeArea', 'treemap'].includes(value)
    },
    series: {
        type: Array,
        required: true
    },
    options: {
        type: Object,
        default: () => ({})
    },
    height: {
        type: [String, Number],
        default: 350
    },
    theme: {
        type: String,
        default: 'dark'
    }
});

const chartOptions = computed(() => {
    const defaultOptions = {
        chart: {
            type: props.type,
            background: 'transparent',
            foreColor: '#9ca3af',
            fontFamily: 'Inter, sans-serif',
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            }
        },
        theme: {
            mode: props.theme
        },
        colors: ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#8b5cf6'],
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.1)',
            strokeDashArray: 4
        },
        xaxis: {
            labels: {
                style: {
                    colors: '#9ca3af'
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#9ca3af'
                }
            }
        },
        legend: {
            labels: {
                colors: '#9ca3af'
            }
        },
        tooltip: {
            theme: props.theme,
            style: {
                fontSize: '12px',
                fontFamily: 'Inter, sans-serif'
            }
        }
    };

    return { ...defaultOptions, ...props.options };
});
</script>
