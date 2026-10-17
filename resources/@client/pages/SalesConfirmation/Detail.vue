<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import { Tab } from '@/components/Base/Headless'
import Lucide from '@/components/Base/Lucide'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'
import { FORBIDDEN_ACTION_MESSAGE, actionErrorMessage, redirectToErrorPage } from '@/utils/http-error-route'
import { quotationPermission } from '@/utils/quotation-brand'
import type {
  ApiResponse,
  ArBucketKey,
  SalesConfirmationAdminAttachment,
  SalesConfirmationApprovalFlow,
  SalesConfirmationApprovalStep,
  SalesConfirmationDetail,
} from '@/types'
import PoCustomerItemsTable from '@/pages/PoCustomer/components/PoCustomerItemsTable.vue'

import { SALES_CONFIRMATION_STATUS, salesConfirmationBadgeClass } from './status'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const ROLE_ADMIN_FINANCE = 9
const idPoc = Number(route.params.id)

const monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des']

const arBuckets = [
  { key: 'outstanding_current', label: 'Belum Jatuh Tempo' },
  { key: 'overdue_1_30', label: '1–30 Hari' },
  { key: 'overdue_31_60', label: '31–60 Hari' },
  { key: 'overdue_61_90', label: '61–90 Hari' },
  { key: 'overdue_90_plus', label: '> 90 Hari' },
] as const

const TAB_ITEMS = [{ label: 'Overview' }, { label: 'AR Balance' }]

const detail = ref<SalesConfirmationDetail | null>(null)
const loading = ref(true)
const submitting = ref(false)
const formError = ref<string | null>(null)

const decision = ref<'approve' | 'reject'>('approve')
const decisionNote = ref('')
const decisionSubmitting = ref(false)
const decisionConfirmOpen = ref(false)

const submitConfirmOpen = ref(false)

const returnDialogOpen = ref(false)
const returning = ref(false)

const creditLimitDisplay = ref<number>(0)
const bucketDisplay = ref<Record<ArBucketKey, number>>({
  outstanding_current: 0,
  overdue_1_30: 0,
  overdue_31_60: 0,
  overdue_61_90: 0,
  overdue_90_plus: 0,
})

const form = ref<{
  admin_summary: string
}>({
  admin_summary: '',
})

const newAttachments = ref<File[]>([])
const removedAttachments = ref<string[]>([])

const scDisposisi = computed<number | null>(() => {
  const value = detail.value?.sc?.disposisi
  return value == null ? null : Number(value)
})

const pageTitle = computed<string>(() => `Sales Confirmation · ${detail.value?.poc.nomor_poc ?? '-'}`)

const isRejected = computed<boolean>(() => scDisposisi.value === SALES_CONFIRMATION_STATUS.Rejected)

const isAdminEditable = computed<boolean>(
  () =>
    auth.hasRole(ROLE_ADMIN_FINANCE) &&
    (scDisposisi.value === null ||
      scDisposisi.value === SALES_CONFIRMATION_STATUS.PendingAdmin ||
      scDisposisi.value === SALES_CONFIRMATION_STATUS.Rejected),
)

const approvalFlow = computed<SalesConfirmationApprovalFlow | null>(() => detail.value?.approval_flow ?? null)

const activeStep = computed<SalesConfirmationApprovalStep | null>(
  () =>
    approvalFlow.value?.steps.find(
      (step) => step.step_order === approvalFlow.value?.current_step_order && step.status === 'pending',
    ) ?? null,
)

const rejectedStep = computed<SalesConfirmationApprovalStep | null>(
  () => approvalFlow.value?.steps.find((step) => step.status === 'rejected') ?? null,
)

const canDecide = computed<boolean>(() => approvalFlow.value?.can_decide === true)

const decisionSubmitDisabled = computed<boolean>(
  () => decisionSubmitting.value || (decision.value === 'reject' && decisionNote.value.trim() === ''),
)

const submitAdminLabel = computed<string>(() => (isRejected.value ? 'Ajukan Ulang' : 'Ajukan SC'))

const adminStep = computed<StepItem>(() => {
  const approval = detail.value?.approval
  if (!approval?.adm_result_date) {
    return { title: 'Admin Finance', status: 'pending', statusText: 'Belum diajukan' }
  }
  return {
    title: 'Admin Finance',
    status: 'completed',
    statusText: 'Diajukan',
    description: approval.adm_pic || undefined,
    timestamp: formatDateTime(approval.adm_result_date) || undefined,
  }
})

