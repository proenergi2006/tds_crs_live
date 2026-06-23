<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, maxLength, required } from '@vuelidate/validators'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormSwitch, FormTextarea } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'

type FileKey = 'npwp' | 'nib' | 'sppkp' | 'bank' | 'profile'
type FileField =
  | 'npwp_file'
  | 'nib_file'
  | 'sppkp_file'
  | 'bank_account_letter_file'
  | 'company_profile_file'

type ExistingFile = {
  id?: string | number;
  name: string;
  url?: string;
}

const router = useRouter()
const route = useRoute()
const { success, error: notifyError } = useNotification()
const vendorApi = createResourceApi('/vendors')

const vendorId = computed(() => Number(route.params.id || 0))
const mode = computed<'create' | 'edit'>(() => vendorId.value ? 'edit' : 'create')
const loading = ref(false)
const pageLoading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  nama_vendor: '',
  inisial: '',
  catatan: '',
  is_active: true,
  npwp_number: '',
  nib_number: '',
  sppkp_number: '',
  npwp_file: null as string | null,
  nib_file: null as string | null,
  sppkp_file: null as string | null,
  bank_account_letter_file: null as string | null,
  company_profile_file: null as string | null,
  created_by: '',
  lastupdate_by: '',
})

const serverErrors = reactive({
  nama_vendor: '',
  inisial: '',
  npwp_number: '',
  nib_number: '',
  sppkp_number: '',
})

const optionalLengthBetween = (min: number, max: number) =>
  helpers.withParams(
    { type: 'optionalLengthBetween', min, max },
    (value: unknown) => {
      if (!helpers.req(value)) return true

      const length = String(value).length
      return length >= min && length <= max
    },
  )

const optionalExactLength = (length: number) =>
  helpers.withParams(
    { type: 'optionalExactLength', length },
    (value: unknown) => !helpers.req(value) || String(value).length === length,
  )

const rules = {
  nama_vendor: {
    required: helpers.withMessage('Nama Vendor wajib diisi', required),
  },
  inisial: {
    required: helpers.withMessage('Inisial wajib diisi', required),
    maxLength: helpers.withMessage('Inisial maksimal 10 karakter', maxLength(10)),
  },
  npwp_number: {
    exactLength: helpers.withMessage('NPWP harus 16 digit angka.', optionalExactLength(16)),
  },
  nib_number: {
    lengthBetween: helpers.withMessage('Nomor NIB/TDP/SIUP harus 10-20 digit.', optionalLengthBetween(10, 20)),
  },
  sppkp_number: {
    lengthBetween: helpers.withMessage('Nomor SPPKP harus 8-20 digit.', optionalLengthBetween(8, 20)),
  },
}

const v$ = useVuelidate(rules, form)

const files = reactive<Record<FileKey, File | null>>({
  npwp: null,
  nib: null,
  sppkp: null,
  bank: null,
  profile: null,
})

const removeFlags = reactive<Record<FileField, boolean>>({
  npwp_file: false,
  nib_file: false,
  sppkp_file: false,
  bank_account_letter_file: false,
  company_profile_file: false,
})

const fileConfigs: Array<{
  key: FileKey;
  field: FileField;
  removeField: string;
  label: string;
}> = [
    {
      key: 'npwp',
      field: 'npwp_file',
      removeField: 'remove_npwp_file',
      label: 'Lampiran NPWP',
    },
    {
      key: 'nib',
      field: 'nib_file',
      removeField: 'remove_nib_file',
      label: 'Lampiran NIB/TDP/SIUP',
    },
    {
      key: 'sppkp',
      field: 'sppkp_file',
      removeField: 'remove_sppkp_file',
      label: 'Lampiran SPPKP',
    },
    {
      key: 'bank',
      field: 'bank_account_letter_file',
      removeField: 'remove_bank_account_letter_file',
      label: 'Surat Pernyataan / Rek Giro / Scan Buku Rekening',
    },
    {
      key: 'profile',
      field: 'company_profile_file',
      removeField: 'remove_company_profile_file',
      label: 'Company Profile',
    },
  ]

const pageTitle = computed(() => mode.value === 'create' ? 'Tambah Vendor' : 'Edit Vendor')
const pageDescription = computed(() =>
  mode.value === 'create'
    ? 'Tambahkan vendor baru beserta dokumen pendukungnya.'
    : 'Perbarui informasi vendor dan dokumen pendukungnya.',
)
const submitText = computed(() => mode.value === 'create' ? 'Tambah Vendor' : 'Simpan Perubahan')
const updatedByInfo = computed(() => {
  if (!form.lastupdate_by) return `Created By ${form.created_by || 'N/A'}`

  return `Last Updated By ${form.lastupdate_by || 'N/A'}`
})

