<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <!-- full width on all breakpoints -->
      <div class="col-span-12">
        <div class="box p-5">
          <h2 class="text-lg font-medium mb-4">Tambah Provinsi</h2>
          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <FormLabel htmlFor="nama">Nama Provinsi</FormLabel>
              <FormInput
                id="nama"
                v-model="form.nama_provinsi"
                placeholder="Masukkan nama provinsi"
                class="w-full"
                autocomplete="off"
              />
            </div>

            <div class="mt-6 flex justify-end gap-2">
              <Button
                type="button"
                variant="outline-secondary"
                @click="cancel"
                class="inline-flex items-center gap-2"
                :disabled="loading"
              >
                <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
                <span>Batal</span>
              </Button>

              <Button
                type="submit"
                variant="primary"
                :loading="loading"
                class="inline-flex items-center gap-2"
              >
                <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
                <span>Simpan</span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router = useRouter()

const form = reactive({
  nama_provinsi: '',
})

const loading = ref(false)
const error   = ref('')

async function submit() {
  error.value = ''
  if (!form.nama_provinsi.trim()) {
    return Swal.fire('Error','Nama provinsi wajib diisi','error')
  }
  loading.value = true
  try {
    await axios.post('/api/provinsis', form)
    Swal.fire({
      icon:'success',
      title:'Provinsi dibuat',
      toast:true,
      position:'top-end',
      timer:1500,
      showConfirmButton:false
    })
    router.push({ name: 'provinsi-list' })
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

<style scoped>
/* optional: biar box nggak terlalu mepet di mobile */
.box { border-radius: 0.5rem; }
</style>
