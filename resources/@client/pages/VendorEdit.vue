<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12 lg:col-span-8">
        <div class="p-5 box">
          <h2 class="text-lg font-medium mb-4">Edit Vendor</h2>

          <!-- Banner error umum -->
          <p v-if="error" class="text-red-500 mb-3 whitespace-pre-line">{{ error }}</p>

          <div class="space-y-5">
            <!-- Nama Vendor -->
            <div>
              <FormLabel for="nama">Nama Vendor</FormLabel>
              <FormInput id="nama" v-model="form.nama_vendor" placeholder="Nama Vendor" />
              <small v-if="fieldErrors.nama_vendor" class="text-red-500">{{ fieldErrors.nama_vendor[0] }}</small>
              <small v-else-if="tried && !form.nama_vendor.trim()" class="text-red-500">Nama wajib diisi</small>
            </div>

            <!-- Inisial -->
            <div>
              <FormLabel for="inisial">Inisial</FormLabel>
              <FormInput id="inisial" v-model="form.inisial" maxlength="10" />
              <small v-if="fieldErrors.inisial" class="text-red-500">{{ fieldErrors.inisial[0] }}</small>
              <small v-else-if="tried && !form.inisial.trim()" class="text-red-500">Inisial wajib diisi</small>
            </div>

            <hr class="my-2">

            <!-- NPWP -->
            <div>
              <FormLabel for="npwp">NPWP (16 digit)</FormLabel>
              <input
                id="npwp"
                type="text"
                class="w-full border rounded px-3 py-2"
                :class="npwpError ? 'border-red-400 focus:border-red-500' : ''"
                :value="npwpInput"
                @input="onNpwpInput(($event.target as HTMLInputElement).value)"
                placeholder="Masukkan NPWP (boleh pakai spasi/tanda baca)"
                inputmode="numeric"
                autocomplete="off"
              />
              <small v-if="npwpError" class="text-red-500">{{ npwpError }}</small>
              <small v-else class="text-slate-500">Disimpan sebagai 16 digit tanpa pemisah.</small>

              <div class="mt-3">
                <FormLabel for="npwp_file">Lampiran NPWP (PDF/JPG/PNG, maks 4MB)</FormLabel>
                <div class="flex items-center gap-2">
                  <input id="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, 'npwp')" />
                  <template v-if="currentFiles.npwp_file && !removeFlags.npwp_file && !newFiles.npwp">
                    <a :href="fileUrl(currentFiles.npwp_file)" target="_blank" class="text-blue-600 underline">Lihat lampiran</a>
                    <Button variant="outline-danger" size="sm" @click="removeFlags.npwp_file = true">Hapus lampiran</Button>
                  </template>
                  <template v-if="removeFlags.npwp_file">
                    <span class="text-rose-600 text-sm">Lampiran akan dihapus</span>
                    <Button variant="outline-secondary" size="sm" @click="removeFlags.npwp_file = false">Batal</Button>
                  </template>
                  <template v-if="newFiles.npwp">
                    <span class="text-slate-600 text-sm truncate max-w-[240px]">Baru: {{ newFiles.npwp.name }}</span>
                    <Button variant="outline-secondary" size="sm" @click="clearNewFile('npwp')">Batalkan file baru</Button>
                  </template>
                </div>
              </div>
            </div>

            <!-- NIB / TDP / SIUP -->
            <div>
              <FormLabel for="nib">NIB / TDP / SIUP (10–20 digit)</FormLabel>
              <input
                id="nib"
                type="text"
                class="w-full border rounded px-3 py-2"
                :class="nibError ? 'border-red-400 focus:border-red-500' : ''"
                :value="nibInput"
                @input="onNibInput(($event.target as HTMLInputElement).value)"
                placeholder="Masukkan nomor NIB/TDP/SIUP"
                inputmode="numeric"
                autocomplete="off"
              />
              <small v-if="nibError" class="text-red-500">{{ nibError }}</small>

              <div class="mt-3">
                <FormLabel for="nib_file">Lampiran NIB/TDP/SIUP (PDF/JPG/PNG, maks 4MB)</FormLabel>
                <div class="flex items-center gap-2">
                  <input id="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, 'nib')" />
                  <template v-if="currentFiles.nib_file && !removeFlags.nib_file && !newFiles.nib">
                    <a :href="fileUrl(currentFiles.nib_file)" target="_blank" class="text-blue-600 underline">Lihat lampiran</a>
                    <Button variant="outline-danger" size="sm" @click="removeFlags.nib_file = true">Hapus lampiran</Button>
                  </template>
                  <template v-if="removeFlags.nib_file">
                    <span class="text-rose-600 text-sm">Lampiran akan dihapus</span>
                    <Button variant="outline-secondary" size="sm" @click="removeFlags.nib_file = false">Batal</Button>
                  </template>
                  <template v-if="newFiles.nib">
                    <span class="text-slate-600 text-sm truncate max-w-[240px]">Baru: {{ newFiles.nib.name }}</span>
                    <Button variant="outline-secondary" size="sm" @click="clearNewFile('nib')">Batalkan file baru</Button>
                  </template>
                </div>
              </div>
            </div>

            <!-- SPPKP -->
            <div>
              <FormLabel for="sppkp">SPPKP (8–20 digit)</FormLabel>
              <input
                id="sppkp"
                type="text"
                class="w-full border rounded px-3 py-2"
                :class="sppkpError ? 'border-red-400 focus:border-red-500' : ''"
                :value="sppkpInput"
                @input="onSppkpInput(($event.target as HTMLInputElement).value)"
                placeholder="Masukkan nomor SPPKP"
                inputmode="numeric"
                autocomplete="off"
              />
              <small v-if="sppkpError" class="text-red-500">{{ sppkpError }}</small>

              <div class="mt-3">
                <FormLabel for="sppkp_file">Lampiran SPPKP (PDF/JPG/PNG, maks 4MB)</FormLabel>
                <div class="flex items-center gap-2">
                  <input id="sppkp_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, 'sppkp')" />
                  <template v-if="currentFiles.sppkp_file && !removeFlags.sppkp_file && !newFiles.sppkp">
                    <a :href="fileUrl(currentFiles.sppkp_file)" target="_blank" class="text-blue-600 underline">Lihat lampiran</a>
                    <Button variant="outline-danger" size="sm" @click="removeFlags.sppkp_file = true">Hapus lampiran</Button>
                  </template>
                  <template v-if="removeFlags.sppkp_file">
                    <span class="text-rose-600 text-sm">Lampiran akan dihapus</span>
                    <Button variant="outline-secondary" size="sm" @click="removeFlags.sppkp_file = false">Batal</Button>
                  </template>
                  <template v-if="newFiles.sppkp">
                    <span class="text-slate-600 text-sm truncate max-w-[240px]">Baru: {{ newFiles.sppkp.name }}</span>
                    <Button variant="outline-secondary" size="sm" @click="clearNewFile('sppkp')">Batalkan file baru</Button>
                  </template>
                </div>
              </div>
            </div>

            <!-- Surat Pernyataan / Rek Giro / Scan Buku Rekening -->
            <div>
              <FormLabel for="bank_letter_file">Surat Pernyataan / Rek Giro / Scan Buku Rekening (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <div class="flex items-center gap-2">
                <input id="bank_letter_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, 'bank')" />
                <template v-if="currentFiles.bank_account_letter_file && !removeFlags.bank_account_letter_file && !newFiles.bank">
                  <a :href="fileUrl(currentFiles.bank_account_letter_file)" target="_blank" class="text-blue-600 underline">Lihat lampiran</a>
                  <Button variant="outline-danger" size="sm" @click="removeFlags.bank_account_letter_file = true">Hapus lampiran</Button>
                </template>
                <template v-if="removeFlags.bank_account_letter_file">
                  <span class="text-rose-600 text-sm">Lampiran akan dihapus</span>
                  <Button variant="outline-secondary" size="sm" @click="removeFlags.bank_account_letter_file = false">Batal</Button>
                </template>
                <template v-if="newFiles.bank">
                  <span class="text-slate-600 text-sm truncate max-w-[240px]">Baru: {{ newFiles.bank.name }}</span>
                  <Button variant="outline-secondary" size="sm" @click="clearNewFile('bank')">Batalkan file baru</Button>
                </template>
              </div>
            </div>

            <!-- Company Profile -->
            <div>
              <FormLabel for="company_profile_file">Company Profile (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <div class="flex items-center gap-2">
                <input id="company_profile_file" type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange($event, 'profile')" />
                <template v-if="currentFiles.company_profile_file && !removeFlags.company_profile_file && !newFiles.profile">
                  <a :href="fileUrl(currentFiles.company_profile_file)" target="_blank" class="text-blue-600 underline">Lihat lampiran</a>
                  <Button variant="outline-danger" size="sm" @click="removeFlags.company_profile_file = true">Hapus lampiran</Button>
                </template>
                <template v-if="removeFlags.company_profile_file">
                  <span class="text-rose-600 text-sm">Lampiran akan dihapus</span>
                  <Button variant="outline-secondary" size="sm" @click="removeFlags.company_profile_file = false">Batal</Button>
                </template>
                <template v-if="newFiles.profile">
                  <span class="text-slate-600 text-sm truncate max-w-[240px]">Baru: {{ newFiles.profile.name }}</span>
                  <Button variant="outline-secondary" size="sm" @click="clearNewFile('profile')">Batalkan file baru</Button>
                </template>
              </div>
            </div>

            <!-- Catatan -->
            <div>
              <FormLabel for="catatan">Catatan</FormLabel>
              <textarea id="catatan" v-model="form.catatan" rows="3" class="w-full border rounded px-3 py-2"></textarea>
              <small v-if="fieldErrors.catatan" class="text-red-500">{{ fieldErrors.catatan[0] }}</small>
            </div>

            <!-- Active -->
            <div class="flex items-center space-x-2">
              <FormSwitch>
                <FormSwitch.Input type="checkbox" v-model="form.is_active"/>
              </FormSwitch>
              <span>Active</span>
            </div>

            <!-- Updated By -->
            <div>
              <FormLabel for="updater">Updated By</FormLabel>
              <FormInput id="updater" v-model="form.lastupdate_by" readonly class="bg-gray-100 cursor-not-allowed" />
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline-secondary" @click="cancel">Cancel</Button>
            <Button variant="primary" :loading="loading" :disabled="disableUpdate" @click="submit">Update</Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'
import { FormInput, FormLabel, FormSwitch } from '@/components/Base/Form'
import Button from '@/components/Base/Button'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()
const id     = Number(route.params.id)

const error        = ref('')
const loading      = ref(false)
const tried        = ref(false)
const fieldErrors  = ref<Record<string, string[]>>({})

// ====== FORM (text) ======
const form = reactive<any>({
  id_vendor:     id,
  nama_vendor:   '',
  inisial:       '',
  catatan:       '',
  is_active:     true,
  lastupdate_by: '',

  // nomor dokumen (disimpan digit-only)
  npwp_number:   '',
  nib_number:    '',
  sppkp_number:  '',
})

// ====== FILE STATE ======
const currentFiles = reactive({
  npwp_file: null as string | null,
  nib_file:  null as string | null,
  sppkp_file: null as string | null,
  bank_account_letter_file: null as string | null,
  company_profile_file: null as string | null,
})

// file baru dipilih user
const newFiles = reactive<Record<'npwp'|'nib'|'sppkp'|'bank'|'profile', File|null>>({
  npwp: null, nib: null, sppkp: null, bank: null, profile: null
})

// flag hapus file lama
const removeFlags = reactive({
  npwp_file: false,
  nib_file:  false,
  sppkp_file: false,
  bank_account_letter_file: false,
  company_profile_file: false,
})

// ====== NPWP / NIB / SPPKP INPUT ======
const npwpInput  = ref('')
const nibInput   = ref('')
const sppkpInput = ref('')

function formatGroup4(digits: string) {
  return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim()
}
function onNpwpInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 16)
  form.npwp_number = digits
  npwpInput.value  = formatGroup4(digits)
}
function onNibInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 20)
  form.nib_number = digits
  nibInput.value  = digits
}
function onSppkpInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 20)
  form.sppkp_number = digits
  sppkpInput.value  = digits
}

