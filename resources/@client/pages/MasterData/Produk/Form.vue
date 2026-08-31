<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormSelect, FormLabel, FormSwitch } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

const produkApi = createResourceApi('/produks')
const ukuranApi = createResourceApi('/ukurans')
const jenisApi = createResourceApi('/jenis-produks')
const { success, error } = useNotification()
const auth = useAuthStore()

const props = withDefaults(
  defineProps<{
    open: boolean
    mode: 'create' | 'edit'
    item?: {
      id_produk?: number
      nama_produk: string
      merk_dagang: string
      deskripsi?: string
      id_ukuran: number | string
      id_jenis: number | string
      is_active: boolean
      created_by?: string
      lastupdate_by?: string
    } | null
  }>(),
  {
    item: null,
  },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', data: any, mode: 'create' | 'edit'): void
}>()

const ukurans = ref<any[]>([])
const jenisProduks = ref<any[]>([])
const loading = ref(false)
const formError = ref<string | null>(null)

const DEFAULT_MERK_DAGANG = 'Crushed Stone'

const form = reactive({
  id_produk: 0,
  nama_produk: '',
  merk_dagang: DEFAULT_MERK_DAGANG,
  deskripsi: '',
  id_ukuran: '',
  id_jenis: '',
  is_active: true,
  created_by: '',
  lastupdate_by: '',
})

const rules = {
  nama_produk: {
    required: helpers.withMessage('Nama Produk wajib diisi', required),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create' ? 'Tambah Produk' : 'Edit Produk',
)

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan produk baru ke sistem.'
    : 'Perbarui informasi produk.',
)

const submitText = computed(() =>
  props.mode === 'create' ? 'Tambah' : 'Simpan',
)

const submitIcon = computed(() =>
  props.mode === 'create' ? 'PlusCircle' : 'Save',
)

const currentUserName = computed(() => auth.user?.name || '')

const updatedByInfo = computed(() => {
  if (form.lastupdate_by) {
    return `Last Updated By ${form.lastupdate_by}`
  }

  return `Created By ${form.created_by || 'N/A'}`
})

onMounted(() => {
  initFormDependencies()
})

watch(
  () => [props.open, props.mode, props.item],
  () => {
    if (props.open) {
      resetForm()
    }
  },
  { immediate: true },
)

async function initFormDependencies() {
  try {
    const { data } = await ukuranApi.getAll({ per_page: 100 })
    ukurans.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data ukuran')
  }

  try {
    const { data } = await jenisApi.getAll({ per_page: 100 })
    jenisProduks.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data jenis produk')
  }
}

function resetForm() {
  resetFormErrors()

  if (props.mode === 'edit' && props.item) {
    Object.assign(form, {
      id_produk: props.item.id_produk ?? 0,
      nama_produk: props.item.nama_produk ?? '',
      merk_dagang: props.item.merk_dagang ?? DEFAULT_MERK_DAGANG,
      deskripsi: props.item.deskripsi ?? '',
      id_ukuran: props.item.id_ukuran ?? '',
      id_jenis: props.item.id_jenis ?? '',
      is_active: props.item.is_active,
      created_by: props.item.created_by ?? '',
      lastupdate_by: props.item.lastupdate_by ?? '',
    })
    return
  }

  Object.assign(form, {
    id_produk: 0,
    nama_produk: '',
    merk_dagang: DEFAULT_MERK_DAGANG,
    deskripsi: '',
    id_ukuran: '',
    id_jenis: '',
    is_active: true,
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'nama_produk') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function getFormPayload() {
  return {
    nama_produk: form.nama_produk,
    merk_dagang: form.merk_dagang,
    deskripsi: form.deskripsi,
    id_ukuran: form.id_ukuran || null,
    id_jenis: form.id_jenis || null,
    is_active: form.is_active,
    ...(props.mode === 'create'
      ? { created_by: form.created_by || currentUserName.value }
      : { lastupdate_by: currentUserName.value }),
  }
}

async function submitForm() {
  const isValid = await v$.value.$validate()

  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true

  try {
    const response =
      props.mode === 'create'
        ? await produkApi.store(getFormPayload())
        : await produkApi.update(form.id_produk, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Produk berhasil ditambahkan'
        : 'Produk berhasil diperbarui',
    )

    emit('success', response.data, props.mode)
  } catch (e: any) {
    const message = e.response?.data?.message ?? 'Terjadi kesalahan'
    formError.value = message
    error('Gagal', message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <FormModal :open="open" :title="modalTitle" :description="modalDescription" :loading="loading" :error="formError"
    :submit-text="submitText" :submit-icon="submitIcon" @close="$emit('close')" @submit="submitForm">
    <div class="space-y-3">
      <div>
        <FormLabel htmlFor="edit-nama">Nama Produk
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="edit-nama" v-model="form.nama_produk" placeholder="Nama Produk"
          :class="v$.nama_produk.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.nama_produk.$error" class="font-caption !text-rose-600">{{ getFieldError('nama_produk') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="edit-merk">Merk Dagang</FormLabel>
        <FormInput id="edit-merk" v-model="form.merk_dagang" placeholder="Merk Dagang" />
      </div>

      <div>
        <FormLabel htmlFor="edit-deskripsi">Deskripsi</FormLabel>
        <FormInput id="edit-deskripsi" v-model="form.deskripsi" placeholder="Deskripsi" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <FormLabel htmlFor="edit-ukuran">Ukuran</FormLabel>
          <FormSelect id="edit-ukuran" v-model="form.id_ukuran">
            <option value="">-- Pilih Ukuran --</option>
            <option v-for="u in ukurans" :key="u.id_ukuran" :value="u.id_ukuran">
              {{ u.nama_ukuran }} ({{ u.satuan?.nama_satuan || '-' }})
            </option>
          </FormSelect>
        </div>

        <div>
          <FormLabel htmlFor="edit-jenis">Jenis Produk</FormLabel>
          <FormSelect id="edit-jenis" v-model="form.id_jenis">
            <option value="">-- Pilih Jenis Produk --</option>
            <option v-for="j in jenisProduks" :key="j.id_jenis" :value="j.id_jenis">
              {{ j.nama }}
            </option>
          </FormSelect>
        </div>
      </div>

      <div>
        <FormLabel htmlFor="edit-status">Status</FormLabel>
        <div class="mt-2 flex items-center gap-3">
          <FormSwitch>
            <FormSwitch.Input id="edit-status" v-model="form.is_active" type="checkbox" />
          </FormSwitch>
          <span class="font-body">
            {{ form.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>

      <div v-if="props.mode === 'edit'">
        <p class="font-caption text-right mt-6">
          <i>* {{ updatedByInfo }}</i>
        </p>
      </div>
    </div>
  </FormModal>
</template>
