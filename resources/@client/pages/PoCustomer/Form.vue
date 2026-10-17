<script setup lang="ts">
import { computed, onMounted, reactive, ref, toRefs } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, integer, minValue, required, requiredIf } from '@vuelidate/validators'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import { FormCheck, FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import Tippy from '@/components/Base/Tippy'
import TomSelect from '@/components/Base/TomSelect'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { QUOTATION_STATUS } from '@/pages/Quotation/status'
import {
  DEFAULT_PPN_RATE,
  formatCurrency,
  formatDate,
  formatDateRangeShort,
  formatNumber,
  formatPercentRate,
  toDateKey,
  todayDateKey,
} from '@/utils/format'
import { actionErrorMessage } from '@/utils/http-error-route'
import type {
  ApiPaginatedResponse,
  ApiResponse,
  PoCustomerDetail,
  PoCustomerItem,
  PoCustomerTotals,
  QuotationIndexRow,
} from '@/types'

interface PoFormRow {
  quotationItemId: number
  productLabel: string
  productCaption: string | null
  sourceBranch: string | null
  quantityQuoted: number
  unitPrice: number
  transportPrice: number
  finalPrice: number
  checked: boolean
  volume: number
  lastVolume: number
  maxVolume: number
  overOrderLegacy: boolean
}

interface QuotationDetailItem {
  id: number
  quantity: number | string | null
  unit_price: number | string | null
  transport_price: number | string | null
  final_price: number | string | null
  source_branch: { nama_cabang: string } | null
  product: {
    nama_produk: string
    ukuran: { nama_ukuran: string; satuan: { nama_satuan: string } | null } | null
  } | null
}

interface QuotationDetailResponse {
  id: number
  customer_id: number
  customer: { company_name: string } | null
  nomor_penawaran: string | null
  masa_berlaku: string
  sampai_dengan: string
  tipe_pembayaran: string | null
  repayment_hari: number | string | null
  ppn_rate: number | string | null
  items: QuotationDetailItem[]
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const quotationIdFromQuery = String(route.query.quotation_id ?? '')
const idPoc = String(route.params.id ?? '')

const HEADER_ERROR_KEYS = [
  'nomor_poc',
  'tanggal_poc',
  'supply_date',
  'tipe_bayar',
  'termin_hari',
  'quotation_id',
  'id_customer',
  'lampiran_poc',
  'items',
]

const pageLoading = ref(false)
const submitting = ref(false)
const formError = ref('')
const rowsTouched = ref(false)
const unavailableQuotationAlert = ref(false)
const ppnRate = ref<number>(DEFAULT_PPN_RATE)
const rows = ref<PoFormRow[]>([])
const serverErrors = ref<Record<string, string>>({})
const quotationOptions = ref<Array<{ id: number; nomor_penawaran: string; customer_nama: string }>>([])
const poRecord = ref<PoCustomerDetail | null>(null)
const existingAttachments = ref<Array<{ name: string; url: string }>>([])

const form = reactive({
  customer_id: '' as number | string,
  customer_nama: '',
  quotation_id: quotationIdFromQuery as number | string,
  nomor_penawaran: '',
  masa_berlaku: '',
  sampai_dengan: '',
  nomor_po: '',
  tipe_bayar: '' as string,
  termin_hari: '' as string | number,
  tanggal_po: '',
  tanggal_kirim: '',
  lampiran: null as File | null,
})

const isEditMode = computed(() => route.name === 'po-customers-edit')
const canEditPo = computed<boolean>(() => !!poRecord.value?.can_update && !poRecord.value.is_locked)
const checkedRows = computed(() => rows.value.filter(row => row.checked))

const previewTotals = computed<PoCustomerTotals>(() => {
  const taken = checkedRows.value
  const dpp = round2(taken.reduce((sum, row) => sum + rowLineTotal(row), 0))
  const ppn = round2(dpp * ppnRate.value)

  return {
    volume: round2(taken.reduce((sum, row) => sum + row.volume, 0)),
    material_subtotal: round2(taken.reduce((sum, row) => sum + round2(row.volume * row.unitPrice), 0)),
    transport_total: round2(taken.reduce((sum, row) => sum + round2(row.volume * row.transportPrice), 0)),
    dpp,
    ppn_rate: ppnRate.value,
    ppn,
    grand_total: round2(dpp + ppn),
  }
})

const rowErrors = computed<string[]>(() =>
  rows.value.map(row => {
    if (!row.checked) return ''
    if (!(row.volume > 0)) return 'Isi volume atau hapus centang.'
    if (row.volume > row.maxVolume) return `Volume melebihi batas (${formatNumber(row.maxVolume)}).`
    return ''
  }),
)

const itemsError = computed<string>(() => {
  if (!rowsTouched.value) return serverError('items')
  if (rows.value.length > 0 && checkedRows.value.length === 0) return 'Pilih minimal satu item.'
  return serverError('items')
})

const pageTitle = computed(() => (isEditMode.value ? 'Edit PO Customer' : 'Buat PO Customer'))
const pageDescription = computed(() =>
  isEditMode.value
    ? 'Perbarui detail PO Customer. Penawaran hanya dapat diganti dengan penawaran customer yang sama dan masih berlaku.'
    : 'Sumber harga dari Penawaran terpilih. Lengkapi detail PO untuk memproses.',
)
const isPoBlocked = computed(() => isEditMode.value && poRecord.value?.status_key === 'blocked')
const validityRangeLabel = computed(() => formatDateRangeShort(form.masa_berlaku, form.sampai_dengan))
const isQuotationExpiredToday = computed(() => {
  if (isEditMode.value || !form.quotation_id || !form.masa_berlaku || !form.sampai_dengan) return false
  const today = todayDateKey()
  return today < toDateKey(form.masa_berlaku) || today > toDateKey(form.sampai_dengan)
})

const rules = {
  quotation_id: { required: helpers.withMessage('Pilih Penawaran terlebih dahulu', required) },
  nomor_po: { required: helpers.withMessage('Nomor PO Customer wajib diisi', required) },
  tipe_bayar: { required: helpers.withMessage('Tipe pembayaran wajib dipilih', required) },
  termin_hari: {
    requiredIfCredit: helpers.withMessage(
      'Termin (hari) wajib diisi untuk tipe Credit',
      requiredIf(() => form.tipe_bayar === 'CREDIT'),
    ),
    integer: helpers.withMessage('Termin harus berupa angka bulat', integer),
    minValue: helpers.withMessage('Termin harus lebih dari 0', minValue(1)),
  },
  tanggal_po: {
    required: helpers.withMessage('Tanggal PO wajib diisi', required),
    withinQuotationValidity: helpers.withMessage(
      () => `Tanggal PO harus berada dalam masa berlaku penawaran (${validityRangeLabel.value}).`,
      (value: string) => {
        if (!value || !form.masa_berlaku || !form.sampai_dengan) return true
        const day = toDateKey(value)
        return day >= toDateKey(form.masa_berlaku) && day <= toDateKey(form.sampai_dengan)
      },
    ),
  },
  tanggal_kirim: {
    required: helpers.withMessage('Tanggal pengiriman wajib diisi', required),
    notBeforeTanggalPo: helpers.withMessage(
      'Tanggal pengiriman tidak boleh sebelum tanggal PO',
      (value: string) => !value || !form.tanggal_po || value >= form.tanggal_po,
    ),
  },
}

const v$ = useVuelidate(rules, toRefs(form))

onMounted(async () => {
  if (isEditMode.value) {
    const ok = await loadPoCustomer()
    if (!ok) return
  }

  await fetchQuotationOptions()

  if (isEditMode.value) {
    applyPoCustomerToForm()
    ensureCurrentQuotationOption()
    return
  }

  if (quotationIdFromQuery) {
    const available = quotationOptions.value.some(opt => String(opt.id) === quotationIdFromQuery)
    if (available) {
      await fetchQuotationDetail(quotationIdFromQuery)
    } else {
      form.quotation_id = ''
      unavailableQuotationAlert.value = true
    }
  }
})

function rowsFromQuotationItems(items: QuotationDetailItem[]): PoFormRow[] {
  return items.map(it => {
    const quantity = Number(it.quantity ?? 0)
    const unitPrice = Number(it.unit_price ?? 0)
    const transportPrice = Number(it.transport_price ?? 0)

    return {
      quotationItemId: it.id,
      productLabel: it.product?.nama_produk ?? '-',
      productCaption: captionOf(it.product?.ukuran?.nama_ukuran, it.product?.ukuran?.satuan?.nama_satuan),
      sourceBranch: it.source_branch?.nama_cabang ?? null,
      quantityQuoted: quantity,
      unitPrice,
      transportPrice,
      finalPrice: Number(it.final_price ?? round2(unitPrice + transportPrice)),
      checked: true,
      volume: quantity,
      lastVolume: quantity,
      maxVolume: quantity,
      overOrderLegacy: false,
    }
  })
}

function rowsFromPoItems(items: PoCustomerItem[]): PoFormRow[] {
  return items.map(it => ({
    quotationItemId: it.quotation_item_id,
    productLabel: it.product?.name ?? '-',
    productCaption: captionOf(it.product?.size, it.product?.unit),
    sourceBranch: it.source_branch?.name ?? null,
    quantityQuoted: it.quantity_quoted,
    unitPrice: it.unit_price,
    transportPrice: it.transport_price,
    finalPrice: it.final_price,
    checked: it.volume > 0,
    volume: it.volume,
    lastVolume: it.volume > 0 ? it.volume : it.quantity_quoted,
    maxVolume: Math.max(it.quantity_quoted, it.volume),
    overOrderLegacy: it.volume > it.quantity_quoted,
  }))
}

function toggleRow(row: PoFormRow, checked: boolean): void {
  if (checked) {
    row.volume = row.lastVolume
  } else {
    row.lastVolume = row.volume || row.lastVolume
    row.volume = 0
  }
  row.checked = checked
}

function rowVolumeError(index: number): string {
  const local = rowsTouched.value ? rowErrors.value[index] : ''
  return local || serverError(`items.${index}.volume`)
}

function applyServerErrors(errors: Record<string, string[]>): void {
  serverErrors.value = Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0] ?? '']))

  const unmapped = Object.entries(errors)
    .filter(([key]) => !HEADER_ERROR_KEYS.includes(key) && !/^items\.\d+\.(volume|quotation_item_id)$/.test(key))
    .flatMap(([, messages]) => messages)
  formError.value = unmapped.join('\n')
}

