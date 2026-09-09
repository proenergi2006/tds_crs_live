<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormCheck, FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { salesConfirmationBadgeClass } from './status'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const ROLE_BM = 8
const ROLE_ADMIN_FINANCE = 9
const idPoc = Number(route.params.id)

const arBuckets = [
  { key: 'not_yet', label: 'Not Yet' },
  { key: 'ov_up_07', label: 'Overdue 1-7 Hari' },
  { key: 'ov_under_30', label: 'Overdue 8-30 Hari' },
  { key: 'ov_under_60', label: 'Overdue 31-60 Hari' },
  { key: 'ov_under_90', label: 'Overdue 61-90 Hari' },
  { key: 'ov_up_90', label: 'Overdue > 90 Hari' },
] as const

const detail = ref<any>(null)
const loading = ref(true)
const submitting = ref(false)
const formError = ref<string | null>(null)

const form = ref<{
  credit_limit: number
  not_yet: number
  ov_up_07: number
  ov_under_30: number
  ov_under_60: number
  ov_under_90: number
  ov_up_90: number
  po_status: string
  po_volume: number
  po_amount: number
  reminding: string
  proposed_status: 0 | 1
  add_top: 0 | 1
  add_cl: 0 | 1
  type_customer: 1 | 2 | null
  customer_amount: number
  customer_date: string
  approval: 0 | 1 | 2
  admin_summary: string
}>({
  credit_limit: 0,
  not_yet: 0,
  ov_up_07: 0,
  ov_under_30: 0,
  ov_under_60: 0,
  ov_under_90: 0,
  ov_up_90: 0,
  po_status: 'new',
  po_volume: 0,
  po_amount: 0,
  reminding: '',
  proposed_status: 0,
  add_top: 0,
  add_cl: 0,
  type_customer: null,
  customer_amount: 0,
  customer_date: '',
  approval: 0,
  admin_summary: '',
})

const collateral = ref<Array<{ item: string; amount: number; date: string }>>([])
const attachment = ref<File | null>(null)
const bm = ref<{ result: 1 | 2 | null; summary: string }>({ result: null, summary: '' })

const hargaPerM3 = computed<number>(() => Number(detail.value?.poc?.harga_poc ?? 0))

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
  () => auth.hasRole(ROLE_BM) && scDisposisi.value === 2,
)

const remaining = computed<number>(() => {
  const f = form.value
  const total =
    Number(f.not_yet || 0) +
    Number(f.ov_up_07 || 0) +
    Number(f.ov_under_30 || 0) +
    Number(f.ov_under_60 || 0) +
    Number(f.ov_under_90 || 0) +
    Number(f.ov_up_90 || 0)
  return Number(f.credit_limit || 0) - total
})

const headerRows = computed<Array<{ label: string; value: string }>>(() => {
  const d = detail.value
  if (!d) return []
  return [
    { label: 'Date / Periode', value: formatDate(d.poc?.tanggal_poc) },
    { label: 'Supply Date', value: formatDate(d.poc?.supply_date) },
    { label: 'Kode Customer', value: d.customer?.customer_code || '-' },
    { label: 'Nama Customer', value: d.customer?.company_name || '-' },
    { label: 'TOP', value: d.penawaran?.top != null && d.penawaran?.top !== '' ? String(d.penawaran.top) : '-' },
    { label: 'Credit Limit (hasil verifikasi)', value: formatCurrency(d.customer?.credit_limit) },
  ]
})

const adminSummaryRows = computed<Array<{ label: string; value: string }>>(() => {
  const h = detail.value?.sc_header
  if (!h) return []
  const rows = [
    { label: 'Tipe PO', value: poStatusLabel(h.po_status) },
    { label: 'Volume', value: `${formatNumber(h.po_volume)} m³` },
    { label: 'Amount', value: formatCurrency(h.po_amount) },
    { label: 'Jadwal Pembayaran', value: Number(h.proposed_status) === 1 ? 'Proposed' : 'Not Proposed' },
    { label: 'Add TOP', value: Number(h.add_top) === 1 ? 'Ya' : 'Tidak' },
    { label: 'Add CL', value: Number(h.add_cl) === 1 ? 'Ya' : 'Tidak' },
    { label: 'Tipe Pembayaran', value: paymentTypeLabel(h.type_customer) },
  ]
  if (Number(h.type_customer) === 1) {
    rows.push({ label: 'Commitment Amount', value: formatCurrency(h.customer_amount) })
    rows.push({ label: 'Commitment Date', value: formatDate(h.customer_date) })
  }
  return rows
})

