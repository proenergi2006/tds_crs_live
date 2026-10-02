<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import TomSelect from '@/components/Base/TomSelect'
import { FormCheck, FormInput, FormLabel, FormSwitch, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormWizardModal from '@/components/SystemDesign/Form/FormWizardModal.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import ImageUploadField from '@/components/SystemDesign/Form/ImageUploadField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useRegionCascade } from '@/composables/useRegionCascade'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi'
import { typeBusinessOptions } from '@/pages/CustomerOnboarding/optionSets'

const props = withDefaults(defineProps<{
  idCustomer: number
  customerLogistik?: Record<string, any> | null
  readonly?: boolean
}>(), {
  customerLogistik: null,
  readonly: false,
})

const emit = defineEmits<{ (e: 'saved'): void }>()

const { success, error: notifyError } = useNotification()
const auth = useAuthStore()

const api = createResourceApi(`/customers/${props.idCustomer}/lcr-sites`)
const documentsApi = createResourceApi(`/customers/${props.idCustomer}/documents`)
const documentTypesApi = createResourceApi('/customer-document-types')

const sites = ref<any[]>([])
const loading = ref(true)

const wilayahOaOptions = ref<any[]>([])

// dropdownParent body: biar dropdown gak kepotong overflow container modal
const wilayahOaSelectOptions = { dropdownParent: 'body' as const }

const wizardOpen = ref(false)
const wizardError = ref<string | null>(null)
const saving = ref(false)
const currentStep = ref(0)
const editingSite = ref<any | null>(null)

const region = useRegionCascade()

const isHydratingRegion = ref(false)

const logisticClaimModalOpen = ref(false)

const steps = [
  { title: 'Identitas & Lokasi' },
  { title: 'Profil Bisnis & Operasional' },
  { title: 'Akses & Rute' },
  { title: 'Unloading & Storage' },
  { title: 'Verifikasi Quality/Quantity' },
  { title: 'Vessel Info' },
  { title: 'Dokumentasi Foto' },
]

const deleteDialogOpen = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<any | null>(null)

const storageTypeOptions = [
  { value: 'indoor', label: 'Indoor' },
  { value: 'outdoor', label: 'Outdoor' },
]

const siteEnvironmentOptions = [
  { value: 'industrial', label: 'Industri' },
  { value: 'residential', label: 'Pemukiman' },
]

const qualityCheckingOptions = [
  { value: 'lab_test', label: 'Lab Test' },
]

const vesselQualityCheckingOptions = [
  { value: 'lab_test', label: 'Lab Test' },
]

const quantityCheckingOptions = [
  { value: 'weighbridge_truck_scale', label: 'Weighbridge (Truck Scale)' },
  { value: 'platform_scale', label: 'Platform Scale' },
  { value: 'volume_measurement', label: 'Volume Measurement' },
  { value: 'truck_counting', label: 'Truck Counting' },
  { value: 'delivery_order_verification', label: 'Delivery Order Verification' },
  { value: 'net_weight_verification', label: 'Net Weight Verification' },
  { value: 'sampling', label: 'Sampling' },
]

const vesselTypeOptions = [
  { value: 'bulk_carrier', label: 'Bulk Carrier' },
  { value: 'barge', label: 'Tongkang (Barge)' },
  { value: 'self_propelled_barge', label: 'Self-Propelled Barge (SPOB)' },
  { value: 'other', label: 'Lainnya' },
]
const vesselUnloadingMethodOptions = [
  { value: 'grab_crane', label: 'Grab Crane' },
  { value: 'conveyor_belt', label: 'Conveyor Belt' },
  { value: 'floating_crane', label: 'Floating Crane' },
  { value: 'other', label: 'Lainnya' },
]
const vesselQuantityCheckingMethodOptions = [
  { value: 'draft_survey', label: 'Draft Survey' },
  { value: 'weighbridge_after_unload', label: 'Weighbridge Setelah Bongkar' },
  { value: 'loadmaster_certificate', label: 'Loadmaster Certificate' },
  { value: 'other', label: 'Lainnya' },
]

const PHOTO_CATEGORIES = [
  { field: 'road_condition_photos', code: 'lcr_road_condition', label: 'Foto Kondisi Jalan Menuju Lokasi' },
  { field: 'site_layout_photos', code: 'lcr_site_layout', label: 'Foto Layout Site/Pabrik' },
  { field: 'unloading_layout_photos', code: 'lcr_unloading_layout', label: 'Foto Layout Area Unloading' },
  { field: 'storage_facility_photos', code: 'lcr_storage_facility', label: 'Foto Fasilitas Penyimpanan' },
  { field: 'measurement_evidence_photos', code: 'lcr_measurement_evidence', label: 'Foto Alat Ukur' },
  { field: 'vessel_layout_photos', code: 'lcr_vessel_layout', label: 'Foto Layout Vessel/Jetty' },
  { field: 'company_office_photos', code: 'lcr_company_office', label: 'Foto Kantor & Gerbang Perusahaan' },
  { field: 'additional_photos', code: 'lcr_additional', label: 'Foto Tambahan' },
] as const
type PhotoField = typeof PHOTO_CATEGORIES[number]['field']

interface LcrDocumentRecord {
  id: number
  id_document_type: number
  file_name: string
  url: string | null
  notes: string | null
}

function createDefaultForm() {
  return {
    site_name: '',
    address_line: '',
    province_id: '',
    regency_id: '',
    district_id: '',
    village_id: '',
    postal_code: '',
    survey_date: '',
    surveyor_names: '',
    site_business_type: '',
    site_business_type_other: '',
    site_environment: '',
    site_environment_other: '',
    site_environment_notes: '',
    competitors: '',
    operating_hours: '',
    product_volume: [{ produk: '', volume_bulan: '' }] as { produk: string; volume_bulan: string }[],
    survey_notes: '',
    contact: { id_contact: null, full_name: '', position: '', mobile: '', email: '' } as
      { id_contact: number | null; full_name: string; position: string; mobile: string; email: string },
    id_wil_oa: '',
    supports_vessel_delivery: false,

    max_truck_capacity_min: null as number | null,
    max_truck_capacity_max: null as number | null,
    access_notes: '',
    route_costs: [{ cost_type: '', amount: null as number | null, notes: '' }],
    distance_from_depot: '',
    min_vol_kirim: '',
    rute_lokasi: '',
    note_lokasi: '',

    unloading_method: '',
    max_trucks_per_day: null as number | null,
    unloading_notes: '',

    storage_type: '',
    storage_type_other: '',
    storage_capacity: '',
    storage_notes: '',

    quality_checking_method: [] as string[],
    quality_checking_method_other: '',
    quality_checking_notes: '',
    quantity_checking_method: [] as string[],
    quantity_checking_method_other: '',
    quantity_checking_notes: '',

    vessel_type: '',
    vessel_type_other: '',
    vessel_cargo_capacity: '',
    vessel_unloading_method: '',
    vessel_unloading_method_other: '',
    vessel_quantity_checking_method: [] as string[],
    vessel_quantity_checking_method_other: '',
    vessel_quantity_checking_notes: '',
    vessel_quality_checking_method: [] as string[],
    vessel_quality_checking_method_other: '',
    vessel_quality_checking_notes: '',
    jetty_type: '',
    max_loa: null as number | null,
    min_pbl: null as number | null,
    draft_lws: null as number | null,
    jetty_capacity_dwt: null as number | null,
    jetty_permit_info: '',
    document_requirements: '',

    latitude: null as number | null,
    longitude: null as number | null,
    google_maps_link: '',
  }
}

const form = reactive(createDefaultForm())

const documentTypeMap = reactive<Record<string, number>>({})
let documentTypeMapReady: Promise<void> | null = null

