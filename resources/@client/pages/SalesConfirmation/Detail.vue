<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader variant="flat" :title="PAGE_TITLE" :description="PAGE_DESCRIPTION">
        <template #action>
          <Button variant="outline-secondary" @click="backToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
            Kembali
          </Button>
        </template>
      </PageHeader>

      <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
        <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
        <span class="text-body">Memuat Sales Confirmation...</span>
      </div>

      <div v-else-if="!detail" class="flex flex-col justify-center items-center gap-2 min-h-[320px] text-center">
        <div class="flex justify-center items-center bg-rose-50 rounded-full w-14 h-14">
          <Lucide icon="AlertTriangle" class="w-7 h-7 text-rose-500" />
        </div>
        <h3 class="text-section-title">Sales Confirmation tidak ditemukan</h3>
        <p class="text-body">Silakan kembali ke halaman sebelumnya.</p>
        <Button variant="outline-secondary" @click="backToIndex">
          <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
          Kembali ke daftar
        </Button>
      </div>

      <template v-else>
        <ScHero :detail="detail" :stage="stage" :rejection="rejectedStep" :refreshing="refreshing" />

        <div v-if="formError" role="alert"
          class="bg-rose-50 px-4 py-3 border border-rose-200 rounded-lg text-body !text-rose-700 whitespace-pre-line">
          {{ formError }}
        </div>

        <div class="items-start gap-4 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px]">
          <div class="space-y-4 min-w-0">
            <ScComparisonCard :detail="detail" :comparison="comparison" :quotation-href="quotationHref"
              :po-customer-href="poCustomerHref" />

            <div class="items-start gap-4 grid grid-cols-1 xl:grid-cols-2">
              <ScCompliancePanel :differences="comparison.differences" :checked="comparison.checked" />
              <ScCreditCard :credit="detail.credit" :credit-limit="Number(detail.customer.credit_limit ?? 0)"
                :buckets="arBuckets" />
            </div>
          </div>

          <aside aria-label="Status dan aksi" class="min-w-0 order-first lg:order-none"
            :class="isAdminEditable ? '' : 'lg:sticky lg:top-4'">
            <div class="space-y-4">
              <ScSubmitCard v-if="isAdminEditable" v-model:summary="form.admin_summary" v-model:files="newAttachments"
                :resubmit="isRejected" :submitting="submitting" :returning="returning" :disabled="refreshing"
                :existing-files="existingAdminFiles" @remove-existing="markAdminAttachmentRemoved"
                @submit="submitConfirmOpen = true" @return="returnDialogOpen = true" />

              <ScDecisionCard v-else-if="canDecide" :step-name="activeStep?.step_name ?? 'Approval'"
                :next-step-name="nextStep?.step_name ?? null" :submitting="decisionSubmitting || refreshing"
                @submit="submitDecision" />

              <ScActionResult v-else-if="actionResult" :tone="actionResult.tone" :title="actionResult.title"
                :message="actionResult.message" :detail="stage.label" />

              <ScStatusTimeline :steps="timeline" />

              <ScAdminNoteCard v-if="showAdminNote" :summary="admSummaryText" :author="detail.approval?.adm_pic ?? null"
                :attachments="adminAttachments" />
            </div>
          </aside>
        </div>
      </template>
    </div>

    <ConfirmDialog :open="submitConfirmOpen"
      :title="isRejected ? 'Ajukan ulang Sales Confirmation?' : 'Ajukan Sales Confirmation?'"
      description="Sales Confirmation akan diajukan untuk persetujuan. Catatan dan lampiran ikut terkirim."
      :confirm-text="isRejected ? 'Ya, ajukan ulang' : 'Ya, ajukan'" icon="Send" icon-class="bg-primary/10 text-primary"
      variant="primary" :loading="submitting" @close="submitConfirmOpen = false" @confirm="submitAdmin" />

    <ConfirmDialog :open="returnDialogOpen" variant="warning" title="Kembalikan ke Marketing?"
      description="PO Customer akan kembali ke Marketing untuk direvisi. Sales Confirmation yang sedang disiapkan (termasuk catatan Admin Finance) akan dihapus, dan Marketing perlu menjalankan Proses SC ulang setelah revisi."
      confirm-text="Ya, kembalikan" :loading="returning" @close="returnDialogOpen = false" @confirm="submitReturn" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'
import { FORBIDDEN_ACTION_MESSAGE, actionErrorMessage, redirectToErrorPage } from '@/utils/http-error-route'
import { quotationPermission, quotationPermissionsForAllBrands } from '@/utils/quotation-brand'
import type {
  ApiResponse,
  ArBucketKey,
  SalesConfirmationAdminAttachment,
  SalesConfirmationApprovalStep,
  SalesConfirmationDetail,
} from '@/types'

