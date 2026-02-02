<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Kapal</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Nama Kapal *</label>
            <FormInput v-model="form.nama_kapal" placeholder="Contoh: KM Cinta Laut" />
          </div>
          <div>
  <label for="kapasitas_max" class="block text-sm font-medium mb-1">Kapasitas Max *</label>
  <InputGroup>
    <FormInput
      id="kapasitas_max"
      v-model="form.kapasitas_max"
      type="number"
      placeholder="Masukkan kapasitas"
      aria-describedby="input-group-kapasitas"
    />
    <InputGroup.Text id="input-group-kapasitas">m³</InputGroup.Text>
  </InputGroup>
</div>
          <div>
            <label class="block text-sm font-medium mb-1">Kelas</label>
            <FormInput v-model="form.kelas" placeholder="Contoh: B" />
          </div>
          <!-- Panjang -->
<div>
  <label for="panjang" class="block text-sm font-medium mb-1">Panjang *</label>
  <div class="flex rounded border border-slate-300">
    <input
      id="panjang"
      v-model="form.panjang"
      type="number"
      placeholder="Masukkan panjang"
      class="flex-1 px-3 py-2 border-0 rounded-l focus:ring-0 focus:outline-none"
    />
    <span class="px-3 py-2 bg-slate-100 border-l border-slate-300 rounded-r text-sm text-gray-700">m</span>
  </div>
</div>

<!-- Lebar -->
<div>
  <label for="lebar" class="block text-sm font-medium mb-1">Lebar *</label>
  <div class="flex rounded border border-slate-300">
    <input
      id="lebar"
      v-model="form.lebar"
      type="number"
      placeholder="Masukkan lebar"
      class="flex-1 px-3 py-2 border-0 rounded-l focus:ring-0 focus:outline-none"
    />
    <span class="px-3 py-2 bg-slate-100 border-l border-slate-300 rounded-r text-sm text-gray-700">m</span>
  </div>
</div>

          <div>
            <label class="block text-sm font-medium mb-1">Asal Kapal</label>
            <FormInput v-model="form.asal_kapal" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tipe Kapal</label>
            <FormInput v-model="form.tipe_kapal" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Transportir *</label>
            <FormSelect v-model="form.id_transportir">
              <option value="">Pilih</option>
              <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }}</option>
            </FormSelect>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Nama Dokumen</label>
            <FormInput v-model="form.dokumen" placeholder="Contoh: Surat Laut" />
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
  import Swal from 'sweetalert2'
  import axios from 'axios'
  import Button from '@/components/Base/Button'
  import { FormInput, InputGroup, FormSelect } from '@/components/Base/Form'
  
  const router = useRouter()
  const route = useRoute()
  const isEdit = !!route.params.id
  const loading = ref(false)
  const transportirs = ref<any[]>([])
  
  const form = reactive({
    nama_kapal: '',
    kapasitas_max: '',
    kelas: '',
    panjang: '',
    lebar: '',
    asal_kapal: '',
    tipe_kapal: '',
    id_transportir: '',
    dokumen: '',
    masa_berlaku: '',
    lampiran: null as File | null,
    is_active: 1
  })
  
  async function fetchTransportirs() {
    try {
      const { data } = await axios.get('/api/transportirs')
      transportirs.value = data.data || data
    } catch {
      Swal.fire('Error', 'Gagal memuat transportir', 'error')
    }
  }
  
  function handleFile(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file && file.type === 'application/pdf' && file.size <= 2 * 1024 * 1024) {
      form.lampiran = file
    } else {
      Swal.fire('Peringatan', 'Lampiran harus PDF dan maksimal 2MB', 'warning')
    }
  }
  
  async function submit() {
    if (!form.nama_kapal || !form.kapasitas_max || !form.id_transportir) {
      return Swal.fire('Peringatan', 'Isian wajib belum lengkap', 'warning')
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
      if (isEdit) {
        await axios.post(`/api/master-kapals/${route.params.id}?_method=PUT`, formData)
      } else {
        await axios.post('/api/master-kapals', formData)
      }
      Swal.fire('Sukses', 'Data disimpan', 'success')
      router.push({ name: 'kapals-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error')
    } finally {
      loading.value = false
    }
  }
  
  onMounted(async () => {
    await fetchTransportirs()
    if (isEdit) {
      const { data } = await axios.get(`/api/master-kapals/${route.params.id}`)
      Object.assign(form, data)
      form.is_active = data.is_active ? 1 : 0
    }
  })
  </script>
  