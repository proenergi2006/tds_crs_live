<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import Table from '@/components/Base/Table'

interface Item {
  id_produk: number | null | ''
  volume_po: number
  harga_tebus: number
  total_harga: number
  kd_tax: string
  tax_amount: number
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const poId = computed(() => Number(route.params.id || 0))
const mode = computed<'create' | 'edit'>(() => poId.value ? 'edit' : 'create')
const pageTitle = computed(() => mode.value === 'create' ? 'Tambah Vendor PO' : 'Edit Vendor PO')
const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Lengkapi informasi purchase order vendor, rincian produk, nilai transaksi, dan terms.'
    : 'Perbarui informasi purchase order vendor, rincian produk, nilai transaksi, dan terms.',
)
const submitText = computed(() => mode.value === 'create' ? 'Simpan Purchase Order' : 'Simpan Perubahan')

const loading = ref(false)
const pageLoading = ref(false)
const error = ref('')
const termsChecked = ref(false)

const vendors = ref<any[]>([])
const terminals = ref<any[]>([])
const produks = ref<any[]>([])

const form = reactive({
  id_vendor: '' as number | '',
  id_terminal: '' as number | '',
  nomor_po: '',
  tanggal_inven: '',
  terms: '',
  terms_day: 0,
  items: [makeEmptyItem()] as Item[],
  keterangan: '',
  terms_condition: '',
  created_by: '',
  lastupdate_by: '',
})

const calcSubtotal = computed(() => form.items.reduce((sum, item) => sum + item.total_harga, 0))

/**
 * Kode tax sekarang tidak lagi berada di header level, disesuikan agar bisa mendefinisikan tax per item
 * Dengan begitu, ppn11% tidak relevan lagi. Gunakan totalTax untuk menggantikan ppn11%
 * Field di db tetap sama, namun tujuannya sekarang digunakan untuk menyimpan totalTax per item
 */
// const calcPPN = computed(() => Math.round(calcSubtotal.value * 0.11))

const calcTotalTax = computed(() => form.items.reduce((sum, item) => sum + item.tax_amount, 0))
const calcTotalOrder = computed(() => calcSubtotal.value + calcTotalTax.value)

onMounted(init)

function makeEmptyItem(): Item {
  return {
    id_produk: '',
    volume_po: 0,
    harga_tebus: 0,
    total_harga: 0,
    kd_tax: '',
    tax_amount: 0,
  }
}

async function init() {
  pageLoading.value = true

  try {
    await Promise.all([
      fetchCurrentUser(),
      fetchDropdowns(),
    ])

    if (mode.value === 'edit') {
      await fetchPo()
    }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal memuat data purchase order'
  } finally {
    pageLoading.value = false
  }
}

async function fetchCurrentUser() {
  try {
    const { data } = await axios.get('/api/user')

    form.created_by = data.name
    form.lastupdate_by = data.name
  } catch {
    form.created_by = ''
    form.lastupdate_by = ''
  }
}

async function fetchDropdowns() {
  const [vendorRes, terminalRes, produkRes] = await Promise.all([
    axios.get('/api/vendors', { params: { per_page: 100 } }),
    axios.get('/api/terminals', { params: { per_page: 100 } }),
    axios.get('/api/produks', { params: { per_page: 100 } }),
  ])

  vendors.value = vendorRes.data.data || vendorRes.data || []
  terminals.value = terminalRes.data.data || terminalRes.data || []
  produks.value = produkRes.data.data || produkRes.data || []
}

async function fetchPo() {
  const { data: po } = await axios.get(`/api/vendor-pos/${poId.value}`)

  Object.assign(form, {
    id_vendor: po.id_vendor,
    id_terminal: po.id_terminal,
    nomor_po: po.nomor_po,
    tanggal_inven: po.tanggal_inven,
    terms: po.terms,
    terms_day: po.terms_day,
    keterangan: po.keterangan || '',
    terms_condition: po.terms_condition || '',
  })

  termsChecked.value = Boolean(po.terms_condition)

  const { data: items } = await axios.get('/api/vendor-pos-produk', {
    params: { id_po: poId.value },
  })

  form.items = (items || []).map((item: any) => ({
    id_produk: item.id_produk,
    volume_po: toDbInt(item.volume_po),
    harga_tebus: toDbInt(item.harga_tebus),
    total_harga: toDbInt(item.jumlah_harga),
    kd_tax: item.kd_tax ?? '',
    tax_amount: Number(item.tax_amount) || 0,
  }))

  if (form.items.length === 0) {
    form.items = [makeEmptyItem()]
  }

  computeAllTotals()
}

function addRow() {
  form.items.push(makeEmptyItem())
}

function removeRow(index: number) {
  if (form.items.length <= 1) return

  form.items.splice(index, 1)
}

function computeTotal(index: number) {
  const item = form.items[index]
  item.total_harga = toInt(item.volume_po) * toInt(item.harga_tebus)
  computeTax(index)
}

function computeTax(index: number) {
  const item = form.items[index]
  item.tax_amount = item.kd_tax === 'E' ? Math.round(item.total_harga * 0.11) : 0
}