async function loadPoCustomer(): Promise<boolean> {
  pageLoading.value = true

  try {
    const { data: body } = await axios.get<ApiResponse<PoCustomerDetail>>(`/api/customer-pos/${idPoc}`)
    poRecord.value = body.data

    if (!canEditPo.value) {
      notifyError('Gagal', 'PO Customer tidak bisa diubah karena sudah masuk proses Sales Confirmation.')
      router.push({ name: 'po-customers-detail', params: { id: idPoc } })
      return false
    }

    form.quotation_id = body.data.quotation_id
    return true
  } catch (e: any) {
    notifyError('Gagal', actionErrorMessage(e, 'Gagal memuat data PO Customer'))
    router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    return false
  } finally {
    pageLoading.value = false
  }
}

function applyQuotationHeaderFromPo(po: PoCustomerDetail): void {
  form.quotation_id = po.quotation_id
  form.nomor_penawaran = po.quotation?.nomor_penawaran || '-'
  form.masa_berlaku = po.quotation?.masa_berlaku ?? ''
  form.sampai_dengan = po.quotation?.sampai_dengan ?? ''
}

function applyPoCustomerToForm(): void {
  const po = poRecord.value
  if (!po) return

  form.customer_id = po.id_customer
  form.customer_nama = po.customer?.company_name || '-'
  applyQuotationHeaderFromPo(po)
  ppnRate.value = Number(po.totals.ppn_rate)
  form.nomor_po = po.nomor_poc ?? ''
  form.tanggal_po = po.tanggal_poc ?? ''
  form.tanggal_kirim = po.supply_date ?? ''
  form.tipe_bayar = po.tipe_bayar ?? ''
  form.termin_hari = po.termin_hari ?? ''
  rows.value = rowsFromPoItems(po.items)

  if (po.lampiran_poc) {
    existingAttachments.value = [{ name: po.lampiran_poc_ori ?? po.lampiran_poc, url: `/storage/${po.lampiran_poc}` }]
  }
}