const pendingUploadFiles = reactive<Record<PhotoField, File[]>>(
  Object.fromEntries(PHOTO_CATEGORIES.map(c => [c.field, []])) as Record<PhotoField, File[]>,
)
const pendingUploadCaptions: Record<PhotoField, Map<File, string>> = Object.fromEntries(
  PHOTO_CATEGORIES.map(c => [c.field, new Map<File, string>()]),
) as Record<PhotoField, Map<File, string>>
const editUploadSelection = reactive<Record<PhotoField, File[] | null>>(
  Object.fromEntries(PHOTO_CATEGORIES.map(c => [c.field, null])) as Record<PhotoField, File[] | null>,
)
const lcrDocuments = reactive<Record<PhotoField, LcrDocumentRecord[]>>(
  Object.fromEntries(PHOTO_CATEGORIES.map(c => [c.field, []])) as Record<PhotoField, LcrDocumentRecord[]>,
)

const requiredMsg = (label: string) => helpers.withMessage(`${label} wajib diisi.`, required)
const wizardRules = {
  site_name: { required: requiredMsg('Nama lokasi') },
  address_line: { required: requiredMsg('Alamat site') },
  province_id: { required: requiredMsg('Provinsi') },
  regency_id: { required: requiredMsg('Kabupaten/Kota') },
  district_id: { required: requiredMsg('Kecamatan') },
  village_id: { required: requiredMsg('Kelurahan/Desa') },
  latitude: { required: requiredMsg('Koordinat lokasi (link Google Maps)') },
  longitude: { required: requiredMsg('Koordinat lokasi (link Google Maps)') },
  id_wil_oa: { required: requiredMsg('Wilayah OA') },
  survey_date: { required: requiredMsg('Tanggal survey') },
  surveyor_names: { required: requiredMsg('Nama surveyor') },
}
const v$ = useVuelidate(wizardRules, form)
const siteNameError = computed(() => v$.value.site_name?.$errors[0]?.$message?.toString() || '')

const mapPreviewUrl = computed(() => {
  if (form.latitude === null || form.longitude === null) return ''
  return `https://maps.google.com/maps?q=${encodeURIComponent(`${form.latitude},${form.longitude}`)}&z=15&output=embed`
})

watch(
  () => form.province_id,
  async (newProv) => {
    if (isHydratingRegion.value) return
    form.regency_id = ''
    await region.fetchRegencies(newProv || null)
  }
)

watch(
  () => form.regency_id,
  async (newRegency) => {
    if (isHydratingRegion.value) return
    form.district_id = ''
    await region.fetchDistricts(newRegency || null)
  }
)

watch(
  () => form.district_id,
  async (newDistrict) => {
    if (isHydratingRegion.value) return
    form.village_id = ''
    await region.fetchVillages(newDistrict || null)
  }
)

watch(
  () => form.village_id,
  (newVillage) => {
    if (isHydratingRegion.value) return
    if (!newVillage) return
    const matched = region.villages.value.find((v) => v.id === newVillage)
    if (matched && matched.postal_code) {
      form.postal_code = matched.postal_code
    }
  }
)

async function fetchSites() {
  loading.value = true
  try {
    const { data } = await api.getAll()
    sites.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat site LCR.')
  } finally {
    loading.value = false
  }
}

function fetchDocumentTypeMap(): Promise<void> {
  documentTypeMapReady = (async () => {
    try {
      const { data } = await documentTypesApi.getAll({ as_list: true })
      const list = Array.isArray(data) ? data : []
      for (const type of list) {
        if (typeof type.code === 'string' && type.code.startsWith('lcr_')) {
          documentTypeMap[type.code] = type.id
        }
      }
    } catch (e: any) {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat tipe dokumen LCR.')
    }
  })()
  return documentTypeMapReady
}

// nunggu documentTypeMapReady: tanpa ini filter id_document_type bisa kosong kalau wizard dibuka sebelum fetch types selesai
async function fetchLcrDocuments(idLcr: number): Promise<void> {
  if (documentTypeMapReady) await documentTypeMapReady
  try {
    const { data } = await documentsApi.getAll({ id_lcr: idLcr })
    const list: LcrDocumentRecord[] = Array.isArray(data) ? data : []
    for (const category of PHOTO_CATEGORIES) {
      lcrDocuments[category.field] = list.filter(d => d.id_document_type === documentTypeMap[category.code])
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat foto site LCR.')
  }
}

async function uploadLcrDocument(file: File, categoryCode: string, idLcr: number, notes: string): Promise<LcrDocumentRecord> {
  if (documentTypeMapReady) await documentTypeMapReady
  const formData = new FormData()
  formData.append('id_document_type', String(documentTypeMap[categoryCode]))
  formData.append('file', file)
  formData.append('id_lcr', String(idLcr))
  if (notes) formData.append('notes', notes)

  const { data } = await documentsApi.store(formData)
  return { id: data.id, id_document_type: data.id_document_type, file_name: data.file_name, url: data.url, notes: data.notes }
}

async function deleteLcrDocument(id: number): Promise<void> {
  await documentsApi.destroy(id)
}

async function updateLcrDocumentNotes(id: number, notes: string): Promise<LcrDocumentRecord> {
  const { data } = await documentsApi.update(id, { notes })
  return { id: data.id, id_document_type: data.id_document_type, file_name: data.file_name, url: data.url, notes: data.notes }
}

function toExistingFiles(records: LcrDocumentRecord[]): { id: number; name: string; url: string; caption: string | null }[] {
  return records.map(r => ({ id: r.id, name: r.file_name, url: r.url ?? '', caption: r.notes }))
}

const isEditMode = computed(() => !!editingSite.value)

const wizardTitle = computed(() => {
  if (props.readonly) return 'Detail Site LCR'
  return editingSite.value ? 'Edit Site LCR' : 'Tambah Site LCR'
})

const wizardDescription = computed(() =>
  props.readonly ? 'Data survei lokasi LCR.' : 'Isi data survei lokasi LCR secara bertahap.',
)

function photoModelValue(field: PhotoField): File[] {
  return isEditMode.value ? (editUploadSelection[field] ?? []) : pendingUploadFiles[field]
}

function photoExistingFiles(field: PhotoField): { id: number; name: string; url: string }[] {
  return isEditMode.value ? toExistingFiles(lcrDocuments[field]) : []
}

async function handlePhotoFileChange(field: PhotoField, value: File | File[] | null) {
  if (props.readonly) return

  if (!isEditMode.value) {
    pendingUploadFiles[field] = Array.isArray(value) ? value : value ? [value] : []
    return
  }

  const files = Array.isArray(value) ? value : value ? [value] : []
  const category = PHOTO_CATEGORIES.find(c => c.field === field)!
  for (const file of files) {
    try {
      const uploaded = await uploadLcrDocument(file, category.code, editingSite.value.id_lcr, '')
      lcrDocuments[field].push(uploaded)
    } catch (e: any) {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengunggah foto.')
    }
  }
  editUploadSelection[field] = null
}

async function handleRemoveExistingPhoto(field: PhotoField, file: { id?: string | number; name: string; url: string }) {
  if (props.readonly) return

  const id = Number(file.id)
  if (Number.isNaN(id)) return

  try {
    await deleteLcrDocument(id)
    lcrDocuments[field] = lcrDocuments[field].filter(d => d.id !== id)
    success('Berhasil', 'Foto berhasil dihapus.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus foto.')
  }
}

const captionUpdateTimers = new Map<number, ReturnType<typeof setTimeout>>()

function handlePhotoCaptionChange(
  field: PhotoField,
  file: File | { id?: string | number; name: string; url: string },
  caption: string,
) {
  if (props.readonly) return

  if (file instanceof File) {
    pendingUploadCaptions[field].set(file, caption)
    return
  }
  const id = Number(file.id)
  if (Number.isNaN(id)) return

  const existingTimer = captionUpdateTimers.get(id)
  if (existingTimer) clearTimeout(existingTimer)

  captionUpdateTimers.set(
    id,
    setTimeout(async () => {
      try {
        await updateLcrDocumentNotes(id, caption)
        const doc = lcrDocuments[field].find(d => d.id === id)
        if (doc) doc.notes = caption
      } catch (e: any) {
        notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan keterangan foto.')
      } finally {
        captionUpdateTimers.delete(id)
      }
    }, 600),
  )
}

function approvalBadgeClass(status?: string | null) {
  switch (status) {
    case 'approved':
      return 'bg-emerald-100 text-emerald-700'
    case 'rejected':
      return 'bg-rose-100 text-rose-700'
    case 'in_progress':
      return 'bg-amber-100 text-amber-700'
    default:
      return 'bg-slate-100 text-slate-500'
  }
}

function addProductVolumeRow() {
  if (props.readonly) return
  form.product_volume.push({ produk: '', volume_bulan: '' })
}
function removeProductVolumeRow(index: number) {
  if (props.readonly) return
  if (form.product_volume.length > 1) form.product_volume.splice(index, 1)
  else form.product_volume.splice(index, 1, { produk: '', volume_bulan: '' })
}

function addRouteCostRow() {
  if (props.readonly) return
  form.route_costs.push({ cost_type: '', amount: null, notes: '' })
}
function removeRouteCostRow(index: number) {
  if (props.readonly) return
  if (form.route_costs.length > 1) form.route_costs.splice(index, 1)
  else form.route_costs.splice(index, 1, { cost_type: '', amount: null, notes: '' })
}

function parseCoordsFromMapsLink(link: string): { lat: number; lng: number } | null {
  const patterns = [
    /[?&]query=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
    /@(-?\d+\.?\d*),(-?\d+\.?\d*)/,
    /\/maps\/search\/(-?\d+\.?\d*),\+?(-?\d+\.?\d*)/,
  ]
  for (const pattern of patterns) {
    const match = link.match(pattern)
    if (match) {
      const lat = Number.parseFloat(match[1])
      const lng = Number.parseFloat(match[2])
      if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng }
    }
  }
  return null
}

