<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import axios from 'axios'
import { Chart as ChartJS, type ChartData, type ChartOptions, type Plugin } from 'chart.js/auto'

import Lucide from '@/components/Base/Lucide'
import Chart from '@/components/Base/Chart'
import { FormSelect } from '@/components/Base/Form'
import DashboardSummaryCard from '@/pages/Dashboard/components/DashboardSummaryCard.vue'
import { useAuthStore } from '@/stores/auth'
import { getColor, getDonutColors } from '@/utils/colors'
import { formatCurrency } from '@/utils/format'

/* Types */
interface VendorPoQueueItem {
  id_po: number
  nomor_po: string
  vendor_name: string | null
  total_order: number
  waiting_since: string
  aging_days: number
}

interface VendorPoStatusBucket {
  count: number
  total_order: number
}

interface VendorPoVendorBucket {
  vendor_name: string | null
  count: number
  total_order: number
}

interface PendingCeoPricePeriodItem {
  periode_awal: string
  periode_akhir: string
  jumlah_data: number
  jumlah_belum_lengkap: number
}

interface PoMonthlyTrendPoint {
  month: string
  label: string
  total: number
}

interface CeoSummary {
  vendor_po_approval_queue: { total: number; items: VendorPoQueueItem[] }
  vendor_po_value_summary: {
    by_status: Record<string, VendorPoStatusBucket>
    by_vendor: Record<string, VendorPoVendorBucket>
  }
  vendor_po_monthly_trend: PoMonthlyTrendPoint[]
  pending_ceo_price_period: { total: number; items: PendingCeoPricePeriodItem[] }
}

const auth = useAuthStore()

/* State: summary dari API */
const summary = ref<CeoSummary | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

/* State + fetch: donut valuasi per vendor, tahun independen dari chart tren PO */
const vendorByVendor = ref<Record<string, VendorPoVendorBucket>>({})
const vendorValueLoading = ref(false)

async function fetchVendorValueSummary(year: number) {
  vendorValueLoading.value = true
  try {
    const { data } = await axios.get('/api/dashboard/ceo-vendor-value-summary', { params: { year } })
    vendorByVendor.value = data.by_vendor
  } catch (e: any) {
    vendorByVendor.value = {}
  } finally {
    vendorValueLoading.value = false
  }
}

const byVendorList = computed(() =>
  Object.entries(vendorByVendor.value).map(([idVendor, bucket]) => ({
    idVendor,
    ...bucket,
  })),
)

const userName = computed(() => auth.user?.name ?? 'CEO')

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})
/* Computed: KPI stat strip */
const kpiApprovalQueueTotal = computed(() => summary.value?.vendor_po_approval_queue.total ?? 0)
// fixed ke tahun berjalan, gak ada dropdown -- beda dari donut per-vendor di bawah
const kpiStockValuation = computed(() => {
  const byStatus = summary.value?.vendor_po_value_summary.by_status
  return formatCurrency(byStatus?.Approved?.total_order ?? 0)
})
const kpiPendingCeoPriceTotal = computed(() => summary.value?.pending_ceo_price_period.total ?? 0)

/* State + fetch: chart line tren jumlah PO per bulan, per tahun (dropdown) */
const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - i)
const monthlyTrendPoints = ref<PoMonthlyTrendPoint[]>([])
const monthlyTrendLoading = ref(false)

async function fetchMonthlyTrend(year: number) {
  monthlyTrendLoading.value = true
  try {
    const { data } = await axios.get('/api/dashboard/ceo-po-monthly-trend', { params: { year } })
    monthlyTrendPoints.value = data.points
  } catch (e: any) {
    monthlyTrendPoints.value = []
  } finally {
    monthlyTrendLoading.value = false
  }
}

watch(selectedYear, year => { fetchMonthlyTrend(year) })

// Chart.js v4 gak punya opsi bawaan buat dash garis grid, jadi digambar manual di sini, di-scope via flag `plugins.dashedYGrid`
const dashedYGridPlugin: Plugin<'line'> = {
  id: 'dashedYGrid',
  beforeDraw(chart) {
    if (!(chart.options.plugins as Record<string, unknown>)?.dashedYGrid) return
    const { ctx, chartArea, scales } = chart
    if (!chartArea || !scales.y) return

    ctx.save()
    ctx.setLineDash([4, 4])
    ctx.strokeStyle = getColor('slate.200')
    ctx.lineWidth = 1
    scales.y.ticks.forEach((_, index) => {
      const y = scales.y.getPixelForTick(index)
      ctx.beginPath()
      ctx.moveTo(chartArea.left, y)
      ctx.lineTo(chartArea.right, y)
      ctx.stroke()
    })
    ctx.restore()
  },
}
ChartJS.register(dashedYGridPlugin)

