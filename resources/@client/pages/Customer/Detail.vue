<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, defineComponent, h, type PropType } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import { Tab } from '@/components/Base/Headless'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

/* Type: opsi remote-select untuk Cabang & Wilayah OA (dipakai Tab 3 — LCR) */
type SimpleOption<T = any> = { value: number; label: string; raw?: T }
interface CabangOption<T = any> extends SimpleOption<T> {
  id_wilayah: number | null
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

/* State: load utama & resolusi id_verification (drive Tab 1/2/4) */
const loading = ref(true)
const customerSummary = ref<any>({})
const idVerification = ref<number | null>(null)
const hasVerification = computed(() => idVerification.value !== null)

/* State: tab bar — icon & label per tab (dipakai Tab.List di template) */
const tabItems = [
  { label: '1. Data Customer', icon: 'FileText' as const },
  { label: '2. Sales Review', icon: 'ClipboardEdit' as const },
  { label: '3. LCR', icon: 'ClipboardList' as const },
  { label: '4. Credit Application / TOP', icon: 'Wallet' as const },
]

/* State: Tab 1 — Data Customer (read-only, dari reviewShow) */
const verifCustomer = ref<any>({})
const legal = ref<any>({})
const finance = ref<any>({})
const logistik = ref<any>({})

/* State: Tab 2 — Sales Review (editable) */
const reviewForm = reactive({
  credit_limit_proposed: 0,
  marketing_notes: '',
  flow_review: '',
  invoice_schedule: '',
  payment_authority: '',
  existing_vendor: '',
  history: '',
  depot_location: '',
  opportunities: '',
})
const reviewSaving = ref(false)
const forwardDialogOpen = ref(false)

/* State: Tab 3 — LCR (editable, create/edit via GET /api/customer-lcrs?id_customer=) */
const lcrLoading = ref(true)
const lcrSaving = ref(false)
const lcrId = ref<number | null>(null)
const lcrErrors = ref<Record<string, string[]>>({})
const lcrMediaOpen = ref(false)

const lcrForm = ref<any>({
  id_cabang: null,
  id_wilayah: null,
  id_wil_oa: null,
  alamat_survey: '',
  tgl_survey: '',
  review: '',
  jenis_usaha: '',
  website: '',
  logistik_result: 1,
  toleransi: '',
  latitude_lokasi: '',
  longitude_lokasi: '',
  link_google_maps: '',
  jarak_depot: '',
  rute_lokasi: '',
  note_lokasi: '',
  max_truk: '',
  min_vol_kirim: '',
  penjelasan_bongkar: '',
  catatan_tangki: '',
  catatan_kapal: '',
  flag_approval: 0,
  layout_lokasi: [],
  layout_bongkar: [],
  kondisi_jalan: [],
  kantor_perusahaan: [],
  fasilitas_storage: [],
  inlet_pipa: [],
  alat_ukur_gambar: [],
  media_datar: [],
  keterangan_lain: [],
})

const lcrSurveyorRows = ref<string[]>([''])
const lcrHasilRows = ref<string[]>([''])
const lcrProdukVolRows = ref<{ produk: string; volbul: string }[]>([{ produk: '', volbul: '' }])
const lcrPicRows = ref<{ nama: string; posisi: string; telepon: string }[]>([{ nama: '', posisi: '', telepon: '' }])
const lcrKompetitorRows = ref<string[]>([''])

const lcrTangkiRows = ref<any[]>([{ tipe: '', kapasitas: '', jumlah: '', produk: '', inlet: '', ukuran: '' }])
const lcrPendukungRows = ref<any[]>([{ pompa: '', aliran: '', selang: '', valve: '', ground: '', sinyal: '' }])
const lcrQuantityTangkiRows = ref<any[]>([{ alat: '', merk: '', tera: '', masa: '', flowmeter: '' }])
const lcrQualityTangkiRows = ref<any[]>([{ spec: '', lab: '', coq: '' }])

const lcrKapalRows = ref<any[]>([{ tipe: '', kapasitas: '', jumlah: '', inlet: '', ukuran: '', metode: '' }])
const lcrJettyRows = ref<any[]>([{ loa: '', pbl: '', lws: '', kekuatan: '', izin: '', syarat: '' }])
const lcrQuantityKapalRows = ref<any[]>([{ alat: '', merk: '', tera: '', masa: '', flowmeter: '' }])
const lcrQualityKapalRows = ref<any[]>([{ spec: '', lab: '', coq: '' }])

const lcrMedia = reactive({
  layout_lokasi: [] as any[],
  layout_bongkar: [] as any[],
  kondisi_jalan: [] as any[],
  kantor_perusahaan: [] as any[],
  fasilitas_storage: [] as any[],
  inlet_pipa: [] as any[],
  alat_ukur_gambar: [] as any[],
  media_datar: [] as any[],
  keterangan_lain: [] as any[],
})

/* State: opsi dropdown Tab 3 — LCR (identik dgn CustomerLcrForm.vue) */
const jenisUsahaOptions = ['Manufacture', 'Trading', 'Retail', 'Transport', 'Bunker', 'Lainnya']
const maxTrukOptions = ['8 KL', '16 KL', '24 KL', '32 KL']
const tangkiTypeOptions = ['Fixed', 'Mobile']
const inletOptions = ['Manhole', 'Pipa', 'Quick Coupling']
const ukuranOptions = ['1 In', '1.5 In', '2 In', '3 In', '4 In']
const pompaOptions = ['Transportir', 'Customer']
const adaTidakOptions = ['Ada', 'Tidak']
const yaTidakOptions = ['Ya', 'Tidak']
const sinyalOptions = ['Telkomsel', 'Indosat', 'XL', '3', 'Smartfren', 'Lainnya']
const specOptions = ['Migas', 'Non Migas']

/* Computed: Tab 1 — ringkasan Data Customer, dikelompokkan per section (pola summarySections dari CustomerUpdateForm.vue) */
const summarySections = computed(() => {
  const c = legal.value?.corporate || {}
  const p = legal.value?.pic || {}
  const pay = finance.value?.payment || {}
  const s = logistik.value?.supply || {}
  const jam = s.operational_from && s.operational_to
    ? `${s.operational_from} - ${s.operational_to}`
    : (s.operational_from || s.operational_to || null)

  return [
    {
      title: 'Corporate Details',
      rows: [
        { label: 'Nama Perusahaan', value: dash(c.nama || verifCustomer.value?.nama_perusahaan) },
        { label: 'Holding', value: dash(c.holding) },
        { label: 'Alamat NPWP', value: dash(c.alamat) },
        { label: 'Kelurahan', value: dash(c.kelurahan) },
        { label: 'Kecamatan', value: dash(c.kecamatan) },
        { label: 'Kota/Kabupaten', value: dash(c.kota) },
        { label: 'Provinsi', value: dash(c.provinsi) },
        { label: 'Kode Pos', value: dash(c.postal_code) },
        { label: 'Telepon', value: dash(c.telepon || verifCustomer.value?.telepon) },
        { label: 'Fax', value: dash(c.fax || verifCustomer.value?.fax) },
      ],
    },
    {
      title: 'Person in Charge',
      rows: [
        { label: 'Director / Owner', value: dash(p.owner) },
        { label: 'Procurement', value: dash(p.procurement) },
        { label: 'Finance', value: dash(p.finance) },
        { label: 'Site / Fuelman PIC', value: dash(p.fuelman) },
      ],
    },
    {
      title: 'Payment Term & Banking Detail',
      rows: [
        { label: 'Pricing Method', value: dash(pay.pricing_method) },
        { label: 'Payment Method', value: dash(pay.payment_method) },
        { label: 'Term of Payment', value: dash(pay.term) },
        { label: 'Bank Name', value: dash(pay.bank_name) },
        { label: 'Currency', value: dash(pay.currency) },
        { label: 'Bank Address', value: dash(pay.bank_address) },
        { label: 'Account Number', value: dash(pay.account_number) },
        { label: 'Credit Facility', value: dash(pay.has_credit ? 'Ya' : 'Tidak') },
      ],
    },
    {
      title: 'Supply Scheme',
      rows: [
        { label: 'Scheme Details', value: dash(s.scheme_details) },
        { label: 'Specify Product', value: dash(s.specify_product) },
        { label: 'Volume per Bulan', value: dash(s.volume_per_month) },
        { label: 'Jam Operasional', value: dash(jam) },
        { label: 'INCO Terms', value: dash(s.inco_terms) },
      ],
    },
  ]
})

/* Computed: Tab 3 — mode create/edit & preview peta */
const lcrMode = computed(() => (lcrId.value ? 'edit' : 'create'))
const lcrMapUrl = computed(() => {
  const lat = parseFloat(lcrForm.value.latitude_lokasi)
  const lon = parseFloat(lcrForm.value.longitude_lokasi)
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) return ''
  const q = encodeURIComponent(`${lat},${lon}`)
  return `https://maps.google.com/maps?q=${q}&z=15&output=embed`
})