const npwpInput = computed({
  get: () => formatGroup4(form.npwp_number),
  set: value => {
    form.npwp_number = onlyDigits(value, 16)
  },
})

const nibInput = computed({
  get: () => form.nib_number,
  set: value => {
    form.nib_number = onlyDigits(value, 20)
  },
})

const sppkpInput = computed({
  get: () => form.sppkp_number,
  set: value => {
    form.sppkp_number = onlyDigits(value, 20)
  },
})

onMounted(() => {
  if (mode.value === 'edit') {
    fetchVendor()
  }
})

async function fetchVendor() {
  pageLoading.value = true

  try {
    const { data } = await vendorApi.getById(vendorId.value)

    Object.assign(form, {
      nama_vendor: data.nama_vendor || '',
      inisial: data.inisial || '',
      catatan: data.catatan || '',
      is_active: !!data.is_active,
      npwp_number: onlyDigits(String(data.npwp_number || ''), 16),
      nib_number: onlyDigits(String(data.nib_number || ''), 20),
      sppkp_number: onlyDigits(String(data.sppkp_number || ''), 20),
      npwp_file: data.npwp_file || null,
      nib_file: data.nib_file || null,
      sppkp_file: data.sppkp_file || null,
      bank_account_letter_file: data.bank_account_letter_file || null,
      company_profile_file: data.company_profile_file || null,
      created_by: data.created_by || '',
      lastupdate_by: data.lastupdate_by || '',
    })
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data vendor')
    router.push({ name: 'vendors-list' })
  } finally {
    pageLoading.value = false
  }
}

function onlyDigits(value: string, maxLength: number) {
  return value.replace(/\D/g, '').slice(0, maxLength)
}

function formatGroup4(value: string) {
  return value.replace(/(\d{4})(?=\d)/g, '$1 ').trim()
}

function resetErrors() {
  formError.value = null
  v$.value.$reset()
  Object.assign(serverErrors, {
    nama_vendor: '',
    inisial: '',
    npwp_number: '',
    nib_number: '',
    sppkp_number: '',
  })
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || v$.value[field].$errors[0]?.$message?.toString() || ''
}

function existingFile(config: typeof fileConfigs[number]): ExistingFile[] {
  const path = form[config.field]

  if (!path || removeFlags[config.field]) return []

  return [
    {
      id: config.field,
      name: fileName(path),
      url: `/storage/${path}`,
    },
  ]
}

function fileName(path: string) {
  return path.split('/').pop() || path
}

function markExistingFileRemoved(config: typeof fileConfigs[number]) {
  removeFlags[config.field] = true
}

function appendIfFilled(fd: FormData, key: string, value: string) {
  if (value) fd.append(key, value)
}

function buildPayload() {
  const fd = new FormData()

  fd.append('nama_vendor', form.nama_vendor)
  fd.append('inisial', form.inisial)
  fd.append('catatan', form.catatan || '')
  fd.append('is_active', form.is_active ? '1' : '0')

  appendIfFilled(fd, 'npwp_number', form.npwp_number)
  appendIfFilled(fd, 'nib_number', form.nib_number)
  appendIfFilled(fd, 'sppkp_number', form.sppkp_number)

  fileConfigs.forEach(config => {
    const file = files[config.key]

    if (file) fd.append(config.field, file)
    if (mode.value === 'edit' && removeFlags[config.field]) {
      fd.append(config.removeField, '1')
    }
  })

  return fd
}

async function submit() {
  resetErrors()
  const isValid = await v$.value.$validate()

  if (!isValid) {
    notifyError('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true

  try {
    const payload = buildPayload()

    if (mode.value === 'create') {
      await vendorApi.store(payload)
    } else {
      await vendorApi.updateMultipart(vendorId.value, payload)
    }

    success(
      'Berhasil',
      mode.value === 'create'
        ? 'Vendor berhasil ditambahkan'
        : 'Vendor berhasil diperbarui',
    )
    router.push({ name: 'vendors-list' })
  } catch (e: any) {
    const errors = e.response?.data?.errors

    if (e.response?.status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in serverErrors) {
          serverErrors[key as keyof typeof serverErrors] = value?.[0] || ''
        }
      })
      formError.value = Object.values(errors).map((value: any) => value?.[0]).filter(Boolean).join('\n')
    } else {
      formError.value = e.response?.data?.message ?? 'Terjadi kesalahan'
    }
  } finally {
    loading.value = false
  }
}

