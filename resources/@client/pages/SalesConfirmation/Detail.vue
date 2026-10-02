<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { salesConfirmationBadgeClass } from './status'

type AdminAttachment = {
  path: string
  original_name: string | null
  url: string | null
  size_bytes: number | null
}

type ArBucketKey =
  | 'outstanding_current'
  | 'overdue_1_30'
  | 'overdue_31_60'
  | 'overdue_61_90'
  | 'overdue_90_plus'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const ROLE_ADMIN_FINANCE = 9
const ROLE_BM = 8
const idPoc = Number(route.params.id)

const arBuckets = [
  { key: 'outstanding_current', label: 'Belum Jatuh Tempo' },
  { key: 'overdue_1_30', label: '1–30 Hari' },
  { key: 'overdue_31_60', label: '31–60 Hari' },
  { key: 'overdue_61_90', label: '61–90 Hari' },
  { key: 'overdue_90_plus', label: '> 90 Hari' },
] as const

const detail = ref<any>(null)
const loading = ref(true)
const submitting = ref(false)
const formError = ref<string | null>(null)

const bmDecision = ref<'approve' | 'reject'>('approve')
const bmNote = ref('')
const bmSubmitting = ref(false)
const bmConfirmOpen = ref(false)

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

const isAdminEditable = computed<boolean>(
  () =>
    auth.hasRole(ROLE_ADMIN_FINANCE) &&
    (scDisposisi.value === null || scDisposisi.value === 1),
)

const isBmDecidable = computed<boolean>(
  () =>
    auth.hasRole(ROLE_BM) &&
    scDisposisi.value === 2 &&
    detail.value?.bm_approval?.current_step_order === 1,
)

const bmSubmitDisabled = computed<boolean>(
  () => bmSubmitting.value || (bmDecision.value === 'reject' && bmNote.value.trim() === ''),
)

const bmRejectionStep = computed<any>(() => {
  if (scDisposisi.value !== 1) return null
  const step = detail.value?.bm_approval?.steps?.[0]
  return step?.decision_note ? step : null
})

const totalAr = computed<number>(() =>
  arBuckets.reduce((sum, bucket) => sum + Number(bucketDisplay.value[bucket.key] || 0), 0),
)

const remaining = computed<number>(() => creditLimitDisplay.value - totalAr.value)

const penawaranItems = computed<any[]>(() => detail.value?.penawaran?.items ?? [])

