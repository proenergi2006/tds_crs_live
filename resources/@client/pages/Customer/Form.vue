<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import { debounce } from 'lodash'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { Dialog } from '@/components/Base/Headless'
import { FormCheck, FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useRegionCascade } from '@/composables/useRegionCascade'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()
const customerApi = createResourceApi('/customers')

const customerId = computed(() => Number(route.params.id || 0))
const mode = computed<'create' | 'edit'>(() => (customerId.value ? 'edit' : 'create'))

/* Tentukan varian (TDS vs Proenergi) dari nama route */
const isProenergi = computed(() => String(route.name ?? '').includes('proenergi'))
const indexRoute = computed(() =>
  isProenergi.value ? 'customers-list-proenergi' : 'customers-list'
)

const loading = ref(false)
const pageLoading = ref(false)
const formError = ref<string | null>(null)

/* State: kunci edit field inti customer -- true kalau mode edit & kyc_status
   != draft (guard 409 backend, CustomerController::update()). Widget kontak
   & dokumen (CustomerDataTab.vue, endpoint terpisah) TIDAK ikut guard ini. */
const isLocked = ref(false)

/* State: lookups (cascading province -> regency, BPS data via useRegionCascade) */
const region = useRegionCascade()

/* State: fallback notice untuk record lama yang cuma punya id_provinsi/
   id_kabupaten (skema pra-migrasi BPS), belum punya province_id/regency_id */
const hasLegacyAddressOnly = ref(false)
const legacyAddressLabel = ref('')

/* Nama pemilik (id_user) untuk ditampilkan di Ringkasan — create mode selalu
   user yang login (owner otomatis di-assign saat submit), edit mode ambil
   dari relasi `user` milik customer (bisa beda dari user yang sedang login). */
const editOwnerName = ref('')
const ownerName = computed(() =>
  mode.value === 'create' ? (auth.user?.name || '-') : (editOwnerName.value || '-')
)

const form = reactive({
  email: '',
  customer_type: '',
  company_name: '',
  company_address: '',
  province_id: '',
  regency_id: '',
  district_id: '',
  village_id: '',
  postal_code: '',
  phone: '',
  fax: '',
  marketing: [],
})

/* State: cek ketersediaan nama perusahaan (informational, tidak menahan submit) */
const nameCheckStatus = ref<'idle' | 'checking' | 'available' | 'taken'>('idle')
const nameMatches = ref<{
  id_customer: number
  company_name: string
  marketing: { id: number | null; name: string | null }
}[]>([])
const showDuplicatePopup = ref(false)

const rules = {
  company_name: {
    required: helpers.withMessage('Nama perusahaan wajib diisi', required),
  },
  province_id: {
    required: helpers.withMessage('Provinsi wajib dipilih', required),
  },
  regency_id: {
    required: helpers.withMessage('Kabupaten/Kota wajib dipilih', required),
  },
  phone: {
    required: helpers.withMessage('Telepon wajib diisi', required),
  },
  customer_type: {
    required: helpers.withMessage('Jenis customer wajib dipilih', required),
  },
}

const v$ = useVuelidate(rules, form)

const pageTitle = computed(() => {
  const suffix = isProenergi.value ? ' Proenergi' : ''
  return mode.value === 'create' ? `Tambah Customer${suffix}` : `Edit Customer${suffix}`
})

const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Tambahkan data customer baru untuk proses penawaran.'
    : 'Perbarui informasi customer.'
)

const submitText = computed(() =>
  mode.value === 'create' ? 'Simpan Customer' : 'Simpan Perubahan'
)

/* Guard: true selama fetchCustomer() mengisi province_id → village_id secara
   berjenjang di edit mode. Tanpa ini, tiap assignment di bawah memicu watcher
   yang sama dan balik me-reset field level berikutnya jadi '' — race dengan
   nilai yang baru saja di-set manual oleh fetchCustomer() (regency_id/
   district_id bisa kembali kosong meski data aslinya lengkap). Watcher tetap
   aktif normal untuk interaksi user (ganti pilihan manual di form). */
const isHydratingRegion = ref(false)

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

/* Watch: cek ketersediaan nama perusahaan — terpisah dari watcher cascade
   wilayah di atas, debounced supaya tidak request tiap keystroke. */
watch(() => form.company_name, debounce(checkCompanyName, 400))

onMounted(async () => {
  await region.fetchProvinces()
  if (mode.value === 'edit') {
    await fetchCustomer()
  }
})

