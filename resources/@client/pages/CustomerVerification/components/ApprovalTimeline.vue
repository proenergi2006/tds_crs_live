<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import { formatDateTime } from '@/utils/format'

interface TimelineStep {
  step_order: number
  step_name: string | null
  status: 'pending' | 'approved' | 'rejected'
  actor_id: number | null
  actor_name: string | null
  acted_at: string | null
  decision_note: string | null
}

interface TimelineCycle {
  id_approval: number
  status: 'in_progress' | 'approved' | 'rejected'
  current_step_order: number | null
  started_at: string | null
  completed_at: string | null
  steps: TimelineStep[]
}

const props = defineProps<{ id: number }>()

const emit = defineEmits<{
  (e: 'loaded', cycles: TimelineCycle[]): void
}>()

const cycles = ref<TimelineCycle[]>([])
const loading = ref(false)

const CYCLE_LABEL: Record<TimelineCycle['status'], string> = {
  in_progress: 'Dalam Proses',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const CYCLE_BADGE: Record<TimelineCycle['status'], string> = {
  in_progress: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-rose-100 text-rose-700',
}

const STEP_LABEL: Record<TimelineStep['status'], string> = {
  pending: 'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const STEP_BADGE: Record<TimelineStep['status'], string> = {
  pending: 'bg-slate-100 text-slate-600',
  approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-rose-100 text-rose-700',
}

const STEP_ICON: Record<TimelineStep['status'], Icon> = {
  pending: 'Clock',
  approved: 'CheckCircle2',
  rejected: 'XCircle',
}

async function fetchTimeline() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/review/customer-verifications/${props.id}/approval-timeline`)
    cycles.value = data.data ?? []
  } catch {
    cycles.value = []
  } finally {
    loading.value = false
    emit('loaded', cycles.value)
  }
}

defineExpose({ refresh: fetchTimeline })

onMounted(fetchTimeline)
</script>

<template>
  <CardSection title="Riwayat Persetujuan" description="Siklus approval Admin Finance → BM" icon="History"
    icon-class="bg-success/10 text-success">
    <div v-if="loading" class="flex items-center justify-center gap-2 py-8 text-slate-500">
      <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
      <span class="font-body">Memuat riwayat...</span>
    </div>

    <div v-else-if="cycles.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center">
      <p class="font-body">Belum ada siklus persetujuan. Menunggu Marketing memforward data ini.</p>
    </div>

    <div v-else class="space-y-5">
      <div v-for="(cycle, idx) in cycles" :key="cycle.id_approval"
        class="rounded-xl border border-slate-200 p-4" :class="idx === 0 ? 'bg-white' : 'bg-slate-50'">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="font-strong">{{ idx === 0 ? 'Siklus Saat Ini' : `Siklus Sebelumnya` }}</span>
            <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5" :class="CYCLE_BADGE[cycle.status]">
              {{ CYCLE_LABEL[cycle.status] }}
            </span>
          </div>
          <span v-if="formatDateTime(cycle.started_at)" class="font-caption">
            Mulai {{ formatDateTime(cycle.started_at) }}
          </span>
        </div>

        <div class="space-y-3">
          <div v-for="step in cycle.steps" :key="step.step_order"
            class="flex items-start gap-3 rounded-lg border border-slate-100 bg-white px-3 py-2.5">
            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
              :class="STEP_BADGE[step.status]">
              <Lucide :icon="STEP_ICON[step.status]" class="h-4 w-4" />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-strong">{{ step.step_name || `Step ${step.step_order}` }}</span>
                <span class="font-label inline-flex items-center rounded-full px-2 py-0.5" :class="STEP_BADGE[step.status]">
                  {{ STEP_LABEL[step.status] }}
                </span>
              </div>

              <p v-if="step.actor_name || step.acted_at" class="font-caption mt-1">
                {{ step.actor_name || '-' }}
                <span v-if="formatDateTime(step.acted_at)">- {{ formatDateTime(step.acted_at) }}</span>
              </p>

              <p v-if="step.decision_note" class="font-body mt-1.5 whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2">
                {{ step.decision_note }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </CardSection>
</template>
