<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12 lg:col-span-8 xl:col-span-6">
        <div class="box p-5">
          <h2 class="text-lg font-medium mb-4">Add Kabupaten</h2>
          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <FormLabel htmlFor="provinsi">Provinsi *</FormLabel>
              <FormSelect
                id="provinsi"
                ref="provinsiRef"
                v-model="form.id_provinsi"
                :class="{'border-rose-500': showErrors && !form.id_provinsi}"
                required
              >
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
              <FormLabel htmlFor="nama">Nama Kabupaten *</FormLabel>
              <FormInput
                id="nama"
                ref="namaRef"
                v-model="form.nama_kabupaten"
                placeholder="Masukkan nama kabupaten"
                :class="{'border-rose-500': showErrors && !form.nama_kabupaten.trim()}"
                autocomplete="off"
                required
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
                <span>Cancel</span>
              </Button>

              <Button
                type="submit"
                variant="primary"
                :loading="loading"
                class="inline-flex items-center gap-2"
              >
                <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
                <span>Save</span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router     = useRouter()
const provinsis  = ref<any[]>([])
const form       = reactive({
  id_provinsi:    '',
  nama_kabupaten: '',
})
const loading    = ref(false)
const error      = ref('')
const showErrors = ref(false)

// refs untuk fokus otomatis
const provinsiRef = ref<HTMLElement|null>(null)
const namaRef     = ref<HTMLInputElement|null>(null)

// fetch provinsi untuk dropdown
onMounted(async () => {
  try {
    const res = await axios.get('/api/provinsis', { params:{ per_page:100 }})
    provinsis.value = res.data.data || res.data
  } catch {}
})

const isFormValid = computed(() =>
  !!form.id_provinsi && !!form.nama_kabupaten.trim()
)

function buildMissingList() {
  const missing: string[] = []
  if (!form.id_provinsi)        missing.push('Provinsi')
  if (!form.nama_kabupaten.trim()) missing.push('Nama Kabupaten')
  return missing
}

async function showRequiredAlert(missing: string[]) {
  const listHtml = `<ul style="text-align:left;margin:8px 0 0 14px;">${missing
    .map(m => `<li>${m}</li>`)
    .join('')}</ul>`
  await Swal.fire({
    icon: 'error',
    title: 'Lengkapi data wajib',
    html: `Field berikut wajib diisi:${listHtml}`,
    confirmButtonText: 'OK'
  })
}

async function focusFirstMissing() {
  await nextTick()
  if (!form.id_provinsi && provinsiRef.value) {
    ;(provinsiRef.value as HTMLSelectElement).focus()
    return
  }
  if (!form.nama_kabupaten.trim() && namaRef.value) {
    namaRef.value.focus()
  }
}

async function submit() {
  error.value = ''
  showErrors.value = true

  // VALIDASI WAJIB (SweetAlert)
  const missing = buildMissingList()
  if (missing.length) {
    await showRequiredAlert(missing)
    await focusFirstMissing()
    return
  }

  loading.value = true
  try {
    await axios.post('/api/kabupatens', form)
    Swal.fire({
      icon:'success',
      title:'Kabupaten dibuat',
      toast:true,
      position:'top-end',
      timer:1500,
      showConfirmButton:false
    })
    router.push({ name: 'kabupatens-list' })
  } catch (e:any) {
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
/* opsional: perjelas border merah */
.border-rose-500 { box-shadow: 0 0 0 1px rgba(244, 63, 94, .6) inset; }
</style>
