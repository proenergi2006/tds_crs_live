<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormLabel, FormSwitch, FormTextarea } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

const roleApi = createResourceApi('/roles')
const { success, error } = useNotification()
const auth = useAuthStore()

const props = withDefaults(
  defineProps<{
    open: boolean
    mode: 'create' | 'edit'
    item?: {
      id?: number
      name: string
      role_desc?: string
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

const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id: 0,
  name: '',
  role_desc: '',
  is_active: true,
  created_by: '',
  lastupdate_by: '',
})

const rules = {
  name: {
    required: helpers.withMessage('Nama Role wajib diisi', required),
  },
  role_desc: {
    required: helpers.withMessage('Deskripsi Role wajib diisi', required),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create' ? 'Tambah Role' : 'Edit Role',
)

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan role baru ke sistem.'
    : 'Perbarui informasi role.',
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
      name: props.item.name ?? '',
      role_desc: props.item.role_desc ?? '',
      is_active: props.item.is_active,
      created_by: props.item.created_by ?? '',
      lastupdate_by: props.item.lastupdate_by ?? '',
    })
    return
  }

  Object.assign(form, {
    id: 0,
    name: '',
    role_desc: '',
    is_active: true,
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'name' | 'role_desc') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function getFormPayload() {
  return {
    name: form.name,
    role_desc: form.role_desc,
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
        ? await roleApi.store(getFormPayload())
        : await roleApi.update(form.id, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Role berhasil ditambahkan'
        : 'Role berhasil diperbarui',
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
        <FormLabel htmlFor="role-nama">Nama Role
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="role-nama" v-model="form.name" placeholder="Nama Role"
          :class="v$.name.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.name.$error" class="font-caption !text-rose-600">{{ getFieldError('name') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="role-deskripsi">Deskripsi
          <RequiredAsterisk />
        </FormLabel>
        <FormTextarea id="role-deskripsi" v-model="form.role_desc" placeholder="Deskripsi Role"
          :class="v$.role_desc.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.role_desc.$error" class="font-caption !text-rose-600">{{ getFieldError('role_desc') }}</small>
      </div>

      <div>
        <FormLabel htmlFor="role-status">Status</FormLabel>
        <div class="mt-2 flex items-center gap-3">
          <FormSwitch>
            <FormSwitch.Input id="role-status" v-model="form.is_active" type="checkbox" />
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