const approvalSteps = computed<StepItem[]>(() => {
  const flow = approvalFlow.value
  if (!flow) return [adminStep.value]

  const awaitingDecision =
    scDisposisi.value === SALES_CONFIRMATION_STATUS.PendingBm ||
    scDisposisi.value === SALES_CONFIRMATION_STATUS.PendingOm

  const flowSteps = flow.steps.map((step): StepItem => {
    const note = [step.actor_name, step.decision_note].filter(Boolean).join(' · ')
    const base = {
      title: step.step_name || `Step ${step.step_order}`,
      description: note || undefined,
      timestamp: step.acted_at ? formatDateTime(step.acted_at) : undefined,
    }

    if (step.status === 'approved') return { ...base, status: 'completed' }
    if (step.status === 'rejected') return { ...base, status: 'rejected' }
    if (rejectedStep.value) return { ...base, status: 'pending', statusText: 'Tidak dilanjutkan' }
    if (awaitingDecision && step.status === 'pending' && step.step_order === flow.current_step_order) return { ...base, status: 'active' }
    return { ...base, status: 'pending' }
  })

  return [adminStep.value, ...flowSteps]
})

const totalAr = computed<number>(() =>
  arBuckets.reduce((sum, bucket) => sum + Number(bucketDisplay.value[bucket.key] || 0), 0),
)

const remaining = computed<number>(() => creditLimitDisplay.value - totalAr.value)

const quotationApprovalSteps = computed<Array<{ label: string; text: string }>>(() => {
  const steps: Array<{
    step_order: number
    step_name: string | null
    status: string
    actor_name: string | null
    acted_at: string | null
  }> = detail.value?.quotation?.approvals ?? []
  return steps.map((step) => {
    const label = step.step_name || `Step ${step.step_order}`
    if (step.status === 'approved') {
      const date = formatDate(step.acted_at)
      return { label, text: step.actor_name ? `${step.actor_name} • ${date}` : date }
    }
    if (step.status === 'rejected') return { label, text: 'Ditolak' }
    return { label, text: 'Menunggu' }
  })
})

const quotationLink = computed<{ name: string } | null>(() => {
  const p = detail.value?.quotation
  if (!p?.id || !p.brand) return null
  if (auth.can(quotationPermission(p.brand, 'viewOwn'))) return { name: 'quotations.show' }
  if (auth.can(quotationPermission(p.brand, 'verify'))) return { name: 'verification.quotations.show' }
  return null
})

const quotationHref = computed<string | null>(() =>
  quotationLink.value
    ? router.resolve({ ...quotationLink.value, params: { id: detail.value?.quotation.id } }).href
    : null,
)

const pocHref = computed<string>(() =>
  router.resolve({ name: 'po-customers-detail', params: { id: detail.value?.poc?.id_poc ?? idPoc } }).href,
)

const gateLabel = computed<string>(() => {
  const type = detail.value?.credit?.gate?.type
  if (type === 'cbd_cod') return 'Lolos otomatis (CBD/COD)'
  if (type === 'unblock') return 'Lolos via Unblock'
  return 'Lolos otomatis'
})

const remainingAfterOrder = computed<number>(() => Number(detail.value?.credit?.sisa_limit_setelah_order ?? 0))

const priceValidity = computed<string>(() => {
  const p = detail.value?.quotation
  if (!p?.masa_berlaku || !p?.sampai_dengan) return '-'
  const from = new Date(p.masa_berlaku)
  const to = new Date(p.sampai_dengan)
  const part = (date: Date, withYear: boolean) =>
    `${String(date.getDate()).padStart(2, '0')} ${monthShort[date.getMonth()]}${withYear ? ` ${date.getFullYear()}` : ''}`
  const sameYear = from.getFullYear() === to.getFullYear()
  return `${part(from, !sameYear)} - ${part(to, true)}`
})

const pocPaymentLabel = computed<string>(() => {
  const poc = detail.value?.poc
  if (!poc?.tipe_bayar) return '-'
  return poc.tipe_bayar === 'CREDIT' && poc.termin_hari
    ? `${poc.tipe_bayar_label} — ${poc.termin_hari} Hari`
    : (poc.tipe_bayar_label ?? poc.tipe_bayar)
})

const attachmentName = computed<string>(
  () => detail.value?.poc?.lampiran_poc_ori || detail.value?.poc?.lampiran_poc || '',
)

const adminAttachments = computed<SalesConfirmationAdminAttachment[]>(() => detail.value?.approval?.adm_attachments ?? [])

