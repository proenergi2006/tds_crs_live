<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import DateRangeInline from '@/components/SystemDesign/Form/DateRangeInline.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { FormCheck, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'

type MoneyField =
  | 'price_list'
  | 'price_list_pe'
  | 'bm_price'
  | 'cogs_price'
  | 'margin_amount'
  | 'om_price'
  | 'ceo_price'

type PriceRow = {
  id?: number
  branch_id: number | string
  product_id: number | string
  price_list: number
  price_list_pe: number
  bm_price: number
  cogs_price: number
  margin_amount: number
  om_price: number
  ceo_price: number
  cogs_basis: string
  notes: string
}

type PeriodAttachment = {
  path: string
  original_filename: string
  uploaded_at?: string
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()
const auth = useAuthStore()
const hargaProdukApi = createResourceApi('/product-prices')
const periodeApi = createResourceApi('/price-periods')
const cabangApi = createResourceApi('/cabangs')
const produkApi = createResourceApi('/produks')

const hargaId = computed(() => Number(route.params.id || 0))
const mode = computed<'create' | 'edit'>(() => hargaId.value ? 'edit' : 'create')

const cabangs = ref<any[]>([])
const produks = ref<any[]>([])

const loading = ref(false)
const pageLoading = ref(false)
const formError = ref<string | null>(null)
const validationSubmitted = ref(false)

const period = reactive({
  start_date: '',
  end_date: '',
  notes: '',
})

const periodId = ref<number | null>(null)
const periodAttachments = ref<PeriodAttachment[]>([])
const newAttachments = ref<File[]>([])
const removedAttachmentIndexes = ref<number[]>([])

const existingAttachmentsForUpload = computed(() =>
  periodAttachments.value
    .map((att, index) => ({ id: index, name: att.original_filename, url: `/storage/${att.path}` }))
    .filter(file => !removedAttachmentIndexes.value.includes(Number(file.id))),
)

const periodRange = computed({
  get() {
    if (!period.start_date && !period.end_date) return ''

    return `${period.start_date || ''} - ${period.end_date || ''}`
  },
  set(value: string) {
    const [start = '', end = ''] = value.split(' - ')

    period.start_date = start
    period.end_date = end
  },
})

const rows = ref<PriceRow[]>([makeEmptyRow()])

const canSetCogs = computed(() => auth.can('product-price.manage'))
const canSetPriceList = computed(() => auth.can('product-price.verify'))

const pageTitle = computed(() => mode.value === 'create' ? 'Tambah Harga Produk' : 'Edit Harga Produk')
const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Tentukan periode harga, lalu tambahkan daftar produk beserta harga referensinya.'
    : 'Perbarui referensi harga produk untuk periode yang dipilih.',
)
const submitText = computed(() => mode.value === 'create' ? 'Simpan Harga' : 'Simpan Perubahan')
const canAddRows = computed(() => mode.value === 'create')

const visibleMoneyFields = computed<MoneyField[]>(() => {
  if (canSetCogs.value && !canSetPriceList.value) return ['cogs_price']

  return [
    'cogs_price',
    'margin_amount',
    'price_list',
    'price_list_pe',
    'bm_price',
    'om_price',
    'ceo_price',
  ]
})

const showRowNumber = computed(() => mode.value === 'create')
const showCogsColumn = computed(() => visibleMoneyFields.value.includes('cogs_price'))
const showMarginColumn = computed(() => visibleMoneyFields.value.includes('margin_amount'))
const showPriceListColumn = computed(() =>
  visibleMoneyFields.value.includes('price_list_pe')
  || visibleMoneyFields.value.includes('price_list'),
)
const showApprovalColumn = computed(() =>
  visibleMoneyFields.value.includes('bm_price')
  || visibleMoneyFields.value.includes('om_price')
  || visibleMoneyFields.value.includes('ceo_price'),
)