onMounted(load)

async function load(): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    detail.value = data
    prefillForm(data)
  } catch (e: any) {
    detail.value = null
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat Sales Confirmation.')
  } finally {
    loading.value = false
  }
}

function prefillForm(data: any): void {
  const poc = data.poc ?? {}
  const arnya = data.arnya ?? {}
  const header = data.sc_header
  const approval = data.approval

  form.value.credit_limit = Number(data.customer?.credit_limit ?? 0)

  if (header) {
    form.value.not_yet = Number(header.not_yet ?? 0)
    form.value.ov_up_07 = Number(header.ov_up_07 ?? 0)
    form.value.ov_under_30 = Number(header.ov_under_30 ?? 0)
    form.value.ov_under_60 = Number(header.ov_under_60 ?? 0)
    form.value.ov_under_90 = Number(header.ov_under_90 ?? 0)
    form.value.ov_up_90 = Number(header.ov_up_90 ?? 0)
    form.value.po_status = header.po_status || 'new'
    form.value.po_volume = Number(header.po_volume ?? 0)
    form.value.po_amount = Number(header.po_amount ?? 0)
    form.value.reminding = header.reminding ?? ''
    form.value.proposed_status = Number(header.proposed_status) === 1 ? 1 : 0
    form.value.add_top = Number(header.add_top) === 1 ? 1 : 0
    form.value.add_cl = Number(header.add_cl) === 1 ? 1 : 0
    form.value.type_customer = header.type_customer ? (Number(header.type_customer) as 1 | 2) : null
    form.value.customer_amount = Number(header.customer_amount ?? 0)
    form.value.customer_date = header.customer_date ?? ''
  } else {
    form.value.not_yet = Number(arnya.not_yet ?? 0)
    form.value.ov_up_07 = Number(arnya.ov_up_07 ?? 0)
    form.value.ov_under_30 = Number(arnya.ov_under_30 ?? 0)
    form.value.ov_under_60 = Number(arnya.ov_under_60 ?? 0)
    form.value.ov_under_90 = Number(arnya.ov_under_90 ?? 0)
    form.value.ov_up_90 = Number(arnya.ov_up_90 ?? 0)
    form.value.po_volume = Number(poc.volume_poc ?? 0)
    form.value.po_amount = hargaPerM3.value * Number(poc.volume_poc ?? 0)
  }

  if (approval) {
    form.value.approval = ([0, 1, 2].includes(Number(approval.adm_result))
      ? Number(approval.adm_result)
      : 0) as 0 | 1 | 2
    form.value.admin_summary = String(approval.adm_summary ?? '').replace(/<br\s*\/?>/gi, '\n')
    bm.value.result =
      Number(approval.bm_result) === 1 || Number(approval.bm_result) === 2
        ? (Number(approval.bm_result) as 1 | 2)
        : null
    bm.value.summary = String(approval.bm_summary ?? '').replace(/<br\s*\/?>/gi, '\n')
  }
}

function poStatusLabel(value: string | null | undefined): string {
  if (value === 'new') return 'New PO'
  if (value === 'partial') return 'Partial PO / Kontrak'
  return value || '-'
}

function paymentTypeLabel(value: number | string | null | undefined): string {
  if (Number(value) === 1) return 'Customer Commitment'
  if (Number(value) === 2) return 'Customer Collateral'
  return '-'
}

function onVolumeInput(value: number): void {
  form.value.po_volume = Number(value || 0)
  form.value.po_amount = hargaPerM3.value * Number(value || 0)
}

function buildAdminPayload(): FormData {
  const fd = new FormData()
  const f = form.value
  const fields: Record<string, unknown> = {
    credit_limit: f.credit_limit,
    not_yet: f.not_yet,
    ov_up_07: f.ov_up_07,
    ov_under_30: f.ov_under_30,
    ov_under_60: f.ov_under_60,
    ov_under_90: f.ov_under_90,
    ov_up_90: f.ov_up_90,
    po_status: f.po_status,
    po_volume: f.po_volume,
    po_amount: f.po_amount,
    reminding: f.reminding,
    proposed_status: f.proposed_status,
    add_top: f.add_top,
    add_cl: f.add_cl,
    type_customer: f.type_customer,
    customer_amount: f.customer_amount,
    customer_date: f.customer_date,
    approval: f.approval,
    admin_summary: f.admin_summary,
  }
  for (const [key, value] of Object.entries(fields)) {
    fd.append(key, value == null ? '' : String(value))
  }
  collateral.value.forEach((row, index) => {
    fd.append(`item_coll[${index}]`, row.item || '')
    fd.append(`customer_amount_coll[${index}]`, String(Number(row.amount) || 0))
    fd.append(`customer_date_coll[${index}]`, row.date || '')
  })
  if (attachment.value) {
    fd.append('lampiran_unblock', attachment.value)
  }
  return fd
}

