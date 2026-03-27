<template>
  <div class="min-h-screen px-6 py-6 bg-slate-100">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="mb-6 intro-y">
        <h1 class="text-2xl font-semibold text-slate-800">
          Tambah Customer Proenergi
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Lengkapi informasi customer untuk proses penawaran, invoice, dan operasional.
        </p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="px-4 py-3 mb-6 text-sm border rounded-lg bg-danger/10 border-danger/20 text-danger"
      >
        {{ error }}
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Section: Informasi Dasar -->
        <div class="p-6 bg-white border shadow-sm rounded-xl intro-y">
          <div class="pb-4 mb-6 border-b">
            <h2 class="text-lg font-semibold text-slate-800">Informasi Dasar</h2>
            <p class="mt-1 text-sm text-slate-500">
              Data utama customer yang akan digunakan dalam sistem.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <!-- User -->
            <div class="md:col-span-2">
              <FormLabel for="user" class="font-medium text-slate-700">
                User
              </FormLabel>
              <FormSelect
                id="user"
                ref="userRef"
                v-model="form.id_user"
                disabled
                class="bg-slate-100 pointer-events-none"
              >
                <option disabled value="">-- Pilih User --</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
              </FormSelect>
              <p class="mt-1 text-xs text-slate-500">
                Otomatis sesuai user yang login.
              </p>
            </div>

            <!-- Nama Perusahaan -->
            <div>
              <FormLabel for="nama_perusahaan" class="font-medium text-slate-700">
                Nama Perusahaan <span class="text-rose-500">*</span>
              </FormLabel>
              <FormInput
                id="nama_perusahaan"
                ref="namaPerusahaanRef"
                v-model="form.nama_perusahaan"
                placeholder="Nama Perusahaan"
                class="uppercase"
                :class="{ 'border-rose-500': showErrors && !form.nama_perusahaan.trim() }"
              />
              <p
                v-if="showErrors && !form.nama_perusahaan.trim()"
                class="mt-1 text-xs text-rose-500"
              >
                Nama perusahaan wajib diisi.
              </p>
            </div>

            <!-- Email -->
            <div>
              <FormLabel for="email" class="font-medium text-slate-700">
                Email
              </FormLabel>
              <FormInput
                id="email"
                ref="emailRef"
                v-model="form.email"
                type="email"
                placeholder="Email"
                autocomplete="off"
              />
            </div>

            <!-- Jenis Customer -->
            <div>
              <FormLabel for="jenis_customer" class="font-medium text-slate-700">
                Jenis Customer <span class="text-rose-500">*</span>
              </FormLabel>
              <FormSelect
                id="jenis_customer"
                ref="jenisRef"
                v-model="form.jenis_customer"
                :class="{ 'border-rose-500': showErrors && !form.jenis_customer }"
              >
                <option disabled value="">-- Pilih Jenis --</option>
                <option value="Retail">Retail</option>
                <option value="Project">Project</option>
              </FormSelect>
              <p
                v-if="showErrors && !form.jenis_customer"
                class="mt-1 text-xs text-rose-500"
              >
                Jenis customer wajib dipilih.
              </p>
            </div>

            <!-- Telepon -->
            <div>
              <FormLabel for="telepon" class="font-medium text-slate-700">
                Telepon <span class="text-rose-500">*</span>
              </FormLabel>
              <FormInput
                id="telepon"
                ref="teleponRef"
                v-model="form.telepon"
                type="text"
                placeholder="Telepon"
                autocomplete="off"
                :class="{ 'border-rose-500': showErrors && !form.telepon.trim() }"
              />
              <p
                v-if="showErrors && !form.telepon.trim()"
                class="mt-1 text-xs text-rose-500"
              >
                Telepon wajib diisi.
              </p>
            </div>
          </div>
        </div>

        <!-- Section: Lokasi -->
        <div class="p-6 bg-white border shadow-sm rounded-xl intro-y">
          <div class="pb-4 mb-6 border-b">
            <h2 class="text-lg font-semibold text-slate-800">Lokasi & Wilayah</h2>
            <p class="mt-1 text-sm text-slate-500">
              Tentukan area customer berdasarkan provinsi dan kabupaten.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <!-- Provinsi -->
            <div>
              <FormLabel for="provinsi" class="font-medium text-slate-700">
                Provinsi <span class="text-rose-500">*</span>
              </FormLabel>
              <FormSelect
                id="provinsi"
                ref="provinsiRef"
                v-model="form.id_provinsi"
                :class="{ 'border-rose-500': showErrors && !form.id_provinsi }"
              >
                <option disabled value="">-- Pilih Provinsi --</option>
                <option
                  v-for="p in provinsis"
                  :key="p.id_provinsi"
                  :value="p.id_provinsi"
                >
                  {{ p.nama_provinsi }}
                </option>
              </FormSelect>
              <p
                v-if="showErrors && !form.id_provinsi"
                class="mt-1 text-xs text-rose-500"
              >
                Provinsi wajib dipilih.
              </p>
            </div>

            <!-- Kabupaten -->
            <div>
              <FormLabel for="kabupaten" class="font-medium text-slate-700">
                Kabupaten <span class="text-rose-500">*</span>
              </FormLabel>
              <FormSelect
                id="kabupaten"
                ref="kabupatenRef"
                v-model="form.id_kabupaten"
                :class="{ 'border-rose-500': showErrors && !form.id_kabupaten }"
              >
                <option disabled value="">-- Pilih Kabupaten --</option>
                <option
                  v-for="k in kabupatens"
                  :key="k.id_kabupaten"
                  :value="k.id_kabupaten"
                >
                  {{ k.nama_kabupaten }}
                </option>
              </FormSelect>
              <p
                v-if="showErrors && !form.id_kabupaten"
                class="mt-1 text-xs text-rose-500"
              >
                Kabupaten wajib dipilih.
              </p>
            </div>
          </div>
        </div>

        <!-- Section: Alamat & Detail -->
        <div class="p-6 bg-white border shadow-sm rounded-xl intro-y">
          <div class="pb-4 mb-6 border-b">
            <h2 class="text-lg font-semibold text-slate-800">Alamat & Detail Tambahan</h2>
            <p class="mt-1 text-sm text-slate-500">
              Informasi pelengkap customer untuk kebutuhan administrasi.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <!-- Alamat -->
            <div class="md:col-span-2">
              <FormLabel for="alamat_perusahaan" class="font-medium text-slate-700">
                Alamat Perusahaan
              </FormLabel>
              <textarea
                id="alamat_perusahaan"
                v-model="form.alamat_perusahaan"
                rows="4"
                class="w-full px-3 py-2 transition border rounded-lg resize-none border-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                placeholder="Alamat perusahaan"
              ></textarea>
            </div>

            <!-- Fax -->
            <div>
              <FormLabel for="fax" class="font-medium text-slate-700">
                Fax
              </FormLabel>
              <FormInput
                id="fax"
                v-model="form.fax"
                placeholder="Fax (opsional)"
              />
            </div>

            <!-- Kode Pos -->
            <div>
              <FormLabel for="postal_code" class="font-medium text-slate-700">
                Kode Pos
              </FormLabel>
              <FormInput
                id="postal_code"
                v-model="form.postal_code"
                placeholder="Kode Pos"
              />
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="flex flex-col gap-3 p-4 bg-white border shadow-sm rounded-xl md:flex-row md:justify-end intro-y">
          <Button
            type="button"
            variant="outline-secondary"
            @click="cancel"
            class="inline-flex items-center justify-center gap-2"
            :disabled="loading"
          >
            <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
            <span>Batal</span>
          </Button>

          <Button
            type="submit"
            variant="primary"
            :loading="loading"
            class="inline-flex items-center justify-center gap-2"
          >
            <Lucide
              v-if="!loading"
              icon="Save"
              class="w-4 h-4"
              aria-hidden="true"
            />
            <span>{{ loading ? 'Menyimpan...' : 'Simpan' }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

type UserItem = {
  id: number
  name: string
}

type ProvinsiItem = {
  id_provinsi: number
  nama_provinsi: string
}

type KabupatenItem = {
  id_kabupaten: number
  nama_kabupaten: string
}

const router = useRouter()
const auth = useAuthStore()

const form = reactive({
  id_user: (auth.user?.id ?? '') as number | string,
  email: '',
  id_provinsi: '' as number | string,
  id_kabupaten: '' as number | string,
  telepon: '',
  jenis_customer: '',
  nama_perusahaan: '',
  alamat_perusahaan: '',
  fax: '',
  postal_code: '',
  is_active: true,
  created_by: auth.user?.name || '',
})

const users = ref<UserItem[]>([])
const provinsis = ref<ProvinsiItem[]>([])
const kabupatens = ref<KabupatenItem[]>([])

const loading = ref(false)
const error = ref('')
const showErrors = ref(false)

// refs
const emailRef = ref<HTMLInputElement | null>(null)
const provinsiRef = ref<HTMLElement | null>(null)
const kabupatenRef = ref<HTMLElement | null>(null)
const teleponRef = ref<HTMLInputElement | null>(null)
const jenisRef = ref<HTMLElement | null>(null)
const userRef = ref<HTMLElement | null>(null)
const namaPerusahaanRef = ref<HTMLInputElement | null>(null)

async function fetchUsers() {
  if (auth.user) {
    users.value = [{ id: auth.user.id, name: auth.user.name }]
  }
}

async function fetchProvinsis() {
  const res = await axios.get('/api/provinsis', {
    params: { per_page: 100 },
  })
  provinsis.value = res.data.data || res.data
}

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
        params: {
          id_provinsi: newProv,
          per_page: 100,
        },
      })
      kabupatens.value = res.data.data || res.data
    } catch {
      kabupatens.value = []
    }
  }
)

