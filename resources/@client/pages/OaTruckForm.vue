<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} OA Truck</h2>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Transportir *</label>
            <FormSelect v-model="form.id_transportir">
              <option value="">Pilih</option>
              <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }}</option>
            </FormSelect>
          </div>
  
          <div>
            <label class="block text-sm font-medium mb-1">Wilayah Angkut *</label>
            <FormSelect v-model="form.id_angkut_wilayah">
              <option value="">Pilih</option>
              <option v-for="w in wilayahs" :key="w.id" :value="w.id">
                {{ w.provinsi?.nama_provinsi }} - {{ w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
              </option>
            </FormSelect>
          </div>
        </div>
  
        <div class="mt-4 border-t pt-4">
          <h3 class="font-semibold mb-2">Detail Ongkos per Volume</h3>
          <div
            v-for="(d, i) in form.details"
            :key="i"
            class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-2 items-center"
          >
            <div>
              <label class="block text-sm font-medium mb-1">Volume *</label>
              <FormSelect v-model="d.id_volume">
                <option value="">Pilih</option>
                <option v-for="v in volumes" :key="v.id_volume" :value="v.id_volume">{{ v.volume }}</option>
              </FormSelect>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">OA *</label>
              <FormInput v-model="d.oa" type="number" placeholder="Ongkos Angkut" />
            </div>
            <button type="button" class="text-red-600 mt-6" @click="removeDetail(i)">Hapus</button>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" @click="addDetail">+ Tambah Detail</button>
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
  import { useRouter, useRoute } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  
  const router = useRouter()
  const route = useRoute()
  const isEdit = !!route.params.id
  const loading = ref(false)
  
  const transportirs = ref<any[]>([])
  const wilayahs = ref<any[]>([])
  const volumes = ref<any[]>([])
  
  const form = reactive({
    id_transportir: '',
    id_angkut_wilayah: '',
    details: [{ id_volume: '', oa: '' }]
  })
  
  function addDetail() {
    form.details.push({ id_volume: '', oa: '' })
  }
  function removeDetail(index: number) {
    form.details.splice(index, 1)
  }
  
  async function fetchAll() {
    const [t, w, v] = await Promise.all([
      axios.get('/api/transportirs'),
      axios.get('/api/wilayah-angkuts'),
      axios.get('/api/volumes')
    ])
    transportirs.value = t.data.data || t.data
    wilayahs.value = w.data.data || w.data
    volumes.value = v.data.data || v.data
  }
  
  async function submit() {
    if (!form.id_transportir || !form.id_angkut_wilayah) {
      return Swal.fire('Peringatan', 'Transportir dan Wilayah wajib dipilih', 'warning')
    }
    loading.value = true
    try {
      const url = isEdit ? `/api/ongkos-trucks/${route.params.id}` : '/api/ongkos-trucks'
      const method = isEdit ? 'put' : 'post'
      await axios[method](url, form)
      Swal.fire('Berhasil', 'Data disimpan', 'success')
      router.push({ name: 'oa-trucks-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error')
    } finally {
      loading.value = false
    }
  }
  
  onMounted(async () => {
    await fetchAll()
    if (isEdit) {
      const { data } = await axios.get(`/api/ongkos-trucks/${route.params.id}`)
      Object.assign(form, {
        id_transportir: data.id_transportir,
        id_angkut_wilayah: data.id_angkut_wilayah,
        details: data.details || []
      })
    }
  })
  </script>
  