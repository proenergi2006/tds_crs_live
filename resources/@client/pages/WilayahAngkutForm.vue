<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Wilayah Angkut</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="provinsi" class="block text-sm font-medium mb-1">Provinsi *</label>
            <FormSelect id="provinsi" v-model="form.id_provinsi">
              <option value="">Pilih Provinsi</option>
              <option v-for="p in provinsis" :key="p.id_provinsi" :value="p.id_provinsi">
                {{ p.nama_provinsi }}
              </option>
            </FormSelect>
          </div>
  
          <div>
            <label for="kabupaten" class="block text-sm font-medium mb-1">Kabupaten *</label>
            <FormSelect id="kabupaten" v-model="form.id_kabupaten">
              <option value="">Pilih Kabupaten</option>
              <option v-for="k in kabupatens" :key="k.id_kabupaten" :value="k.id_kabupaten">
                {{ k.nama_kabupaten }}
              </option>
            </FormSelect>
          </div>
  
          <div>
            <label for="destinasi" class="block text-sm font-medium mb-1">Destinasi *</label>
            <FormInput id="destinasi" v-model="form.destinasi" placeholder="Nama destinasi" />
          </div>
  
          <div>
            <label for="status" class="block text-sm font-medium mb-1">Status *</label>
            <FormSelect id="status" v-model="form.is_active">
              <option :value="1">Aktif</option>
              <option :value="0">Tidak Aktif</option>
            </FormSelect>
          </div>
        </div>
  
        <div class="mt-4 flex justify-end space-x-2">
          <Button variant="outline-secondary" @click.prevent="router.back()">Batal</Button>
          <Button type="submit" :loading="loading" variant="primary">Simpan</Button>
        </div>
      </form>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  
  const router = useRouter()
  const route = useRoute()
  const isEdit = !!route.params.id
  const loading = ref(false)
  
  const provinsis = ref<any[]>([])
  const kabupatens = ref<any[]>([])
  
  const form = reactive({
    id_provinsi: '',
    id_kabupaten: '',
    destinasi: '',
    is_active: 1,
  })
  
  async function fetchWilayah() {
    try {
      const resProv = await axios.get('/api/provinsis')
      provinsis.value = resProv.data.data || resProv.data
  
      const resKab = await axios.get('/api/kabupatens')
      kabupatens.value = resKab.data.data || resKab.data
    } catch {
      Swal.fire('Error', 'Gagal memuat data wilayah', 'error')
    }
  }
  
  onMounted(async () => {
    await fetchWilayah()
    if (isEdit) {
      try {
        const { data } = await axios.get(`/api/wilayah-angkuts/${route.params.id}`)
        form.id_provinsi = data.id_provinsi
        form.id_kabupaten = data.id_kabupaten
        form.destinasi = data.destinasi
        form.is_active = Number(data.is_active)
      } catch {
        Swal.fire('Error', 'Gagal memuat data', 'error')
        router.back()
      }
    }
  })
  
  async function submit() {
    if (!form.id_provinsi) return Swal.fire('Peringatan', 'Provinsi wajib dipilih', 'warning')
    if (!form.id_kabupaten) return Swal.fire('Peringatan', 'Kabupaten wajib dipilih', 'warning')
    if (!form.destinasi.trim()) return Swal.fire('Peringatan', 'Destinasi wajib diisi', 'warning')
  
    loading.value = true
    try {
      if (isEdit) {
        await axios.put(`/api/wilayah-angkuts/${route.params.id}`, form)
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/wilayah-angkuts', form)
        Swal.fire('Berhasil', 'Data ditambahkan', 'success')
      }
      router.push({ name: 'wilayah-angkut-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || JSON.stringify(e.response?.data?.errors), 'error')
    } finally {
      loading.value = false
    }
  }
  </script>
  