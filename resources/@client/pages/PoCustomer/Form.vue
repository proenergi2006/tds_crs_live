<script setup lang="ts">
import { computed, onMounted, reactive, ref, toRefs } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, integer, minValue, required, requiredIf } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import TippyContent from '@/components/Base/TippyContent'
import TomSelect from '@/components/Base/TomSelect'
import Alert from '@/components/Base/Alert'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { QUOTATION_STATUS } from '@/pages/Quotation/status'
import { formatCurrency, formatDate, formatDateRangeShort, formatNumber, toDateKey, todayDateKey } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const quotationIdFromQuery = String(route.query.quotation_id ?? '')
const idPoc = String(route.params.id ?? '')
const isEditMode = computed(() => route.name === 'po-customers-edit')

const pageLoading = ref(false)
const submitting = ref(false)
const formError = ref('')
const items = ref<any[]>([])
const volumePoc = ref<number>(0)
const quotationOptions = ref<Array<{ id: number; nomor_penawaran: string; customer_nama: string }>>([])
const poRecord = ref<any>(null)
const existingAttachments = ref<Array<{ name: string; url: string }>>([])

const form = reactive({
  customer_id: '' as number | string,
  customer_nama: '',
  quotation_id: quotationIdFromQuery as number | string,
  nomor_penawaran: '',
  masa_berlaku: '',
  sampai_dengan: '',
  dpp: 0,
  nomor_po: '',
  tipe_bayar: '' as string,
  termin_hari: '' as string | number,
  tanggal_po: '',
  tanggal_kirim: '',
  lampiran: null as File | null,
  produk_poc: '' as number | string,
})

const quotationTotalQuantity = computed<number>(() =>
  items.value.reduce((sum, it) => sum + Number(it.quantity ?? 0), 0),
)
const pricePerM3 = computed<number>(() =>
  quotationTotalQuantity.value > 0
    ? Math.round((Number(form.dpp) / quotationTotalQuantity.value) * 10000) / 10000
    : 0,
)
const totalHarga = computed(() => pricePerM3.value * volumePoc.value)

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
        if (!isEditMode.value || !value || !form.masa_berlaku || !form.sampai_dengan) return true
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
  volumePoc: {
    required: helpers.withMessage('Total Volume PO wajib diisi', required),
    integer: helpers.withMessage('Total Volume PO harus berupa angka bulat', integer),
    minValue: helpers.withMessage('Total Volume PO harus lebih dari 0', minValue(1)),
  },
}

const v$ = useVuelidate(rules, { ...toRefs(form), volumePoc })

onMounted(async () => {
  if (isEditMode.value) {
    const ok = await loadPoCustomer()
    if (!ok) return
  } else if (quotationIdFromQuery) {
    form.quotation_id = quotationIdFromQuery
  }

  await Promise.all([
    fetchQuotationOptions(),
    form.quotation_id ? fetchQuotationDetail(form.quotation_id) : Promise.resolve(),
  ])

  if (isEditMode.value) {
    applyPoCustomerToForm()
  }

  const selectedQuotationId = String(form.quotation_id || '')
  if (selectedQuotationId && !quotationOptions.value.some(opt => String(opt.id) === selectedQuotationId)) {
    quotationOptions.value.push({
      id: Number(form.quotation_id),
      nomor_penawaran: form.nomor_penawaran,
      customer_nama: form.customer_nama,
    })
  }
})

async function loadPoCustomer(): Promise<boolean> {
  pageLoading.value = true

  try {
    const { data } = await axios.get(`/api/customer-pos/${idPoc}`)

    if (!['awaiting_process', 'blocked'].includes(data.status_key)) {
      notifyError('Gagal', 'PO Customer tidak bisa diubah karena sudah masuk proses Sales Confirmation.')
      router.push({ name: 'po-customers-detail', params: { id: idPoc } })
      return false
    }

    poRecord.value = data
    form.quotation_id = data.quotation_id
    return true
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data PO Customer')
    router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    return false
  } finally {
    pageLoading.value = false
  }
}

