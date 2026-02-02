<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-6 box bg-white shadow rounded">
          <h2 class="text-2xl font-semibold mb-6">Edit Attachment Harga Dasar</h2>
          <p v-if="error" class="text-red-500 mb-4">{{ error }}</p>

          <div class="space-y-6">
            <div>
              <FormLabel htmlFor="periode_awal">Periode Awal</FormLabel>
              <FormInput
                id="periode_awal"
                type="date"
                v-model="form.periode_awal"
                class="w-full"
              />
            </div>

            <div>
              <FormLabel htmlFor="periode_akhir">Periode Akhir</FormLabel>
              <FormInput
                id="periode_akhir"
                type="date"
                v-model="form.periode_akhir"
                class="w-full"
              />
            </div>

            <div>
              <FormLabel htmlFor="catatan">Catatan</FormLabel>
              <textarea
                id="catatan"
                v-model="form.catatan"
                rows="4"
                class="w-full border rounded px-3 py-2"
                placeholder="(opsional)"
              ></textarea>
            </div>

            <div>
              <FormLabel htmlFor="lampiran">
                Lampiran (PDF, kosong = tidak ganti) — maks 2 MB
              </FormLabel>
              <input
                id="lampiran"
                type="file"
                accept="application/pdf"
                @change="onFileChange"
                class="w-full border rounded px-3 py-2"
              />
              <div v-if="fileName" class="mt-2 text-sm text-slate-600 inline-flex items-center gap-2">
                <Lucide icon="Paperclip" class="w-4 h-4" aria-hidden="true" />
                <span class="truncate max-w-[280px]">{{ fileName }}</span>
                <button @click="clearFile" type="button" class="text-slate-500 hover:text-rose-600">
                  <Lucide icon="XCircle" class="w-4 h-4" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>

          <div class="mt-8 flex justify-end space-x-4">
            <Button variant="outline-secondary" @click="cancel" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Batal</span>
            </Button>
            <Button variant="primary" :loading="loading" @click="submit" class="inline-flex items-center gap-2">
              <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
              <span>Update</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import { FormLabel, FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router = useRouter()
const route  = useRoute()
const id     = Number(route.params.id)
const error  = ref('')
const loading = ref(false)

const form = reactive({
  periode_awal:  '',
  periode_akhir: '',
  catatan:       '',
  lastupdate_by: '',
})

const lampiranFile = ref<File|null>(null)
const fileName     = ref('')

onMounted(async () => {
  // ambil user
  try {
    const { data: u } = await axios.get('/api/user')
    form.lastupdate_by = u.name
  } catch {}

  // ambil data existing
  try {
    const { data: it } = await axios.get(`/api/attachment-harga-dasar/${id}`)
    Object.assign(form, it)
  } catch {
    Swal.fire('Error','Data tidak ditemukan','error')
    router.back()
  }
})

/** ==== Validasi file: PDF & ≤ 2 MB ==== */
const MAX_SIZE = 2 * 1024 * 1024 // 2 MB

function resetFileInput() {
  lampiranFile.value = null
  fileName.value = ''
  const input = document.getElementById('lampiran') as HTMLInputElement | null
  if (input) input.value = ''
}

function onFileChange(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0] ?? null
  if (!f) {
    resetFileInput()
    return
  }

  const isPdf = f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf')
  if (!isPdf) {
    Swal.fire('Error','Hanya PDF yang diperbolehkan','error')
    resetFileInput()
    return
  }

  if (f.size > MAX_SIZE) {
    const mb = (f.size / (1024 * 1024)).toFixed(2)
    Swal.fire('Error', `Ukuran file (${mb} MB) melebihi batas 2 MB`, 'error')
    resetFileInput()
    return
  }

  lampiranFile.value = f
  fileName.value = f.name
}

function clearFile() {
  resetFileInput()
}

/** ==== Submit ==== */
async function submit() {
  error.value = ''

  // Validasi form dasar
  if (!form.periode_awal || !form.periode_akhir) {
    return Swal.fire('Error','Periode wajib diisi','error')
  }
  if (new Date(form.periode_akhir) < new Date(form.periode_awal)) {
    return Swal.fire('Error','Periode Akhir tidak boleh lebih awal dari Periode Awal','error')
  }

  // Re-check file jika user memilih file baru
  if (lampiranFile.value) {
    const isPdf = lampiranFile.value.type === 'application/pdf' || lampiranFile.value.name.toLowerCase().endsWith('.pdf')
    if (!isPdf || lampiranFile.value.size > MAX_SIZE) {
      return Swal.fire('Error','File lampiran tidak valid (harus PDF dan ≤ 2 MB)','error')
    }
  }

  loading.value = true
  try {
    const fd = new FormData()
    fd.append('periode_awal',  form.periode_awal)
    fd.append('periode_akhir', form.periode_akhir)
    fd.append('catatan',       form.catatan)
    fd.append('lastupdate_by', form.lastupdate_by)

    // lampiran opsional saat edit
    if (lampiranFile.value) {
      // sertakan nama file saat append
      fd.append('lampiran', lampiranFile.value as File, (lampiranFile.value as File).name)
    }

    // Method spoofing PUT — biarkan browser set boundary (jangan override headers)
    await axios.post(`/api/attachment-harga-dasar/${id}?_method=PUT`, fd)

    Swal.fire({ icon:'success', title:'Berhasil diperbarui', toast:true, position:'top-end', timer:1500, showConfirmButton:false })
    router.push({ name: 'attachment-harga-dasar-list' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal update'
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.back()
}
</script>

<style scoped>
/* tidak ada override khusus */
</style>
