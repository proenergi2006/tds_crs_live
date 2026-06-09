<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import DateRangeInline from '@/components/SystemDesign/Form/DateRangeInline.vue'
import { FormSelect, FormTextarea } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'

type MoneyField =
  | 'harga_price_list'
  | 'harga_price_list_pe'
  | 'harga_bm'
  | 'harga_cogs'
  | 'harga_margin'
  | 'harga_om'
  | 'harga_ceo'

type PriceRow = {
  id_cabang: number | string
  id_produk: number | string
  harga_price_list: number
  harga_price_list_pe: number
  harga_bm: number
  harga_cogs: number
  harga_margin: number
  harga_om: number
  harga_ceo: number
  catatan: string
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()
const auth = useAuthStore()
const hargaProdukApi = createResourceApi('/produk-hargas')
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
  periode_awal: '',
  periode_akhir: '',
})

const periodRange = computed({
  get() {
    if (!period.periode_awal && !period.periode_akhir) return ''

    return `${period.periode_awal || ''} - ${period.periode_akhir || ''}`
  },
  set(value: string) {
    const [start = '', end = ''] = value.split(' - ')

    period.periode_awal = start
    period.periode_akhir = end
  },
})

const rows = ref<PriceRow[]>([makeEmptyRow()])

const currentUser = computed(() => auth.user)
const currentUserName = computed(() => currentUser.value?.name || '')
const isRole2 = computed(() => Number(currentUser.value?.id_role) === 2)
const isRole5 = computed(() => Number(currentUser.value?.id_role) === 5)
const isRole8 = computed(() => Number(currentUser.value?.id_role) === 8)
const isRole10 = computed(() => Number(currentUser.value?.id_role) === 10)

const pageTitle = computed(() => mode.value === 'create' ? 'Tambah Harga Produk' : 'Edit Harga Produk')
const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Tentukan periode harga, lalu tambahkan daftar produk beserta harga referensinya.'
    : 'Perbarui referensi harga produk untuk periode yang dipilih.',
)
const submitText = computed(() => mode.value === 'create' ? 'Simpan Harga' : 'Simpan Perubahan')
const canAddRows = computed(() => mode.value === 'create')

const visibleMoneyFields = computed<MoneyField[]>(() => {
  if (isRole5.value) return ['harga_cogs']
  if (isRole8.value) return ['harga_cogs', 'harga_bm']
  if (isRole10.value) return ['harga_cogs', 'harga_om']

  return [
    'harga_cogs',
    'harga_margin',
    'harga_price_list',
    'harga_price_list_pe',
    'harga_bm',
    'harga_om',
    'harga_ceo',
  ]
})

const showRowNumber = computed(() => mode.value === 'create')
const showCogsColumn = computed(() => visibleMoneyFields.value.includes('harga_cogs'))
const showMarginColumn = computed(() => visibleMoneyFields.value.includes('harga_margin'))
const showPriceListColumn = computed(() =>
  visibleMoneyFields.value.includes('harga_price_list_pe')
  || visibleMoneyFields.value.includes('harga_price_list'),
)
const showApprovalColumn = computed(() =>
  visibleMoneyFields.value.includes('harga_bm')
  || visibleMoneyFields.value.includes('harga_om')
  || visibleMoneyFields.value.includes('harga_ceo'),
)

