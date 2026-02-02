<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Truck</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
         
          <div>
            <label class="block text-sm font-medium mb-1">Plat Nomor *</label>
            <FormInput v-model="form.nopol" placeholder="Contoh: B 1234 CD" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Jenis Truck</label>
            <FormInput v-model="form.jenis_truck" placeholder="Contoh: Trailer" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Kapasitas (KL)</label>
            <FormInput v-model="form.kapasitas" type="number" placeholder="Masukkan kapasitas" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Transportir *</label>
            <FormSelect v-model="form.id_transportir">
              <option value="">Pilih Transportir</option>
              <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }}</option>
            </FormSelect>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Nama Dokumen</label>
            <FormInput v-model="form.nama_dokumen" placeholder="Contoh: STNK" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Masa Berlaku Dokumen</label>
            <FormInput v-model="form.masa_berlaku" type="date" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Status *</label>
            <FormSelect v-model="form.is_active">
              <option :value="1">Aktif</option>
              <option :value="0">Tidak Aktif</option>
            </FormSelect>
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium mb-1">Lampiran (PDF max 2MB)</label>
            <input type="file" @change="handleFile" accept="application/pdf" class="w-full border rounded p-2" />
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-2">
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
  const transportirs = ref<any[]>([])
  
  const form = reactive({
   
    nopol: '',
    jenis_truck: '',
    kapasitas: '',
    id_transportir: '',
    nama_dokumen: '',
    masa_berlaku: '',
    lampiran: null as File | null,
    is_active: 1
  })
  
  function handleFile(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file && file.type === 'application/pdf' && file.size <= 2 * 1024 * 1024) {
      form.lampiran = file
    } else {
      Swal.fire('Peringatan', 'Lampiran harus PDF dan maksimal 2MB', 'warning')
    }
  }
  
  async function fetchTransportirs() {
    const { data } = await axios.get('/api/transportirs')
    transportirs.value = data.data || data
  }
  
  async function submit() {
    if (!form.nopol || !form.id_transportir) {
      return Swal.fire('Peringatan', 'Nama, Plat Nomor, dan Transportir wajib diisi', 'warning')
    }
  
    const formData = new FormData()
    Object.entries(form).forEach(([k, v]) => {
      if (v !== null && v !== '') {
        if (k === 'lampiran' && v instanceof File) formData.append(k, v)
        else formData.append(k, String(v))
      }
    })
  
    loading.value = true
    try {
      const url = isEdit ? `/api/master-trucks/${route.params.id}?_method=PUT` : '/api/master-trucks'
      await axios.post(url, formData)
      Swal.fire('Sukses', 'Data berhasil disimpan', 'success')
      router.push({ name: 'trucks-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error')
    } finally {
      loading.value = false
    }
  }
  
  onMounted(async () => {
    await fetchTransportirs()
    if (isEdit) {
      const { data } = await axios.get(`/api/master-trucks/${route.params.id}`)
      Object.assign(form, data)
      form.is_active = data.is_active ? 1 : 0
    }
  })
  </script>
  