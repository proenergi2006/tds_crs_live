<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'

import Lucide from '@/components/Base/Lucide'
import { type Icon } from '@/components/Base/Lucide/Lucide.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useRegionCascade } from '@/composables/useRegionCascade'
import { createResourceApi } from '@/utils/resourceApi'

import OnboardingLayout from './components/OnboardingLayout.vue'
import OnboardingSidebar from './components/OnboardingSidebar.vue'
import StepCompanyInformation from './components/StepCompanyInformation.vue'
import StepCompanyDocuments from './components/StepCompanyDocuments.vue'
import StepPaymentInfo from './components/StepPaymentInfo.vue'
import StepLogisticClaimInfo from './components/StepLogisticClaimInfo.vue'
import StepSummaryAgreement from './components/StepSummaryAgreement.vue'
import type { OnboardingForm, OnboardingPageState } from './types'
import {
  incoTermsOptions,
  ownershipOptions,
  paymentMethodOptions,
  paymentTermBasisOptions,
  qualityCheckingOptions,
  quantityCheckingOptions,
  siteEnvironmentOptions,
  storageTypeOptions,
  typeBusinessOptions,
} from './optionSets'

/* Composables */
const route = useRoute()
const { success, error } = useNotification()
const api = createResourceApi('customer-onboarding')

/* State: token & page lifecycle */
const token = route.params.token as string
const pageState = ref<OnboardingPageState>('loading')

/* State: wizard */
const currentStep = ref(1)
const stepTitles = [
  'Company Information',
  'Document Attachments',
  'Payment Info',
  'Logistic Info',
  'Summary & Agreement',
]

/* State: form & error */
function createDefaultForm(): OnboardingForm {
  return {
    identity: {
      company_name: '',
      company_address: '',
      phone: '',
      fax: '',
      email: '',
      website: '',
      business_type: '',
      business_type_other: '',
      ownership_type: '',
      ownership_type_other: '',
      parent_company: '',
      province_id: null,
      regency_id: null,
      district_id: null,
      village_id: null,
      postal_code: '',
      customer_sub_district: '',
      customer_village: '',
      inco_terms: '',
      inco_terms_other: '',
    },
    registered_address: {
      address_line: '',
      province_id: null,
      regency_id: null,
      district_id: null,
      village_id: null,
      postal_code: '',
    },
    invoice_contact: {
      name: '',
      position: '',
      phone: '',
      mobile: '',
      email: '',
    },
    payment: {
      method: '',
      method_other: '',
      invoice_tax: false,
      note: '',
      /* Sementara pricing method calculation cuma berlaku Quotation di TDS —
         field disembunyikan dari user, fixed default dikirim ke backend. */
      pricing_method: 'Quotation',
      bank_name: '',
      currency: 'IDR',
      bank_address: '',
      account_number: '',
      has_credit: false,
      creditor_name: '',
      term: '',
      term_days: null,
      term_basis: '',
    },
    logistics: {
      site_environment: '',
      site_environment_other: '',
      site_environment_notes: '',
      storage_type: '',
      storage_type_other: '',
      storage_notes: '',
      operating_hours: '',
      operating_hours_other: '',
      quality_checking_method: '',
      quality_checking_notes: '',
      quantity_checking_method: '',
      quantity_checking_notes: '',
      max_truck_capacity_min: null,
      max_truck_capacity_max: null,
      supports_vessel_delivery: false,
      product_notes: '',
      estimated_monthly_volume: null,
    },
    documents: {
      nib: { file: null, number: '' },
      npwp: { file: null, number: '' },
      sertifikat: { file: null, number: '' },
      dokumen_lainnya: [],
    },
    agreement: {
      agree: false,
      updated_by: '',
    },
  }
}

const form = reactive<OnboardingForm>(createDefaultForm())
const fieldErrors = reactive<Record<string, string>>({})
const submitting = ref(false)

/* State: region cascade — 2 instance, Head Office & NPWP */
const headOfficeRegion = useRegionCascade()
const npwpRegion = useRegionCascade()

/* State: mapping group payload -> step, dipakai untuk badge error sidebar & auto-jump 422 */
const GROUP_STEP_MAP: Record<string, number> = {
  identity: 1,
  registered_address: 1,
  invoice_contact: 1,
  documents: 2,
  payment: 3,
  logistics: 4,
  agreement: 5,
}

const stepsMeta = computed(() =>
  stepTitles.map((title, idx) => ({
    title,
    hasError: Object.keys(fieldErrors).some((key) => GROUP_STEP_MAP[key.split('.')[0]] === idx + 1),
  })),
)