import ScActionResult from './components/ScActionResult.vue'
import ScAdminNoteCard from './components/ScAdminNoteCard.vue'
import ScComparisonCard from './components/ScComparisonCard.vue'
import ScCompliancePanel from './components/ScCompliancePanel.vue'
import ScCreditCard from './components/ScCreditCard.vue'
import ScDecisionCard from './components/ScDecisionCard.vue'
import ScHero from './components/ScHero.vue'
import ScStatusTimeline from './components/ScStatusTimeline.vue'
import ScSubmitCard from './components/ScSubmitCard.vue'
import { adminAttachmentName, normalizeAdminSummary } from './adminSubmission'
import { buildComparison, type Comparison } from './comparison'
import { resolveArBuckets } from './credit'
import { SALES_CONFIRMATION_STATUS } from './status'
import {
  buildStage,
  buildTimelineSteps,
  findActiveStep,
  findNextStep,
  findRejectedStep,
  type Stage,
  type TimelineStep,
} from './workflow'

interface ActionResult {
  tone: 'success' | 'danger'
  title: string
  message: string
}

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const ROLE_ADMIN_FINANCE = 9
const PAGE_TITLE = 'Detail Sales Confirmation'
const PAGE_DESCRIPTION = 'Bandingkan Quotation dengan PO Customer sebelum dikonfirmasi.'
const PO_CUSTOMER_VIEW_PERMISSIONS = [
  ...quotationPermissionsForAllBrands('viewAny'),
  ...quotationPermissionsForAllBrands('manage'),
  'sales-confirmation.manage',
]

const pocId = Number(route.params.id)

const detail = ref<SalesConfirmationDetail | null>(null)
const loading = ref(true)
const refreshing = ref(false)
const submitting = ref(false)
const returning = ref(false)
const decisionSubmitting = ref(false)
const formError = ref<string | null>(null)
const actionResult = ref<ActionResult | null>(null)

const submitConfirmOpen = ref(false)
const returnDialogOpen = ref(false)

const form = ref({ admin_summary: '' })
const newAttachments = ref<File[]>([])
const removedAttachments = ref<string[]>([])

const scStatus = computed<number | null>(() => (detail.value ? Number(detail.value.sc.disposisi) : null))

const isRejected = computed<boolean>(() => scStatus.value === SALES_CONFIRMATION_STATUS.Rejected)

const isAdminEditable = computed<boolean>(
  () =>
    auth.hasRole(ROLE_ADMIN_FINANCE) &&
    (scStatus.value === SALES_CONFIRMATION_STATUS.PendingAdmin || scStatus.value === SALES_CONFIRMATION_STATUS.Rejected),
)

const canDecide = computed<boolean>(() => detail.value?.approval_flow?.can_decide === true)

const comparison = computed<Comparison>(() => buildComparison(detail.value!))

const stage = computed<Stage>(() => buildStage(detail.value!))

const timeline = computed<TimelineStep[]>(() => buildTimelineSteps(detail.value!))

const arBuckets = computed<Record<ArBucketKey, number>>(() => resolveArBuckets(detail.value!))

const rejectedStep = computed<SalesConfirmationApprovalStep | null>(() =>
  detail.value && isRejected.value ? findRejectedStep(detail.value) : null,
)

const activeStep = computed<SalesConfirmationApprovalStep | null>(() => (detail.value ? findActiveStep(detail.value) : null))

const nextStep = computed<SalesConfirmationApprovalStep | null>(() => (detail.value ? findNextStep(detail.value) : null))

const adminAttachments = computed<SalesConfirmationAdminAttachment[]>(() => detail.value?.approval?.adm_attachments ?? [])

const admSummaryText = computed<string>(() => normalizeAdminSummary(detail.value?.approval?.adm_summary))

const showAdminNote = computed<boolean>(
  () => !isAdminEditable.value && (admSummaryText.value !== '' || adminAttachments.value.length > 0),
)

const existingAdminFiles = computed(() =>
  adminAttachments.value
    .filter(file => !removedAttachments.value.includes(file.path))
    .map(file => ({
      id: file.path,
      name: adminAttachmentName(file),
      url: file.url ?? undefined,
      size: file.size_bytes ?? undefined,
    })),
)

const quotationHref = computed<string | null>(() => {
  const quotation = detail.value?.quotation
  if (!quotation?.id || !quotation.brand) return null

  if (auth.can(quotationPermission(quotation.brand, 'viewOwn'))) {
    return router.resolve({ name: 'quotations.show', params: { id: quotation.id } }).href
  }
  if (auth.can(quotationPermission(quotation.brand, 'verify'))) {
    return router.resolve({ name: 'verification.quotations.show', params: { id: quotation.id } }).href
  }
  return null
})