onMounted(async () => {
  await fetchUsers()
  await fetchProvinsis()
})

function missingFields(): string[] {
  const m: string[] = []

  if (!form.nama_perusahaan.trim()) m.push('Nama Perusahaan')
  if (!form.id_provinsi) m.push('Provinsi')
  if (!form.id_kabupaten) m.push('Kabupaten')
  if (!form.telepon.trim()) m.push('Telepon')
  if (!form.jenis_customer) m.push('Jenis Customer')

  return m
}

async function showMissingAlert(miss: string[]) {
  const list = `<ul style="text-align:left;margin:8px 0 0 14px;">${miss
    .map((i) => `<li>${i}</li>`)
    .join('')}</ul>`

  await Swal.fire({
    icon: 'error',
    title: 'Lengkapi data wajib',
    html: `Field berikut tidak boleh kosong:${list}`,
    confirmButtonText: 'OK',
  })
}

async function focusFirstMissing() {
  await nextTick()

  if (!form.nama_perusahaan.trim() && namaPerusahaanRef.value) {
    return namaPerusahaanRef.value.focus()
  }

  if (!form.id_provinsi && provinsiRef.value) {
    return (provinsiRef.value as HTMLSelectElement).focus()
  }

  if (!form.id_kabupaten && kabupatenRef.value) {
    return (kabupatenRef.value as HTMLSelectElement).focus()
  }

  if (!form.telepon.trim() && teleponRef.value) {
    return teleponRef.value.focus()
  }

  if (!form.jenis_customer && jenisRef.value) {
    return (jenisRef.value as HTMLSelectElement).focus()
  }
}

async function submit() {
  error.value = ''
  showErrors.value = true

  const miss = missingFields()
  if (miss.length) {
    await showMissingAlert(miss)
    await focusFirstMissing()
    return
  }

  loading.value = true

  try {
    const payload = {
      ...form,
      id_user: Number(form.id_user),
      id_provinsi: Number(form.id_provinsi),
      id_kabupaten: Number(form.id_kabupaten),
    }

    await axios.post('/api/customers', payload)

    await Swal.fire({
      icon: 'success',
      title: 'Customer berhasil dibuat',
      toast: true,
      position: 'top-end',
      timer: 1500,
      showConfirmButton: false,
    })

    router.push({ name: 'customers-list-proenergi' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan data customer.'
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.back()
}
</script>

<style scoped>
.border-rose-500 {
  box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.6) inset;
}

.pointer-events-none {
  pointer-events: none;
}
</style>