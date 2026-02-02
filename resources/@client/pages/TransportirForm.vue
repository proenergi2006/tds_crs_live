<template>
  <div class="p-8">
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg p-8">
      <h2 class="text-2xl font-bold text-gray-700 mb-6 border-b pb-2">
        {{ isEdit ? 'Edit Data Transportir' : 'Tambah Data Transportir' }}
      </h2>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium mb-1">Nama Perusahaan *</label>
            <FormInput v-model="form.nama_perusahaan" placeholder="PT. Contoh Transportir" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Singkatan *</label>
            <FormInput v-model="form.singkatan" placeholder="CTT" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Kepemilikan *</label>
            <FormSelect v-model="form.kepemilikan">
              <option value="">Pilih Kepemilikan</option>
              <option value="Milik Sendiri">Milik Sendiri</option>
              <option value="Thirdparty">Thirdparty</option>
            </FormSelect>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Lokasi *</label>
            <FormSelect v-model="form.id_cabang">
              <option value="">Pilih Lokasi</option>
              <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                {{ c.nama_cabang }}
              </option>
            </FormSelect>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Telepon *</label>
            <FormInput v-model="form.telpon" placeholder="021-12345678" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Fax</label>
            <FormInput v-model="form.fax" placeholder="021-87654321" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <FormInput v-model="form.email" placeholder="email@contoh.com" type="email" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Nomor HP</label>
            <FormInput v-model="form.nomor_hp" placeholder="08123456789" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Angkutan Kirim *</label>
            <FormSelect v-model="form.angkutan_kirim">
              <option value="">Pilih Angkutan</option>
              <option value="Truck">Truck</option>
              <option value="Kapal">Kapal</option>
              <option value="Truck & Kapal">Truck & Kapal</option>
            </FormSelect>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Terms</label>
            <FormInput v-model="form.terms" placeholder="Net 30 / Net 60" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Alamat</label>
            <FormInput v-model="form.alamat" placeholder="Jl. Contoh No.123" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Perizinan</label>
            <FormInput v-model="form.perizinan" placeholder="SIUP / NIB / DLL" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Masa Berlaku</label>
            <FormInput v-model="form.masa_berlaku" type="date" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Status *</label>
            <FormSelect v-model="form.is_active">
              <option :value="1">Aktif</option>
              <option :value="0">Tidak Aktif</option>
            </FormSelect>
          </div>

          <div class="col-span-1 md:col-span-2">
            <label class="block text-sm font-medium mb-1">Catatan</label>
            <textarea v-model="form.catatan" class="w-full border rounded p-2" rows="3" placeholder="Tambahkan catatan tambahan..."></textarea>
          </div>

          <div class="col-span-1 md:col-span-2 flex items-center space-x-2">
            <input type="checkbox" v-model="form.fleet" :true-value="1" :false-value="0" class="form-checkbox text-blue-600" />
            <span>Fleet</span>
          </div>

          <div class="col-span-1 md:col-span-2">
            <label class="block text-sm font-medium mb-1">Lampiran (PDF max 2MB)</label>
            <input type="file" @change="handleFile" accept="application/pdf" class="border rounded p-2 w-full" />
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
          <Button variant="outline-secondary" @click.prevent="router.back()">Kembali</Button>
          <Button type="submit" :loading="loading" variant="primary">Simpan</Button>
        </div>
      </form>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import Button from '@/components/Base/Button'
import { FormInput, FormSelect } from '@/components/Base/Form'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const cabangs = ref<any[]>([])
const idParam = route.params.id
const isEdit = !!idParam

const form = reactive({
  nama_perusahaan: '',
  singkatan: '',
  kepemilikan: '',
  id_cabang: '',
  telpon: '',
  fax: '',
  email: '',
  nomor_hp: '',
  angkutan_kirim: '',
  terms: '',
  alamat: '',
  perizinan: '',
  masa_berlaku: '',
  is_active: 1,
  catatan: '',
  fleet: 0,
  lampiran: null as File | null
})

onMounted(() => {
  fetchCabangs()
  if (isEdit) {
    axios.get(`/api/transportirs/${idParam}`).then(res => {
      Object.assign(form, res.data)
      form.is_active = res.data.is_active ? 1 : 0
      form.fleet = res.data.fleet ? 1 : 0
    }).catch(() => {
      Swal.fire('Error', 'Gagal memuat data', 'error')
      router.back()
    })
  }
})

async function fetchCabangs() {
  try {
    const { data } = await axios.get('/api/cabangs')
    cabangs.value = data.data || data
  } catch {
    Swal.fire('Error', 'Gagal memuat cabang', 'error')
  }
}

function handleFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    if (file.type !== 'application/pdf') {
      Swal.fire('Peringatan', 'Lampiran harus PDF', 'warning')
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      Swal.fire('Peringatan', 'Lampiran max 2MB', 'warning')
      return
    }
    form.lampiran = file
  }
}

async function submit() {
  if (!form.nama_perusahaan.trim()) return Swal.fire('Peringatan', 'Nama Perusahaan wajib diisi', 'warning')
  if (!form.singkatan.trim()) return Swal.fire('Peringatan', 'Singkatan wajib diisi', 'warning')
  if (!form.kepemilikan) return Swal.fire('Peringatan', 'Kepemilikan wajib diisi', 'warning')
  if (!form.id_cabang) return Swal.fire('Peringatan', 'Lokasi wajib diisi', 'warning')
  if (!form.telpon.trim()) return Swal.fire('Peringatan', 'Telepon wajib diisi', 'warning')
  if (!form.angkutan_kirim) return Swal.fire('Peringatan', 'Angkutan Kirim wajib diisi', 'warning')

  const formData = new FormData()
  Object.entries(form).forEach(([key, value]) => {
    if (value !== null && value !== '') {
      if (key === 'lampiran' && value instanceof File) {
        formData.append(key, value)
      } else {
        formData.append(key, String(value))
      }
    }
  })

  loading.value = true
  try {
    if (isEdit) {
      await axios.post(`/api/transportirs/${idParam}?_method=PUT`, formData)
      Swal.fire('Sukses', 'Data diperbarui', 'success')
    } else {
      await axios.post('/api/transportirs', formData)
      Swal.fire('Sukses', 'Data ditambahkan', 'success')
    }
    router.push({ name: 'transportir-list' })
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || JSON.stringify(e.response?.data?.errors), 'error')
  } finally {
    loading.value = false
  }
}
</script>
