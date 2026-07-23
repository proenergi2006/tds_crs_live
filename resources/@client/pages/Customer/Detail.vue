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
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

/* Type: opsi remote-select untuk Cabang & Wilayah OA (dipakai Tab 3 — LCR) */
type SimpleOption<T = any> = { value: number; label: string; raw?: T }
interface CabangOption<T = any> extends SimpleOption<T> {
  id_wilayah: number | null
}

/* Type: dokumen customer (customer_document_types + customer_documents), Tab 1 */
interface CustomerDocumentType {
  id: number
  code: string
  name: string
  is_active: boolean
  requires_number: boolean
}
interface CustomerDocumentRecord {
  id: number
  id_customer: number
  id_document_type: number
  document_type: { id: number; code: string; name: string; requires_number: boolean } | null
  document_number: string | null
  file_name: string
  file_path: string
  url: string | null
  uploaded_at: string | null
  uploaded_by: { id: number; name: string } | null
}
interface DocumentRowState {
  file: File | null
  documentNumber: string
  editing: boolean
  uploading: boolean
  error: string
}

/* Type: kontak customer (customer_contact_types + customer_contacts), Tab 1 */
interface CustomerContactType {
  id: number
  code: string
  name: string
  is_active: boolean
}
interface CustomerContactRecord {
  id: number
  id_customer: number
  id_contact_type: number
  contact_type: { id: number; code: string; name: string } | null
  id_lcr: number | null
  full_name: string
  position: string | null
  phone: string | null
  mobile: string | null
  email: string | null
  created_at: string | null
  updated_at: string | null
}

