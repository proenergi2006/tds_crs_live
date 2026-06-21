<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
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

/* State: lookups */
const provinsis = ref<any[]>([])
const kabupatens = ref<any[]>([])

const form = reactive({
  email: '',
  id_provinsi: '',
  id_kabupaten: '',
  telepon: '',
  jenis_customer: '',
  nama_perusahaan: '',
  alamat_perusahaan: '',
  fax: '',
  postal_code: '',
})

const rules = {
  nama_perusahaan: {
    required: helpers.withMessage('Nama perusahaan wajib diisi', required),
  },
  id_provinsi: {
    required: helpers.withMessage('Provinsi wajib dipilih', required),
  },
  id_kabupaten: {
    required: helpers.withMessage('Kabupaten wajib dipilih', required),
  },
  telepon: {
    required: helpers.withMessage('Telepon wajib diisi', required),
  },
  jenis_customer: {
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

watch(
  () => form.id_provinsi,
  async (newProv) => {
    form.id_kabupaten = ''
    if (!newProv) {
      kabupatens.value = []
      return
    }
    try {
      const res = await axios.get('/api/kabupatens', {
        params: { id_provinsi: newProv, per_page: 500 },
      })
      kabupatens.value = res.data.data || res.data
    } catch {
      kabupatens.value = []
    }
  }
)

onMounted(async () => {
  await fetchProvinsis()
  if (mode.value === 'edit') {
    await fetchCustomer()
  }
})

async function fetchProvinsis() {
  try {
    const res = await axios.get('/api/provinsis', { params: { per_page: 100 } })
    provinsis.value = res.data.data || res.data
  } catch {
    provinsis.value = []
  }
}

async function fetchCustomer() {
  pageLoading.value = true
  try {
    const { data } = await customerApi.getById(customerId.value)
    Object.assign(form, {
      email: data.email || '',
      id_provinsi: data.id_provinsi ? String(data.id_provinsi) : '',
      telepon: data.telepon || '',
      jenis_customer: data.jenis_customer || '',
      nama_perusahaan: data.nama_perusahaan || '',
      alamat_perusahaan: data.alamat_perusahaan || '',
      fax: data.fax || '',
      postal_code: data.postal_code || '',
    })
    if (data.id_provinsi) {
      const res = await axios.get('/api/kabupatens', {
        params: { id_provinsi: data.id_provinsi, per_page: 500 },
      })
      kabupatens.value = res.data.data || res.data
      form.id_kabupaten = data.id_kabupaten ? String(data.id_kabupaten) : ''
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer')
    router.push({ name: indexRoute.value })
  } finally {
    pageLoading.value = false
  }
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
    const payload = {
      ...form,
      nama_perusahaan: form.nama_perusahaan.trim().toUpperCase(),
      id_provinsi: Number(form.id_provinsi),
      id_kabupaten: Number(form.id_kabupaten),
    }

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
    submit-icon="Save" cancel-icon="ArrowLeft" @cancel="cancel" @submit="submit">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <!-- Section: Informasi Dasar -->
    <CardSection title="Informasi Dasar" description="Data utama customer dalam sistem">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel>User</FormLabel>
          <FormInput :value="auth.user?.name || '-'" disabled class="bg-slate-50" />
          <small class="text-slate-500">Otomatis sesuai user yang login</small>
        </div>

        <div class="md:col-span-2">
          <FormLabel for="nama_perusahaan">
            Nama Perusahaan
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="nama_perusahaan" v-model="form.nama_perusahaan" placeholder="Nama Perusahaan" class="uppercase"
            :class="getFieldError('nama_perusahaan') ? 'border-rose-500' : ''" @blur="v$.nama_perusahaan.$touch()" />
          <small v-if="getFieldError('nama_perusahaan')" class="text-rose-600">
            {{ getFieldError('nama_perusahaan') }}
          </small>
        </div>

        <div>
          <FormLabel for="jenis_customer">
            Jenis Customer
            <RequiredAsterisk />
          </FormLabel>
          <FormSelect id="jenis_customer" v-model="form.jenis_customer"
            :class="getFieldError('jenis_customer') ? 'border-rose-500' : ''" @blur="v$.jenis_customer.$touch()">
            <option value="">-- Pilih Jenis --</option>
            <option value="Retail">Retail</option>
            <option value="Project">Project</option>
          </FormSelect>
          <small v-if="getFieldError('jenis_customer')" class="text-rose-600">
            {{ getFieldError('jenis_customer') }}
          </small>
        </div>

        <div>
          <FormLabel for="telepon">
            Telepon
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="telepon" v-model="form.telepon" placeholder="Telepon"
            :class="getFieldError('telepon') ? 'border-rose-500' : ''" @blur="v$.telepon.$touch()" />
          <small v-if="getFieldError('telepon')" class="text-rose-600">
            {{ getFieldError('telepon') }}
          </small>
        </div>

        <div>
          <FormLabel for="email">Email</FormLabel>
          <FormInput id="email" v-model="form.email" type="email" placeholder="Email (opsional)" autocomplete="off" />
        </div>
      </div>
    </CardSection>

    <!-- Section: Lokasi & Wilayah -->
    <CardSection title="Lokasi & Wilayah" description="Area customer berdasarkan provinsi dan kabupaten">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <FormLabel for="id_provinsi">
            Provinsi
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            id="id_provinsi"
            v-model="form.id_provinsi"
            class="w-full"
            :class="getFieldError('id_provinsi') ? 'border-rose-500' : ''"
            @change="v$.id_provinsi.$touch()"
          >
            <option value="">-- Pilih Provinsi --</option>
            <option v-for="p in provinsis" :key="p.id_provinsi" :value="String(p.id_provinsi)">
              {{ p.nama_provinsi }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('id_provinsi')" class="text-rose-600">
            {{ getFieldError('id_provinsi') }}
          </small>
        </div>

        <div>
          <FormLabel for="id_kabupaten">
            Kabupaten
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            id="id_kabupaten"
            v-model="form.id_kabupaten"
            class="w-full"
            :class="getFieldError('id_kabupaten') ? 'border-rose-500' : ''"
            @change="v$.id_kabupaten.$touch()"
          >
            <option value="">
              {{ form.id_provinsi ? '-- Pilih Kabupaten --' : '-- Pilih Provinsi dulu --' }}
            </option>
            <option v-for="k in kabupatens" :key="k.id_kabupaten" :value="String(k.id_kabupaten)">
              {{ k.nama_kabupaten }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('id_kabupaten')" class="text-rose-600">
            {{ getFieldError('id_kabupaten') }}
          </small>
        </div>
      </div>
    </CardSection>

    <!-- Section: Alamat & Detail -->
    <CardSection title="Alamat & Detail" description="Informasi pelengkap untuk keperluan administrasi">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel for="alamat_perusahaan">Alamat Perusahaan</FormLabel>
          <FormTextarea id="alamat_perusahaan" v-model="form.alamat_perusahaan"
            placeholder="Alamat perusahaan (opsional)" :rows="3" />
        </div>

        <div>
          <FormLabel for="fax">Fax</FormLabel>
          <FormInput id="fax" v-model="form.fax" placeholder="Fax (opsional)" />
        </div>

        <div>
          <FormLabel for="postal_code">Kode Pos</FormLabel>
          <FormInput id="postal_code" v-model="form.postal_code" placeholder="Kode Pos (opsional)" />
        </div>
      </div>
    </CardSection>

    <!-- Sidebar: Ringkasan & Aksi -->
    <template #sidebar>
      <CardSection title="Ringkasan" description="Informasi singkat customer">
        <div class="space-y-3">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Nama</p>
            <p class="mt-0.5 font-medium text-slate-800">
              {{ form.nama_perusahaan || '-' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Jenis</p>
            <p class="mt-0.5 text-slate-700">{{ form.jenis_customer || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Telepon</p>
            <p class="mt-0.5 text-slate-700">{{ form.telepon || '-' }}</p>
          </div>
          <div v-if="form.email">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</p>
            <p class="mt-0.5 text-slate-700">{{ form.email }}</p>
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
