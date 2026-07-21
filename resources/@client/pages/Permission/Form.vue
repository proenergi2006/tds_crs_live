<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, maxLength } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import TomSelect from '@/components/Base/TomSelect'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const permissionApi = createResourceApi('/permissions')
const { success, error } = useNotification()

const NAME_FORMAT_REGEX = /^[a-z0-9-]+(\.[a-z0-9-]+)+$/

/**
 * Create mode (item = null): `name` and `module` are editable — `name` must
 * follow the `module.action` kebab-case format, `guard_name` is never sent
 * (the backend forces it to `web`).
 *
 * Edit mode (item set): only `module` and `description` are editable.
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
    moduleOptions?: string[]
  }>(),
  {
    item: null,
    moduleOptions: () => [],
  },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', data: any): void
}>()

const loading = ref(false)
const formError = ref<string | null>(null)

const mode = computed<'create' | 'edit'>(() => (props.item ? 'edit' : 'create'))

const modalTitle = computed(() =>
  mode.value === 'create' ? 'Tambah Permission' : 'Edit Permission',
)

const modalDescription = computed(() =>
  mode.value === 'create'
    ? 'Tambahkan permission baru ke sistem.'
    : 'Perbarui module dan deskripsi permission.',
)

const submitText = computed(() => (mode.value === 'create' ? 'Tambah' : 'Simpan'))
const submitIcon = computed(() => (mode.value === 'create' ? 'PlusCircle' : 'Save'))

const form = reactive({
  id: 0,
  name: '',
  guard_name: '',
  module: '',
  description: '',
})

const rules = {
  name: {
    required: helpers.withMessage('Nama permission wajib diisi', (value: string) => {
      if (mode.value !== 'create') return true
      return !!value && value.trim().length > 0
    }),
    format: helpers.withMessage(
      'Format harus module.action, huruf kecil/angka/tanda hubung saja (contoh: produk-harga.create)',
      (value: string) => {
        if (mode.value !== 'create' || !value) return true
        return NAME_FORMAT_REGEX.test(value)
      },
    ),
    maxLength: helpers.withMessage('Nama maksimal 150 karakter', maxLength(150)),
  },
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

function getFieldError(field: 'name' | 'module' | 'description') {
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
    const response =
      mode.value === 'create'
        ? await permissionApi.store({
            name: form.name,
            module: form.module,
            description: form.description,
          })
        : await permissionApi.update(form.id, {
            module: form.module,
            description: form.description,
          })

    success(
      'Berhasil',
      mode.value === 'create'
        ? 'Permission berhasil ditambahkan'
        : 'Permission berhasil diperbarui',
    )
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
  <FormModal :open="open" :title="modalTitle" :description="modalDescription" :loading="loading" :error="formError"
    :submit-text="submitText" :submit-icon="submitIcon" @close="$emit('close')" @submit="submitForm">
    <div class="space-y-3">
      <template v-if="mode === 'create'">
        <div>
          <FormLabel htmlFor="permission-name">Name
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="permission-name" v-model="form.name" placeholder="produk-harga.create"
            :class="v$.name.$error ? 'border-rose-500' : ''" />
          <small class="font-caption text-slate-500">
            Format: module.action — huruf kecil, angka, dan tanda hubung saja.
          </small>
          <small v-if="v$.name.$error" class="font-caption !text-rose-600 block">{{ getFieldError('name') }}</small>
        </div>
      </template>

      <template v-else>
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
      </template>

      <div>
        <FormLabel htmlFor="permission-module">Module
          <RequiredAsterisk />
        </FormLabel>
        <TomSelect id="permission-module" v-model="form.module" class="w-full"
          :class="v$.module.$error ? 'border-rose-500' : ''" :options="{
            create: true,
            createOnBlur: true,
            placeholder: 'Pilih atau ketik module baru...',
            dropdownParent: 'body' as const,
          }">
          <option v-for="m in moduleOptions" :key="m" :value="m">{{ m }}</option>
        </TomSelect>
        <small v-if="v$.module.$error" class="font-caption !text-rose-600">{{ getFieldError('module') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="permission-description">Deskripsi</FormLabel>
        <FormTextarea id="permission-description" v-model="form.description" placeholder="Deskripsi"
          :class="v$.description.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.description.$error" class="font-caption !text-rose-600">{{ getFieldError('description') }}</small>
      </div>
    </div>
  </FormModal>
</template>
