<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import axios from 'axios'

import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi'

type PeriodAttachment = {
  path: string
  original_filename: string
  uploaded_at?: string
}

const props = defineProps<{
  open: boolean
  mode: 'create' | 'edit'
  periodId?: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', periodId: number): void
}>()

const auth = useAuthStore()
const periodeApi = createResourceApi('/price-periods')
const { success, error: notifyError } = useNotification()

const form = reactive({
  start_date: '',
  end_date: '',
  notes: '',
})

const errors = reactive<{ start_date?: string; end_date?: string }>({})

const pageLoading = ref(false)
const loading = ref(false)
const submitError = ref<string | null>(null)

const existingAttachments = ref<PeriodAttachment[]>([])
const newAttachments = ref<File[]>([])
const removedAttachmentIndexes = ref<number[]>([])

const existingAttachmentsForUpload = computed(() =>
  existingAttachments.value
    .map((att, index) => ({ id: index, name: att.original_filename, url: `/storage/${att.path}` }))
    .filter(file => !removedAttachmentIndexes.value.includes(Number(file.id))),
)

const roleSuffix = computed(() =>
  auth.user?.primary_role?.name ? ` (${auth.user.primary_role.name})` : '',
)

const title = computed(() =>
  props.mode === 'create'
    ? `Buat Periode Harga Baru${roleSuffix.value}`
    : `Edit Periode Harga${roleSuffix.value}`,
)

const submitText = computed(() =>
  props.mode === 'create' ? 'Simpan Periode Baru' : 'Simpan Perubahan',
)

watch(() => props.open, (isOpen) => {
  if (!isOpen) return

  resetForm()

  if (props.mode === 'edit' && props.periodId) {
    fetchPeriode(props.periodId)
  }
})

function resetForm() {
  form.start_date = ''
  form.end_date = ''
  form.notes = ''
  errors.start_date = undefined
  errors.end_date = undefined
  submitError.value = null
  existingAttachments.value = []
  newAttachments.value = []
  removedAttachmentIndexes.value = []
}

async function fetchPeriode(id: number) {
  pageLoading.value = true
  try {
    const { data } = await periodeApi.getById(id)
    const item = data.data ?? data
    form.start_date = item.start_date ?? ''
    form.end_date = item.end_date ?? ''
    form.notes = item.notes ?? ''
    existingAttachments.value = item.attachments ?? []
  } catch (e: any) {
    submitError.value = e.response?.data?.message ?? 'Gagal memuat data periode.'
  } finally {
    pageLoading.value = false
  }
}

function handleAttachmentsSelected(value: File | File[] | null) {
  newAttachments.value = Array.isArray(value) ? value : value ? [value] : []
}

function handleRemoveExistingAttachment(file: { id?: string | number }) {
  if (typeof file.id === 'number') {
    removedAttachmentIndexes.value.push(file.id)
  }
}

function validate(): boolean {
  errors.start_date = form.start_date ? undefined : 'Tanggal awal periode wajib diisi'
  errors.end_date = !form.end_date
    ? 'Tanggal akhir periode wajib diisi'
    : (form.start_date && new Date(form.end_date) < new Date(form.start_date))
      ? 'Tanggal akhir tidak boleh lebih awal dari tanggal awal'
      : undefined

  return !errors.start_date && !errors.end_date
}

async function submit() {
  submitError.value = null

  if (!validate()) return

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('price_period[start_date]', form.start_date)
    formData.append('price_period[end_date]', form.end_date)
    formData.append('price_period[notes]', form.notes ?? '')
    newAttachments.value.forEach(file => formData.append('attachments[]', file))
    removedAttachmentIndexes.value.forEach(index => formData.append('remove_attachments[]', String(index)))

    const response = props.mode === 'create'
      ? await axios.post('/api/price-periods', formData)
      : await axios.post(`/api/price-periods/${props.periodId}?_method=PUT`, formData)

    const savedId = response.data.price_period?.id ?? props.periodId

    success(
      'Berhasil',
      props.mode === 'create' ? 'Periode harga berhasil dibuat.' : 'Periode harga berhasil diperbarui.',
    )
    emit('saved', savedId)
    emit('close')
  } catch (e: any) {
    const message = e.response?.data?.message ?? 'Gagal menyimpan periode harga.'
    submitError.value = message
    notifyError('Gagal', message)
  } finally {
    loading.value = false
  }
}

function handleClose() {
  if (loading.value) return
  emit('close')
}
</script>

<template>
  <FormModal :open="open" :title="title" :loading="loading || pageLoading" :error="submitError" size="md"
    :submit-text="submitText" submit-icon="Save" @close="handleClose" @submit="submit">
    <div class="space-y-5">
      <div class="grid grid-cols-2 gap-4">
        <DateField v-model="form.start_date" label="Tanggal Awal Periode" required :error="errors.start_date" />
        <DateField v-model="form.end_date" label="Tanggal Akhir Periode" required :error="errors.end_date" />
      </div>

      <div>
        <FormLabel class="block !mb-1 font-label">Catatan Alasan Periode</FormLabel>
        <FormTextarea v-model="form.notes" rows="3"
          placeholder="Contoh: Penyesuaian tarif bea masuk baja Q4 & inflasi bahan baku..." />
      </div>

      <div>
        <FormLabel class="block !mb-1 font-label">Lampiran Dokumen Memo / SK Direksi</FormLabel>
        <FileUploadField :model-value="newAttachments" multiple :existing-files="existingAttachmentsForUpload"
          accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="10" choose-text="Pilih File (Max 10MB)"
          empty-text="Unggah file PDF / Surat SK Direksi" @update:model-value="handleAttachmentsSelected"
          @remove-existing="handleRemoveExistingAttachment" @error="(msg: string) => notifyError('Gagal', msg)" />
      </div>
    </div>
  </FormModal>
</template>