function ensureCurrentQuotationOption(): void {
  const selectedQuotationId = String(form.quotation_id || '')
  if (selectedQuotationId && !quotationOptions.value.some(opt => String(opt.id) === selectedQuotationId)) {
    quotationOptions.value.push({
      id: Number(form.quotation_id),
      nomor_penawaran: form.nomor_penawaran,
      customer_nama: form.customer_nama,
    })
  }
}

async function fetchQuotationOptions(): Promise<void> {
  try {
    const { data } = await axios.get<ApiPaginatedResponse<QuotationIndexRow>>('/api/quotations', {
      params: {
        status: QUOTATION_STATUS.Approved,
        per_page: 200,
        valid_today: 1,
        without_po_customer: 1,
        ...(isEditMode.value && poRecord.value ? { customer_id: poRecord.value.id_customer } : {}),
        ...(isEditMode.value && poRecord.value?.quotation?.brand ? { brand: poRecord.value.quotation.brand } : {}),
      },
    })
    quotationOptions.value = (data.data ?? []).map(row => ({
      id: row.id,
      nomor_penawaran: row.nomor_penawaran ?? '-',
      customer_nama: row.customer?.company_name || '-',
    }))
  } catch {
    notifyError('Gagal', 'Gagal memuat daftar penawaran')
  }
}

