<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import axios from 'axios'

import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'

/* Types */
interface QuickAction {
  label: string
  description: string
  icon: Icon
  iconClass: string
  routeName: string
}

interface RecentActivityApi {
  type: string
  label: string
  occurred_at: string
}

interface MarketingSummary {
  customers: { total: number; verified: number; unverified: number }
  penawarans: { total: number; approved: number; unapproved: number }
  recent_activities: RecentActivityApi[]
}

const router = useRouter()

/* State: summary dari API */
const summary = ref<MarketingSummary | null>(null)
const loading = ref(false)

/* State: quick actions (route beneran, bukan mock) */
const quickActions: QuickAction[] = [
  {
    label: 'Customer',
    description: 'Tambah customer baru',
    icon: 'UserPlus',
    iconClass: 'bg-primary/10 text-primary',
    routeName: 'customers-create',
  },
  {
    label: 'Buat Quotation',
    description: 'Ajukan penawaran baru',
    icon: 'FilePlus',
    iconClass: 'bg-emerald-100 text-emerald-600',
    routeName: 'penawarans-create',
  },
]

/* Computed: recent activity dari summary API */
const recentActivities = computed(() => summary.value?.recent_activities ?? [])

/* Helper: icon + warna per tipe activity */
function activityIcon(type: string): { icon: Icon; iconClass: string } {
  switch (type) {
    case 'customer_created':
      return { icon: 'UserPlus', iconClass: 'bg-primary/10 text-primary' }
    case 'customer_link_generated':
      return { icon: 'Link2', iconClass: 'bg-sky-100 text-sky-600' }
    case 'penawaran_created':
      return { icon: 'FilePlus', iconClass: 'bg-emerald-100 text-emerald-600' }
    case 'penawaran_status_changed':
      return { icon: 'BadgeCheck', iconClass: 'bg-amber-100 text-amber-600' }
    default:
      return { icon: 'History', iconClass: 'bg-slate-100 text-slate-600' }
  }
}

/* Helper: waktu relatif berbahasa Indonesia */
function formatRelativeTime(iso: string): string {
  const occurredAt = new Date(iso)
  const diffMs = Date.now() - occurredAt.getTime()
  const diffMinutes = Math.floor(diffMs / 60000)

  if (diffMinutes < 1) return 'Baru saja'
  if (diffMinutes < 60) return `${diffMinutes} menit lalu`

  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours} jam lalu`

  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 30) return `${diffDays} hari lalu`

  return occurredAt.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

/* Action: navigasi quick action */
function goToQuickAction(routeName: string) {
  router.push({ name: routeName })
}

/* Init: fetch ringkasan dashboard marketing */
onMounted(async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/dashboard/marketing-summary')
    summary.value = data
  } catch (e: any) {
    // tampilkan state kosong/gagal, jangan biarkan halaman crash
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="intro-y flex flex-col gap-4">
    <PageHeader title="Dashboard Marketing" description="Ringkasan aktivitas customer dan quotation kamu" />

    <!-- Ringkasan + Quick Actions -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <CardSection title="Ringkasan Customer" description="Customer yang kamu kelola" icon="Users"
        icon-class="bg-primary/10 text-primary">
        <div class="grid grid-cols-3 gap-3">
          <div>
            <div class="font-label">Total</div>
            <div class="font-num-display-lg mt-1">{{ loading || !summary ? '-' : summary.customers.total }}</div>
          </div>
          <div>
            <div class="font-label">Verified</div>
            <div class="font-num-display-lg mt-1 !text-emerald-600">{{ loading || !summary ? '-' :
              summary.customers.verified }}</div>
          </div>
          <div>
            <div class="font-label">Unverified</div>
            <div class="font-num-display-lg mt-1 !text-amber-600">{{ loading || !summary ? '-' :
              summary.customers.unverified }}</div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Ringkasan Penawaran" description="Status approval penawaran kamu" icon="FileText"
        icon-class="bg-emerald-100 text-emerald-600">
        <div class="grid grid-cols-3 gap-3">
          <div>
            <div class="font-label">Total</div>
            <div class="font-num-display-lg mt-1">{{ loading || !summary ? '-' : summary.penawarans.total }}</div>
          </div>
          <div>
            <div class="font-label">Approved</div>
            <div class="font-num-display-lg mt-1 !text-emerald-600">{{ loading || !summary ? '-' :
              summary.penawarans.approved }}</div>
          </div>
          <div>
            <div class="font-label">Unapproved</div>
            <div class="font-num-display-lg mt-1 !text-amber-600">{{ loading || !summary ? '-' :
              summary.penawarans.unapproved }}</div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Quick Actions" description="Akses cepat ke aktivitas harian kamu" icon="Zap"
        icon-class="bg-amber-100 text-amber-600">
        <div class="grid grid-cols-2 gap-3">
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
    </div>

    <!-- Recent Activity -->
    <CardSection title="Aktivitas Terbaru" description="Aktivitas customer & quotation terakhir" icon="History"
      icon-class="bg-slate-100 text-slate-600">
      <div v-if="!loading && !recentActivities.length" class="py-3 text-center">
        <div class="font-caption">Belum ada aktivitas terbaru.</div>
      </div>
      <div v-else class="divide-y divide-slate-100">
        <div v-for="(activity, idx) in recentActivities" :key="idx"
          class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
            :class="activityIcon(activity.type).iconClass">
            <Lucide :icon="activityIcon(activity.type).icon" class="h-4 w-4" />
          </div>
          <div class="flex-1">
            <div class="font-body">{{ activity.label }}</div>
          </div>
          <div class="font-caption shrink-0">{{ formatRelativeTime(activity.occurred_at) }}</div>
        </div>
      </div>
    </CardSection>
  </div>
</template>
