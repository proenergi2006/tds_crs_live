<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { salesConfirmationBadgeClass } from './status'

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

const scDisposisi = computed<number | null>(() => {
  const value = detail.value?.sc?.disposisi
  return value == null ? null : Number(value)
})

const isAdminEditable = computed<boolean>(
  () =>
    auth.hasRole(ROLE_ADMIN_FINANCE) &&
    (scDisposisi.value === null || scDisposisi.value === 1),
)

const totalAr = computed<number>(() =>
  arBuckets.reduce((sum, bucket) => sum + Number(bucketDisplay.value[bucket.key] || 0), 0),
)

const remaining = computed<number>(() => creditLimitDisplay.value - totalAr.value)

const customerRows = computed<Array<{ label: string; value: string }>>(() => {
  const d = detail.value
  if (!d) return []
  return [
    { label: 'Kode Customer', value: d.customer?.customer_code || '-' },
    { label: 'Nama Customer', value: d.customer?.company_name || '-' },
    { label: 'Nomor Penawaran', value: d.penawaran?.nomor_penawaran || '-' },
    { label: 'Marketing', value: d.penawaran?.marketing_name || '-' },
  ]
})

const poRows = computed<Array<{ label: string; value: string }>>(() => {
  const d = detail.value
  if (!d) return []
  return [
    { label: 'Nomor PO', value: d.poc?.nomor_poc || '-' },
    { label: 'Tanggal PO', value: formatDate(d.poc?.tanggal_poc) },
    { label: 'Supply Date', value: formatDate(d.poc?.supply_date) },
    {
      label: 'Tipe Pembayaran',
      value: d.poc?.tipe_bayar
        ? (d.poc.tipe_bayar === 'CREDIT' && d.poc.termin_hari
            ? `${d.poc.tipe_bayar_label} — ${d.poc.termin_hari} Hari`
            : (d.poc.tipe_bayar_label ?? d.poc.tipe_bayar))
        : '-',
    },
    { label: 'Volume', value: `${formatNumber(d.poc?.volume_poc)} m³` },
    { label: 'Harga per m³', value: formatCurrency(d.poc?.harga_poc) },
    { label: 'Total Nilai PO', value: formatCurrency(Number(d.poc?.harga_poc ?? 0) * Number(d.poc?.volume_poc ?? 0)) },
  ]
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

function buildAdminPayload(): { admin_summary: string } {
  return { admin_summary: form.value.admin_summary }
}

async function submitAdmin(): Promise<void> {
  formError.value = null
  submitting.value = true
  try {
    await axios.post(`/api/sales-confirmations/po/${idPoc}`, buildAdminPayload())
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

  <div v-else class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-6">
      <PageHeader :title="isAdminEditable ? 'Sales Confirmation' : 'Detail Sales Confirmation'"
        :description="isAdminEditable
          ? 'Tinjau dan konfirmasi Sales Confirmation.'
          : 'Ringkasan Sales Confirmation dan keputusan verifikasi.'"
        variant="flat">
        <template #action>
          <Button variant="outline-secondary" @click="backToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </template>
      </PageHeader>

      <div v-if="formError"
        class="whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
        {{ formError }}
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <CardSection title="Customer & Penawaran" icon="Users" icon-class="bg-blue-100 text-blue-600">
          <dl class="space-y-3">
            <div v-for="row in customerRows" :key="row.label"
              class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">{{ row.label }}</span>
              <span class="font-strong text-right">{{ row.value }}</span>
            </div>
          </dl>
        </CardSection>

        <CardSection title="PO" icon="FileText">
          <dl class="space-y-3">
            <div v-for="row in poRows" :key="row.label"
              class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">{{ row.label }}</span>
              <span class="font-strong text-right">{{ row.value }}</span>
            </div>
          </dl>
        </CardSection>

        <CardSection title="Balance AR" icon="Wallet" icon-class="bg-amber-100 text-amber-600"
          description="Snapshot dari AR Aging Customer.">
          <dl class="space-y-3">
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Credit Limit (Hasil Verifikasi)</span>
              <span class="font-strong text-right">{{ formatCurrency(creditLimitDisplay) }}</span>
            </div>
            <div v-for="bucket in arBuckets" :key="bucket.key"
              class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">{{ bucket.label }}</span>
              <span class="font-strong text-right">{{ formatCurrency(bucketDisplay[bucket.key]) }}</span>
            </div>
          </dl>
          <div class="mt-4 flex flex-col gap-2">
            <div class="flex items-center justify-between gap-3">
              <span class="font-label">Total AR</span>
              <span class="font-strong">{{ formatCurrency(totalAr) }}</span>
            </div>
            <div class="flex items-center justify-between gap-3">
              <span class="font-label">Sisa Limit</span>
              <span class="font-strong">{{ formatCurrency(remaining) }}</span>
            </div>
          </div>
        </CardSection>
      </div>

      <form v-if="isAdminEditable" class="flex flex-col gap-6" @submit.prevent="submitAdmin">
        <CardSection title="Catatan & Konfirmasi" icon="MessageSquare" icon-class="bg-emerald-100 text-emerald-600">
          <div class="space-y-4">
            <div>
              <FormLabel htmlFor="admin-summary">Catatan Admin Finance</FormLabel>
              <FormTextarea id="admin-summary" v-model="form.admin_summary" :rows="4"
                placeholder="Catatan / analisa Admin Finance (opsional)" :disabled="submitting" />
            </div>

            <div class="flex justify-end">
              <Button type="submit" variant="primary" :disabled="submitting">
                <Lucide v-if="submitting" icon="Loader2" class="mr-2 h-4 w-4 animate-spin" />
                <Lucide v-else icon="ShieldCheck" class="mr-2 h-4 w-4" />
                Konfirmasi SC
              </Button>
            </div>
          </div>
        </CardSection>
      </form>

      <template v-else>
        <CardSection title="Disposisi" icon="ShieldCheck" icon-class="bg-emerald-100 text-emerald-600">
          <div class="space-y-3">
            <div class="space-y-2">
              <span class="font-label inline-flex items-center rounded-full px-4 py-1.5 text-base"
                :class="salesConfirmationBadgeClass(detail.sc?.disposisi)">
                {{ detail.sc?.disposisi_label || '-' }}
              </span>
              <div v-if="formatDateTime(detail.sc?.disposisi_time)" class="font-body text-slate-500">
                {{ formatDateTime(detail.sc?.disposisi_time) }} WIB
              </div>
            </div>

            <div v-if="detail.approval?.adm_summary" class="pt-2 border-t border-slate-100">
              <span class="font-label">Catatan Admin Finance</span>
              <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-3 font-body"
                v-html="detail.approval.adm_summary"></div>
            </div>
          </div>
        </CardSection>
      </template>
    </div>
  </div>
</template>
