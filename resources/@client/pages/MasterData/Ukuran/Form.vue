<script setup lang="ts">
import FormModal from '@/components/SystemDesign/Form/FormModal.vue';
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    form: {
      nama_ukuran: string;
      id_satuan: string | number;
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

const updatedByInfo = computed(() => {
  if (props.form.lastupdate_by !== null) {
    return `Last Updated By ${props.form.lastupdate_by || 'N/A'}`;
  } else {
    return `Created By ${props.form.created_by || 'N/A'}`;
  }
});
</script>

<template>
  <FormModal :open="open" :title="modalTitle" :description="modalDescription" :loading="loading" :error="error"
    :submit-text="submitText" :submit-icon="submitIcon" @close="$emit('close')" @submit="$emit('submit')">
    <div class="space-y-3">
      <!-- Nama Ukuran (required) -->
      <div>
        <FormLabel htmlFor="edit-nama">Nama Ukuran <span class="text-danger">*</span></FormLabel>
        <FormInput id="edit-nama" v-model="form.nama_ukuran" placeholder="Nama Ukuran"
          :class="fieldErrors.nama_ukuran ? 'border-rose-500' : ''" required />
        <small v-if="fieldErrors.nama_ukuran" class="text-rose-600">{{ fieldErrors.nama_ukuran
          }}</small>
      </div>

      <!-- Satuan (required) -->
      <div>
        <FormLabel htmlFor="edit-satuan">Satuan <span class="text-danger">*</span></FormLabel>
        <FormSelect id="edit-satuan" v-model="form.id_satuan" :class="fieldErrors.id_satuan ? 'border-rose-500' : ''"
          required>
          <option disabled value="">-- Pilih Satuan --</option>
          <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">
            {{ s.nama_satuan }}
          </option>
        </FormSelect>
        <small v-if="fieldErrors.id_satuan" class="text-rose-600">{{ fieldErrors.id_satuan }}</small>
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