onMounted(loadCustomer)
onMounted(loadLcr)

/* Watch: auto isi id_wilayah dari cabang yang dipilih (Tab 3) */
watch(() => lcrForm.value.id_cabang, async (id) => {
  if (!id) {
    lcrForm.value.id_wilayah = null
    return
  }
  try {
    const cb = await getCabangById(id)
    lcrForm.value.id_wilayah = cb?.id_wilayah ?? null
  } catch {
    lcrForm.value.id_wilayah = null
  }
})

/* Fetch: resolve id_verification dari GET /api/customers/{id}, lalu load data Tab 1 & 2 */
async function loadCustomer() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}`)
    customerSummary.value = data || {}
    idVerification.value = data?.latest_verification?.id_verification ?? null

    if (idVerification.value) {
      await loadVerificationData()
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer.')
  } finally {
    loading.value = false
  }
}

async function loadVerificationData() {
  try {
    const [metaRes, reviewRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${idVerification.value}`),
      axios.get(`/api/review/customer-verifications/${idVerification.value}/review`).catch(() => ({ data: null })),
    ])

    const meta = metaRes.data || {}
    verifCustomer.value = meta.customer || {}
    legal.value = meta.legal || {}
    finance.value = meta.finance || {}
    logistik.value = meta.logistik || {}

    const review = reviewRes?.data?.review
    if (review) {
      reviewForm.credit_limit_proposed = Number(review.review1) || 0
      reviewForm.marketing_notes = review.review_summary || ''
      reviewForm.flow_review = review.alur_proses_periksaan || ''
      reviewForm.invoice_schedule = review.jadwal_penerimaan || ''
      reviewForm.payment_authority = review.review2 || ''
      reviewForm.existing_vendor = review.review3 || ''
      reviewForm.history = review.background_bisnis || ''
      reviewForm.depot_location = review.lokasi_depo || ''
      reviewForm.opportunities = review.opportunity_bisnis || ''
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data verifikasi customer.')
  }
}

