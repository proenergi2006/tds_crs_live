<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import axios from 'axios'
import { type ChartData, type ChartOptions } from 'chart.js/auto'

import Lucide from '@/components/Base/Lucide'
import Chart from '@/components/Base/Chart'
import DashboardSummaryCard from '@/pages/Dashboard/components/DashboardSummaryCard.vue'
import { useAuthStore } from '@/stores/auth'
import { getColor, getDonutColors } from '@/utils/colors'

/* Types */
interface PenawaranQueueItem {
  id_penawaran: number
  nomor_penawaran: string
}

interface PenawaranFunnel {
  draft: number
  waiting_bm: number
  waiting_om: number
  approved: number
  rejected_bm: number
  rejected_om: number
}

interface OmSummary {
  penawaran_approval_queue: { total: number; items: PenawaranQueueItem[] }
  penawaran_funnel: PenawaranFunnel
}

const auth = useAuthStore()

/* State: summary dari API */
const summary = ref<OmSummary | null>(null)
const loading = ref(false)
const error = ref<string | null>(null)

const userName = computed(() => auth.user?.name ?? 'OM')

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 11) return 'Selamat Pagi'
  if (hour < 15) return 'Selamat Siang'
  if (hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})

/* Computed: KPI stat strip */
const kpiApprovalQueueTotal = computed(() => summary.value?.penawaran_approval_queue.total ?? 0)
const kpiApprovedTotal = computed(() => summary.value?.penawaran_funnel.approved ?? 0)

/* Label & urutan funnel: selalu tampil 6 key, konsisten dengan backend */
const funnelLabels: Record<keyof PenawaranFunnel, string> = {
  draft: 'Draft',
  waiting_bm: 'Menunggu BM',
  waiting_om: 'Menunggu OM',
  approved: 'Disetujui',
  rejected_bm: 'Ditolak BM',
  rejected_om: 'Ditolak OM',
}
const funnelOrder: Array<keyof PenawaranFunnel> = [
  'draft',
  'waiting_bm',
  'waiting_om',
  'approved',
  'rejected_bm',
  'rejected_om',
]

/* Computed: chart doughnut funnel penawaran (selalu 6 slice, termasuk yang 0) */
const funnelChartData = computed<ChartData<'doughnut'>>(() => {
  const values = funnelOrder.map(key => summary.value?.penawaran_funnel[key] ?? 0)
  return {
    labels: funnelOrder.map(key => funnelLabels[key]),
    datasets: [
      {
        data: values,
        // warna diurutin berdasar RANK, bukan semantik tetap per status -- konsisten sama donut Ceo.vue
        backgroundColor: getDonutColors(values),
        borderWidth: 2,
        borderColor: getColor('white'),
      },
    ],
  }
})
const funnelChartOptions = computed<ChartOptions<'doughnut'>>(() => ({
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
        // itung persentase dari dataset chart langsung, bukan summary luar -- pola sama kayak donut Ceo.vue
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
    const { data } = await axios.get('/api/dashboard/om-summary')
    summary.value = data
  } catch (e: any) {
    error.value = 'Gagal memuat data dashboard OM. Silakan muat ulang halaman.'
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
      <!-- kolom kanan (Quick Links) row-span-2 biar tingginya nyamain greeting+Penawaran Disetujui+Funnel digabung -->
      <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
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

        <DashboardSummaryCard label="Penawaran Disetujui" :value="kpiApprovedTotal" icon="BadgeCheck"
          icon-class="bg-emerald-100 text-emerald-600" description="Total Penawaran yang sudah disetujui"
          :link-to="{ name: 'penawarans-verifikasi' }" link-label="Lihat semua" />

        <DashboardSummaryCard class="lg:row-span-2" label="Antrian Approval Penawaran" :value="kpiApprovalQueueTotal"
          icon="ClipboardCheck" icon-class="bg-blue-100 text-blue-600" description="Menunggu verifikasi OM">
          <template #action>
            <div class="flex flex-col gap-3 px-5 pt-4 pb-5">
              <div class="font-label">Quick Links</div>
              <div v-if="!summary.penawaran_approval_queue.items.length" class="py-3 text-center">
                <div class="font-caption">Tidak ada Penawaran menunggu approval.</div>
              </div>
              <div v-else class="flex flex-col gap-2">
                <RouterLink v-for="p in summary.penawaran_approval_queue.items" :key="p.id_penawaran"
                  :to="{ name: 'penawarans-verifikasi-om-detail', params: { id: p.id_penawaran } }"
                  class="flex justify-between items-center bg-white hover:bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl font-body text-sm">
                  {{ p.nomor_penawaran }}
                  <Lucide icon="ArrowRight" class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                </RouterLink>
              </div>
            </div>
          </template>
        </DashboardSummaryCard>

        <DashboardSummaryCard class="lg:col-span-2" title="Funnel Penawaran"
          description="Distribusi status seluruh Penawaran" icon="BarChart3"
          icon-class="bg-emerald-100 text-emerald-600">
          <div class="h-full min-h-64">
            <Chart type="doughnut" :data="funnelChartData" :options="funnelChartOptions" />
          </div>
        </DashboardSummaryCard>
      </div>
    </template>
  </div>
</template>
