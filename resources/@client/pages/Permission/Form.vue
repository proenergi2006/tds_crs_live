<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, maxLength } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const permissionApi = createResourceApi('/permissions')
const { success, error } = useNotification()

/**
 * Edit-only modal: only `module` and `description` are editable.
 * `name` and `guard_name` are shown read-only for reference — they are
 * technical keys referenced elsewhere (e.g. router.meta.permission) and
 * must never be changed through this form.
 */
const props = withDefaults(
  defineProps<{
    open: boolean
    item?: {
      id: number
      name: string
      guard_name?: string
      module: string
      description?: string
    } | null
  }>(),
  {
    item: null,
  },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', data: any): void
}>()

const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id: 0,
  name: '',
  guard_name: '',
  module: '',
  description: '',
})

const rules = {
  module: {
    required: helpers.withMessage('Module wajib diisi', required),
    maxLength: helpers.withMessage('Module maksimal 100 karakter', maxLength(100)),
  },
  description: {
    maxLength: helpers.withMessage('Deskripsi maksimal 255 karakter', maxLength(255)),
  },
}

const v$ = useVuelidate(rules, form)

watch(
  () => [props.open, props.item],
  () => {
    if (props.open) {
      resetForm()
    }
  },
  { immediate: true },
)

function resetForm() {
  resetFormErrors()

  Object.assign(form, {
    id: props.item?.id ?? 0,
    name: props.item?.name ?? '',
    guard_name: props.item?.guard_name ?? '',
    module: props.item?.module ?? '',
    description: props.item?.description ?? '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'module' | 'description') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

async function submitForm() {
  const isValid = await v$.value.$validate()

  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  loading.value = true

  try {
    const response = await permissionApi.update(form.id, {
      module: form.module,
      description: form.description,
    })

    success('Berhasil', 'Permission berhasil diperbarui')
    emit('success', response.data.data)
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
  <FormModal :open="open" title="Edit Permission" description="Perbarui module dan deskripsi permission."
    :loading="loading" :error="formError" submit-text="Simpan" submit-icon="Save" @close="$emit('close')"
    @submit="submitForm">
    <div class="space-y-3">
      <div>
        <FormLabel htmlFor="edit-permission-name">Name</FormLabel>
        <div id="edit-permission-name" class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 font-num text-sm text-slate-600">
          {{ form.name }}
        </div>
      </div>

      <div>
        <FormLabel htmlFor="edit-permission-guard">Guard Name</FormLabel>
        <div id="edit-permission-guard" class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 font-num text-sm text-slate-600">
          {{ form.guard_name || '-' }}
        </div>
      </div>

      <div>
        <FormLabel htmlFor="edit-permission-module">Module
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="edit-permission-module" v-model="form.module" placeholder="Module"
          :class="v$.module.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.module.$error" class="font-caption !text-rose-600">{{ getFieldError('module') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="edit-permission-description">Deskripsi</FormLabel>
        <FormTextarea id="edit-permission-description" v-model="form.description" placeholder="Deskripsi"
          :class="v$.description.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.description.$error" class="font-caption !text-rose-600">{{ getFieldError('description') }}</small>
      </div>
    </div>
  </FormModal>
</template>