function applyPoCustomerToForm() {
  const po = poRecord.value
  if (!po) return

  form.nomor_po = po.nomor_poc ?? ''
  form.tanggal_po = po.tanggal_poc ?? ''
  form.tanggal_kirim = po.supply_date ?? ''
  volumePoc.value = Math.round(Number(po.volume_poc ?? 0))
  form.tipe_bayar = po.tipe_bayar ?? ''
  form.termin_hari = po.termin_hari ?? ''
  form.produk_poc = po.produk_poc ?? ''

  if (po.lampiran_poc) {
    existingAttachments.value = [{ name: po.lampiran_poc_ori, url: `/storage/${po.lampiran_poc}` }]
  }
}

async function fetchQuotationOptions() {
  try {
    const { data } = await axios.get('/api/penawarans', {
      params: {
        status: QUOTATION_STATUS.Approved,
        per_page: 200,
        valid_today: 1,
        ...(isEditMode.value && poRecord.value?.id_customer ? { customer_id: poRecord.value.id_customer } : {}),
      },
    })
    quotationOptions.value = (data.data ?? []).map((row: any) => ({
      id: row.id,
      nomor_penawaran: row.nomor_penawaran,
      customer_nama: row.customer?.company_name || '-',
    }))
  } catch {
    notifyError('Gagal', 'Gagal memuat daftar penawaran')
  }
}