/* Fetch: Tab 3 — deteksi create-vs-edit dari GET /api/customer-lcrs?id_customer= */
async function loadLcr() {
  lcrLoading.value = true
  try {
    const { data } = await axios.get('/api/customer-lcrs', { params: { id_customer: idCustomer } })
    const rows = data?.data ?? []
    if (rows.length > 0) {
      hydrateLcrForm(rows[0])
    } else {
      lcrId.value = null
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data LCR.')
  } finally {
    lcrLoading.value = false
  }
}

function hydrateLcrForm(record: any) {
  lcrId.value = record.id_lcr ?? null
  Object.assign(lcrForm.value, {
    id_cabang: null,
    id_wilayah: record.id_wilayah ?? null,
    id_wil_oa: record.id_wil_oa ?? null,
    alamat_survey: record.alamat_survey ?? '',
    tgl_survey: record.tgl_survey ?? '',
    review: record.review ?? '',
    jenis_usaha: record.jenis_usaha ?? '',
    website: record.website ?? '',
    logistik_result: record.logistik_result ?? 1,
    toleransi: record.toleransi ?? '',
    latitude_lokasi: record.latitude_lokasi ?? '',
    longitude_lokasi: record.longitude_lokasi ?? '',
    link_google_maps: record.link_google_maps ?? '',
    jarak_depot: record.jarak_depot ?? '',
    rute_lokasi: record.rute_lokasi ?? '',
    note_lokasi: record.note_lokasi ?? '',
    max_truk: record.max_truk ?? '',
    min_vol_kirim: record.min_vol_kirim ?? '',
    penjelasan_bongkar: record.penjelasan_bongkar ?? '',
    catatan_tangki: record.catatan_tangki ?? '',
    catatan_kapal: record.catatan_kapal ?? '',
    flag_approval: record.flag_approval ?? 0,
    layout_lokasi: record.layout_lokasi ?? [],
    layout_bongkar: record.layout_bongkar ?? [],
    kondisi_jalan: record.kondisi_jalan ?? [],
    kantor_perusahaan: record.kantor_perusahaan ?? [],
    fasilitas_storage: record.fasilitas_storage ?? [],
    inlet_pipa: record.inlet_pipa ?? [],
    alat_ukur_gambar: record.alat_ukur_gambar ?? [],
    media_datar: record.media_datar ?? [],
    keterangan_lain: record.keterangan_lain ?? [],
  })

  lcrSurveyorRows.value = record.nama_surveyor?.length ? record.nama_surveyor : ['']
  lcrHasilRows.value = record.hasilsurv?.length ? record.hasilsurv : ['']
  lcrProdukVolRows.value = record.produkvol?.length ? record.produkvol : [{ produk: '', volbul: '' }]
  lcrPicRows.value = record.picustomer?.length ? record.picustomer : [{ nama: '', posisi: '', telepon: '' }]
  lcrKompetitorRows.value = record.kompetitor?.length ? record.kompetitor : ['']

  lcrTangkiRows.value = record.tangki?.length ? record.tangki : [{ tipe: '', kapasitas: '', jumlah: '', produk: '', inlet: '', ukuran: '' }]
  lcrPendukungRows.value = record.pendukung?.length ? record.pendukung : [{ pompa: '', aliran: '', selang: '', valve: '', ground: '', sinyal: '' }]
  lcrQuantityTangkiRows.value = record.quantity_tangki?.length ? record.quantity_tangki : [{ alat: '', merk: '', tera: '', masa: '', flowmeter: '' }]
  lcrQualityTangkiRows.value = record.quality_tangki?.length ? record.quality_tangki : [{ spec: '', lab: '', coq: '' }]

  lcrKapalRows.value = record.kapal?.length ? record.kapal : [{ tipe: '', kapasitas: '', jumlah: '', inlet: '', ukuran: '', metode: '' }]
  lcrJettyRows.value = record.jetty?.length ? record.jetty : [{ loa: '', pbl: '', lws: '', kekuatan: '', izin: '', syarat: '' }]
  lcrQuantityKapalRows.value = record.quantity_kapal?.length ? record.quantity_kapal : [{ alat: '', merk: '', tera: '', masa: '', flowmeter: '' }]
  lcrQualityKapalRows.value = record.quality_kapal?.length ? record.quality_kapal : [{ spec: '', lab: '', coq: '' }]
}

async function saveReview() {
  if (!idVerification.value) return
  reviewSaving.value = true
  try {
    await axios.post(`/api/review/customer-verifications/${idVerification.value}/review`, {
      credit_limit_diajukan: reviewForm.credit_limit_proposed || undefined,
      review1: reviewForm.credit_limit_proposed || undefined,
      review_summary: reviewForm.marketing_notes || undefined,
      alur_proses_periksaan: reviewForm.flow_review || undefined,
      jadwal_penerimaan: reviewForm.invoice_schedule || undefined,
      review2: reviewForm.payment_authority || undefined,
      review3: reviewForm.existing_vendor || undefined,
      background_bisnis: reviewForm.history || undefined,
      lokasi_depo: reviewForm.depot_location || undefined,
      opportunity_bisnis: reviewForm.opportunities || undefined,
    })

    forwardDialogOpen.value = false
    success('Berhasil', 'Data review berhasil disimpan & diforward ke Admin Finance.')
    await loadVerificationData()
  } catch (e: any) {
    forwardDialogOpen.value = false
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memforward data ke Admin Finance.')
  } finally {
    reviewSaving.value = false
  }
}

/* Action: Tab 3 — simpan data LCR (create atau update, tergantung lcrMode) */
async function submitLcr() {
  lcrForm.value.nama_surveyor = lcrSurveyorRows.value
  lcrForm.value.hasilsurv = lcrHasilRows.value
  lcrForm.value.produkvol = lcrProdukVolRows.value
  lcrForm.value.picustomer = lcrPicRows.value
  lcrForm.value.kompetitor = lcrKompetitorRows.value
  lcrForm.value.tangki = lcrTangkiRows.value
  lcrForm.value.pendukung = lcrPendukungRows.value
  lcrForm.value.quantity_tangki = lcrQuantityTangkiRows.value
  lcrForm.value.quality_tangki = lcrQualityTangkiRows.value
  lcrForm.value.kapal = lcrKapalRows.value
  lcrForm.value.jetty = lcrJettyRows.value
  lcrForm.value.quantity_kapal = lcrQuantityKapalRows.value
  lcrForm.value.quality_kapal = lcrQualityKapalRows.value

  lcrSaving.value = true
  lcrErrors.value = {}

  try {
    if (lcrMode.value === 'edit' && lcrId.value) {
      await axios.put(`/api/customer-lcrs/${lcrId.value}`, lcrForm.value)
      success('Berhasil', 'Data LCR berhasil diperbarui.')
    } else {
      const { data } = await axios.post('/api/customer-lcrs', { ...lcrForm.value, id_customer: idCustomer })
      lcrId.value = data?.id_lcr ?? null
      success('Berhasil', 'Data LCR berhasil disimpan.')
    }
  } catch (e: any) {
    if (e.response?.status === 422) {
      lcrErrors.value = e.response?.data?.errors || {}
      const firstMsg = Object.values(lcrErrors.value)[0]?.[0] || 'Periksa kembali input Anda.'
      notifyError('Validasi gagal', firstMsg)
      return
    }
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan data LCR.')
  } finally {
    lcrSaving.value = false
  }
}

/* Action: Tab 3 — kelola foto pendukung (upload langsung ke uploadImage, disimpan via PATCH) */
function openLcrMediaModal() {
  const keys = Object.keys(lcrMedia) as (keyof typeof lcrMedia)[]
  for (const k of keys) {
    const src = (lcrForm.value as any)[k]
    lcrMedia[k] = Array.isArray(src) ? [...src] : []
  }
  lcrMediaOpen.value = true
}

async function onLcrMediaPick(field: keyof typeof lcrMedia, ev: Event) {
  const input = ev.target as HTMLInputElement
  const files = Array.from(input.files || [])
  for (const f of files) {
    const fd = new FormData()
    fd.append('file', f)
    const { data } = await axios.post('/api/uploads/lcr-image', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    lcrMedia[field].push({ path: data.path, url: data.url, caption: '' })
  }
  input.value = ''
}

function removeLcrMedia(field: keyof typeof lcrMedia, idx: number) {
  lcrMedia[field].splice(idx, 1)
}

async function saveLcrMedia() {
  if (!lcrId.value) return
  try {
    await axios.patch(`/api/customer-lcrs/${lcrId.value}`, { ...lcrMedia })
    Object.assign(lcrForm.value, lcrMedia)
    lcrMediaOpen.value = false
    success('Berhasil', 'Foto pendukung berhasil disimpan.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan foto pendukung.')
  }
}

function goBack() {
  router.push({ name: 'customers-list' })
}

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}

/* Helper: Tab 3 — error field, link peta, add/remove baris dinamis */
function lcrFirstError(field: string): string | null {
  if (!lcrErrors.value) return null
  if (lcrErrors.value[field]?.length) return lcrErrors.value[field][0]
  const key = Object.keys(lcrErrors.value).find(k => k === field || k.startsWith(field + '.'))
  return key ? (lcrErrors.value[key]?.[0] ?? null) : null
}

function buildLcrMapLink() {
  const lat = lcrForm.value.latitude_lokasi
  const lon = lcrForm.value.longitude_lokasi
  if (lat && lon) {
    lcrForm.value.link_google_maps = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`
  }
}

const addLcrSurveyor = () => lcrSurveyorRows.value.push('')
const removeLcrSurveyor = (i: number) => lcrSurveyorRows.value.splice(i, 1)
const addLcrHasil = () => lcrHasilRows.value.push('')
const removeLcrHasil = (i: number) => lcrHasilRows.value.splice(i, 1)
const addLcrProdukVol = () => lcrProdukVolRows.value.push({ produk: '', volbul: '' })
const removeLcrProdukVol = (i: number) => lcrProdukVolRows.value.splice(i, 1)
const addLcrPic = () => lcrPicRows.value.push({ nama: '', posisi: '', telepon: '' })
const removeLcrPic = (i: number) => lcrPicRows.value.splice(i, 1)
const addLcrKompetitor = () => lcrKompetitorRows.value.push('')
const removeLcrKompetitor = (i: number) => lcrKompetitorRows.value.splice(i, 1)
const addLcrTangki = () => lcrTangkiRows.value.push({ tipe: '', kapasitas: '', jumlah: '', produk: '', inlet: '', ukuran: '' })
const removeLcrTangki = (i: number) => lcrTangkiRows.value.splice(i, 1)
const addLcrPendukung = () => lcrPendukungRows.value.push({ pompa: '', aliran: '', selang: '', valve: '', ground: '', sinyal: '' })
const removeLcrPendukung = (i: number) => lcrPendukungRows.value.splice(i, 1)
const addLcrQuantityTangki = () => lcrQuantityTangkiRows.value.push({ alat: '', merk: '', tera: '', masa: '', flowmeter: '' })
const removeLcrQuantityTangki = (i: number) => lcrQuantityTangkiRows.value.splice(i, 1)
const addLcrQualityTangki = () => lcrQualityTangkiRows.value.push({ spec: '', lab: '', coq: '' })
const removeLcrQualityTangki = (i: number) => lcrQualityTangkiRows.value.splice(i, 1)
const addLcrKapal = () => lcrKapalRows.value.push({ tipe: '', kapasitas: '', jumlah: '', inlet: '', ukuran: '', metode: '' })
const removeLcrKapal = (i: number) => lcrKapalRows.value.splice(i, 1)

/* Fetcher: Cabang & Wilayah OA — dipakai SearchableRemoteSelect di bawah (adaptasi CustomerLcrForm.vue) */
async function searchCabangs(search = '', perPage = 10): Promise<CabangOption[]> {
  const { data } = await axios.get('/api/cabangs', { params: { per_page: perPage, search: search || undefined } })
  const rows = data.data ?? data
  return rows.map((r: any) => ({
    value: r.id_cabang,
    label: r.nama_cabang ?? 'Cabang',
    id_wilayah: r.id_wilayah ?? null,
    raw: r,
  }))
}
async function getCabangById(id: number): Promise<CabangOption> {
  const { data } = await axios.get(`/api/cabangs/${id}`)
  return {
    value: data.id_cabang,
    label: data.nama_cabang ?? 'Cabang',
    id_wilayah: data.id_wilayah ?? null,
    raw: data,
  }
}
async function searchWilayahOa(search = '', perPage = 10): Promise<SimpleOption[]> {
  const { data } = await axios.get('/api/wilayah-angkuts', { params: { per_page: perPage, search: search || undefined } })
  const rows = data.data ?? data
  return rows.map((r: any) => ({ value: r.id, label: r.destinasi, raw: r }))
}
async function getWilayahOaById(id: number): Promise<SimpleOption> {
  const { data } = await axios.get(`/api/wilayah-angkuts/${id}`)
  return {
    value: data.id,
    label: data.destinasi ?? data.nama ?? data.wilayah ?? data.kota ?? `Wilayah OA (#${data.id})`,
    raw: data,
  }
}

/* Komponen: dropdown pencarian remote untuk Cabang/Wilayah OA (adaptasi verbatim dari CustomerLcrForm.vue) */
const SearchableRemoteSelect = defineComponent({
  name: 'SearchableRemoteSelect',
  props: {
    modelValue: { type: Number as PropType<number | null>, default: null },
    fetcher: { type: Function as unknown as PropType<(q?: string) => Promise<SimpleOption[]>>, required: true },
    getById: { type: Function as unknown as PropType<(id: number) => Promise<SimpleOption>>, required: true },
    placeholder: { type: String, default: 'Pilih…' },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'selected'],
  setup(props, { emit }) {
    const open = ref(false)
    const search = ref('')
    const options = ref<SimpleOption[]>([])
    const loadingOptions = ref(false)
    const selectedLabel = ref('')

    const inputId = ref(`srsel-${Math.random().toString(36).slice(2)}`)
    const wrapperId = ref(`srwrap-${Math.random().toString(36).slice(2)}`)

    const load = async (q = '') => {
      loadingOptions.value = true
      try {
        options.value = await props.fetcher(q) || []
      } finally {
        loadingOptions.value = false
      }
    }
    const debouncedLoad = debounce((q: string) => { void load(q) }, 250)

    const setByIdLabel = async () => {
      if (props.modelValue != null) {
        try {
          const o = await props.getById(props.modelValue)
          selectedLabel.value = o?.label ?? ''
          if (o && !options.value.some(x => x.value === o.value)) {
            options.value = [o, ...options.value]
          }
        } catch {
          selectedLabel.value = ''
        }
      } else {
        selectedLabel.value = ''
      }
    }

    const toggle = async () => {
      if (props.disabled) return
      open.value = !open.value
      if (open.value) {
        await load('')
        queueMicrotask(() => (document.getElementById(inputId.value) as HTMLInputElement | null)?.focus())
      }
    }
    const pick = (o: SimpleOption) => {
      emit('update:modelValue', o.value)
      emit('selected', o)
      selectedLabel.value = o.label
      open.value = false
    }

    const onClickOutside = (e: MouseEvent) => {
      const root = document.getElementById(wrapperId.value)
      if (root && !root.contains(e.target as Node)) open.value = false
    }

    onMounted(() => {
      setByIdLabel()
      document.addEventListener('mousedown', onClickOutside)
    })
    onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))

    watch(() => props.modelValue, setByIdLabel)
    watch(search, (v) => debouncedLoad(v))

    return () =>
      h('div', { id: wrapperId.value, class: 'relative' }, [
        h('button', {
          type: 'button',
          class:
            `w-full border rounded px-3 py-2 text-left hover:border-slate-400 ` +
            `${props.disabled ? 'opacity-60 cursor-not-allowed ' : ''}` +
            `${props.invalid ? ' border-danger ring-1 ring-danger/40 ' : ''}`,
          onClick: toggle,
        }, [
          h('span', { class: selectedLabel.value ? '' : 'text-slate-400' }, selectedLabel.value || props.placeholder),
          h('span', { class: 'float-right text-slate-400' }, [
            h(Lucide as any, { icon: 'ChevronDown', class: 'w-4 h-4 inline' }),
          ]),
        ]),
        open.value
          ? h('div', { class: 'absolute z-50 mt-1 w-full bg-white border rounded shadow' }, [
            h('div', { class: 'p-2 border-b' }, [
              h('input', {
                id: inputId.value,
                value: search.value,
                placeholder: 'Cari...',
                class: 'w-full outline-none',
                onInput: (e: Event) => (search.value = (e.target as HTMLInputElement).value),
              }),
            ]),
            h('div', { class: 'max-h-56 overflow-auto' }, [
              loadingOptions.value
                ? h('div', { class: 'p-3 text-sm text-slate-500' }, 'Memuat…')
                : options.value.length === 0
                  ? h('div', { class: 'p-3 text-sm text-slate-500' }, 'Tidak ada hasil')
                  : options.value.map(o =>
                    h('div', {
                      key: String(o.value),
                      class: 'px-3 py-2 hover:bg-slate-100 cursor-pointer text-sm',
                      onClick: () => pick(o),
                    }, o.label),
                  ),
            ]),
          ])
          : null,
      ])
  },
})
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">{{ customerSummary.nama_perusahaan || 'Detail Customer' }}</h2>
          <p class="font-lead mt-1">Data customer, hasil sales review, dan status verifikasi.</p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data...</span>
      </div>

      <Tab.Group v-else>
        <Tab.List variant="link-tabs" class="gap-1 border-b border-slate-200">
          <Tab v-for="t in tabItems" :key="t.label" :full-width="false" v-slot="{ selected }">
            <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
              ? 'text-primary border-b-primary font-medium'
              : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
              <Lucide :icon="t.icon" class="h-4 w-4" />
              <span>{{ t.label }}</span>
            </Tab.Button>
          </Tab>
        </Tab.List>

        <Tab.Panels class="mt-4">
          <!-- TAB 1: Data Customer (read-only) -->
          <Tab.Panel>
            <CardSection title="Data Customer"
              description="Data legal, finansial, dan logistik dari hasil submit form publik." icon="FileText"
              icon-class="bg-violet-100 text-violet-600">
              <div v-if="!hasVerification"
                class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
                <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
                <div class="font-strong">Belum ada data verifikasi</div>
                <div class="font-body max-w-md">
                  Customer ini belum memiliki siklus verifikasi. Data legal, finansial, dan logistik akan tampil di sini
                  setelah customer mengisi formulir verifikasi.
                </div>
              </div>

              <div v-else class="space-y-5">
                <div v-for="section in summarySections" :key="section.title">
                  <div class="font-section mb-3 pb-2 border-b border-slate-100">{{ section.title }}</div>
                  <div class="grid md:grid-cols-2 gap-3 bg-slate-50 rounded p-4">
                    <div v-for="row in section.rows" :key="row.label"
                      class="flex justify-between gap-4 border-b border-slate-200 pb-1">
                      <span class="font-label">{{ row.label }}</span>
                      <span class="font-strong text-right">{{ row.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </CardSection>
          </Tab.Panel>

          <!-- TAB 2: Sales Review (editable) -->
          <Tab.Panel>
            <div v-if="!hasVerification"
              class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
              <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
              <div class="font-strong">Belum ada data verifikasi</div>
              <div class="font-body max-w-md">
                Review baru bisa diisi setelah customer memiliki siklus verifikasi yang aktif.
              </div>
            </div>

            <div v-else class="space-y-4">
              <CardSection title="Review & Catatan Marketing"
                description="Diisi Marketing sebelum forward ke Admin Finance" icon="ClipboardEdit"
                icon-class="bg-emerald-100 text-emerald-600">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div class="sm:col-span-2">
                    <FormLabel>Catatan Marketing / Key Account</FormLabel>
                    <FormTextarea v-model="reviewForm.marketing_notes" :rows="3"
                      placeholder="Ringkasan hasil review data customer..." />
                  </div>

                  <div class="sm:col-span-2">
                    <FormLabel>Alur pemeriksaan/review dokumen & rata-rata waktu sampai pembayaran</FormLabel>
                    <FormTextarea v-model="reviewForm.flow_review" :rows="2" />
                  </div>

                  <div>
                    <FormLabel>Pemilik Authority Pembayaran</FormLabel>
                    <FormTextarea v-model="reviewForm.payment_authority" :rows="2" placeholder="Nama, Posisi, No. HP" />
                  </div>

                  <div>
                    <FormLabel>Existing Fuel Vendor</FormLabel>
                    <FormTextarea v-model="reviewForm.existing_vendor" :rows="2" placeholder="Nama, credit term" />
                  </div>

                  <div class="sm:col-span-2">
                    <FormLabel>Historical/Background Bisnis/Group</FormLabel>
                    <FormTextarea v-model="reviewForm.history" :rows="2" />
                  </div>

                  <div>
                    <FormLabel>Lokasi Depo Sumber Produk (Terminal)</FormLabel>
                    <FormTextarea v-model="reviewForm.depot_location" :rows="2" />
                  </div>

                  <div>
                    <FormLabel>Opportunity Bisnis</FormLabel>
                    <FormTextarea v-model="reviewForm.opportunities" :rows="2" />
                  </div>
                </div>
              </CardSection>

              <div class="flex justify-end">
                <Button variant="primary" class="inline-flex items-center gap-2" @click="forwardDialogOpen = true">
                  <Lucide icon="Send" class="h-4 w-4" />
                  Forward ke Admin Finance
                </Button>
              </div>
            </div>
          </Tab.Panel>

          <!-- TAB 3: LCR (editable, create/edit terdeteksi otomatis) -->
          <Tab.Panel>
            <div v-if="lcrLoading" class="flex min-h-[220px] items-center justify-center gap-3 text-slate-500">
              <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
              <span class="font-body">Memuat data LCR...</span>
            </div>

            <div v-else class="space-y-4">
              <CardSection title="Informasi Umum & Data Survei"
                description="Profil lokasi dan tim survei, diisi berdasarkan laporan yang Anda terima."
                icon="ClipboardList" icon-class="bg-sky-100 text-sky-600" :collapsible="true">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <FormLabel>Wilayah (Cabang)</FormLabel>
                    <SearchableRemoteSelect v-model="lcrForm.id_cabang" :fetcher="searchCabangs"
                      :getById="getCabangById" placeholder="Pilih cabang…"
                      @selected="(o: any) => { if (o?.raw?.id_wilayah) lcrForm.id_wilayah = o.raw.id_wilayah }" />
                  </div>

                  <div>
                    <FormLabel>Wilayah OA *</FormLabel>
                    <SearchableRemoteSelect v-model="lcrForm.id_wil_oa" :fetcher="searchWilayahOa"
                      :getById="getWilayahOaById" placeholder="Pilih wilayah OA…"
                      :invalid="!!lcrFirstError('id_wil_oa')" />
                    <div class="text-xs text-red-600 mt-1" v-if="lcrFirstError('id_wil_oa')">{{
                      lcrFirstError('id_wil_oa') }}</div>
                  </div>

                  <div class="sm:col-span-2">
                    <FormLabel>Alamat Lokasi Customer *</FormLabel>
                    <FormTextarea v-model="lcrForm.alamat_survey" :rows="2"
                      :class="lcrFirstError('alamat_survey') ? 'border-danger ring-1 ring-danger/40' : ''" />
                    <div class="text-xs text-red-600 mt-1" v-if="lcrFirstError('alamat_survey')">{{
                      lcrFirstError('alamat_survey') }}</div>
                  </div>

                  <div>
                    <DateField v-model="lcrForm.tgl_survey" label="Tanggal Survey" required
                      :error="lcrFirstError('tgl_survey') || ''" />
                    <p class="font-caption mt-1">Tanggal survei dilakukan, sesuai laporan tim lapangan atau informasi
                      dari customer.</p>
                  </div>

                  <div>
                    <FormLabel>Jenis Usaha *</FormLabel>
                    <FormSelect v-model="lcrForm.jenis_usaha"
                      :class="lcrFirstError('jenis_usaha') ? 'border-danger ring-1 ring-danger/40' : ''">
                      <option value="">- Pilihan -</option>
                      <option v-for="o in jenisUsahaOptions" :key="o" :value="o">{{ o }}</option>
                    </FormSelect>
                    <div class="text-xs text-red-600 mt-1" v-if="lcrFirstError('jenis_usaha')">{{
                      lcrFirstError('jenis_usaha') }}</div>
                  </div>

                  <div>
                    <FormLabel>Website</FormLabel>
                    <FormInput v-model="lcrForm.website" placeholder="https://..." />
                  </div>

                  <div class="sm:col-span-2">
                    <FormLabel>Review</FormLabel>
                    <FormTextarea v-model="lcrForm.review" :rows="3"
                      placeholder="Ringkasan hasil survei/laporan lokasi..." />
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Surveyor</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase text-left">No</th>
                          <th class="px-3 py-2 text-xs uppercase text-left">Nama Surveyor</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, i) in lcrSurveyorRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormInput v-model="lcrSurveyorRows[i]" />
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrSurveyor">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrSurveyorRows.length === 1"
                              @click="removeLcrSurveyor(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                  <div>
                    <div class="font-section mb-2">Hasil Survei</div>
                    <div class="space-y-2">
                      <div class="flex items-center gap-2" v-for="(h, i) in lcrHasilRows" :key="i">
                        <FormInput v-model="lcrHasilRows[i]" class="flex-1" />
                        <Button size="sm" variant="soft-primary" title="Tambah baris"
                          class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrHasil">
                          <Lucide icon="Plus" class="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="soft-danger" title="Hapus baris" class="!h-8 !w-8 !p-0 !shadow-none"
                          :disabled="lcrHasilRows.length === 1" @click="removeLcrHasil(i)">
                          <Lucide icon="Trash2" class="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>

                  <div>
                    <div class="font-section mb-2">Kompetitor</div>
                    <div class="space-y-2">
                      <div class="flex items-center gap-2" v-for="(k, i) in lcrKompetitorRows" :key="i">
                        <FormInput v-model="lcrKompetitorRows[i]" class="flex-1" />
                        <Button size="sm" variant="soft-primary" title="Tambah baris"
                          class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrKompetitor">
                          <Lucide icon="Plus" class="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="soft-danger" title="Hapus baris" class="!h-8 !w-8 !p-0 !shadow-none"
                          :disabled="lcrKompetitorRows.length === 1" @click="removeLcrKompetitor(i)">
                          <Lucide icon="Trash2" class="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Produk & Volume per Bulan</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">No</th>
                          <th class="px-3 py-2 text-xs uppercase">Produk</th>
                          <th class="px-3 py-2 text-xs uppercase">Volume/Bulan</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(p, i) in lcrProdukVolRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.produk" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.volbul" placeholder="5000" />
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrProdukVol">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrProdukVolRows.length === 1"
                              @click="removeLcrProdukVol(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Penanggung Jawab (PIC Customer)</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">No</th>
                          <th class="px-3 py-2 text-xs uppercase">Nama</th>
                          <th class="px-3 py-2 text-xs uppercase">Posisi</th>
                          <th class="px-3 py-2 text-xs uppercase">Telepon</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(p, i) in lcrPicRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.nama" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.posisi" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.telepon" />
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrPic">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrPicRows.length === 1"
                              @click="removeLcrPic(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <FormLabel>Komitmen Penerimaan</FormLabel>
                    <FormSelect v-model="lcrForm.logistik_result">
                      <option :value="1">Ok</option>
                      <option :value="0">Tidak</option>
                      <option :value="2">Perlu Review</option>
                    </FormSelect>
                  </div>
                  <div>
                    <FormLabel>Toleransi</FormLabel>
                    <FormInput v-model="lcrForm.toleransi" placeholder="0.5 %" />
                  </div>
                </div>
              </CardSection>

              <CardSection title="Informasi Lokasi & Rute"
                description="Koordinat dan rute lokasi, sesuai laporan atau titik pada peta — tidak perlu berada di lokasi saat ini."
                icon="MapPin" icon-class="bg-emerald-100 text-emerald-600" :collapsible="true">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                  <div>
                    <FormLabel>Latitude *</FormLabel>
                    <FormInput v-model="lcrForm.latitude_lokasi" placeholder="-6.9"
                      :class="lcrFirstError('latitude_lokasi') ? 'border-danger ring-1 ring-danger/40' : ''" />
                    <div class="text-xs text-red-600 mt-1" v-if="lcrFirstError('latitude_lokasi')">{{
                      lcrFirstError('latitude_lokasi') }}</div>
                  </div>
                  <div>
                    <FormLabel>Longitude *</FormLabel>
                    <div class="flex">
                      <FormInput v-model="lcrForm.longitude_lokasi" placeholder="112.45" class="flex-1"
                        :class="lcrFirstError('longitude_lokasi') ? 'border-danger ring-1 ring-danger/40' : ''" />
                      <Button class="ml-2" title="Buat link Google Maps" @click="buildLcrMapLink">
                        <Lucide icon="Search" class="h-4 w-4" />
                      </Button>
                    </div>
                    <div class="text-xs text-red-600 mt-1" v-if="lcrFirstError('longitude_lokasi')">{{
                      lcrFirstError('longitude_lokasi') }}</div>
                  </div>
                  <div>
                    <FormLabel>Jarak dari Jetty/Depo</FormLabel>
                    <FormInput v-model="lcrForm.jarak_depot" placeholder="39,1" />
                  </div>
                  <div>
                    <FormLabel>Link Google Maps</FormLabel>
                    <FormInput v-model="lcrForm.link_google_maps" placeholder="https://maps.google.com/..." />
                  </div>
                </div>

                <p class="font-caption mt-2">
                  Masukkan koordinat sesuai laporan survei atau titik pada Google Maps — bukan lokasi Anda saat mengisi
                  form ini.
                </p>

                <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                  <div class="lg:col-span-2">
                    <div class="font-caption mb-2">Preview Peta</div>
                    <div class="h-72 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                      <iframe v-if="lcrMapUrl" :src="lcrMapUrl" class="h-full w-full" loading="lazy"></iframe>
                      <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                        Isi Latitude & Longitude untuk melihat preview peta
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div>
                      <FormLabel>Rute Menuju Lokasi</FormLabel>
                      <FormTextarea v-model="lcrForm.rute_lokasi" :rows="4" />
                    </div>
                    <div>
                      <FormLabel>Catatan Lokasi</FormLabel>
                      <FormTextarea v-model="lcrForm.note_lokasi" :rows="4" />
                    </div>
                  </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                  <div>
                    <FormLabel>Max Kapasitas Truk</FormLabel>
                    <FormSelect v-model="lcrForm.max_truk">
                      <option value="">Pilih salah satu</option>
                      <option v-for="o in maxTrukOptions" :key="o" :value="o">{{ o }}</option>
                    </FormSelect>
                  </div>
                  <div>
                    <FormLabel>Min Kapasitas Truk</FormLabel>
                    <FormInput v-model="lcrForm.min_vol_kirim" placeholder="5 (m³)" />
                  </div>
                  <div class="sm:col-span-3">
                    <FormLabel>Penjelasan Proses Bongkar</FormLabel>
                    <FormTextarea v-model="lcrForm.penjelasan_bongkar" :rows="3" />
                  </div>
                </div>
              </CardSection>

              <CardSection title="Pembongkaran via Truk (Tangki)"
                description="Detail media bongkar truk/tangki di lokasi customer, sesuai laporan survei." icon="Truck"
                icon-class="bg-amber-100 text-amber-600" :collapsible="true" :default-open="false">
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                      <tr>
                        <th class="px-3 py-2 text-xs uppercase">No</th>
                        <th class="px-3 py-2 text-xs uppercase">Tipe</th>
                        <th class="px-3 py-2 text-xs uppercase">Kapasitas</th>
                        <th class="px-3 py-2 text-xs uppercase">Jumlah</th>
                        <th class="px-3 py-2 text-xs uppercase">Produk</th>
                        <th class="px-3 py-2 text-xs uppercase">Inlet Pipa</th>
                        <th class="px-3 py-2 text-xs uppercase">Ukuran</th>
                        <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(t, i) in lcrTangkiRows" :key="i" class="border-b">
                        <td class="px-3 py-2">{{ i + 1 }}</td>
                        <td class="px-3 py-2">
                          <FormSelect v-model="t.tipe">
                            <option value="">- Pilihan -</option>
                            <option v-for="o in tangkiTypeOptions" :key="o" :value="o">{{ o }}</option>
                          </FormSelect>
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="t.kapasitas" placeholder="7KL" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="t.jumlah" placeholder="1" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="t.produk" placeholder="HSD" />
                        </td>
                        <td class="px-3 py-2">
                          <FormSelect v-model="t.inlet">
                            <option value="">- Pilihan -</option>
                            <option v-for="o in inletOptions" :key="o" :value="o">{{ o }}</option>
                          </FormSelect>
                        </td>
                        <td class="px-3 py-2">
                          <FormSelect v-model="t.ukuran">
                            <option value="">- Pilihan -</option>
                            <option v-for="o in ukuranOptions" :key="o" :value="o">{{ o }}</option>
                          </FormSelect>
                        </td>
                        <td class="px-3 py-2 text-center space-x-2">
                          <Button size="sm" variant="soft-primary" title="Tambah baris"
                            class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrTangki">
                            <Lucide icon="Plus" class="h-4 w-4" />
                          </Button>
                          <Button size="sm" variant="soft-danger" title="Hapus baris"
                            class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrTangkiRows.length === 1"
                            @click="removeLcrTangki(i)">
                            <Lucide icon="Trash2" class="h-4 w-4" />
                          </Button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Pendukung</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">No</th>
                          <th class="px-3 py-2 text-xs uppercase">Pompa</th>
                          <th class="px-3 py-2 text-xs uppercase">Laju Aliran</th>
                          <th class="px-3 py-2 text-xs uppercase">P. Selang</th>
                          <th class="px-3 py-2 text-xs uppercase">Valve</th>
                          <th class="px-3 py-2 text-xs uppercase">Grounding</th>
                          <th class="px-3 py-2 text-xs uppercase">Sinyal HP</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(p, i) in lcrPendukungRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="p.pompa">
                              <option value="">- Pilihan -</option>
                              <option v-for="o in pompaOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.aliran" placeholder="300 LPM" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="p.selang" placeholder="15 M" />
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="p.valve">
                              <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="p.ground">
                              <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="p.sinyal">
                              <option v-for="o in sinyalOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrPendukung">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrPendukungRows.length === 1"
                              @click="removeLcrPendukung(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Quantity</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">No</th>
                          <th class="px-3 py-2 text-xs uppercase">Alat Ukur</th>
                          <th class="px-3 py-2 text-xs uppercase">Merk</th>
                          <th class="px-3 py-2 text-xs uppercase">Tera</th>
                          <th class="px-3 py-2 text-xs uppercase">Masa Berlaku</th>
                          <th class="px-3 py-2 text-xs uppercase">Flowmeter tiap Pengiriman</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(q, i) in lcrQuantityTangkiRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormInput v-model="q.alat" placeholder="Flowmeter" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="q.merk" placeholder="LC M10" />
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="q.tera">
                              <option v-for="o in adaTidakOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="q.masa" placeholder="Berlaku" />
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="q.flowmeter">
                              <option value="Ya">Ya</option>
                              <option value="Tidak">Tidak</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrQuantityTangki">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrQuantityTangkiRows.length === 1"
                              @click="removeLcrQuantityTangki(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Quality</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">No</th>
                          <th class="px-3 py-2 text-xs uppercase">Min. Spec.</th>
                          <th class="px-3 py-2 text-xs uppercase">Uji Lab</th>
                          <th class="px-3 py-2 text-xs uppercase">COQ tiap Pengiriman</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(q, i) in lcrQualityTangkiRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ i + 1 }}</td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="q.spec">
                              <option value="">- Pilihan -</option>
                              <option v-for="o in specOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="q.lab">
                              <option v-for="o in yaTidakOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <FormSelect v-model="q.coq">
                              <option v-for="o in yaTidakOptions" :key="o" :value="o">{{ o }}</option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrQualityTangki">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrQualityTangkiRows.length === 1"
                              @click="removeLcrQualityTangki(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-4">
                  <FormLabel>Catatan Tangki</FormLabel>
                  <FormTextarea v-model="lcrForm.catatan_tangki" :rows="3" />
                </div>
              </CardSection>

              <CardSection title="Pembongkaran via Kapal & Jetty"
                description="Detail media bongkar kapal dan fasilitas jetty, sesuai laporan survei (jika tersedia)."
                icon="Ship" icon-class="bg-indigo-100 text-indigo-600" :collapsible="true" :default-open="false">
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                      <tr>
                        <th class="px-3 py-2 text-xs uppercase">No</th>
                        <th class="px-3 py-2 text-xs uppercase">Tipe</th>
                        <th class="px-3 py-2 text-xs uppercase">Kapasitas</th>
                        <th class="px-3 py-2 text-xs uppercase">Jumlah</th>
                        <th class="px-3 py-2 text-xs uppercase">Inlet Pipa</th>
                        <th class="px-3 py-2 text-xs uppercase">Ukuran</th>
                        <th class="px-3 py-2 text-xs uppercase">Metode</th>
                        <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(k, i) in lcrKapalRows" :key="i" class="border-b">
                        <td class="px-3 py-2">{{ i + 1 }}</td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.tipe" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.kapasitas" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.jumlah" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.inlet" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.ukuran" />
                        </td>
                        <td class="px-3 py-2">
                          <FormInput v-model="k.metode" />
                        </td>
                        <td class="px-3 py-2 text-center space-x-2">
                          <Button size="sm" variant="soft-primary" title="Tambah baris"
                            class="!h-8 !w-8 !p-0 !shadow-none" @click="addLcrKapal">
                            <Lucide icon="Plus" class="h-4 w-4" />
                          </Button>
                          <Button size="sm" variant="soft-danger" title="Hapus baris"
                            class="!h-8 !w-8 !p-0 !shadow-none" :disabled="lcrKapalRows.length === 1"
                            @click="removeLcrKapal(i)">
                            <Lucide icon="Trash2" class="h-4 w-4" />
                          </Button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Jetty</div>
                  <p class="font-caption mb-2">Data jetty ditampilkan dari hasil survei sebelumnya (bila ada) — tidak
                    diisi manual di form ini.</p>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">Max LOA</th>
                          <th class="px-3 py-2 text-xs uppercase">Min PBL</th>
                          <th class="px-3 py-2 text-xs uppercase">Draft (LWS)</th>
                          <th class="px-3 py-2 text-xs uppercase">Kekuatan (DWT)</th>
                          <th class="px-3 py-2 text-xs uppercase">Izin</th>
                          <th class="px-3 py-2 text-xs uppercase">Persyaratan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(j, i) in lcrJettyRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ j.loa || '-' }}</td>
                          <td class="px-3 py-2">{{ j.pbl || '-' }}</td>
                          <td class="px-3 py-2">{{ j.lws || '-' }}</td>
                          <td class="px-3 py-2">{{ j.kekuatan || '-' }}</td>
                          <td class="px-3 py-2">{{ j.izin || '-' }}</td>
                          <td class="px-3 py-2">{{ j.syarat || '-' }}</td>
                        </tr>
                        <tr v-if="!lcrJettyRows?.length">
                          <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Quantity</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">Alat Ukur</th>
                          <th class="px-3 py-2 text-xs uppercase">Merk</th>
                          <th class="px-3 py-2 text-xs uppercase">Tera</th>
                          <th class="px-3 py-2 text-xs uppercase">Masa Berlaku</th>
                          <th class="px-3 py-2 text-xs uppercase">Flowmeter tiap Pengiriman</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(q, i) in lcrQuantityKapalRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ q.alat || '-' }}</td>
                          <td class="px-3 py-2">{{ q.merk || '-' }}</td>
                          <td class="px-3 py-2">{{ q.tera || '-' }}</td>
                          <td class="px-3 py-2">{{ q.masa || '-' }}</td>
                          <td class="px-3 py-2">{{ q.flowmeter || '-' }}</td>
                        </tr>
                        <tr v-if="!lcrQuantityKapalRows?.length">
                          <td colspan="5" class="px-3 py-2 text-center text-slate-500">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Quality</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">Min. Spec.</th>
                          <th class="px-3 py-2 text-xs uppercase">Uji Lab</th>
                          <th class="px-3 py-2 text-xs uppercase">COQ tiap Pengiriman</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(q, i) in lcrQualityKapalRows" :key="i" class="border-b">
                          <td class="px-3 py-2">{{ q.spec || '-' }}</td>
                          <td class="px-3 py-2">{{ q.lab || '-' }}</td>
                          <td class="px-3 py-2">{{ q.coq || '-' }}</td>
                        </tr>
                        <tr v-if="!lcrQualityKapalRows?.length">
                          <td colspan="3" class="px-3 py-2 text-center text-slate-500">-</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="mt-4">
                  <FormLabel>Catatan Kapal</FormLabel>
                  <FormTextarea v-model="lcrForm.catatan_kapal" :rows="3" />
                </div>
              </CardSection>

              <CardSection v-if="lcrMode === 'edit' && lcrForm.flag_approval === 1" title="Foto Pendukung Lokasi"
                description="Unggah foto yang Anda terima dari lokasi customer (kondisi jalan, gudang, dsb.) sebagai lampiran pendukung LCR."
                icon="Image" icon-class="bg-rose-100 text-rose-600" :collapsible="true" :default-open="false">
                <template #action>
                  <Button variant="outline-primary" class="inline-flex items-center gap-2" @click="openLcrMediaModal">
                    <Lucide icon="Upload" class="h-4 w-4" />
                    Kelola Foto Pendukung
                  </Button>
                </template>
                <p class="font-body">
                  Data LCR ini sudah disetujui — foto pendukung lokasi dapat diunggah dan dikelola lewat tombol di atas.
                </p>
              </CardSection>

              <div class="flex justify-end">
                <Button variant="primary" class="inline-flex items-center gap-2" :disabled="lcrSaving"
                  @click="submitLcr">
                  <Lucide icon="Save" class="h-4 w-4" />
                  {{ lcrSaving ? 'Menyimpan...' : 'Simpan Data LCR' }}
                </Button>
              </div>
            </div>
          </Tab.Panel>

          <!-- TAB 4: Credit Application / TOP (editable, berbagi save action dgn Tab 2) -->
          <Tab.Panel>
            <div v-if="!hasVerification"
              class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
              <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
              <div class="font-strong">Belum ada data verifikasi</div>
              <div class="font-body max-w-md">
                Pengajuan credit limit & TOP baru bisa diisi setelah customer memiliki siklus verifikasi yang aktif.
              </div>
            </div>

            <div v-else class="space-y-4">
              <CardSection title="Pengajuan Credit Limit & Term of Payment (TOP)"
                description="Usulan awal dari Marketing/Key Account — akan direview dan dikonfirmasi oleh Admin Finance."
                icon="Wallet" icon-class="bg-teal-100 text-teal-600">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <CurrencyField v-model="reviewForm.credit_limit_proposed" label="Credit Limit Diajukan" />

                  <div class="sm:col-span-2">
                    <FormLabel>Usulan Term of Payment (TOP)</FormLabel>
                    <FormTextarea v-model="reviewForm.invoice_schedule" :rows="3"
                      placeholder="Contoh: TOP 14 hari setelah tanggal pengiriman, jatuh tempo setiap tanggal 10..." />
                  </div>
                </div>
              </CardSection>

              <div class="flex justify-end">
                <Button variant="primary" class="inline-flex items-center gap-2" @click="forwardDialogOpen = true">
                  <Lucide icon="Send" class="h-4 w-4" />
                  Forward ke Admin Finance
                </Button>
              </div>
            </div>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>

    <ConfirmDialog :open="forwardDialogOpen" title="Forward data ke Admin Finance?"
      description="Data review akan disimpan dan siklus persetujuan Admin Finance → BM akan dimulai."
      confirm-text="Ya, Forward" icon="Send" icon-class="bg-primary/10 text-primary" variant="primary"
      :loading="reviewSaving" @close="forwardDialogOpen = false" @confirm="saveReview" />

    <!-- Modal: Kelola Foto Pendukung LCR -->
    <div v-if="lcrMediaOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="lcrMediaOpen = false"></div>
      <div class="relative w-[1000px] max-w-[95vw] rounded-lg bg-white shadow-lg">
        <div class="flex items-center border-b px-5 py-3">
          <div class="font-strong">Kelola Foto Pendukung Lokasi</div>
          <button class="ml-auto text-slate-500" @click="lcrMediaOpen = false">
            <Lucide icon="X" class="h-5 w-5" />
          </button>
        </div>

        <div class="max-h-[75vh] space-y-8 overflow-y-auto p-5">
          <template v-for="section in [
            { key: 'layout_lokasi', label: 'Peta / Layout Pabrik / Site' },
            { key: 'layout_bongkar', label: 'Rute Pembongkaran' },
            { key: 'kondisi_jalan', label: 'Kondisi Jalan Menuju Lokasi' },
            { key: 'kantor_perusahaan', label: 'Pintu Gerbang & Kantor Perusahaan' },
            { key: 'fasilitas_storage', label: 'Fasilitas Penyimpanan' },
            { key: 'inlet_pipa', label: 'Inlet Pipa' },
            { key: 'alat_ukur_gambar', label: 'Alat Ukur' },
            { key: 'media_datar', label: 'Media Datar' },
            { key: 'keterangan_lain', label: 'Keterangan Penunjang Lain' },
          ]" :key="section.key">
            <div class="font-strong mb-2">{{ section.label }}</div>
            <p class="font-caption mb-3">Unggah foto yang Anda terima terkait bagian ini — bisa lebih dari satu.</p>

            <div class="mb-3 flex items-center gap-3">
              <input type="file" multiple @change="onLcrMediaPick(section.key as any, $event)" />
            </div>

            <div class="grid grid-cols-12 gap-3">
              <div v-for="(it, i) in (lcrMedia as any)[section.key]" :key="i"
                class="col-span-12 rounded border p-2 md:col-span-6 lg:col-span-4">
                <img :src="it.url" alt="" class="h-40 w-full rounded object-cover" />
                <input v-model="it.caption" class="mt-2 w-full rounded border px-2 py-1"
                  placeholder="Keterangan gambar…" />
                <button class="mt-2 text-sm text-red-600"
                  @click="removeLcrMedia(section.key as any, i as number)">Hapus</button>
              </div>

              <div v-if="!(lcrMedia as any)[section.key]?.length" class="col-span-12 text-slate-500">
                Belum ada foto untuk bagian ini.
              </div>
            </div>
          </template>
        </div>

        <div class="flex items-center justify-end gap-2 border-t px-5 py-3">
          <Button variant="outline-secondary" @click="lcrMediaOpen = false">Tutup</Button>
          <Button variant="primary" @click="saveLcrMedia">Simpan</Button>
        </div>
      </div>
    </div>
  </div>
</template>