function computeAllTotals() {
  form.items.forEach((_, index) => computeTotal(index))
}

function updateHargaTebus(index: number, value: number) {
  form.items[index].harga_tebus = toInt(value)
  computeTotal(index)
}

function toInt(value: unknown): number {
  if (value === null || value === undefined || value === '') return 0
  if (typeof value === 'number') return Math.trunc(value)

  const text = String(value).trim()

  if (/^\d+\.\d{1,2}$/.test(text)) {
    return Math.trunc(Number.parseFloat(text))
  }

  const normalized = text.split(',')[0].replace(/[^\d]/g, '')
  return normalized ? Number.parseInt(normalized, 10) : 0
}

function toDbInt(value: unknown): number {
  if (value === null || value === undefined || value === '') return 0
  if (typeof value === 'number') return Math.trunc(value)

  const text = String(value).trim()

  if (/^\d+(\.\d+)?$/.test(text)) {
    return Math.trunc(Number.parseFloat(text))
  }

  return toInt(value)
}

function formatNumber(value: number) {
  return toInt(value).toLocaleString('id-ID')
}

function validateForm() {
  if (!form.id_vendor) return 'Vendor wajib dipilih'
  if (!form.id_terminal) return 'Terminal wajib dipilih'
  if (mode.value === 'edit' && !form.nomor_po) return 'Nomor PO wajib diisi'

  return ''
}

function buildHeaderPayload() {
  return {
    id_vendor: Number(form.id_vendor),
    id_terminal: Number(form.id_terminal),
    ...(mode.value === 'edit' ? { nomor_po: form.nomor_po } : {}),
    tanggal_inven: form.tanggal_inven,
    terms: form.terms,
    terms_day: Number(form.terms_day),
    subtotal: calcSubtotal.value,
    ppn11: calcTotalTax.value,
    total_order: calcTotalOrder.value,
    keterangan: form.keterangan,
    terms_condition: termsChecked.value ? form.terms_condition : null,
    ...(mode.value === 'create'
      ? { created_by: form.created_by }
      : { disposisi_po: 0, lastupdate_by: form.lastupdate_by }),
  }
}

function buildPayload(idPo?: number) {
  return {
    ...buildHeaderPayload(),
    items: form.items.map(item => ({
      id_produk: Number(item.id_produk),
      volume_po: toInt(item.volume_po),
      harga_tebus: toInt(item.harga_tebus),
      jumlah_harga: toInt(item.total_harga),
      kd_tax: item.kd_tax || null,
      tax_amount: item.tax_amount,
    })),
  }
}