async function fetchCustomer() {
  pageLoading.value = true
  try {
    const { data } = await customerApi.getById(customerId.value)
    Object.assign(form, {
      email: data.email || '',
      phone: data.phone || '',
      customer_type: data.customer_type || '',
      company_name: data.company_name || '',
      company_address: data.company_address || '',
      fax: data.fax || '',
      postal_code: data.postal_code || '',
    })
    editOwnerName.value = data.user?.name || ''
    isLocked.value = !!data.latest_verification && data.latest_verification.kyc_status !== 'draft'

    if (data.province_id) {
      isHydratingRegion.value = true
      try {
        form.province_id = String(data.province_id)
        await region.fetchRegencies(form.province_id)
        form.regency_id = data.regency_id ? String(data.regency_id) : ''
        if (form.regency_id) {
          await region.fetchDistricts(form.regency_id)
          form.district_id = data.district_id ? String(data.district_id) : ''
          if (form.district_id) {
            await region.fetchVillages(form.district_id)
            form.village_id = data.village_id ? String(data.village_id) : ''
          }
        }
      } finally {
        isHydratingRegion.value = false
      }
    } else if (data.id_provinsi) {
      // Record lama (sebelum migrasi BPS) cuma punya id_provinsi/id_kabupaten,
      // belum punya province_id/regency_id — tampilkan info dari relasi lama
      // dan minta user pilih ulang dari daftar wilayah BPS baru di bawah.
      hasLegacyAddressOnly.value = true
      legacyAddressLabel.value = [data.provinsi?.nama_provinsi, data.kabupaten?.nama_kabupaten]
        .filter(Boolean)
        .join(', ') || 'Data lokasi lama tidak lengkap'
    }
  } catch (e: any) {
    const isForbidden = e.response?.status === 403
    notifyError(
      isForbidden ? 'Akses Ditolak' : 'Gagal',
      isForbidden
        ? 'Kamu tidak punya akses untuk mengedit customer ini.'
        : e.response?.data?.message ?? 'Gagal memuat data customer',
    )
    router.push({ name: indexRoute.value })
  } finally {
    pageLoading.value = false
  }
}

async function checkCompanyName() {
  const companyName = form.company_name.trim()
  if (!companyName) {
    nameCheckStatus.value = 'idle'
    nameMatches.value = []
    return
  }

  nameCheckStatus.value = 'checking'
  try {
    const { data } = await axios.get('/api/customers/check-company-name', {
      params: {
        company_name: companyName,
        exclude_id: mode.value === 'edit' ? customerId.value : undefined,
      },
    })
    nameMatches.value = data.matches || []
    nameCheckStatus.value = data.available ? 'available' : 'taken'
  } catch {
    // Informational feature — kegagalan cek tidak boleh mengganggu pengisian form.
    nameCheckStatus.value = 'idle'
    nameMatches.value = []
  }
}

/* Binding manual (bukan v-model) supaya uppercase transform tidak memaksa
   cursor melompat ke akhir — set .value native pada <input> selalu
   memindahkan cursor kecuali posisi selection direstore manual setelahnya. */
function onCompanyNameInput(event: Event) {
  const target = event.target as HTMLInputElement
  const cursorPos = target.selectionStart
  form.company_name = target.value.toUpperCase()
  nextTick(() => {
    target.setSelectionRange(cursorPos, cursorPos)
  })
}

function getFieldError(field: keyof typeof form) {
  return v$.value[field]?.$errors[0]?.$message?.toString() || ''
}