/* Type: pengajuan kredit customer (customer_credit_submissions + customer_credit_items), Tab 4 */
interface CustomerCreditItemRecord {
  id: number
  id_submission: number
  id_produk: number
  produk: { id_produk: number; nama_produk: string } | null
  volume: string | number | null
  unit: string | null
  existing_limit: string | number | null
  actual_payment: string | number | null
  guarantee: string | null
  credit_limit_request: string | number | null
  credit_limit_approval: string | number | null
  top_request: number | null
  top_approval: number | null
  notes: string | null
}
interface CustomerCreditSubmissionRecord {
  id: number
  id_customer: number
  submission_type: string
  submission_type_label: string
  top_payment: number | null
  items: CustomerCreditItemRecord[]
  approval: unknown | null
  created_by: { id: number; name: string } | null
  created_at: string | null
  updated_at: string | null
}
interface ProdukOption {
  id_produk: number
  nama_produk: string
  ukuran?: { satuan?: { nama_satuan: string } | null } | null
}
interface CreditItemRow {
  id_produk: number | ''
  volume: number | null
  unit: string
  existing_limit: number | null
  actual_payment: number | null
  guarantee: string
  credit_limit_request: number | null
  top_request: number | null
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

/* State: Tab 1 — Dokumen Customer (customer_document_types + customer_documents) */
const documentTypesApi = createResourceApi('/customer-document-types')
const customerDocumentsApi = createResourceApi(`/customers/${idCustomer}/documents`)

const documentTypesLoading = ref(true)
const documentsLoading = ref(true)
const documentTypes = ref<CustomerDocumentType[]>([])
const customerDocuments = ref<CustomerDocumentRecord[]>([])
const documentRowState = reactive<Record<number, DocumentRowState>>({})

const deleteDocumentDialogOpen = ref(false)
const deleteDocumentTarget = ref<CustomerDocumentRecord | null>(null)
const deleteDocumentLoading = ref(false)

/* State: Tab 1 — Kontak Customer (customer_contact_types + customer_contacts) */
const contactTypesApi = createResourceApi('/customer-contact-types')
const customerContactsApi = createResourceApi(`/customers/${idCustomer}/contacts`)

const contactTypesLoading = ref(true)
const contactsLoading = ref(true)
const contactTypes = ref<CustomerContactType[]>([])
const customerContacts = ref<CustomerContactRecord[]>([])

const contactFormOpen = ref(false)
const contactFormMode = ref<'create' | 'edit'>('create')
const contactFormSaving = ref(false)
const contactFormError = ref<string | null>(null)
const contactFormErrors = ref<Record<string, string[]>>({})
const contactForm = reactive({
  id: null as number | null,
  id_contact_type: '' as number | '',
  full_name: '',
  position: '',
  phone: '',
  mobile: '',
  email: '',
})

const deleteContactDialogOpen = ref(false)
const deleteContactTarget = ref<CustomerContactRecord | null>(null)
const deleteContactLoading = ref(false)

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

/* State: Tab 4 — Pengajuan Kredit & TOP (customer_credit_submissions + customer_credit_items, CRUD independen dari siklus verifikasi) */
const creditSubmissionsApi = createResourceApi(`/customers/${idCustomer}/credit-submissions`)
const produksApi = createResourceApi('/produks')

const creditSubmissionsLoading = ref(true)
const creditSubmissions = ref<CustomerCreditSubmissionRecord[]>([])
const produkOptions = ref<ProdukOption[]>([])
const produkOptionsLoading = ref(true)

const creditSubmissionTypeOptions: { value: string; label: string }[] = [
  { value: 'new_customer', label: 'Customer Baru' },
  { value: 're_activated', label: 'Reaktivasi Customer' },
  { value: 'add_top', label: 'Penambahan TOP' },
  { value: 'add_credit_limit', label: 'Penambahan Limit Kredit' },
]

function emptyCreditItemRow(): CreditItemRow {
  return {
    id_produk: '',
    volume: null,
    unit: '',
    existing_limit: null,
    actual_payment: null,
    guarantee: '',
    credit_limit_request: null,
    top_request: null,
  }
}

const creditSubmissionFormOpen = ref(false)
const creditSubmissionSaving = ref(false)
const creditSubmissionError = ref<string | null>(null)
const creditSubmissionForm = reactive({
  submission_type: '' as string,
  top_payment: null as number | null,
})
const creditItemRows = ref<CreditItemRow[]>([emptyCreditItemRow()])

const editSubmissionFormOpen = ref(false)
const editSubmissionSaving = ref(false)
const editSubmissionError = ref<string | null>(null)
const editSubmissionTarget = ref<CustomerCreditSubmissionRecord | null>(null)
const editSubmissionForm = reactive({
  submission_type: '' as string,
  top_payment: null as number | null,
})

const deleteSubmissionDialogOpen = ref(false)
const deleteSubmissionTarget = ref<CustomerCreditSubmissionRecord | null>(null)
const deleteSubmissionLoading = ref(false)

const itemFormOpen = ref(false)
const itemFormMode = ref<'create' | 'edit'>('create')
const itemFormSaving = ref(false)
const itemFormError = ref<string | null>(null)
const itemFormSubmissionId = ref<number | null>(null)
const itemForm = reactive({
  id: null as number | null,
  id_produk: '' as number | '',
  volume: null as number | null,
  unit: '',
  existing_limit: null as number | null,
  actual_payment: null as number | null,
  guarantee: '',
  credit_limit_request: null as number | null,
  credit_limit_approval: null as number | null,
  top_request: null as number | null,
  top_approval: null as number | null,
  notes: '',
})

const deleteItemDialogOpen = ref(false)
const deleteItemTarget = ref<CustomerCreditItemRecord | null>(null)
const deleteItemSubmissionId = ref<number | null>(null)
const deleteItemLoading = ref(false)

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

/* Computed: Tab 1 — daftar baris dokumen aktif, digabung dengan dokumen yang sudah diupload */
const activeDocumentTypes = computed(() => documentTypes.value.filter(t => t.is_active))
const documentRows = computed(() =>
  activeDocumentTypes.value.map(type => ({
    type,
    document: customerDocuments.value.find(d => d.id_document_type === type.id) ?? null,
  })),
)

/* Computed: Tab 1 — jenis kontak aktif, dipakai dropdown form tambah/edit kontak */
const activeContactTypes = computed(() => contactTypes.value.filter(t => t.is_active))

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
onMounted(fetchDocumentTypes)
onMounted(fetchCustomerDocuments)
onMounted(fetchContactTypes)
onMounted(fetchCustomerContacts)
onMounted(fetchCreditSubmissions)
onMounted(fetchProdukOptions)

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

/* Fetch: Tab 1 — jenis dokumen aktif (master, CSR-first — data kecil) */
async function fetchDocumentTypes() {
  documentTypesLoading.value = true
  try {
    const { data } = await documentTypesApi.getAll({ as_list: true })
    documentTypes.value = Array.isArray(data) ? data : []
    documentTypes.value.forEach(type => rowState(type.id))
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat jenis dokumen customer.')
  } finally {
    documentTypesLoading.value = false
  }
}

/* Fetch: Tab 1 — dokumen yang sudah diupload customer ini (tidak dipaginate) */
async function fetchCustomerDocuments() {
  documentsLoading.value = true
  try {
    const { data } = await customerDocumentsApi.getAll()
    customerDocuments.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat dokumen customer.')
  } finally {
    documentsLoading.value = false
  }
}

/* Helper: Tab 1 — state upload per baris jenis dokumen (lazy-init, key = id_document_type) */
function rowState(typeId: number): DocumentRowState {
  if (!documentRowState[typeId]) {
    documentRowState[typeId] = { file: null, documentNumber: '', editing: false, uploading: false, error: '' }
  }
  return documentRowState[typeId]
}

function startDocumentUpload(typeId: number) {
  const state = rowState(typeId)
  state.editing = true
  state.file = null
  state.documentNumber = ''
  state.error = ''
}

function cancelDocumentUpload(typeId: number) {
  const state = rowState(typeId)
  state.editing = false
  state.file = null
  state.documentNumber = ''
  state.error = ''
}

/* Action: Tab 1 — upload/replace dokumen. Backend cuma sediakan create+delete (tidak ada
   endpoint replace/update), jadi "Ganti" diimplementasikan sebagai upload dokumen baru lalu
   hapus dokumen lama milik jenis yang sama setelah upload sukses (best-effort, tidak
   memblokir sukses utama kalau cleanup gagal). */
async function submitDocumentUpload(row: { type: CustomerDocumentType; document: CustomerDocumentRecord | null }) {
  const state = rowState(row.type.id)

  if (!state.file) {
    state.error = 'Pilih file terlebih dahulu.'
    return
  }
  if (row.type.requires_number && !state.documentNumber.trim()) {
    state.error = 'Nomor dokumen wajib diisi untuk jenis dokumen ini.'
    return
  }

  state.uploading = true
  state.error = ''
  try {
    const formData = new FormData()
    formData.append('id_document_type', String(row.type.id))
    formData.append('file', state.file)
    if (state.documentNumber.trim()) {
      formData.append('document_number', state.documentNumber.trim())
    }

    await customerDocumentsApi.store(formData)

    const previousDocument = row.document
    if (previousDocument) {
      await customerDocumentsApi.destroy(previousDocument.id).catch(() => null)
    }

    await fetchCustomerDocuments()
    cancelDocumentUpload(row.type.id)
    success('Berhasil', `Dokumen ${row.type.name} berhasil ${previousDocument ? 'diganti' : 'diunggah'}.`)
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      state.error = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      state.error = e.response?.data?.message ?? 'Gagal mengunggah dokumen.'
    }
  } finally {
    state.uploading = false
  }
}

function confirmDeleteDocument(document: CustomerDocumentRecord) {
  deleteDocumentTarget.value = document
  deleteDocumentDialogOpen.value = true
}

async function performDeleteDocument() {
  const target = deleteDocumentTarget.value
  if (!target) return

  deleteDocumentLoading.value = true
  try {
    await customerDocumentsApi.destroy(target.id)
    customerDocuments.value = customerDocuments.value.filter(d => d.id !== target.id)
    success('Berhasil', 'Dokumen berhasil dihapus.')
    deleteDocumentDialogOpen.value = false
    deleteDocumentTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus dokumen.')
  } finally {
    deleteDocumentLoading.value = false
  }
}

function formatDocumentDate(value: string | null) {
  if (!value) return '-'
  try {
    return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return value
  }
}

/* Fetch: Tab 1 — jenis kontak aktif (master, CSR-first — data kecil) */
async function fetchContactTypes() {
  contactTypesLoading.value = true
  try {
    const { data } = await contactTypesApi.getAll({ as_list: true })
    contactTypes.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat jenis kontak customer.')
  } finally {
    contactTypesLoading.value = false
  }
}