const visibleAdminAttachments = computed<SalesConfirmationAdminAttachment[]>(() =>
  adminAttachments.value.filter((file) => !removedAttachments.value.includes(file.path)),
)

const existingAdminFiles = computed(() =>
  visibleAdminAttachments.value.map((file) => ({
    id: file.path,
    name: adminAttachmentName(file),
    url: file.url ?? undefined,
    size: file.size_bytes ?? undefined,
  })),
)

const admSummaryText = computed<string>(() => normalizeAdminSummary(detail.value?.approval?.adm_summary))

const attachmentKind = computed(() => resolveAttachmentKind(attachmentName.value))

onMounted(load)

function resolveAttachmentKind(name: string): { icon: 'FileText' | 'FileImage' | 'File'; iconClass: string; label: string } {
  const ext = name.split('.').pop()?.toLowerCase() ?? ''
  if (ext === 'pdf') return { icon: 'FileText', iconClass: 'bg-red-100 text-red-600', label: 'PDF file' }
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    return { icon: 'FileImage', iconClass: 'bg-blue-100 text-blue-600', label: 'Image file' }
  }
  return { icon: 'File', iconClass: 'bg-slate-100 text-slate-600', label: 'File' }
}

function normalizeAdminSummary(value: string | null | undefined): string {
  return String(value ?? '').replace(/<br\s*\/?>(\r?\n)?/gi, (_match, newline) => (newline ? newline : '\n'))
}

function adminAttachmentName(file: SalesConfirmationAdminAttachment): string {
  return file.original_name || file.path.split('/').pop() || file.path
}

function markAdminAttachmentRemoved(file: { id?: string | number }): void {
  if (typeof file.id === 'string') removedAttachments.value.push(file.id)
}

async function load(): Promise<void> {
  if (!Number.isInteger(idPoc) || idPoc <= 0) {
    detail.value = null
    loading.value = false
    redirectToErrorPage(router, 404)
    return
  }

  loading.value = true
  try {
    const { data } = await axios.get<ApiResponse<SalesConfirmationDetail>>(`/api/sales-confirmations/po/${idPoc}`)
    detail.value = data.data
    newAttachments.value = []
    removedAttachments.value = []
    prefillForm(data.data)
  } catch (e: any) {
    detail.value = null
    if (redirectToErrorPage(router, e.response?.status)) return
    notifyError('Gagal', actionErrorMessage(e, 'Gagal memuat Sales Confirmation.'))
  } finally {
    loading.value = false
  }
}

function prefillForm(data: SalesConfirmationDetail): void {
  const arnya = data.arnya ?? {}
  const header = data.sc_header
  const approval = data.approval

  creditLimitDisplay.value = Number(data.customer?.credit_limit ?? 0)

  bucketDisplay.value = {
    outstanding_current: Number(header?.not_yet ?? arnya.outstanding_current ?? 0),
    overdue_1_30: Number(header?.ov_under_30 ?? arnya.overdue_1_30 ?? 0),
    overdue_31_60: Number(header?.ov_under_60 ?? arnya.overdue_31_60 ?? 0),
    overdue_61_90: Number(header?.ov_under_90 ?? arnya.overdue_61_90 ?? 0),
    overdue_90_plus: Number(header?.ov_up_90 ?? arnya.overdue_90_plus ?? 0),
  }

  if (approval) {
    form.value.admin_summary = normalizeAdminSummary(approval.adm_summary)
  }
}

function buildAdminPayload(): FormData {
  const payload = new FormData()
  payload.append('admin_summary', form.value.admin_summary)
  newAttachments.value.forEach((file) => payload.append('attachments[]', file))
  removedAttachments.value.forEach((path) => payload.append('removed_attachments[]', path))
  return payload
}

