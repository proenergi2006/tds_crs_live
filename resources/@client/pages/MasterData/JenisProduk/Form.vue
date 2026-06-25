<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormSwitch } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

const jenisProdukApi = createResourceApi('/jenis-produks')
const { success, error } = useNotification()
const auth = useAuthStore()

const props = withDefaults(
  defineProps<{
    open: boolean
    mode: 'create' | 'edit'
    item?: {
      id_jenis?: number
      nama: string
      deskripsi?: string
      is_active: boolean
      created_by?: string
      updated_by?: string
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

const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id_jenis: 0,
  nama: '',
  deskripsi: '',
  is_active: true,
  created_by: '',
  updated_by: '',
})

const rules = {
  nama: {
    required: helpers.withMessage('Nama Jenis Produk wajib diisi', required),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create' ? 'Tambah Jenis Produk' : 'Edit Jenis Produk',
)

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan jenis produk baru ke sistem.'
    : 'Perbarui informasi jenis produk.',
)

const submitText = computed(() =>
  props.mode === 'create' ? 'Tambah' : 'Simpan',
)

const submitIcon = computed(() =>
  props.mode === 'create' ? 'PlusCircle' : 'Save',
)

const currentUserName = computed(() => auth.user?.name || '')

const updatedByInfo = computed(() => {
  if (form.updated_by) {
    return `Last Updated By ${form.updated_by}`
  }

  return `Created By ${form.created_by || 'N/A'}`
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

function resetForm() {
  resetFormErrors()

  if (props.mode === 'edit' && props.item) {
    Object.assign(form, {
      id_jenis: props.item.id_jenis ?? 0,
      nama: props.item.nama ?? '',
      deskripsi: props.item.deskripsi ?? '',
      is_active: props.item.is_active,
      created_by: props.item.created_by ?? '',
      updated_by: props.item.updated_by ?? '',
    })
    return
  }

  Object.assign(form, {
    id_jenis: 0,
    nama: '',
    deskripsi: '',
    is_active: true,
    created_by: currentUserName.value,
    updated_by: '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'nama') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function getFormPayload() {
  return {
    nama: form.nama,
    deskripsi: form.deskripsi,
    is_active: form.is_active,
    ...(props.mode === 'create'
      ? { created_by: form.created_by || currentUserName.value }
      : { updated_by: currentUserName.value }),
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
        ? await jenisProdukApi.store(getFormPayload())
        : await jenisProdukApi.update(form.id_jenis, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Jenis Produk berhasil ditambahkan'
        : 'Jenis Produk berhasil diperbarui',
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
        <FormLabel htmlFor="edit-nama">Nama Jenis Produk
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="edit-nama" v-model="form.nama" placeholder="Nama Jenis Produk"
          :class="v$.nama.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.nama.$error" class="font-caption !text-rose-600">{{ getFieldError('nama') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="edit-deskripsi">Deskripsi</FormLabel>
        <FormInput id="edit-deskripsi" v-model="form.deskripsi" placeholder="Deskripsi" />
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