async function fetchQuotationDetail(id: string | number): Promise<void> {
  if (!id) return

  pageLoading.value = true

  try {
    const { data: body } = await axios.get<ApiResponse<QuotationDetailResponse>>(`/api/quotations/${id}`)
    const data = body.data

    form.customer_id = data.customer_id
    form.customer_nama = data.customer?.company_name || '-'
    form.quotation_id = data.id
    form.nomor_penawaran = data.nomor_penawaran || '-'
    form.masa_berlaku = data.masa_berlaku
    form.sampai_dengan = data.sampai_dengan
    ppnRate.value = Number(data.ppn_rate ?? DEFAULT_PPN_RATE)

    if (!isEditMode.value) {
      const { tipeBayar, terminHari } = deriveDefaultPayment(data.tipe_pembayaran, data.repayment_hari)
      form.tipe_bayar = tipeBayar
      form.termin_hari = terminHari ?? ''
    }

    rows.value = rowsFromQuotationItems(Array.isArray(data.items) ? data.items : [])
    serverErrors.value = {}
    rowsTouched.value = false
  } catch {
    notifyError('Gagal', 'Gagal memuat data penawaran')
  } finally {
    pageLoading.value = false
  }
}

function onQuotationSelected(value: string | string[]): void {
  const id = Array.isArray(value) ? value[0] : value
  if (!id || String(id) === String(form.quotation_id)) return

  unavailableQuotationAlert.value = false
  serverErrors.value = {}

  if (isEditMode.value && poRecord.value && String(id) === String(poRecord.value.quotation_id)) {
    applyQuotationHeaderFromPo(poRecord.value)
    ppnRate.value = Number(poRecord.value.totals.ppn_rate)
    rows.value = rowsFromPoItems(poRecord.value.items)
    rowsTouched.value = false
    return
  }

  form.quotation_id = id
  fetchQuotationDetail(id)
}

