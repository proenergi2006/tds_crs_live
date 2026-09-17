<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import axios from 'axios'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import RadioCard from '@/components/SystemDesign/Form/RadioCard.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import TomSelect from '@/components/Base/TomSelect'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

type MoneyField =
  | 'price_list'
  | 'price_list_pe'
  | 'bm_price'
  | 'cogs_material_price'
  | 'cogs_transport_price'
  | 'margin_amount'
  | 'om_price'
  | 'ceo_price'

type EditableProductPrice = {
  id: number
  price_period_id: number
  branch_id: number | string
  product_id: number | string
  price_list: string | number | null
  price_list_pe: string | number | null
  bm_price: string | number | null
  cogs_material_price: string | number | null
  cogs_transport_price: string | number | null
  margin_amount: string | number | null
  om_price: string | number | null
  ceo_price: string | number | null
  cogs_basis: string | null
  notes: string | null
}

const props = defineProps<{
  open: boolean
  mode: 'create' | 'edit'
  productPrice?: EditableProductPrice | null
  pricePeriodId: number | null
  periodLabel?: string
  cabangs: any[]
  produks: any[]
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const loading = ref(false)

const title = computed(() => (props.mode === 'edit' ? 'Edit Baris Harga Produk' : 'Tambah Baris Harga Produk'))
const submitText = computed(() => (props.mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Baris'))

const canSetCogs = computed(() => auth.can('product-price.manage'))
const canSetPriceList = computed(() => auth.can('product-price.verify'))

const form = reactive({
  branch_id: '',
  product_id: '',
  price_list: 0,
  price_list_pe: 0,
  bm_price: 0,
  cogs_material_price: 0,
  cogs_transport_price: 0,
  margin_amount: 0,
  om_price: 0,
  ceo_price: 0,
  cogs_basis: '',
  notes: '',
})

const errors = reactive<Record<string, string | undefined>>({})
const submitError = ref<string | null>(null)

function renderProdukRow(data: any, escape: (v: string) => string, wrapClass: string) {
  if (!data.value) {
    return `<div class="${wrapClass} text-slate-400">${escape(data.text)}</div>`
  }

  const ukuran = data.ukuran
    ? `${escape(data.ukuran)}${data.satuan ? ' ' + escape(data.satuan) : ''}`
    : ''
  const badge = ukuran
    ? `<span class="inline-block bg-slate-50 px-1.5 py-0.5 border border-slate-300 rounded-md font-num text-slate-600 text-xs break-words whitespace-normal">${ukuran}</span>`
    : ''

  return `<div class="flex flex-wrap items-start gap-x-2 gap-y-1 ${wrapClass}">
    <span class="font-strong">${escape(data.text)}</span>
    ${badge}
  </div>`
}

const produkSelectOptions = {
  dropdownParent: 'body' as const,
  render: {
    option: (data: any, escape: (v: string) => string) => renderProdukRow(data, escape, 'py-1.5 px-1 border-t border-slate-100'),
    item: (data: any, escape: (v: string) => string) => renderProdukRow(data, escape, ''),
  },
}

const visibleMoneyFields = computed<MoneyField[]>(() => {
  if (canSetCogs.value && !canSetPriceList.value) return ['cogs_material_price', 'cogs_transport_price']

  return [
    'cogs_material_price',
    'cogs_transport_price',
    'margin_amount',
    'price_list',
    'price_list_pe',
    'bm_price',
    'om_price',
    'ceo_price',
  ]
})

const procurementLocked = computed(() => !canSetCogs.value)

const showCogs = computed(() => visibleMoneyFields.value.includes('cogs_material_price'))
const showMargin = computed(() => visibleMoneyFields.value.includes('margin_amount'))
const showPriceList = computed(() =>
  visibleMoneyFields.value.includes('price_list') || visibleMoneyFields.value.includes('price_list_pe'),
)
const showApproval = computed(() =>
  (['bm_price', 'om_price', 'ceo_price'] as MoneyField[]).some(f => visibleMoneyFields.value.includes(f)),
)

watch(() => props.open, (isOpen) => {
  if (!isOpen) return

  if (props.mode === 'edit' && props.productPrice) {
    fillFromRow(props.productPrice)
  } else {
    resetForm()
  }
})

function fillFromRow(row: EditableProductPrice) {
  resetForm()
  form.branch_id = row.branch_id != null ? String(row.branch_id) : ''
  form.product_id = row.product_id != null ? String(row.product_id) : ''
  form.cogs_basis = row.cogs_basis ?? ''
  form.notes = row.notes ?? ''
  form.cogs_material_price = toIntMoney(row.cogs_material_price)
  form.cogs_transport_price = toIntMoney(row.cogs_transport_price)
  form.margin_amount = toIntMoney(row.margin_amount)
  form.price_list = toIntMoney(row.price_list)
  form.price_list_pe = toIntMoney(row.price_list_pe)
  form.bm_price = toIntMoney(row.bm_price)
  form.om_price = toIntMoney(row.om_price)
  form.ceo_price = toIntMoney(row.ceo_price)
}

function resetForm() {
  form.branch_id = ''
  form.product_id = ''
  form.price_list = 0
  form.price_list_pe = 0
  form.bm_price = 0
  form.cogs_material_price = 0
  form.cogs_transport_price = 0
  form.margin_amount = 0
  form.om_price = 0
  form.ceo_price = 0
  form.cogs_basis = ''
  form.notes = ''
  Object.keys(errors).forEach(k => (errors[k] = undefined))
  submitError.value = null
}

function isReadonly(field: MoneyField) {
  if (field === 'price_list') return true
  if (canSetCogs.value && !canSetPriceList.value) {
    return field !== 'cogs_material_price' && field !== 'cogs_transport_price'
  }
  if (canSetPriceList.value) return field === 'cogs_material_price' || field === 'cogs_transport_price'

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

function totalCogs(): number {
  return form.cogs_basis === 'franco'
    ? toIntMoney(form.cogs_material_price) + toIntMoney(form.cogs_transport_price)
    : toIntMoney(form.cogs_material_price)
}

function recomputePriceList() {
  const total = totalCogs() + toIntMoney(form.margin_amount)
  form.price_list = total
  form.price_list_pe = total
}

function updateMoney(field: MoneyField, value: number) {
  if (isReadonly(field)) return

  form[field] = toIntMoney(value)

  if (field === 'cogs_material_price' || field === 'cogs_transport_price' || field === 'margin_amount') {
    recomputePriceList()
  }
}

function handleCogsBasisChange() {
  if (form.cogs_basis === 'loco') {
    form.cogs_transport_price = 0
  }
  recomputePriceList()
}

function validate(): boolean {
  Object.keys(errors).forEach(k => (errors[k] = undefined))

  if (!form.branch_id) errors.branch_id = 'Cabang wajib dipilih'
  if (!form.product_id) errors.product_id = 'Produk wajib dipilih'
  if (!form.cogs_basis) errors.cogs_basis = 'Tipe Harga COGS wajib dipilih'

  if (showCogs.value && !isReadonly('cogs_material_price') && toIntMoney(form.cogs_material_price) <= 0) {
    errors.cogs_material_price = form.cogs_basis === 'franco' ? 'COGS Material wajib diisi' : 'COGS wajib diisi'
  }

  if (form.cogs_basis === 'franco' && !isReadonly('cogs_transport_price') && toIntMoney(form.cogs_transport_price) <= 0) {
    errors.cogs_transport_price = 'COGS Transport wajib diisi'
  }

  if (canSetPriceList.value) {
    if (toIntMoney(form.margin_amount) <= 0) errors.margin_amount = 'Margin wajib diisi'
    if (toIntMoney(form.price_list_pe) <= 0) errors.price_list_pe = 'Price List PE wajib diisi'
    if (toIntMoney(form.price_list) <= 0) errors.price_list = 'Price List TDS wajib diisi'
    if (toIntMoney(form.bm_price) <= 0) errors.bm_price = 'Approval BM wajib diisi'
    if (toIntMoney(form.om_price) <= 0) errors.om_price = 'Approval OM wajib diisi'
    if (toIntMoney(form.ceo_price) <= 0) errors.ceo_price = 'Approval CEO wajib diisi'
  }

  return !Object.values(errors).some(Boolean)
}

async function submit() {
  submitError.value = null

  if (!validate()) return

  const periodId = props.productPrice?.price_period_id ?? props.pricePeriodId
  if (!periodId) {
    submitError.value = 'Periode harga tidak ditemukan.'
    return
  }

  const payload: Record<string, any> = {
    price_period_id: periodId,
    branch_id: Number(form.branch_id),
    product_id: Number(form.product_id),
    cogs_basis: form.cogs_basis,
    notes: form.notes || null,
  }

  if (canSetCogs.value) {
    payload.cogs_material_price = toIntMoney(form.cogs_material_price)
    payload.cogs_transport_price = form.cogs_basis === 'franco' ? toIntMoney(form.cogs_transport_price) : null
  }

  if (canSetPriceList.value) {
    payload.price_list = toIntMoney(form.price_list)
    payload.price_list_pe = toIntMoney(form.price_list_pe)
    payload.margin_amount = toIntMoney(form.margin_amount)
    payload.bm_price = toIntMoney(form.bm_price)
    payload.om_price = toIntMoney(form.om_price)
    payload.ceo_price = toIntMoney(form.ceo_price)
  }

  loading.value = true
  try {
    if (props.mode === 'edit' && props.productPrice) {
      await axios.put(`/api/product-prices/${props.productPrice.id}`, payload)
    } else {
      await axios.post('/api/product-prices', payload)
    }

    success(
      'Berhasil',
      props.mode === 'edit'
        ? 'Baris harga produk berhasil diperbarui.'
        : 'Baris harga produk berhasil ditambahkan.',
    )
    emit('saved')
    emit('close')
  } catch (e: any) {
    const message = e.response?.data?.message ?? 'Gagal menyimpan baris harga produk.'
    submitError.value = message
    notifyError('Gagal', message)
  } finally {
    loading.value = false
  }
}

function handleClose() {
  if (loading.value) return
  emit('close')
}
</script>

<template>
  <FormModal :open="open" :title="title" :description="periodLabel ? `Periode: ${periodLabel}` : undefined"
    :loading="loading" :error="submitError" size="lg" :submit-text="submitText" submit-icon="Save" @close="handleClose"
    @submit="submit">
    <div class="space-y-5">
      <div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
        <div>
          <FormLabel class="block !mb-1 font-label">Cabang
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect v-model="form.branch_id" class="w-full" :disabled="procurementLocked"
            :class="errors.branch_id ? 'border-rose-500' : ''">
            <option value="">-- Pilih Cabang --</option>
            <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="String(cabang.id_cabang)">
              {{ cabang.nama_cabang }}
            </option>
          </TomSelect>
          <small v-if="errors.branch_id" class="font-caption !text-rose-600">{{ errors.branch_id }}</small>
        </div>

        <div>
          <FormLabel class="block !mb-1 font-label">Produk
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect v-model="form.product_id" class="w-full" :options="produkSelectOptions"
            :disabled="procurementLocked" :class="errors.product_id ? 'border-rose-500' : ''">
            <option value="">-- Pilih Produk --</option>
            <option v-for="produk in produks" :key="produk.id_produk" :value="String(produk.id_produk)"
              :data-ukuran="produk.ukuran?.nama_ukuran || ''" :data-satuan="produk.ukuran?.satuan?.nama_satuan || ''">
              {{ produk.nama_produk }}
            </option>
          </TomSelect>
          <small v-if="errors.product_id" class="font-caption !text-rose-600">{{ errors.product_id }}</small>
        </div>
      </div>

      <div v-if="showCogs" class="gap-4 grid grid-cols-3">
        <div class="col-span-2">
          <div class="gap-4 grid grid-cols-1" :class="{ 'sm:grid-cols-2': form.cogs_basis === 'franco' }">
            <div v-if="form.cogs_basis !== 'franco'">
              <FormLabel class="block !mb-1 font-label">
                Harga COGS
                <RequiredAsterisk v-if="!isReadonly('cogs_material_price')" />
              </FormLabel>
              <CurrencyField :model-value="form.cogs_material_price" placeholder="0"
                :readonly="isReadonly('cogs_material_price')" :error="errors.cogs_material_price"
                @update:model-value="updateMoney('cogs_material_price', $event)" />
            </div>

            <template v-else>
              <div>
                <FormLabel class="block !mb-1 font-label">
                  COGS Material
                  <RequiredAsterisk v-if="!isReadonly('cogs_material_price')" />
                </FormLabel>
                <CurrencyField :model-value="form.cogs_material_price" placeholder="0"
                  :readonly="isReadonly('cogs_material_price')" :error="errors.cogs_material_price"
                  @update:model-value="updateMoney('cogs_material_price', $event)" />
              </div>

              <div>
                <FormLabel class="block !mb-1 font-label">
                  COGS Transport
                  <RequiredAsterisk v-if="!isReadonly('cogs_transport_price')" />
                </FormLabel>
                <CurrencyField :model-value="form.cogs_transport_price" placeholder="0"
                  :readonly="isReadonly('cogs_transport_price')" :error="errors.cogs_transport_price"
                  @update:model-value="updateMoney('cogs_transport_price', $event)" />
              </div>
            </template>
          </div>
        </div>

        <div>
          <FormLabel class="block !mb-1 font-label">Tipe Harga COGS
            <RequiredAsterisk />
          </FormLabel>
          <div class="gap-3 grid grid-cols-2">
            <RadioCard v-model="form.cogs_basis" value="loco" title="Loco" :disabled="isReadonly('cogs_material_price')"
              @update:model-value="handleCogsBasisChange" />
            <RadioCard v-model="form.cogs_basis" value="franco" title="Franco"
              :disabled="isReadonly('cogs_material_price')" @update:model-value="handleCogsBasisChange" />
          </div>
          <small v-if="errors.cogs_basis" class="font-caption !text-rose-600">{{ errors.cogs_basis }}</small>
        </div>
      </div>

      <div>
        <FormLabel class="block !mb-1 font-label">Catatan</FormLabel>
        <FormTextarea v-model="form.notes" rows="2" placeholder="Catatan (opsional)" :disabled="procurementLocked" />
      </div>

      <div v-if="showMargin || showPriceList" class="gap-4 grid grid-cols-1 sm:grid-cols-3">
        <div v-if="showMargin">
          <FormLabel class="block !mb-1 font-label">Margin
            <RequiredAsterisk v-if="canSetPriceList" />
          </FormLabel>
          <CurrencyField :model-value="form.margin_amount" placeholder="0" :readonly="isReadonly('margin_amount')"
            :error="errors.margin_amount" @update:model-value="updateMoney('margin_amount', $event)" />
        </div>

        <div v-if="visibleMoneyFields.includes('price_list')">
          <FormLabel class="block !mb-1 font-label">Price List TDS</FormLabel>
          <CurrencyField :model-value="form.price_list" placeholder="0" readonly />
        </div>

        <div v-if="visibleMoneyFields.includes('price_list_pe')">
          <FormLabel class="block !mb-1 font-label">
            Price List PE
            <RequiredAsterisk v-if="canSetPriceList" />
          </FormLabel>
          <CurrencyField :model-value="form.price_list_pe" placeholder="0" :readonly="isReadonly('price_list_pe')"
            :error="errors.price_list_pe" @update:model-value="updateMoney('price_list_pe', $event)" />
        </div>
      </div>

      <div v-if="showApproval" class="gap-4 grid grid-cols-1 sm:grid-cols-3">
        <div>
          <FormLabel class="block !mb-1 font-label">Approval BM
            <RequiredAsterisk v-if="canSetPriceList" />
          </FormLabel>
          <CurrencyField :model-value="form.bm_price" placeholder="0" :readonly="isReadonly('bm_price')"
            :error="errors.bm_price" @update:model-value="updateMoney('bm_price', $event)" />
        </div>

        <div>
          <FormLabel class="block !mb-1 font-label">Approval OM
            <RequiredAsterisk v-if="canSetPriceList" />
          </FormLabel>
          <CurrencyField :model-value="form.om_price" placeholder="0" :readonly="isReadonly('om_price')"
            :error="errors.om_price" @update:model-value="updateMoney('om_price', $event)" />
        </div>

        <div>
          <FormLabel class="block !mb-1 font-label">Approval CEO
            <RequiredAsterisk v-if="canSetPriceList" />
          </FormLabel>
          <CurrencyField :model-value="form.ceo_price" placeholder="0" :readonly="isReadonly('ceo_price')"
            :error="errors.ceo_price" @update:model-value="updateMoney('ceo_price', $event)" />
        </div>
      </div>
    </div>
  </FormModal>
</template>
