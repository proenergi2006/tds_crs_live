<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12 lg:col-span-8">
        <div class="p-5 box">
          <h2 class="text-lg font-medium mb-4">Add Vendor</h2>

          <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>

          <div class="space-y-5">
            <!-- Nama Vendor -->
            <div>
              <FormLabel for="nama">Nama Vendor</FormLabel>
              <FormInput id="nama" v-model="form.nama_vendor" placeholder="Nama Vendor" />
            </div>

            <!-- Inisial -->
            <div>
              <FormLabel for="inisial">Inisial</FormLabel>
              <FormInput id="inisial" v-model="form.inisial" placeholder="Inisial (max 10)" maxlength="10" />
            </div>

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
              <p v-if="npwpError" class="text-red-500 text-sm mt-1">{{ npwpError }}</p>
              <p v-else class="text-slate-500 text-xs mt-1">Disimpan sebagai 16 digit angka tanpa pemisah.</p>
            </div>

            <!-- Lampiran NPWP -->
            <div>
              <FormLabel for="npwp_file">Lampiran NPWP (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <input id="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" @change="onNpwpFileChange" />
              <p v-if="npwpFileName" class="text-xs text-slate-600 mt-1">File: {{ npwpFileName }}</p>
            </div>

            <hr class="my-2">

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
              <p v-if="nibError" class="text-red-500 text-sm mt-1">{{ nibError }}</p>
              <p v-else class="text-slate-500 text-xs mt-1">Disimpan sebagai digit tanpa pemisah.</p>
            </div>

            <!-- Lampiran NIB -->
            <div>
              <FormLabel for="nib_file">Lampiran NIB/TDP/SIUP (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <input id="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" @change="onNibFileChange" />
              <p v-if="nibFileName" class="text-xs text-slate-600 mt-1">File: {{ nibFileName }}</p>
            </div>

            <hr class="my-2">

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
              <p v-if="sppkpError" class="text-red-500 text-sm mt-1">{{ sppkpError }}</p>
              <p v-else class="text-slate-500 text-xs mt-1">Disimpan sebagai digit tanpa pemisah.</p>
            </div>

            <!-- Lampiran SPPKP -->
            <div>
              <FormLabel for="sppkp_file">Lampiran SPPKP (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <input id="sppkp_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" @change="onSppkpFileChange" />
              <p v-if="sppkpFileName" class="text-xs text-slate-600 mt-1">File: {{ sppkpFileName }}</p>
            </div>

            <hr class="my-2">

            <!-- Surat Pernyataan/Rekening/Giro / Scan Buku Rekening -->
            <div>
              <FormLabel for="bank_letter_file">Surat Pernyataan / Rek Giro / Scan Buku Rekening (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <input id="bank_letter_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" @change="onBankFileChange" />
              <p v-if="bankFileName" class="text-xs text-slate-600 mt-1">File: {{ bankFileName }}</p>
            </div>

            <!-- Company Profile -->
            <div>
              <FormLabel for="company_profile_file">Company Profile (PDF/JPG/PNG, maks 4MB)</FormLabel>
              <input id="company_profile_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-3 py-2" @change="onProfileFileChange" />
              <p v-if="profileFileName" class="text-xs text-slate-600 mt-1">File: {{ profileFileName }}</p>
            </div>

            <!-- Catatan -->
            <div>
              <FormLabel for="catatan">Catatan</FormLabel>
              <textarea id="catatan" v-model="form.catatan" rows="3" class="w-full border rounded px-3 py-2" placeholder="Catatan (opsional)"></textarea>
            </div>

            <!-- Active -->
            <div class="flex items-center space-x-2">
              <FormSwitch>
                <FormSwitch.Input type="checkbox" v-model="form.is_active"/>
              </FormSwitch>
              <span>Active</span>
            </div>

            <!-- Created by -->
            <div>
              <FormLabel for="creator">Created By</FormLabel>
              <FormInput id="creator" v-model="form.created_by" readonly class="bg-gray-100 cursor-not-allowed" />
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <Button variant="outline-secondary" @click="cancel">Cancel</Button>
            <Button
              variant="primary"
              :loading="loading"
              :disabled="disableSave"
              @click="submit"
            >Save</Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'
import { FormInput, FormLabel, FormSwitch } from '@/components/Base/Form'
import Button from '@/components/Base/Button'

const router = useRouter()
const auth   = useAuthStore()

// ===================
// Form state (no file)
// ===================
const form = reactive({
  nama_vendor: '',
  inisial:     '',
  catatan:     '',
  is_active:   true,
  created_by:  '',

  npwp_number: '',
  nib_number:  '',
  sppkp_number:'',
})

// ==============
// NPWP handling
// ==============
const npwpInput = ref('')
const npwpFile  = ref<File|null>(null)
const npwpFileName = computed(() => npwpFile.value?.name ?? '')

function formatNPWP16(digits: string) {
  // opsi: tampil 4 4 4 4
  return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim()
}
function onNpwpInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 16)
  form.npwp_number = digits
  npwpInput.value = formatNPWP16(digits)
}
const npwpError = computed(() => {
  if (!form.npwp_number) return '' // opsional
  return form.npwp_number.length === 16 ? '' : 'NPWP harus 16 digit angka.'
})
function onNpwpFileChange(e: Event) {
  const i = e.target as HTMLInputElement
  const f = i.files?.[0] || null
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    i.value = ''
    npwpFile.value = null
    return
  }
  npwpFile.value = f
}

// ==================
// NIB / TDP / SIUP
// ==================
const nibInput = ref('')
const nibFile  = ref<File|null>(null)
const nibFileName = computed(() => nibFile.value?.name ?? '')

function onNibInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 20)
  form.nib_number = digits
  nibInput.value = digits
}
const nibError = computed(() => {
  if (!form.nib_number) return '' // opsional
  const len = form.nib_number.length
  return len >= 10 && len <= 20 ? '' : 'Nomor NIB/TDP/SIUP harus 10–20 digit.'
})
function onNibFileChange(e: Event) {
  const i = e.target as HTMLInputElement
  const f = i.files?.[0] || null
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    i.value = ''
    nibFile.value = null
    return
  }
  nibFile.value = f
}