async function submitAdmin(): Promise<void> {
  if (form.value.proposed_status === 1 && !attachment.value) {
    formError.value = 'File Attachment Unblock wajib diupload saat Proposed.'
    return
  }
  formError.value = null
  submitting.value = true
  try {
    await axios.post(`/api/sales-confirmations/po/${idPoc}`, buildAdminPayload(), {
      headers: { 'Content-Type': 'multipart/form-data' },
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

async function submitBm(): Promise<void> {
  if (bm.value.result == null) {
    notifyError('Validasi', 'Pilih persetujuan BM (Setuju/Tidak).')
    return
  }
  submitting.value = true
  try {
    await axios.post(`/api/sales-confirmations/po/${idPoc}/bm`, {
      bm_result: bm.value.result,
      bm_summary: bm.value.summary,
    })
    success('Berhasil', 'Keputusan Branch Manager berhasil disimpan.')
    backToIndex()
  } catch (e: any) {
    const status = e.response?.status
    if (status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Status Sales Confirmation sudah berubah.')
      load()
    } else if (status === 404) {
      notifyError('Gagal', e.response?.data?.message ?? 'Sales Confirmation belum dibuat oleh Admin.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan keputusan Branch Manager.')
    }
  } finally {
    submitting.value = false
  }
}

function addCollateralRow(): void {
  collateral.value.push({ item: '', amount: 0, date: '' })
}

function removeCollateralRow(index: number): void {
  collateral.value.splice(index, 1)
}

function backToIndex(): void {
  router.push({ name: 'sales-confirmations' })
}
</script>

<template>
  <div v-if="loading" class="page-content-wrapper">
    <div class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
      <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
      <span class="font-body">Memuat Sales Confirmation...</span>
    </div>
  </div>

  <div v-else-if="!detail" class="page-content-wrapper">
    <div class="intro-y flex min-h-[320px] flex-col items-center justify-center gap-2 text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
        <Lucide icon="AlertTriangle" class="h-7 w-7 text-rose-500" />
      </div>
      <h3 class="font-header">Sales Confirmation tidak ditemukan</h3>
      <p class="font-body">Silakan kembali ke halaman sebelumnya.</p>
      <Button variant="outline-secondary" @click="backToIndex">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali ke daftar
      </Button>
    </div>
  </div>

  <FormPage v-else-if="isAdminEditable" title="Sales Confirmation"
    description="Lengkapi data dan usulan Sales Confirmation." surface="plain" size="xl"
    :error="formError" :loading="submitting" submit-text="Simpan" @submit="submitAdmin" @cancel="backToIndex">
    <template #action>
      <Button variant="outline-secondary" @click="backToIndex">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali
      </Button>
    </template>

    <div class="flex flex-col gap-6">
      <CardSection title="Informasi" icon="FileText">
        <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
          <div v-for="row in headerRows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </dl>
      </CardSection>

      <CardSection title="Balance AR" icon="Wallet" icon-class="bg-amber-100 text-amber-600"
        description="Saldo piutang per bucket umur. Sisa limit dihitung otomatis.">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <CurrencyField v-for="bucket in arBuckets" :key="bucket.key" v-model="form[bucket.key]"
            :label="bucket.label" :disabled="submitting" />
        </div>
        <div class="mt-4 flex items-center justify-end gap-3">
          <span class="font-label">Sisa Limit</span>
          <span class="font-strong">{{ formatCurrency(remaining) }}</span>
        </div>
      </CardSection>

      <CardSection title="PO & Usulan" icon="ClipboardList" icon-class="bg-indigo-100 text-indigo-600">
        <div class="space-y-5">
          <div>
            <div class="font-label mb-1.5">Tipe PO</div>
            <div class="flex flex-wrap gap-6">
              <FormCheck>
                <FormCheck.Input id="po-status-new" type="radio" value="new" v-model="form.po_status"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="po-status-new">New PO</FormCheck.Label>
              </FormCheck>
              <FormCheck>
                <FormCheck.Input id="po-status-partial" type="radio" value="partial" v-model="form.po_status"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="po-status-partial">Partial PO / Kontrak</FormCheck.Label>
              </FormCheck>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <NumberField :model-value="form.po_volume" :decimals="0" suffix="m³" label="Volume"
              :disabled="submitting" @update:model-value="onVolumeInput" />
            <CurrencyField v-model="form.po_amount" label="Amount" :disabled="submitting" />
          </div>

          <div>
            <div class="font-label mb-1.5">Tipe Pembayaran</div>
            <div class="flex flex-wrap gap-6">
              <FormCheck>
                <FormCheck.Input id="type-customer-1" type="radio" :value="1" v-model.number="form.type_customer"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="type-customer-1">Customer Commitment</FormCheck.Label>
              </FormCheck>
              <FormCheck>
                <FormCheck.Input id="type-customer-2" type="radio" :value="2" v-model.number="form.type_customer"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="type-customer-2">Customer Collateral</FormCheck.Label>
              </FormCheck>
            </div>
          </div>

          <div v-if="form.type_customer === 1" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <CurrencyField v-model="form.customer_amount" label="Commitment Amount" :disabled="submitting" />
            <DateField v-model="form.customer_date" label="Commitment Date" :disabled="submitting" />
          </div>

          <div v-else-if="form.type_customer === 2" class="space-y-3">
            <div class="font-label">Item Collateral</div>
            <div v-for="(row, index) in collateral" :key="index"
              class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
              <FormInput v-model="row.item" placeholder="Item" :disabled="submitting" />
              <CurrencyField v-model="row.amount" placeholder="0" :disabled="submitting" />
              <DateField v-model="row.date" :disabled="submitting" />
              <Button type="button" variant="outline-secondary" :disabled="submitting"
                @click="removeCollateralRow(index)">
                <Lucide icon="Trash2" class="h-4 w-4" />
              </Button>
            </div>
            <Button type="button" variant="outline-secondary" :disabled="submitting" @click="addCollateralRow">
              <Lucide icon="Plus" class="mr-2 h-4 w-4" />
              Tambah Baris
            </Button>
          </div>

          <div>
            <div class="font-label mb-1.5">Jadwal Pembayaran</div>
            <div class="flex flex-wrap gap-6">
              <FormCheck>
                <FormCheck.Input id="proposed-0" type="radio" :value="0" v-model.number="form.proposed_status"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="proposed-0">Not Proposed</FormCheck.Label>
              </FormCheck>
              <FormCheck>
                <FormCheck.Input id="proposed-1" type="radio" :value="1" v-model.number="form.proposed_status"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="proposed-1">Proposed</FormCheck.Label>
              </FormCheck>
            </div>
          </div>

          <div v-if="form.proposed_status === 1" class="space-y-4">
            <FileUploadField v-model="attachment" label="Attachment Unblock" accept=".pdf,.jpg,.jpeg,.png"
              :max-size-mb="2" hint="Wajib diupload saat status Proposed." :disabled="submitting" />
            <div class="flex flex-wrap gap-6">
              <FormCheck>
                <FormCheck.Input id="add-top" type="checkbox" :model-value="form.add_top === 1"
                  :disabled="submitting" @update:model-value="form.add_top = $event ? 1 : 0" />
                <FormCheck.Label htmlFor="add-top">Add TOP</FormCheck.Label>
              </FormCheck>
              <FormCheck>
                <FormCheck.Input id="add-cl" type="checkbox" :model-value="form.add_cl === 1"
                  :disabled="submitting" @update:model-value="form.add_cl = $event ? 1 : 0" />
                <FormCheck.Label htmlFor="add-cl">Add CL</FormCheck.Label>
              </FormCheck>
            </div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Catatan & Persetujuan" icon="MessageSquare" icon-class="bg-emerald-100 text-emerald-600">
        <div class="space-y-4">
          <div>
            <FormLabel htmlFor="admin-summary">Catatan Admin Finance</FormLabel>
            <FormTextarea id="admin-summary" v-model="form.admin_summary" :rows="4"
              placeholder="Catatan / analisa Admin Finance" :disabled="submitting" />
          </div>
          <div>
            <div class="font-label mb-1.5">Persetujuan</div>
            <div class="flex flex-wrap gap-6">
              <FormCheck>
                <FormCheck.Input id="approval-ya" type="radio" :value="1" v-model.number="form.approval"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="approval-ya">Ya</FormCheck.Label>
              </FormCheck>
              <FormCheck>
                <FormCheck.Input id="approval-tidak" type="radio" :value="2" v-model.number="form.approval"
                  :disabled="submitting" />
                <FormCheck.Label htmlFor="approval-tidak">Tidak</FormCheck.Label>
              </FormCheck>
            </div>
          </div>
        </div>
      </CardSection>
    </div>
  </FormPage>

  <div v-else class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-6">
      <PageHeader title="Detail Sales Confirmation"
        description="Ringkasan Sales Confirmation dan keputusan verifikasi." variant="flat">
        <template #action>
          <Button variant="outline-secondary" @click="backToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </template>
      </PageHeader>

      <CardSection title="Informasi" icon="FileText">
        <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
          <div v-for="row in headerRows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </dl>
      </CardSection>

      <CardSection title="Balance AR" icon="Wallet" icon-class="bg-amber-100 text-amber-600">
        <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="bucket in arBuckets" :key="bucket.key"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ bucket.label }}</span>
            <span class="font-strong text-right">{{ formatCurrency(form[bucket.key]) }}</span>
          </div>
        </dl>
        <div class="mt-4 flex items-center justify-end gap-3">
          <span class="font-label">Sisa Limit</span>
          <span class="font-strong">{{ formatCurrency(remaining) }}</span>
        </div>
      </CardSection>

      <CardSection title="Disposisi" icon="ShieldCheck" icon-class="bg-emerald-100 text-emerald-600">
        <div class="space-y-2">
          <span class="font-label inline-flex items-center rounded-full px-4 py-1.5 text-base"
            :class="salesConfirmationBadgeClass(detail.sc?.disposisi)">
            {{ detail.sc?.disposisi_label || '-' }}
          </span>
          <div v-if="formatDateTime(detail.sc?.disposisi_time)" class="font-body text-slate-500">
            {{ formatDateTime(detail.sc?.disposisi_time) }} WIB
          </div>
        </div>
      </CardSection>

      <CardSection v-if="detail.sc_header" title="Ringkasan Admin Finance" icon="ClipboardList"
        icon-class="bg-indigo-100 text-indigo-600">
        <div class="space-y-4">
          <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
            <div v-for="row in adminSummaryRows" :key="row.label"
              class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">{{ row.label }}</span>
              <span class="font-strong text-right">{{ row.value }}</span>
            </div>
          </dl>

          <div v-if="detail.sc_header.lampiran_unblock">
            <a :href="`/storage/${detail.sc_header.lampiran_unblock}`" target="_blank"
              class="font-strong inline-flex items-center gap-1 text-primary">
              <Lucide icon="Paperclip" class="h-4 w-4" />
              {{ detail.sc_header.lampiran_unblock_ori || 'Lihat lampiran unblock' }}
            </a>
          </div>

          <div v-if="detail.approval?.adm_summary">
            <span class="font-label">Catatan Admin Finance</span>
            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 font-body"
              v-html="detail.approval.adm_summary"></div>
          </div>
        </div>
      </CardSection>

      <CardSection v-if="isBmDecidable" title="Keputusan Branch Manager" icon="Gavel"
        icon-class="bg-blue-100 text-blue-600" description="Verifikasi Sales Confirmation sebagai Branch Manager.">
        <div class="space-y-4">
          <div class="flex flex-wrap gap-6">
            <FormCheck>
              <FormCheck.Input id="bm-setuju" type="radio" :value="1" v-model.number="bm.result"
                :disabled="submitting" />
              <FormCheck.Label htmlFor="bm-setuju">Setuju</FormCheck.Label>
            </FormCheck>
            <FormCheck>
              <FormCheck.Input id="bm-tolak" type="radio" :value="2" v-model.number="bm.result"
                :disabled="submitting" />
              <FormCheck.Label htmlFor="bm-tolak">Tidak</FormCheck.Label>
            </FormCheck>
          </div>

          <div>
            <FormLabel htmlFor="bm-summary">Catatan</FormLabel>
            <FormTextarea id="bm-summary" v-model="bm.summary" :rows="3"
              placeholder="Catatan keputusan (opsional)" :disabled="submitting" />
          </div>

          <div class="flex justify-end gap-2">
            <Button variant="outline-secondary" :disabled="submitting" @click="backToIndex">
              Batal
            </Button>
            <Button variant="primary" :disabled="submitting" @click="submitBm">
              <Lucide icon="Send" class="mr-2 h-4 w-4" />
              Simpan
            </Button>
          </div>
        </div>
      </CardSection>
    </div>
  </div>
</template>