/* Fetch: Tab 1 — kontak customer ini (tidak dipaginate) */
async function fetchCustomerContacts() {
  contactsLoading.value = true
  try {
    const { data } = await customerContactsApi.getAll()
    customerContacts.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat kontak customer.')
  } finally {
    contactsLoading.value = false
  }
}

function resetContactForm() {
  contactFormError.value = null
  contactFormErrors.value = {}
  Object.assign(contactForm, {
    id: null,
    id_contact_type: '',
    full_name: '',
    position: '',
    phone: '',
    mobile: '',
    email: '',
  })
}

function openCreateContact() {
  contactFormMode.value = 'create'
  resetContactForm()
  contactFormOpen.value = true
}

function openEditContact(contact: CustomerContactRecord) {
  contactFormMode.value = 'edit'
  resetContactForm()
  Object.assign(contactForm, {
    id: contact.id,
    id_contact_type: contact.id_contact_type,
    full_name: contact.full_name,
    position: contact.position ?? '',
    phone: contact.phone ?? '',
    mobile: contact.mobile ?? '',
    email: contact.email ?? '',
  })
  contactFormOpen.value = true
}

function closeContactForm() {
  contactFormOpen.value = false
}

/* Action: Tab 1 — simpan kontak (create atau update, tergantung contactFormMode) */
async function submitContactForm() {
  contactFormError.value = null
  contactFormErrors.value = {}

  if (!contactForm.id_contact_type) {
    contactFormError.value = 'Tipe kontak wajib dipilih.'
    return
  }
  if (!contactForm.full_name.trim()) {
    contactFormError.value = 'Nama wajib diisi.'
    return
  }

  const payload = {
    id_contact_type: contactForm.id_contact_type,
    full_name: contactForm.full_name.trim(),
    position: contactForm.position.trim() || null,
    phone: contactForm.phone.trim() || null,
    mobile: contactForm.mobile.trim() || null,
    email: contactForm.email.trim() || null,
  }

  contactFormSaving.value = true
  try {
    if (contactFormMode.value === 'edit' && contactForm.id) {
      const { data } = await customerContactsApi.update(contactForm.id, payload)
      const index = customerContacts.value.findIndex(c => c.id === data.id)
      if (index !== -1) customerContacts.value[index] = data
      success('Berhasil', 'Kontak berhasil diperbarui.')
    } else {
      const { data } = await customerContactsApi.store(payload)
      customerContacts.value.push(data)
      success('Berhasil', 'Kontak berhasil ditambahkan.')
    }
    contactFormOpen.value = false
  } catch (e: any) {
    if (e.response?.status === 422) {
      contactFormErrors.value = e.response?.data?.errors || {}
      contactFormError.value = Object.values(contactFormErrors.value)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      contactFormError.value = e.response?.data?.message ?? 'Gagal menyimpan kontak.'
    }
  } finally {
    contactFormSaving.value = false
  }
}

function confirmDeleteContact(contact: CustomerContactRecord) {
  deleteContactTarget.value = contact
  deleteContactDialogOpen.value = true
}

async function performDeleteContact() {
  const target = deleteContactTarget.value
  if (!target) return

  deleteContactLoading.value = true
  try {
    await customerContactsApi.destroy(target.id)
    customerContacts.value = customerContacts.value.filter(c => c.id !== target.id)
    success('Berhasil', 'Kontak berhasil dihapus.')
    deleteContactDialogOpen.value = false
    deleteContactTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus kontak.')
  } finally {
    deleteContactLoading.value = false
  }
}

/* Fetch: Tab 4 — pengajuan kredit customer ini (tidak dipaginate) */
async function fetchCreditSubmissions() {
  creditSubmissionsLoading.value = true
  try {
    const { data } = await creditSubmissionsApi.getAll()
    creditSubmissions.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat pengajuan kredit customer.')
  } finally {
    creditSubmissionsLoading.value = false
  }
}

/* Fetch: Tab 4 — daftar produk untuk dropdown item pengajuan kredit */
async function fetchProdukOptions() {
  produkOptionsLoading.value = true
  try {
    const { data } = await produksApi.getAll({ as_list: true })
    produkOptions.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat daftar produk.')
  } finally {
    produkOptionsLoading.value = false
  }
}

/* Helper: Tab 4 — API item scoped ke 1 submission (endpoint dinamis, bukan fixed seperti resource lain) */
function creditItemsApi(submissionId: number) {
  return createResourceApi(`/customers/${idCustomer}/credit-submissions/${submissionId}/items`)
}

function findCreditSubmission(submissionId: number) {
  return creditSubmissions.value.find(s => s.id === submissionId)
}

/* Helper: Tab 4 — satuan default dari produk terpilih, dipakai isi awal field unit kalau masih kosong */
function produkUnitLabel(idProduk: number | string) {
  const produk = produkOptions.value.find(p => p.id_produk === Number(idProduk))
  return produk?.ukuran?.satuan?.nama_satuan ?? ''
}

/* Action: Tab 4 — buat pengajuan kredit baru (header + item produk sekaligus, pola add-row sama seperti Tab 3 LCR) */
function openCreateCreditSubmission() {
  creditSubmissionError.value = null
  Object.assign(creditSubmissionForm, { submission_type: '', top_payment: null })
  creditItemRows.value = [emptyCreditItemRow()]
  creditSubmissionFormOpen.value = true
}

function closeCreditSubmissionForm() {
  creditSubmissionFormOpen.value = false
}

const addCreditItemRow = () => creditItemRows.value.push(emptyCreditItemRow())
const removeCreditItemRow = (i: number) => creditItemRows.value.splice(i, 1)

function onCreditItemRowProdukChange(row: CreditItemRow) {
  if (!row.unit) row.unit = produkUnitLabel(row.id_produk)
}

