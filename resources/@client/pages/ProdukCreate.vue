<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import Lucide from '@/components/Base/Lucide'

import Button from '@/components/Base/Button'
import { FormInput, FormSelect, FormLabel, FormSwitch } from '@/components/Base/Form'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const currentUser = ref('')

const form = reactive({
  nama_produk: '',
  merk_dagang: '',
  deskripsi: '',
  id_ukuran: '',
  id_jenis: '',
  is_active: true,
  created_by: ''
})

const satuans = ref<any[]>([])
const sizes = ref<any[]>([])
const jenisList = ref<any[]>([])

async function fetchSatuans() {
  try {
    const { data } = await axios.get('/api/satuans', { params: { per_page: 100 } })
    satuans.value = data.data || data
  } catch {}

  try {
    const res = await axios.get('/api/ukurans', { params: { per_page: 100 } })
    sizes.value = res.data.data
  } catch {}
}

async function fetchJenisProduks() {
  try {
    const res = await axios.get('/api/jenis-produks', { params: { per_page: 100 } })
    jenisList.value = res.data.data
  } catch {}
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/user')
    currentUser.value = data.name
    form.created_by = data.name
  } catch (e) {
    console.error(e)
  }
  await fetchSatuans()
  await fetchJenisProduks()
})

async function submit() {
  error.value = ''
  if (!form.nama_produk.trim()) return Swal.fire('Error', 'Nama Produk wajib diisi', 'error')
  if (!form.merk_dagang) return Swal.fire('Error', 'Merk Dagang wajib dipilih', 'error')
  if (!form.id_ukuran) return Swal.fire('Error', 'Ukuran wajib dipilih', 'error')
  if (!form.id_jenis) return Swal.fire('Error', 'Jenis Produk wajib dipilih', 'error')

  loading.value = true
  try {
    await axios.post('/api/produks', form)
    Swal.fire({ icon: 'success', title: 'Produk ditambahkan', toast: true, position: 'top-end', timer: 2000 })
    router.push({ name: 'produks-list' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.back()
}
</script>

<template>
  <div class="py-2 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-5 box">
          <h2 class="text-lg font-medium mb-4">Tambah Produk</h2>

          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <div class="space-y-4">
            <div>
              <FormLabel htmlFor="nama">Nama Produk *</FormLabel>
              <FormInput id="nama" v-model="form.nama_produk" placeholder="Masukkan nama produk" />
            </div>

            <div>
              <FormLabel htmlFor="merk">Merek Dagang *</FormLabel>
              <FormInput id="merk" v-model="form.merk_dagang" placeholder="Masukkan merek dagang " />
            </div>

            <div>
              <FormLabel htmlFor="desc">Deskripsi</FormLabel>
              <FormInput id="desc" v-model="form.deskripsi" placeholder="Deskripsi produk (opsional)" />
            </div>

            <div>
              <FormLabel htmlFor="ukuran">Ukuran *</FormLabel>
              <FormSelect id="ukuran" v-model="form.id_ukuran">
                <option disabled value="">-- Pilih Ukuran --</option>
                <option v-for="u in sizes" :key="u.id_ukuran" :value="u.id_ukuran">
                  {{ u.nama_ukuran }} ({{ u.satuan?.nama_satuan || '-' }}) 
                </option>
              </FormSelect>
            </div>

            <div>
              <FormLabel htmlFor="jenis">Jenis Produk *</FormLabel>
              <FormSelect id="jenis" v-model="form.id_jenis">
                <option disabled value="">-- Pilih Jenis Produk --</option>
                <option v-for="j in jenisList" :key="j.id_jenis" :value="j.id_jenis">
                  {{ j.nama }} 
                </option>
              </FormSelect>
            </div>

            <div class="flex items-center space-x-2">
              <FormSwitch>
                <FormSwitch.Input type="checkbox" v-model="form.is_active" />
              </FormSwitch>
              <span>Status Active</span>
            </div>

            <div>
              <FormLabel htmlFor="creator">Created By</FormLabel>
              <FormInput id="creator" v-model="form.created_by" readonly class="bg-gray-100 cursor-not-allowed" />
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-2">
  <Button variant="outline-secondary" @click="cancel" class="inline-flex items-center gap-2">
    <Lucide v-if="!loading" icon="X" class="w-4 h-4" aria-hidden="true" />
    <span>Batal</span>
  </Button>

  <Button variant="primary" :loading="loading" @click="submit" class="inline-flex items-center gap-2">
    <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
    <span>Simpan</span>
  </Button>
</div>
        </div>
      </div>
    </div>
  </div>
</template>
