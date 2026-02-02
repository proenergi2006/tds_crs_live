<template>
    <div class="py-6 bg-slate-100 min-h-screen">
        <div class="intro-y grid grid-cols-12 gap-6 mt-8">
        <div class="col-span-12 lg:col-span-8 xl:col-span-6">
            <div class="box p-5">
        <h2 class="text-lg font-medium mb-4">Edit Kabupaten</h2>
        <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>
  
        <div class="space-y-4">
          <div>
            <FormLabel htmlFor="provinsi">Provinsi</FormLabel>
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
  
          <div>
            <FormLabel htmlFor="nama">Nama Kabupaten</FormLabel>
            <FormInput
              id="nama"
              v-model="form.nama_kabupaten"
              placeholder="Masukkan nama kabupaten"
            />
          </div>
        </div>
  
        <div class="mt-6 flex justify-end space-x-2">
          <Button variant="outline-secondary" @click="cancel">Cancel</Button>
          <Button variant="primary" :loading="loading" @click="submit">Update</Button>
        </div>
    </div>
</div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRouter, useRoute } from 'vue-router'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
  
  const router    = useRouter()
  const route     = useRoute()
  const id        = Number(route.params.id)
  const provinsis = ref<any[]>([])
  const form      = reactive({
    id_kabupaten:    id,
    id_provinsi:     '',
    nama_kabupaten:  '',
  })
  const loading = ref(false)
  const error   = ref('')
  
  onMounted(async () => {
    // fetch provinsi
    try {
      const res = await axios.get('/api/provinsis', { params:{ per_page:100 }})
      provinsis.value = res.data.data || res.data
    } catch {}
  
    // fetch data kabupaten
    try {
      const { data } = await axios.get(`/api/kabupatens/${id}`)
      Object.assign(form, data)
    } catch (e:any) {
      Swal.fire('Error','Gagal memuat data','error')
      router.back()
    }
  })
  
  async function submit() {
    loading.value = true
    try {
      await axios.put(`/api/kabupatens/${id}`, {
        id_provinsi:    form.id_provinsi,
        nama_kabupaten: form.nama_kabupaten,
      })
      Swal.fire({ icon:'success', title:'Kabupaten diperbarui', toast:true, position:'top-end', timer:1500 })
      router.push({ name: 'kabupatens-list' })
    } catch (e:any) {
      error.value = e.response?.data?.message || 'Gagal update'
    } finally {
      loading.value = false
    }
  }
  
  function cancel() {
    router.back()
  }
  </script>
  