const resolvingMapsLink = ref(false)

async function handleMapsLinkBlur() {
  if (props.readonly) return

  const link = form.google_maps_link.trim()
  if (!link) return

  const coords = parseCoordsFromMapsLink(link)
  if (coords) {
    form.latitude = coords.lat
    form.longitude = coords.lng
    return
  }

  // short link (maps.app.goo.gl) gak bisa di-parse di client, backend yang follow redirect (gak kena CORS)
  resolvingMapsLink.value = true
  try {
    const { data } = await axios.get('/api/maps-link/resolve', { params: { url: link } })
    form.latitude = data.lat
    form.longitude = data.lng
    success('Berhasil', 'Koordinat berhasil diambil dari link.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Link Google Maps tidak dikenali. Pakai link lengkap atau isi Latitude/Longitude manual.')
  } finally {
    resolvingMapsLink.value = false
  }
}

function resetPhotoState() {
  for (const category of PHOTO_CATEGORIES) {
    pendingUploadFiles[category.field] = []
    pendingUploadCaptions[category.field].clear()
    editUploadSelection[category.field] = null
    lcrDocuments[category.field] = []
  }
}

function openCreateWizard() {
  if (props.readonly) return

  editingSite.value = null
  wizardError.value = null
  v$.value.$reset()
  Object.assign(form, createDefaultForm())
  form.surveyor_names = auth.user?.name ?? ''
  resetPhotoState()
  currentStep.value = 0
  wizardOpen.value = true
}

async function openEditWizard(site: any) {
  editingSite.value = site
  wizardError.value = null
  v$.value.$reset()
  resetPhotoState()
  Object.assign(form, createDefaultForm(), {
    site_name: site.site_name ?? '',
    address_line: site.address?.address_line ?? '',
    postal_code: site.address?.postal_code ?? '',
    survey_date: site.survey_date ?? '',
    surveyor_names: site.surveyor_names ?? '',
    site_business_type: site.site_business_type ?? '',
    site_business_type_other: site.site_business_type_other ?? '',
    site_environment: site.site_environment ?? '',
    site_environment_other: site.site_environment_other ?? '',
    site_environment_notes: site.site_environment_notes ?? '',
    competitors: site.competitors ?? '',
    operating_hours: site.operating_hours ?? '',
    product_volume: site.product_volume?.length
      ? site.product_volume.map((p: any) => ({ produk: p.produk ?? '', volume_bulan: p.volume_bulan ?? '' }))
      : [{ produk: '', volume_bulan: '' }],
    survey_notes: site.survey_notes ?? '',
    contact: site.contact
      ? {
        id_contact: site.contact.id_contact ?? null,
        full_name: site.contact.full_name ?? '',
        position: site.contact.position ?? '',
        mobile: site.contact.mobile ?? '',
        email: site.contact.email ?? '',
      }
      : { id_contact: null, full_name: '', position: '', mobile: '', email: '' },
    id_wil_oa: site.id_wil_oa !== null && site.id_wil_oa !== undefined ? String(site.id_wil_oa) : '',
    supports_vessel_delivery: !!site.supports_vessel_delivery,

    max_truck_capacity_min: site.max_truck_capacity_min ?? null,
    max_truck_capacity_max: site.max_truck_capacity_max ?? null,
    access_notes: site.access_notes ?? '',
    route_costs: site.route_costs?.length
      ? site.route_costs.map((r: any) => ({ cost_type: r.cost_type ?? '', amount: r.amount ?? null, notes: r.notes ?? '' }))
      : [{ cost_type: '', amount: null, notes: '' }],
    distance_from_depot: site.distance_from_depot ?? '',
    min_vol_kirim: site.min_vol_kirim ?? '',
    rute_lokasi: site.rute_lokasi ?? '',
    note_lokasi: site.note_lokasi ?? '',

    unloading_method: site.unloading_method ?? '',
    max_trucks_per_day: site.max_trucks_per_day ?? null,
    unloading_notes: site.unloading_notes ?? '',

    storage_type: site.storage_type ?? '',
    storage_type_other: site.storage_type_other ?? '',
    storage_capacity: site.storage_capacity ?? '',
    storage_notes: site.storage_notes ?? '',

    quality_checking_method: Array.isArray(site.quality_checking_method) ? site.quality_checking_method : [],
    quality_checking_method_other: site.quality_checking_method_other ?? '',
    quality_checking_notes: site.quality_checking_notes ?? '',
    quantity_checking_method: Array.isArray(site.quantity_checking_method) ? site.quantity_checking_method : [],
    quantity_checking_method_other: site.quantity_checking_method_other ?? '',
    quantity_checking_notes: site.quantity_checking_notes ?? '',

    vessel_type: site.vessel_type ?? '',
    vessel_type_other: site.vessel_type_other ?? '',
    vessel_cargo_capacity: site.vessel_cargo_capacity ?? '',
    vessel_unloading_method: site.vessel_unloading_method ?? '',
    vessel_unloading_method_other: site.vessel_unloading_method_other ?? '',
    vessel_quantity_checking_method: Array.isArray(site.vessel_quantity_checking_method) ? site.vessel_quantity_checking_method : [],
    vessel_quantity_checking_method_other: site.vessel_quantity_checking_method_other ?? '',
    vessel_quantity_checking_notes: site.vessel_quantity_checking_notes ?? '',
    vessel_quality_checking_method: Array.isArray(site.vessel_quality_checking_method) ? site.vessel_quality_checking_method : [],
    vessel_quality_checking_method_other: site.vessel_quality_checking_method_other ?? '',
    vessel_quality_checking_notes: site.vessel_quality_checking_notes ?? '',
    jetty_type: site.jetty_type ?? '',
    max_loa: site.max_loa ?? null,
    min_pbl: site.min_pbl ?? null,
    draft_lws: site.draft_lws ?? null,
    jetty_capacity_dwt: site.jetty_capacity_dwt ?? null,
    jetty_permit_info: site.jetty_permit_info ?? '',
    document_requirements: site.document_requirements ?? '',

    latitude: site.latitude_lokasi ?? null,
    longitude: site.longitude_lokasi ?? null,
    google_maps_link: site.link_google_maps ?? '',
  })

  if (site.address?.province_id) {
    isHydratingRegion.value = true
    try {
      form.province_id = String(site.address.province_id)
      await region.fetchRegencies(form.province_id)
      form.regency_id = site.address.regency_id ? String(site.address.regency_id) : ''
      if (form.regency_id) {
        await region.fetchDistricts(form.regency_id)
        form.district_id = site.address.district_id ? String(site.address.district_id) : ''
        if (form.district_id) {
          await region.fetchVillages(form.district_id)
          form.village_id = site.address.village_id ? String(site.address.village_id) : ''
        }
      }
    } finally {
      isHydratingRegion.value = false
    }
  }

  await fetchLcrDocuments(site.id_lcr)

  currentStep.value = 0
  wizardOpen.value = true
}

