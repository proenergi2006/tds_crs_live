<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'

/* Types */
interface FunnelStatus {
  key: string
  label: string
  count: number
  barClass: string
  dotClass: string
}

interface QuickAction {
  label: string
  description: string
  icon: Icon
  iconClass: string
  routeName: string
}

interface RecentActivity {
  text: string
  time: string
  icon: Icon
  iconClass: string
}

const router = useRouter()

/* State: stat cards (mock, frontend-only — jangan panggil API) */
const stats = {
  totalCustomer: 71,
  verified: 12,
  unverified: 59,
  totalQuotations: 34,
}

/*
 * State: Customer Onboarding Funnel (mock)
 * Label & pemetaan warna mengikuti persis getVerificationBadgeClass/Label di
 * Customer/Index.vue. Untuk segmen bar & dot legend dipakai varian solid dari
 * hue yang sama (bukan skema warna baru) supaya kontras terbaca di atas bar —
 * badge aslinya pakai bg-*-100 yang terlalu pudar untuk fill sebesar ini.
 * "link_kedaluwarsa" dan "ditolak" sama-sama red pada badge asli, di sini
 * dibedakan jadi red-400 vs red-600 agar dua segmen tetap kebeda di bar.
 */
const funnelStatuses: FunnelStatus[] = [
  { key: 'verified', label: 'Verified', count: 12, barClass: 'bg-emerald-500', dotClass: 'bg-emerald-500' },
  { key: 'belum_ada_link', label: 'Belum Ada Link', count: 18, barClass: 'bg-slate-400', dotClass: 'bg-slate-400' },
  { key: 'menunggu_customer', label: 'Menunggu Customer', count: 24, barClass: 'bg-amber-500', dotClass: 'bg-amber-500' },
  { key: 'link_kedaluwarsa', label: 'Link Kedaluwarsa', count: 5, barClass: 'bg-red-400', dotClass: 'bg-red-400' },
  { key: 'perlu_direview', label: 'Perlu Direview', count: 6, barClass: 'bg-sky-500', dotClass: 'bg-sky-500' },
  { key: 'proses_internal', label: 'Proses Internal', count: 4, barClass: 'bg-indigo-500', dotClass: 'bg-indigo-500' },
  { key: 'ditolak', label: 'Ditolak', count: 2, barClass: 'bg-red-600', dotClass: 'bg-red-600' },
]

const funnelTotal = computed(() =>
  funnelStatuses.reduce((sum, status) => sum + status.count, 0),
)

/* State: quick actions (route beneran, bukan mock) */
const quickActions: QuickAction[] = [
  {
    label: 'Customer',
    description: 'Kelola data customer kamu',
    icon: 'User',
    iconClass: 'bg-primary/10 text-primary',
    routeName: 'customers-list',
  },
  {
    label: 'Buat Quotation',
    description: 'Ajukan penawaran baru',
    icon: 'FilePlus',
    iconClass: 'bg-emerald-100 text-emerald-600',
    routeName: 'penawarans-create',
  },
  {
    label: 'Sales Order',
    description: 'Pantau PO customer',
    icon: 'ShoppingCart',
    iconClass: 'bg-amber-100 text-amber-600',
    routeName: 'po-customers-index',
  },
  {
    label: 'Customer Onboarding',
    description: 'Kelola link verifikasi customer',
    icon: 'Link2',
    iconClass: 'bg-sky-100 text-sky-600',
    routeName: 'link-customers',
  },
]

/* State: recent activity (mock, opsional) */
const recentActivities: RecentActivity[] = [
  {
    text: 'Customer PT Sumber Makmur submit data verifikasi',
    time: '2 jam lalu',
    icon: 'ClipboardCheck',
    iconClass: 'bg-sky-100 text-sky-600',
  },
  {
    text: 'Quotation QT-2026-0142 untuk PT Karya Abadi disetujui BM',
    time: '5 jam lalu',
    icon: 'BadgeCheck',
    iconClass: 'bg-emerald-100 text-emerald-600',
  },
  {
    text: 'Link verifikasi customer PT Cipta Selaras kedaluwarsa',
    time: '1 hari lalu',
    icon: 'AlertTriangle',
    iconClass: 'bg-red-100 text-red-600',
  },
  {
    text: 'Customer baru PT Mitra Jaya berhasil ditambahkan',
    time: '2 hari lalu',
    icon: 'UserPlus',
    iconClass: 'bg-primary/10 text-primary',
  },
]

/* Helper: proporsi segmen funnel dalam persen (dari total mock) */
function funnelPercent(count: number) {
  if (!funnelTotal.value) return 0
  return (count / funnelTotal.value) * 100
}

/* Action: navigasi quick action */
function goToQuickAction(routeName: string) {
  router.push({ name: routeName })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Dashboard Marketing"
        description="Ringkasan aktivitas customer dan quotation kamu" />

      <!-- Stat cards -->
      <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="box p-4">
          <div class="font-label">Total Customer</div>
          <div class="font-num-display mt-1">{{ stats.totalCustomer }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Verified</div>
          <div class="font-num-display mt-1 !text-emerald-600">{{ stats.verified }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Unverified</div>
          <div class="font-num-display mt-1 !text-amber-600">{{ stats.unverified }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Total Quotations</div>
          <div class="font-num-display mt-1 !text-primary">{{ stats.totalQuotations }}</div>
        </div>
      </div>

      <!-- Customer Onboarding Funnel -->
      <CardSection title="Customer Onboarding Funnel"
        description="Sebaran status verifikasi customer di seluruh pipeline kamu" icon="Filter"
        icon-class="bg-primary/10 text-primary">
        <div class="flex h-8 w-full overflow-hidden rounded-full bg-slate-100">
          <div v-for="status in funnelStatuses" :key="status.key" :class="status.barClass"
            :style="{ flexBasis: funnelPercent(status.count) + '%' }" class="h-full shrink-0 grow-0"
            :title="`${status.label}: ${status.count}`" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="status in funnelStatuses" :key="status.key"
            class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
            <div class="flex items-center gap-2">
              <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="status.dotClass" />
              <span class="font-body">{{ status.label }}</span>
            </div>
            <span class="font-num">{{ status.count }}</span>
          </div>
        </div>
      </CardSection>

      <!-- Quick Actions -->
      <CardSection title="Quick Actions" description="Akses cepat ke aktivitas harian kamu" icon="Zap"
        icon-class="bg-amber-100 text-amber-600">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <button v-for="action in quickActions" :key="action.routeName" type="button"
            class="box flex items-center gap-3 p-4 text-left transition hover:shadow-md"
            @click="goToQuickAction(action.routeName)">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="action.iconClass">
              <Lucide :icon="action.icon" class="h-5 w-5" />
            </div>
            <div>
              <div class="font-strong">{{ action.label }}</div>
              <div class="font-caption mt-0.5">{{ action.description }}</div>
            </div>
          </button>
        </div>
      </CardSection>

      <!-- Recent Activity -->
      <CardSection title="Aktivitas Terbaru" description="Aktivitas customer & quotation terakhir" icon="History"
        icon-class="bg-slate-100 text-slate-600">
        <div class="divide-y divide-slate-100">
          <div v-for="(activity, idx) in recentActivities" :key="idx" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="activity.iconClass">
              <Lucide :icon="activity.icon" class="h-4 w-4" />
            </div>
            <div class="flex-1">
              <div class="font-body">{{ activity.text }}</div>
            </div>
            <div class="font-caption shrink-0">{{ activity.time }}</div>
          </div>
        </div>
      </CardSection>
    </div>
  </div>
</template>
