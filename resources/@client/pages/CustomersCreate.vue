<!-- src/pages/CustomerCreate.vue -->
<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-5 box">
          <!-- Header -->
          <div class="flex items-center mb-4">
            <h2 class="text-lg font-medium">Tambah Customer</h2>
          </div>

          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <form @submit.prevent="submit" class="space-y-4">
            <!-- 1. User (readonly) -->
            <div>
              <FormLabel for="user">User</FormLabel>
              <FormSelect
                id="user"
                ref="userRef"
                v-model="form.id_user"
                :disabled="true"
                class="bg-slate-100 pointer-events-none"
              >
                <option disabled value="">-- Pilih User --</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
              </FormSelect>
              <small class="text-slate-500">Otomatis sesuai user yang login</small>
            </div>

            <div>
              <FormLabel for="nama_perusahaan">Nama Perusahaan *</FormLabel>
              <FormInput
  id="nama_perusahaan"
  v-model="form.nama_perusahaan"
  placeholder="Nama Perusahaan"
  class="uppercase"
  :class="{'border-rose-500': showErrors && !form.nama_perusahaan.trim()}"
/>

            </div>

            <!-- 2. Email -->
            <div>
              <FormLabel for="email">Email </FormLabel>
              <FormInput
                id="email"
                ref="emailRef"
                v-model="form.email"
                placeholder="Email"
              
                autocomplete="off"
              />
            </div>

            <!-- 3. Provinsi -->
            <div>
              <FormLabel for="provinsi">Provinsi *</FormLabel>
              <FormSelect
                id="provinsi"
                ref="provinsiRef"
                v-model="form.id_provinsi"
                :class="{'border-rose-500': showErrors && !form.id_provinsi}"
              >
                <option disabled value="">-- Pilih Provinsi --</option>
                <option v-for="p in provinsis" :key="p.id_provinsi" :value="p.id_provinsi">
                  {{ p.nama_provinsi }}
                </option>
              </FormSelect>
            </div>

            <!-- 4. Kabupaten -->
            <div>
              <FormLabel for="kabupaten">Kabupaten *</FormLabel>
              <FormSelect
                id="kabupaten"
                ref="kabupatenRef"
                v-model="form.id_kabupaten"
                :class="{'border-rose-500': showErrors && !form.id_kabupaten}"
              >
                <option disabled value="">-- Pilih Kabupaten --</option>
                <option v-for="k in kabupatens" :key="k.id_kabupaten" :value="k.id_kabupaten">
                  {{ k.nama_kabupaten }}
                </option>
              </FormSelect>
            </div>

            <!-- 5. Telepon -->
            <div>
              <FormLabel for="telepon">Telepon *</FormLabel>
              <FormInput
                id="telepon"
                ref="teleponRef"
                v-model="form.telepon"
                type="text"
                placeholder="Telepon"
                :class="{'border-rose-500': showErrors && !form.telepon.trim()}"
                autocomplete="off"
              />
            </div>

            <!-- 6. Jenis Customer -->
            <div>
              <FormLabel for="jenis_customer">Jenis Customer *</FormLabel>
              <FormSelect
                id="jenis_customer"
                ref="jenisRef"
                v-model="form.jenis_customer"
                :class="{'border-rose-500': showErrors && !form.jenis_customer}"
              >
                <option disabled value="">-- Pilih Jenis --</option>
                <option value="Retail">Retail</option>
                <option value="Project">Project</option>
              </FormSelect>
            </div>

            <!-- 7. Nama Perusahaan (opsional) -->
       

            <!-- 8. Alamat Perusahaan (opsional) -->
            <div>
              <FormLabel for="alamat_perusahaan">Alamat Perusahaan</FormLabel>
              <textarea
                id="alamat_perusahaan"
                v-model="form.alamat_perusahaan"
                rows="3"
                class="w-full border rounded px-3 py-2"
              ></textarea>
            </div>

            <!-- 9. Fax & Kode Pos (opsional) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <FormLabel for="fax">Fax</FormLabel>
                <FormInput id="fax" v-model="form.fax" placeholder="Fax (opsional)" />
              </div>
              <div>
                <FormLabel for="postal_code">Kode Pos</FormLabel>
                <FormInput id="postal_code" v-model="form.postal_code" placeholder="Kode Pos" />
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end gap-2">
              <Button
                type="button"
                variant="outline-secondary"
                @click="cancel"
                class="inline-flex items-center gap-2"
                :disabled="loading"
              >
                <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
                <span>Batal</span>
              </Button>
              <Button
                type="submit"
                variant="primary"
                :loading="loading"
                class="inline-flex items-center gap-2"
              >
                <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
                <span>Simpan</span>
              </Button>
            </div>
          </form>
        </div>
      </div>
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

