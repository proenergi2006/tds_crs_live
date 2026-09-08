<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toRaw } from 'vue'
import axios from 'axios'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, requiredIf } from '@vuelidate/validators'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import Table from '@/components/Base/Table'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import ItemsGeneratorModal from './components/ItemsGeneratorModal.vue'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idParam = route.params.id as string | undefined
const isEdit = Boolean(idParam)

type Brand = 'tds' | 'proenergi'
const brand: Brand = (route.meta.brand as Brand) === 'proenergi' ? 'proenergi' : 'tds'
const isProenergi = brand === 'proenergi'

const BRAND_CONFIG = {
  tds: {
    apiBase: '/api/penawarans',
    listRoute: 'penawarans-list',
    detailRoute: 'penawarans-detail',
    usePe: false,
    showAcuan: false,
  },
  proenergi: {
    apiBase: '/api/penawarans-proenergi',
    listRoute: 'penawarans-list-proenergi',
    detailRoute: 'penawarans-detail-proenergi',
    usePe: true,
    showAcuan: true,
  },
}
const cfg = BRAND_CONFIG[brand]
const brandLabel = isProenergi ? 'Penawaran Proenergi' : 'Penawaran'

const customers = ref<any[]>([])
const cabangs = ref<any[]>([])
const produks = ref<any[]>([])
const transportirs = ref<any[]>([])
const wilayahs = ref<any[]>([])
const volumes = ref<any[]>([])

const customerContacts = ref<any[]>([])
const customerContactsLoading = ref(false)
const newContactOpen = ref(false)
const newContactSaving = ref(false)
const newContactError = ref<string | null>(null)
const newContactForm = reactive({
  full_name: '',
  position: '',
  phone: '',
  mobile: '',
  email: '',
})

const oaKapal = ref(0)
const oaTruck = ref(0)
const oaKapalInput = reactive({ id_transportir: '', id_angkut_wilayah: '', id_volume: '' })
const oaTruckInput = reactive({ id_transportir: '', id_angkut_wilayah: '', id_volume: '' })
const oaSelectKey = ref(0)

const loading = ref(false)
const pricePeriods = ref<any[]>([])
const selectedPeriodId = ref<string>('')

const itemsModalOpen = ref(false)

interface ItemLine {
  id_produk: string
  source_branch_id?: string
  product_price_id?: number | null
  source_name?: string
  volume_order: string
  persen: number
  harga_price_list?: number
}

const form = reactive({
  nomor_penawaran: '',
  id_customer: '',
  customer_contact_id: '',
  id_cabang: '' as number | '',
  masa_berlaku: '',
  sampai_dengan: '',

  type_pengiriman: '',
  metode: '',
  ukuran_dasar: '',
  items: [] as ItemLine[],
  lokasi_pengiriman: '',
  keterangan: '',

  tipe_pembayaran: '',
  acuan_pembayaran: '',
  dp_persen: '',
  dp_keterangan: '',
  repayment_persen: '',
  repayment_hari: '',
  order_method: '',
  toleransi_penyusutan: '',
  abrasi: '',
  refund: 0,
  other_cost: 0,

  harga_dasar: 0,
  oat: 0,
  discount: 0,

  catatan: '',
  syarat_ketentuan: '',
  lampiran_tambahan: '',
})

const hargaDasarNumber = computed(() => toNum(form.harga_dasar || 0))
const oatPerVolume = computed(() => toNum(form.oat || 0))

const dppHargaDasar = computed(() => hargaDasarNumber.value + oatPerVolume.value)
const ppnHargaDasar = computed(() => Math.round(dppHargaDasar.value * 0.11))
const grandTotalHargaDasar = computed(() => dppHargaDasar.value + ppnHargaDasar.value)

const avgHargaPriceList = computed(() => {
  const totalPersen = form.items.reduce((s, it) => s + toFloat(it.persen), 0)
  if (totalPersen <= 0) return 0
  const totalWeighted = form.items.reduce((sum, it) => {
    return sum + (toFloat(it.persen) * Number(it.harga_price_list || 0))
  }, 0)
  return totalWeighted / totalPersen
})

const totalPersenNumber = computed(() =>
  Math.round(form.items.reduce((sum, it) => sum + toFloat(it.persen), 0) * 100) / 100
)
const totalPersenDisplay = computed(() => totalPersenNumber.value.toLocaleString('id-ID'))

const totalVolumePO = computed(() =>
  form.items.reduce((sum, it) => sum + (parseInt((it.volume_order || '').replace(/\./g, ''), 10) || 0), 0)
)
const totalVolume = computed(() => totalVolumePO.value.toLocaleString('id-ID'))

const periodeOptions = computed(() => {
  const rank = (c: string) => (c === 'active' ? 0 : c === 'upcoming' ? 1 : 2)
  return [...pricePeriods.value]
    .filter(p => p.category === 'active' || p.category === 'upcoming')
    .sort((a, b) =>
      rank(a.category) - rank(b.category) ||
      String(a.start_date).localeCompare(String(b.start_date)),
    )
})

const periodeSelectList = computed(() => {
  const list = periodeOptions.value
  const stored = form.masa_berlaku && form.sampai_dengan
  const matched = list.some(
    p => p.start_date === form.masa_berlaku && p.end_date === form.sampai_dengan,
  )
  if (stored && !matched) {
    return [
      {
        id: 'custom',
        category: 'inactive',
        start_date: form.masa_berlaku,
        end_date: form.sampai_dengan,
        label: `${form.masa_berlaku} – ${form.sampai_dengan} (di luar daftar periode)`,
      },
      ...list,
    ]
  }
  return list
})

const periodeIsChoice = computed(() => periodeSelectList.value.length > 1)