function closeWizard() {
  if (saving.value) return
  wizardOpen.value = false
}

async function goNext() {
  wizardError.value = null
  if (currentStep.value === 0 && !props.readonly) {
    const valid = await v$.value.$validate()
    if (!valid) {
      const firstError = v$.value.$errors[0]?.$message?.toString()
      notifyError('Gagal', firstError ?? 'Lengkapi seluruh field wajib di step ini sebelum lanjut.')
      return
    }
    if (!hasValidPic()) {
      notifyError('Gagal', 'Minimal satu Penanggung Jawab (PIC) dengan nama wajib diisi.')
      return
    }
  }
  if (currentStep.value < steps.length - 1) currentStep.value += 1
}

function goBack() {
  wizardError.value = null
  if (currentStep.value > 0) currentStep.value -= 1
}

function loadFromLogisticClaims() {
  if (props.readonly) return

  const claim = props.customerLogistik
  if (!claim) return

  Object.assign(form, {
    site_environment: claim.site_environment ?? form.site_environment,
    site_environment_other: claim.site_environment_other ?? form.site_environment_other,
    site_environment_notes: claim.site_environment_notes ?? form.site_environment_notes,
    storage_type: claim.storage_type ?? form.storage_type,
    storage_type_other: claim.storage_type_other ?? form.storage_type_other,
    storage_notes: claim.storage_notes ?? form.storage_notes,
    quality_checking_notes: claim.quality_checking_notes ?? form.quality_checking_notes,
    quantity_checking_notes: claim.quantity_checking_notes ?? form.quantity_checking_notes,
    max_truck_capacity_min: claim.max_truck_capacity_min ?? form.max_truck_capacity_min,
    max_truck_capacity_max: claim.max_truck_capacity_max ?? form.max_truck_capacity_max,
    supports_vessel_delivery: !!claim.supports_vessel_delivery,
  })

  logisticClaimModalOpen.value = false
  success('Berhasil', 'Data dari profil logistik customer dimuat ke form.')
}

function buildPayload() {
  const { address_line, province_id, regency_id, district_id, village_id, postal_code, contact, ...rest } = form
  const payload: Record<string, any> = {
    ...rest,
    product_volume: form.product_volume.filter(p => p.produk.trim() || p.volume_bulan.trim()),
    contact,
    route_costs: form.route_costs.filter(r => r.cost_type.trim() || r.amount !== null || r.notes.trim()),
    // rule enum Laravel gak anggap '' sebagai nullable
    vessel_type: form.vessel_type || null,
    vessel_unloading_method: form.vessel_unloading_method || null,
  }

  // address dihilangkan kalau address_line kosong, biar gak kena required_with & gak nimpa address existing
  if (address_line.trim()) {
    payload.address = { address_line, province_id, regency_id, district_id, village_id, postal_code }
  }

  return payload
}

function hasAnyPhoto(): boolean {
  return PHOTO_CATEGORIES.some(c =>
    isEditMode.value ? lcrDocuments[c.field].length > 0 : pendingUploadFiles[c.field].length > 0,
  )
}

function hasValidPic(): boolean {
  return form.contact.full_name.trim().length > 0
}

async function submitWizard() {
  if (props.readonly) return

  if (!hasValidPic()) {
    notifyError('Gagal', 'Minimal satu Penanggung Jawab (PIC) dengan nama wajib diisi (Step 1).')
    currentStep.value = 0
    return
  }

  if (!hasAnyPhoto()) {
    notifyError('Gagal', 'Minimal 1 foto lampiran wajib diunggah (foto kantor, foto jalan, atau kategori lainnya) sebelum menyimpan.')
    return
  }

  wizardError.value = null
  saving.value = true
  try {
    const payload = buildPayload()
    const wasEditing = !!editingSite.value
    let response
    if (editingSite.value) {
      response = await api.update(editingSite.value.id_lcr, payload)
    } else {
      response = await api.store(payload)
    }

    if (response.data?.contact) {
      form.contact = {
        id_contact: response.data.contact.id_contact ?? null,
        full_name: response.data.contact.full_name ?? '',
        position: response.data.contact.position ?? '',
        mobile: response.data.contact.mobile ?? '',
        email: response.data.contact.email ?? '',
      }
    }

    if (wasEditing) {
      wizardOpen.value = false
      await fetchSites()
      success('Berhasil', 'Site LCR berhasil diperbarui.')
    } else {
      const newIdLcr = response.data.id_lcr
      editingSite.value = response.data

      const failedCategories: string[] = []
      for (const category of PHOTO_CATEGORIES) {
        const files = pendingUploadFiles[category.field]
        if (!files.length) continue
        for (const file of files) {
          try {
            const caption = pendingUploadCaptions[category.field].get(file) ?? ''
            const uploaded = await uploadLcrDocument(file, category.code, newIdLcr, caption)
            lcrDocuments[category.field].push(uploaded)
          } catch {
            if (!failedCategories.includes(category.label)) failedCategories.push(category.label)
          }
        }
      }
      for (const category of PHOTO_CATEGORIES) {
        pendingUploadFiles[category.field] = []
        pendingUploadCaptions[category.field].clear()
      }

      await fetchSites()

      if (failedCategories.length > 0) {
        notifyError(
          'Sebagian foto gagal diunggah',
          `Kategori: ${failedCategories.join(', ')}. Site LCR sudah tersimpan, silakan unggah ulang foto yang gagal.`,
        )
      } else {
        wizardOpen.value = false
        success('Berhasil', 'Site LCR berhasil ditambahkan.')
      }
    }
    emit('saved')
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      wizardError.value = (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.'
    } else {
      wizardError.value = e.response?.data?.message ?? 'Gagal menyimpan site LCR.'
    }
  } finally {
    saving.value = false
  }
}

function confirmDeleteSite(site: any) {
  if (props.readonly) return

  deleteTarget.value = site
  deleteDialogOpen.value = true
}