async function submitAdmin(): Promise<void> {
  formError.value = null
  submitting.value = true
  try {
    const { data } = await axios.post<ApiResponse<unknown>>(`/api/sales-confirmations/po/${idPoc}`, buildAdminPayload(), {
      headers: { Accept: 'application/json' },
    })
    success('Berhasil', data.message || 'Sales Confirmation berhasil diajukan.')
    await load()
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422) {
      const errors = e.response?.data?.errors ?? {}
      const messages = Object.values(errors).flat() as string[]
      formError.value = messages.length
        ? messages.join('\n')
        : e.response?.data?.message ?? 'Data yang dikirim tidak valid.'
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

function openReturnDialog(): void {
  returnDialogOpen.value = true
}

async function submitReturn(): Promise<void> {
  returning.value = true
  try {
    const { data } = await axios.post<ApiResponse<unknown>>(`/api/sales-confirmations/po/${idPoc}/return`)
    returnDialogOpen.value = false
    success('Berhasil', data.message || 'PO dikembalikan ke Marketing.')
    router.push({ name: 'sales-confirmations' })
  } catch (e: any) {
    const status = e.response?.status
    if (status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'PO Customer belum siap dikembalikan.')
      returnDialogOpen.value = false
      load()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal mengembalikan PO ke Marketing.'))
    }
  } finally {
    returning.value = false
  }
}

async function submitDecision(): Promise<void> {
  if (decision.value === 'reject' && decisionNote.value.trim() === '') {
    decisionConfirmOpen.value = false
    notifyError('Validasi', 'Catatan wajib diisi untuk keputusan Tolak.')
    return
  }

  formError.value = null
  decisionSubmitting.value = true
  try {
    const { data } = await axios.post<ApiResponse<{ disposisi: number; disposisi_label: string }>>(
      `/api/sales-confirmations/po/${idPoc}/decision`,
      { decision: decision.value, note: decisionNote.value },
    )
    success('Berhasil', data.message || 'Keputusan berhasil disimpan.')
    decisionNote.value = ''
    decision.value = 'approve'
    await load()
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422) {
      const errors = e.response?.data?.errors ?? {}
      const messages = Object.values(errors).flat() as string[]
      formError.value = messages.length
        ? messages.join('\n')
        : e.response?.data?.message ?? 'Data yang dikirim tidak valid.'
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
    decisionConfirmOpen.value = false
  }
}

function backToIndex(): void {
  router.push({ name: 'sales-confirmations' })
}
</script>

<template>
  <div v-if="loading" class="page-content-wrapper">
    <div class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
      <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
      <span class="text-body">Memuat Sales Confirmation...</span>
    </div>
  </div>

  <div v-else-if="!detail" class="page-content-wrapper">
    <div class="flex flex-col justify-center items-center gap-2 min-h-[320px] text-center intro-y">
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
  </div>

  <FormPage v-else :title="pageTitle" size="full" layout="sidebar" surface="plain" :show-footer="false"
    :loading="submitting || returning || decisionSubmitting" :error="formError">
    <template #action>
      <Button variant="outline-secondary" @click="backToIndex">
        <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
        Kembali
      </Button>
    </template>

    <template #header>
      <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-form-label"
          :class="salesConfirmationBadgeClass(detail.sc?.disposisi)">
          {{ detail.sc?.disposisi_label || '-' }}
        </span>
        <span v-if="formatDateTime(detail.sc?.disposisi_time)" class="text-body text-slate-500">
          {{ formatDateTime(detail.sc?.disposisi_time) }} WIB
        </span>
        <span class="text-body text-slate-500">
          {{ detail.customer?.company_name || '-' }} · Penawaran {{ detail.quotation?.nomor_penawaran || '-' }}
        </span>
      </div>
      <p v-if="isRejected && rejectedStep"
        class="bg-rose-50 mt-3 px-4 py-3 border border-rose-200 rounded-lg !text-rose-700 text-body">
        Ditolak {{ rejectedStep.step_name || '-' }} · {{ rejectedStep.actor_name || '-' }} ·
        {{ formatDateTime(rejectedStep.acted_at) || '-' }}
        <span v-if="rejectedStep.decision_note" class="block">{{ rejectedStep.decision_note }}</span>
      </p>
    </template>

    <Tab.Group>
      <Tab.List variant="link-tabs" class="gap-1 border-slate-200 border-b">
        <Tab v-for="t in TAB_ITEMS" :key="t.label" :full-width="false" v-slot="{ selected }">
          <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-form-label" :class="selected
            ? 'text-primary border-b-primary'
            : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
            <span>{{ t.label }}</span>
          </Tab.Button>
        </Tab>
      </Tab.List>

      <Tab.Panels class="mt-4">
        <Tab.Panel>
          <div class="space-y-6">
            <CardSection title="PO & Penawaran" icon="FileText" icon-class="bg-emerald-100 text-emerald-600"
              class="border border-slate-200">
              <template v-if="detail.quotation?.type_pengiriman" #action>
                <span class="bg-emerald-100 px-3 py-1 rounded-full !text-emerald-700 text-form-label">
                  {{ detail.quotation.type_pengiriman }}
                </span>
              </template>

              <div class="bg-slate-50 p-4 border border-slate-200 rounded-lg">
                <span class="text-form-label">Nama Customer</span>
                <div class="text-section-title">{{ detail.customer?.company_name || '-' }}</div>
                <div class="mt-1 text-caption text-slate-500">
                  Kode: {{ detail.customer?.customer_code || '-' }}
                </div>
              </div>

              <div class="gap-6 grid grid-cols-1 md:grid-cols-2 mt-4">
                <div>
                  <div class="mb-2 text-overline">Purchase Order</div>
                  <dl class="space-y-1">
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Nomor PO</span>
                      <span class="text-body-strong text-right">{{ detail.poc.nomor_poc || '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Tanggal PO</span>
                      <span class="text-body-strong text-right">{{ formatDate(detail.poc.tanggal_poc) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Supply Date</span>
                      <span class="text-body-strong text-right">{{ formatDate(detail.poc.supply_date) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Tipe Pembayaran</span>
                      <span class="text-body-strong text-right">{{ pocPaymentLabel }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Total Volume</span>
                      <span class="text-body-strong text-right">{{ formatNumber(detail.poc.totals.volume) }} m³</span>
                    </div>
                    <div class="flex justify-between gap-4 bg-slate-100 px-2 py-1.5 rounded-lg">
                      <span class="text-form-label">Grand Total (incl. PPN)</span>
                      <span class="text-section-title text-right">{{ formatCurrency(detail.poc.totals.grand_total)
                        }}</span>
                    </div>
                  </dl>
                </div>

                <div>
                  <div class="mb-2 text-overline">Penawaran</div>
                  <dl class="space-y-1">
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">No. Penawaran</span>
                      <span class="text-body-strong text-right">{{ detail.quotation?.nomor_penawaran || '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Marketing</span>
                      <span class="text-body-strong text-right">{{ detail.quotation?.marketing_name || '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 px-2 py-1.5">
                      <span class="text-form-label">Masa Berlaku Harga</span>
                      <span class="text-body-strong text-right">{{ priceValidity }}</span>
                    </div>
                  </dl>

                  <div class="space-y-1 mt-2 px-2">
                    <span class="text-form-label">Lokasi Pengiriman</span>
                    <div class="p-3 border border-slate-200 rounded-lg text-body">
                      {{ detail.quotation?.lokasi_pengiriman || '-' }}
                    </div>
                  </div>

                  <div v-if="detail.quotation?.id"
                    class="space-y-3 bg-slate-50 mx-2 mt-3 p-4 border border-slate-200 rounded-lg">
                    <span class="text-form-label">Persetujuan Penawaran</span>
                    <div v-if="quotationApprovalSteps.length" class="gap-3 grid grid-cols-2">
                      <div v-for="step in quotationApprovalSteps" :key="step.label">
                        <div class="text-caption text-slate-500">{{ step.label }}</div>
                        <div class="text-body-strong">{{ step.text }}</div>
                      </div>
                    </div>
                    <div v-else class="text-body">-</div>
                  </div>
                </div>
              </div>

              <div class="gap-6 grid grid-cols-1 md:grid-cols-2 mt-4 pt-4 border-slate-100 border-t">
                <div class="space-y-2">
                  <div class="flex justify-between gap-4 px-2">
                    <span class="text-form-label">Dibuat Oleh</span>
                    <span class="text-body-strong text-right">{{ detail.poc.created_by || '-' }}</span>
                  </div>
                  <div class="flex justify-between gap-4 px-2">
                    <span class="text-form-label">Tanggal Dibuat</span>
                    <span class="text-body-strong text-right">{{ formatDateTime(detail.poc.created_time) || '-'
                      }}</span>
                  </div>
                </div>

                <div class="space-y-2">
                  <span class="text-form-label">Lampiran PO Customer</span>
                  <div v-if="detail.poc.lampiran_poc"
                    class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg">
                    <div class="flex justify-center items-center rounded-lg w-10 h-10 shrink-0"
                      :class="attachmentKind.iconClass">
                      <Lucide :icon="attachmentKind.icon" class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-body-strong truncate" :title="attachmentName">{{ attachmentName }}</div>
                      <div class="text-caption text-slate-500">{{ attachmentKind.label }}</div>
                    </div>
                    <Button as="a" variant="outline-secondary" class="!shadow-none !p-0 !w-8 !h-8" title="Pratinjau"
                      :href="`/storage/${detail.poc.lampiran_poc}`" target="_blank">
                      <Lucide icon="Eye" class="w-4 h-4" />
                    </Button>
                    <Button as="a" variant="primary" class="!shadow-none !p-0 !w-8 !h-8" title="Unduh"
                      :href="`/storage/${detail.poc.lampiran_poc}`" :download="detail.poc.lampiran_poc_ori">
                      <Lucide icon="Download" class="w-4 h-4" />
                    </Button>
                  </div>
                  <div v-else
                    class="p-4 border border-slate-300 border-dashed rounded-lg text-body text-slate-500 text-center">
                    Tidak ada lampiran
                  </div>
                </div>
              </div>

              <div class="flex flex-wrap justify-end gap-2 mt-4">
                <Button v-if="quotationHref" as="a" variant="outline-secondary" :href="quotationHref" target="_blank">
                  <Lucide icon="Eye" class="mr-2 w-4 h-4" />
                  Buka Penawaran
                </Button>
                <Button as="a" variant="dark" :href="pocHref" target="_blank">
                  <Lucide icon="ExternalLink" class="mr-2 w-4 h-4" />
                  Buka PO Full Detail
                </Button>
              </div>
            </CardSection>

            <CardSection title="Item PO" description="Volume dan harga per item yang diambil customer" icon="Layers"
              icon-class="bg-indigo-100 text-indigo-600" class="border border-slate-200">
              <PoCustomerItemsTable :items="detail.poc.items" :totals="detail.poc.totals" />
            </CardSection>
          </div>
        </Tab.Panel>

        <Tab.Panel>
          <div class="items-start gap-6 grid grid-cols-1 lg:grid-cols-2">
            <CardSection title="Posisi Kredit" icon="Wallet" icon-class="bg-amber-100 text-amber-600"
              class="border border-slate-200">
              <div class="space-y-2 bg-amber-50 p-4 border border-amber-200 rounded-lg">
                <div class="flex justify-between gap-4">
                  <span class="text-form-label">Credit Limit (Hasil Verifikasi)</span>
                  <span class="text-body-strong text-right">{{ formatCurrency(creditLimitDisplay) }}</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="text-form-label">TOP (Hasil Verifikasi)</span>
                  <span class="text-body-strong text-right">
                    {{ detail.credit?.approved_top != null ? `${detail.credit.approved_top} Hari` : '-' }}
                  </span>
                </div>
              </div>

              <div class="space-y-1 mt-3">
                <div class="flex justify-between gap-4 px-2 py-1.5">
                  <span class="text-form-label">Total AR</span>
                  <span class="text-body-strong">{{ formatCurrency(totalAr) }}</span>
                </div>
                <div class="flex justify-between gap-4 px-2 py-1.5">
                  <span class="text-form-label">Sisa Limit</span>
                  <span class="text-body-strong">{{ formatCurrency(remaining) }}</span>
                </div>
                <div class="flex justify-between gap-4 px-2 py-1.5">
                  <span class="text-form-label">Nilai Order Ini (Incl. PPN)</span>
                  <span class="text-body-strong">{{ formatCurrency(detail.credit?.nilai_order_ppn) }}</span>
                </div>
              </div>

              <div class="flex justify-between items-center gap-4 mt-3 p-3 border rounded-lg"
                :class="remainingAfterOrder < 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50'">
                <span class="text-form-label">Sisa Limit Setelah Order Ini</span>
                <span class="text-body-strong" :class="remainingAfterOrder < 0 ? '!text-red-600' : '!text-emerald-700'">
                  {{ formatCurrency(remainingAfterOrder) }}
                </span>
              </div>

              <div class="flex items-start gap-3 bg-emerald-50 mt-3 p-3 border border-emerald-200 rounded-lg">
                <Lucide icon="CheckCircle" class="mt-0.5 w-6 h-6 text-emerald-600 shrink-0" />
                <div class="min-w-0">
                  <span class="text-form-label">Status Credit Gate</span>
                  <div class="text-body-strong">{{ gateLabel }}</div>
                  <template v-if="detail.credit?.gate?.type === 'unblock' && detail.credit.gate.unblock">
                    <div class="text-caption text-slate-500">
                      {{ detail.credit.gate.unblock.requested_by_name || '-' }} •
                      {{ formatDateTime(detail.credit.gate.unblock.requested_at) || '-' }}
                    </div>
                    <div v-if="detail.credit.gate.unblock.reason" class="text-body">
                      {{ detail.credit.gate.unblock.reason }}
                    </div>
                  </template>
                </div>
              </div>
            </CardSection>

            <CardSection title="Aging AR" icon="CalendarClock" icon-class="bg-amber-100 text-amber-600"
              class="border border-slate-200">

              <dl class="space-y-1">
                <div v-for="bucket in arBuckets" :key="bucket.key" class="flex justify-between gap-4 px-2 py-1.5">
                  <span class="text-form-label">{{ bucket.label }}</span>
                  <span class="text-body-strong text-right">{{ formatCurrency(bucketDisplay[bucket.key]) }}</span>
                </div>
              </dl>

              <hr class="my-3" />

              <div class="flex justify-between gap-4 px-2 py-1.5">
                <span class="text-form-label">Total AR</span>
                <span class="text-body-strong">{{ formatCurrency(totalAr) }}</span>
              </div>
            </CardSection>
          </div>
        </Tab.Panel>
      </Tab.Panels>
    </Tab.Group>

    <template #sidebar>
      <CardSection title="Status Approval" icon="ShieldCheck" icon-class="bg-blue-100 text-blue-600">
        <div class="px-2">
          <Stepper :steps="approvalSteps" direction="vertical" />
        </div>
      </CardSection>

      <CardSection v-if="!isAdminEditable && (detail.approval?.adm_summary || adminAttachments.length)"
        title="Catatan & Lampiran Admin" icon="MessageSquare" icon-class="bg-emerald-100 text-emerald-600">
        <div class="space-y-3">
          <div v-if="detail.approval?.adm_summary" class="space-y-1">
            <span class="text-form-label">Catatan Admin Finance</span>
            <div
              class="bg-slate-50 mt-1 p-3 border border-slate-200 rounded-lg text-body break-words whitespace-pre-line">
              {{
                admSummaryText }}</div>
          </div>

          <div v-if="adminAttachments.length" class="space-y-2">
            <span class="text-form-label">Lampiran Admin Finance</span>
            <div v-for="file in adminAttachments" :key="file.path"
              class="flex items-center gap-2 p-2 border border-slate-200 rounded-lg">
              <div class="flex justify-center items-center rounded-lg w-8 h-8 shrink-0"
                :class="resolveAttachmentKind(adminAttachmentName(file)).iconClass">
                <Lucide :icon="resolveAttachmentKind(adminAttachmentName(file)).icon" class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-body-strong truncate" :title="adminAttachmentName(file)">{{ adminAttachmentName(file)
                  }}</div>
                <div class="text-caption text-slate-500">{{ resolveAttachmentKind(adminAttachmentName(file)).label }}
                </div>
              </div>
              <template v-if="file.url">
                <Button as="a" variant="outline-secondary" class="!shadow-none !p-0 !w-8 !h-8 shrink-0"
                  title="Pratinjau" :href="file.url" target="_blank">
                  <Lucide icon="Eye" class="w-4 h-4" />
                </Button>
                <Button as="a" variant="primary" class="!shadow-none !p-0 !w-8 !h-8 shrink-0" title="Unduh"
                  :href="file.url" :download="adminAttachmentName(file)">
                  <Lucide icon="Download" class="w-4 h-4" />
                </Button>
              </template>
            </div>
          </div>
        </div>
      </CardSection>

      <CardSection v-if="isAdminEditable" title="Pengajuan SC" icon="MessageSquare"
        icon-class="bg-emerald-100 text-emerald-600">
        <div class="space-y-4">
          <div>
            <FormLabel htmlFor="admin-summary">Catatan Admin Finance</FormLabel>
            <FormTextarea id="admin-summary" v-model="form.admin_summary" :rows="4"
              placeholder="Catatan / analisa Admin Finance (opsional)" :disabled="submitting" />
          </div>

          <FileUploadField v-model="newAttachments" label="Lampiran" hint="Opsional. PDF/JPG/PNG maks 2MB per file."
            accept=".pdf,.jpg,.jpeg,.png" multiple :max-size-mb="2" :existing-files="existingAdminFiles"
            :disabled="submitting" @remove-existing="markAdminAttachmentRemoved" />

          <div class="flex flex-col gap-2">
            <Button type="button" variant="primary" class="inline-flex justify-center items-center gap-2 w-full"
              :disabled="submitting || returning" @click="submitConfirmOpen = true">
              <Lucide v-if="submitting" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="ShieldCheck" class="w-4 h-4" />
              {{ submitAdminLabel }}
            </Button>
            <Button type="button" variant="outline-danger" class="inline-flex justify-center items-center gap-2 w-full"
              :disabled="submitting || returning" @click="openReturnDialog">
              <Lucide icon="Undo2" class="w-4 h-4" />
              Kembalikan ke Marketing
            </Button>
          </div>
        </div>
      </CardSection>

      <CardSection v-else-if="canDecide" :title="`Keputusan ${activeStep?.step_name ?? 'Approval'}`"
        description="Putuskan setuju atau tolak Sales Confirmation ini." icon="Gavel"
        icon-class="bg-blue-100 text-blue-600">
        <div class="space-y-3">
          <div class="space-y-1 bg-slate-50 p-3 border border-slate-200 rounded-lg">
            <div class="flex justify-between gap-4">
              <span class="text-form-label">Nilai Order (Incl. PPN)</span>
              <span class="text-body-strong text-right">{{ formatCurrency(detail.credit?.nilai_order_ppn) }}</span>
            </div>
            <div class="flex justify-between gap-4">
              <span class="text-form-label">Sisa Limit Setelah Order</span>
              <span class="text-body-strong text-right"
                :class="remainingAfterOrder < 0 ? '!text-red-600' : '!text-emerald-700'">
                {{ formatCurrency(remainingAfterOrder) }}
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <Button type="button" class="flex-1 justify-center items-center gap-2"
              :variant="decision === 'approve' ? 'primary' : 'outline-secondary'" :disabled="decisionSubmitting"
              @click="decision = 'approve'">
              Setuju
            </Button>
            <Button type="button" class="flex-1 justify-center items-center gap-2"
              :variant="decision === 'reject' ? 'danger' : 'outline-secondary'" :disabled="decisionSubmitting"
              @click="decision = 'reject'">
              Tolak
            </Button>
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <FormLabel htmlFor="decision-note">Catatan Keputusan</FormLabel>
              <span class="text-caption" :class="decision === 'reject' ? 'text-amber-600' : 'text-slate-400'">
                {{ decision === 'reject' ? 'Wajib untuk Tolak' : 'Opsional' }}
              </span>
            </div>
            <FormTextarea id="decision-note" v-model="decisionNote" :rows="4" placeholder="Catatan keputusan"
              :disabled="decisionSubmitting" />
          </div>

          <Button type="button" class="inline-flex justify-center items-center gap-2 w-full"
            :variant="decision === 'approve' ? 'primary' : 'danger'" :disabled="decisionSubmitDisabled"
            @click="decisionConfirmOpen = true">
            <Lucide icon="Send" class="w-4 h-4" />
            Ajukan Keputusan
          </Button>
        </div>
      </CardSection>
    </template>

    <ConfirmDialog :open="submitConfirmOpen"
      :title="isRejected ? 'Ajukan ulang Sales Confirmation?' : 'Ajukan Sales Confirmation?'"
      description="Sales Confirmation akan diajukan untuk persetujuan BM lalu OM."
      :confirm-text="isRejected ? 'Ya, ajukan ulang' : 'Ya, ajukan'" icon="Send" icon-class="bg-primary/10 text-primary"
      variant="primary" :loading="submitting" @close="submitConfirmOpen = false" @confirm="submitAdmin" />

    <ConfirmDialog :open="decisionConfirmOpen"
      :title="decision === 'approve' ? 'Setujui Sales Confirmation ini?' : 'Tolak Sales Confirmation ini?'"
      :description="decision === 'approve'
        ? `Sales Confirmation akan disetujui sebagai ${activeStep?.step_name ?? 'approver'}.`
        : 'Sales Confirmation akan ditolak dan dikembalikan ke Admin Finance beserta catatan Anda.'"
      :confirm-text="decision === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
      :icon="decision === 'approve' ? 'Check' : 'X'"
      :icon-class="decision === 'approve' ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
      :variant="decision === 'approve' ? 'primary' : 'danger'" :loading="decisionSubmitting"
      @close="decisionConfirmOpen = false" @confirm="submitDecision" />

    <ConfirmDialog :open="returnDialogOpen" variant="warning" title="Kembalikan ke Marketing?"
      description="PO akan kembali ke Marketing untuk direvisi. Sales Confirmation yang sedang disiapkan (termasuk catatan Admin Finance) akan dihapus, dan Marketing perlu menjalankan Proses SC ulang setelah revisi."
      confirm-text="Ya, kembalikan" :loading="returning" @close="returnDialogOpen = false" @confirm="submitReturn" />
  </FormPage>
</template>