const router = useRouter()
const auth   = useAuthStore()

const form = reactive({
  id_user:           (auth.user?.id ?? '') as number | string,
  email:             '',
  id_provinsi:       '' as number | string,
  id_kabupaten:      '' as number | string,
  telepon:           '',
  jenis_customer:    '',
  nama_perusahaan:   '',
  alamat_perusahaan: '',
  fax:               '',
  postal_code:       '',
  is_active:         true,
  created_by:        auth.user?.name || ''
})

const users      = ref<any[]>([])
const provinsis  = ref<any[]>([])
const kabupatens = ref<any[]>([])

const loading = ref(false)
const error   = ref('')

// refs
const emailRef     = ref<HTMLInputElement|null>(null)
const provinsiRef  = ref<HTMLElement|null>(null)
const kabupatenRef = ref<HTMLElement|null>(null)
const teleponRef   = ref<HTMLInputElement|null>(null)
const jenisRef     = ref<HTMLElement|null>(null)
const userRef      = ref<HTMLElement|null>(null)
  const namaPerusahaanRef = ref<HTMLInputElement|null>(null)

// data awal
async function fetchUsers() {
  // isi hanya user yang login supaya select tetap tampil
  if (auth.user) users.value = [{ id: auth.user.id, name: auth.user.name }]
}
async function fetchProvinsis() {
  const res = await axios.get('/api/provinsis', { params: { per_page: 100 } })
  provinsis.value = res.data.data || res.data
}

watch(() => form.id_provinsi, async (newProv) => {
  form.id_kabupaten = ''
  if (!newProv) {
    kabupatens.value = []
    return
  }
  const res = await axios.get('/api/kabupatens', {
    params: { id_provinsi: newProv, per_page: 100 }
  })
  kabupatens.value = res.data.data || res.data
})

onMounted(async () => {
  await fetchUsers()
  await fetchProvinsis()
})

/* ===== VALIDASI SWEETALERT ===== */
const showErrors = ref(false)

function missingFields(): string[] {
  const m: string[] = []
  if (!form.nama_perusahaan.trim()) m.push('Nama Perusahaan')
  if (!form.id_provinsi)            m.push('Provinsi')
  if (!form.id_kabupaten)           m.push('Kabupaten')
  if (!form.telepon.trim())         m.push('Telepon')
  if (!form.jenis_customer)         m.push('Jenis Customer')
  return m
}


async function showMissingAlert(miss: string[]) {
  const list = `<ul style="text-align:left;margin:8px 0 0 14px;">${miss.map(i=>`<li>${i}</li>`).join('')}</ul>`
  await Swal.fire({
    icon: 'error',
    title: 'Lengkapi data wajib',
    html: `Field berikut tidak boleh kosong:${list}`,
    confirmButtonText: 'OK'
  })
}

async function focusFirstMissing() {
  await nextTick()
  if (!form.nama_perusahaan.trim() && namaPerusahaanRef.value) return namaPerusahaanRef.value.focus()
  if (!form.id_provinsi && provinsiRef.value)                  return (provinsiRef.value as HTMLSelectElement).focus()
  if (!form.id_kabupaten && kabupatenRef.value)                return (kabupatenRef.value as HTMLSelectElement).focus()
  if (!form.telepon.trim() && teleponRef.value)                return teleponRef.value.focus()
  if (!form.jenis_customer && jenisRef.value)                  return (jenisRef.value as HTMLSelectElement).focus()
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
      id_user:      Number(form.id_user),      // tetap kirim, tapi server akan override
      id_provinsi:  Number(form.id_provinsi),
      id_kabupaten: Number(form.id_kabupaten),
    }
    await axios.post('/api/customers', payload)
    Swal.fire({
      icon: 'success',
      title: 'Customer berhasil dibuat',
      toast: true,
      position: 'top-end',
      timer: 1500,
      showConfirmButton: false
    })
    router.push({ name: 'customers-list' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

function cancel() { router.back() }
</script>

<style scoped>
/* highlight merah saat invalid */
.border-rose-500 { box-shadow: 0 0 0 1px rgba(244, 63, 94, .6) inset; }
.pointer-events-none { pointer-events: none; }
</style>