const approvalSteps = computed<Array<{ label: string; text: string }>>(() => {
  const steps: any[] = detail.value?.penawaran?.approvals ?? []
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

const penawaranLink = computed<{ name: string } | null>(() => {
  const p = detail.value?.penawaran
  if (!p?.id_penawaran) return null
  if (p.brand === 'tds') {
    if (auth.can('penawaran.viewOwn')) return { name: 'penawarans-detail' }
    if (auth.can('penawaran.verify')) return { name: 'penawarans-verifikasi-bm-detail' }
  } else if (p.brand === 'proenergi') {
    if (auth.can('penawaran.proenergi.viewOwn')) return { name: 'penawarans-detail-proenergi' }
    if (auth.can('penawaran.proenergi.verify-bm')) {
      return { name: 'penawarans-verifikasi-bm-detail-proenergi' }
    }
  }
  return null
})

const penawaranHref = computed<string | null>(() =>
  penawaranLink.value
    ? router.resolve({ ...penawaranLink.value, params: { id: detail.value.penawaran.id_penawaran } }).href
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

const headerTheme = computed<{ banner: string; icon: string }>(() => {
  switch (scDisposisi.value) {
    case 1:
      return { banner: 'border-amber-200 bg-amber-50', icon: 'bg-amber-500 text-white' }
    case 4:
      return { banner: 'border-emerald-200 bg-emerald-50', icon: 'bg-emerald-600 text-white' }
    default:
      return { banner: 'border-blue-200 bg-blue-50', icon: 'bg-blue-600 text-white' }
  }
})

const monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des']

const priceValidity = computed<string>(() => {
  const p = detail.value?.penawaran
  if (!p?.masa_berlaku || !p?.sampai_dengan) return '-'
  const from = new Date(p.masa_berlaku)
  const to = new Date(p.sampai_dengan)
  const part = (date: Date, withYear: boolean) =>
    `${String(date.getDate()).padStart(2, '0')} ${monthShort[date.getMonth()]}${withYear ? ` ${date.getFullYear()}` : ''}`
  const sameYear = from.getFullYear() === to.getFullYear()
  return `${part(from, !sameYear)} - ${part(to, true)}`
})

const paymentTypeLabel = computed<string>(() => {
  const p = detail.value?.penawaran
  if (!p?.tipe_pembayaran) return '-'
  return Number(p.repayment_hari) > 0 && !/\d/.test(p.tipe_pembayaran)
    ? `${p.tipe_pembayaran} — ${p.repayment_hari} hari`
    : p.tipe_pembayaran
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

const adminAttachments = computed<AdminAttachment[]>(() => detail.value?.approval?.adm_attachments ?? [])

const visibleAdminAttachments = computed<AdminAttachment[]>(() =>
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

function resolveAttachmentKind(name: string): { icon: 'FileText' | 'FileImage' | 'File'; iconClass: string; label: string } {
  const ext = name.split('.').pop()?.toLowerCase() ?? ''
  if (ext === 'pdf') return { icon: 'FileText', iconClass: 'bg-red-100 text-red-600', label: 'PDF file' }
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    return { icon: 'FileImage', iconClass: 'bg-blue-100 text-blue-600', label: 'Image file' }
  }
  return { icon: 'File', iconClass: 'bg-slate-100 text-slate-600', label: 'File' }
}

const attachmentKind = computed(() => resolveAttachmentKind(attachmentName.value))

function adminAttachmentName(file: AdminAttachment): string {
  return file.original_name || file.path.split('/').pop() || file.path
}

function markAdminAttachmentRemoved(file: { id?: string | number }): void {
  if (typeof file.id === 'string') removedAttachments.value.push(file.id)
}

onMounted(load)

async function load(): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    detail.value = data
    newAttachments.value = []
    removedAttachments.value = []
    prefillForm(data)
  } catch (e: any) {
    detail.value = null
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat Sales Confirmation.')
  } finally {
    loading.value = false
  }
}

function prefillForm(data: any): void {
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
    form.value.admin_summary = String(approval.adm_summary ?? '').replace(/<br\s*\/?>/gi, '\n')
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
    await axios.post(`/api/sales-confirmations/po/${idPoc}`, buildAdminPayload(), {
      headers: { Accept: 'application/json' },
    })
    success('Berhasil', 'Sales Confirmation berhasil disimpan.')
    backToIndex()
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
      load()
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan Sales Confirmation.')
    }
  } finally {
    submitting.value = false
  }
}

function openReturnDialog(): void {
  returnDialogOpen.value = true
}

async function submitReturn(): Promise<void> {
  returning.value = true
  try {
    await axios.post(`/api/sales-confirmations/po/${idPoc}/return`)
    returnDialogOpen.value = false
    success('Berhasil', 'PO dikembalikan ke Marketing.')
    router.push({ name: 'sales-confirmations' })
  } catch (e: any) {
    const status = e.response?.status
    if (status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'PO Customer belum siap dikembalikan.')
      returnDialogOpen.value = false
      load()
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengembalikan PO ke Marketing.')
    }
  } finally {
    returning.value = false
  }
}

async function submitBmDecision(): Promise<void> {
  if (bmDecision.value === 'reject' && bmNote.value.trim() === '') {
    bmConfirmOpen.value = false
    notifyError('Validasi', 'Catatan wajib diisi untuk keputusan Tolak.')
    return
  }

  formError.value = null
  bmSubmitting.value = true
  try {
    await axios.post(`/api/sales-confirmations/po/${idPoc}/bm`, {
      decision: bmDecision.value,
      note: bmNote.value,
    })
    success('Berhasil', 'Keputusan Branch Manager berhasil disimpan.')
    load()
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
      load()
    } else if (status === 403) {
      notifyError('Gagal', e.response?.data?.message ?? 'Anda tidak berwenang melakukan aksi ini.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan keputusan Branch Manager.')
    }
  } finally {
    bmSubmitting.value = false
    bmConfirmOpen.value = false
  }
}

function backToIndex(): void {
  router.push({ name: 'sales-confirmations' })
}
</script>

