<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required } from '@vuelidate/validators'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { FormInput, FormSelect, FormLabel, FormTextarea } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

const terminalApi = createResourceApi('/terminals')
const cabangApi = createResourceApi('/cabangs')
const { success, error } = useNotification()
const auth = useAuthStore()

const props = withDefaults(
  defineProps<{
    open: boolean
    mode: 'create' | 'edit'
    item?: {
      id_terminal?: number
      nama_terminal: string
      id_cabang: number | string
      kategori_terminal: string
      inisial?: string
      lokasi?: string
      telp_terminal?: string
      alamat?: string
      fax?: string
      pic?: string
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

const cabangs = ref<any[]>([])
const loading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id_terminal: 0,
  nama_terminal: '',
  id_cabang: '',
  kategori_terminal: '',
  inisial: '',
  lokasi: '',
  telp_terminal: '',
  alamat: '',
  fax: '',
  pic: '',
  created_by: '',
  lastupdate_by: '',
})

const rules = {
  nama_terminal: {
    required: helpers.withMessage('Nama Terminal wajib diisi', required),
  },
  id_cabang: {
    required: helpers.withMessage('Cabang wajib dipilih', required),
  },
  kategori_terminal: {
    required: helpers.withMessage('Kategori wajib dipilih', required),
  },
}

const v$ = useVuelidate(rules, form)

const modalTitle = computed(() =>
  props.mode === 'create' ? 'Tambah Terminal' : 'Edit Terminal',
)

const modalDescription = computed(() =>
  props.mode === 'create'
    ? 'Tambahkan terminal baru ke sistem.'
    : 'Perbarui informasi terminal.',
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
    const { data } = await cabangApi.getAll({ per_page: 100 })
    cabangs.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data cabang')
  }
}

function resetForm() {
  resetFormErrors()

  if (props.mode === 'edit' && props.item) {
    Object.assign(form, {
      id_terminal: props.item.id_terminal ?? 0,
      nama_terminal: props.item.nama_terminal ?? '',
      id_cabang: props.item.id_cabang ?? '',
      kategori_terminal: props.item.kategori_terminal ?? '',
      inisial: props.item.inisial ?? '',
      lokasi: props.item.lokasi ?? '',
      telp_terminal: props.item.telp_terminal ?? '',
      alamat: props.item.alamat ?? '',
      fax: props.item.fax ?? '',
      pic: props.item.pic ?? '',
      created_by: props.item.created_by ?? '',
      lastupdate_by: props.item.lastupdate_by ?? '',
    })
    return
  }

  Object.assign(form, {
    id_terminal: 0,
    nama_terminal: '',
    id_cabang: '',
    kategori_terminal: '',
    inisial: '',
    lokasi: '',
    telp_terminal: '',
    alamat: '',
    fax: '',
    pic: '',
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
}

function resetFormErrors() {
  formError.value = null
  v$.value.$reset()
}

function getFieldError(field: 'nama_terminal' | 'id_cabang' | 'kategori_terminal') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function getFormPayload() {
  return {
    nama_terminal: form.nama_terminal,
    id_cabang: Number(form.id_cabang),
    kategori_terminal: form.kategori_terminal,
    inisial: form.inisial,
    lokasi: form.lokasi,
    telp_terminal: form.telp_terminal,
    alamat: form.alamat,
    fax: form.fax,
    pic: form.pic,
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
        ? await terminalApi.store(getFormPayload())
        : await terminalApi.update(form.id_terminal, getFormPayload())

    success(
      'Berhasil',
      props.mode === 'create'
        ? 'Terminal berhasil ditambahkan'
        : 'Terminal berhasil diperbarui',
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
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <FormLabel htmlFor="terminal-nama">Nama Terminal
            <RequiredAsterisk />
          </FormLabel>
          <FormInput id="terminal-nama" v-model="form.nama_terminal" placeholder="Nama Terminal"
            :class="v$.nama_terminal.$error ? 'border-rose-500' : ''" />
          <small v-if="v$.nama_terminal.$error" class="text-rose-600">{{ getFieldError('nama_terminal') }}</small>
        </div>

        <div>
          <FormLabel htmlFor="terminal-cabang">Cabang
            <RequiredAsterisk />
          </FormLabel>
          <FormSelect id="terminal-cabang" v-model="form.id_cabang"
            :class="v$.id_cabang.$error ? 'border-rose-500' : ''">
            <option disabled value="">-- Pilih Cabang --</option>
            <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
              {{ cabang.nama_cabang }}
            </option>
          </FormSelect>
          <small v-if="v$.id_cabang.$error" class="text-rose-600">{{ getFieldError('id_cabang') }}</small>
        </div>

        <div>
          <FormLabel htmlFor="terminal-kategori">Kategori
            <RequiredAsterisk />
          </FormLabel>
          <FormSelect id="terminal-kategori" v-model="form.kategori_terminal"
            :class="v$.kategori_terminal.$error ? 'border-rose-500' : ''">
            <option disabled value="">-- Pilih Kategori --</option>
            <option value="Jetty">Jetty</option>
            <option value="StockPile">StockPile</option>
          </FormSelect>
          <small v-if="v$.kategori_terminal.$error" class="text-rose-600">
            {{ getFieldError('kategori_terminal') }}
          </small>
        </div>

        <div>
          <FormLabel htmlFor="terminal-inisial">Inisial</FormLabel>
          <FormInput id="terminal-inisial" v-model="form.inisial" placeholder="Inisial (opsional)" />
        </div>

        <div>
          <FormLabel htmlFor="terminal-lokasi">Lokasi</FormLabel>
          <FormInput id="terminal-lokasi" v-model="form.lokasi" placeholder="Lokasi (opsional)" />
        </div>

        <div>
          <FormLabel htmlFor="terminal-telp">Telepon</FormLabel>
          <FormInput id="terminal-telp" v-model="form.telp_terminal" placeholder="Telepon (opsional)" />
        </div>

        <div>
          <FormLabel htmlFor="terminal-fax">Fax</FormLabel>
          <FormInput id="terminal-fax" v-model="form.fax" placeholder="Fax (opsional)" />
        </div>

        <div>
          <FormLabel htmlFor="terminal-pic">PIC</FormLabel>
          <FormInput id="terminal-pic" v-model="form.pic" placeholder="Person In Charge (opsional)" />
        </div>
      </div>

      <div>
        <FormLabel htmlFor="terminal-alamat">Alamat</FormLabel>
        <FormTextarea id="terminal-alamat" v-model="form.alamat" rows="3" placeholder="Alamat (opsional)" />
      </div>

      <div v-if="props.mode === 'edit'">
        <p class="text-right text-xs text-gray-500 mt-6">
          <i>* {{ updatedByInfo }}</i>
        </p>
      </div>
    </div>
  </FormModal>
</template>