/* Tampilan non-active state (used/expired/invalidated/not-found) -- hanya
   pakai data yang benar-benar tersedia sekarang (nama perusahaan, status).
   Tidak ada nomor registrasi/QR/tanggal submit/PDF karena backend belum
   punya data itu (belum ada kolom submitted_at, nomor registrasi, dst). */
type NonActiveState = 'used' | 'expired' | 'invalidated' | 'not-found'
const statusDisplay = computed((): {
  icon: Icon
  iconBg: string
  iconColor: string
  title: string
  description: string
} => {
  const map: Record<NonActiveState, { icon: Icon; iconBg: string; iconColor: string; title: string; description: string }> = {
    used: {
      icon: 'CheckCircle2',
      iconBg: 'bg-emerald-100',
      iconColor: 'text-emerald-600',
      title: 'Formulir Sudah Terkirim',
      description: form.identity.company_name
        ? `Terima kasih, data registrasi ${form.identity.company_name} sudah kami terima dan sedang dalam proses verifikasi oleh tim kami. Tidak ada tindakan lebih lanjut yang diperlukan saat ini.`
        : 'Data registrasi sudah kami terima dan sedang dalam proses verifikasi oleh tim kami. Tidak ada tindakan lebih lanjut yang diperlukan saat ini.',
    },
    expired: {
      icon: 'Clock',
      iconBg: 'bg-amber-100',
      iconColor: 'text-amber-600',
      title: 'Link Sudah Kedaluwarsa',
      description: 'Link registrasi ini sudah tidak berlaku lagi. Silakan hubungi tim marketing kami untuk mendapatkan link baru.',
    },
    invalidated: {
      icon: 'Ban',
      iconBg: 'bg-rose-100',
      iconColor: 'text-rose-600',
      title: 'Link Tidak Berlaku',
      description: 'Link ini sudah digantikan dengan link yang lebih baru. Jika Anda merasa ini keliru, silakan hubungi tim marketing kami.',
    },
    'not-found': {
      icon: 'SearchX',
      iconBg: 'bg-slate-100',
      iconColor: 'text-slate-500',
      title: 'Link Tidak Ditemukan',
      description: 'Link registrasi ini tidak valid atau sudah tidak tersedia. Pastikan Anda menggunakan link yang benar.',
    },
  }

  return map[(pageState.value as NonActiveState) in map ? (pageState.value as NonActiveState) : 'not-found']
})

/* Fetch: status token */
async function fetchStatus() {
  try {
    const { data } = await api.getById(token)
    pageState.value = data.status

    if (data.customer) Object.assign(form.identity, data.customer)
    if (data.registered_address) Object.assign(form.registered_address, data.registered_address)

    if (pageState.value === 'active') {
      await headOfficeRegion.fetchProvinces()
      await npwpRegion.fetchProvinces()

      if (form.identity.province_id) await headOfficeRegion.fetchRegencies(form.identity.province_id)
      if (form.identity.regency_id) await headOfficeRegion.fetchDistricts(form.identity.regency_id)
      if (form.identity.district_id) await headOfficeRegion.fetchVillages(form.identity.district_id)

      if (form.registered_address.province_id) await npwpRegion.fetchRegencies(form.registered_address.province_id)
      if (form.registered_address.regency_id) await npwpRegion.fetchDistricts(form.registered_address.regency_id)
      if (form.registered_address.district_id) await npwpRegion.fetchVillages(form.registered_address.district_id)
    }
  } catch (e) {
    pageState.value = 'not-found'
  }
}
onMounted(fetchStatus)

/* Dev-only: isi seluruh form dengan data dummy untuk mempercepat testing alur
   submit (upload file tetap harus manual, tidak bisa disimulasikan). Tombol
   pemicunya di-guard isDev di template -- import.meta langsung di template
   expression tidak didukung compiler SFC Vue ("import.meta may appear only
   with 'sourceType: module'"), jadi harus dipindah jadi konstanta di sini. */
const isDev = import.meta.env.DEV
const seeding = ref(false)

async function seedRegion(
  region: ReturnType<typeof useRegionCascade>,
  target: { province_id: string | null; regency_id: string | null; district_id: string | null; village_id: string | null; postal_code: string },
  postalCode: string,
) {
  if (!region.provinces.value.length) await region.fetchProvinces()
  const province = region.provinces.value[0]
  if (!province) return
  target.province_id = province.id
  await region.fetchRegencies(province.id)

  const regency = region.regencies.value[0]
  if (!regency) return
  target.regency_id = regency.id
  await region.fetchDistricts(regency.id)

  const district = region.districts.value[0]
  if (!district) return
  target.district_id = district.id
  await region.fetchVillages(district.id)

  const village = region.villages.value[0]
  if (village) target.village_id = village.id
  target.postal_code = postalCode
}