const rules = computed(() => ({
  period: {
    periode_awal: {
      required: helpers.withMessage('Periode Harga wajib diisi', required),
    },
    periode_akhir: {
      required: helpers.withMessage('Periode Harga wajib diisi', required),
      afterStartDate: helpers.withMessage(
        'Tanggal akhir tidak boleh lebih awal dari tanggal awal',
        (value: string) => {
          if (!helpers.req(value) || !helpers.req(period.periode_awal)) return true

          return new Date(value) >= new Date(period.periode_awal)
        },
      ),
    },
  },
  rows: {
    $each: helpers.forEach({
      id_cabang: {
        required: helpers.withMessage('Cabang wajib dipilih', required),
      },
      id_produk: {
        required: helpers.withMessage('Produk wajib dipilih', required),
      },
      harga_cogs: {
        required: helpers.withMessage(
          'COGS wajib diisi',
          (value: number) => isReadonly('harga_cogs') || toIntMoney(value) > 0,
        ),
      },
      harga_price_list_pe: {
        required: helpers.withMessage(
          'Price List PE wajib diisi',
          (value: number) => !isRole2.value || toIntMoney(value) > 0,
        ),
      },
      harga_price_list: {
        required: helpers.withMessage(
          'Price List TDS wajib diisi',
          (value: number) => !isRole2.value || toIntMoney(value) > 0,
        ),
      },
      harga_bm: {
        required: helpers.withMessage(
          'Approval BM wajib diisi',
          (value: number) => !isRole2.value || toIntMoney(value) > 0,
        ),
      },
      harga_om: {
        required: helpers.withMessage(
          'Approval OM wajib diisi',
          (value: number) => !isRole2.value || toIntMoney(value) > 0,
        ),
      },
      harga_ceo: {
        required: helpers.withMessage(
          'Approval CEO wajib diisi',
          (value: number) => !isRole2.value || toIntMoney(value) > 0,
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

    period.periode_awal = data.periode_awal || ''
    period.periode_akhir = data.periode_akhir || ''
    rows.value = [rowFromData(data)]
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data harga')
    router.push({ name: 'produk-hargas' })
  }
}

function makeEmptyRow(): PriceRow {
  return {
    id_cabang: '',
    id_produk: '',
    harga_price_list: 0,
    harga_price_list_pe: 0,
    harga_bm: 0,
    harga_cogs: 0,
    harga_margin: 0,
    harga_om: 0,
    harga_ceo: 0,
    catatan: '',
  }
}

function rowFromData(data: any): PriceRow {
  const row = makeEmptyRow()

  Object.assign(row, {
    id_cabang: data.id_cabang ?? '',
    id_produk: data.id_produk ?? '',
    harga_price_list: toIntMoney(data.harga_price_list),
    harga_price_list_pe: toIntMoney(data.harga_price_list_pe),
    harga_bm: toIntMoney(data.harga_bm),
    harga_cogs: toIntMoney(data.harga_cogs),
    harga_margin: toIntMoney(data.harga_margin),
    harga_om: toIntMoney(data.harga_om),
    harga_ceo: toIntMoney(data.harga_ceo),
    catatan: data.catatan ?? '',
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
  if (isRole5.value && field !== 'harga_cogs') return true
  if (isRole8.value && field !== 'harga_bm') return true
  if (isRole10.value && field !== 'harga_om') return true
  if (isRole2.value && field === 'harga_cogs') return true

  return false
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

  if (field === 'harga_cogs' || field === 'harga_price_list') {
    updateMargin(row)
  }
}

function updateMargin(row: PriceRow) {
  row.harga_margin = Math.max(toIntMoney(row.harga_price_list) - toIntMoney(row.harga_cogs), 0)
}

function getPeriodFieldError(field: 'periode_awal' | 'periode_akhir') {
  return v$.value.period[field].$errors[0]?.$message?.toString() ?? ''
}

const periodRangeError = computed(() => {
  const startError = getPeriodFieldError('periode_awal')
  const endError = getPeriodFieldError('periode_akhir')

  if (startError === 'Periode Harga wajib diisi' || endError === 'Periode Harga wajib diisi') {
    return 'Periode Harga wajib diisi'
  }

  return startError || endError
})

function getRowFieldError(index: number, field: 'id_cabang' | 'id_produk' | MoneyField) {
  if (!validationSubmitted.value) return ''

  const errors = v$.value.rows.$each.$response.$errors[index]?.[field]

  return errors?.[0]?.$message?.toString() ?? ''
}

function buildPayload(row: PriceRow) {
  return {
    periode_awal: period.periode_awal,
    periode_akhir: period.periode_akhir,
    id_cabang: Number(row.id_cabang),
    id_produk: Number(row.id_produk),
    harga_price_list: toIntMoney(row.harga_price_list),
    harga_price_list_pe: toIntMoney(row.harga_price_list_pe),
    harga_bm: toIntMoney(row.harga_bm),
    harga_cogs: toIntMoney(row.harga_cogs),
    harga_margin: toIntMoney(row.harga_margin),
    harga_om: toIntMoney(row.harga_om),
    harga_ceo: toIntMoney(row.harga_ceo),
    catatan: row.catatan,
    ...(mode.value === 'create'
      ? { created_by: currentUserName.value }
      : { lastupdate_by: currentUserName.value }),
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
    if (mode.value === 'create') {
      await Promise.all(
        rows.value.map(row => hargaProdukApi.store(buildPayload(row))),
      )
    } else {
      await hargaProdukApi.update(hargaId.value, buildPayload(rows.value[0]))
    }

    success(
      'Berhasil',
      mode.value === 'create'
        ? 'Harga produk berhasil ditambahkan'
        : 'Harga produk berhasil diperbarui',
      mode.value === 'edit'
        ? {
            action: {
              label: 'Ke daftar',
              variant: 'primary',
              onClick: () => router.push({ name: 'produk-hargas' }),
            },
          }
        : undefined,
    )

    if (mode.value === 'create') {
      router.push({ name: 'produk-hargas' })
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
  router.push({ name: 'produk-hargas' })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="full" :loading="loading || pageLoading"
    :error="formError" :submit-text="submitText" submit-icon="Save" @cancel="cancel" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <template #header>
      <div class="grid grid-cols-12">
        <div class="col-span-4">
          <DateRangeInline v-model="periodRange" label="Periode Harga" required :error="periodRangeError"
            :auto-default="false" />
        </div>
      </div>
    </template>

    <div class="space-y-5">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-800">Daftar Harga Produk</h3>
          <p class="mt-1 text-sm text-slate-500">
            Semua baris akan memakai periode yang sama dari bagian atas.
          </p>
        </div>

        <div v-if="canAddRows" class="flex flex-col items-end">
          <Button type="button" variant="outline-primary" class="inline-flex items-center gap-2" @click="addRow">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Baris
          </Button>
          <div class="mt-1 text-sm text-slate-500">
            Tambahkan baris baru untuk input harga produk lain.
          </div>
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th v-if="showRowNumber"
                class="w-14 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                No
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                Cabang
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                Produk
              </th>
              <th v-if="showCogsColumn"
                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                Harga COGS
                <RequiredAsterisk v-if="!isReadonly('harga_cogs')" />
              </th>
              <th v-if="showMarginColumn"
                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                Margin
              </th>
              <th v-if="showPriceListColumn"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                Price List
              </th>
              <th v-if="showApprovalColumn"
                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                Harga Approval
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                Catatan
              </th>
              <th v-if="canAddRows"
                class="w-20 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">
                Aksi
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(row, index) in rows" :key="index" class="transition hover:bg-slate-50">
              <td v-if="showRowNumber" class="px-4 py-3 align-top text-sm font-medium text-slate-700">
                {{ index + 1 }}.
              </td>

              <td class="px-4 py-3 align-top">
                <FormSelect :id="`cabang-${index}`" v-model="row.id_cabang" class="min-w-[180px]"
                  :class="getRowFieldError(index, 'id_cabang') ? 'border-rose-500' : ''">
                  <option disabled value="">-- Pilih Cabang --</option>
                  <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
                    {{ cabang.nama_cabang }}
                  </option>
                </FormSelect>
                <small v-if="getRowFieldError(index, 'id_cabang')" class="text-rose-600">
                  {{ getRowFieldError(index, 'id_cabang') }}
                </small>
              </td>

              <td class="px-4 py-3 align-top">
                <FormSelect :id="`produk-${index}`" v-model="row.id_produk" class="min-w-[280px]"
                  :class="getRowFieldError(index, 'id_produk') ? 'border-rose-500' : ''">
                  <option disabled value="">-- Pilih Produk --</option>
                  <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
                    {{ produk.nama_produk }} ({{ produk.ukuran?.nama_ukuran }} {{ produk.ukuran?.satuan?.nama_satuan }})
                  </option>
                </FormSelect>
                <small v-if="getRowFieldError(index, 'id_produk')" class="text-rose-600">
                  {{ getRowFieldError(index, 'id_produk') }}
                </small>
              </td>

              <td v-if="showCogsColumn" class="px-4 py-3 align-top">
                <CurrencyField :model-value="row.harga_cogs" class="min-w-[150px]" placeholder="0"
                  :readonly="isReadonly('harga_cogs')" :error="getRowFieldError(index, 'harga_cogs')"
                  @update:model-value="updateMoney(row, 'harga_cogs', $event)" />
              </td>

              <td v-if="showMarginColumn" class="px-4 py-3 align-top">
                <CurrencyField :model-value="row.harga_margin" class="min-w-[150px]" readonly placeholder="0" />
              </td>

              <td v-if="showPriceListColumn" class="px-4 py-3 align-top">
                <div class="min-w-[190px] space-y-2">
                  <div v-if="visibleMoneyFields.includes('harga_price_list_pe')"
                    class="grid grid-cols-[42px_minmax(0,1fr)] items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">
                      PE
                      <RequiredAsterisk v-if="isRole2" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.harga_price_list_pe" placeholder="0"
                        :readonly="isReadonly('harga_price_list_pe')"
                        :error="getRowFieldError(index, 'harga_price_list_pe')"
                        @update:model-value="updateMoney(row, 'harga_price_list_pe', $event)" />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('harga_price_list')"
                    class="grid grid-cols-[42px_minmax(0,1fr)] items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">
                      TDS
                      <RequiredAsterisk v-if="isRole2" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.harga_price_list" placeholder="0"
                        :readonly="isReadonly('harga_price_list')"
                        :error="getRowFieldError(index, 'harga_price_list')"
                        @update:model-value="updateMoney(row, 'harga_price_list', $event)" />
                    </div>
                  </div>
                </div>
              </td>

              <td v-if="showApprovalColumn" class="px-4 py-3 align-top">
                <div class="min-w-[190px] space-y-2">
                  <div v-if="visibleMoneyFields.includes('harga_bm')"
                    class="grid grid-cols-[42px_minmax(0,1fr)] items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">
                      BM
                      <RequiredAsterisk v-if="isRole2" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.harga_bm" placeholder="0"
                        :readonly="isReadonly('harga_bm')" :error="getRowFieldError(index, 'harga_bm')"
                        @update:model-value="updateMoney(row, 'harga_bm', $event)" />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('harga_om')"
                    class="grid grid-cols-[42px_minmax(0,1fr)] items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">
                      OM
                      <RequiredAsterisk v-if="isRole2" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.harga_om" placeholder="0"
                        :readonly="isReadonly('harga_om')" :error="getRowFieldError(index, 'harga_om')"
                        @update:model-value="updateMoney(row, 'harga_om', $event)" />
                    </div>
                  </div>

                  <div v-if="visibleMoneyFields.includes('harga_ceo')"
                    class="grid grid-cols-[42px_minmax(0,1fr)] items-center gap-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">
                      CEO
                      <RequiredAsterisk v-if="isRole2" />
                    </span>
                    <div>
                      <CurrencyField :model-value="row.harga_ceo" placeholder="0"
                        :readonly="isReadonly('harga_ceo')" :error="getRowFieldError(index, 'harga_ceo')"
                        @update:model-value="updateMoney(row, 'harga_ceo', $event)" />
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-4 py-3 align-top">
                <FormTextarea :id="`catatan-${index}`" v-model="row.catatan" rows="1" auto-resize class="min-w-[220px]"
                  placeholder="Catatan" />
              </td>

              <td v-if="canAddRows" class="px-4 py-3 text-center align-top">
                <Button v-if="rows.length > 1" type="button" variant="soft-danger" rounded
                  class="!h-8 !w-8 !p-0 !shadow-none" title="Hapus" @click="removeRow(index)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
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
