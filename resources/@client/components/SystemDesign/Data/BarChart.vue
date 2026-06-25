<script setup lang="ts">
import { computed } from 'vue'
import { type ChartData, type ChartOptions } from 'chart.js/auto'

import Chart from '@/components/Base/Chart'
import { useDarkModeStore } from '@/stores/dark-mode'
import { getColor } from '@/utils/colors'

type ChartItem = {
  label: string
  value: number | string
}

const props = withDefaults(
  defineProps<{
    data: ChartItem[];
    datasetLabel?: string;
    height?: number;
    valueSuffix?: string;
    yAxisLabelCount?: number;
  }>(),
  {
    datasetLabel: 'Nilai',
    height: 300,
    valueSuffix: '',
    yAxisLabelCount: 5,
  },
)

const darkMode = computed(() => useDarkModeStore().darkMode)

const labels = computed(() => props.data.map(item => item.label))
const values = computed(() => props.data.map(item => toNumber(item.value)))
const maxValue = computed(() => Math.max(...values.value, 0))
const barThickness = computed(() => {
  const total = values.value.length

  if (total <= 2) return 72
  if (total <= 4) return 56
  if (total <= 8) return 40
  if (total <= 14) return 28

  return 18
})

const chartData = computed<ChartData<'bar'>>(() => ({
  labels: labels.value,
  datasets: [
    {
      label: props.datasetLabel,
      data: values.value,
      barPercentage: values.value.length <= 4 ? 0.88 : 0.72,
      categoryPercentage: values.value.length <= 4 ? 0.82 : 0.72,
      barThickness: barThickness.value,
      maxBarThickness: 84,
      minBarLength: 2,
      borderRadius: {
        topLeft: 8,
        topRight: 8,
        bottomLeft: 8,
        bottomRight: 8,
      },
      backgroundColor(context: any) {
        const value = Number(context.raw || 0)
        const chart = context.chart
        const { chartArea, ctx } = chart

        if (!chartArea) {
          return value === maxValue.value
            ? getColor('primary', 0.88)
            : getColor('slate.200', darkMode.value ? 0.35 : 0.9)
        }

        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top)

        if (value === maxValue.value && maxValue.value > 0) {
          gradient.addColorStop(0, getColor('primary', 0.78))
          gradient.addColorStop(1, getColor('primary', 0.98))
          return gradient
        }

        gradient.addColorStop(0, darkMode.value ? getColor('slate.600', 0.42) : getColor('slate.200', 0.95))
        gradient.addColorStop(1, darkMode.value ? getColor('slate.500', 0.55) : getColor('slate.100', 0.95))

        return gradient
      },
      borderColor(context: any) {
        const value = Number(context.raw || 0)

        return value === maxValue.value && maxValue.value > 0
          ? getColor('primary')
          : getColor('slate.200', darkMode.value ? 0.25 : 0.95)
      },
      borderWidth: 1,
      hoverBackgroundColor: getColor('primary', 0.9),
    },
  ],
}))

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
  maintainAspectRatio: false,
  responsive: true,
  interaction: {
    intersect: false,
    mode: 'index',
  },
  layout: {
    padding: {
      top: 8,
      right: 8,
      bottom: 0,
      left: 2,
    },
  },
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: darkMode.value ? getColor('slate.800', 0.95) : '#ffffff',
      borderColor: getColor('slate.200', darkMode.value ? 0.25 : 0.9),
      borderWidth: 1,
      bodyColor: getColor('slate.700'),
      titleColor: getColor('slate.900'),
      padding: 12,
      displayColors: false,
      callbacks: {
        title(context) {
          return context[0]?.label ?? ''
        },
        label(context) {
          return `${props.datasetLabel}: ${formatNumber(context.parsed.y)}${props.valueSuffix}`
        },
      },
    },
  },
  scales: {
    x: {
      ticks: {
        color: getColor('slate.500', 0.85),
        maxRotation: 0,
        autoSkip: true,
        font: {
          size: 12,
          weight: '500',
        },
        callback(value) {
          const label = labels.value[Number(value)] ?? ''

          return truncateLabel(label)
        },
      },
      grid: {
        display: false,
      },
      border: {
        display: false,
      },
    },
    y: {
      beginAtZero: true,
      ticks: {
        color: getColor('slate.500', 0.85),
        count: props.yAxisLabelCount,
        font: {
          size: 12,
        },
        callback(value) {
          return `${formatNumber(Number(value))}${props.valueSuffix}`
        },
      },
      grid: {
        color: () => (
          darkMode.value
            ? getColor('slate.600', 0.22)
            : getColor('slate.200', 0.75)
        ),
        drawTicks: false,
      },
      border: {
        dash: [3, 3],
        display: false,
      },
    },
  },
}))

function toNumber(value: number | string) {
  const numberValue = typeof value === 'string'
    ? Number.parseFloat(value)
    : value

  return Number.isNaN(numberValue) ? 0 : numberValue
}

function formatNumber(value: number) {
  return Number(value || 0).toLocaleString('id-ID')
}

function truncateLabel(value: string) {
  if (value.length <= 14) return value

  return `${value.slice(0, 13)}...`
}
</script>

<template>
  <div class="space-y-4">
    <div
      v-if="data.length === 0"
      class="font-body flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50"
    >
      Belum ada data chart.
    </div>

    <template v-else>
      <div class="rounded-xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white px-3 py-4">
        <Chart
          type="bar"
          :height="height"
          :data="chartData"
          :options="chartOptions"
        />
      </div>
    </template>
  </div>
</template>
