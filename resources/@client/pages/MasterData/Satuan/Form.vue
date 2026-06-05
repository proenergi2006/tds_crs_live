<script setup lang="ts">
import FormModal from '@/components/SystemDesign/Form/FormModal.vue';
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue';
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    form: {
      nama_satuan: string;
      deskripsi: string;
      is_active: boolean;
      created_by?: string;
      lastupdate_by?: string;
    };
    satuans: any[];
    loading?: boolean;
    error?: string | null;
    fieldErrors?: any;
  }>(),
  {
    loading: false,
    error: null,
    fieldErrors: {},
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'submit'): void;
}>();

const modalTitle = computed(() =>
  props.mode === 'create'
    ? 'Tambah Satuan'
    : 'Edit Satuan',
);

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan satuan baru ke sistem.'
    : 'Perbarui informasi satuan.',
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

const updatedByInfo = computed(() => {
  if (props.form.lastupdate_by === null || props.form.lastupdate_by === '') {
    return `Created By ${props.form.created_by || 'N/A'}`;
  } else {
    return `Last Updated By ${props.form.lastupdate_by || 'N/A'}`;
  }
});
</script>

<template>
  <FormModal :open="open" :title="modalTitle" :description="modalDescription" :loading="loading" :error="error"
    :submit-text="submitText" :submit-icon="submitIcon" @close="$emit('close')" @submit="$emit('submit')">
    <div class="space-y-3">
      <!-- Nama Satuan (required) -->
      <div>
        <FormLabel htmlFor="edit-nama">Nama Satuan <RequiredAsterisk /></FormLabel>
        <FormInput id="edit-nama" v-model="form.nama_satuan" placeholder="Nama Satuan"
          :class="fieldErrors.nama_satuan ? 'border-rose-500' : ''" required />
        <small v-if="fieldErrors.nama_satuan" class="text-rose-600">{{ fieldErrors.nama_satuan
        }}</small>
      </div>

      <!-- Deskripsi (optional) -->
      <div>
        <FormLabel htmlFor="edit-deskripsi">Deskripsi</FormLabel>
        <FormInput id="edit-deskripsi" v-model="form.deskripsi" placeholder="Deskripsi" />
      </div>

      <!-- Status (required) -->
      <div>
        <FormLabel htmlFor="edit-status">Status <RequiredAsterisk /></FormLabel>
        <FormSelect id="edit-status" v-model="form.is_active" :class="fieldErrors.is_active ? 'border-rose-500' : ''"
          required>
          <option :value="true">Active</option>
          <option :value="false">Inactive</option>
        </FormSelect>
        <small v-if="fieldErrors.is_active" class="text-rose-600">{{ fieldErrors.is_active }}</small>
      </div>

      <!-- Updated By -->
      <div v-if="props.mode === 'edit'">
        <p class="text-sm text-right text-xs text-gray-500 mt-6">
          <i>* {{ updatedByInfo }}</i>
        </p>
      </div>
    </div>
  </FormModal>
</template>