function buildPayload(): FormData {
  const payload = new FormData()

  if (isEditMode.value) {
    payload.append('_method', 'PUT')
    if (String(form.quotation_id) !== String(poRecord.value?.quotation_id)) {
      payload.append('quotation_id', String(form.quotation_id))
    }
  } else {
    payload.append('id_customer', String(form.customer_id))
    payload.append('quotation_id', String(form.quotation_id))
  }

  payload.append('nomor_poc', form.nomor_po)
  payload.append('tipe_bayar', form.tipe_bayar)
  if (form.tipe_bayar === 'CREDIT') {
    payload.append('termin_hari', String(form.termin_hari))
  }
  payload.append('tanggal_poc', form.tanggal_po)
  payload.append('supply_date', form.tanggal_kirim)

  if (form.lampiran) {
    payload.append('lampiran_poc', form.lampiran)
  }

  rows.value.forEach((row, index) => {
    payload.append(`items[${index}][quotation_item_id]`, String(row.quotationItemId))
    payload.append(`items[${index}][volume]`, (row.checked ? row.volume : 0).toFixed(2))
  })

  return payload
}

async function reloadAfterConflict(): Promise<void> {
  if (isEditMode.value) {
    const ok = await loadPoCustomer()
    if (!ok) return
    applyPoCustomerToForm()
    await fetchQuotationOptions()
    ensureCurrentQuotationOption()
    return
  }

  await fetchQuotationOptions()
  const stillAvailable = quotationOptions.value.some(opt => String(opt.id) === String(form.quotation_id))
  if (!stillAvailable) {
    form.quotation_id = ''
    rows.value = []
    unavailableQuotationAlert.value = true
  }
}

async function submit(): Promise<void> {
  formError.value = ''
  serverErrors.value = {}
  rowsTouched.value = true

  if (isQuotationExpiredToday.value) return

  const valid = await v$.value.$validate()
  const rowsValid = checkedRows.value.length > 0 && rowErrors.value.every(message => !message)
  if (!valid || !rowsValid) return

  submitting.value = true

  try {
    const payload = buildPayload()
    const headers = { 'Content-Type': 'multipart/form-data' }

    if (isEditMode.value) {
      const { data } = await axios.post<ApiResponse<PoCustomerDetail>>(`/api/customer-pos/${idPoc}`, payload, { headers })
      success('Berhasil', data.message || 'PO Customer berhasil diperbarui')
      router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    } else {
      const { data } = await axios.post<ApiResponse<PoCustomerDetail>>('/api/customer-pos', payload, { headers })
      success('Berhasil', data.message || 'PO Customer berhasil dibuat')
      router.push({ name: 'po-customers-detail', params: { id: data.data.id_poc } })
    }
  } catch (e: any) {
    if (e.response?.status === 422) {
      applyServerErrors(e.response?.data?.errors || {})
    } else if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Data sudah berubah.')
      await reloadAfterConflict()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Terjadi kesalahan saat menyimpan PO'))
    }
  } finally {
    submitting.value = false
  }
}

function goBack(): void {
  if (isEditMode.value) {
    router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    return
  }
  router.push({ name: 'quotations.index' })
}