<template>
  <div v-if="loading" class="page-content-wrapper">
    <div class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
      <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
      <span class="text-body">Memuat Sales Confirmation...</span>
    </div>
  </div>

  <div v-else-if="!detail" class="page-content-wrapper">
    <div class="intro-y flex min-h-[320px] flex-col items-center justify-center gap-2 text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
        <Lucide icon="AlertTriangle" class="h-7 w-7 text-rose-500" />
      </div>
      <h3 class="text-section-title">Sales Confirmation tidak ditemukan</h3>
      <p class="text-body">Silakan kembali ke halaman sebelumnya.</p>
      <Button variant="outline-secondary" @click="backToIndex">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali ke daftar
      </Button>
    </div>
  </div>

  <div v-else class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-6">
      <div class="flex flex-col gap-4 rounded-lg border p-5 sm:flex-row sm:items-center sm:justify-between"
        :class="headerTheme.banner">
        <div class="flex items-center gap-4">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg" :class="headerTheme.icon">
            <Lucide icon="FileText" class="h-6 w-6" />
          </div>
          <div>
            <h1 class="text-section-title">{{ detail.sc?.disposisi_label || 'Detail Sales Confirmation' }}</h1>
            <p class="text-body">Ringkasan Sales Confirmation dan keputusan verifikasi kredit customer.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <span v-if="formatDateTime(detail.poc?.created_time)"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-form-label">
            <Lucide icon="Calendar" class="h-4 w-4" />
            Dibuat: {{ formatDateTime(detail.poc?.created_time) }}
          </span>
          <Button variant="outline-secondary" class="bg-white" @click="backToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </div>
      </div>

      <div v-if="formError"
        class="whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-body !text-rose-700">
        {{ formError }}
      </div>

      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <CardSection title="Customer & Penawaran" icon="Users" icon-class="bg-blue-100 text-blue-600"
          class="border border-slate-200">
          <template v-if="detail.penawaran?.type_pengiriman" #action>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-form-label !text-emerald-700">
              {{ detail.penawaran.type_pengiriman }}
            </span>
          </template>

          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <span class="text-form-label">Nama Customer</span>
            <div class="text-section-title">{{ detail.customer?.company_name || '-' }}</div>
            <div class="mt-1 text-caption text-slate-500">
              Kode: {{ detail.customer?.customer_code || '-' }} · No. Penawaran:
              {{ detail.penawaran?.nomor_penawaran || '-' }}
            </div>
          </div>

          <template v-if="detail.penawaran?.id_penawaran">
            <div class="mt-3 grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-slate-200 p-3">
                <span class="text-form-label">Marketing</span>
                <div class="text-body-strong">{{ detail.penawaran.marketing_name || '-' }}</div>
              </div>
              <div class="rounded-lg border border-slate-200 p-3">
                <span class="text-form-label">Masa Berlaku Harga</span>
                <div class="text-body-strong">{{ priceValidity }}</div>
              </div>
            </div>

            <dl class="mt-3 space-y-1">
              <div class="flex justify-between gap-4 px-2 py-1.5">
                <span class="text-form-label">Harga Dasar</span>
                <span class="text-body-strong text-right">{{ formatCurrency(detail.penawaran.harga_dasar) }}</span>
              </div>
              <div class="flex justify-between gap-4 px-2 py-1.5">
                <span class="text-form-label">OAT</span>
                <span class="text-body-strong text-right">{{ formatCurrency(detail.penawaran.oat) }}</span>
              </div>
              <div class="flex justify-between gap-4 rounded-lg bg-emerald-50 px-2 py-1.5">
                <span class="text-form-label">Harga per m³</span>
                <span class="text-body-strong text-right !text-emerald-700">
                  {{ formatCurrency(detail.penawaran.harga_per_m3) }}
                </span>
              </div>
              <div class="flex justify-between gap-4 px-2 py-1.5">
                <span class="text-form-label">Total Volume</span>
                <span class="text-body-strong text-right">{{ formatNumber(detail.penawaran.total_volume) }} m³</span>
              </div>
              <div class="flex justify-between gap-4 px-2 py-1.5">
                <span class="text-form-label">Total Harga (Sebelum PPN)</span>
                <span class="text-body-strong text-right">{{ formatCurrency(detail.penawaran.total_harga) }}</span>
              </div>
              <div class="flex justify-between gap-4 rounded-lg bg-slate-100 px-2 py-1.5">
                <span class="text-form-label">Total Harga (Incl. PPN 11%)</span>
                <span class="text-body-strong text-right">{{ formatCurrency(detail.penawaran.total_harga_ppn) }}</span>
              </div>
            </dl>

            <hr class="my-3" />

            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Tipe Pembayaran</span>
              <span class="text-body-strong text-right">{{ paymentTypeLabel }}</span>
            </div>

            <div class="mt-2 space-y-1">
              <span class="text-form-label">Lokasi Pengiriman</span>
              <div class="rounded-lg border border-slate-200 p-3 text-body">
                {{ detail.penawaran.lokasi_pengiriman || '-' }}
              </div>
            </div>
          </template>

          <div v-if="penawaranItems.length" class="mt-4 space-y-2">
            <span class="text-form-label">Detail Produk</span>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
              <table class="w-full text-left text-body">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-3 py-2 text-form-label">Produk</th>
                    <th class="px-3 py-2 text-form-label">Ukuran</th>
                    <th class="px-3 py-2 text-right text-form-label">Rasio</th>
                    <th class="px-3 py-2 text-right text-form-label">Volume</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in penawaranItems" :key="index" class="border-t border-slate-100">
                    <td class="px-3 py-2">{{ item.produk || '-' }}</td>
                    <td class="px-3 py-2">
                      {{ item.ukuran || '-' }}<template v-if="item.satuan"> {{ item.satuan }}</template>
                    </td>
                    <td class="px-3 py-2 text-right">{{ item.persen != null ? `${formatNumber(item.persen)}%` : '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ formatNumber(item.volume_order) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="detail.penawaran?.id_penawaran"
            class="mt-4 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <span class="text-form-label">Persetujuan Penawaran</span>
            <div v-if="approvalSteps.length" class="grid grid-cols-2 gap-3">
              <div v-for="step in approvalSteps" :key="step.label">
                <div class="text-caption text-slate-500">{{ step.label }}</div>
                <div class="text-body-strong">{{ step.text }}</div>
              </div>
            </div>
            <div v-else class="text-body">-</div>

            <Button v-if="penawaranHref" as="a" variant="outline-secondary" class="w-full bg-white"
              :href="penawaranHref" target="_blank">
              <Lucide icon="Eye" class="mr-2 h-4 w-4" />
              Buka Penawaran
            </Button>
          </div>
        </CardSection>

        <CardSection title="Purchase Order (PO)" icon="FileText" icon-class="bg-emerald-100 text-emerald-600"
          class="border border-slate-200">
          <template #action>
            <span class="rounded-full bg-blue-100 px-3 py-1 font-mono text-xs font-semibold text-blue-700">
              {{ detail.poc?.nomor_poc || '-' }}
            </span>
          </template>

          <dl class="space-y-1">
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Nomor PO</span>
              <span class="text-body-strong text-right font-mono">{{ detail.poc?.nomor_poc || '-' }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Tanggal PO</span>
              <span class="text-body-strong text-right">{{ formatDate(detail.poc?.tanggal_poc) }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Supply Date</span>
              <span class="text-body-strong text-right">{{ formatDate(detail.poc?.supply_date) }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Tipe Pembayaran</span>
              <span class="text-body-strong text-right">{{ pocPaymentLabel }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Volume</span>
              <span class="text-body-strong text-right">{{ formatNumber(detail.poc?.volume_poc) }} m³</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Harga per m³</span>
              <span class="text-body-strong text-right">{{ formatCurrency(detail.poc?.harga_poc) }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Total Nilai PO</span>
              <span class="text-body-strong text-right">{{ formatCurrency(detail.poc?.total_nilai) }}</span>
            </div>
            <div class="flex justify-between gap-4 rounded-lg bg-slate-100 px-2 py-1.5">
              <span class="text-form-label">Total Nilai PO (Incl. PPN 11%)</span>
              <span class="text-body-strong text-right">{{ formatCurrency(detail.poc?.total_nilai_ppn) }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Dibuat Oleh</span>
              <span class="text-body-strong text-right">{{ detail.poc?.created_by || '-' }}</span>
            </div>
            <div class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">Tanggal Dibuat</span>
              <span class="text-body-strong text-right">{{ formatDateTime(detail.poc?.created_time) || '-' }}</span>
            </div>
          </dl>

          <div class="mt-4 space-y-2">
            <span class="text-form-label">Lampiran PO Customer</span>
            <div v-if="detail.poc?.lampiran_poc"
              class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                :class="attachmentKind.iconClass">
                <Lucide :icon="attachmentKind.icon" class="h-5 w-5" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate text-body-strong" :title="attachmentName">{{ attachmentName }}</div>
                <div class="text-caption text-slate-500">{{ attachmentKind.label }}</div>
              </div>
              <Button as="a" variant="outline-secondary" class="!h-8 !w-8 !p-0 !shadow-none" title="Pratinjau"
                :href="`/storage/${detail.poc.lampiran_poc}`" target="_blank">
                <Lucide icon="Eye" class="h-4 w-4" />
              </Button>
              <Button as="a" variant="primary" class="!h-8 !w-8 !p-0 !shadow-none" title="Unduh"
                :href="`/storage/${detail.poc.lampiran_poc}`" :download="detail.poc.lampiran_poc_ori">
                <Lucide icon="Download" class="h-4 w-4" />
              </Button>
            </div>
            <div v-else
              class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-body text-slate-500">
              Tidak ada lampiran
            </div>
          </div>

          <Button as="a" variant="dark" class="mt-4 w-full" :href="pocHref" target="_blank">
            <Lucide icon="ExternalLink" class="mr-2 h-4 w-4" />
            Buka PO Full Detail
          </Button>
        </CardSection>

        <CardSection title="Balance AR" icon="Wallet" icon-class="bg-amber-100 text-amber-600"
          class="border border-slate-200">
          <template #action>
            <span class="rounded-full border border-slate-300 px-3 py-1 text-form-label">Snapshot AR</span>
          </template>

          <div class="space-y-2 rounded-lg border border-amber-200 bg-amber-50 p-4">
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

          <dl class="mt-3 space-y-1">
            <div v-for="bucket in arBuckets" :key="bucket.key" class="flex justify-between gap-4 px-2 py-1.5">
              <span class="text-form-label">{{ bucket.label }}</span>
              <span class="text-body-strong text-right">{{ formatCurrency(bucketDisplay[bucket.key]) }}</span>
            </div>
          </dl>

          <hr class="my-3" />

          <div class="space-y-1">
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

          <div class="mt-3 flex items-center justify-between gap-4 rounded-lg border p-3"
            :class="remainingAfterOrder < 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50'">
            <span class="text-form-label">Sisa Limit Setelah Order Ini</span>
            <span class="text-body-strong" :class="remainingAfterOrder < 0 ? '!text-red-600' : '!text-emerald-700'">
              {{ formatCurrency(remainingAfterOrder) }}
            </span>
          </div>

          <div class="mt-3 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <Lucide icon="CheckCircle" class="mt-0.5 h-6 w-6 shrink-0 text-emerald-600" />
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
      </div>

      <form v-if="isAdminEditable" class="flex flex-col gap-6" @submit.prevent="submitAdmin">
        <CardSection title="Catatan & Konfirmasi" icon="MessageSquare" icon-class="bg-emerald-100 text-emerald-600"
          class="border border-slate-200">
          <div class="space-y-4">
            <div v-if="bmRejectionStep" class="pt-2 border-t border-slate-100">
              <span class="text-form-label">Ditolak Branch Manager</span>
              <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-body">
                {{ bmRejectionStep.decision_note }}
              </div>
              <div class="mt-1 text-caption text-slate-500">
                {{ bmRejectionStep.actor_name || '-' }} ·
                {{ formatDateTime(bmRejectionStep.acted_at) || '-' }}
              </div>
            </div>

            <div>
              <FormLabel htmlFor="admin-summary">Catatan Admin Finance</FormLabel>
              <FormTextarea id="admin-summary" v-model="form.admin_summary" :rows="4"
                placeholder="Catatan / analisa Admin Finance (opsional)" :disabled="submitting" />
            </div>

            <FileUploadField v-model="newAttachments" label="Lampiran"
              hint="Opsional. PDF/JPG/PNG maks 2MB per file." accept=".pdf,.jpg,.jpeg,.png" multiple
              :max-size-mb="2" :existing-files="existingAdminFiles" :disabled="submitting"
              @remove-existing="markAdminAttachmentRemoved" />

            <div class="flex justify-between">
              <Button type="button" variant="outline-danger" :disabled="submitting || returning"
                @click="openReturnDialog">
                <Lucide icon="Undo2" class="mr-2 h-4 w-4" />
                Kembalikan ke Marketing
              </Button>

              <Button type="submit" variant="primary" :disabled="submitting || returning">
                <Lucide v-if="submitting" icon="Loader2" class="mr-2 h-4 w-4 animate-spin" />
                <Lucide v-else icon="ShieldCheck" class="mr-2 h-4 w-4" />
                Konfirmasi SC
              </Button>
            </div>
          </div>
        </CardSection>
      </form>

      <div v-else-if="isBmDecidable" class="flex flex-col gap-6">
        <CardSection title="Keputusan" description="Putuskan setuju atau tolak Sales Confirmation ini."
          icon="Gavel" icon-class="bg-blue-100 text-blue-600" class="border border-slate-200">
          <div class="space-y-3">
            <div v-if="detail.approval?.adm_summary" class="space-y-1">
              <span class="text-form-label">Catatan Admin Finance</span>
              <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-body"
                v-html="detail.approval.adm_summary"></div>
            </div>

            <div v-if="visibleAdminAttachments.length" class="space-y-2">
              <span class="text-form-label">Lampiran Admin Finance</span>
              <div v-for="file in visibleAdminAttachments" :key="file.path"
                class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                  :class="resolveAttachmentKind(adminAttachmentName(file)).iconClass">
                  <Lucide :icon="resolveAttachmentKind(adminAttachmentName(file)).icon" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-body-strong" :title="adminAttachmentName(file)">{{ adminAttachmentName(file) }}</div>
                  <div class="text-caption text-slate-500">{{ resolveAttachmentKind(adminAttachmentName(file)).label }}</div>
                </div>
                <template v-if="file.url">
                  <Button as="a" variant="outline-secondary" class="!h-8 !w-8 !p-0 !shadow-none" title="Pratinjau"
                    :href="file.url" target="_blank">
                    <Lucide icon="Eye" class="h-4 w-4" />
                  </Button>
                  <Button as="a" variant="primary" class="!h-8 !w-8 !p-0 !shadow-none" title="Unduh"
                    :href="file.url" :download="adminAttachmentName(file)">
                    <Lucide icon="Download" class="h-4 w-4" />
                  </Button>
                </template>
              </div>
            </div>

            <div class="flex gap-2">
              <Button type="button" class="flex-1 justify-center items-center gap-2"
                :variant="bmDecision === 'approve' ? 'primary' : 'outline-secondary'" :disabled="bmSubmitting"
                @click="bmDecision = 'approve'">
                Setuju
              </Button>
              <Button type="button" class="flex-1 justify-center items-center gap-2"
                :variant="bmDecision === 'reject' ? 'danger' : 'outline-secondary'" :disabled="bmSubmitting"
                @click="bmDecision = 'reject'">
                Tolak
              </Button>
            </div>

            <div>
              <div class="mb-1 flex items-center justify-between">
                <FormLabel htmlFor="bm-note">Catatan Keputusan</FormLabel>
                <span class="text-caption" :class="bmDecision === 'reject' ? 'text-amber-600' : 'text-slate-400'">
                  {{ bmDecision === 'reject' ? 'Wajib untuk Tolak' : 'Opsional' }}
                </span>
              </div>
              <FormTextarea id="bm-note" v-model="bmNote" :rows="4" placeholder="Catatan keputusan Branch Manager"
                :disabled="bmSubmitting" />
            </div>

            <Button type="button" class="inline-flex justify-center items-center gap-2 w-full"
              :variant="bmDecision === 'approve' ? 'primary' : 'danger'" :disabled="bmSubmitDisabled"
              @click="bmConfirmOpen = true">
              <Lucide icon="Send" class="h-4 w-4" />
              Ajukan Keputusan
            </Button>
          </div>
        </CardSection>
      </div>

      <template v-else>
        <CardSection title="Disposisi" icon="ShieldCheck" icon-class="bg-emerald-100 text-emerald-600"
          class="border border-slate-200">
          <div class="space-y-3">
            <div class="space-y-2">
              <span class="text-form-label inline-flex items-center rounded-full px-4 py-1.5 text-base"
                :class="salesConfirmationBadgeClass(detail.sc?.disposisi)">
                {{ detail.sc?.disposisi_label || '-' }}
              </span>
              <div v-if="formatDateTime(detail.sc?.disposisi_time)" class="text-body text-slate-500">
                {{ formatDateTime(detail.sc?.disposisi_time) }} WIB
              </div>
            </div>

            <div v-if="detail.approval?.adm_summary" class="pt-2 border-t border-slate-100">
              <span class="text-form-label">Catatan Admin Finance</span>
              <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-body"
                v-html="detail.approval.adm_summary"></div>
            </div>

            <div v-if="visibleAdminAttachments.length" class="space-y-2 border-t border-slate-100 pt-2">
              <span class="text-form-label">Lampiran Admin Finance</span>
              <div v-for="file in visibleAdminAttachments" :key="file.path"
                class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                  :class="resolveAttachmentKind(adminAttachmentName(file)).iconClass">
                  <Lucide :icon="resolveAttachmentKind(adminAttachmentName(file)).icon" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-body-strong" :title="adminAttachmentName(file)">{{ adminAttachmentName(file) }}</div>
                  <div class="text-caption text-slate-500">{{ resolveAttachmentKind(adminAttachmentName(file)).label }}</div>
                </div>
                <template v-if="file.url">
                  <Button as="a" variant="outline-secondary" class="!h-8 !w-8 !p-0 !shadow-none" title="Pratinjau"
                    :href="file.url" target="_blank">
                    <Lucide icon="Eye" class="h-4 w-4" />
                  </Button>
                  <Button as="a" variant="primary" class="!h-8 !w-8 !p-0 !shadow-none" title="Unduh"
                    :href="file.url" :download="adminAttachmentName(file)">
                    <Lucide icon="Download" class="h-4 w-4" />
                  </Button>
                </template>
              </div>
            </div>

            <div v-if="detail.bm_approval?.steps?.[0]?.decision_note" class="pt-2 border-t border-slate-100">
              <span class="text-form-label">Catatan Branch Manager</span>
              <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-body">
                {{ detail.bm_approval.steps[0].decision_note }}
              </div>
              <div class="mt-1 text-caption text-slate-500">
                {{ detail.bm_approval.steps[0].actor_name || '-' }} ·
                {{ formatDateTime(detail.bm_approval.steps[0].acted_at) || '-' }}
              </div>
            </div>
          </div>
        </CardSection>
      </template>

      <ConfirmDialog :open="bmConfirmOpen"
        :title="bmDecision === 'approve' ? 'Setujui Sales Confirmation ini?' : 'Tolak Sales Confirmation ini?'"
        :description="bmDecision === 'approve'
          ? 'Sales Confirmation akan disetujui dan dilanjutkan ke proses berikutnya.'
          : 'Sales Confirmation akan dikembalikan ke Admin Finance beserta catatan penolakan Anda.'"
        :confirm-text="bmDecision === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
        :icon="bmDecision === 'approve' ? 'Check' : 'X'"
        :icon-class="bmDecision === 'approve' ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
        :variant="bmDecision === 'approve' ? 'primary' : 'danger'" :loading="bmSubmitting"
        @close="bmConfirmOpen = false" @confirm="submitBmDecision" />

      <ConfirmDialog :open="returnDialogOpen" variant="warning" title="Kembalikan ke Marketing?"
        description="PO akan kembali ke Marketing untuk direvisi. Sales Confirmation yang sedang disiapkan (termasuk catatan Admin Finance) akan dihapus, dan Marketing perlu menjalankan Proses SC ulang setelah revisi."
        confirm-text="Ya, kembalikan" :loading="returning" @close="returnDialogOpen = false" @confirm="submitReturn" />
    </div>
  </div>
</template>
