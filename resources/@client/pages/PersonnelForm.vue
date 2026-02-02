<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Personnel</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Nama *</label>
            <FormInput v-model="form.nama" placeholder="Nama lengkap" />
          </div>
  
          <div>
            <label class="block text-sm font-medium mb-1">Transportir *</label>
            <FormSelect v-model="form.id_transportir">
              <option value="">Pilih</option>
              <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }} - {{ t.singkatan }}</option>
            </FormSelect>
          </div>
  
          <div>
            <label class="block text-sm font-medium mb-1">Nama Dokumen</label>
            <FormInput v-model="form.nama_dokumen" placeholder="SIM / Sertifikat" />
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
            <label class="block text-sm font-medium mb-1">Lampiran (PDF max 2MB)</label>
            <input type="file" @change="handleFile" accept="application/pdf" class="border rounded p-2 w-full" />
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
  import { useRouter, useRoute } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  
  const router = useRouter()
  const route = useRoute()
  const loading = ref(false)
  const isEdit = !!route.params.id
  const transportirs = ref<any[]>([])
  
  const form = reactive({
    nama: '',
    id_transportir: '',
    nama_dokumen: '',
    masa_berlaku: '',
    is_active: 1,
    lampiran: null as File | null
  })
  
  onMounted(async () => {
    try {
      const { data } = await axios.get('/api/transportirs')
      transportirs.value = data.data || data
      if (isEdit) {
        const res = await axios.get(`/api/personnels/${route.params.id}`)
        Object.assign(form, res.data)
        form.is_active = res.data.is_active ? 1 : 0
      }
    } catch {
      Swal.fire('Error', 'Gagal memuat data awal', 'error')
    }
  })
  
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
    const formData = new FormData()
    Object.entries(form).forEach(([k, v]) => {
      if (v !== null && v !== '') {
        if (k === 'lampiran' && v instanceof File) {
          formData.append(k, v)
        } else {
          formData.append(k, String(v))
        }
      }
    })
  
    loading.value = true
    try {
      if (isEdit) {
        await axios.post(`/api/personnels/${route.params.id}?_method=PUT`, formData)
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/personnels', formData)
        Swal.fire('Berhasil', 'Data ditambahkan', 'success')
      }
      router.push({ name: 'personnel-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || JSON.stringify(e.response?.data?.errors), 'error')
    } finally {
      loading.value = false
    }
  }
  </script>
  