function fieldError(field: 'quotation_id' | 'nomor_po' | 'tipe_bayar' | 'termin_hari' | 'tanggal_po' | 'tanggal_kirim'): string {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function serverError(key: string): string {
  return serverErrors.value[key] ?? ''
}

function priceBreakdown(unitPrice: number, transportPrice: number): string {
  return `Material/m³: ${formatCurrency(unitPrice)}<br>Transport/m³: ${formatCurrency(transportPrice)}`
}

function subtotalBreakdown(materialSubtotal: number, transportTotal: number): string {
  return `Subtotal Material: ${formatCurrency(materialSubtotal)}<br>Total Transport: ${formatCurrency(transportTotal)}`
}

function round2(n: number): number {
  return Math.round((n + Number.EPSILON) * 100) / 100
}

function captionOf(...parts: Array<string | null | undefined>): string | null {
  const text = parts.filter(Boolean).join(' ')
  return text || null
}

function rowLineTotal(row: PoFormRow): number {
  return row.checked ? round2(row.volume * row.finalPrice) : 0
}

function deriveDefaultPayment(tipePembayaran: string | null | undefined, repaymentHari: unknown): { tipeBayar: string; terminHari: number | null } {
  const val = String(tipePembayaran ?? '').trim().toUpperCase()
  if (val === 'CBD') return { tipeBayar: 'CBD', terminHari: null }
  if (val === 'COD') return { tipeBayar: 'COD', terminHari: null }
  const topMatch = val.match(/^TOP\s*(\d+)$/)
  if (topMatch) return { tipeBayar: 'CREDIT', terminHari: Number(topMatch[1]) }
  if (val === 'CUSTOM') return { tipeBayar: 'CREDIT', terminHari: Number(repaymentHari) || null }
  return { tipeBayar: 'CREDIT', terminHari: null }
}
</script>

<template>
  <FormPage :title="pageTitle"
    :description="pageDescription" size="full" layout="sidebar"
    surface="plain" :show-footer="false" :loading="pageLoading || submitting" :error="formError" @submit="submit">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <CardSection title="Sumber Penawaran" description="Data penawaran yang menjadi dasar PO Customer." icon="FileText">
      <div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
        <div>
          <div class="text-form-label">Nomor Penawaran</div>
          <TomSelect :model-value="String(form.quotation_id)" class="w-full"
            :options="{ placeholder: 'Pilih penawaran...', dropdownParent: 'body' }"
            @update:modelValue="onQuotationSelected">
            <option v-for="opt in quotationOptions" :key="opt.id" :value="String(opt.id)">
              {{ opt.nomor_penawaran }} — {{ opt.customer_nama }}
            </option>
          </TomSelect>
          <small v-if="isEditMode" class="block text-caption text-slate-500">Hanya penawaran customer ini yang berlaku hari ini.</small>
          <small v-if="fieldError('quotation_id')" class="text-caption !text-rose-600">{{ fieldError('quotation_id') }}</small>
          <small v-if="serverError('quotation_id') || serverError('id_customer')" class="block input-error-text">{{
            serverError('quotation_id') || serverError('id_customer') }}</small>
          <Alert v-if="unavailableQuotationAlert" variant="soft-warning" class="flex items-start gap-2 mt-2">
            <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>Penawaran ini sudah memiliki PO Customer atau tidak tersedia.</span>
          </Alert>
        </div>
        <div>
          <div class="text-form-label">Masa Berlaku Harga</div>
          <FormInput type="text" :value="`${formatDate(form.masa_berlaku)} – ${formatDate(form.sampai_dengan)}`"
            readonly class="w-full !bg-white dark:!bg-darkmode-800" />
        </div>
      </div>
    </CardSection>

    <CardSection title="Item PO" description="Centang item yang diambil customer dan isi volumenya." icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <div class="overflow-x-auto">
        <Table bordered sm class="text-body">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="px-4 py-3 text-form-label text-center">Ambil</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-left">Produk</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Qty Penawaran</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Harga Final/m³</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Volume</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Subtotal</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody class="bg-white">
            <template v-for="(row, i) in rows" :key="row.quotationItemId">
              <Table.Tr :class="row.checked ? '' : 'opacity-60'">
                <Table.Td class="px-4 py-3 text-center">
                  <FormCheck class="justify-center">
                    <FormCheck.Input :id="'take-' + row.quotationItemId" type="checkbox" :checked="row.checked"
                      @change="toggleRow(row, ($event.target as HTMLInputElement).checked)" />
                  </FormCheck>
                </Table.Td>
                <Table.Td class="px-4 py-3">
                  <span v-if="row.sourceBranch" class="inline-block bg-slate-100 mb-1 px-2 rounded-full text-caption">Source: {{ row.sourceBranch }}</span>
                  <div class="text-body-strong">{{ row.productLabel }}</div>
                  <div v-if="row.productCaption" class="text-caption">{{ row.productCaption }}</div>
                  <div v-if="row.overOrderLegacy" class="text-caption !text-amber-700">Melebihi kuantitas penawaran (data lama)</div>
                </Table.Td>
                <Table.Td class="px-4 py-3 num-sm text-right">{{ formatNumber(row.quantityQuoted) }}</Table.Td>
                <Table.Td class="px-4 py-3 num-sm text-right whitespace-nowrap">
                  <Tippy :content="priceBreakdown(row.unitPrice, row.transportPrice)" :options="{ allowHTML: true }" as="span"
                    class="inline-flex items-center gap-1 border-slate-400 border-b border-dotted !cursor-help">
                    {{ formatCurrency(row.finalPrice) }}
                  </Tippy>
                </Table.Td>
                <Table.Td class="px-4 py-3 text-right whitespace-nowrap">
                  <NumberField v-model.number="row.volume" class="ml-auto w-36 max-w-[9rem]" suffix="m³" :decimals="2"
                    :min="0" :max="row.maxVolume" :disabled="!row.checked" :error="rowVolumeError(i)" />
                </Table.Td>
                <Table.Td class="px-4 py-3 num-sm text-right whitespace-nowrap">{{ formatCurrency(rowLineTotal(row)) }}</Table.Td>
              </Table.Tr>
              <Table.Tr v-if="serverError(`items.${i}.quotation_item_id`)">
                <Table.Td colspan="6" class="px-4 py-2">
                  <small class="input-error-text">{{ serverError(`items.${i}.quotation_item_id`) }}</small>
                </Table.Td>
              </Table.Tr>
            </template>

            <Table.Tr v-if="!rows.length">
              <Table.Td colspan="6" class="px-4 py-6 text-body text-center">Pilih penawaran untuk menampilkan item</Table.Td>
            </Table.Tr>
          </Table.Tbody>

          <Table.Tbody class="bg-slate-50">
            <Table.Tr>
              <Table.Td colspan="4" class="px-4 py-2 text-body-strong text-right">Total</Table.Td>
              <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">{{ formatNumber(previewTotals.volume) }} m³</Table.Td>
              <Table.Td class="px-4 py-2"></Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="5" class="px-4 py-2 text-body-strong text-right">Subtotal (DPP)</Table.Td>
              <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">
                <Tippy :content="subtotalBreakdown(previewTotals.material_subtotal, previewTotals.transport_total)" :options="{ allowHTML: true }" as="span"
                  class="inline-flex items-center gap-1 border-slate-400 border-b border-dotted !cursor-help">
                  {{ formatCurrency(previewTotals.dpp) }}
                </Tippy>
              </Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="5" class="px-4 py-2 text-body-strong text-right">PPN ({{ formatPercentRate(ppnRate) }})</Table.Td>
              <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">{{ formatCurrency(previewTotals.ppn) }}</Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="5" class="px-4 py-3 text-section-title text-right">Grand Total</Table.Td>
              <Table.Td class="px-4 py-3 num-lg text-right whitespace-nowrap !text-emerald-700">{{ formatCurrency(previewTotals.grand_total) }}</Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
      <small v-if="itemsError" class="block mt-2 input-error-text">{{ itemsError }}</small>
      <p class="mt-2 text-caption">Pratinjau. Angka final dihitung server saat disimpan.</p>
    </CardSection>

    <template #sidebar>
      <CardSection title="Detail PO Customer" description="Lengkapi data wajib untuk memproses PO." icon="ClipboardList"
        icon-class="bg-amber-100 text-amber-600">
        <template v-if="form.quotation_id" #action>
          <span class="bg-rose-50 px-2.5 py-1 rounded-full text-caption !text-rose-600">Wajib</span>
        </template>

        <div v-if="!form.quotation_id" class="flex flex-col items-center justify-center gap-2 py-10 text-center">
          <Lucide icon="FileSearch" class="h-8 w-8 text-slate-300" />
          <p class="text-body text-slate-500">Pilih Penawaran terlebih dahulu untuk mengisi detail PO Customer.</p>
        </div>

        <div v-else class="space-y-4">
          <div>
            <FormLabel for="nomor_po">Nomor PO Customer
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="nomor_po" v-model="form.nomor_po" placeholder="mis. PO/2026/001"
              :class="v$.nomor_po.$error || serverError('nomor_poc') ? 'border-rose-500' : ''" />
            <small v-if="v$.nomor_po.$error" class="text-caption !text-rose-600">{{ fieldError('nomor_po') }}</small>
            <small v-else-if="serverError('nomor_poc')" class="text-caption !text-rose-600">{{ serverError('nomor_poc') }}</small>
          </div>

          <div class="gap-4 grid grid-cols-2">
            <div>
              <FormLabel for="tipe_bayar">Tipe Pembayaran
                <RequiredAsterisk />
              </FormLabel>
              <FormSelect id="tipe_bayar" v-model="form.tipe_bayar" class="w-full"
                :class="v$.tipe_bayar.$error || serverError('tipe_bayar') ? 'border-rose-500' : ''">
                <option value="" disabled>Pilih…</option>
                <option value="CBD">CBD</option>
                <option value="COD">COD</option>
                <option value="CREDIT">Credit</option>
              </FormSelect>
              <small v-if="v$.tipe_bayar.$error" class="text-caption !text-rose-600">{{ fieldError('tipe_bayar') }}</small>
              <small v-else-if="serverError('tipe_bayar')" class="text-caption !text-rose-600">{{ serverError('tipe_bayar') }}</small>
            </div>

            <div v-if="form.tipe_bayar === 'CREDIT'">
              <FormLabel for="termin_hari">Termin (Hari)
                <RequiredAsterisk />
              </FormLabel>
              <NumberField id="termin_hari" v-model.number="form.termin_hari" suffix="hari" :decimals="0"
                :error="fieldError('termin_hari') || serverError('termin_hari')" />
            </div>
          </div>

          <div class="gap-4 grid grid-cols-2">
            <DateField v-model="form.tanggal_po" label="Tanggal PO" required placeholder="Pilih tanggal PO"
              :error="fieldError('tanggal_po') || serverError('tanggal_poc')" />
            <DateField v-model="form.tanggal_kirim" label="Tanggal Pengiriman" required
              placeholder="Pilih tanggal pengiriman" :error="fieldError('tanggal_kirim') || serverError('supply_date')" />
          </div>

          <div>
            <FileUploadField v-model="form.lampiran" :existing-files="existingAttachments"
              label="Lampiran Dokumen PO" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="2"
              :hint="isEditMode ? 'Upload file baru untuk mengganti.' : 'Opsional. PDF, JPG, atau PNG maksimal 2MB.'" />
            <small v-if="serverError('lampiran_poc')" class="block input-error-text">{{ serverError('lampiran_poc') }}</small>
          </div>
        </div>

        <Alert v-if="isQuotationExpiredToday" variant="soft-warning" class="flex items-start gap-2 mt-4">
          <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
          <span>Penawaran ini tidak berlaku hari ini (masa berlaku {{ validityRangeLabel }}). PO tidak dapat dibuat.</span>
        </Alert>

        <div v-if="isPoBlocked" class="flex items-start gap-2 bg-amber-50 mt-4 px-4 py-3 border border-amber-200 rounded-lg text-body text-amber-800">
          <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
          <span>PO ini di-block oleh gerbang kredit. Setelah disimpan, PO kembali ke status belum diproses dan perlu Proses SC ulang.</span>
        </div>

        <div class="mt-4 pt-4 border-slate-100 border-t">
          <Button type="submit" variant="primary" class="inline-flex items-center justify-center gap-2 w-full"
            :disabled="pageLoading || submitting || isQuotationExpiredToday || !form.quotation_id">
            <Lucide v-if="pageLoading || submitting" icon="Loader2" class="h-4 w-4 animate-spin" />
            <Lucide v-else icon="Save" class="h-4 w-4" />
            Simpan
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