async function submitCreditSubmissionForm() {
  creditSubmissionError.value = null

  if (!creditSubmissionForm.submission_type) {
    creditSubmissionError.value = 'Jenis pengajuan wajib dipilih.'
    return
  }

  const items = creditItemRows.value
    .filter(row => row.id_produk)
    .map(row => ({
      id_produk: Number(row.id_produk),
      volume: row.volume,
      unit: row.unit || null,
      existing_limit: row.existing_limit,
      actual_payment: row.actual_payment,
      guarantee: row.guarantee || null,
      credit_limit_request: row.credit_limit_request,
      top_request: row.top_request,
    }))

  creditSubmissionSaving.value = true
  try {
    const { data } = await creditSubmissionsApi.store({
      submission_type: creditSubmissionForm.submission_type,
      top_payment: creditSubmissionForm.top_payment,
      items,
    })
    creditSubmissions.value.push(data)
    success('Berhasil', 'Pengajuan kredit berhasil dibuat.')
    creditSubmissionFormOpen.value = false
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      creditSubmissionError.value = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      creditSubmissionError.value = e.response?.data?.message ?? 'Gagal membuat pengajuan kredit.'
    }
  } finally {
    creditSubmissionSaving.value = false
  }
}

/* Action: Tab 4 — edit header pengajuan kredit (submission_type + top_payment; item dikelola terpisah lewat endpoint item) */
function openEditCreditSubmission(submission: CustomerCreditSubmissionRecord) {
  editSubmissionError.value = null
  editSubmissionTarget.value = submission
  Object.assign(editSubmissionForm, {
    submission_type: submission.submission_type,
    top_payment: submission.top_payment,
  })
  editSubmissionFormOpen.value = true
}

function closeEditCreditSubmission() {
  editSubmissionFormOpen.value = false
}

async function submitEditCreditSubmission() {
  const target = editSubmissionTarget.value
  if (!target) return

  editSubmissionError.value = null
  if (!editSubmissionForm.submission_type) {
    editSubmissionError.value = 'Jenis pengajuan wajib dipilih.'
    return
  }

  editSubmissionSaving.value = true
  try {
    const { data } = await creditSubmissionsApi.update(target.id, {
      submission_type: editSubmissionForm.submission_type,
      top_payment: editSubmissionForm.top_payment,
    })
    const index = creditSubmissions.value.findIndex(s => s.id === data.id)
    if (index !== -1) creditSubmissions.value[index] = data
    success('Berhasil', 'Pengajuan kredit berhasil diperbarui.')
    editSubmissionFormOpen.value = false
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      editSubmissionError.value = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      editSubmissionError.value = e.response?.data?.message ?? 'Gagal memperbarui pengajuan kredit.'
    }
  } finally {
    editSubmissionSaving.value = false
  }
}

/* Action: Tab 4 — hapus pengajuan kredit (beserta seluruh item, cascade di backend) */
function confirmDeleteCreditSubmission(submission: CustomerCreditSubmissionRecord) {
  deleteSubmissionTarget.value = submission
  deleteSubmissionDialogOpen.value = true
}

async function performDeleteCreditSubmission() {
  const target = deleteSubmissionTarget.value
  if (!target) return

  deleteSubmissionLoading.value = true
  try {
    await creditSubmissionsApi.destroy(target.id)
    creditSubmissions.value = creditSubmissions.value.filter(s => s.id !== target.id)
    success('Berhasil', 'Pengajuan kredit berhasil dihapus.')
    deleteSubmissionDialogOpen.value = false
    deleteSubmissionTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus pengajuan kredit.')
  } finally {
    deleteSubmissionLoading.value = false
  }
}

/* Action: Tab 4 — tambah/edit item produk pada pengajuan kredit yang sudah ada (endpoint item terpisah dari header) */
function resetItemForm() {
  itemFormError.value = null
  Object.assign(itemForm, {
    id: null,
    id_produk: '',
    volume: null,
    unit: '',
    existing_limit: null,
    actual_payment: null,
    guarantee: '',
    credit_limit_request: null,
    credit_limit_approval: null,
    top_request: null,
    top_approval: null,
    notes: '',
  })
}

function openCreateItem(submission: CustomerCreditSubmissionRecord) {
  itemFormMode.value = 'create'
  itemFormSubmissionId.value = submission.id
  resetItemForm()
  itemFormOpen.value = true
}

function openEditItem(submission: CustomerCreditSubmissionRecord, item: CustomerCreditItemRecord) {
  itemFormMode.value = 'edit'
  itemFormSubmissionId.value = submission.id
  resetItemForm()
  Object.assign(itemForm, {
    id: item.id,
    id_produk: item.id_produk,
    volume: item.volume !== null ? Number(item.volume) : null,
    unit: item.unit ?? '',
    existing_limit: item.existing_limit !== null ? Number(item.existing_limit) : null,
    actual_payment: item.actual_payment !== null ? Number(item.actual_payment) : null,
    guarantee: item.guarantee ?? '',
    credit_limit_request: item.credit_limit_request !== null ? Number(item.credit_limit_request) : null,
    credit_limit_approval: item.credit_limit_approval !== null ? Number(item.credit_limit_approval) : null,
    top_request: item.top_request,
    top_approval: item.top_approval,
    notes: item.notes ?? '',
  })
  itemFormOpen.value = true
}

function closeItemForm() {
  itemFormOpen.value = false
}

function onItemFormProdukChange() {
  if (!itemForm.unit) itemForm.unit = produkUnitLabel(itemForm.id_produk)
}

async function submitItemForm() {
  const submissionId = itemFormSubmissionId.value
  if (!submissionId) return

  itemFormError.value = null
  if (!itemForm.id_produk) {
    itemFormError.value = 'Produk wajib dipilih.'
    return
  }

  const payload = {
    id_produk: Number(itemForm.id_produk),
    volume: itemForm.volume,
    unit: itemForm.unit || null,
    existing_limit: itemForm.existing_limit,
    actual_payment: itemForm.actual_payment,
    guarantee: itemForm.guarantee || null,
    credit_limit_request: itemForm.credit_limit_request,
    top_request: itemForm.top_request,
    notes: itemForm.notes || null,
  }

  const itemsApi = creditItemsApi(submissionId)
  itemFormSaving.value = true
  try {
    if (itemFormMode.value === 'edit' && itemForm.id) {
      const { data } = await itemsApi.update(itemForm.id, payload)
      const submission = findCreditSubmission(submissionId)
      if (submission) {
        const index = submission.items.findIndex(i => i.id === data.id)
        if (index !== -1) submission.items[index] = data
      }
      success('Berhasil', 'Item pengajuan kredit berhasil diperbarui.')
    } else {
      const { data } = await itemsApi.store(payload)
      const submission = findCreditSubmission(submissionId)
      if (submission) submission.items.push(data)
      success('Berhasil', 'Item pengajuan kredit berhasil ditambahkan.')
    }
    itemFormOpen.value = false
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      itemFormError.value = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      itemFormError.value = e.response?.data?.message ?? 'Gagal menyimpan item pengajuan kredit.'
    }
  } finally {
    itemFormSaving.value = false
  }
}