async function submitForm() {
  const validationMessage = validateForm()

  if (validationMessage) {
    notifyError('Gagal', validationMessage)
    return
  }

  loading.value = true
  error.value = ''

  try {
    if (mode.value === 'create') {
      await axios.post('/api/vendor-pos', buildPayload())
      success('Berhasil', 'PO berhasil disimpan')
    } else {
      await axios.put(`/api/vendor-pos/${poId.value}`, buildPayload())
      success('Berhasil', 'PO diperbarui')
    }

    router.push({ name: 'vendor-pos-list' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.push({ name: 'vendor-pos-list' })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="full" :loading="loading || pageLoading"
    :error="error" :submit-text="submitText" submit-icon="Save" cancel-icon="ArrowLeft" layout="sidebar" surface="plain"
    footer-placement="sidebar" @cancel="cancel" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-4">
          <div class="font-label mb-1">Data PO</div>
          <div class="flex flex-col gap-4 rounded border border-slate-200 p-4">
            <div v-if="mode === 'edit'">
              <FormLabel for="nomor_po">Nomor PO</FormLabel>
              <FormInput id="nomor_po" v-model="form.nomor_po" placeholder="Nomor PO" disabled />
            </div>

            <div v-else>
              <FormLabel>Nomor PO</FormLabel>
              <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-2.5 font-body text-xs">
                *<i>Generate</i> otomatis setelah simpan.
              </div>
            </div>

            <DateField v-model="form.tanggal_inven" label="Tanggal" placeholder="Pilih tanggal PO" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-4">
          <div class="font-label mb-1">Data Vendor</div>
          <div class="flex flex-col gap-4 rounded border border-slate-200 p-4">
            <div>
              <FormLabel for="vendor">Vendor</FormLabel>
              <FormSelect id="vendor" v-model="form.id_vendor">
                <option disabled value="">-- Pilih Vendor --</option>
                <option v-for="vendor in vendors" :key="vendor.id_vendor" :value="vendor.id_vendor">
                  {{ vendor.nama_vendor }}
                </option>
              </FormSelect>
            </div>

            <div>
              <FormLabel for="terminal">Terminal</FormLabel>
              <FormSelect id="terminal" v-model="form.id_terminal">
                <option disabled value="">-- Pilih Terminal --</option>
                <option v-for="terminal in terminals" :key="terminal.id_terminal" :value="terminal.id_terminal">
                  {{ terminal.nama_terminal }}
                </option>
              </FormSelect>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-4">
          <div class="font-label mb-1">Data Lainnya</div>
          <div class="flex flex-col gap-4 rounded border border-slate-200 p-4">
            <div class="col-span-12 md:col-span-3">
              <FormLabel for="terms">Terms</FormLabel>
              <FormSelect id="terms" v-model="form.terms">
                <option disabled value="">-- Pilih Terms --</option>
                <option value="CBD">CBD</option>
                <option value="COD">COD</option>
                <option value="TOP">TOP</option>
              </FormSelect>
            </div>

            <div class="col-span-12 md:col-span-3">
              <FormLabel for="terms_day">Terms Day</FormLabel>
              <FormInput id="terms_day" v-model.number="form.terms_day" type="number" />
            </div>
          </div>
        </div>
      </div>
    </CardSection>

    <CardSection title="Rincian Produk" description="Produk, volume, dan harga tebus" icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <template #action>
        <Button type="button" variant="outline-primary" class="inline-flex items-center gap-2" @click="addRow">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah Baris
        </Button>
      </template>

      <div class="overflow-x-auto">
        <Table bordered sm class="font-body">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="w-16 px-4 py-3 font-label text-center">#</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-left">Produk</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Volume PO</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Harga Tebus</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Total Harga</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-center">Kode Tax</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Tax Amount</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody class="bg-white">
            <Table.Tr v-for="(item, index) in form.items" :key="index" class="transition hover:bg-slate-50">
              <Table.Td class="px-4 py-3 text-center">
                <Button v-if="form.items.length > 1" type="button" variant="soft-danger" rounded
                  class="!h-8 !w-8 !p-0 !shadow-none" title="Hapus" @click="removeRow(index)">
                  <Lucide icon="Trash2" class="h-3 w-3" />
                </Button>
              </Table.Td>

              <Table.Td class="px-4 py-3">
                <FormSelect :id="`produk-${index}`" v-model="item.id_produk" class="min-w-64">
                  <option disabled value="">-- Pilih Produk --</option>
                  <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
                    {{ produk.nama_produk }} ({{ produk.ukuran?.nama_ukuran }} - {{ produk.ukuran?.satuan?.nama_satuan
                    }})
                  </option>
                </FormSelect>
              </Table.Td>

              <Table.Td class="px-4 py-3">
                <NumberField class="w-32" :id="`volume-po-${index}`" v-model="item.volume_po" placeholder="100" :min="0"
                  :decimals="0" @update:model-value="computeTotal(index)" />
              </Table.Td>

              <Table.Td class="px-4 py-3">
                <CurrencyField :model-value="item.harga_tebus" class="min-w-[120px]"
                  @update:model-value="updateHargaTebus(index, $event)" />
              </Table.Td>

              <Table.Td class="px-4 py-3">
                <CurrencyField :model-value="item.total_harga" class="min-w-[160px]" readonly />
              </Table.Td>

              <Table.Td class="px-4 py-3 text-center">
                <FormSelect :id="`kd-tax-${index}`" v-model="item.kd_tax" class="w-20" @change="computeTax(index)">
                  <option disabled value="">-</option>
                  <option value="E">E</option>
                  <option value="EC">EC</option>
                </FormSelect>
              </Table.Td>

              <Table.Td class="px-4 py-3">
                <CurrencyField :model-value="item.tax_amount" class="min-w-[140px]" readonly />
              </Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="4" class="px-4 py-3 font-strong text-right">Subtotal</Table.Td>
              <Table.Td class="px-4 py-3 font-num-lg text-right">
                {{ formatNumber(calcSubtotal) }}
              </Table.Td>
              <Table.Td></Table.Td>
              <Table.Td></Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="4" class="px-4 py-3 font-strong text-right">Total Tax</Table.Td>
              <Table.Td class="px-4 py-3 font-num-lg text-right">
                {{ formatNumber(calcTotalTax) }}
              </Table.Td>
              <Table.Td></Table.Td>
              <Table.Td></Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="4" class="px-4 py-4 text-right font-header">Total Order</Table.Td>
              <Table.Td class="px-4 py-4 font-num-lg text-right !text-emerald-700">
                {{ formatNumber(calcTotalOrder) }}
              </Table.Td>
              <Table.Td></Table.Td>
              <Table.Td></Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </table>
      </div>
    </CardSection>

    <template #sidebar>
      <CardSection title="Catatan & Terms" description="Informasi tambahan untuk purchase order" icon="StickyNote"
        icon-class="bg-amber-100 text-amber-600">
        <div class="space-y-4">
          <div>
            <FormLabel for="keterangan">Catatan</FormLabel>
            <FormTextarea id="keterangan" v-model="form.keterangan" rows="4" :autoResize="true"
              placeholder="(opsional)" />
          </div>

          <label class="inline-flex items-center gap-2 font-strong">
            <input v-model="termsChecked" type="checkbox"
              class="rounded border-slate-300 text-primary focus:ring-primary" />
            Terms & Condition
          </label>

          <div>
            <FormTextarea id="terms_condition" v-model="form.terms_condition" rows="6" :autoResize="true"
              placeholder="Isi terms & condition" :disabled="!termsChecked"
              :class="!termsChecked ? 'bg-slate-100 text-slate-400' : ''" />
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
