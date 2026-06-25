<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { helpers, required } from '@vuelidate/validators';

import FormModal from '@/components/SystemDesign/Form/FormModal.vue';
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue';
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

const ukuranApi = createResourceApi('/ukurans')
const satuanApi = createResourceApi('/satuans')
const { success, error } = useNotification()
const auth = useAuthStore()

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    item?: {
      id_ukuran?: number;
      nama_ukuran: string;
      id_satuan: string | number;
      created_by?: string;
      lastupdate_by?: string;
    } | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'success', data: any, mode: 'create' | 'edit'): void;
}>();

const satuans = ref<any[]>([])
const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id_ukuran: 0,
  nama_ukuran: '',
  id_satuan: '',
  created_by: '',
  lastupdate_by: '',
})

const rules = {
  nama_ukuran: {
    required: helpers.withMessage('Nama Ukuran wajib diisi', required),
  },
  id_satuan: {
    required: helpers.withMessage('Satuan wajib dipilih', required),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create'
    ? 'Tambah Ukuran'
    : 'Edit Ukuran',
);

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan ukuran baru ke sistem.'
    : 'Perbarui informasi ukuran.',
);

const submitText = computed(() =>
  props.mode === 'create'
    ? 'Tambah'
    : 'Simpan',
);

const submitIcon = computed(() =>
  props.mode === 'create'
    ? 'PlusCircle'
    : 'Save',
);

const currentUserName = computed(() => auth.user?.name || '')

const updatedByInfo = computed(() => {
  if (form.lastupdate_by) {
    return `Last Updated By ${form.lastupdate_by}`;
  }

  return `Created By ${form.created_by || 'N/A'}`;
});

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
    const { data } = await satuanApi.getAll({ per_page: 100 })
    satuans.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data satuan')
  }
}

function resetForm() {
  resetFormErrors()

  if (props.mode === 'edit' && props.item) {
    Object.assign(form, {
      id_ukuran: props.item.id_ukuran ?? 0,
      nama_ukuran: props.item.nama_ukuran ?? '',
      id_satuan: props.item.id_satuan ?? '',
      created_by: props.item.created_by ?? '',
      lastupdate_by: props.item.lastupdate_by ?? '',
    })
    return
  }

  Object.assign(form, {
    id_ukuran: 0,
    nama_ukuran: '',
    id_satuan: '',
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'nama_ukuran' | 'id_satuan') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function getFormPayload() {
  return {
    nama_ukuran: form.nama_ukuran,
    id_satuan: form.id_satuan,
    ...(props.mode === 'create'
      ? { created_by: form.created_by || currentUserName.value }
      : { lastupdate_by: currentUserName.value }),
  }
}

async function submitForm() {
  const isValid = await v$.value.$validate()

  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang Anda masukkan')
    return
  }

  loading.value = true

  try {
    const response =
      props.mode === 'create'
        ? await ukuranApi.store(getFormPayload())
        : await ukuranApi.update(form.id_ukuran, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Ukuran berhasil ditambahkan'
        : 'Ukuran berhasil diperbarui',
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
      <!-- Nama Ukuran (required) -->
      <div>
        <FormLabel htmlFor="edit-nama">Nama Ukuran
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="edit-nama" v-model="form.nama_ukuran" placeholder="Nama Ukuran"
          :class="v$.nama_ukuran.$error ? 'border-rose-500' : ''" />
        <small v-if="v$.nama_ukuran.$error" class="font-caption !text-rose-600">{{ getFieldError('nama_ukuran')
          }}</small>
      </div>

      <!-- Satuan (required) -->
      <div>
        <FormLabel htmlFor="edit-satuan">Satuan
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="edit-satuan" v-model="form.id_satuan" :class="v$.id_satuan.$error ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Satuan --</option>
          <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">
            {{ s.nama_satuan }}
          </option>
        </FormSelect>
        <small v-if="v$.id_satuan.$error" class="font-caption !text-rose-600">{{ getFieldError('id_satuan') }}</small>
      </div>

      <!-- Updated By -->
      <div v-if="props.mode === 'edit'">
        <p class="font-caption text-right mt-6">
          <i>* {{ updatedByInfo }}</i>
        </p>
      </div>
    </div>
  </FormModal>
</template>
