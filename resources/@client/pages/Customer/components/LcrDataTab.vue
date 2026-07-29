<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormInput, FormLabel, FormSelect, FormSwitch, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormWizardModal from '@/components/SystemDesign/Form/FormWizardModal.vue'
import ImageUploadField from '@/components/SystemDesign/Form/ImageUploadField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

/* List + FormWizardModal 4 step. Field mengikuti StoreCustomerLcrRequest::rules()
   Grup 1-7 + CustomerLcrController::formatSite() -- 8 field foto, field
   vessel/jetty terpisah dari truk. */

/* Tab ini SENGAJA TIDAK menerima prop kycStatus -- LCR adalah lampiran,
   bukan syarat wajib forward, dan tetap bisa diedit pasca-forward/closed. */
const props = defineProps<{
  idCustomer: number
}>()

const { success, error: notifyError } = useNotification()

const api = createResourceApi(`/customers/${props.idCustomer}/lcr-sites`)

/* Type: item media (json array {path,url,caption} -- 8 field foto Grup
   2/3/4/5/6/7). */
interface PhotoItem {
  path: string
  url: string
  caption: string
}
type PhotoField =
  | 'road_condition_photos'
  | 'site_layout_photos'
  | 'unloading_layout_photos'
  | 'storage_facility_photos'
  | 'measurement_evidence_photos'
  | 'vessel_layout_photos'
  | 'company_office_photos'
  | 'additional_photos'

/* State: list site */
const sites = ref<any[]>([])
const loading = ref(true)

/* State: wizard (create/edit) */
const wizardOpen = ref(false)
const wizardError = ref<string | null>(null)
const saving = ref(false)
const currentStep = ref(0)
const editingSite = ref<any | null>(null)

const steps = [
  { title: 'Informasi Umum', description: 'Identitas & info umum lokasi' },
  { title: 'Lokasi', description: 'Akses, rute & koordinat' },
  { title: 'Info Unloading', description: 'Layout, penyimpanan & quality/quantity' },
  { title: 'Media & Dokumen', description: 'Foto pendukung' },
]

/* State: delete */
const deleteDialogOpen = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<any | null>(null)

/* Options: enum backend (Grup 6 Vessel/Jetty) -- lihat
   app/Enums/CustomerLcrVesselType.php, CustomerLcrVesselUnloadingMethod.php,
   CustomerLcrVesselQuantityCheckingMethod.php untuk source of truth. */
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

/* State: form wizard -- 1 objek gabungan seluruh field dari 4 step (bukan 4
   objek terpisah), field dinamai PERSIS sama dengan key REQUEST
   (StoreCustomerLcrRequest::rules()) supaya payload submit tidak perlu
   remapping lagi. */
function createDefaultForm() {
  return {
    /* Grup 1: Identitas & info umum */
    site_name: '',
    survey_address: '',
    // String (bukan number) -- FormInput (base component) tidak menerapkan
    // modifier v-model.number pada component custom, dan backend
    // (nullable|integer) menerima string numerik dengan valid.
    survey_province: '',
    survey_regency: '',
    survey_date: '',
    surveyor_names: [''] as string[],
    site_business_type: '',
    site_business_type_other: '',
    site_environment: '',
    site_environment_other: '',
    site_environment_notes: '',
    competitors: [''] as string[],
    operating_hours: [''] as string[],
    product_volume: [{ produk: '', volume_bulan: '' }] as { produk: string; volume_bulan: string }[],
    survey_notes: '',
    picustomer: [{ nama: '', posisi: '', telepon: '' }] as { nama: string; posisi: string; telepon: string }[],
    website: '',
    survey_phone: '',
    survey_fax: '',
    id_wilayah: '',
    id_wil_oa: '',
    supports_vessel_delivery: false,

    /* Grup 2: Akses & rute */
    max_truck_capacity_min: null as number | null,
    max_truck_capacity_max: null as number | null,
    access_notes: '',
    route_costs: [{ cost_type: '', amount: null as number | null, notes: '' }],
    distance_from_depot: '',
    min_vol_kirim: '',
    rute_lokasi: '',
    note_lokasi: '',

    /* Grup 3: Layout & unloading truk */
    unloading_method: '',
    max_trucks_per_day: null as number | null,
    unloading_notes: '',

    /* Grup 4: Penyimpanan */
    storage_type: '',
    storage_type_other: '',
    storage_capacity: '',
    storage_notes: '',

    /* Grup 5: Verifikasi quality/quantity */
    quality_checking_method: '',
    quality_checking_notes: '',
    quantity_checking_method: '',
    quantity_checking_notes: '',

    /* Grup 6: Vessel/Jetty (non-foto -- kondisional supports_vessel_delivery) */
    vessel_type: '',
    vessel_cargo_capacity: '',
    vessel_unloading_method: '',
    vessel_quantity_checking_method: '',
    vessel_quantity_checking_notes: '',
    vessel_quality_checking_method: '',
    vessel_quality_checking_notes: '',
    jetty_type: '',
    max_loa: null as number | null,
    min_pbl: null as number | null,
    draft_lws: null as number | null,
    jetty_capacity_dwt: null as number | null,
    jetty_permit_info: '',
    document_requirements: '',

    /* Grup 7: Foto lain & lokasi */
    latitude: null as number | null,
    longitude: null as number | null,
    google_maps_link: '',

    /* 8 field foto (Step 4) */
    road_condition_photos: [] as PhotoItem[],
    site_layout_photos: [] as PhotoItem[],
    unloading_layout_photos: [] as PhotoItem[],
    storage_facility_photos: [] as PhotoItem[],
    measurement_evidence_photos: [] as PhotoItem[],
    vessel_layout_photos: [] as PhotoItem[],
    company_office_photos: [] as PhotoItem[],
    additional_photos: [] as PhotoItem[],
  }
}