function confirmDeleteItem(submission: CustomerCreditSubmissionRecord, item: CustomerCreditItemRecord) {
  deleteItemTarget.value = item
  deleteItemSubmissionId.value = submission.id
  deleteItemDialogOpen.value = true
}

async function performDeleteItem() {
  const target = deleteItemTarget.value
  const submissionId = deleteItemSubmissionId.value
  if (!target || !submissionId) return

  deleteItemLoading.value = true
  try {
    await creditItemsApi(submissionId).destroy(target.id)
    const submission = findCreditSubmission(submissionId)
    if (submission) submission.items = submission.items.filter(i => i.id !== target.id)
    success('Berhasil', 'Item pengajuan kredit berhasil dihapus.')
    deleteItemDialogOpen.value = false
    deleteItemTarget.value = null
    deleteItemSubmissionId.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus item pengajuan kredit.')
  } finally {
    deleteItemLoading.value = false
  }
}

/* Helper: Tab 4 — format tampilan nominal & kuantitas item (API mengembalikan string desimal) */
function formatCreditAmount(value: string | number | null) {
  if (value === null || value === undefined || value === '') return '-'
  const amount = Math.trunc(Number(value))
  if (!Number.isFinite(amount)) return '-'
  return `Rp ${amount.toLocaleString('id-ID')}`
}