async function performDeleteSite() {
  if (props.readonly || !deleteTarget.value) return
  deleteLoading.value = true
  try {
    await api.destroy(deleteTarget.value.id_lcr)
    sites.value = sites.value.filter(s => s.id_lcr !== deleteTarget.value.id_lcr)
    success('Berhasil', 'Site LCR berhasil dihapus.')
    deleteDialogOpen.value = false
    deleteTarget.value = null
    emit('saved')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus site LCR.')
  } finally {
    deleteLoading.value = false
  }
}

async function fetchWilayahOaOptions() {
  try {
    const { data } = await createResourceApi('/transport-areas').getAll({ as_list: true })
    wilayahOaOptions.value = data.data
  } catch {
    wilayahOaOptions.value = []
  }
}

onMounted(() => {
  fetchSites()
  region.fetchProvinces()
  fetchWilayahOaOptions()
  fetchDocumentTypeMap()
})
</script>

<template>
  <div class="gap-6 grid">
    <CardSection title="Site LCR" description="Lokasi survei LCR milik customer ini." icon="MapPin"
      icon-class="bg-cyan-100 text-cyan-600">
      <template v-if="!readonly" #action>
        <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2" @click="openCreateWizard">
          <Lucide icon="Plus" class="w-4 h-4" />
          Tambah Site
        </Button>
      </template>

      <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[120px] text-slate-500">
        <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
        <span class="text-body">Memuat site LCR...</span>
      </div>

      <div v-else-if="sites.length === 0"
        class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
        <Lucide icon="Inbox" class="w-8 h-8 text-slate-400" />
        <div class="text-body">Belum ada site LCR yang ditambahkan.</div>
      </div>

      <div v-else class="border border-slate-200 rounded-xl overflow-x-auto">
        <table class="divide-y divide-slate-200 w-full min-w-[760px]">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 w-12 text-form-label text-center">No</th>
              <th class="px-3 py-2 text-form-label text-left">Nama Lokasi</th>
              <th class="px-3 py-2 text-form-label text-left">Alamat Survey</th>
              <th class="px-3 py-2 text-form-label text-left">Tanggal Survey</th>
              <th class="px-3 py-2 text-form-label text-left">Status Approval</th>
              <th class="px-3 py-2 w-24 text-form-label text-center">Aksi</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="(site, index) in sites" :key="site.id_lcr" class="hover:bg-slate-50 transition">
              <td class="px-3 py-2 text-center num-sm">{{ index + 1 }}.</td>
              <td class="px-3 py-2 text-body-strong">{{ site.site_name || '-' }}</td>
              <td class="px-3 py-2 max-w-xs text-body truncate">{{ site.address?.address_line || '-' }}</td>
              <td class="px-3 py-2 text-body">{{ site.survey_date || '-' }}</td>
              <td class="px-3 py-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium text-xs"
                  :class="approvalBadgeClass(site.approval?.status)">
                  {{ site.approval?.status_label ?? 'Belum Diajukan' }}
                </span>
              </td>
              <td class="px-3 py-2 text-center">
                <div class="inline-flex justify-center items-center gap-2">
                  <Button v-if="readonly" size="sm" variant="soft-dark" title="Lihat"
                    class="!shadow-none !p-0 !w-8 !h-8" @click="openEditWizard(site)">
                    <Lucide icon="Eye" class="w-4 h-4" />
                  </Button>
                  <template v-else>
                    <Button size="sm" variant="soft-warning" title="Edit" class="!shadow-none !p-0 !w-8 !h-8"
                      @click="openEditWizard(site)">
                      <Lucide icon="Edit" class="w-4 h-4" />
                    </Button>
                    <Button size="sm" variant="soft-danger" title="Hapus" class="!shadow-none !p-0 !w-8 !h-8"
                      @click="confirmDeleteSite(site)">
                      <Lucide icon="Trash2" class="w-4 h-4" />
                    </Button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardSection>

    <FormWizardModal :open="wizardOpen" :title="wizardTitle" :description="wizardDescription" :steps="steps"
      :current-step="currentStep" :loading="saving" :error="wizardError" size="xl"
      :submit-text="editingSite ? 'Simpan Perubahan' : 'Simpan'" :hide-submit="readonly"
      :cancel-text="readonly ? 'Tutup' : 'Batal'" static-backdrop @close="closeWizard" @back="goBack" @next="goNext"
      @submit="submitWizard">
      <template v-if="!readonly" #footer-start>
        <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2"
          :disabled="!customerLogistik" title="Muat data dari profil logistik customer (hasil onboarding)"
          @click="logisticClaimModalOpen = true">
          <Lucide icon="MousePointerSquare" class="w-4 h-4" />
          Load Data Customer Claims
        </Button>

        <!-- harus di dalam FormWizardModal biar headlessui kenal sebagai Dialog bersarang, kalau sibling outside-click nutup keduanya -->
        <FormModal :open="logisticClaimModalOpen" title="Data Logistik Customer (Onboarding)"
          description="Profil logistik yang diisi customer sendiri saat onboarding." size="md" cancel-text="Tutup"
          submit-text="Load Data" submit-icon="MousePointerSquare" @close="logisticClaimModalOpen = false"
          @submit="loadFromLogisticClaims">
          <div v-if="!customerLogistik"
            class="bg-slate-50 px-4 py-6 border border-slate-300 border-dashed rounded-lg text-body text-center">
            Belum ada data logistik dari onboarding untuk customer ini.
          </div>
          <pre v-else
            class="bg-slate-50 p-4 rounded-lg text-xs break-all whitespace-pre-wrap">{{ JSON.stringify(customerLogistik, null, 2) }}</pre>
        </FormModal>
      </template>

      <fieldset :disabled="readonly" class="contents">
        <div v-if="currentStep === 0" class="space-y-5">
          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Nama Lokasi
                <RequiredAsterisk />
              </FormLabel>
            </label>

            <div class="col-span-9">
              <FormInput v-model="form.site_name" placeholder="cth. Gudang Site Cikarang"
                :class="siteNameError ? 'border-rose-500' : ''" @blur="v$.site_name.$touch()" />
              <small v-if="siteNameError" class="!text-rose-600 text-caption">{{ siteNameError }}</small>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Alamat Site
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="col-span-9">
              <div class="gap-1 grid grid-cols-2">
                <FormTextarea class="col-span-2" v-model="form.address_line" rows="2"
                  placeholder="cth: Jl. Pegangsaan Timur No. 17" />
                <TomSelect v-model="form.province_id" class="w-full" :disabled="readonly">
                  <option value="">Cari Provinsi</option>
                  <option v-for="p in region.provinces.value" :key="p.id" :value="p.id">{{ p.name }}</option>
                </TomSelect>
                <TomSelect :key="String(!!form.province_id)" v-model="form.regency_id" class="w-full"
                  :disabled="readonly || !form.province_id">
                  <option value="">{{ form.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}</option>
                  <option v-for="k in region.regencies.value" :key="k.id" :value="k.id">{{ k.name }}</option>
                </TomSelect>
                <TomSelect :key="String(!!form.regency_id)" v-model="form.district_id" class="w-full"
                  :disabled="readonly || !form.regency_id">
                  <option value="">{{ form.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --' }}</option>
                  <option v-for="d in region.districts.value" :key="d.id" :value="d.id">{{ d.name }}</option>
                </TomSelect>
                <TomSelect :key="String(!!form.district_id)" v-model="form.village_id" class="w-full"
                  :disabled="readonly || !form.district_id">
                  <option value="">{{ form.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
                  </option>
                  <option v-for="v in region.villages.value" :key="v.id" :value="v.id">{{ v.name }}</option>
                </TomSelect>
                <!-- postal_code sengaja gak dirender, keisi dari watcher village_id -->
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Link Google Maps
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="col-span-9">
              <FormInput v-model="form.google_maps_link" placeholder="https://maps.google.com/..."
                :disabled="resolvingMapsLink" @blur="handleMapsLinkBlur" />
              <small v-if="resolvingMapsLink" class="block mt-1 text-caption text-slate-500">Mencari koordinat dari
                link...</small>
              <div v-if="mapPreviewUrl" class="mt-3 border border-slate-200 rounded-lg h-64 overflow-hidden">
                <iframe :src="mapPreviewUrl" class="w-full h-full" loading="lazy" />
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Wilayah OA
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="col-span-9">
              <TomSelect v-model="form.id_wil_oa" class="w-full" :options="wilayahOaSelectOptions" :disabled="readonly">
                <option value="">Pilih Wilayah</option>
                <option v-for="w in wilayahOaOptions" :key="w.id" :value="String(w.id)">
                  {{ w.province?.name || w.provinsi?.nama_provinsi }} - {{ w.regency?.name ||
                    w.kabupaten?.nama_kabupaten
                  }} -
                  {{ w.name }}
                </option>
              </TomSelect>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Tanggal Survey
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="col-span-9">
              <DateField v-model="form.survey_date" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Nama Surveyor
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="col-span-9">
              <FormInput v-model="form.surveyor_names" placeholder="cth. Budi Santoso" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>
                Penanggung Jawab (PIC)
                <RequiredAsterisk />
              </FormLabel>
            </label>
            <div class="gap-4 grid grid-cols-2 col-span-9">
              <FormInput v-model="form.contact.full_name" placeholder="cth. Ahmad Fauzi (Nama)" />
              <FormInput v-model="form.contact.position" placeholder="cth. Kepala Gudang (Posisi)" />
              <FormInput v-model="form.contact.mobile" placeholder="cth. 0812-3456-7890 (No. HP)" />
              <FormInput v-model="form.contact.email" type="email" placeholder="cth. nama@perusahaan.com (Email)" />
              <small v-if="!readonly" class="col-span-2 text-caption text-slate-500">Nama PIC wajib diisi.</small>
            </div>
          </div>
        </div>

        <div v-else-if="currentStep === 1" class="space-y-5">
          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Jenis Usaha Site</FormLabel>
            </label>
            <div class="col-span-9">
              <div class="p-4 border border-slate-200 rounded-lg">
                <div class="gap-3 grid grid-cols-2">
                  <FormCheck v-for="(opt, idx) in typeBusinessOptions" :key="opt">
                    <FormCheck.Input :id="'site-business-type-' + idx" type="radio" :value="opt"
                      v-model="form.site_business_type" />
                    <FormCheck.Label :htmlFor="'site-business-type-' + idx">{{ opt }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="site-business-type-other" type="radio" value="other"
                      v-model="form.site_business_type" />
                    <FormCheck.Label htmlFor="site-business-type-other">Lainnya</FormCheck.Label>
                  </FormCheck>
                </div>
                <FormInput v-if="form.site_business_type === 'other'" v-model="form.site_business_type_other"
                  placeholder="cth. Perkebunan Kelapa Sawit" class="mt-3" />
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Lingkungan Site</FormLabel>
            </label>
            <div class="col-span-9">
              <div class="p-4 border border-slate-200 rounded-lg">
                <div class="gap-3 grid grid-cols-3">
                  <FormCheck v-for="opt in siteEnvironmentOptions" :key="opt.value">
                    <FormCheck.Input :id="'site-environment-' + opt.value" type="radio" :value="opt.value"
                      v-model="form.site_environment" />
                    <FormCheck.Label :htmlFor="'site-environment-' + opt.value">{{ opt.label }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="site-environment-other" type="radio" value="other"
                      v-model="form.site_environment" />
                    <FormCheck.Label htmlFor="site-environment-other">Lainnya</FormCheck.Label>
                  </FormCheck>
                </div>
                <FormInput v-if="form.site_environment === 'other'" v-model="form.site_environment_other"
                  placeholder="cth. Kawasan Pergudangan" class="mt-3" />
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Lingkungan</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.site_environment_notes" rows="2"
                placeholder="cth. Berada di kawasan industri, akses jalan besar" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Survey</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.survey_notes" rows="2"
                placeholder="cth. Kondisi site secara umum baik, akses mudah dijangkau" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Kompetitor</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.competitors" rows="2"
                placeholder="Nama kompetitor, pisahkan dengan koma kalau lebih dari satu" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Jam Operasional</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.operating_hours" rows="2"
                placeholder="cth. Senin-Jumat 08:00-16:00, Sabtu 08:00-12:00" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <div class="flex flex-col items-start gap-2 col-span-3 pt-2 text-form-label">
              <FormLabel>Produk & Volume/Bulan</FormLabel>
              <Button v-if="!readonly" size="sm" variant="outline-secondary" @click="addProductVolumeRow">
                <Lucide icon="Plus" class="mr-1 w-4 h-4" /> Tambah
              </Button>
            </div>
            <div class="col-span-9 overflow-x-auto">
              <Table>
                <Table.Thead>
                  <Table.Tr>
                    <Table.Th>Produk</Table.Th>
                    <Table.Th>Volume/Bulan</Table.Th>
                    <Table.Th v-if="!readonly" class="text-center">Aksi</Table.Th>
                  </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                  <Table.Tr v-for="(row, i) in form.product_volume" :key="i">
                    <Table.Td>
                      <FormInput v-model="row.produk" placeholder="cth. Batu Split 2-3 cm" />
                    </Table.Td>
                    <Table.Td>
                      <FormInput v-model="row.volume_bulan" placeholder="cth. 5000 Ton" />
                    </Table.Td>
                    <Table.Td v-if="!readonly" class="text-center">
                      <Button size="sm" variant="soft-danger" :disabled="form.product_volume.length === 1"
                        @click="removeProductVolumeRow(i)">
                        <Lucide icon="X" class="w-4 h-4" />
                      </Button>
                    </Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </div>

        </div>

        <div v-else-if="currentStep === 2" class="space-y-5">
          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Kapasitas Truk (Ton)</FormLabel>
            </label>
            <div class="gap-4 grid grid-cols-2 col-span-9">
              <NumberField v-model="form.max_truck_capacity_min" prefix="Min" :decimals="2" placeholder="8" />
              <NumberField v-model="form.max_truck_capacity_max" prefix="Max" :decimals="2" placeholder="12" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Akses</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.access_notes" rows="2"
                placeholder="cth. Jalan sempit, hanya bisa dilalui truk kecil" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Jarak dari Depot</FormLabel>
            </label>
            <div class="col-span-9">
              <FormInput v-model="form.distance_from_depot" placeholder="cth. 39,1 KM" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Minimal Volume Kirim</FormLabel>
            </label>
            <div class="col-span-9">
              <FormInput v-model="form.min_vol_kirim" placeholder="cth. 5 m³" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Rute Lokasi</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.rute_lokasi" rows="3"
                placeholder="cth. Dari depot lurus ke arah Cikarang, belok kanan di pertigaan pasar" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Lokasi</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.note_lokasi" rows="3" placeholder="Catatan tambahan mengenai lokasi" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Biaya Rute</FormLabel>
            </label>
            <div class="col-span-9 overflow-x-auto">
              <Table>
                <Table.Thead>
                  <Table.Tr>
                    <Table.Th>Jenis Biaya</Table.Th>
                    <Table.Th>Nominal</Table.Th>
                    <Table.Th>Catatan</Table.Th>
                    <Table.Th v-if="!readonly" class="text-center">Aksi</Table.Th>
                  </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                  <Table.Tr v-for="(row, i) in form.route_costs" :key="i">
                    <Table.Td>
                      <FormInput v-model="row.cost_type" placeholder="cth. Tol, Parkir" />
                    </Table.Td>
                    <Table.Td>
                      <NumberField v-model="row.amount" :decimals="0" placeholder="cth. 150.000" />
                    </Table.Td>
                    <Table.Td>
                      <FormInput v-model="row.notes" placeholder="cth. Tol Jakarta-Cikampek PP" />
                    </Table.Td>
                    <Table.Td v-if="!readonly" class="text-center">
                      <div class="inline-flex items-center gap-2">
                        <Button size="sm" variant="outline-secondary" @click="addRouteCostRow">
                          <Lucide icon="Plus" class="w-4 h-4" />
                        </Button>
                        <Button size="sm" variant="soft-danger" :disabled="form.route_costs.length === 1"
                          @click="removeRouteCostRow(i)">
                          <Lucide icon="X" class="w-4 h-4" />
                        </Button>
                      </div>
                    </Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </div>

        </div>

        <div v-else-if="currentStep === 3" class="space-y-6">
          <div class="space-y-5">
            <div class="text-overline">Layout & Unloading Truk</div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Metode Unloading</FormLabel>
              </label>
              <div class="col-span-9">
                <FormInput v-model="form.unloading_method" placeholder="cth. Manual, Forklift, Excavator" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Maks Truk per Hari</FormLabel>
              </label>
              <div class="col-span-9">
                <NumberField v-model="form.max_trucks_per_day" :decimals="0" placeholder="cth. 20" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Catatan Unloading</FormLabel>
              </label>
              <div class="col-span-9">
                <FormTextarea v-model="form.unloading_notes" rows="2"
                  placeholder="cth. Proses unloading memakan waktu 2-3 jam per truk" />
              </div>
            </div>

          </div>

          <div class="space-y-5">
            <div class="text-overline">Penyimpanan</div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Tipe Penyimpanan</FormLabel>
              </label>
              <div class="col-span-9">
                <div class="p-4 border border-slate-200 rounded-lg">
                  <div class="gap-3 grid grid-cols-3">
                    <FormCheck v-for="opt in storageTypeOptions" :key="opt.value">
                      <FormCheck.Input :id="'storage-type-' + opt.value" type="radio" :value="opt.value"
                        v-model="form.storage_type" />
                      <FormCheck.Label :htmlFor="'storage-type-' + opt.value">{{ opt.label }}</FormCheck.Label>
                    </FormCheck>
                    <FormCheck>
                      <FormCheck.Input id="storage-type-other" type="radio" value="other" v-model="form.storage_type" />
                      <FormCheck.Label htmlFor="storage-type-other">Lainnya</FormCheck.Label>
                    </FormCheck>
                  </div>
                  <FormInput v-if="form.storage_type === 'other'" v-model="form.storage_type_other"
                    placeholder="Sebutkan" class="mt-3" />
                </div>
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Kapasitas Penyimpanan</FormLabel>
              </label>
              <div class="col-span-9">
                <FormInput v-model="form.storage_capacity" placeholder="cth. 50 Ton" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Catatan Penyimpanan</FormLabel>
              </label>
              <div class="col-span-9">
                <FormTextarea v-model="form.storage_notes" rows="2"
                  placeholder="cth. Gudang tertutup, terlindung dari hujan" />
              </div>
            </div>

          </div>
        </div>

        <div v-else-if="currentStep === 4" class="space-y-5">
          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Metode Pemeriksaan Kualitas</FormLabel>
            </label>
            <div class="col-span-9">
              <div class="p-4 border border-slate-200 rounded-lg">
                <div class="gap-3 grid grid-cols-2">
                  <FormCheck v-for="opt in qualityCheckingOptions" :key="opt.value">
                    <FormCheck.Input :id="'quality-checking-' + opt.value" type="checkbox" :value="opt.value"
                      v-model="form.quality_checking_method" />
                    <FormCheck.Label :htmlFor="'quality-checking-' + opt.value">{{ opt.label }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="quality-checking-other" type="checkbox" value="other"
                      v-model="form.quality_checking_method" />
                    <FormCheck.Label htmlFor="quality-checking-other">Lainnya</FormCheck.Label>
                  </FormCheck>
                </div>
                <FormInput v-if="form.quality_checking_method.includes('other')"
                  v-model="form.quality_checking_method_other" placeholder="cth. Uji Kadar Air" class="mt-3" />
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Pemeriksaan Kualitas</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.quality_checking_notes" rows="2"
                placeholder="cth. Sampling dilakukan setiap pengiriman" />
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Metode Pemeriksaan Kuantitas</FormLabel>
            </label>
            <div class="col-span-9">
              <div class="p-4 border border-slate-200 rounded-lg">
                <div class="gap-3 grid grid-cols-2">
                  <FormCheck v-for="opt in quantityCheckingOptions" :key="opt.value">
                    <FormCheck.Input :id="'quantity-checking-' + opt.value" type="checkbox" :value="opt.value"
                      v-model="form.quantity_checking_method" />
                    <FormCheck.Label :htmlFor="'quantity-checking-' + opt.value">{{ opt.label }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="quantity-checking-other" type="checkbox" value="other"
                      v-model="form.quantity_checking_method" />
                    <FormCheck.Label htmlFor="quantity-checking-other">Lainnya</FormCheck.Label>
                  </FormCheck>
                </div>
                <FormInput v-if="form.quantity_checking_method.includes('other')"
                  v-model="form.quantity_checking_method_other" placeholder="cth. Manual Tally" class="mt-3" />
              </div>
            </div>
          </div>

          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Catatan Pemeriksaan Kuantitas</FormLabel>
            </label>
            <div class="col-span-9">
              <FormTextarea v-model="form.quantity_checking_notes" rows="2"
                placeholder="cth. Penimbangan dilakukan 2x (masuk & keluar)" />
            </div>
          </div>
        </div>

        <div v-else-if="currentStep === 5" class="space-y-5">
          <div class="items-start gap-4 grid grid-cols-12">
            <label class="col-span-3 pt-2 text-form-label">
              <FormLabel>Mendukung Pengiriman via Kapal?</FormLabel>
            </label>
            <div class="flex items-center gap-3 col-span-9">
              <FormSwitch>
                <FormSwitch.Input v-model="form.supports_vessel_delivery" type="checkbox" />
              </FormSwitch>
              <span class="text-body">
                {{ form.supports_vessel_delivery ? 'Ya, tampilkan field Vessel/Jetty' : 'Tidak' }}
              </span>
            </div>
          </div>

          <div v-if="form.supports_vessel_delivery" class="space-y-5">
            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Jenis Kapal</FormLabel>
              </label>
              <div class="col-span-9">
                <div class="p-4 border border-slate-200 rounded-lg">
                  <div class="gap-3 grid grid-cols-2">
                    <FormCheck v-for="opt in vesselTypeOptions" :key="opt.value">
                      <FormCheck.Input :id="'vessel-type-' + opt.value" type="radio" :value="opt.value"
                        v-model="form.vessel_type" />
                      <FormCheck.Label :htmlFor="'vessel-type-' + opt.value">{{ opt.label }}</FormCheck.Label>
                    </FormCheck>
                  </div>
                  <FormInput v-if="form.vessel_type === 'other'" v-model="form.vessel_type_other"
                    placeholder="cth. Tongkang Kayu" class="mt-3" />
                </div>
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Kapasitas Kargo Kapal</FormLabel>
              </label>
              <div class="col-span-9">
                <FormInput v-model="form.vessel_cargo_capacity" placeholder="cth. 5000 Ton" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Metode Bongkar Kapal</FormLabel>
              </label>
              <div class="col-span-9">
                <div class="p-4 border border-slate-200 rounded-lg">
                  <div class="gap-3 grid grid-cols-2">
                    <FormCheck v-for="opt in vesselUnloadingMethodOptions" :key="opt.value">
                      <FormCheck.Input :id="'vessel-unloading-method-' + opt.value" type="radio" :value="opt.value"
                        v-model="form.vessel_unloading_method" />
                      <FormCheck.Label :htmlFor="'vessel-unloading-method-' + opt.value">{{ opt.label }}
                      </FormCheck.Label>
                    </FormCheck>
                  </div>
                  <FormInput v-if="form.vessel_unloading_method === 'other'"
                    v-model="form.vessel_unloading_method_other" placeholder="cth. Excavator di atas Tongkang"
                    class="mt-3" />
                </div>
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Metode Pemeriksaan Kuantitas Kapal</FormLabel>
              </label>
              <div class="col-span-9">
                <div class="p-4 border border-slate-200 rounded-lg">
                  <div class="gap-3 grid grid-cols-2">
                    <FormCheck v-for="o in vesselQuantityCheckingMethodOptions" :key="o.value">
                      <FormCheck.Input :id="'vessel-quantity-checking-' + o.value" type="checkbox" :value="o.value"
                        v-model="form.vessel_quantity_checking_method" />
                      <FormCheck.Label :htmlFor="'vessel-quantity-checking-' + o.value">{{ o.label }}</FormCheck.Label>
                    </FormCheck>
                  </div>
                  <FormInput v-if="form.vessel_quantity_checking_method.includes('other')"
                    v-model="form.vessel_quantity_checking_method_other" placeholder="cth. Draft Survey Manual"
                    class="mt-3" />
                </div>
                <FormTextarea v-model="form.vessel_quantity_checking_notes" rows="3"
                  placeholder="cth. Draft survey dilakukan sebelum & sesudah bongkar" class="mt-3" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Metode Pemeriksaan Kualitas Kapal</FormLabel>
              </label>
              <div class="col-span-9">
                <div class="p-4 border border-slate-200 rounded-lg">
                  <div class="gap-3 grid grid-cols-2">
                    <FormCheck v-for="o in vesselQualityCheckingOptions" :key="o.value">
                      <FormCheck.Input :id="'vessel-quality-checking-' + o.value" type="checkbox" :value="o.value"
                        v-model="form.vessel_quality_checking_method" />
                      <FormCheck.Label :htmlFor="'vessel-quality-checking-' + o.value">{{ o.label }}</FormCheck.Label>
                    </FormCheck>
                    <FormCheck>
                      <FormCheck.Input id="vessel-quality-checking-other" type="checkbox" value="other"
                        v-model="form.vessel_quality_checking_method" />
                      <FormCheck.Label htmlFor="vessel-quality-checking-other">Lainnya</FormCheck.Label>
                    </FormCheck>
                  </div>
                  <FormInput v-if="form.vessel_quality_checking_method.includes('other')"
                    v-model="form.vessel_quality_checking_method_other" placeholder="cth. Uji Lab Independen"
                    class="mt-3" />
                </div>
                <FormTextarea v-model="form.vessel_quality_checking_notes" rows="3"
                  placeholder="cth. Sampel diambil dari 3 titik berbeda" class="mt-3" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Tipe Jetty</FormLabel>
              </label>
              <div class="col-span-9">
                <FormInput v-model="form.jetty_type" placeholder="cth. Jetty Beton, Jetty Kayu" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Max LOA</FormLabel>
              </label>
              <div class="col-span-9">
                <NumberField v-model="form.max_loa" :decimals="2" placeholder="cth. 120" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Min PBL</FormLabel>
              </label>
              <div class="col-span-9">
                <NumberField v-model="form.min_pbl" :decimals="2" placeholder="cth. 15" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Draft (LWS)</FormLabel>
              </label>
              <div class="col-span-9">
                <NumberField v-model="form.draft_lws" :decimals="2" placeholder="cth. 6" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Kapasitas Jetty (DWT)</FormLabel>
              </label>
              <div class="col-span-9">
                <NumberField v-model="form.jetty_capacity_dwt" :decimals="2" placeholder="cth. 8000" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Info Izin Jetty</FormLabel>
              </label>
              <div class="col-span-9">
                <FormTextarea v-model="form.jetty_permit_info" rows="2"
                  placeholder="cth. Izin berlaku hingga 2027, terbit dari Kesyahbandaran" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Persyaratan Dokumen</FormLabel>
              </label>
              <div class="col-span-9">
                <FormTextarea v-model="form.document_requirements" rows="2"
                  placeholder="cth. SIB, Manifest, Surat Jalan" />
              </div>
            </div>
          </div>
        </div>

        <div v-else class="space-y-6">
          <div class="space-y-5">
            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Kondisi Jalan Menuju Lokasi</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('road_condition_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('road_condition_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('road_condition_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('road_condition_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('road_condition_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Layout Site</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('site_layout_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('site_layout_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('site_layout_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('site_layout_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('site_layout_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>
            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Layout Area Unloading</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('unloading_layout_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('unloading_layout_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('unloading_layout_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('unloading_layout_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('unloading_layout_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Fasilitas Penyimpanan</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('storage_facility_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('storage_facility_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('storage_facility_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('storage_facility_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('storage_facility_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Alat Ukur</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('measurement_evidence_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('measurement_evidence_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('measurement_evidence_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('measurement_evidence_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('measurement_evidence_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>

            <div v-if="form.supports_vessel_delivery" class="space-y-5">

              <div class="items-start gap-4 grid grid-cols-12">
                <label class="col-span-3 pt-2 text-form-label">
                  <FormLabel>Foto Layout Vessel/Jetty</FormLabel>
                </label>
                <div class="col-span-9">
                  <ImageUploadField :model-value="photoModelValue('vessel_layout_photos')" multiple with-caption
                    :disabled="readonly" :existing-files="photoExistingFiles('vessel_layout_photos')"
                    @update:model-value="(v) => handlePhotoFileChange('vessel_layout_photos', v)"
                    @remove-existing="(f) => handleRemoveExistingPhoto('vessel_layout_photos', f)"
                    @update:caption="(f, c) => handlePhotoCaptionChange('vessel_layout_photos', f, c)"
                    @error="(msg: string) => notifyError('Gagal', msg)" />
                </div>
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Kantor & Gerbang Perusahaan</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('company_office_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('company_office_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('company_office_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('company_office_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('company_office_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>

            <div class="items-start gap-4 grid grid-cols-12">
              <label class="col-span-3 pt-2 text-form-label">
                <FormLabel>Foto Tambahan</FormLabel>
              </label>
              <div class="col-span-9">
                <ImageUploadField :model-value="photoModelValue('additional_photos')" multiple with-caption
                  :disabled="readonly" :existing-files="photoExistingFiles('additional_photos')"
                  @update:model-value="(v) => handlePhotoFileChange('additional_photos', v)"
                  @remove-existing="(f) => handleRemoveExistingPhoto('additional_photos', f)"
                  @update:caption="(f, c) => handlePhotoCaptionChange('additional_photos', f, c)"
                  @error="(msg: string) => notifyError('Gagal', msg)" />
              </div>
            </div>
          </div>
        </div>
      </fieldset>
    </FormWizardModal>

    <DeleteRecordDialog v-if="!readonly" :open="deleteDialogOpen" title="Hapus Site LCR"
      :description="`Site ${deleteTarget?.site_name ?? ''} akan dihapus permanen.`" :loading="deleteLoading"
      @close="deleteDialogOpen = false" @confirm="performDeleteSite" />
  </div>
</template>