// bulan depan di-null-kan (bukan dipotong) biar garisnya putus di situ, cuma buat tahun berjalan -- tahun lalu tetep tampil apa adanya
const currentMonthIndex = new Date().getMonth() + 1
const relevantTotals = computed(() => {
  const points = monthlyTrendPoints.value
  return selectedYear.value === currentYear
    ? points.slice(0, currentMonthIndex).map(point => point.total)
    : points.map(point => point.total)
})

const hasMonthlyTrendData = computed(() => relevantTotals.value.some(total => total > 0))
const monthlyTrendChartData = computed<ChartData<'line'>>(() => {
  const points = monthlyTrendPoints.value
  return {
    labels: points.map(point => point.label),
    datasets: [
      {
        label: 'Jumlah PO',
        data: points.map((point, index) =>
          selectedYear.value === currentYear && index >= currentMonthIndex ? null : point.total,
        ),
        borderColor: getColor('primary'),
        backgroundColor: getColor('primary', 0.15),
        fill: true,
        tension: 0.3,
        pointRadius: 3,
        pointBackgroundColor: getColor('primary'),
      },
    ],
  }
})
// spare 1 baris di atas nilai max, step dipaksa 1 biar "spare 1 baris" selalu 1 tick beneran
const monthlyTrendYMax = computed(() => Math.max(0, ...relevantTotals.value) + 1)
const monthlyTrendChartOptions = computed<ChartOptions<'line'>>(() => ({
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    // bukan opsi resmi Chart.js, flag ini dibaca sendiri sama dashedYGridPlugin di atas
    ...({ dashedYGrid: true } as Record<string, unknown>),
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: getColor('slate.500') } },
    y: {
      beginAtZero: true,
      max: monthlyTrendYMax.value,
      ticks: { color: getColor('slate.500'), precision: 0, stepSize: 1 },
      grid: { display: false },
    },
  },
}))

// dropdown tahun donut vendor, sengaja independen dari selectedYear chart tren PO
const selectedVendorYear = ref(currentYear)
watch(selectedVendorYear, year => { fetchVendorValueSummary(year) })

/* Computed: chart donut valuasi PO Approved per vendor (by_vendor) */
const hasVendorData = computed(() => byVendorList.value.length > 0)
const vendorChartData = computed<ChartData<'doughnut'>>(() => ({
  labels: byVendorList.value.map(vendor => vendor.vendor_name ?? '-'),
  datasets: [
    {
      data: byVendorList.value.map(vendor => vendor.total_order),
      // warna diurutin berdasar RANK persentase (vendor terbesar dapet warna pertama), bukan urutan index
      backgroundColor: getDonutColors(byVendorList.value.map(vendor => vendor.total_order)),
      borderWidth: 2,
      borderColor: getColor('white'),
    },
  ],
}))
const vendorChartOptions = computed<ChartOptions<'doughnut'>>(() => ({
  maintainAspectRatio: false,
  cutout: '65%',
  plugins: {
    legend: {
      position: 'right',
      align: 'center',
      labels: {
        color: getColor('slate.600'),
        boxWidth: 10,
        padding: 12,
        // itung persentase dari dataset chart langsung, bukan byVendorList luar -- biar konsisten pas ada legend item ke-toggle
        generateLabels(chart) {
          const dataset = chart.data.datasets[0]
          const values = (dataset.data as number[]) ?? []
          const total = values.reduce((sum, value) => sum + (value ?? 0), 0)
          const colors = dataset.backgroundColor as string[]

          return (chart.data.labels as string[]).map((label, index) => {
            const value = values[index] ?? 0
            const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0'
            return {
              text: `${label} (${percent}%)`,
              fillStyle: colors[index],
              strokeStyle: dataset.borderColor as string,
              lineWidth: dataset.borderWidth as number,
              hidden: false,
              index,
            }
          })
        },
      },
    },
  },
}))