const form = reactive(createDefaultForm())

/* State: file yang baru dipilih di ImageUploadField sebelum diupload --
   direset ke null begitu upload selesai (file yang sukses diupload langsung
   pindah jadi entri di form[field], pola sama seperti
   SalesReviewDataTab.vue pendingFiles). */
const pendingPhotoFiles = reactive<Record<PhotoField, File | File[] | null>>({
  road_condition_photos: null,
  site_layout_photos: null,
  unloading_layout_photos: null,
  storage_facility_photos: null,
  measurement_evidence_photos: null,
  vessel_layout_photos: null,
  company_office_photos: null,
  additional_photos: null,
})

/* Validasi client-side minimal -- hampir semua field backend nullable, cukup
   wajibkan site_name (dipakai identitas baris di list) sebelum "Lanjut" dari
   Step 1. */
const wizardRules = {
  site_name: { required: helpers.withMessage('Nama lokasi wajib diisi.', required) },
}
const v$ = useVuelidate(wizardRules, form)
const siteNameError = computed(() => v$.value.site_name?.$errors[0]?.$message?.toString() || '')

const mapPreviewUrl = computed(() => {
  if (form.latitude === null || form.longitude === null) return ''
  return `https://maps.google.com/maps?q=${encodeURIComponent(`${form.latitude},${form.longitude}`)}&z=15&output=embed`
})

/* Fetch */
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

/* Helpers */
function normalizePhoto(item: any): PhotoItem {
  return { path: item?.path ?? '', url: item?.url ?? '', caption: item?.caption ?? '' }
}