function cancel() {
  if (loading.value) return

  router.push({ name: 'vendors-list' })
}
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="full" :loading="loading || pageLoading"
    :error="formError" :submit-text="submitText" submit-icon="Save" @cancel="cancel" @submit="submit">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <div class="space-y-5">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <FormLabel htmlFor="vendor-nama">Nama Vendor
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="vendor-nama" v-model="form.nama_vendor" placeholder="Nama Vendor"
            :class="getFieldError('nama_vendor') ? 'border-rose-500' : ''" />
          <small v-if="getFieldError('nama_vendor')" class="font-caption !text-rose-600">{{ getFieldError('nama_vendor') }}</small>
        </div>

        <div>
          <FormLabel htmlFor="vendor-inisial">Inisial
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="vendor-inisial" v-model="form.inisial" placeholder="Inisial" maxlength="10"
            :class="getFieldError('inisial') ? 'border-rose-500' : ''" />
          <small v-if="getFieldError('inisial')" class="font-caption !text-rose-600">{{ getFieldError('inisial') }}</small>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 p-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div>
            <FormLabel htmlFor="vendor-npwp">NPWP (16 digit)</FormLabel>
            <FormInput id="vendor-npwp" v-model="npwpInput" placeholder="Masukkan NPWP"
              :class="getFieldError('npwp_number') ? 'border-rose-500' : ''" inputmode="numeric" autocomplete="off" />
            <small v-if="getFieldError('npwp_number')" class="font-caption !text-rose-600">{{ getFieldError('npwp_number') }}</small>
            <small v-else class="text-slate-500">Disimpan sebagai 16 digit angka tanpa pemisah.</small>
          </div>

          <FileUploadField v-model="files.npwp" :existing-files="existingFile(fileConfigs[0])"
            :label="fileConfigs[0].label" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="4"
            @remove-existing="markExistingFileRemoved(fileConfigs[0])" />
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 p-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div>
            <FormLabel htmlFor="vendor-nib">NIB / TDP / SIUP (10-20 digit)</FormLabel>
            <FormInput id="vendor-nib" v-model="nibInput" placeholder="Masukkan nomor NIB/TDP/SIUP"
              :class="getFieldError('nib_number') ? 'border-rose-500' : ''" inputmode="numeric" autocomplete="off" />
            <small v-if="getFieldError('nib_number')" class="font-caption !text-rose-600">{{ getFieldError('nib_number') }}</small>
          </div>

          <FileUploadField v-model="files.nib" :existing-files="existingFile(fileConfigs[1])"
            :label="fileConfigs[1].label" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="4"
            @remove-existing="markExistingFileRemoved(fileConfigs[1])" />
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 p-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div>
            <FormLabel htmlFor="vendor-sppkp">SPPKP (8-20 digit)</FormLabel>
            <FormInput id="vendor-sppkp" v-model="sppkpInput" placeholder="Masukkan nomor SPPKP"
              :class="getFieldError('sppkp_number') ? 'border-rose-500' : ''" inputmode="numeric" autocomplete="off" />
            <small v-if="getFieldError('sppkp_number')" class="font-caption !text-rose-600">{{ getFieldError('sppkp_number')
              }}</small>
          </div>

          <FileUploadField v-model="files.sppkp" :existing-files="existingFile(fileConfigs[2])"
            :label="fileConfigs[2].label" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="4"
            @remove-existing="markExistingFileRemoved(fileConfigs[2])" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <FileUploadField v-for="config in fileConfigs.slice(3)" :key="config.key" v-model="files[config.key]"
          :existing-files="existingFile(config)" :label="config.label" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="4"
          @remove-existing="markExistingFileRemoved(config)" />
      </div>

      <div>
        <FormLabel htmlFor="vendor-catatan">Catatan</FormLabel>
        <FormTextarea id="vendor-catatan" v-model="form.catatan" rows="3" placeholder="Catatan (opsional)" />
      </div>

      <div>
        <FormLabel htmlFor="vendor-status">Status</FormLabel>
        <div class="mt-2 flex items-center gap-3">
          <FormSwitch>
            <FormSwitch.Input id="vendor-status" v-model="form.is_active" type="checkbox" />
          </FormSwitch>
          <span class="font-body">
            {{ form.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>

      <div v-if="mode === 'edit'">
        <p class="font-caption text-right mt-6">
          <i>* {{ updatedByInfo }}</i>
        </p>
      </div>
    </div>
  </FormPage>
</template>