const selectedPeriodLabel = computed(() => {
  const p = periodeSelectList.value.find(x => String(x.id) === String(selectedPeriodId.value))
  if (p) return p.label + (p.category === 'upcoming' ? ' · akan datang' : '')
  if (form.masa_berlaku) return `${form.masa_berlaku} s/d ${form.sampai_dengan}`
  return ''
})

const selectedCustomer = computed(() =>
  customers.value.find(c => String(c.id_customer) === String(form.id_customer)) ?? null
)
const selectedContact = computed(() =>
  customerContacts.value.find(c => String(c.id) === String(form.customer_contact_id)) ?? null
)

const produkById = computed<Record<string, any>>(() => {
  const m: Record<string, any> = {}
  for (const p of produks.value) m[String(p.id_produk)] = p
  return m
})

function produkLabel(id: string | number): string {
  const p = produkById.value[String(id)]
  if (!p) return `#${id}`
  const uk = p.ukuran?.nama_ukuran
    ? ` · ${p.ukuran.nama_ukuran}${p.ukuran?.satuan?.nama_satuan ? ' ' + p.ukuran.satuan.nama_satuan : ''}`
    : ''
  return `${p.nama_produk}${uk}`
}

const selectedPeriodLabelText = computed(() => selectedPeriodLabel.value || form.masa_berlaku)

function openItemsModal() {
  if (!selectedPeriodId.value || selectedPeriodId.value === 'custom') {
    notifyError('Periode belum siap', 'Pilih periode harga dulu sebelum menyusun item.')
    return
  }
  itemsModalOpen.value = true
}

function onItemsSaved(items: any[]) {
  form.items = items.map((x: any) => ({ ...x }))
  itemsModalOpen.value = false
}

const validationRules = computed(() => ({
  id_customer: { required: helpers.withMessage('Customer wajib diisi.', required) },
  customer_contact_id: { required: helpers.withMessage('Kontak tujuan wajib dipilih.', required) },
  id_cabang: { required: helpers.withMessage('Cabang wajib dipilih.', required) },
  type_pengiriman: { required: helpers.withMessage('Type Pengiriman wajib dipilih.', required) },
  masa_berlaku: { required: helpers.withMessage('Periode harga wajib dipilih.', required) },
  sampai_dengan: {
    required: helpers.withMessage('Sampai dengan wajib diisi.', required),
    afterStart: helpers.withMessage(
      'Tanggal "Sampai Dengan" tidak boleh lebih awal dari "Masa Berlaku".',
      (value: string) => {
        if (!value || !form.masa_berlaku) return true
        const mb = new Date(form.masa_berlaku).getTime()
        const sd = new Date(value).getTime()
        if (Number.isNaN(mb) || Number.isNaN(sd)) return true
        return sd >= mb
      },
    ),
  },
  metode: { required: helpers.withMessage('Metode wajib dipilih.', required) },
  tipe_pembayaran: { required: helpers.withMessage('Tipe pembayaran wajib dipilih.', required) },
  dp_persen: {
    requiredIfCustom: helpers.withMessage(
      'Persentase DP wajib diisi untuk tipe Custom.',
      requiredIf(() => form.tipe_pembayaran === 'CUSTOM'),
    ),
  },
  repayment_persen: {
    requiredIfCustom: helpers.withMessage(
      'Persentase Repayment wajib diisi untuk tipe Custom.',
      requiredIf(() => form.tipe_pembayaran === 'CUSTOM'),
    ),
  },
  oat: {
    requiredForNonFob: helpers.withMessage(
      'OAT per volume wajib terisi (> 0) untuk metode selain FOB.',
      (value: number | string) => {
        if (!form.metode || form.metode === 'FOB') return true
        return toNum(value) > 0
      },
    ),
  },
  items: {
    minLength: helpers.withMessage(
      'Minimal 1 item produk.',
      (val: ItemLine[]) => Array.isArray(val) && val.length > 0,
    ),
    totalPersen: helpers.withMessage(
      'Total rasio harus 100%.',
      () => totalPersenNumber.value === 100,
    ),
    $each: helpers.forEach({
      id_produk: { required: helpers.withMessage('Produk wajib dipilih.', required) },
      persen: {
        positive: helpers.withMessage(
          'Rasio tiap item wajib diisi.',
          (v: number | string) => (parseInt(String(v ?? '').replace(/\./g, ''), 10) || 0) > 0,
        ),
      },
    }),
  },
}))

const v$ = useVuelidate(validationRules, form)

function fieldError(field: string): string {
  const f = (v$.value as any)[field]
  return f?.$error ? (f.$errors[0]?.$message?.toString() ?? '') : ''
}

function inputClass(field: string): string {
  return (v$.value as any)[field]?.$error ? 'input-error' : ''
}

function collectErrorMessages(): string[] {
  const msgs: string[] = []
  const push = (m?: string) => { if (m && !msgs.includes(m)) msgs.push(m) }

  for (const e of v$.value.$errors) push(e.$message?.toString())

  const eachErrors = (v$.value.items as any)?.$each?.$response?.$errors ?? []
  for (const rowError of eachErrors) {
    if (!rowError) continue
    for (const field of Object.keys(rowError)) {
      for (const er of rowError[field]) push(er?.$message?.toString())
    }
  }
  return msgs
}

onMounted(async () => {
  await Promise.all([fetchSelects(), fetchTransportirWilayahVolume()])
  if (!isEdit) {
    const jakarta = cabangs.value.find(
      c => String(c.nama_cabang ?? '').toLowerCase().includes('jakarta'),
    )
    if (jakarta) form.id_cabang = jakarta.id_cabang

    const defaultPeriod = periodeOptions.value[0]
    if (defaultPeriod) selectedPeriodId.value = String(defaultPeriod.id)

    if (route.query.customer_id) {
      form.id_customer = String(route.query.customer_id)
    }
  } else {
    await fetchPenawaran()
  }
})