const npwpError = computed(() => {
  if (!form.npwp_number) return ''
  return form.npwp_number.length === 16 ? '' : 'NPWP harus 16 digit angka.'
})
const nibError = computed(() => {
  if (!form.nib_number) return ''
  const len = form.nib_number.length
  return len >= 10 && len <= 20 ? '' : 'Nomor NIB/TDP/SIUP harus 10–20 digit.'
})
const sppkpError = computed(() => {
  if (!form.sppkp_number) return ''
  const len = form.sppkp_number.length
  return len >= 8 && len <= 20 ? '' : 'Nomor SPPKP harus 8–20 digit.'
})

const disableUpdate = computed(() => {
  const basicInvalid = !form.nama_vendor.trim() || !form.inisial.trim()
  return loading.value || basicInvalid || !!npwpError.value || !!nibError.value || !!sppkpError.value
})

// ====== FILE HANDLERS ======
function guardSize(input: HTMLInputElement, f: File|null) {
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    input.value = ''
    return null
  }
  return f
}
function onFileChange(e: Event, kind: 'npwp'|'nib'|'sppkp'|'bank'|'profile') {
  const i = e.target as HTMLInputElement
  const f = guardSize(i, i.files?.[0] || null)
  if (f) {
    if (kind === 'npwp')   removeFlags.npwp_file = false
    if (kind === 'nib')    removeFlags.nib_file = false
    if (kind === 'sppkp')  removeFlags.sppkp_file = false
    if (kind === 'bank')   removeFlags.bank_account_letter_file = false
    if (kind === 'profile')removeFlags.company_profile_file = false
  }
  newFiles[kind] = f
}
function clearNewFile(kind: 'npwp'|'nib'|'sppkp'|'bank'|'profile') {
  newFiles[kind] = null
  const input = document.getElementById(
    kind === 'npwp' ? 'npwp_file' :
    kind === 'nib' ? 'nib_file' :
    kind === 'sppkp' ? 'sppkp_file' :
    kind === 'bank' ? 'bank_letter_file' : 'company_profile_file'
  ) as HTMLInputElement | null
  if (input) input.value = ''
}
function fileUrl(path: string | null) {
  if (!path) return '#'
  return `/storage/${path}` // storage:link
}

