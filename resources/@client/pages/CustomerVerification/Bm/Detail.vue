<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import ApprovalTimeline from '../components/ApprovalTimeline.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface TimelineCycle {
  id_approval: number
  status: 'in_progress' | 'approved' | 'rejected'
  steps: { step_order: number; step_name: string | null; status: string }[]
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const id = Number(route.params.id)

const loading = ref(true)
const decisionLoading = ref(false)
const approveDialogOpen = ref(false)
const rejectDialogOpen = ref(false)
const rejectNote = ref('')

const customer = ref<any>({})

/* Section: Evaluasi Admin Finance (read-only, referensi keputusan BM) */
const evaluation = ref({
  top_text: '-',
  potential_volume: '-',
  credit_limit_request: '-',
  financial_review: '-',
})

const cycles = ref<TimelineCycle[]>([])
const latestCycle = computed<TimelineCycle | null>(() => cycles.value[0] ?? null)
const canDecide = computed(() => {
  if (!latestCycle.value) return false
  if (latestCycle.value.status !== 'in_progress') return false
  const step2 = latestCycle.value.steps.find(s => s.step_order === 2)
  return step2?.status === 'pending'
})

function onTimelineLoaded(loaded: TimelineCycle[]) {
  cycles.value = loaded
}

async function loadAll() {
  loading.value = true
  try {
    const [metaRes, adminEvalRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${id}`),
      axios.get(`/api/review/customer-verifications/${id}/admin-evaluation`).catch(() => ({ data: null })),
    ])

    customer.value = metaRes.data?.customer || {}

    const ev = adminEvalRes?.data
    if (ev) {
      evaluation.value = {
        top_text: ev.top_text || '-',
        potential_volume: ev.potential_volume || '-',
        credit_limit_request: ev.credit_limit_request || '-',
        financial_review: ev.financial_review || '-',
      }
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data verifikasi customer.')
  } finally {
    loading.value = false
  }
}

async function submitDecision(decision: 'APPROVE' | 'REJECT') {
  decisionLoading.value = true
  try {
    await axios.patch(`/api/review/bm/customer-verifications/${id}/verify`, {
      decision,
      notes: decision === 'REJECT' ? rejectNote.value : undefined,
    })

    approveDialogOpen.value = false
    rejectDialogOpen.value = false

    if (decision === 'APPROVE') {
      success('Berhasil', 'Customer disetujui & terverifikasi.')
    } else {
      success('Berhasil', 'Pengajuan ditolak & dikembalikan ke Marketing.')
    }

    router.push({ name: 'verify-data-customer-bm' })
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan keputusan BM.')
  } finally {
    decisionLoading.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(loadAll)
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">Verifikasi Data Customer — BM</h2>
          <p class="font-lead mt-1">Keputusan final: <code>{{ customer.nama_perusahaan || '-' }}</code></p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data...</span>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
          <CardSection title="Customer" icon="Building2" icon-class="bg-indigo-100 text-indigo-600">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div>
                <dt class="font-label">Nama Perusahaan</dt>
                <dd class="font-strong mt-1">{{ customer.nama_perusahaan || '-' }}</dd>
              </div>
              <div>
                <dt class="font-label">Alamat</dt>
                <dd class="font-strong mt-1">{{ customer.alamat_perusahaan || '-' }}</dd>
              </div>
            </dl>
          </CardSection>

          <CardSection title="Evaluasi Admin Finance" description="Referensi keputusan Admin Finance (read-only)"
            icon="ClipboardCheck" icon-class="bg-emerald-100 text-emerald-600">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div>
                <dt class="font-label">TOP</dt>
                <dd class="font-strong mt-1">{{ evaluation.top_text }}</dd>
              </div>
              <div>
                <dt class="font-label">Potential Volume</dt>
                <dd class="font-strong mt-1">{{ evaluation.potential_volume }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="font-label">Pengajuan Credit Limit</dt>
                <dd class="font-strong mt-1">{{ evaluation.credit_limit_request }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="font-label">Financial Review</dt>
                <dd class="font-body mt-1 whitespace-pre-line">{{ evaluation.financial_review }}</dd>
              </div>
            </dl>
          </CardSection>
        </div>

        <div class="xl:col-span-1">
          <div class="sticky top-6 space-y-4">
            <ApprovalTimeline :id="id" @loaded="onTimelineLoaded" />

            <CardSection title="Keputusan BM" icon="Gavel" icon-class="bg-primary/10 text-primary">
              <div class="space-y-3">
                <p v-if="!canDecide" class="font-body rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 !text-amber-700">
                  Verifikasi ini tidak sedang menunggu keputusan BM (mungkin sudah diputuskan atau belum sampai step ini).
                </p>

                <div v-else class="flex flex-col gap-2">
                  <Button variant="danger" class="inline-flex w-full items-center justify-center gap-2"
                    @click="rejectDialogOpen = true">
                    <Lucide icon="X" class="h-4 w-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2"
                    @click="approveDialogOpen = true">
                    <Lucide icon="Check" class="h-4 w-4" />
                    Setujui Customer
                  </Button>
                </div>
              </div>
            </CardSection>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ConfirmDialog :open="approveDialogOpen" title="Setujui customer ini?"
    description="Customer akan berstatus terverifikasi. Tindakan ini adalah keputusan final." confirm-text="Ya, Setujui"
    icon="CheckCircle" icon-class="bg-success/10 text-success" variant="success" :loading="decisionLoading"
    @close="approveDialogOpen = false" @confirm="() => submitDecision('APPROVE')" />

  <ConfirmDialog :open="rejectDialogOpen" title="Tolak pengajuan ini?"
    description="Verifikasi akan dikembalikan ke Marketing untuk direvisi ulang." confirm-text="Ya, Tolak" icon="XCircle"
    icon-class="bg-danger/10 text-danger" variant="danger" :loading="decisionLoading"
    @close="rejectDialogOpen = false; rejectNote = ''" @confirm="() => submitDecision('REJECT')">
    <FormLabel>Alasan Penolakan</FormLabel>
    <FormTextarea v-model="rejectNote" placeholder="Jelaskan alasan penolakan..." :rows="3" />
  </ConfirmDialog>
</template>
