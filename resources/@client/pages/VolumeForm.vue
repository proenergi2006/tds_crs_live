<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Volume</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="volume" class="block text-sm font-medium mb-1">Volume *</label>
            <FormInput id="volume" v-model="form.volume" placeholder="Masukkan volume" type="number" />
          </div>
  
          <div>
            <label for="satuan" class="block text-sm font-medium mb-1">Satuan *</label>
            <FormSelect id="satuan" v-model="form.id_satuan">
              <option :value="null">Pilih Satuan</option>
              <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">{{ s.nama_satuan }}</option>
            </FormSelect>
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
  const loading = ref(false)
  const isEdit = !!route.params.id
  const satuans = ref<any[]>([])
  
  const form = reactive({
    volume: '',
    id_satuan: null,
    is_active: 1
  })
  
  async function fetchSatuans() {
    try {
      const { data } = await axios.get('/api/satuans')
      satuans.value = data.data || data
    } catch {
      Swal.fire('Error', 'Gagal memuat satuan', 'error')
    }
  }
  
  onMounted(async () => {
    await fetchSatuans()
    if (isEdit) {
      try {
        const { data } = await axios.get(`/api/volumes/${route.params.id}`)
        Object.assign(form, data)
        form.is_active = data.is_active ? 1 : 0
      } catch {
        Swal.fire('Error', 'Gagal memuat data volume', 'error')
        router.back()
      }
    }
  })
  
  async function submit() {
    if (!form.volume) return Swal.fire('Peringatan', 'Volume wajib diisi', 'warning')
    if (!form.id_satuan) return Swal.fire('Peringatan', 'Satuan wajib dipilih', 'warning')
  
    loading.value = true
    try {
      if (isEdit) {
        await axios.put(`/api/volumes/${route.params.id}`, form)
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/volumes', form)
        Swal.fire('Berhasil', 'Data ditambahkan', 'success')
      }
      router.push({ name: 'volumes-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || JSON.stringify(e.response?.data?.errors), 'error')
    } finally {
      loading.value = false
    }
  }
  </script>
2  