function formatQuantity(value: string | number | null) {
  if (value === null || value === undefined || value === '') return '-'
  const n = Number(value)
  return Number.isFinite(n) ? n.toLocaleString('id-ID') : String(value)
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

            <CardSection title="Dokumen Customer"
              description="Upload dan kelola dokumen legal customer (NIB, NPWP, Sertifikat, dst)." icon="FileCheck2"
              icon-class="bg-indigo-100 text-indigo-600" class="mt-4">
              <div v-if="documentTypesLoading || documentsLoading"
                class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
                <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
                <span class="font-body">Memuat dokumen...</span>
              </div>

              <div v-else-if="documentRows.length === 0"
                class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
                <div class="font-body">Belum ada jenis dokumen yang aktif.</div>
              </div>

              <div v-else class="space-y-3">
                <div v-for="row in documentRows" :key="row.type.id" class="rounded-lg border border-slate-200 p-4">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="font-strong">{{ row.type.name }}</span>
                        <span v-if="row.document"
                          class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                          <Lucide icon="CheckCircle2" class="h-3 w-3" /> Sudah diunggah
                        </span>
                        <span v-else
                          class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                          Belum ada file
                        </span>
                      </div>

                      <div v-if="row.document" class="mt-1.5 space-y-0.5">
                        <a :href="row.document.url ?? undefined" target="_blank"
                          class="font-body !text-primary break-all underline">
                          {{ row.document.file_name }}
                        </a>
                        <p v-if="row.document.document_number" class="font-caption">
                          No. Dokumen: {{ row.document.document_number }}
                        </p>
                        <p class="font-caption">
                          Diunggah {{ formatDocumentDate(row.document.uploaded_at) }}
                          <span v-if="row.document.uploaded_by"> oleh {{ row.document.uploaded_by.name }}</span>
                        </p>
                      </div>
                    </div>

                    <div class="flex shrink-0 gap-2">
                      <Button v-if="!rowState(row.type.id).editing && row.document" size="sm"
                        variant="outline-secondary" class="inline-flex items-center gap-2"
                        @click="startDocumentUpload(row.type.id)">
                        <Lucide icon="RefreshCw" class="h-4 w-4" /> Ganti
                      </Button>
                      <Button v-else-if="!rowState(row.type.id).editing" size="sm" variant="outline-primary"
                        class="inline-flex items-center gap-2" @click="startDocumentUpload(row.type.id)">
                        <Lucide icon="Upload" class="h-4 w-4" /> Upload
                      </Button>

                      <Button v-if="row.document" size="sm" variant="soft-danger" title="Hapus"
                        class="!h-8 !w-8 !p-0 !shadow-none" @click="confirmDeleteDocument(row.document)">
                        <Lucide icon="Trash2" class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  <div v-if="rowState(row.type.id).editing" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                    <div v-if="row.type.requires_number">
                      <FormLabel>Nomor Dokumen *</FormLabel>
                      <FormInput v-model="rowState(row.type.id).documentNumber" placeholder="Masukkan nomor dokumen" />
                    </div>

                    <FileUploadField v-model="rowState(row.type.id).file" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
                      :max-size-mb="10" :error="rowState(row.type.id).error" choose-text="Pilih file"
                      empty-text="Belum ada file dipilih"
                      @error="(msg: string) => (rowState(row.type.id).error = msg)" />

                    <div class="flex justify-end gap-2">
                      <Button size="sm" variant="outline-secondary" :disabled="rowState(row.type.id).uploading"
                        @click="cancelDocumentUpload(row.type.id)">
                        Batal
                      </Button>
                      <Button size="sm" variant="primary" class="inline-flex items-center gap-2"
                        :disabled="rowState(row.type.id).uploading" @click="submitDocumentUpload(row)">
                        <Lucide v-if="rowState(row.type.id).uploading" icon="Loader2"
                          class="h-4 w-4 animate-spin" />
                        Simpan
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </CardSection>

            <DeleteRecordDialog :open="deleteDocumentDialogOpen" title="Hapus Dokumen"
              :description="`Dokumen ${deleteDocumentTarget?.document_type?.name ?? ''} milik customer ini akan dihapus permanen.`"
              :loading="deleteDocumentLoading" @close="deleteDocumentDialogOpen = false"
              @confirm="performDeleteDocument" />

            <CardSection title="Kontak Customer"
              description="Kelola PIC/kontak customer per tipe (Direktur, Procurement, Finance, PIC Site, dst)."
              icon="Users" icon-class="bg-cyan-100 text-cyan-600" class="mt-4">
              <template #action>
                <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2"
                  @click="openCreateContact">
                  <Lucide icon="Plus" class="h-4 w-4" />
                  Tambah Kontak
                </Button>
              </template>

              <div v-if="contactTypesLoading || contactsLoading"
                class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
                <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
                <span class="font-body">Memuat kontak...</span>
              </div>

              <div v-else-if="customerContacts.length === 0"
                class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
                <div class="font-body">Belum ada kontak yang ditambahkan.</div>
              </div>

              <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead class="bg-slate-50">
                    <tr>
                      <th class="px-3 py-2 text-xs uppercase text-left">Tipe</th>
                      <th class="px-3 py-2 text-xs uppercase text-left">Nama</th>
                      <th class="px-3 py-2 text-xs uppercase text-left">Posisi</th>
                      <th class="px-3 py-2 text-xs uppercase text-left">Telepon</th>
                      <th class="px-3 py-2 text-xs uppercase text-left">Mobile</th>
                      <th class="px-3 py-2 text-xs uppercase text-left">Email</th>
                      <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="contact in customerContacts" :key="contact.id" class="border-b">
                      <td class="px-3 py-2">
                        <span
                          class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                          {{ contact.contact_type?.name ?? '-' }}
                        </span>
                      </td>
                      <td class="px-3 py-2 font-strong">{{ contact.full_name }}</td>
                      <td class="px-3 py-2">{{ contact.position || '-' }}</td>
                      <td class="px-3 py-2">{{ contact.phone || '-' }}</td>
                      <td class="px-3 py-2">{{ contact.mobile || '-' }}</td>
                      <td class="px-3 py-2">{{ contact.email || '-' }}</td>
                      <td class="px-3 py-2 text-center space-x-2">
                        <Button size="sm" variant="soft-pending" title="Edit" class="!h-8 !w-8 !p-0 !shadow-none"
                          @click="openEditContact(contact)">
                          <Lucide icon="Edit" class="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="soft-danger" title="Hapus" class="!h-8 !w-8 !p-0 !shadow-none"
                          @click="confirmDeleteContact(contact)">
                          <Lucide icon="Trash2" class="h-4 w-4" />
                        </Button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardSection>

            <FormModal :open="contactFormOpen" :title="contactFormMode === 'create' ? 'Tambah Kontak' : 'Edit Kontak'"
              description="Data kontak/PIC customer." :loading="contactFormSaving" :error="contactFormError"
              :submit-text="contactFormMode === 'create' ? 'Tambah' : 'Simpan'"
              :submit-icon="contactFormMode === 'create' ? 'PlusCircle' : 'Save'" @close="closeContactForm"
              @submit="submitContactForm">
              <div class="space-y-3">
                <div>
                  <FormLabel>Tipe Kontak *</FormLabel>
                  <FormSelect v-model="contactForm.id_contact_type">
                    <option value="">- Pilihan -</option>
                    <option v-for="t in activeContactTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                  </FormSelect>
                </div>
                <div>
                  <FormLabel>Nama Lengkap *</FormLabel>
                  <FormInput v-model="contactForm.full_name" placeholder="Nama lengkap" />
                </div>
                <div>
                  <FormLabel>Posisi/Jabatan</FormLabel>
                  <FormInput v-model="contactForm.position" placeholder="Contoh: Finance Manager" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <FormLabel>Telepon</FormLabel>
                    <FormInput v-model="contactForm.phone" placeholder="021-xxxxxxx" />
                  </div>
                  <div>
                    <FormLabel>Mobile</FormLabel>
                    <FormInput v-model="contactForm.mobile" placeholder="08xx-xxxx-xxxx" />
                  </div>
                </div>
                <div>
                  <FormLabel>Email</FormLabel>
                  <FormInput v-model="contactForm.email" type="email" placeholder="nama@email.com" />
                </div>
              </div>
            </FormModal>

            <DeleteRecordDialog :open="deleteContactDialogOpen" title="Hapus Kontak"
              :description="`Kontak ${deleteContactTarget?.full_name ?? ''} akan dihapus permanen.`"
              :loading="deleteContactLoading" @close="deleteContactDialogOpen = false"
              @confirm="performDeleteContact" />
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

                  <div>
                    <CurrencyField v-model="reviewForm.credit_limit_proposed" label="Credit Limit Diajukan" />
                  </div>

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

          <!-- TAB 4: Credit Application / TOP (customer_credit_submissions + customer_credit_items, CRUD independen dari siklus verifikasi Tab 2) -->
          <Tab.Panel>
            <div class="space-y-4">
              <CardSection v-if="creditSubmissionFormOpen" title="Form Pengajuan Kredit Baru"
                description="Jenis pengajuan, usulan TOP, dan item produk yang diajukan." icon="FilePlus"
                icon-class="bg-teal-100 text-teal-600">
                <div v-if="creditSubmissionError"
                  class="font-body mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 !text-rose-700">
                  {{ creditSubmissionError }}
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <div>
                    <FormLabel>Jenis Pengajuan *</FormLabel>
                    <FormSelect v-model="creditSubmissionForm.submission_type">
                      <option value="">- Pilihan -</option>
                      <option v-for="opt in creditSubmissionTypeOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </FormSelect>
                  </div>
                  <NumberField v-model="creditSubmissionForm.top_payment" label="Usulan TOP" suffix="hari"
                    :decimals="0" />
                </div>

                <div class="mt-6">
                  <div class="font-section mb-2">Item Produk</div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-slate-50">
                        <tr>
                          <th class="px-3 py-2 text-xs uppercase">Produk</th>
                          <th class="px-3 py-2 text-xs uppercase">Volume</th>
                          <th class="px-3 py-2 text-xs uppercase">Unit</th>
                          <th class="px-3 py-2 text-xs uppercase">Existing Limit</th>
                          <th class="px-3 py-2 text-xs uppercase">Actual Payment</th>
                          <th class="px-3 py-2 text-xs uppercase">Guarantee</th>
                          <th class="px-3 py-2 text-xs uppercase">Credit Limit Request</th>
                          <th class="px-3 py-2 text-xs uppercase">Credit Limit Approval</th>
                          <th class="px-3 py-2 text-xs uppercase">TOP Request</th>
                          <th class="px-3 py-2 text-xs uppercase">TOP Approval</th>
                          <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, i) in creditItemRows" :key="i" class="border-b">
                          <td class="px-3 py-2">
                            <FormSelect v-model="row.id_produk" class="min-w-[160px]" :disabled="produkOptionsLoading"
                              @change="onCreditItemRowProdukChange(row)">
                              <option value="">{{ produkOptionsLoading ? 'Memuat produk...' : '- Pilih -' }}</option>
                              <option v-for="p in produkOptions" :key="p.id_produk" :value="p.id_produk">
                                {{ p.nama_produk }}
                              </option>
                            </FormSelect>
                          </td>
                          <td class="px-3 py-2">
                            <NumberField v-model="row.volume" class="min-w-[100px]" :decimals="4" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="row.unit" class="min-w-[80px]" placeholder="M3" />
                          </td>
                          <td class="px-3 py-2">
                            <CurrencyField v-model="row.existing_limit" class="min-w-[140px]" />
                          </td>
                          <td class="px-3 py-2">
                            <CurrencyField v-model="row.actual_payment" class="min-w-[140px]" />
                          </td>
                          <td class="px-3 py-2">
                            <FormInput v-model="row.guarantee" class="min-w-[120px]" />
                          </td>
                          <td class="px-3 py-2">
                            <CurrencyField v-model="row.credit_limit_request" class="min-w-[160px]" />
                          </td>
                          <td class="px-3 py-2">
                            <CurrencyField :model-value="null" class="min-w-[160px]" disabled
                              placeholder="Diisi saat approval" />
                          </td>
                          <td class="px-3 py-2">
                            <NumberField v-model="row.top_request" class="min-w-[90px]" suffix="hari" :decimals="0" />
                          </td>
                          <td class="px-3 py-2">
                            <NumberField :model-value="null" class="min-w-[90px]" suffix="hari" :decimals="0" disabled
                              placeholder="Diisi saat approval" />
                          </td>
                          <td class="px-3 py-2 text-center space-x-2">
                            <Button size="sm" variant="soft-primary" title="Tambah baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" @click="addCreditItemRow">
                              <Lucide icon="Plus" class="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="soft-danger" title="Hapus baris"
                              class="!h-8 !w-8 !p-0 !shadow-none" :disabled="creditItemRows.length === 1"
                              @click="removeCreditItemRow(i)">
                              <Lucide icon="Trash2" class="h-4 w-4" />
                            </Button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p class="font-caption mt-2">Baris tanpa produk dipilih tidak akan disimpan.</p>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                  <Button variant="outline-secondary" :disabled="creditSubmissionSaving"
                    @click="closeCreditSubmissionForm">
                    Batal
                  </Button>
                  <Button variant="primary" class="inline-flex items-center gap-2" :disabled="creditSubmissionSaving"
                    @click="submitCreditSubmissionForm">
                    <Lucide v-if="creditSubmissionSaving" icon="Loader2" class="h-4 w-4 animate-spin" />
                    <Lucide v-else icon="Save" class="h-4 w-4" />
                    Simpan Pengajuan
                  </Button>
                </div>
              </CardSection>

              <CardSection title="Pengajuan Credit Limit & Term of Payment (TOP)"
                description="Data entry pengajuan kredit per produk — siklus approval terpisah, dikelola independen dari Sales Review."
                icon="Wallet" icon-class="bg-teal-100 text-teal-600">
                <template #action>
                  <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2"
                    :disabled="creditSubmissionFormOpen" @click="openCreateCreditSubmission">
                    <Lucide icon="Plus" class="h-4 w-4" />
                    Buat Pengajuan Baru
                  </Button>
                </template>

                <div v-if="creditSubmissionsLoading"
                  class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
                  <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
                  <span class="font-body">Memuat pengajuan kredit...</span>
                </div>

                <div v-else-if="creditSubmissions.length === 0"
                  class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                  <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
                  <div class="font-body">Belum ada pengajuan kredit yang dibuat.</div>
                </div>

                <div v-else class="space-y-4">
                  <div v-for="submission in creditSubmissions" :key="submission.id"
                    class="rounded-lg border border-slate-200">
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                      <span
                        class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
                        {{ submission.submission_type_label }}
                      </span>
                      <div class="font-body">
                        TOP: <span class="font-strong">{{ submission.top_payment ?? '-' }} hari</span>
                      </div>
                      <div class="font-body">{{ submission.items.length }} item produk</div>
                      <span
                        class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                        {{ submission.approval ? 'Approval berjalan' : 'Belum diajukan approval' }}
                      </span>

                      <div class="ml-auto flex items-center gap-2">
                        <Button size="sm" variant="soft-pending" title="Edit" class="!h-8 !w-8 !p-0 !shadow-none"
                          @click="openEditCreditSubmission(submission)">
                          <Lucide icon="Edit" class="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="soft-danger" title="Hapus" class="!h-8 !w-8 !p-0 !shadow-none"
                          @click="confirmDeleteCreditSubmission(submission)">
                          <Lucide icon="Trash2" class="h-4 w-4" />
                        </Button>
                      </div>
                    </div>

                    <div class="border-t border-slate-200 px-4 py-3">
                      <div class="mb-3 flex items-center justify-between">
                        <div class="font-section">Item Produk</div>
                        <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2"
                          @click="openCreateItem(submission)">
                          <Lucide icon="Plus" class="h-4 w-4" />
                          Tambah Item
                        </Button>
                      </div>

                      <div v-if="submission.items.length === 0" class="font-body text-slate-500">
                        Belum ada item produk untuk pengajuan ini.
                      </div>

                      <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                          <thead class="bg-slate-50">
                            <tr>
                              <th class="px-3 py-2 text-xs uppercase text-left">Produk</th>
                              <th class="px-3 py-2 text-xs uppercase text-left">Volume</th>
                              <th class="px-3 py-2 text-xs uppercase text-left">Existing Limit</th>
                              <th class="px-3 py-2 text-xs uppercase text-left">Actual Payment</th>
                              <th class="px-3 py-2 text-xs uppercase text-left">Guarantee</th>
                              <th class="px-3 py-2 text-xs uppercase text-right">Credit Limit Request</th>
                              <th class="px-3 py-2 text-xs uppercase text-right">Credit Limit Approval</th>
                              <th class="px-3 py-2 text-xs uppercase text-center">TOP Request</th>
                              <th class="px-3 py-2 text-xs uppercase text-center">TOP Approval</th>
                              <th class="px-3 py-2 text-xs uppercase text-left">Catatan</th>
                              <th class="px-3 py-2 text-xs uppercase text-center">Aksi</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="item in submission.items" :key="item.id" class="border-b">
                              <td class="px-3 py-2 font-strong">{{ item.produk?.nama_produk ?? '-' }}</td>
                              <td class="px-3 py-2">{{ formatQuantity(item.volume) }} {{ item.unit }}</td>
                              <td class="px-3 py-2">{{ formatCreditAmount(item.existing_limit) }}</td>
                              <td class="px-3 py-2">{{ formatCreditAmount(item.actual_payment) }}</td>
                              <td class="px-3 py-2">{{ dash(item.guarantee) }}</td>
                              <td class="px-3 py-2 text-right">{{ formatCreditAmount(item.credit_limit_request) }}</td>
                              <td class="px-3 py-2 text-right">{{ formatCreditAmount(item.credit_limit_approval) }}</td>
                              <td class="px-3 py-2 text-center">
                                {{ item.top_request != null ? item.top_request + ' hari' : '-' }}
                              </td>
                              <td class="px-3 py-2 text-center">
                                {{ item.top_approval != null ? item.top_approval + ' hari' : '-' }}
                              </td>
                              <td class="px-3 py-2">{{ dash(item.notes) }}</td>
                              <td class="px-3 py-2 text-center space-x-2">
                                <Button size="sm" variant="soft-pending" title="Edit"
                                  class="!h-8 !w-8 !p-0 !shadow-none" @click="openEditItem(submission, item)">
                                  <Lucide icon="Edit" class="h-4 w-4" />
                                </Button>
                                <Button size="sm" variant="soft-danger" title="Hapus"
                                  class="!h-8 !w-8 !p-0 !shadow-none" @click="confirmDeleteItem(submission, item)">
                                  <Lucide icon="Trash2" class="h-4 w-4" />
                                </Button>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </CardSection>
            </div>

            <FormModal :open="editSubmissionFormOpen" title="Edit Pengajuan Kredit"
              description="Ubah jenis pengajuan & usulan TOP. Item produk dikelola lewat bagian Item Produk di bawah daftar."
              :loading="editSubmissionSaving" :error="editSubmissionError" submit-text="Simpan" submit-icon="Save"
              @close="closeEditCreditSubmission" @submit="submitEditCreditSubmission">
              <div class="space-y-3">
                <div>
                  <FormLabel>Jenis Pengajuan *</FormLabel>
                  <FormSelect v-model="editSubmissionForm.submission_type">
                    <option value="">- Pilihan -</option>
                    <option v-for="opt in creditSubmissionTypeOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </FormSelect>
                </div>
                <NumberField v-model="editSubmissionForm.top_payment" label="Usulan TOP" suffix="hari"
                  :decimals="0" />
              </div>
            </FormModal>

            <DeleteRecordDialog :open="deleteSubmissionDialogOpen" title="Hapus Pengajuan Kredit"
              :description="`Pengajuan ${deleteSubmissionTarget?.submission_type_label ?? ''} beserta seluruh item produknya akan dihapus permanen.`"
              :loading="deleteSubmissionLoading" @close="deleteSubmissionDialogOpen = false"
              @confirm="performDeleteCreditSubmission" />

            <FormModal :open="itemFormOpen"
              :title="itemFormMode === 'create' ? 'Tambah Item Produk' : 'Edit Item Produk'"
              description="Detail per produk untuk pengajuan kredit ini." :loading="itemFormSaving"
              :error="itemFormError" :submit-text="itemFormMode === 'create' ? 'Tambah' : 'Simpan'"
              :submit-icon="itemFormMode === 'create' ? 'PlusCircle' : 'Save'" size="lg" @close="closeItemForm"
              @submit="submitItemForm">
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                  <FormLabel>Produk *</FormLabel>
                  <FormSelect v-model="itemForm.id_produk" :disabled="produkOptionsLoading"
                    @change="onItemFormProdukChange">
                    <option value="">{{ produkOptionsLoading ? 'Memuat produk...' : '- Pilih -' }}</option>
                    <option v-for="p in produkOptions" :key="p.id_produk" :value="p.id_produk">
                      {{ p.nama_produk }}
                    </option>
                  </FormSelect>
                </div>
                <NumberField v-model="itemForm.volume" label="Volume" :decimals="4" />
                <div>
                  <FormLabel>Unit</FormLabel>
                  <FormInput v-model="itemForm.unit" placeholder="M3" />
                </div>
                <CurrencyField v-model="itemForm.existing_limit" label="Existing Limit" />
                <CurrencyField v-model="itemForm.actual_payment" label="Actual Payment" />
                <div>
                  <FormLabel>Guarantee</FormLabel>
                  <FormInput v-model="itemForm.guarantee" placeholder="Bank Guarantee" />
                </div>
                <CurrencyField v-model="itemForm.credit_limit_request" label="Credit Limit Request" />
                <CurrencyField :model-value="itemForm.credit_limit_approval" label="Credit Limit Approval" disabled
                  placeholder="Diisi saat approval" />
                <NumberField v-model="itemForm.top_request" label="TOP Request" suffix="hari" :decimals="0" />
                <NumberField :model-value="itemForm.top_approval" label="TOP Approval" suffix="hari" :decimals="0"
                  disabled placeholder="Diisi saat approval" />
                <div class="sm:col-span-2">
                  <FormLabel>Catatan</FormLabel>
                  <FormTextarea v-model="itemForm.notes" :rows="2" />
                </div>
              </div>
            </FormModal>

            <DeleteRecordDialog :open="deleteItemDialogOpen" title="Hapus Item Produk"
              :description="`Item ${deleteItemTarget?.produk?.nama_produk ?? ''} akan dihapus permanen dari pengajuan ini.`"
              :loading="deleteItemLoading" @close="deleteItemDialogOpen = false" @confirm="performDeleteItem" />
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
