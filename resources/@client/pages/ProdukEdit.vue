<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import Lucide from '@/components/Base/Lucide'

import Button from '@/components/Base/Button'
import { FormInput, FormSelect, FormLabel, FormSwitch } from '@/components/Base/Form'

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const form = reactive({
  id_produk: id,
  nama_produk: '',
  merk_dagang: '',
  deskripsi: '',
  id_ukuran: '',
  id_jenis: '',
  is_active: true,
  lastupdate_by: ''
})

const sizes = ref<any[]>([])
const jenisList = ref<any[]>([])
const error = ref('')
const loading = ref(false)

// state untuk highlight invalid
const invalid = reactive<Record<string, boolean>>({
  nama: false,
  merk: false,
  ukuran: false,
  jenis: false,
})

async function fetchSizes() {
  try {
    const { data } = await axios.get('/api/ukurans', { params: { per_page: 100 } })
    sizes.value = data.data
  } catch { /* ignore */ }
}

async function fetchJenisProduks() {
  try {
    const res = await axios.get('/api/jenis-produks', { params: { per_page: 100 } })
    jenisList.value = res.data.data
  } catch { /* ignore */ }
}

onMounted(async () => {
  await fetchSizes()
  await fetchJenisProduks()

  try {
    const { data: u } = await axios.get('/api/user')
    form.lastupdate_by = u.name
  } catch { /* ignore */ }

  try {
    const { data: p } = await axios.get(`/api/produks/${id}`)
    form.nama_produk = p.nama_produk ?? ''
    form.merk_dagang = p.merk_dagang ?? ''
    form.deskripsi = p.deskripsi ?? ''
    form.id_ukuran = p.id_ukuran ?? ''
    form.id_jenis = p.id_jenis ?? ''
    form.is_active = !!p.is_active
  } catch (e: any) {
    await Swal.fire('Error', 'Gagal memuat data produk', 'error')
    router.back()
  }
})

function validateForm(): Array<{ id: 'nama'|'merk'|'ukuran'|'jenis'; label: string }> {
  const missing: Array<{ id: 'nama'|'merk'|'ukuran'|'jenis'; label: string }> = []

  invalid.nama = !(form.nama_produk || '').trim()
  invalid.merk = !(form.merk_dagang || '').trim()
  invalid.ukuran = !form.id_ukuran
  invalid.jenis = !form.id_jenis

  if (invalid.nama)   missing.push({ id: 'nama',  label: 'Nama Produk' })
  if (invalid.merk)   missing.push({ id: 'merk',  label: 'Merek Dagang' })
  if (invalid.ukuran) missing.push({ id: 'ukuran', label: 'Ukuran' })
  if (invalid.jenis)  missing.push({ id: 'jenis',  label: 'Jenis Produk' })

  return missing
}

async function submit() {
  error.value = ''

  const missing = validateForm()
  if (missing.length) {
    const htmlList = `<ul style="text-align:left; margin:0; padding-left:18px">
      ${missing.map(m => `<li><b>${m.label}</b> wajib diisi</li>`).join('')}
    </ul>`

    await Swal.fire({
      icon: 'error',
      title: 'Data tidak boleh kosong',
      html: htmlList,
      confirmButtonText: 'OK',
    })

    const first = missing[0]?.id
    if (first) document.getElementById(first)?.focus()
    return
  }

  loading.value = true
  try {
    await axios.put(`/api/produks/${id}`, form)
    await Swal.fire({ icon: 'success', title: 'Produk diperbarui', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false })
    router.push({ name: 'produks-list' })
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Gagal mengupdate produk'
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.back()
}
</script>

<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6">
      <div class="col-span-12">
        <div class="p-5 box">
          <h2 class="text-lg font-medium mb-4">Edit Produk</h2>
          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <div class="space-y-4">
            <div>
              <FormLabel htmlFor="nama">Nama Produk *</FormLabel>
              <FormInput
                id="nama"
                v-model="form.nama_produk"
                placeholder="Masukkan nama produk"
                :class="{'border-red-500 focus:border-red-500': invalid.nama}"
              />
            </div>

            <div>
              <FormLabel htmlFor="merk">Merek Dagang *</FormLabel>
              <FormInput
                id="merk"
                v-model="form.merk_dagang"
                placeholder="Masukkan merek dagang"
                :class="{'border-red-500 focus:border-red-500': invalid.merk}"
              />
            </div>

            <div>
              <FormLabel htmlFor="desc">Deskripsi</FormLabel>
              <FormInput
                id="desc"
                v-model="form.deskripsi"
                placeholder="Deskripsi produk (opsional)"
              />
            </div>

            <div>
              <FormLabel htmlFor="ukuran">Ukuran *</FormLabel>
              <FormSelect
                id="ukuran"
                v-model="form.id_ukuran"
                :class="{'border-red-500 focus:border-red-500': invalid.ukuran}"
              >
                <option disabled value="">-- Pilih Ukuran --</option>
                <option v-for="u in sizes" :key="u.id_ukuran" :value="u.id_ukuran">
                  {{ u.nama_ukuran }} - ({{ u.satuan?.nama_satuan || '-' }})
                </option>
              </FormSelect>
            </div>

            <div>
              <FormLabel htmlFor="jenis">Jenis Produk *</FormLabel>
              <FormSelect
                id="jenis"
                v-model="form.id_jenis"
                :class="{'border-red-500 focus:border-red-500': invalid.jenis}"
              >
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
              <FormLabel htmlFor="updater">Last Update By</FormLabel>
              <FormInput
                id="updater"
                v-model="form.lastupdate_by"
                readonly
                class="bg-gray-100 cursor-not-allowed"
              />
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-2">
            <Button
              variant="outline-secondary"
              @click="cancel"
              class="inline-flex items-center gap-2"
            >
              <Lucide v-if="!loading" icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Cancel</span>
            </Button>

            <Button
              variant="primary"
              :loading="loading"
              @click="submit"
              class="inline-flex items-center gap-2"
            >
              <Lucide v-if="!loading" icon="RefreshCw" class="w-4 h-4" aria-hidden="true" />
              <span>Update</span>
            </Button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* opsional: perhalus border merah agar konsisten */
.border-red-500 { border-color: #ef4444 !important; }
</style>
