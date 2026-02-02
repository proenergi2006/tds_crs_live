<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-5 box">
          <h2 class="text-lg font-medium mb-4">Edit Customer</h2>

          <!-- Pesan error -->
          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <div class="space-y-4">
            <!-- 1. Pilih User -->
            <div>
              <FormLabel for="user">User</FormLabel>
              <FormSelect id="user" v-model="form.id_user">
                <option disabled value="">-- Pilih User --</option>
                <option
                  v-for="u in users"
                  :key="u.id"
                  :value="u.id"
                >
                  {{ u.name }}
                </option>
              </FormSelect>
            </div>

            <!-- 2. Email -->
            <div>
              <FormLabel for="email">Email</FormLabel>
              <FormInput
                id="email"
                v-model="form.email"
                placeholder="Email"
              />
            </div>

            <!-- 3. Pilih Provinsi -->
            <div>
              <FormLabel for="provinsi">Provinsi</FormLabel>
              <FormSelect id="provinsi" v-model="form.id_provinsi">
                <option disabled value="">-- Pilih Provinsi --</option>
                <option
                  v-for="p in provinsis"
                  :key="p.id_provinsi"
                  :value="p.id_provinsi"
                >
                  {{ p.nama_provinsi }}
                </option>
              </FormSelect>
            </div>

            <!-- 4. Pilih Kabupaten (filtered) -->
            <div>
              <FormLabel for="kabupaten">Kabupaten</FormLabel>
              <FormSelect id="kabupaten" v-model="form.id_kabupaten">
                <option disabled value="">-- Pilih Kabupaten --</option>
                <option
                  v-for="k in kabupatens"
                  :key="k.id_kabupaten"
                  :value="k.id_kabupaten"
                >
                  {{ k.nama_kabupaten }}
                </option>
              </FormSelect>
            </div>

            <!-- 5. Telepon -->
            <div>
              <FormLabel for="telepon">Telepon</FormLabel>
              <FormInput
                id="telepon"
                v-model="form.telepon"
                type="text"
                placeholder="Telepon"
              />
            </div>

            <!-- 6. Jenis Customer -->
            <div>
              <FormLabel for="jenis_customer">Jenis Customer</FormLabel>
              <FormSelect id="jenis_customer" v-model="form.jenis_customer">
                <option disabled value="">-- Pilih Jenis --</option>
                <option value="Retail">Retail</option>
                <option value="Project">Project</option>
              </FormSelect>
            </div>

            <!-- 7. Nama Perusahaan -->
            <div>
              <FormLabel for="nama_perusahaan">Nama Perusahaan</FormLabel>
              <FormInput
                id="nama_perusahaan"
                v-model="form.nama_perusahaan"
                placeholder="Nama Perusahaan (opsional)"
                class="uppercase"
              />
            </div>


            <!-- 8. Alamat Perusahaan -->
            <div>
              <FormLabel for="alamat_perusahaan">Alamat Perusahaan</FormLabel>
              <textarea
                id="alamat_perusahaan"
                v-model="form.alamat_perusahaan"
                rows="3"
                class="w-full border rounded px-3 py-2"
              ></textarea>
            </div>

            <!-- 9. Fax & Kode Pos -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <FormLabel for="fax">Fax</FormLabel>
                <FormInput
                  id="fax"
                  v-model="form.fax"
                  placeholder="Fax (opsional)"
                />
              </div>
              <div>
                <FormLabel for="postal_code">Kode Pos</FormLabel>
                <FormInput
                  id="postal_code"
                  v-model="form.postal_code"
                  placeholder="Kode Pos"
                />
              </div>
            </div>
          </div>

          <!-- Tombol Batal & Update -->
          <div class="mt-6 flex justify-end space-x-2">
            <Button variant="outline-secondary" @click="cancel">Batal</Button>
            <Button variant="primary" :loading="loading" @click="submit">
              Update
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'

// router, route & auth
const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

// ambil ID dari params
const id = Number(route.params.id)

// form reactive
const form = reactive({
  id_user:           '' as number|string,
  email:             '',
  id_provinsi:       '' as number|string,
  id_kabupaten:      '' as number|string,
  telepon:           '',
  jenis_customer:    '',
  nama_perusahaan:   '',
  alamat_perusahaan: '',
  fax:               '',
  postal_code:       '',
  is_active:         true,
  lastupdate_by:     auth.user?.name || ''
})

// data dropdown
const users      = ref<any[]>([])
const provinsis  = ref<any[]>([])
const kabupatens = ref<any[]>([])

// state
const loading = ref(false)
const error   = ref('')

// helper-fetch untuk dropdown
async function fetchUsers() {
  const res = await axios.get('/api/users', { params: { per_page: 100 } })
  users.value = res.data.data || res.data
}
async function fetchProvinsis() {
  const res = await axios.get('/api/provinsis', { params: { per_page: 100 } })
  provinsis.value = res.data.data || res.data
}

// ketika provinsi berubah, refetch kabupaten
watch(() => form.id_provinsi, async provId => {
  form.id_kabupaten = ''
  if (!provId) {
    kabupatens.value = []
    return
  }
  const res = await axios.get('/api/kabupatens', {
    params: { id_provinsi: provId, per_page: 100 }
  })
  kabupatens.value = res.data.data
})

// load initial data
onMounted(async () => {
  // 1) ambil dropdown
  await Promise.all([ fetchUsers(), fetchProvinsis() ])

  // 2) ambil data customer
  try {
    const { data } = await axios.get(`/api/customers/${id}`)
    // assign ke form
    Object.assign(form, {
      id_user:           data.id_user,
      email:             data.email,
      id_provinsi:       data.id_provinsi,
      telepon:           data.telepon,
      jenis_customer:    data.jenis_customer,
      nama_perusahaan:   data.nama_perusahaan,
      alamat_perusahaan: data.alamat_perusahaan,
      fax:               data.fax,
      postal_code:       data.postal_code,
      is_active:         data.is_active,
      // lastupdate_by sudah di-set dari auth
    })
    // 3) fetch kabupatens sesuai provinsi awal
    form.id_kabupaten = data.id_kabupaten
    const res2 = await axios.get('/api/kabupatens', {
      params: { id_provinsi: data.id_provinsi, per_page: 100 }
    })
    kabupatens.value = res2.data.data
  } catch (e:any) {
    Swal.fire('Error','Gagal memuat data customer','error')
  }
})

// submit update
async function submit() {
  error.value = ''
  // validasi singkat seperti Create
  if (!form.id_user)           return Swal.fire('Error','User wajib dipilih','error')
  if (!form.email.trim())      return Swal.fire('Error','Email wajib diisi','error')
  if (!form.id_provinsi)       return Swal.fire('Error','Provinsi wajib dipilih','error')
  if (!form.id_kabupaten)      return Swal.fire('Error','Kabupaten wajib dipilih','error')
  if (!form.telepon.trim())    return Swal.fire('Error','Telepon wajib diisi','error')
  if (!form.jenis_customer)    return Swal.fire('Error','Jenis customer wajib dipilih','error')

  loading.value = true
  try {
    const payload = {
      ...form,
      id_user:      Number(form.id_user),
      id_provinsi:  Number(form.id_provinsi),
      id_kabupaten: Number(form.id_kabupaten),
    }
    await axios.put(`/api/customers/${id}`, payload)
    Swal.fire({
      icon: 'success',
      title: 'Customer berhasil diperbarui',
      toast: true, position: 'top-end', timer: 1500
    })
    router.push({ name: 'customers-list' })
  } catch (e:any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

// batal → kembali
function cancel() {
  router.back()
}
</script>