async function fetchQuotationDetail(id: string | number) {
  if (!id) return

  pageLoading.value = true

  try {
    const { data } = await axios.get(`/api/penawarans/${id}`)

    form.customer_id = data.customer_id
    form.customer_nama = data.customer?.company_name || '-'
    form.quotation_id = data.id
    form.nomor_penawaran = data.nomor_penawaran || '-'
    form.masa_berlaku = data.masa_berlaku
    form.sampai_dengan = data.sampai_dengan
    form.dpp = Number(data.dpp ?? 0)

    if (!isEditMode.value) {
      const { tipeBayar, terminHari } = deriveDefaultPayment(data.tipe_pembayaran, data.repayment_hari)
      form.tipe_bayar = tipeBayar
      form.termin_hari = terminHari ?? ''
    }

    items.value = Array.isArray(data.items) ? data.items : []
    form.produk_poc = items.value[0]?.product_id || ''
    volumePoc.value = Math.round(quotationTotalQuantity.value)
  } catch {
    notifyError('Gagal', 'Gagal memuat data penawaran')
  } finally {
    pageLoading.value = false
  }
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

function onQuotationSelected(value: string | string[]): void {
  const id = Array.isArray(value) ? value[0] : value
  if (id && String(id) !== String(form.quotation_id)) {
    form.quotation_id = id
    fetchQuotationDetail(id)
  }
}

async function submit() {
  formError.value = ''

  if (isQuotationExpiredToday.value) return

  const valid = await v$.value.$validate()
  if (!valid) return

  submitting.value = true

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
  payload.append('volume_poc', String(Math.round(volumePoc.value)))
  payload.append('produk_poc', String(form.produk_poc || ''))

  if (form.lampiran) {
    payload.append('lampiran_poc', form.lampiran)
  }

  try {
    if (isEditMode.value) {
      await axios.post(`/api/customer-pos/${idPoc}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      success('Berhasil', 'PO Customer berhasil diperbarui')
      router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    } else {
      await axios.post('/api/customer-pos', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      success('Berhasil', 'PO Customer berhasil dibuat')
      router.push({ name: 'po-customers-index' })
    }
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errs = e.response?.data?.errors || {}
      formError.value = Object.values(errs).flat().join('\n')
    } else if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'PO Customer tidak bisa diubah.')
      router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan saat menyimpan PO')
    }
  } finally {
    submitting.value = false
  }
}

function goBack() {
  if (isEditMode.value) {
    router.push({ name: 'po-customers-detail', params: { id: idPoc } })
    return
  }
  router.push({ name: 'penawarans-list' })
}

function fieldError(field: 'quotation_id' | 'nomor_po' | 'tipe_bayar' | 'termin_hari' | 'tanggal_po' | 'tanggal_kirim' | 'volumePoc') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function poVolumeForItem(it: any): number {
  return quotationTotalQuantity.value > 0
    ? Math.round(volumePoc.value * Number(it.quantity ?? 0) / quotationTotalQuantity.value)
    : 0
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
        </div>
        <div>
          <div class="text-form-label">Masa Berlaku Harga</div>
          <FormInput type="text" :value="`${formatDate(form.masa_berlaku)} – ${formatDate(form.sampai_dengan)}`"
            readonly class="w-full !bg-white dark:!bg-darkmode-800" />
        </div>
      </div>
    </CardSection>

    <CardSection title="Rincian Item Penawaran" description="Produk, quantity, dan volume dari penawaran." icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <div class="overflow-x-auto">
        <Table bordered sm class="text-body">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="px-4 py-3 text-form-label text-left">Produk</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-left">Ukuran</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Quantity</Table.Th>
              <Table.Th class="px-4 py-3 text-form-label text-right">Volume</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody class="bg-white">
            <Table.Tr v-for="(it, i) in items" :key="it.id || i">
              <Table.Td class="px-4 py-3">
                <div class="text-body-strong">{{ it.product?.nama_produk || '-' }}</div>
                <div v-if="it.product?.jenis?.nama" class="text-caption">{{ it.product?.jenis?.nama }}</div>
              </Table.Td>
              <Table.Td class="px-4 py-3">
                {{ it.product?.ukuran?.nama_ukuran || '-' }}
                <span class="text-caption">({{ it.product?.ukuran?.satuan?.nama_satuan || '-' }})</span>
              </Table.Td>
              <Table.Td class="px-4 py-3 num-sm text-right">{{ formatNumber(it.quantity) }}</Table.Td>
              <Table.Td class="px-4 py-3 num-sm text-right">
                <div>{{ formatNumber(poVolumeForItem(it)) }} m³</div>
              </Table.Td>
            </Table.Tr>

            <Table.Tr v-if="!items.length">
              <Table.Td colspan="4" class="px-4 py-6 text-body text-center">Belum ada item penawaran.</Table.Td>
            </Table.Tr>
          </Table.Tbody>

          <Table.Tbody class="bg-slate-50">
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 text-body-strong text-right">Total Volume</Table.Td>
              <Table.Td class="px-4 py-2 text-right">
                <NumberField v-model.number="volumePoc" class="ml-auto w-40 max-w-[10rem]" suffix="m³" :decimals="0"
                  :error="fieldError('volumePoc')" />
              </Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 text-body-strong text-right">Harga per m³ (termasuk transport)</Table.Td>
              <Table.Td class="px-4 py-2 text-right">
                <div class="num-sm">
                  <span class="num-sm text-xl hover:underline cursor-pointer" :data-tooltip="'po-dpp-breakdown'">{{
                    formatCurrency(pricePerM3) }}</span>
                </div>
                <div class="tooltip-content">
                  <TippyContent :to="'po-dpp-breakdown'" :options="{ placement: 'left', interactive: true }">
                    <div class="w-60 max-w-[85vw]">
                      <div class="flex items-center gap-2 mb-3">
                        <span class="bg-primary rounded-full w-2 h-2 shrink-0"></span>
                        <span class="text-form-label text-slate-500 tracking-wide">Komposisi Harga per m³</span>
                      </div>
                      <div class="flex justify-between items-center gap-4">
                        <span class="text-caption text-slate-500">DPP penawaran</span>
                        <span class="text-body-strong">{{ formatCurrency(form.dpp) }}</span>
                      </div>
                      <div class="flex justify-between items-center gap-4">
                        <span class="text-caption text-slate-500">Total quantity penawaran</span>
                        <span class="text-body-strong">{{ formatNumber(quotationTotalQuantity) }}</span>
                      </div>
                      <div class="flex justify-between items-center gap-4 mt-2 pt-1 border-slate-100 border-t">
                        <span class="text-caption text-slate-500">Harga per m³</span>
                        <span class="text-body-strong">{{ formatCurrency(pricePerM3) }}</span>
                      </div>
                    </div>
                  </TippyContent>
                </div>
              </Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 text-section-title text-right">Total Harga</Table.Td>
              <Table.Td class="px-4 py-2 num-md text-xl text-right">{{ formatCurrency(totalHarga) }}</Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
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
              :class="v$.nomor_po.$error ? 'border-rose-500' : ''" />
            <small v-if="v$.nomor_po.$error" class="text-caption !text-rose-600">{{ fieldError('nomor_po') }}</small>
          </div>

          <div class="gap-4 grid grid-cols-2">
            <div>
              <FormLabel for="tipe_bayar">Tipe Pembayaran
                <RequiredAsterisk />
              </FormLabel>
              <FormSelect id="tipe_bayar" v-model="form.tipe_bayar" class="w-full"
                :class="v$.tipe_bayar.$error ? 'border-rose-500' : ''">
                <option value="" disabled>Pilih…</option>
                <option value="CBD">CBD</option>
                <option value="COD">COD</option>
                <option value="CREDIT">Credit</option>
              </FormSelect>
              <small v-if="v$.tipe_bayar.$error" class="text-caption !text-rose-600">{{ fieldError('tipe_bayar') }}</small>
            </div>

            <div v-if="form.tipe_bayar === 'CREDIT'">
              <FormLabel for="termin_hari">Termin (Hari)
                <RequiredAsterisk />
              </FormLabel>
              <NumberField id="termin_hari" v-model.number="form.termin_hari" suffix="hari" :decimals="0"
                :error="fieldError('termin_hari')" />
            </div>
          </div>

          <div class="gap-4 grid grid-cols-2">
            <DateField v-model="form.tanggal_po" label="Tanggal PO" required placeholder="Pilih tanggal PO"
              :error="fieldError('tanggal_po')" />
            <DateField v-model="form.tanggal_kirim" label="Tanggal Pengiriman" required
              placeholder="Pilih tanggal pengiriman" :error="fieldError('tanggal_kirim')" />
          </div>

          <FileUploadField v-model="form.lampiran" :existing-files="existingAttachments"
            label="Lampiran Dokumen PO" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="2"
            :hint="isEditMode ? 'Upload file baru untuk mengganti.' : 'Opsional. PDF, JPG, atau PNG maksimal 2MB.'" />

          <Alert v-if="isQuotationExpiredToday" variant="soft-warning" class="flex items-start gap-2">
            <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>Penawaran ini tidak berlaku hari ini (masa berlaku {{ validityRangeLabel }}). PO tidak dapat dibuat.</span>
          </Alert>

          <div v-if="isPoBlocked" class="flex items-start gap-2 bg-amber-50 px-4 py-3 border border-amber-200 rounded-lg text-body text-amber-800">
            <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>PO ini di-block oleh gerbang kredit. Setelah disimpan, PO kembali ke status belum diproses dan perlu Proses SC ulang.</span>
          </div>

          <Button type="submit" variant="primary" class="inline-flex items-center justify-center gap-2 w-full"
            :disabled="pageLoading || submitting || isQuotationExpiredToday">
            <Lucide v-if="pageLoading || submitting" icon="Loader2" class="h-4 w-4 animate-spin" />
            <Lucide v-else icon="Save" class="h-4 w-4" />
            Simpan
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