watch(selectedPeriodId, (id) => {
  if (id === 'custom') return
  const p = pricePeriods.value.find(x => String(x.id) === String(id))
  form.masa_berlaku = p?.start_date ?? ''
  form.sampai_dengan = p?.end_date ?? ''
})

watch(() => form.id_customer, async (val) => {
  await fetchCustomerContacts(val)
  if (form.customer_contact_id && !customerContacts.value.some(c => String(c.id) === String(form.customer_contact_id))) {
    form.customer_contact_id = ''
  }
})

watch(() => oaKapalInput.id_volume, (val) => {
  const vol = volumes.value.find(v => String(v.id_volume) === String(val))
  if (vol) form.ukuran_dasar = String(vol.volume ?? '')
})

watch(() => [oaKapalInput.id_transportir, oaKapalInput.id_angkut_wilayah, oaKapalInput.id_volume], async () => {
  if (form.metode !== 'CIF' && form.metode !== 'DAP') return
  if (!oaKapalInput.id_transportir || !oaKapalInput.id_angkut_wilayah || !oaKapalInput.id_volume) return
  try {
    const { data } = await axios.get('/api/ongkos-kapal/check', { params: toRaw(oaKapalInput) })
    oaKapal.value = data.oa || 0
  } catch {
    oaKapal.value = 0
  }
})

watch(() => [oaTruckInput.id_transportir, oaTruckInput.id_angkut_wilayah, oaTruckInput.id_volume], async () => {
  if (form.metode !== 'DAP' && form.metode !== 'FOT') return
  if (!oaTruckInput.id_transportir || !oaTruckInput.id_angkut_wilayah || !oaTruckInput.id_volume) return
  try {
    const { data } = await axios.get('/api/ongkos-trucks/check', { params: toRaw(oaTruckInput) })
    oaTruck.value = data.oa || 0
  } catch {
    oaTruck.value = 0
  }
})

async function fetchSelects() {
  try {
    const [cusData, cabData, prdData, ppData] = await Promise.all([
      axios.get('/api/customers', { params: { as_list: true } }),
      axios.get('/api/cabangs'),
      axios.get('/api/produks', { params: { with: 'ukuran', per_page: 1000 } }),
      axios.get('/api/price-periods'),
    ])
    customers.value = cusData.data.data || cusData.data
    cabangs.value = cabData.data.data || cabData.data
    produks.value = prdData.data.data || prdData.data
    pricePeriods.value = ppData.data.data || ppData.data
  } catch {
    notifyError('Gagal', 'Gagal memuat data master')
  }
}

