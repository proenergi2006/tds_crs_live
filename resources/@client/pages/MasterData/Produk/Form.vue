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
      nama_produk: string;
      merk_dagang: string;
      deskripsi: string;
      id_ukuran: number | string;
      id_jenis: number | string;
      is_active: boolean;
      created_by?: string;
      lastupdate_by?: string;
    };
    ukurans: any[];
    jenisProduks: any[];
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
    ? 'Tambah Produk'
    : 'Edit Produk',
);

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan produk baru ke sistem.'
    : 'Perbarui informasi produk.',
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
      <!-- Nama Produk (required) -->
      <div>
        <FormLabel htmlFor="edit-nama">Nama Produk <RequiredAsterisk /></FormLabel>
        <FormInput id="edit-nama" v-model="form.nama_produk" placeholder="Nama Produk"
          :class="fieldErrors.nama_produk ? 'border-rose-500' : ''" required />
        <small v-if="fieldErrors.nama_produk" class="text-rose-600">{{ fieldErrors.nama_produk
        }}</small>
      </div>

      <!-- Merk Dagang (required) -->
      <div>
        <FormLabel htmlFor="edit-merk">Merk Dagang <RequiredAsterisk /></FormLabel>
        <FormInput id="edit-merk" v-model="form.merk_dagang" placeholder="Merk Dagang"
          :class="fieldErrors.merk_dagang ? 'border-rose-500' : ''" required />
        <small v-if="fieldErrors.merk_dagang" class="text-rose-600">{{ fieldErrors.merk_dagang
        }}</small>
      </div>

      <!-- Deskripsi (optional) -->
      <div>
        <FormLabel htmlFor="edit-deskripsi">Deskripsi</FormLabel>
        <FormInput id="edit-deskripsi" v-model="form.deskripsi" placeholder="Deskripsi" />
      </div>

      <!-- Ukuran dan Jenis (required) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <FormLabel htmlFor="edit-ukuran">Ukuran <RequiredAsterisk /></FormLabel>
          <FormSelect id="edit-ukuran" v-model="form.id_ukuran" :class="fieldErrors.id_ukuran ? 'border-rose-500' : ''"
            required>
            <option disabled value="">-- Pilih Ukuran --</option>
            <option v-for="u in ukurans" :key="u.id_ukuran" :value="u.id_ukuran">
              {{ u.nama_ukuran }} ({{ u.satuan?.nama_satuan || '-' }})
            </option>
          </FormSelect>
          <small v-if="fieldErrors.id_ukuran" class="text-rose-600">{{ fieldErrors.id_ukuran }}</small>
        </div>

        <div>
          <FormLabel htmlFor="edit-jenis">Jenis Produk <RequiredAsterisk /></FormLabel>
          <FormSelect id="edit-jenis" v-model="form.id_jenis" :class="fieldErrors.id_jenis ? 'border-rose-500' : ''"
            required>
            <option disabled value="">-- Pilih Jenis Produk --</option>
            <option v-for="j in jenisProduks" :key="j.id_jenis" :value="j.id_jenis">
              {{ j.nama }}
            </option>
          </FormSelect>
          <small v-if="fieldErrors.id_jenis" class="text-rose-600">{{ fieldErrors.id_jenis }}</small>
        </div>
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