const poCustomerHref = computed<string | null>(() =>
  PO_CUSTOMER_VIEW_PERMISSIONS.some(permission => auth.can(permission))
    ? router.resolve({ name: 'po-customers-detail', params: { id: detail.value?.poc.id_poc ?? pocId } }).href
    : null,
)

onMounted(load)

async function load(): Promise<void> {
  if (!Number.isInteger(pocId) || pocId <= 0) {
    detail.value = null
    loading.value = false
    redirectToErrorPage(router, 404)
    return
  }

  const isFirstLoad = detail.value === null
  if (isFirstLoad) loading.value = true
  else refreshing.value = true

  try {
    const { data } = await axios.get<ApiResponse<SalesConfirmationDetail>>(`/api/sales-confirmations/po/${pocId}`)
    detail.value = data.data
    newAttachments.value = []
    removedAttachments.value = []
    form.value.admin_summary = normalizeAdminSummary(data.data.approval?.adm_summary)
  } catch (e: any) {
    if (isFirstLoad) detail.value = null
    if (redirectToErrorPage(router, e.response?.status)) return
    notifyError('Gagal', actionErrorMessage(e, 'Gagal memuat Sales Confirmation.'))
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

function buildAdminPayload(): FormData {
  const payload = new FormData()
  payload.append('admin_summary', form.value.admin_summary)
  newAttachments.value.forEach(file => payload.append('attachments[]', file))
  removedAttachments.value.forEach(path => payload.append('removed_attachments[]', path))
  return payload
}

function validationMessage(e: any): string {
  const errors = e.response?.data?.errors ?? {}
  const messages = Object.values(errors).flat() as string[]
  return messages.length ? messages.join('\n') : (e.response?.data?.message ?? 'Data yang dikirim tidak valid.')
}

function markAdminAttachmentRemoved(file: { id?: string | number }): void {
  if (typeof file.id === 'string') removedAttachments.value.push(file.id)
}

async function submitAdmin(): Promise<void> {
  formError.value = null
  submitting.value = true
  try {
    const wasRejected = isRejected.value
    const { data } = await axios.post<ApiResponse<unknown>>(`/api/sales-confirmations/po/${pocId}`, buildAdminPayload(), {
      headers: { Accept: 'application/json' },
    })
    success('Berhasil', data.message || 'Sales Confirmation berhasil diajukan.')
    await load()
    actionResult.value = {
      tone: 'success',
      title: 'Pengajuan terkirim',
      message: wasRejected ? 'Sales Confirmation diajukan ulang.' : 'Sales Confirmation diajukan.',
    }
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422) {
      formError.value = validationMessage(e)
    } else if (status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Status Sales Confirmation sudah berubah.')
      await load()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal menyimpan Sales Confirmation.'))
    }
  } finally {
    submitting.value = false
    submitConfirmOpen.value = false
  }
}

async function submitDecision(payload: { decision: 'approve' | 'reject'; note: string }): Promise<void> {
  formError.value = null
  decisionSubmitting.value = true
  try {
    const { data } = await axios.post<ApiResponse<{ disposisi: number; disposisi_label: string }>>(
      `/api/sales-confirmations/po/${pocId}/decision`,
      payload,
    )
    success('Berhasil', data.message || 'Keputusan berhasil disimpan.')
    await load()
    actionResult.value = {
      tone: payload.decision === 'approve' ? 'success' : 'danger',
      title: 'Keputusan tersimpan',
      message: `Anda ${payload.decision === 'approve' ? 'menyetujui' : 'menolak'} · ${formatDateTime(new Date().toISOString())}`,
    }
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422) {
      formError.value = validationMessage(e)
    } else if (status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Status Sales Confirmation sudah berubah.')
      await load()
    } else if (status === 403) {
      notifyError('Gagal', FORBIDDEN_ACTION_MESSAGE)
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal menyimpan keputusan.'))
    }
  } finally {
    decisionSubmitting.value = false
  }
}

async function submitReturn(): Promise<void> {
  returning.value = true
  try {
    const { data } = await axios.post<ApiResponse<unknown>>(`/api/sales-confirmations/po/${pocId}/return`)
    returnDialogOpen.value = false
    success('Berhasil', data.message || 'PO Customer dikembalikan ke Marketing.')
    router.push({ name: 'sales-confirmations' })
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'PO Customer belum siap dikembalikan.')
      returnDialogOpen.value = false
      await load()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal mengembalikan PO Customer ke Marketing.'))
    }
  } finally {
    returning.value = false
  }
}

function backToIndex(): void {
  router.push({ name: 'sales-confirmations' })
}
</script>