async function fetchCustomerContacts(idCustomer: number | string) {
  if (!idCustomer) {
    customerContacts.value = []
    return
  }
  customerContactsLoading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}/contacts`)
    customerContacts.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch {
    customerContacts.value = []
    notifyError('Gagal', 'Gagal memuat kontak customer')
  } finally {
    customerContactsLoading.value = false
  }
}

async function fetchTransportirWilayahVolume() {
  const [t, w, v] = await Promise.all([
    axios.get('/api/transportirs'),
    axios.get('/api/wilayah-angkuts'),
    axios.get('/api/volumes'),
  ])
  transportirs.value = t.data.data || t.data
  wilayahs.value = w.data.data || w.data
  volumes.value = v.data.data || v.data
}

async function fetchPenawaran() {
  try {
    const { data } = await axios.get(`${cfg.apiBase}/${idParam}`)

    selectedPeriodId.value = data.price_period_id != null ? String(data.price_period_id) : ''

    Object.assign(form, {
      nomor_penawaran: data.nomor_penawaran,
      id_customer: data.id_customer ? String(data.id_customer) : '',
      customer_contact_id: data.customer_contact?.id_contact ? String(data.customer_contact.id_contact) : '',
      id_cabang: data.id_cabang,
      masa_berlaku: data.masa_berlaku,
      sampai_dengan: data.sampai_dengan,

      type_pengiriman: data.type_pengiriman || '',
      metode: data.metode || '',
      lokasi_pengiriman: data.lokasi_pengiriman || '',
      keterangan: data.keterangan || '',

      tipe_pembayaran: data.tipe_pembayaran || '',
      acuan_pembayaran: data.acuan_pembayaran || '',
      dp_persen: formatInt(data.dp_persen),
      dp_keterangan: data.dp_keterangan || '',
      repayment_persen: formatInt(data.repayment_persen),
      repayment_hari: formatInt(data.repayment_hari),
      order_method: data.order_method || '',
      toleransi_penyusutan: data.toleransi_penyusutan ? String(Number(data.toleransi_penyusutan)) : '',
      abrasi: data.abrasi || '',
      refund: data.refund != null ? Number(data.refund) : 0,
      other_cost: data.other_cost != null ? Number(data.other_cost) : 0,

      harga_dasar: data.harga_dasar != null ? Number(data.harga_dasar) : 0,

      catatan: data.catatan || '',
      syarat_ketentuan: data.syarat_ketentuan || '',
      lampiran_tambahan: data.lampiran_tambahan || '',
    })

    const ongkosList = Array.isArray(data.ongkos) ? data.ongkos : []
    const kapal = ongkosList.find((o: any) => (o.jenis || '').toUpperCase() === 'KAPAL')
    const truck = ongkosList.find((o: any) => (o.jenis || '').toUpperCase() === 'TRUCK')

    if (kapal) {
      oaKapalInput.id_transportir = String(kapal.transportir_id ?? '')
      oaKapalInput.id_angkut_wilayah = String(kapal.wilayah_id ?? '')
      oaKapalInput.id_volume = String(kapal.volume_id ?? '')
      oaKapal.value = Number(kapal.ongkos ?? 0)
    }
    if (truck) {
      oaTruckInput.id_transportir = String(truck.transportir_id ?? '')
      oaTruckInput.id_angkut_wilayah = String(truck.wilayah_id ?? '')
      oaTruckInput.id_volume = String(truck.volume_id ?? '')
      oaTruck.value = Number(truck.ongkos ?? 0)
    }
    oaSelectKey.value++

    form.oat = Number(data.oat) || 0
    form.items = data.items.map((it: any) => ({
      id_produk: it.id_produk ? String(it.id_produk) : '',
      source_branch_id: it.source_branch_id != null ? String(it.source_branch_id) : '',
      product_price_id: it.product_price_id ?? null,
      source_name: it.source_branch?.nama_cabang ?? '',
      volume_order: it.volume_order?.toLocaleString('id-ID') || '',
      persen: it.persen != null ? Number(it.persen) : 0,
      harga_price_list: it.price_list != null ? Number(it.price_list) : 0,
    }))
  } catch {
    notifyError('Gagal', 'Gagal memuat data penawaran')
  }
}

async function submitForm() {
  if (!selectedPeriodId.value || selectedPeriodId.value === 'custom') {
    notifyError('Periode belum dipilih', 'Pilih periode harga yang aktif untuk penawaran ini.')
    return
  }
  const valid = await v$.value.$validate()
  if (!valid) {
    notifyError('Validasi Gagal', 'Periksa kembali isian berikut:', {
      items: collectErrorMessages(),
    })
    return
  }
  loading.value = true
  try {
    const payloadItems = form.items.map((it) => ({
      id_produk: Number(it.id_produk),
      source_branch_id: Number(it.source_branch_id),
      product_price_id: Number(it.product_price_id),
      persen: toFloat(it.persen),
      volume_order: parseInt((it.volume_order || '').replace(/\./g, ''), 10) || 0,
    }))

    const payloadOngkos: any[] = []
    if ((form.metode === 'CIF' || form.metode === 'DAP') && oaKapal.value > 0) {
      payloadOngkos.push({
        jenis: 'KAPAL',
        id_transportir: oaKapalInput.id_transportir,
        id_angkut_wilayah: oaKapalInput.id_angkut_wilayah,
        id_volume: parseInt(oaKapalInput.id_volume || '0', 10),
        ongkos: oaKapal.value,
      })
    }
    if ((form.metode === 'DAP' || form.metode === 'FOT') && oaTruck.value > 0) {
      payloadOngkos.push({
        jenis: 'TRUCK',
        id_transportir: oaTruckInput.id_transportir,
        id_angkut_wilayah: oaTruckInput.id_angkut_wilayah,
        id_volume: parseInt(oaTruckInput.id_volume || '0', 10),
        ongkos: oaTruck.value,
      })
    }

    const payload = {
      id_customer: Number(form.id_customer),
      customer_contact_id: Number(form.customer_contact_id),
      id_cabang: form.id_cabang,
      price_period_id: Number(selectedPeriodId.value),

      type_pengiriman: form.type_pengiriman,
      metode: form.metode,
      ongkos: payloadOngkos,
      items: payloadItems,
      lokasi_pengiriman: form.lokasi_pengiriman,
      keterangan: form.keterangan,

      tipe_pembayaran: form.tipe_pembayaran,
      ...(isProenergi ? {
        acuan_pembayaran: form.acuan_pembayaran,
      } : {}),
      dp_persen: parseInt((form.dp_persen || '0').replace(/\./g, ''), 10) || 0,
      dp_keterangan: form.dp_keterangan,
      repayment_persen: parseInt((form.repayment_persen || '0').replace(/\./g, ''), 10) || 0,
      repayment_hari: parseInt((form.repayment_hari || '0').replace(/\./g, ''), 10) || 0,
      order_method: form.order_method,
      toleransi_penyusutan: parseInt((form.toleransi_penyusutan || '0').replace(/\./g, ''), 10) || 0,
      abrasi: form.abrasi,
      refund: form.refund,
      other_cost: form.other_cost,

      harga_dasar: form.harga_dasar,
      oat: form.oat,
      discount: form.discount,
      ppn_harga_dasar: ppnHargaDasar.value,
      grand_total_harga_dasar: grandTotalHargaDasar.value,

      catatan: form.catatan,
      syarat_ketentuan: form.syarat_ketentuan,
      lampiran_tambahan: form.lampiran_tambahan,
    }

    if (isEdit) {
      await axios.put(`${cfg.apiBase}/${idParam}`, payload)
      success('Berhasil', 'Penawaran berhasil diupdate.')
      router.push({ name: cfg.detailRoute, params: { id: idParam } })
    } else {
      const { data } = await axios.post(cfg.apiBase, payload)
      success('Berhasil', 'Penawaran berhasil dibuat.')
      router.push({ name: cfg.detailRoute, params: { id: data.id_penawaran } })
    }
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data.errors) {
      const items = Object.values(e.response.data.errors).flat() as string[]
      notifyError('Validasi Backend Gagal', 'Perbaiki data berikut:', { items })
    } else {
      notifyError('Gagal', e.response?.data?.message || 'Gagal menyimpan penawaran.')
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({ name: cfg.listRoute })
}

function openNewContactForm() {
  if (!form.id_customer) return
  newContactError.value = null
  Object.assign(newContactForm, { full_name: '', position: '', phone: '', mobile: '', email: '' })
  newContactOpen.value = true
}

async function submitNewContact() {
  newContactError.value = null
  if (!newContactForm.full_name.trim()) {
    newContactError.value = 'Nama wajib diisi.'
    return
  }
  newContactSaving.value = true
  try {
    const { data } = await axios.post(`/api/customers/${form.id_customer}/contacts`, {
      full_name: newContactForm.full_name.trim(),
      position: newContactForm.position.trim() || null,
      phone: newContactForm.phone.trim() || null,
      mobile: newContactForm.mobile.trim() || null,
      email: newContactForm.email.trim() || null,
    })
    customerContacts.value.push(data)
    form.customer_contact_id = String(data.id)
    newContactOpen.value = false
    success('Berhasil', 'Kontak berhasil ditambahkan.')
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      newContactError.value = (Object.values(errors)[0] as string[])?.[0] || 'Periksa kembali input Anda.'
    } else {
      newContactError.value = e.response?.data?.message ?? 'Gagal menyimpan kontak.'
    }
  } finally {
    newContactSaving.value = false
  }
}

function toFloat(v: string | number): number {
  if (typeof v === 'number') return v
  const s = (v || '').toString().replace(/\./g, '').replace(',', '.')
  const n = parseFloat(s)
  return isNaN(n) ? 0 : n
}

function toNum(v: string | number): number {
  if (typeof v === 'number') return v
  const s = (v || '').toString().replace(/\./g, '').replace(',', '.')
  const n = parseFloat(s)
  return Number.isFinite(n) ? n : 0
}

function formatNumeric(obj: any, field: any, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  obj[field] = isNaN(num) ? '' : num.toLocaleString('id-ID')
}

function formatDecimalInput(obj: any, field: string, e: Event) {
  let val = (e.target as HTMLInputElement).value.replace(/[^0-9.]/g, '')
  const parts = val.split('.')
  if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('')
  obj[field] = val
}

function formatInt(v: number | string | null | undefined): string {
  const n = typeof v === 'string' ? parseInt(v.replace(/\D/g, ''), 10) : v ?? 0
  return Number.isFinite(n) ? (n as number).toLocaleString('id-ID') : ''
}

function formatCurrency(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return !isNaN(n) ? `Rp. ${n.toLocaleString('id-ID')}` : '-'
}

</script>

<template>
  <FormPage :title="isEdit ? `Edit ${brandLabel}` : `Tambah ${brandLabel}`"
    :description="isEdit ? 'Perbarui data penawaran ke customer.' : 'Lengkapi data penawaran baru ke customer.'"
    size="full" layout="sidebar" surface="plain" footer-placement="sidebar" :loading="loading"
    :submit-text="isEdit ? 'Update Penawaran' : 'Simpan Penawaran'" submit-icon="Save" cancel-icon="ArrowLeft"
    @cancel="goBack" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <template v-if="isEdit" #header>
      <div class="flex items-start gap-2 bg-amber-50 px-4 py-3 border border-amber-200 rounded-lg text-amber-800">
        <span class="font-body leading-5">
          <b>Info:</b> Mengubah penawaran akan mengembalikan posisi disposisi ke
          <b>Draft</b> dan proses approval akan dimulai dari awal.
        </span>
      </div>
    </template>

    <CardSection title="Informasi Penawaran" description="Customer, source harga dan informasi penerima"
      icon="FileText">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-5">

          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Customer
                <RequiredAsterisk />
              </FormLabel>
              <div>
                <TomSelect v-model="form.id_customer" :options="{
                  placeholder: 'Pilih Customer...',
                  dropdownParent: 'body' as const,
                }" class="w-full" :class="inputClass('id_customer')">
                  <option v-for="c in customers" :key="c.id_customer" :value="String(c.id_customer)">
                    {{ c.company_name }}
                  </option>
                </TomSelect>
              </div>
              <small v-if="fieldError('id_customer')" class="block input-error-text">{{ fieldError('id_customer')
              }}</small>
            </div>

            <div v-if="false">
              <FormLabel>Cabang Invoice
                <RequiredAsterisk />
              </FormLabel>
              <FormSelect v-model="form.id_cabang" class="w-full" :class="inputClass('id_cabang')">
                <option value="" disabled>Pilih Cabang…</option>
                <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                  {{ c.nama_cabang }}
                </option>
              </FormSelect>
              <small v-if="fieldError('id_cabang')" class="block input-error-text">{{ fieldError('id_cabang') }}</small>
            </div>

            <div>
              <FormLabel>Periode Harga
                <RequiredAsterisk />
              </FormLabel>

              <FormSelect v-if="periodeIsChoice" v-model="selectedPeriodId" class="w-full"
                :class="inputClass('masa_berlaku')">
                <option value="" disabled>Pilih periode…</option>
                <option v-for="p in periodeSelectList" :key="p.id" :value="String(p.id)">
                  {{ p.label }}{{ p.category === 'upcoming' ? ' · akan datang' : '' }}
                </option>
              </FormSelect>

              <div v-else-if="selectedPeriodLabel"
                class="bg-slate-50 px-3 py-2 border border-slate-200 rounded-md font-strong text-slate-700">
                {{ selectedPeriodLabel }}
              </div>

              <div v-else class="bg-amber-50 px-3 py-2 border border-amber-200 rounded-md font-body !text-amber-700">
                Belum ada periode harga aktif — hubungi Procurement.
              </div>

              <small v-if="fieldError('masa_berlaku') || fieldError('sampai_dengan')" class="block input-error-text">
                {{ fieldError('masa_berlaku') || fieldError('sampai_dengan') }}
              </small>
              <small v-else-if="periodeIsChoice && form.masa_berlaku" class="block mt-1 font-caption text-slate-500">
                {{ form.masa_berlaku }} s/d {{ form.sampai_dengan }}
              </small>
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-7">
          <div class="px-4 py-3 border border-slate-200 rounded-xl">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12 md:col-span-6">
                <FormLabel>Kepada (Perusahaan / Dept.)</FormLabel>
                <div class="mt-1 font-strong">{{ selectedCustomer?.company_name || '-' }}</div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Kontak Tujuan
                  <RequiredAsterisk />
                </FormLabel>
                <div v-if="!form.id_customer" class="mt-1 font-body text-slate-500">
                  Pilih Customer terlebih dahulu
                </div>
                <div v-else-if="customerContactsLoading"
                  class="inline-flex items-center gap-2 mt-1 font-body text-slate-500">
                  <Lucide icon="Loader2" class="w-4 h-4 animate-spin" />
                  Memuat kontak…
                </div>
                <div v-else-if="customerContacts.length === 0" class="mt-1 font-body text-slate-500">
                  Belum ada kontak untuk customer ini
                </div>
                <TomSelect v-else v-model="form.customer_contact_id" :options="{
                  placeholder: 'Pilih Kontak Tujuan...',
                  dropdownParent: 'body' as const,
                }" class="w-full" :class="inputClass('customer_contact_id')">
                  <option v-for="c in customerContacts" :key="c.id" :value="String(c.id)">
                    {{ c.full_name }}{{ c.position ? ` (${c.position})` : '' }}
                  </option>
                </TomSelect>
                <small v-if="fieldError('customer_contact_id')" class="block input-error-text">{{
                  fieldError('customer_contact_id') }}</small>
                <Button type="button" size="sm" :variant="customerContacts.length === 0 ? 'primary' : 'outline-primary'"
                  class="inline-flex items-center gap-2 mt-2" :disabled="!form.id_customer || customerContactsLoading"
                  @click="openNewContactForm">
                  <Lucide icon="PlusCircle" class="w-4 h-4" />
                  Tambah Kontak Baru
                </Button>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Jabatan</FormLabel>
                <div class="mt-1 font-strong">{{ selectedContact?.position || '-' }}</div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Telepon</FormLabel>
                <div class="mt-1 font-strong">{{ selectedContact?.mobile || '-' }}</div>
              </div>

              <div class="col-span-12">
                <FormLabel>Alamat</FormLabel>
                <div class="mt-1 font-strong whitespace-pre-line">{{ selectedCustomer?.company_address || '-' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardSection>

    <CardSection title="Detail Pengiriman & Daftar Produk"
      description="Instrumen pengiriman, tujuan kirim dan daftar produk penawaran" icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Type Pengiriman
                <RequiredAsterisk />
              </FormLabel>
              <FormSelect v-model="form.type_pengiriman" class="w-full" :class="inputClass('type_pengiriman')">
                <option value="" disabled>Pilih Type Pengiriman…</option>
                <option value="PROJECT">Project</option>
                <option value="RETAIL">Retail</option>
              </FormSelect>
              <small v-if="fieldError('type_pengiriman')" class="block input-error-text">{{
                fieldError('type_pengiriman')
              }}</small>
            </div>

            <div>
              <FormLabel>Metode
                <RequiredAsterisk />
              </FormLabel>
              <FormSelect v-model="form.metode" class="w-full" :class="inputClass('metode')">
                <option value="" disabled>Pilih Metode…</option>
                <template v-if="form.type_pengiriman === 'PROJECT'">
                  <option value="FOB">Free On Board (FOB)</option>
                  <option value="CIF">Cost Insurance & Freight (CIF)</option>
                  <option value="DAP">Delivery At Place (DAP)</option>
                </template>
                <template v-else-if="form.type_pengiriman === 'RETAIL'">
                  <option value="FOT">Free On Truck (FOT)</option>
                  <option value="FRANCO">Franco</option>
                </template>
              </FormSelect>
              <small v-if="fieldError('metode')" class="block input-error-text">{{ fieldError('metode')
              }}</small>
            </div>
            <div v-if="form.metode === 'CIF' || form.metode === 'DAP'"
              class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
              <h4 class="mb-3 font-section">Ongkos Kapal</h4>
              <div class="gap-4 grid grid-cols-12">
                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Transportir</FormLabel>
                  <FormSelect v-model="oaKapalInput.id_transportir" :key="oaSelectKey">
                    <option value="">Pilih Transportir</option>
                    <option v-for="t in transportirs" :key="t.id" :value="String(t.id)">{{ t.nama_perusahaan }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Wilayah Angkut</FormLabel>
                  <FormSelect v-model="oaKapalInput.id_angkut_wilayah" :key="oaSelectKey">
                    <option value="">Pilih Wilayah</option>
                    <option v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                      {{ w.province?.name || w.provinsi?.nama_provinsi }} - {{ w.regency?.name ||
                        w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Volume</FormLabel>
                  <FormSelect v-model="oaKapalInput.id_volume" :key="oaSelectKey">
                    <option value="">Pilih Volume</option>
                    <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12">
                  <CurrencyField label="Ongkos Kapal" :model-value="oaKapal" :readonly="true" />
                </div>
              </div>
            </div>

            <div v-if="form.metode === 'DAP' || form.metode === 'FOT'"
              class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
              <h4 class="mb-3 font-section">Ongkos Truck</h4>
              <div class="gap-4 grid grid-cols-12">
                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Transportir</FormLabel>
                  <FormSelect v-model="oaTruckInput.id_transportir" :key="oaSelectKey">
                    <option value="">Pilih Transportir</option>
                    <option v-for="t in transportirs" :key="t.id" :value="String(t.id)">{{ t.nama_perusahaan }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Wilayah Angkut</FormLabel>
                  <FormSelect v-model="oaTruckInput.id_angkut_wilayah" :key="oaSelectKey">
                    <option value="">Pilih Wilayah</option>
                    <option v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                      {{ w.province?.name || w.provinsi?.nama_provinsi }} - {{ w.regency?.name ||
                        w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12 md:col-span-4">
                  <FormLabel>Volume</FormLabel>
                  <FormSelect v-model="oaTruckInput.id_volume" :key="oaSelectKey">
                    <option value="">Pilih Volume</option>
                    <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}
                    </option>
                  </FormSelect>
                </div>

                <div class="col-span-12">
                  <CurrencyField label="Ongkos Truck" :model-value="oaTruck" :readonly="true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Lokasi Pengiriman</FormLabel>
              <FormTextarea v-model="form.lokasi_pengiriman" :rows="3" :auto-resize="true"
                placeholder="Lokasi pengiriman..." />
            </div>

            <div>
              <FormLabel>Titik Serah Terima & T&C Bongkar</FormLabel>
              <FormTextarea v-model="form.keterangan" :rows="3" :auto-resize="true"
                placeholder="Titik serah terima & T&C bongkar..." />
            </div>
          </div>
        </div>
      </div>

      <hr class="my-4" />

      <div class="flex sm:flex-row flex-col sm:items-center justify-between gap-2">
        <p class="font-body !text-slate-500">
          Item penawaran disusun lewat modal generator berdasarkan periode &amp; source harga.
        </p>
        <Button type="button" variant="primary" class="inline-flex justify-center items-center gap-2 whitespace-nowrap"
          @click="openItemsModal">
          <Lucide icon="Plus" class="w-4 h-4" />
          {{ form.items.length ? 'Ubah Item Penawaran' : 'Susun Item Penawaran' }}
        </Button>
      </div>
      <div class="mt-4 border border-slate-200 rounded-xl overflow-x-auto">
        <table class="divide-y divide-slate-200 w-full min-w-[760px]">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 w-12 font-label text-center">No</th>
              <th class="px-4 py-3 font-label text-left">Produk</th>
              <th class="px-4 py-3 font-label text-left">Source</th>
              <th class="px-4 py-3 w-24 font-label text-right">Persen</th>
              <th class="px-4 py-3 w-32 font-label text-right">Volume</th>
              <th class="px-4 py-3 w-44 font-label text-right">Harga Price List</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50 transition">
              <td class="px-4 py-3 font-num text-center">{{ idx + 1 }}.</td>
              <td class="px-4 py-3 font-strong">{{ produkLabel(item.id_produk) }}</td>
              <td class="px-4 py-3 font-body">{{ item.source_name || '-' }}</td>
              <td class="px-4 py-3 font-num text-right">{{ item.persen }}%</td>
              <td class="px-4 py-3 font-num text-right">{{ item.volume_order || 0 }}</td>
              <td class="px-4 py-3 font-num text-right">{{ formatCurrency(item.harga_price_list || 0) }}</td>
            </tr>
            <tr v-if="form.items.length === 0">
              <td colspan="6" class="px-4 py-10 font-body text-center !text-slate-400">
                Belum ada item. Klik "Susun Item Penawaran".
              </td>
            </tr>
          </tbody>

          <tfoot v-if="form.items.length" class="bg-slate-50 border-slate-200 border-t">
            <tr>
              <td class="px-4 py-3 font-strong text-right" colspan="3">Total</td>
              <td class="px-4 py-3 font-num text-right"
                :class="totalPersenNumber !== 100 ? 'text-red-600' : 'text-slate-800'">
                {{ totalPersenDisplay }}%
              </td>
              <td class="px-4 py-3 font-num text-right">{{ totalVolume }}</td>
              <td class="px-4 py-3 font-num text-right">{{ formatCurrency(avgHargaPriceList) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </CardSection>

    <CardSection title="Pembayaran & Lainnya" description="Metode pembayaran dan detail lainnya" icon="Wallet"
      icon-class="bg-amber-100 text-amber-600">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12" :class="isProenergi ? 'md:col-span-6' : ''">
                <FormLabel>Tipe Pembayaran
                  <RequiredAsterisk />
                </FormLabel>
                <FormSelect v-model="form.tipe_pembayaran" class="w-full" :class="inputClass('tipe_pembayaran')">
                  <option value="" disabled>Pilih…</option>
                  <option value="COD">COD</option>
                  <option value="CBD">CBD</option>
                  <option value="TOP 7">TOP 7</option>
                  <option value="TOP 14">TOP 14</option>
                  <option v-if="!isProenergi" value="TOP 30">TOP 30</option>
                  <option value="CUSTOM">CUSTOM</option>
                </FormSelect>
                <small v-if="fieldError('tipe_pembayaran')" class="block input-error-text">{{
                  fieldError('tipe_pembayaran')
                }}</small>
              </div>

              <div v-if="cfg.showAcuan" class="col-span-12 md:col-span-6">
                <FormLabel>Acuan Pembayaran</FormLabel>
                <FormSelect v-model="form.acuan_pembayaran" class="w-full">
                  <option value="" disabled>Pilih…</option>
                  <option value="After loading">After loading</option>
                  <option value="Before loading">Before loading</option>
                  <option value="After unloading">After unloading</option>
                  <option value="Before unloading">Before unloading</option>
                  <option value="After invoice received">After invoice received</option>
                </FormSelect>
              </div>
            </div>

            <transition name="fade">
              <div v-if="form.tipe_pembayaran === 'CUSTOM'"
                class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
                <h4 class="mb-3 font-section">Detail Pembayaran Custom</h4>
                <div class="flex flex-col gap-4">
                  <div class="">
                    <FormLabel>Down Payment (%)</FormLabel>
                    <div class="flex flex-wrap items-center gap-2">
                      <NumberField class="w-20" v-model="form.dp_persen" placeholder="100" suffix="%" :min="0"
                        :max="100" :decimals="0" :error="fieldError('dp_persen')" />
                      <span class="font-body">After</span>
                      <FormInput v-model="form.dp_keterangan" type="text" class="flex-1 min-w-40"
                        placeholder="PO / 7 days" />
                    </div>
                    <small v-if="fieldError('dp_persen')" class="block input-error-text">{{
                      fieldError('dp_persen')
                    }}</small>
                  </div>

                  <div class="">
                    <FormLabel>Repayment (%)</FormLabel>
                    <div class="flex flex-wrap items-center gap-2">
                      <FormInput v-model="form.repayment_persen" type="text" inputmode="numeric" placeholder="80"
                        class="w-20 text-right" :class="inputClass('repayment_persen')"
                        @input="formatNumeric(form, 'repayment_persen', $event)" />
                      <span class="font-body">% TOP</span>
                      <FormInput v-model="form.repayment_hari" type="text" inputmode="numeric" placeholder="7"
                        class="w-20 text-right" @input="formatNumeric(form, 'repayment_hari', $event)" />
                      <span class="font-body">days</span>
                    </div>
                    <small v-if="fieldError('repayment_persen')" class="block input-error-text">{{
                      fieldError('repayment_persen')
                    }}</small>
                  </div>
                </div>
                <p class="mt-2 font-caption">Contoh: <b>DP 20% after PO</b>, <b>Repayment 80% TOP 7 days</b>.
                </p>
              </div>
            </transition>

            <div>
              <FormLabel>Metode Pemesanan</FormLabel>
              <FormInput v-model="form.order_method" type="text" placeholder="Metode pemesanan…" />
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div class="flex flex-col gap-4">
              <div>
                <FormLabel>Toleransi Penyusutan</FormLabel>
                <div class="relative">
                  <FormInput v-model="form.toleransi_penyusutan" type="text" inputmode="decimal" placeholder="0"
                    class="pr-8 text-right" @input="formatDecimalInput(form, 'toleransi_penyusutan', $event)" />
                  <span class="top-2.5 right-3 absolute font-caption">%</span>
                </div>
              </div>

              <div>
                <FormLabel>Abrasi</FormLabel>
                <FormInput v-model="form.abrasi" type="text" placeholder="Contoh isian: 0-5% atau sesuai kondisi" />
              </div>

              <div>
                <CurrencyField v-model="form.refund" label="Refund" />
              </div>

              <div>
                <CurrencyField v-model="form.other_cost" label="Other Cost" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardSection>

    <CardSection title="Perhitungan Harga Dasar" description="Komponen harga dan total akhir" icon="Calculator"
      icon-class="bg-emerald-100 text-emerald-600">
      <div class="overflow-x-auto">
        <Table bordered sm class="font-body">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="w-12 font-label text-center">No</Table.Th>
              <Table.Th class="font-label text-left">Rincian</Table.Th>
              <Table.Th class="w-60 font-label text-right">Harga (Rp)</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody class="bg-white">
            <Table.Tr>
              <Table.Td class="text-slate-500 text-center">1.</Table.Td>
              <Table.Td class="text-slate-700">Harga Dasar</Table.Td>
              <Table.Td>
                <CurrencyField v-model="form.harga_dasar" />
              </Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td class="text-slate-500 text-center">2.</Table.Td>
              <Table.Td class="text-slate-700">Ongkos Angkut (OAT per Volume)</Table.Td>
              <Table.Td>
                <CurrencyField v-model="form.oat" :error="fieldError('oat')" />
              </Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="2" class="font-strong text-right">Subtotal (Harga Dasar + OA)</Table.Td>
              <Table.Td class="font-num-lg text-right">{{ formatCurrency(dppHargaDasar) }}</Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="2" class="font-strong text-right">PPN (11%)</Table.Td>
              <Table.Td class="font-num-lg text-right">{{ formatCurrency(ppnHargaDasar) }}</Table.Td>
            </Table.Tr>

            <Table.Tr>
              <Table.Td colspan="2" class="font-header text-right">TOTAL</Table.Td>
              <Table.Td class="font-num-lg !text-emerald-700 text-right">{{
                formatCurrency(grandTotalHargaDasar) }}</Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </CardSection>

    <ItemsGeneratorModal :open="itemsModalOpen" :price-period-id="selectedPeriodId"
      :period-label="selectedPeriodLabelText" :cabangs="cabangs" :produks="produks" :initial-items="form.items"
      :use-pe="cfg.usePe" @close="itemsModalOpen = false" @save="onItemsSaved" />

    <FormModal :open="newContactOpen" title="Tambah Kontak Baru"
      description="Kontak baru ini langsung tersimpan di master data customer." :loading="newContactSaving"
      :error="newContactError" submit-text="Tambah" submit-icon="PlusCircle" @close="newContactOpen = false"
      @submit="submitNewContact">
      <div class="space-y-3">
        <div>
          <FormLabel>Nama Lengkap
            <RequiredAsterisk />
          </FormLabel>
          <FormInput v-model="newContactForm.full_name" placeholder="Nama lengkap" />
        </div>
        <div>
          <FormLabel>Posisi/Jabatan</FormLabel>
          <FormInput v-model="newContactForm.position" placeholder="Contoh: Purchasing Manager" />
        </div>
        <div class="gap-3 grid grid-cols-2">
          <div>
            <FormLabel>Telepon</FormLabel>
            <FormInput v-model="newContactForm.phone" placeholder="021-xxxxxxx" />
          </div>
          <div>
            <FormLabel>Mobile</FormLabel>
            <FormInput v-model="newContactForm.mobile" placeholder="08xx-xxxx-xxxx" />
          </div>
        </div>
        <div>
          <FormLabel>Email</FormLabel>
          <FormInput v-model="newContactForm.email" type="email" placeholder="nama@email.com" />
        </div>
      </div>
    </FormModal>

    <template #sidebar>
      <CardSection title="Catatan & Syarat" description="Informasi tambahan penawaran" icon="StickyNote"
        icon-class="bg-rose-100 text-rose-600">
        <div class="space-y-4">
          <div>
            <FormLabel>Catatan</FormLabel>
            <FormTextarea v-model="form.catatan" :rows="3" placeholder="Catatan tambahan…" />
          </div>

          <div>
            <FormLabel>Syarat & Ketentuan</FormLabel>
            <FormTextarea v-model="form.syarat_ketentuan" :rows="5" placeholder="Syarat dan ketentuan…" />
          </div>
        </div>
      </CardSection>

      <CardSection title="Lampiran Tambahan" description="Rincian tambahan di luar template standar" icon="Paperclip"
        icon-class="bg-rose-100 text-rose-600">
        <div>
          <FormLabel>Lampiran Tambahan</FormLabel>
          <FormTextarea v-model="form.lampiran_tambahan" :rows="5" placeholder="Lampiran tambahan…" />
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