// ====== INIT ======
onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/vendors/${id}`)
    form.nama_vendor   = data.nama_vendor || ''
    form.inisial       = data.inisial || ''
    form.catatan       = data.catatan || ''
    form.is_active     = !!data.is_active
    form.lastupdate_by = auth.user?.name || ''

    form.npwp_number   = (String(data.npwp_number || '').replace(/\D/g, '')).slice(0,16)
    form.nib_number    = (String(data.nib_number  || '').replace(/\D/g, '')).slice(0,20)
    form.sppkp_number  = (String(data.sppkp_number|| '').replace(/\D/g, '')).slice(0,20)
    npwpInput.value    = formatGroup4(form.npwp_number)
    nibInput.value     = form.nib_number
    sppkpInput.value   = form.sppkp_number

    currentFiles.npwp_file  = data.npwp_file || null
    currentFiles.nib_file   = data.nib_file || null
    currentFiles.sppkp_file = data.sppkp_file || null
    currentFiles.bank_account_letter_file = data.bank_account_letter_file || null
    currentFiles.company_profile_file     = data.company_profile_file || null
  } catch (e:any) {
    Swal.fire('Error','Gagal memuat data','error')
    router.back()
  }
})

// ====== SUBMIT (POST + _method=PUT) ======
async function submit() {
  tried.value = true
  fieldErrors.value = {}
  if (disableUpdate.value) return

  const fd = new FormData()
  fd.append('nama_vendor', form.nama_vendor)
  fd.append('inisial', form.inisial)
  fd.append('catatan', form.catatan ?? '')
  fd.append('is_active', form.is_active ? '1' : '0')
  fd.append('lastupdate_by', form.lastupdate_by || '')

  if (form.npwp_number)  fd.append('npwp_number', form.npwp_number)
  if (form.nib_number)   fd.append('nib_number', form.nib_number)
  if (form.sppkp_number) fd.append('sppkp_number', form.sppkp_number)

  if (newFiles.npwp)    fd.append('npwp_file', newFiles.npwp)
  if (newFiles.nib)     fd.append('nib_file', newFiles.nib)
  if (newFiles.sppkp)   fd.append('sppkp_file', newFiles.sppkp)
  if (newFiles.bank)    fd.append('bank_account_letter_file', newFiles.bank)
  if (newFiles.profile) fd.append('company_profile_file', newFiles.profile)

  if (removeFlags.npwp_file)  fd.append('remove_npwp_file', '1')
  if (removeFlags.nib_file)   fd.append('remove_nib_file', '1')
  if (removeFlags.sppkp_file) fd.append('remove_sppkp_file', '1')
  if (removeFlags.bank_account_letter_file) fd.append('remove_bank_account_letter_file', '1')
  if (removeFlags.company_profile_file)     fd.append('remove_company_profile_file', '1')

  // Kunci: spoof method PUT supaya FormData terbaca mulus di PHP
  fd.append('_method', 'PUT')

  loading.value = true
  try {
    // Biarkan browser set boundary; JANGAN set headers Content-Type manual
    await axios.post(`/api/vendors/${id}`, fd)

    Swal.fire({ icon:'success', title:'Vendor diperbarui', toast:true, position:'top-end', timer:1500 })
    router.push({ name: 'vendors-list' })
  } catch (e:any) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      fieldErrors.value = e.response.data.errors
      error.value = Object.values(e.response.data.errors).map((arr:any)=>arr[0]).join('\n')
    } else {
      error.value = e.response?.data?.message || 'Gagal menyimpan'
    }
  } finally {
    loading.value = false
  }
}

function cancel() { router.back() }
</script>

<style scoped>
.text-red-500 { font-size: 0.8125rem; }
</style>
