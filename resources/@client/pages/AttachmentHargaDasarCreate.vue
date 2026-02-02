<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-6 box bg-white shadow rounded">
          <h2 class="text-2xl font-semibold mb-6">Tambah Attachment Harga Dasar</h2>
          <p v-if="error" class="text-red-500 mb-4">{{ error }}</p>

          <div class="space-y-6">
            <div>
              <FormLabel htmlFor="periode_awal">Periode Awal</FormLabel>
              <FormInput id="periode_awal" type="date" v-model="form.periode_awal" class="w-full" />
            </div>

            <div>
              <FormLabel htmlFor="periode_akhir">Periode Akhir</FormLabel>
              <FormInput id="periode_akhir" type="date" v-model="form.periode_akhir" class="w-full" />
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
              <FormLabel htmlFor="lampiran">Lampiran (PDF ≤ 2 MB)</FormLabel>
              <input
                id="lampiran"
                type="file"
                accept="application/pdf"
                @change="onFileChange"
                class="w-full border rounded px-3 py-2"
              />
              <p v-if="fileName" class="mt-1 text-sm text-slate-600 inline-flex items-center gap-2">
                <Lucide icon="Paperclip" class="w-4 h-4" aria-hidden="true" />
                <span class="truncate max-w-[260px]">{{ fileName }}</span>
              </p>
            </div>
          </div>

          <div class="mt-8 flex justify-end space-x-4">
            <Button variant="outline-secondary" @click="cancel" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Batal</span>
            </Button>
            <Button variant="primary" :loading="loading" @click="submit" class="inline-flex items-center gap-2">
              <Lucide v-if="!loading" icon="Save" class="w-4 h-4" aria-hidden="true" />
              <span>Simpan</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import { FormLabel, FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router   = useRouter()
const error    = ref('')
const loading  = ref(false)

const form = reactive({
  periode_awal:  '',
  periode_akhir: '',
  catatan:       '',
  created_by:    '',
})

const lampiranFile = ref<File|null>(null)
const fileName     = ref('')

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/user')
    form.created_by = data.name
  } catch {}
})

/** ===== Validasi File (PDF & ≤ 2 MB) ===== */
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
    Swal.fire('Error', 'Hanya file PDF yang diperbolehkan', 'error')
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

/** ===== Submit ===== */
async function submit() {
  error.value = ''

  // Validasi form dasar
  if (!form.periode_awal || !form.periode_akhir) {
    return Swal.fire('Error', 'Periode wajib diisi', 'error')
  }
  if (new Date(form.periode_akhir) < new Date(form.periode_awal)) {
    return Swal.fire('Error', 'Periode Akhir tidak boleh lebih awal dari Periode Awal', 'error')
  }
  if (!lampiranFile.value) {
    return Swal.fire('Error', 'Lampiran PDF wajib diunggah', 'error')
  }
  // Re-check terakhir
  const isPdf = lampiranFile.value.type === 'application/pdf' || lampiranFile.value.name.toLowerCase().endsWith('.pdf')
  if (!isPdf || lampiranFile.value.size > MAX_SIZE) {
    return Swal.fire('Error', 'File lampiran tidak valid (harus PDF dan ≤ 2 MB)', 'error')
  }

  loading.value = true
  try {
    const fd = new FormData()
    fd.append('periode_awal',  form.periode_awal)
    fd.append('periode_akhir', form.periode_akhir)
    fd.append('catatan',       form.catatan)
    fd.append('created_by',    form.created_by)
    // Penting: kirim file sebagai File + sertakan nama file
    fd.append('lampiran', lampiranFile.value as File, (lampiranFile.value as File).name)

    // Penting: JANGAN override headers Content-Type (biarkan browser set boundary)
    await axios.post('/api/attachment-harga-dasar', fd)

    Swal.fire({ icon:'success', title:'Berhasil disimpan', toast:true, position:'top-end', timer:1500, showConfirmButton:false })
    router.push({ name: 'attachment-harga-dasar-list' })
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
/* no extra overrides */
</style>