function nonEmptyStrings(rows: string[]): string[] {
  return rows.map(row => row.trim()).filter(Boolean)
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

function existingPhotoFiles(items: PhotoItem[]) {
  return items.map((item, index) => ({
    id: index,
    name: item.caption || item.path.split('/').pop() || `Foto ${index + 1}`,
    url: item.url,
  }))
}

/* Actions: repeatable rows (add/remove baris teks bebas). */
function addTextRow(rows: string[]) {
  rows.push('')
}
function removeTextRow(rows: string[], index: number) {
  if (rows.length > 1) rows.splice(index, 1)
  else rows.splice(index, 1, '')
}

function addProductVolumeRow() {
  form.product_volume.push({ produk: '', volume_bulan: '' })
}
function removeProductVolumeRow(index: number) {
  if (form.product_volume.length > 1) form.product_volume.splice(index, 1)
  else form.product_volume.splice(index, 1, { produk: '', volume_bulan: '' })
}

function addPicRow() {
  form.picustomer.push({ nama: '', posisi: '', telepon: '' })
}
function removePicRow(index: number) {
  if (form.picustomer.length > 1) form.picustomer.splice(index, 1)
  else form.picustomer.splice(index, 1, { nama: '', posisi: '', telepon: '' })
}

function addRouteCostRow() {
  form.route_costs.push({ cost_type: '', amount: null, notes: '' })
}
function removeRouteCostRow(index: number) {
  if (form.route_costs.length > 1) form.route_costs.splice(index, 1)
  else form.route_costs.splice(index, 1, { cost_type: '', amount: null, notes: '' })
}

function buildMapLink() {
  if (form.latitude !== null && form.longitude !== null) {
    form.google_maps_link = `https://www.google.com/maps/search/?api=1&query=${form.latitude},${form.longitude}`
  }
}

/* Actions: media -- upload dulu ke storage (POST /api/uploads/lcr-image),
   baru simpan {path,url,caption:''} ke state form[field] -- payload akhir
   TIDAK PERNAH berisi File mentah. */
async function uploadSiteImage(file: File): Promise<{ path: string; url: string }> {
  const formData = new FormData()
  formData.append('file', file)
  const { data } = await axios.post('/api/uploads/lcr-image', formData)
  return { path: data.path, url: data.url }
}

async function handlePhotoSelected(field: PhotoField, value: File | File[] | null) {
  const files = Array.isArray(value) ? value : value ? [value] : []
  for (const file of files) {
    try {
      const uploaded = await uploadSiteImage(file)
      form[field].push({ path: uploaded.path, url: uploaded.url, caption: '' })
    } catch (e: any) {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengunggah gambar.')
    }
  }
  pendingPhotoFiles[field] = null
}

function handlePhotoCaption(field: PhotoField, file: File | { id?: string | number; name: string; url: string }, caption: string) {
  if (file instanceof File) return
  const index = Number(file.id)
  if (!Number.isNaN(index) && form[field][index]) {
    form[field][index].caption = caption
  }
}

function removePhoto(field: PhotoField, file: { id?: string | number; name: string; url: string }) {
  const index = Number(file.id)
  if (!Number.isNaN(index)) form[field].splice(index, 1)
}

/* Actions: wizard open/close/navigate */
function openCreateWizard() {
  editingSite.value = null
  wizardError.value = null
  v$.value.$reset()
  Object.assign(form, createDefaultForm())
  currentStep.value = 0
  wizardOpen.value = true
}

function openEditWizard(site: any) {
  editingSite.value = site
  wizardError.value = null
  v$.value.$reset()
  Object.assign(form, createDefaultForm(), {
    site_name: site.site_name ?? '',
    survey_address: site.survey_address ?? '',
    // Key RESPONSE lama (prov_survey dkk) dibaca di sini, key REQUEST baru
    // (survey_province dkk) dipakai saat submit ulang.
    survey_province: site.prov_survey !== null && site.prov_survey !== undefined ? String(site.prov_survey) : '',
    survey_regency: site.kab_survey !== null && site.kab_survey !== undefined ? String(site.kab_survey) : '',
    survey_date: site.survey_date ?? '',
    surveyor_names: site.surveyor_names?.length ? [...site.surveyor_names] : [''],
    site_business_type: site.site_business_type ?? '',
    site_business_type_other: site.site_business_type_other ?? '',
    site_environment: site.site_environment ?? '',
    site_environment_other: site.site_environment_other ?? '',
    site_environment_notes: site.site_environment_notes ?? '',
    competitors: site.competitors?.length ? [...site.competitors] : [''],
    operating_hours: site.operating_hours?.length ? [...site.operating_hours] : [''],
    product_volume: site.product_volume?.length
      ? site.product_volume.map((p: any) => ({ produk: p.produk ?? '', volume_bulan: p.volume_bulan ?? '' }))
      : [{ produk: '', volume_bulan: '' }],
    survey_notes: site.survey_notes ?? '',
    picustomer: site.picustomer?.length
      ? site.picustomer.map((p: any) => ({ nama: p.nama ?? '', posisi: p.posisi ?? '', telepon: p.telepon ?? '' }))
      : [{ nama: '', posisi: '', telepon: '' }],
    website: site.website ?? '',
    survey_phone: site.telp_survey ?? '',
    survey_fax: site.fax_survey ?? '',
    id_wilayah: site.id_wilayah !== null && site.id_wilayah !== undefined ? String(site.id_wilayah) : '',
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

    quality_checking_method: site.quality_checking_method ?? '',
    quality_checking_notes: site.quality_checking_notes ?? '',
    quantity_checking_method: site.quantity_checking_method ?? '',
    quantity_checking_notes: site.quantity_checking_notes ?? '',

    vessel_type: site.vessel_type ?? '',
    vessel_cargo_capacity: site.vessel_cargo_capacity ?? '',
    vessel_unloading_method: site.vessel_unloading_method ?? '',
    vessel_quantity_checking_method: site.vessel_quantity_checking_method ?? '',
    vessel_quantity_checking_notes: site.vessel_quantity_checking_notes ?? '',
    vessel_quality_checking_method: site.vessel_quality_checking_method ?? '',
    vessel_quality_checking_notes: site.vessel_quality_checking_notes ?? '',
    jetty_type: site.jetty_type ?? '',
    max_loa: site.max_loa ?? null,
    min_pbl: site.min_pbl ?? null,
    draft_lws: site.draft_lws ?? null,
    jetty_capacity_dwt: site.jetty_capacity_dwt ?? null,
    jetty_permit_info: site.jetty_permit_info ?? '',
    document_requirements: site.document_requirements ?? '',

    // Key RESPONSE lama (latitude_lokasi dkk).
    latitude: site.latitude_lokasi ?? null,
    longitude: site.longitude_lokasi ?? null,
    google_maps_link: site.link_google_maps ?? '',

    road_condition_photos: Array.isArray(site.road_condition_photos) ? site.road_condition_photos.map(normalizePhoto) : [],
    site_layout_photos: Array.isArray(site.site_layout_photos) ? site.site_layout_photos.map(normalizePhoto) : [],
    unloading_layout_photos: Array.isArray(site.unloading_layout_photos) ? site.unloading_layout_photos.map(normalizePhoto) : [],
    storage_facility_photos: Array.isArray(site.storage_facility_photos) ? site.storage_facility_photos.map(normalizePhoto) : [],
    measurement_evidence_photos: Array.isArray(site.measurement_evidence_photos) ? site.measurement_evidence_photos.map(normalizePhoto) : [],
    vessel_layout_photos: Array.isArray(site.vessel_layout_photos) ? site.vessel_layout_photos.map(normalizePhoto) : [],
    company_office_photos: Array.isArray(site.company_office_photos) ? site.company_office_photos.map(normalizePhoto) : [],
    additional_photos: Array.isArray(site.additional_photos) ? site.additional_photos.map(normalizePhoto) : [],
  })
  currentStep.value = 0
  wizardOpen.value = true
}

function closeWizard() {
  if (saving.value) return
  wizardOpen.value = false
}

async function goNext() {
  if (currentStep.value === 0) {
    const valid = await v$.value.$validate()
    if (!valid) {
      notifyError('Gagal', 'Nama lokasi wajib diisi sebelum lanjut.')
      return
    }
  }
  if (currentStep.value < steps.length - 1) currentStep.value += 1
}

function goBack() {
  if (currentStep.value > 0) currentStep.value -= 1
}

/* Submit */
function buildPayload() {
  return {
    ...form,
    surveyor_names: nonEmptyStrings(form.surveyor_names),
    competitors: nonEmptyStrings(form.competitors),
    operating_hours: nonEmptyStrings(form.operating_hours),
    product_volume: form.product_volume.filter(p => p.produk.trim() || p.volume_bulan.trim()),
    picustomer: form.picustomer.filter(p => p.nama.trim() || p.posisi.trim() || p.telepon.trim()),
    // route_costs.*.cost_type wajib diisi HANYA untuk baris yang punya
    // amount/notes terisi (skip baris kosong sepenuhnya) -- sesuai rule
    // backend route_costs.*.cost_type => required.
    route_costs: form.route_costs.filter(r => r.cost_type.trim() || r.amount !== null || r.notes.trim()),
    // Select enum kosong ('') harus dikirim null, bukan string kosong --
    // Enum rule Laravel tidak menganggap '' sebagai "nullable".
    vessel_type: form.vessel_type || null,
    vessel_unloading_method: form.vessel_unloading_method || null,
    vessel_quantity_checking_method: form.vessel_quantity_checking_method || null,
  }
}

async function submitWizard() {
  wizardError.value = null
  saving.value = true
  try {
    const payload = buildPayload()
    if (editingSite.value) {
      await api.update(editingSite.value.id_lcr, payload)
    } else {
      await api.store(payload)
    }
    wizardOpen.value = false
    await fetchSites()
    success('Berhasil', editingSite.value ? 'Site LCR berhasil diperbarui.' : 'Site LCR berhasil ditambahkan.')
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

/* Actions: delete */
function confirmDeleteSite(site: any) {
  deleteTarget.value = site
  deleteDialogOpen.value = true
}

async function performDeleteSite() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await api.destroy(deleteTarget.value.id_lcr)
    sites.value = sites.value.filter(s => s.id_lcr !== deleteTarget.value.id_lcr)
    success('Berhasil', 'Site LCR berhasil dihapus.')
    deleteDialogOpen.value = false
    deleteTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus site LCR.')
  } finally {
    deleteLoading.value = false
  }
}

onMounted(fetchSites)
</script>

<template>
  <div class="grid gap-6">
    <CardSection title="Site LCR" description="Lokasi survei LCR milik customer ini." icon="MapPin"
      icon-class="bg-cyan-100 text-cyan-600">
      <template #action>
        <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2" @click="openCreateWizard">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah Site
        </Button>
      </template>

      <div v-if="loading" class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
        <span class="font-body">Memuat site LCR...</span>
      </div>

      <div v-else-if="sites.length === 0"
        class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
        <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
        <div class="font-body">Belum ada site LCR yang ditambahkan.</div>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <Table.Thead>
            <Table.Tr>
              <Table.Th class="w-12">No</Table.Th>
              <Table.Th>Nama Lokasi</Table.Th>
              <Table.Th>Alamat Survey</Table.Th>
              <Table.Th>Tanggal Survey</Table.Th>
              <Table.Th>Status Approval</Table.Th>
              <Table.Th class="text-center">Aksi</Table.Th>
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            <Table.Tr v-for="(site, index) in sites" :key="site.id_lcr" class="transition hover:bg-slate-50">
              <Table.Td class="font-num">{{ index + 1 }}.</Table.Td>
              <Table.Td class="font-strong">{{ site.site_name || '-' }}</Table.Td>
              <Table.Td class="max-w-xs truncate">{{ site.survey_address || '-' }}</Table.Td>
              <Table.Td>{{ site.survey_date || '-' }}</Table.Td>
              <Table.Td>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="approvalBadgeClass(site.approval?.status)">
                  {{ site.approval?.status_label ?? 'Belum Diajukan' }}
                </span>
              </Table.Td>
              <Table.Td class="text-center">
                <div class="inline-flex items-center justify-center gap-2">
                  <Button variant="soft-pending" rounded title="Edit" class="!h-8 !w-8 !p-0 !shadow-none"
                    @click="openEditWizard(site)">
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </Button>
                  <Button variant="soft-danger" rounded title="Hapus" class="!h-8 !w-8 !p-0 !shadow-none"
                    @click="confirmDeleteSite(site)">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </CardSection>

    <FormWizardModal :open="wizardOpen" :title="editingSite ? 'Edit Site LCR' : 'Tambah Site LCR'"
      description="Isi data survei lokasi LCR secara bertahap." :steps="steps" :current-step="currentStep"
      :loading="saving" :error="wizardError" size="xl" :submit-text="editingSite ? 'Simpan Perubahan' : 'Simpan'"
      @close="closeWizard" @back="goBack" @next="goNext" @submit="submitWizard">
      <!-- Step 1: Informasi Umum (Grup 1) -->
      <div v-if="currentStep === 0" class="grid gap-4">
        <div class="grid gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <FormLabel>
              Nama Lokasi
              <RequiredAsterisk />
            </FormLabel>
            <FormInput v-model="form.site_name" placeholder="Nama lokasi/site"
              :class="siteNameError ? 'border-rose-500' : ''" @blur="v$.site_name.$touch()" />
            <small v-if="siteNameError" class="font-caption !text-rose-600">{{ siteNameError }}</small>
          </div>

          <div class="md:col-span-2">
            <FormLabel>Alamat Survey</FormLabel>
            <FormTextarea v-model="form.survey_address" rows="2" />
          </div>

          <div>
            <FormLabel>Kode Provinsi (Survey)</FormLabel>
            <FormInput type="number" v-model="form.survey_province" placeholder="cth. 35" />
          </div>
          <div>
            <FormLabel>Kode Kabupaten/Kota (Survey)</FormLabel>
            <FormInput type="number" v-model="form.survey_regency" placeholder="cth. 3578" />
          </div>

          <div>
            <FormLabel>Tanggal Survey</FormLabel>
            <DateField v-model="form.survey_date" />
          </div>
          <div>
            <FormLabel>Website</FormLabel>
            <FormInput v-model="form.website" placeholder="https://..." />
          </div>

          <div>
            <FormLabel>Telepon Survey</FormLabel>
            <FormInput v-model="form.survey_phone" />
          </div>
          <div>
            <FormLabel>Fax Survey</FormLabel>
            <FormInput v-model="form.survey_fax" />
          </div>

          <div>
            <FormLabel>ID Wilayah (Cabang)</FormLabel>
            <FormInput type="number" v-model="form.id_wilayah" />
          </div>
          <div>
            <FormLabel>ID Wilayah OA</FormLabel>
            <FormInput type="number" v-model="form.id_wil_oa" />
          </div>

          <div>
            <FormLabel>Jenis Usaha Site</FormLabel>
            <FormInput v-model="form.site_business_type" placeholder="cth. Manufacture, Trading" />
          </div>
          <div>
            <FormLabel>Jenis Usaha Lainnya</FormLabel>
            <FormInput v-model="form.site_business_type_other" />
          </div>

          <div>
            <FormLabel>Lingkungan Site</FormLabel>
            <FormInput v-model="form.site_environment" />
          </div>
          <div>
            <FormLabel>Lingkungan Lainnya</FormLabel>
            <FormInput v-model="form.site_environment_other" />
          </div>
          <div class="md:col-span-2">
            <FormLabel>Catatan Lingkungan</FormLabel>
            <FormTextarea v-model="form.site_environment_notes" rows="2" />
          </div>

          <div class="md:col-span-2">
            <FormLabel>Catatan Survey</FormLabel>
            <FormTextarea v-model="form.survey_notes" rows="2" />
          </div>
        </div>

        <div>
          <FormLabel>Mendukung Pengiriman via Kapal?</FormLabel>
          <div class="mt-2 flex items-center gap-3">
            <FormSwitch>
              <FormSwitch.Input v-model="form.supports_vessel_delivery" type="checkbox" />
            </FormSwitch>
            <span class="font-body">
              {{ form.supports_vessel_delivery ? 'Ya, tampilkan section Vessel/Jetty' : 'Tidak' }}
            </span>
          </div>
        </div>

        <!-- Nama Surveyor (repeatable string) -->
        <div>
          <div class="font-section mb-2">Nama Surveyor</div>
          <div class="space-y-2">
            <div v-for="(_, i) in form.surveyor_names" :key="i" class="flex items-center gap-2">
              <FormInput v-model="form.surveyor_names[i]" class="flex-1" placeholder="Nama surveyor" />
              <Button size="sm" variant="outline-secondary" @click="addTextRow(form.surveyor_names)">
                <Lucide icon="Plus" class="h-4 w-4" />
              </Button>
              <Button size="sm" variant="soft-danger" :disabled="form.surveyor_names.length === 1"
                @click="removeTextRow(form.surveyor_names, i)">
                <Lucide icon="X" class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Kompetitor (repeatable string) -->
        <div>
          <div class="font-section mb-2">Kompetitor</div>
          <div class="space-y-2">
            <div v-for="(_, i) in form.competitors" :key="i" class="flex items-center gap-2">
              <FormInput v-model="form.competitors[i]" class="flex-1" placeholder="Nama kompetitor" />
              <Button size="sm" variant="outline-secondary" @click="addTextRow(form.competitors)">
                <Lucide icon="Plus" class="h-4 w-4" />
              </Button>
              <Button size="sm" variant="soft-danger" :disabled="form.competitors.length === 1"
                @click="removeTextRow(form.competitors, i)">
                <Lucide icon="X" class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Jam Operasional (repeatable string) -->
        <div>
          <div class="font-section mb-2">Jam Operasional</div>
          <div class="space-y-2">
            <div v-for="(_, i) in form.operating_hours" :key="i" class="flex items-center gap-2">
              <FormInput v-model="form.operating_hours[i]" class="flex-1" placeholder="cth. Senin-Jumat 08:00-16:00" />
              <Button size="sm" variant="outline-secondary" @click="addTextRow(form.operating_hours)">
                <Lucide icon="Plus" class="h-4 w-4" />
              </Button>
              <Button size="sm" variant="soft-danger" :disabled="form.operating_hours.length === 1"
                @click="removeTextRow(form.operating_hours, i)">
                <Lucide icon="X" class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Produk & Volume/Bulan (repeatable object) -->
        <div>
          <div class="font-section mb-2">Produk &amp; Volume/Bulan</div>
          <div class="overflow-x-auto">
            <Table>
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>Produk</Table.Th>
                  <Table.Th>Volume/Bulan</Table.Th>
                  <Table.Th class="text-center">Aksi</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                <Table.Tr v-for="(row, i) in form.product_volume" :key="i">
                  <Table.Td><FormInput v-model="row.produk" placeholder="Produk" /></Table.Td>
                  <Table.Td><FormInput v-model="row.volume_bulan" placeholder="cth. 5000 liter" /></Table.Td>
                  <Table.Td class="text-center">
                    <div class="inline-flex items-center gap-2">
                      <Button size="sm" variant="outline-secondary" @click="addProductVolumeRow">
                        <Lucide icon="Plus" class="h-4 w-4" />
                      </Button>
                      <Button size="sm" variant="soft-danger" :disabled="form.product_volume.length === 1"
                        @click="removeProductVolumeRow(i)">
                        <Lucide icon="X" class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </div>
        </div>

        <!-- Penanggung Jawab / PIC (repeatable object) -->
        <div>
          <div class="font-section mb-2">Penanggung Jawab (PIC)</div>
          <div class="overflow-x-auto">
            <Table>
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>Nama</Table.Th>
                  <Table.Th>Posisi</Table.Th>
                  <Table.Th>Telepon</Table.Th>
                  <Table.Th class="text-center">Aksi</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                <Table.Tr v-for="(row, i) in form.picustomer" :key="i">
                  <Table.Td><FormInput v-model="row.nama" placeholder="Nama" /></Table.Td>
                  <Table.Td><FormInput v-model="row.posisi" placeholder="Posisi/Jabatan" /></Table.Td>
                  <Table.Td><FormInput v-model="row.telepon" placeholder="Telepon" /></Table.Td>
                  <Table.Td class="text-center">
                    <div class="inline-flex items-center gap-2">
                      <Button size="sm" variant="outline-secondary" @click="addPicRow">
                        <Lucide icon="Plus" class="h-4 w-4" />
                      </Button>
                      <Button size="sm" variant="soft-danger" :disabled="form.picustomer.length === 1"
                        @click="removePicRow(i)">
                        <Lucide icon="X" class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </div>
        </div>
      </div>

      <!-- Step 2: Lokasi (Grup 2 + koordinat Grup 7) -->
      <div v-else-if="currentStep === 1" class="grid gap-4">
        <div class="grid gap-4 md:grid-cols-2">
          <NumberField v-model="form.max_truck_capacity_min" label="Kapasitas Truk Min (KL)" :decimals="2" />
          <NumberField v-model="form.max_truck_capacity_max" label="Kapasitas Truk Max (KL)" :decimals="2" />

          <div class="md:col-span-2">
            <FormLabel>Catatan Akses</FormLabel>
            <FormTextarea v-model="form.access_notes" rows="2" />
          </div>

          <div>
            <FormLabel>Jarak dari Depot</FormLabel>
            <FormInput v-model="form.distance_from_depot" placeholder="cth. 39,1 KM" />
          </div>
          <div>
            <FormLabel>Minimal Volume Kirim</FormLabel>
            <FormInput v-model="form.min_vol_kirim" placeholder="cth. 5 m³" />
          </div>

          <div>
            <FormLabel>Rute Lokasi</FormLabel>
            <FormTextarea v-model="form.rute_lokasi" rows="3" />
          </div>
          <div>
            <FormLabel>Catatan Lokasi</FormLabel>
            <FormTextarea v-model="form.note_lokasi" rows="3" />
          </div>
        </div>

        <!-- Biaya Rute (repeatable object) -->
        <div>
          <div class="font-section mb-2">Biaya Rute</div>
          <div class="overflow-x-auto">
            <Table>
              <Table.Thead>
                <Table.Tr>
                  <Table.Th>Jenis Biaya</Table.Th>
                  <Table.Th>Nominal</Table.Th>
                  <Table.Th>Catatan</Table.Th>
                  <Table.Th class="text-center">Aksi</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                <Table.Tr v-for="(row, i) in form.route_costs" :key="i">
                  <Table.Td><FormInput v-model="row.cost_type" placeholder="cth. Tol, Parkir" /></Table.Td>
                  <Table.Td><NumberField v-model="row.amount" :decimals="0" /></Table.Td>
                  <Table.Td><FormInput v-model="row.notes" placeholder="Catatan" /></Table.Td>
                  <Table.Td class="text-center">
                    <div class="inline-flex items-center gap-2">
                      <Button size="sm" variant="outline-secondary" @click="addRouteCostRow">
                        <Lucide icon="Plus" class="h-4 w-4" />
                      </Button>
                      <Button size="sm" variant="soft-danger" :disabled="form.route_costs.length === 1"
                        @click="removeRouteCostRow(i)">
                        <Lucide icon="X" class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </div>
        </div>

        <!-- Koordinat (Grup 7) -->
        <div class="grid gap-4 md:grid-cols-2">
          <NumberField v-model="form.latitude" label="Latitude" :decimals="7" />
          <NumberField v-model="form.longitude" label="Longitude" :decimals="7" />
          <div class="md:col-span-2">
            <FormLabel>Link Google Maps</FormLabel>
            <div class="flex gap-2">
              <FormInput v-model="form.google_maps_link" class="flex-1" placeholder="https://maps.google.com/..." />
              <Button type="button" variant="outline-secondary" title="Buat link dari Latitude/Longitude"
                :disabled="form.latitude === null || form.longitude === null" @click="buildMapLink">
                <Lucide icon="MapPinned" class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <div v-if="mapPreviewUrl" class="h-64 overflow-hidden rounded-lg border border-slate-200">
          <iframe :src="mapPreviewUrl" class="h-full w-full" loading="lazy" />
        </div>
      </div>

      <!-- Step 3: Info Unloading (Grup 3+4+5 selalu, Grup 6 non-foto kondisional) -->
      <div v-else-if="currentStep === 2" class="grid gap-6">
        <div>
          <div class="font-section mb-3">Layout &amp; Unloading Truk</div>
          <div class="grid gap-4 md:grid-cols-2">
            <FormInput v-model="form.unloading_method" placeholder="Metode Unloading" />
            <NumberField v-model="form.max_trucks_per_day" label="Maks Truk per Hari" :decimals="0" />
            <div class="md:col-span-2">
              <FormLabel>Catatan Unloading</FormLabel>
              <FormTextarea v-model="form.unloading_notes" rows="2" />
            </div>
          </div>
        </div>

        <div>
          <div class="font-section mb-3">Penyimpanan</div>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <FormLabel>Tipe Penyimpanan</FormLabel>
              <FormInput v-model="form.storage_type" />
            </div>
            <div>
              <FormLabel>Tipe Penyimpanan Lainnya</FormLabel>
              <FormInput v-model="form.storage_type_other" />
            </div>
            <div>
              <FormLabel>Kapasitas Penyimpanan</FormLabel>
              <FormInput v-model="form.storage_capacity" placeholder="cth. 50 KL" />
            </div>
            <div class="md:col-span-2">
              <FormLabel>Catatan Penyimpanan</FormLabel>
              <FormTextarea v-model="form.storage_notes" rows="2" />
            </div>
          </div>
        </div>

        <div>
          <div class="font-section mb-3">Verifikasi Quality/Quantity</div>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <FormLabel>Metode Pemeriksaan Kualitas</FormLabel>
              <FormInput v-model="form.quality_checking_method" />
            </div>
            <div>
              <FormLabel>Catatan Pemeriksaan Kualitas</FormLabel>
              <FormTextarea v-model="form.quality_checking_notes" rows="2" />
            </div>
            <div>
              <FormLabel>Metode Pemeriksaan Kuantitas</FormLabel>
              <FormInput v-model="form.quantity_checking_method" />
            </div>
            <div>
              <FormLabel>Catatan Pemeriksaan Kuantitas</FormLabel>
              <FormTextarea v-model="form.quantity_checking_notes" rows="2" />
            </div>
          </div>
        </div>

        <div v-if="form.supports_vessel_delivery">
          <div class="font-section mb-3">Vessel &amp; Jetty</div>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <FormLabel>Jenis Kapal</FormLabel>
              <FormSelect v-model="form.vessel_type">
                <option value="">- Pilihan -</option>
                <option v-for="o in vesselTypeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
              </FormSelect>
            </div>
            <div>
              <FormLabel>Kapasitas Kargo Kapal</FormLabel>
              <FormInput v-model="form.vessel_cargo_capacity" />
            </div>

            <div>
              <FormLabel>Metode Bongkar Kapal</FormLabel>
              <FormSelect v-model="form.vessel_unloading_method">
                <option value="">- Pilihan -</option>
                <option v-for="o in vesselUnloadingMethodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
              </FormSelect>
            </div>
            <div />

            <div>
              <FormLabel>Metode Pemeriksaan Kuantitas Kapal</FormLabel>
              <FormSelect v-model="form.vessel_quantity_checking_method">
                <option value="">- Pilihan -</option>
                <option v-for="o in vesselQuantityCheckingMethodOptions" :key="o.value" :value="o.value">
                  {{ o.label }}
                </option>
              </FormSelect>
            </div>
            <div>
              <FormLabel>Catatan Pemeriksaan Kuantitas Kapal</FormLabel>
              <FormTextarea v-model="form.vessel_quantity_checking_notes" rows="2" />
            </div>

            <div>
              <FormLabel>Metode Pemeriksaan Kualitas Kapal</FormLabel>
              <FormInput v-model="form.vessel_quality_checking_method" />
            </div>
            <div>
              <FormLabel>Catatan Pemeriksaan Kualitas Kapal</FormLabel>
              <FormTextarea v-model="form.vessel_quality_checking_notes" rows="2" />
            </div>

            <div>
              <FormLabel>Tipe Jetty</FormLabel>
              <FormInput v-model="form.jetty_type" />
            </div>
            <NumberField v-model="form.max_loa" label="Max LOA" :decimals="2" />
            <NumberField v-model="form.min_pbl" label="Min PBL" :decimals="2" />
            <NumberField v-model="form.draft_lws" label="Draft (LWS)" :decimals="2" />
            <NumberField v-model="form.jetty_capacity_dwt" label="Kapasitas Jetty (DWT)" :decimals="2" />

            <div class="md:col-span-2">
              <FormLabel>Info Izin Jetty</FormLabel>
              <FormTextarea v-model="form.jetty_permit_info" rows="2" />
            </div>
            <div class="md:col-span-2">
              <FormLabel>Persyaratan Dokumen</FormLabel>
              <FormTextarea v-model="form.document_requirements" rows="2" />
            </div>
          </div>
        </div>
      </div>

      <!-- Step 4: Media & Dokumen Pendukung (8 field foto) -->
      <div v-else class="grid gap-6">
        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.road_condition_photos" multiple with-caption
            label="Kondisi Jalan Menuju Lokasi" :existing-files="existingPhotoFiles(form.road_condition_photos)"
            @update:model-value="(v) => handlePhotoSelected('road_condition_photos', v)"
            @remove-existing="(f) => removePhoto('road_condition_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('road_condition_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.site_layout_photos" multiple with-caption
            label="Layout Site/Pabrik" :existing-files="existingPhotoFiles(form.site_layout_photos)"
            @update:model-value="(v) => handlePhotoSelected('site_layout_photos', v)"
            @remove-existing="(f) => removePhoto('site_layout_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('site_layout_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.unloading_layout_photos" multiple with-caption
            label="Layout Area Unloading" :existing-files="existingPhotoFiles(form.unloading_layout_photos)"
            @update:model-value="(v) => handlePhotoSelected('unloading_layout_photos', v)"
            @remove-existing="(f) => removePhoto('unloading_layout_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('unloading_layout_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.storage_facility_photos" multiple with-caption
            label="Fasilitas Penyimpanan" :existing-files="existingPhotoFiles(form.storage_facility_photos)"
            @update:model-value="(v) => handlePhotoSelected('storage_facility_photos', v)"
            @remove-existing="(f) => removePhoto('storage_facility_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('storage_facility_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.measurement_evidence_photos" multiple with-caption
            label="Bukti Alat Ukur" :existing-files="existingPhotoFiles(form.measurement_evidence_photos)"
            @update:model-value="(v) => handlePhotoSelected('measurement_evidence_photos', v)"
            @remove-existing="(f) => removePhoto('measurement_evidence_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('measurement_evidence_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div v-if="form.supports_vessel_delivery">
          <ImageUploadField :model-value="pendingPhotoFiles.vessel_layout_photos" multiple with-caption
            label="Layout Vessel/Jetty" :existing-files="existingPhotoFiles(form.vessel_layout_photos)"
            @update:model-value="(v) => handlePhotoSelected('vessel_layout_photos', v)"
            @remove-existing="(f) => removePhoto('vessel_layout_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('vessel_layout_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.company_office_photos" multiple with-caption
            label="Kantor &amp; Gerbang Perusahaan" :existing-files="existingPhotoFiles(form.company_office_photos)"
            @update:model-value="(v) => handlePhotoSelected('company_office_photos', v)"
            @remove-existing="(f) => removePhoto('company_office_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('company_office_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>

        <div>
          <ImageUploadField :model-value="pendingPhotoFiles.additional_photos" multiple with-caption
            label="Foto Tambahan" :existing-files="existingPhotoFiles(form.additional_photos)"
            @update:model-value="(v) => handlePhotoSelected('additional_photos', v)"
            @remove-existing="(f) => removePhoto('additional_photos', f)"
            @update:caption="(f, c) => handlePhotoCaption('additional_photos', f, c)"
            @error="(msg: string) => notifyError('Gagal', msg)" />
        </div>
      </div>
    </FormWizardModal>

    <DeleteRecordDialog :open="deleteDialogOpen" title="Hapus Site LCR"
      :description="`Site ${deleteTarget?.site_name ?? ''} akan dihapus permanen.`" :loading="deleteLoading"
      @close="deleteDialogOpen = false" @confirm="performDeleteSite" />
  </div>
</template>