async function seedDummyData() {
  seeding.value = true
  try {
    Object.assign(form.identity, {
      // company_name: 'PT Contoh Sejahtera Abadi',
      parent_company: 'PT Induk Sejahtera Group',
      company_address: 'Jl. Industri Raya No. 45, Kawasan Industri Pulogadung',
      phone: '021-4600123',
      fax: '021-4600124',
      email: 'info@contohsejahtera.co.id',
      website: 'https://www.contohsejahtera.co.id',
      business_type: typeBusinessOptions[0],
      ownership_type: ownershipOptions[0],
      inco_terms: incoTermsOptions[0].code,
    })
    await seedRegion(headOfficeRegion, form.identity, '13920')

    form.registered_address.address_line = form.identity.company_address
    await seedRegion(npwpRegion, form.registered_address, form.identity.postal_code)

    Object.assign(form.invoice_contact, {
      name: 'Budi Santoso',
      position: 'Finance Manager',
      phone: '021-4600125',
      mobile: '081234567890',
      email: 'budi.santoso@contohsejahtera.co.id',
    })

    Object.assign(form.payment, {
      method: paymentMethodOptions[0],
      invoice_tax: true,
      note: 'Dummy data untuk testing submit flow',
      bank_name: 'Bank Mandiri',
      bank_address: 'Jl. Jendral Sudirman No. 1, Jakarta',
      account_number: '1234567890',
      has_credit: true,
      creditor_name: 'Bank Mandiri',
      term: 'CREDIT',
      term_days: 30,
      term_basis: paymentTermBasisOptions[0].code,
    })

    Object.assign(form.logistics, {
      site_environment: siteEnvironmentOptions[0].value,
      site_environment_notes: 'Lokasi berada di kawasan industri, akses jalan besar.',
      storage_type: storageTypeOptions[0].value,
      storage_notes: 'Gudang tertutup dengan kapasitas penyimpanan memadai.',
      operating_hours: 'other',
      operating_hours_other: 'Senin-Sabtu 07.00-18.00',
      quality_checking_method: qualityCheckingOptions[0].value,
      quantity_checking_method: quantityCheckingOptions[0].value,
      max_truck_capacity_min: 8,
      max_truck_capacity_max: 20,
      supports_vessel_delivery: false,
      product_notes: 'Dummy data untuk testing submit flow',
      estimated_monthly_volume: 500,
    })

    form.documents.nib.number = '1234567890123'
    form.documents.npwp.number = '01.234.567.8-901.000'
    form.documents.sertifikat.number = 'SERT-2026-00123'

    Object.assign(form.agreement, {
      updated_by: 'Budi Santoso (Dev Seed)',
      agree: true,
    })

    success('Dummy Data Terisi', 'Semua field (kecuali upload file) sudah diisi data dummy.')
  } finally {
    seeding.value = false
  }
}

/* Halaman ini sibling dari Layout.vue (route publik, di luar auth), jadi
   ThemeSwitcher.vue (satu-satunya komponen yang menempelkan class `.theme-1`
   ke <html>) tidak pernah mount di sini. Tanpa class itu token warna
   (--color-theme-1) jatuh ke default :root. */
onMounted(() => { document.documentElement.classList.add('theme-1') })
onUnmounted(() => { document.documentElement.classList.remove('theme-1') })

/* Nav */
function next() {
  if (currentStep.value < stepTitles.length) currentStep.value++
}
function back() {
  if (currentStep.value > 1) currentStep.value--
}

/* Helpers: FormData builder (multipart, mendukung nested object/array/File) */
function appendFormValue(fd: FormData, key: string, value: unknown) {
  if (value === null || value === undefined || value === '') return
  if (value instanceof File) {
    fd.append(key, value)
    return
  }
  if (typeof value === 'boolean') {
    fd.append(key, value ? '1' : '0')
    return
  }
  fd.append(key, String(value))
}