// error-nya ditampilin, bukan disembunyiin
onMounted(async () => {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get('/api/dashboard/ceo-summary')
    summary.value = data
    monthlyTrendPoints.value = data.vendor_po_monthly_trend
    vendorByVendor.value = data.vendor_po_value_summary.by_vendor
  } catch (e: any) {
    error.value = 'Gagal memuat data dashboard CEO. Silakan muat ulang halaman.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="flex flex-col gap-4 intro-y">
    <div v-if="loading" class="p-8 rounded-2xl text-center box">
      <div class="font-body">Memuat data dashboard...</div>
    </div>

    <div v-else-if="error" class="bg-red-50 p-6 border border-red-200 rounded-2xl box">
      <div class="flex items-center gap-3">
        <Lucide icon="AlertCircle" class="w-5 h-5 text-red-600 shrink-0" />
        <div class="font-body !text-red-700">{{ error }}</div>
      </div>
    </div>

    <template v-else-if="summary">
      <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div
          class="relative flex flex-col justify-center bg-gradient-to-br from-theme-1 via-theme-2 to-emerald-900 shadow-sm p-5 rounded-2xl overflow-hidden">
          <svg class="-right-6 -bottom-6 absolute w-36 h-36 pointer-events-none" viewBox="0 0 160 160" fill="none"
            aria-hidden="true">
            <rect x="64" y="24" width="44" height="44" rx="6" transform="rotate(20 68 30)" stroke="white"
              stroke-opacity="0.3" stroke-width="2" />
            <circle cx="112" cy="70" r="38" fill="white" fill-opacity="0.1" />
            <circle cx="88" cy="118" r="24" stroke="white" stroke-opacity="0.35" stroke-width="2" />
            <circle cx="128" cy="128" r="16" fill="white" fill-opacity="0.2" />
            <circle cx="145" cy="95" r="9" fill="white" fill-opacity="0.28" />
          </svg>

          <div class="z-10 relative">
            <div class="text-white/80 text-lg">{{ greeting }},</div>
            <div class="mt-1 font-bold text-white text-2xl">{{ userName }}</div>
          </div>
        </div>
        <DashboardSummaryCard label="Antrian Approval PO" :value="kpiApprovalQueueTotal" icon="ClipboardCheck"
          icon-class="bg-blue-100 text-blue-600" description="Menunggu verifikasi CEO"
          :link-to="{ name: 'po-verification-list' }" link-label="Lihat semua" />
        <DashboardSummaryCard label="Valuasi Stok Procurement" :value="kpiStockValuation" icon="Banknote"
          icon-class="bg-primary/10 text-primary" description="Nilai PO Supplier disetujui tahun ini"
          :link-to="{ name: 'vendor-pos-list' }" link-label="Lihat PO Supplier" />
        <DashboardSummaryCard label="Periode Harga Menunggu CEO" :value="kpiPendingCeoPriceTotal" icon="FileClock"
          icon-class="bg-amber-100 text-amber-600" description="Margin/pricelist belum diisi"
          :link-to="{ name: 'produk-hargas' }" link-label="Kelola harga" />
      </div>

      <div class="gap-4 grid grid-cols-1 lg:grid-cols-2">
        <DashboardSummaryCard title="Tren PO Supplier per Bulan" :description="`Jumlah PO per bulan — ${selectedYear}`"
          icon="TrendingUp" icon-class="bg-blue-100 text-blue-600">
          <template #action>
            <FormSelect v-model.number="selectedYear" class="!w-28">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </FormSelect>
          </template>
          <div v-if="monthlyTrendLoading" class="flex justify-center items-center h-full min-h-64">
            <div class="font-caption">Memuat data...</div>
          </div>
          <div v-else-if="!hasMonthlyTrendData" class="flex justify-center items-center h-full min-h-64">
            <div class="font-caption">Belum ada data PO di tahun {{ selectedYear }}.</div>
          </div>
          <div v-else class="h-full min-h-64">
            <Chart type="line" :data="monthlyTrendChartData" :options="monthlyTrendChartOptions" />
          </div>
        </DashboardSummaryCard>

        <DashboardSummaryCard title="Valuasi Stok per Vendor"
          :description="`Pembagian nilai PO Approved per vendor — ${selectedVendorYear}`" icon="Banknote"
          icon-class="bg-emerald-100 text-emerald-600">
          <template #action>
            <FormSelect v-model.number="selectedVendorYear" class="!w-28">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </FormSelect>
          </template>
          <div v-if="vendorValueLoading" class="flex justify-center items-center h-full min-h-64">
            <div class="font-caption">Memuat data...</div>
          </div>
          <div v-else-if="!hasVendorData" class="flex justify-center items-center h-full min-h-64">
            <div class="font-caption">Belum ada PO Approved per vendor di tahun {{ selectedVendorYear }}.</div>
          </div>
          <div v-else class="h-full min-h-64">
            <Chart type="doughnut" :data="vendorChartData" :options="vendorChartOptions" />
          </div>
        </DashboardSummaryCard>
      </div>
    </template>
  </div>
</template>