const rules = computed(() => ({
  period: {
    start_date: {
      required: helpers.withMessage('Periode Harga wajib diisi', required),
    },
    end_date: {
      required: helpers.withMessage('Periode Harga wajib diisi', required),
      afterStartDate: helpers.withMessage(
        'Tanggal akhir tidak boleh lebih awal dari tanggal awal',
        (value: string) => {
          if (!helpers.req(value) || !helpers.req(period.start_date)) return true

          return new Date(value) >= new Date(period.start_date)
        },
      ),
    },
  },
  rows: {
    $each: helpers.forEach({
      branch_id: {
        required: helpers.withMessage('Cabang wajib dipilih', required),
      },
      product_id: {
        required: helpers.withMessage('Produk wajib dipilih', required),
      },
      cogs_price: {
        required: helpers.withMessage(
          'COGS wajib diisi',
          (value: number) => isReadonly('cogs_price') || toIntMoney(value) > 0,
        ),
      },
      cogs_basis: {
        required: helpers.withMessage(
          'Tipe Harga COGS wajib dipilih',
          (value: string) => isReadonly('cogs_price') || !!value,
        ),
      },
      margin_amount: {
        required: helpers.withMessage(
          'Margin wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
      price_list_pe: {
        required: helpers.withMessage(
          'Price List PE wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
      price_list: {
        required: helpers.withMessage(
          'Price List TDS wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
      bm_price: {
        required: helpers.withMessage(
          'Approval BM wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
      om_price: {
        required: helpers.withMessage(
          'Approval OM wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
      ceo_price: {
        required: helpers.withMessage(
          'Approval CEO wajib diisi',
          (value: number) => !canSetPriceList.value || toIntMoney(value) > 0,
        ),
      },
    }),
  },
}))

const v$ = useVuelidate(rules, { period, rows })

onMounted(async () => {
  await initForm()
})

async function initForm() {
  pageLoading.value = true

  try {
    await Promise.all([
      fetchDropdowns(),
    ])

    if (mode.value === 'edit') {
      await fetchHarga()
    }
  } finally {
    pageLoading.value = false
  }
}

async function fetchDropdowns() {
  try {
    const [cabangRes, produkRes] = await Promise.all([
      cabangApi.getAll({ as_list: true }),
      produkApi.getAll({ as_list: true }),
    ])

    cabangs.value = cabangRes.data.data || cabangRes.data || []
    produks.value = produkRes.data.data || produkRes.data || []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data pilihan')
  }
}

async function fetchHarga() {
  try {
    const { data } = await hargaProdukApi.getById(hargaId.value)
    const item = data.data ?? data

    period.start_date = item.price_period?.start_date || ''
    period.end_date = item.price_period?.end_date || ''
    periodId.value = item.price_period?.id ?? null
    rows.value = [rowFromData(item)]

    if (periodId.value) {
      const { data: periodData } = await periodeApi.getById(periodId.value)
      const periodItem = periodData.data ?? periodData

      period.notes = periodItem.notes || ''
      periodAttachments.value = periodItem.attachments || []
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data harga')
    router.push({ name: 'product-prices' })
  }
}

function makeEmptyRow(): PriceRow {
  return {
    branch_id: '',
    product_id: '',
    price_list: 0,
    price_list_pe: 0,
    bm_price: 0,
    cogs_price: 0,
    margin_amount: 0,
    om_price: 0,
    ceo_price: 0,
    cogs_basis: '',
    notes: '',
  }
}

function rowFromData(data: any): PriceRow {
  const row = makeEmptyRow()

  Object.assign(row, {
    id: data.id,
    branch_id: data.branch_id ?? '',
    product_id: data.product_id ?? '',
    price_list: toIntMoney(data.price_list),
    price_list_pe: toIntMoney(data.price_list_pe),
    bm_price: toIntMoney(data.bm_price),
    cogs_price: toIntMoney(data.cogs_price),
    margin_amount: toIntMoney(data.margin_amount),
    om_price: toIntMoney(data.om_price),
    ceo_price: toIntMoney(data.ceo_price),
    cogs_basis: data.cogs_basis ?? '',
    notes: data.notes ?? '',
  })

  return row
}

function addRow() {
  rows.value.push(makeEmptyRow())
  v$.value.$reset()
  validationSubmitted.value = false
}

function removeRow(index: number) {
  if (rows.value.length <= 1) return
  rows.value.splice(index, 1)
  v$.value.$reset()
}

function isReadonly(field: MoneyField) {
  if (field === 'price_list') return true
  if (canSetCogs.value && !canSetPriceList.value) return field !== 'cogs_price'
  if (canSetPriceList.value) return field === 'cogs_price'

  return true
}

function toIntMoney(value: unknown): number {
  if (value === null || value === undefined || value === '') return 0
  if (typeof value === 'number') return Math.trunc(value)

  const text = String(value).trim()

  if (/^\d+\.\d{1,2}$/.test(text)) {
    return Math.trunc(Number.parseFloat(text))
  }

  const normalized = text.split(',')[0].replace(/[^\d]/g, '')
  return normalized ? parseInt(normalized, 10) : 0
}

function updateMoney(row: PriceRow, field: MoneyField, value: number) {
  if (isReadonly(field)) return

  row[field] = toIntMoney(value)

  if (field === 'cogs_price' || field === 'margin_amount') {
    recalculatePriceList(row)
  }
}

function recalculatePriceList(row: PriceRow) {
  const total = toIntMoney(row.cogs_price) + toIntMoney(row.margin_amount)
  row.price_list = total
  row.price_list_pe = total
}

function getPeriodFieldError(field: 'start_date' | 'end_date') {
  return v$.value.period[field].$errors[0]?.$message?.toString() ?? ''
}

const periodRangeError = computed(() => {
  const startError = getPeriodFieldError('start_date')
  const endError = getPeriodFieldError('end_date')

  if (startError === 'Periode Harga wajib diisi' || endError === 'Periode Harga wajib diisi') {
    return 'Periode Harga wajib diisi'
  }

  return startError || endError
})

function getRowFieldError(index: number, field: 'branch_id' | 'product_id' | 'cogs_basis' | MoneyField) {
  if (!validationSubmitted.value) return ''

  const errors = v$.value.rows.$each.$response.$errors[index]?.[field]

  return errors?.[0]?.$message?.toString() ?? ''
}

function handleAttachmentsSelected(value: File | File[] | null) {
  newAttachments.value = Array.isArray(value) ? value : value ? [value] : []
}

function handleRemoveExistingAttachment(file: { id?: string | number }) {
  if (typeof file.id === 'number') {
    removedAttachmentIndexes.value.push(file.id)
  }
}

function appendRowToFormData(formData: FormData, row: PriceRow, index: number) {
  if (mode.value === 'edit' && row.id) {
    formData.append(`product_prices[${index}][id]`, String(row.id))
  }

  formData.append(`product_prices[${index}][branch_id]`, String(row.branch_id))
  formData.append(`product_prices[${index}][product_id]`, String(row.product_id))
  formData.append(`product_prices[${index}][cogs_basis]`, row.cogs_basis || '')
  formData.append(`product_prices[${index}][notes]`, row.notes ?? '')

  if (canSetCogs.value) {
    formData.append(`product_prices[${index}][cogs_price]`, String(toIntMoney(row.cogs_price)))
  }

  if (canSetPriceList.value) {
    formData.append(`product_prices[${index}][price_list]`, String(toIntMoney(row.price_list)))
    formData.append(`product_prices[${index}][price_list_pe]`, String(toIntMoney(row.price_list_pe)))
    formData.append(`product_prices[${index}][margin_amount]`, String(toIntMoney(row.margin_amount)))
    formData.append(`product_prices[${index}][bm_price]`, String(toIntMoney(row.bm_price)))
    formData.append(`product_prices[${index}][om_price]`, String(toIntMoney(row.om_price)))
    formData.append(`product_prices[${index}][ceo_price]`, String(toIntMoney(row.ceo_price)))
  }
}

async function submitForm() {
  formError.value = null
  validationSubmitted.value = true

  const isValid = await v$.value.$validate()

  if (!isValid) {
    notifyError('Gagal', 'Periksa kembali data yang Anda masukkan')
    return
  }

  loading.value = true

  try {
    const formData = new FormData()
    formData.append('price_period[start_date]', period.start_date)
    formData.append('price_period[end_date]', period.end_date)
    formData.append('price_period[notes]', period.notes ?? '')

    rows.value.forEach((row, index) => appendRowToFormData(formData, row, index))
    newAttachments.value.forEach(file => formData.append('attachments[]', file))
    removedAttachmentIndexes.value.forEach(index => formData.append('remove_attachments[]', String(index)))

    let reusedExistingPeriod = false

    if (mode.value === 'create') {
      const { data } = await axios.post('/api/price-periods', formData)
      reusedExistingPeriod = !!data.reused_existing_period
    } else {
      await axios.post(`/api/price-periods/${periodId.value}?_method=PUT`, formData)
    }

    success(
      'Berhasil',
      mode.value === 'create'
        ? (reusedExistingPeriod
          ? 'Baris harga ditambahkan ke periode yang sudah ada. Catatan/lampiran periode lama tidak diubah.'
          : 'Harga produk berhasil ditambahkan')
        : 'Harga produk berhasil diperbarui',
      mode.value === 'edit'
        ? {
          action: {
            label: 'Ke daftar',
            variant: 'primary',
            onClick: () => router.push({ name: 'product-prices' }),
          },
        }
        : undefined,
    )

    if (mode.value === 'create') {
      router.push({ name: 'product-prices' })
    }
  } catch (e: any) {
    const message = e.response?.data?.message ?? 'Gagal menyimpan data harga produk'
    formError.value = message
    notifyError('Gagal', message)
  } finally {
    loading.value = false
  }
}

function cancel() {
  if (loading.value) return
  router.push({ name: 'product-prices' })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="full" :loading="loading || pageLoading"
    :error="formError" :submit-text="submitText" submit-icon="Save" @cancel="cancel" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <template #header>
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-4">
          <FormLabel class="block !mb-1 font-label">Periode Harga</FormLabel>
          <DateRangeInline v-model="periodRange" :error="periodRangeError" :auto-default="false"
            :disabled="!canSetCogs" />
        </div>

        <div class="col-span-4">
          <FormLabel class="block !mb-1 font-label">Catatan Periode</FormLabel>
          <FormTextarea v-model="period.notes" rows="4" placeholder="Catatan untuk periode ini (opsional)" />
        </div>

        <div class="col-span-4">
          <FormLabel class="block !mb-1 font-label">Lampiran</FormLabel>
          <FileUploadField :model-value="newAttachments" multiple :existing-files="existingAttachmentsForUpload"
            accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="5" choose-text="Pilih lampiran" empty-text="Belum ada lampiran"
            @update:model-value="handleAttachmentsSelected" @remove-existing="handleRemoveExistingAttachment"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>
      </div>
    </template>

    <div class="space-y-5">
      <div class="flex sm:flex-row flex-col sm:justify-between sm:items-center gap-3">
        <div>
          <h3 class="font-header">Daftar Harga Produk</h3>
          <p class="mt-1 font-body">
            Semua baris akan memakai periode yang sama dari bagian atas.
          </p>
        </div>

        <div v-if="canAddRows" class="flex flex-col items-end">
          <Button type="button" variant="outline-primary" class="inline-flex items-center gap-2" @click="addRow">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Baris
          </Button>
          <div class="mt-1 font-body">
            Tambahkan baris baru untuk input harga produk lain.
          </div>
        </div>
      </div>

      <div class="border border-slate-200 rounded-lg overflow-x-auto">
        <table class="divide-y divide-slate-200 min-w-full">
          <thead class="bg-slate-50">
            <tr>
              <th v-if="showRowNumber" class="px-4 py-3 w-14 font-label text-left">
                No
              </th>
              <th class="px-4 py-3 font-label text-left">
                Cabang
              </th>
              <th class="px-4 py-3 font-label text-left">
                Produk
              </th>
              <th v-if="showCogsColumn" class="px-4 py-3 font-label text-right">
                Harga COGS
                <RequiredAsterisk v-if="!isReadonly('cogs_price')" />
              </th>
              <th v-if="showMarginColumn" class="px-4 py-3 font-label text-right">
                Margin
                <RequiredAsterisk v-if="!isReadonly('margin_amount')" />
              </th>
              <th v-if="showPriceListColumn" class="px-4 py-3 font-label text-left">
                Price List
              </th>
              <th v-if="showApprovalColumn" class="px-4 py-3 font-label text-left">
                Harga Approval
              </th>
              <th class="px-4 py-3 font-label text-left">
                Catatan
              </th>
              <th v-if="canAddRows" class="px-4 py-3 w-20 font-label text-center">
                Aksi
              </th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="(row, index) in rows" :key="index" class="hover:bg-slate-50 transition">
              <td v-if="showRowNumber" class="px-4 py-3 font-num align-top">
                {{ index + 1 }}.
              </td>

              <td class="px-4 py-3 align-top">
                <FormSelect :id="`cabang-${index}`" v-model="row.branch_id" class="min-w-[180px]"
                  :class="getRowFieldError(index, 'branch_id') ? 'border-rose-500' : ''" :disabled="!canSetCogs">
                  <option disabled value="">-- Pilih Cabang --</option>
                  <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
                    {{ cabang.nama_cabang }}
                  </option>
                </FormSelect>
                <small v-if="getRowFieldError(index, 'branch_id')" class="font-caption !text-rose-600">
                  {{ getRowFieldError(index, 'branch_id') }}
                </small>
              </td>

              <td class="px-4 py-3 align-top">
                <FormSelect :id="`produk-${index}`" v-model="row.product_id" class="min-w-[280px]"
                  :class="getRowFieldError(index, 'product_id') ? 'border-rose-500' : ''" :disabled="!canSetCogs">
                  <option disabled value="">-- Pilih Produk --</option>
                  <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
                    {{ produk.nama_produk }} ({{ produk.ukuran?.nama_ukuran }} {{ produk.ukuran?.satuan?.nama_satuan }})
                  </option>
                </FormSelect>
                <small v-if="getRowFieldError(index, 'product_id')" class="font-caption !text-rose-600">
                  {{ getRowFieldError(index, 'product_id') }}
                </small>
              </td>

              <td v-if="showCogsColumn" class="px-4 py-3 align-top">
                <div class="space-y-2 min-w-[150px]">
                  <CurrencyField :model-value="row.cogs_price" placeholder="0" :readonly="isReadonly('cogs_price')"
                    :error="getRowFieldError(index, 'cogs_price')"
                    @update:model-value="updateMoney(row, 'cogs_price', $event)" />
                  <div>
                    <div class="flex gap-3">
                      <FormCheck>
                        <FormCheck.Input :id="`cogs-basis-loco-${index}`" type="radio" value="loco"
                          v-model="row.cogs_basis" :disabled="isReadonly('cogs_price')" />
                        <FormCheck.Label :htmlFor="`cogs-basis-loco-${index}`">Loco</FormCheck.Label>
                      </FormCheck>
                      <FormCheck>
                        <FormCheck.Input :id="`cogs-basis-franco-${index}`" type="radio" value="franco"
                          v-model="row.cogs_basis" :disabled="isReadonly('cogs_price')" />
                        <FormCheck.Label :htmlFor="`cogs-basis-franco-${index}`">Franco</FormCheck.Label>
                      </FormCheck>
                    </div>
                    <small v-if="getRowFieldError(index, 'cogs_basis')" class="font-caption !text-rose-600">
                      {{ getRowFieldError(index, 'cogs_basis') }}
                    </small>
                  </div>
                </div>
              </td>

              <td v-if="showMarginColumn" class="px-4 py-3 align-top">
                <CurrencyField :model-value="row.margin_amount" class="min-w-[150px]" placeholder="0"
                  :readonly="isReadonly('margin_amount')" :error="getRowFieldError(index, 'margin_amount')"
                  @update:model-value="updateMoney(row, 'margin_amount', $event)" />
              </td>

              <td v-if="showPriceListColumn" class="px-4 py-3 align-top">
                <div class="space-y-2 min-w-[190px]">
                  <div v-if="visibleMoneyFields.includes('price_list')"
                    class="items-center gap-2 grid grid-cols-[42px_minmax(0,1fr)]">
                    <span class="font-section">TDS</span>
                    <div>
                      <CurrencyField :model-value="row.price_list" placeholder="0" readonly />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('price_list_pe')"
                    class="items-center gap-2 grid grid-cols-[42px_minmax(0,1fr)]">
                    <span class="font-section">
                      PE
                      <RequiredAsterisk v-if="canSetPriceList" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.price_list_pe" placeholder="0"
                        :readonly="isReadonly('price_list_pe')" :error="getRowFieldError(index, 'price_list_pe')"
                        @update:model-value="updateMoney(row, 'price_list_pe', $event)" />
                    </div>
                  </div>
                </div>
              </td>

              <td v-if="showApprovalColumn" class="px-4 py-3 align-top">
                <div class="space-y-2 min-w-[190px]">
                  <div v-if="visibleMoneyFields.includes('bm_price')"
                    class="items-center gap-2 grid grid-cols-[42px_minmax(0,1fr)]">
                    <span class="font-section">
                      BM
                      <RequiredAsterisk v-if="canSetPriceList" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.bm_price" placeholder="0" :readonly="isReadonly('bm_price')"
                        :error="getRowFieldError(index, 'bm_price')"
                        @update:model-value="updateMoney(row, 'bm_price', $event)" />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('om_price')"
                    class="items-center gap-2 grid grid-cols-[42px_minmax(0,1fr)]">
                    <span class="font-section">
                      OM
                      <RequiredAsterisk v-if="canSetPriceList" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.om_price" placeholder="0" :readonly="isReadonly('om_price')"
                        :error="getRowFieldError(index, 'om_price')"
                        @update:model-value="updateMoney(row, 'om_price', $event)" />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('ceo_price')"
                    class="items-center gap-2 grid grid-cols-[42px_minmax(0,1fr)]">
                    <span class="font-section">
                      CEO
                      <RequiredAsterisk v-if="canSetPriceList" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.ceo_price" placeholder="0" :readonly="isReadonly('ceo_price')"
                        :error="getRowFieldError(index, 'ceo_price')"
                        @update:model-value="updateMoney(row, 'ceo_price', $event)" />
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-4 py-3 align-top">
                <FormTextarea :id="`catatan-${index}`" v-model="row.notes" rows="1" auto-resize class="min-w-[220px]"
                  placeholder="Catatan" />
              </td>

              <td v-if="canAddRows" class="px-4 py-3 text-center align-top">
                <Button v-if="rows.length > 1" type="button" variant="soft-danger" rounded
                  class="!shadow-none !p-0 !w-8 !h-8" title="Hapus" @click="removeRow(index)">
                  <Lucide icon="Trash2" class="w-4 h-4" />
                </Button>
                <span v-else class="text-slate-300">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </FormPage>
</template>