function appendNested(fd: FormData, data: any, prefix: string) {
  if (data instanceof File) {
    appendFormValue(fd, prefix, data)
    return
  }
  if (Array.isArray(data)) {
    data.forEach((item, idx) => appendNested(fd, item, `${prefix}[${idx}]`))
    return
  }
  if (data !== null && typeof data === 'object') {
    Object.entries(data).forEach(([k, v]) => appendNested(fd, v, `${prefix}[${k}]`))
    return
  }
  appendFormValue(fd, prefix, data)
}

/* Submit */
async function submit() {
  Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k])
  submitting.value = true

  try {
    const fd = new FormData()
    appendNested(fd, form.identity, 'identity')
    appendNested(fd, form.registered_address, 'registered_address')
    appendNested(fd, form.invoice_contact, 'invoice_contact')
    appendNested(fd, form.payment, 'payment')
    appendNested(fd, form.logistics, 'logistics')
    appendNested(fd, form.agreement, 'agreement')
    appendNested(
      fd,
      {
        nib: form.documents.nib,
        npwp: form.documents.npwp,
        sertifikat: form.documents.sertifikat,
      },
      'documents',
    )
    form.documents.dokumen_lainnya.forEach((file, idx) => {
      if (file) fd.append(`documents[dokumen_lainnya][${idx}][file]`, file)
    })

    await api.updateMultipart(token, fd)
    pageState.value = 'used'
    success('Berhasil', 'Data onboarding berhasil dikirim.')
  } catch (e: any) {
    const status = e?.response?.status

    if (status === 422) {
      const errors = e?.response?.data?.errors ?? {}
      Object.entries(errors).forEach(([key, messages]) => {
        fieldErrors[key] = Array.isArray(messages) ? String(messages[0]) : String(messages)
      })
      const firstKey = Object.keys(errors)[0]
      if (firstKey) currentStep.value = GROUP_STEP_MAP[firstKey.split('.')[0]] ?? currentStep.value
      error('Validasi Gagal', 'Periksa kembali isian yang bertanda merah.')
    } else if (status === 409) {
      pageState.value = 'used'
      error('Sudah Terkirim', 'Data onboarding ini sudah pernah dikirim sebelumnya.')
    } else if (status === 404) {
      error('Token Tidak Valid', 'Link ini sudah tidak berlaku atau dinonaktifkan. Silakan muat ulang halaman.')
    } else {
      error('Gagal', e?.response?.data?.message || 'Gagal mengirim data onboarding.')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div v-if="pageState === 'loading'" class="flex min-h-screen items-center justify-center gap-2 text-slate-500">
      <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
      <span class="font-body">Memuat halaman...</span>
    </div>

    <OnboardingLayout v-else-if="pageState === 'active'">
      <template #sidebar>
        <OnboardingSidebar :steps="stepsMeta" :current-step="currentStep"
          :is-last-step="currentStep === stepTitles.length" :submitting="submitting" @back="back" @next="next"
          @submit="submit" />
      </template>

      <template #content>
        <StepCompanyInformation v-show="currentStep === 1" :form="form" :errors="fieldErrors"
          :head-office-region="headOfficeRegion" :npwp-region="npwpRegion" />
        <StepCompanyDocuments v-show="currentStep === 2" :form="form" :errors="fieldErrors" />
        <StepPaymentInfo v-show="currentStep === 3" :form="form" :errors="fieldErrors" />
        <StepLogisticClaimInfo v-show="currentStep === 4" :form="form" :errors="fieldErrors" />
        <StepSummaryAgreement v-show="currentStep === 5" :form="form" :errors="fieldErrors"
          :head-office-region="headOfficeRegion" :npwp-region="npwpRegion" />
      </template>
    </OnboardingLayout>

    <div v-else class="flex min-h-screen items-center justify-center px-5 py-16">
      <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" :class="statusDisplay.iconBg">
          <Lucide :icon="statusDisplay.icon" class="h-8 w-8" :class="statusDisplay.iconColor" />
        </div>
        <h1 class="font-header mt-5 text-xl">{{ statusDisplay.title }}</h1>
        <p class="font-caption mt-2">{{ statusDisplay.description }}</p>
      </div>
    </div>

    <button v-if="pageState === 'active' && isDev" type="button" :disabled="seeding" @click="seedDummyData"
      class="fixed bottom-4 right-4 z-40 flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2.5 font-label text-white shadow-lg transition hover:bg-amber-600 disabled:opacity-60">
      <Lucide :icon="seeding ? 'Loader2' : 'FlaskConical'" class="h-4 w-4" :class="seeding ? 'animate-spin' : ''" />
      {{ seeding ? 'Mengisi...' : 'Isi Data Dummy (Dev)' }}
    </button>
  </div>
</template>
