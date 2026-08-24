<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, maxLength, required } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormSwitch } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

type CustomerDocumentTypeItem = {
  id?: number
  code: string
  name: string
  is_active: boolean
}

const customerDocumentTypeApi = createResourceApi('/customer-document-types')
const { success, error } = useNotification()

const props = withDefaults(
  defineProps<{
    open: boolean
    mode: 'create' | 'edit'
    item?: CustomerDocumentTypeItem | null
  }>(),
  {
    item: null,
  },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', data: CustomerDocumentTypeItem, mode: 'create' | 'edit'): void
}>()

const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id: 0,
  code: '',
  name: '',
  is_active: true,
})

const serverErrors = reactive({
  code: '',
  name: '',
})

const rules = {
  code: {
    required: helpers.withMessage('Kode wajib diisi', required),
    maxLength: helpers.withMessage('Kode maksimal 100 karakter', maxLength(100)),
  },
  name: {
    required: helpers.withMessage('Nama wajib diisi', required),
    maxLength: helpers.withMessage('Nama maksimal 255 karakter', maxLength(255)),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create' ? 'Tambah Jenis Dokumen' : 'Edit Jenis Dokumen',
)

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan jenis dokumen customer baru ke sistem.'
    : 'Perbarui informasi jenis dokumen customer.',
)

const submitText = computed(() =>
  props.mode === 'create' ? 'Tambah' : 'Simpan',
)

const submitIcon = computed(() =>
  props.mode === 'create' ? 'PlusCircle' : 'Save',
)

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
      id: props.item.id ?? 0,
      code: props.item.code ?? '',
      name: props.item.name ?? '',
      is_active: props.item.is_active,
    })
    return
  }

  Object.assign(form, {
    id: 0,
    code: '',
    name: '',
    is_active: true,
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
  Object.assign(serverErrors, { code: '', name: '' })
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || v$.value[field].$errors[0]?.$message?.toString() || ''
}

function getFormPayload() {
  return {
    code: form.code,
    name: form.name,
    is_active: form.is_active,
  }
}

async function submitForm() {
  resetFormErrors()

  const isValid = await v$.value.$validate()

  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true

  try {
    const response =
      props.mode === 'create'
        ? await customerDocumentTypeApi.store(getFormPayload())
        : await customerDocumentTypeApi.update(form.id, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Jenis dokumen customer berhasil ditambahkan'
        : 'Jenis dokumen customer berhasil diperbarui',
    )

    emit('success', response.data, props.mode)
  } catch (e: any) {
    const status = e.response?.status
    const errors = e.response?.data?.errors

    if (status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in serverErrors) {
          serverErrors[key as keyof typeof serverErrors] = value?.[0] || ''
        }
      })
      formError.value = Object.values(errors).map((value: any) => value?.[0]).filter(Boolean).join('\n')
    } else {
      formError.value = e.response?.data?.message ?? 'Terjadi kesalahan saat menyimpan data'
    }

    error('Gagal', formError.value ?? undefined)
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
        <FormLabel htmlFor="document-type-code">Kode
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="document-type-code" v-model="form.code" placeholder="mis. nib"
          :class="getFieldError('code') ? 'border-rose-500' : ''" />
        <small v-if="getFieldError('code')" class="font-caption !text-rose-600">{{ getFieldError('code') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="document-type-name">Nama
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="document-type-name" v-model="form.name" placeholder="mis. NIB"
          :class="getFieldError('name') ? 'border-rose-500' : ''" />
        <small v-if="getFieldError('name')" class="font-caption !text-rose-600">{{ getFieldError('name') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="document-type-status">Status</FormLabel>
        <div class="mt-2 flex items-center gap-3">
          <FormSwitch>
            <FormSwitch.Input id="document-type-status" v-model="form.is_active" type="checkbox" />
          </FormSwitch>
          <span class="font-body">
            {{ form.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>
    </div>
  </FormModal>
</template>