// ======
// SPPKP
// ======
const sppkpInput = ref('')
const sppkpFile  = ref<File|null>(null)
const sppkpFileName = computed(() => sppkpFile.value?.name ?? '')

function onSppkpInput(val: string) {
  const digits = val.replace(/\D/g, '').slice(0, 20)
  form.sppkp_number = digits
  sppkpInput.value = digits
}
const sppkpError = computed(() => {
  if (!form.sppkp_number) return '' // opsional
  const len = form.sppkp_number.length
  return len >= 8 && len <= 20 ? '' : 'Nomor SPPKP harus 8–20 digit.'
})
function onSppkpFileChange(e: Event) {
  const i = e.target as HTMLInputElement
  const f = i.files?.[0] || null
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    i.value = ''
    sppkpFile.value = null
    return
  }
  sppkpFile.value = f
}

// ============================
// Surat Pernyataan / Rekening
// ============================
const bankFile = ref<File|null>(null)
const bankFileName = computed(() => bankFile.value?.name ?? '')
function onBankFileChange(e: Event) {
  const i = e.target as HTMLInputElement
  const f = i.files?.[0] || null
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    i.value = ''
    bankFile.value = null
    return
  }
  bankFile.value = f
}

// ================
// Company Profile
// ================
const profileFile = ref<File|null>(null)
const profileFileName = computed(() => profileFile.value?.name ?? '')
function onProfileFileChange(e: Event) {
  const i = e.target as HTMLInputElement
  const f = i.files?.[0] || null
  if (f && f.size > 4 * 1024 * 1024) {
    Swal.fire('Error', 'Ukuran file melebihi 4MB', 'error')
    i.value = ''
    profileFile.value = null
    return
  }
  profileFile.value = f
}

// ==========
// Utilities
// ==========
const loading = ref(false)
const error   = ref('')
const disableSave = computed(() =>
  loading.value ||
  !form.nama_vendor.trim() ||
  !form.inisial.trim() ||
  !!npwpError.value || !!nibError.value || !!sppkpError.value
)

onMounted(() => {
  form.created_by = auth.user?.name || ''
})

async function submit() {
  error.value = ''
  if (!form.nama_vendor.trim())  return Swal.fire('Error','Nama Vendor wajib diisi','error')
  if (!form.inisial.trim())      return Swal.fire('Error','Inisial wajib diisi','error')
  if (npwpError.value)           return Swal.fire('Error', npwpError.value, 'error')
  if (nibError.value)            return Swal.fire('Error', nibError.value, 'error')
  if (sppkpError.value)          return Swal.fire('Error', sppkpError.value, 'error')

  const fd = new FormData()
  fd.append('nama_vendor', form.nama_vendor)
  fd.append('inisial', form.inisial)
  fd.append('catatan', form.catatan ?? '')
  fd.append('is_active', form.is_active ? '1' : '0')

  if (form.npwp_number) fd.append('npwp_number', form.npwp_number)
  if (npwpFile.value)   fd.append('npwp_file', npwpFile.value)

  if (form.nib_number)  fd.append('nib_number', form.nib_number)
  if (nibFile.value)    fd.append('nib_file', nibFile.value)

  if (form.sppkp_number) fd.append('sppkp_number', form.sppkp_number)
  if (sppkpFile.value)   fd.append('sppkp_file', sppkpFile.value)

  if (bankFile.value)     fd.append('bank_account_letter_file', bankFile.value)
  if (profileFile.value)  fd.append('company_profile_file', profileFile.value)

  loading.value = true
  try {
    await axios.post('/api/vendors', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    Swal.fire({ icon: 'success', title: 'Vendor berhasil dibuat', toast:true, position:'top-end', timer:1500 })
    router.push({ name: 'vendors-list' })
  } catch (e:any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

function cancel() { router.back() }
</script>