async function submit() {
  formError.value = null
  const isValid = await v$.value.$validate()
  if (!isValid) {
    notifyError('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true
  try {
    const payload = { ...form }

    if (mode.value === 'create') {
      await customerApi.store(payload)
      success('Berhasil', 'Customer berhasil ditambahkan')
    } else {
      await customerApi.update(customerId.value, payload)
      success('Berhasil', 'Customer berhasil diperbarui')
    }

    router.push({ name: indexRoute.value })
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (e.response?.status === 422 && errors) {
      formError.value = Object.values(errors)
        .map((v: any) => v?.[0])
        .filter(Boolean)
        .join('\n')
    } else {
      formError.value = e.response?.data?.message ?? 'Terjadi kesalahan'
    }
  } finally {
    loading.value = false
  }
}

function cancel() {
  if (loading.value) return
  router.push({ name: indexRoute.value })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" surface="plain" size="full" layout="sidebar"
    footer-placement="sidebar" :loading="loading || pageLoading" :error="formError" :submit-text="submitText"
    :disable-submit="isLocked" submit-icon="Save" cancel-icon="ArrowLeft" @cancel="cancel" @submit="submit">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <Alert v-if="isLocked" variant="soft-warning" class="mb-4">
      Data inti customer ini terkunci (KYC sudah di-forward). Kontak &amp; dokumen tetap bisa diedit dari halaman
      Detail.
    </Alert>

    <!-- Section: Informasi Dasar -->
    <CardSection title="Informasi Dasar" description="Data utama customer dalam sistem">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel for="company_name">
            Nama Perusahaan
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="company_name" :value="form.company_name" placeholder="Nama Perusahaan" :disabled="isLocked"
            :class="getFieldError('company_name') ? 'border-rose-500' : ''" @input="onCompanyNameInput"
            @blur="v$.company_name.$touch()" />
          <small v-if="getFieldError('company_name')" class="font-caption !text-rose-600 mt-1">
            {{ getFieldError('company_name') }}
          </small>
          <div v-else-if="nameCheckStatus === 'available'" class="font-caption !text-emerald-600 mt-1">
            Nama tersedia
          </div>
          <div v-else-if="nameCheckStatus === 'taken'"
            class="font-caption flex items-center gap-2 !text-amber-600 mt-1">
            <span>Sudah terdaftar, {{ nameMatches.length }} kecocokan ditemukan</span>
            <button type="button" class="font-semibold underline underline-offset-2" @click="showDuplicatePopup = true">
              Lihat daftar
            </button>
          </div>
        </div>

        <div>
          <FormLabel for="customer_type">
            Jenis Customer
            <RequiredAsterisk />
          </FormLabel>
          <div class="flex gap-6">
            <FormCheck>
              <FormCheck.Input id="customer_type-retail" type="radio" value="Retail" :disabled="isLocked"
                v-model="form.customer_type" @change="v$.customer_type.$touch()" />
              <FormCheck.Label htmlFor="customer_type-retail">Retail</FormCheck.Label>
            </FormCheck>
            <FormCheck>
              <FormCheck.Input id="customer_type-project" type="radio" value="Project" :disabled="isLocked"
                v-model="form.customer_type" @change="v$.customer_type.$touch()" />
              <FormCheck.Label htmlFor="customer_type-project">Project</FormCheck.Label>
            </FormCheck>
          </div>
          <small v-if="getFieldError('customer_type')" class="font-caption !text-rose-600">
            {{ getFieldError('customer_type') }}
          </small>
        </div>

        <div>
          <FormLabel for="phone">
            Telepon
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="phone" v-model="form.phone" placeholder="Telepon" :disabled="isLocked"
            :class="getFieldError('phone') ? 'border-rose-500' : ''" @blur="v$.phone.$touch()" />
          <small v-if="getFieldError('phone')" class="font-caption !text-rose-600">
            {{ getFieldError('phone') }}
          </small>
        </div>

        <div>
          <FormLabel for="fax">Fax</FormLabel>
          <FormInput id="fax" v-model="form.fax" placeholder="Fax (opsional)" :disabled="isLocked" />
        </div>

        <div>
          <FormLabel for="email">Email</FormLabel>
          <FormInput id="email" v-model="form.email" type="email" placeholder="Email (opsional)" autocomplete="off"
            :disabled="isLocked" />
        </div>
      </div>
    </CardSection>

    <!-- Section: Detail Alamat -->
    <CardSection title="Detail Alamat" description="Alamat lengkap customer berdasarkan wilayah BPS">
      <Alert v-if="hasLegacyAddressOnly" variant="soft-warning" class="mb-4">
        Data lokasi customer ini masih pakai skema lama: <strong>{{ legacyAddressLabel }}</strong>.
        Silakan pilih ulang Provinsi &amp; Kabupaten/Kota di bawah berdasarkan daftar wilayah terbaru
        agar tersimpan dengan skema baru.
      </Alert>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel for="company_address">Alamat Perusahaan</FormLabel>
          <FormTextarea id="company_address" v-model="form.company_address" placeholder="Alamat perusahaan (opsional)"
            :rows="3" :disabled="isLocked" />
        </div>

        <div>
          <FormLabel for="province_id">
            Provinsi
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect id="province_id" v-model="form.province_id" class="w-full" :disabled="isLocked"
            :class="getFieldError('province_id') ? 'border-rose-500' : ''" @change="v$.province_id.$touch()">
            <option value="">Cari Provinsi</option>
            <option v-for="p in region.provinces.value" :key="p.id" :value="p.id">
              {{ p.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('province_id')" class="font-caption !text-rose-600">
            {{ getFieldError('province_id') }}
          </small>
        </div>

        <div>
          <FormLabel for="regency_id">
            Kabupaten/Kota
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect :key="String(!!form.province_id)" id="regency_id" v-model="form.regency_id" class="w-full"
            :disabled="isLocked" :class="getFieldError('regency_id') ? 'border-rose-500' : ''"
            @change="v$.regency_id.$touch()">
            <option value="">
              {{ form.province_id ? 'Cari Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}
            </option>
            <option v-for="k in region.regencies.value" :key="k.id" :value="k.id">
              {{ k.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('regency_id')" class="font-caption !text-rose-600">
            {{ getFieldError('regency_id') }}
          </small>
        </div>

        <div>
          <FormLabel for="district_id">Kecamatan</FormLabel>
          <TomSelect :key="String(!!form.regency_id)" id="district_id" v-model="form.district_id" class="w-full"
            :disabled="isLocked">
            <option value="">
              {{ form.regency_id ? 'Cari Kecamatan' : '-- Pilih Kabupaten/Kota dulu --' }}
            </option>
            <option v-for="d in region.districts.value" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </TomSelect>
        </div>

        <div>
          <FormLabel for="village_id">Kelurahan/Desa</FormLabel>
          <TomSelect :key="String(!!form.district_id)" id="village_id" v-model="form.village_id" class="w-full"
            :disabled="isLocked">
            <option value="">
              {{ form.district_id ? 'Cari Kelurahan/Desa' : '-- Pilih Kecamatan dulu --' }}
            </option>
            <option v-for="v in region.villages.value" :key="v.id" :value="v.id">
              {{ v.name }}
            </option>
          </TomSelect>
        </div>

        <div>
          <FormLabel for="postal_code">Kode Pos</FormLabel>
          <FormInput id="postal_code" v-model="form.postal_code" placeholder="Kode Pos (opsional)"
            :disabled="isLocked" />
        </div>
      </div>
    </CardSection>

    <!-- Sidebar: Ringkasan & Aksi -->
    <template #sidebar>
      <CardSection title="Ringkasan" description="Informasi singkat customer">
        <div class="space-y-3">
          <div>
            <p class="font-label">User</p>
            <p class="font-body mt-0.5">{{ ownerName }}</p>
            <small class="font-caption">
              {{ mode === 'create' ? 'Otomatis sesuai user yang login' : 'Pemilik data customer ini' }}
            </small>
          </div>
          <div>
            <p class="font-label">Nama</p>
            <p class="font-body mt-0.5">
              {{ form.company_name || '-' }}
            </p>
          </div>
          <div>
            <p class="font-label">Jenis</p>
            <p class="font-body mt-0.5">{{ form.customer_type || '-' }}</p>
          </div>
          <div>
            <p class="font-label">Telepon</p>
            <p class="font-body mt-0.5">{{ form.phone || '-' }}</p>
          </div>
          <div v-if="form.email">
            <p class="font-label">Email</p>
            <p class="font-body mt-0.5">{{ form.email }}</p>
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <Dialog :open="showDuplicatePopup" size="lg" @close="showDuplicatePopup = false">
    <Dialog.Panel>
      <div class="p-6">
        <div class="border-b border-slate-200 pb-4">
          <h3 class="font-header">Nama Perusahaan Sudah Terdaftar</h3>
          <p class="font-caption mt-1 text-slate-500">
            {{ nameMatches.length }} customer lain memakai nama yang sama/mirip
          </p>
        </div>

        <div class="mt-4 space-y-3">
          <div v-for="match in nameMatches" :key="match.id_customer"
            class="rounded-md border border-slate-200 px-4 py-3">
            <p class="font-body font-semibold">{{ match.company_name }}</p>
            <p class="font-caption mt-0.5 text-slate-500">
              Marketing: {{ match.marketing?.name || '-' }}
            </p>
          </div>
        </div>

        <Alert variant="soft-warning" class="mt-4">
          Hubungi tim Key Account untuk verifikasi sebelum melanjutkan.
        </Alert>
      </div>

      <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
        <Button variant="outline-secondary" @click="showDuplicatePopup = false">Tutup</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
