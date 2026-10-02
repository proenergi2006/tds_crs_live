<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormInput, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface ReviewAnswer {
  question_code: string
  question: string
  answer: string | null
  order: number
  field_type: 'shorttext' | 'longtext'
}
interface ReviewAttachment {
  path: string
  url: string
  original_name: string
}

const props = withDefaults(
  defineProps<{
    idCustomer: number
    isUnderReview: boolean
    readonly?: boolean
  }>(),
  { readonly: false },
)

const emit = defineEmits<{ (e: 'saved'): void }>()

const { success, error: notifyError } = useNotification()

const reviewAnswers = ref<ReviewAnswer[]>([])
const attachments = ref<ReviewAttachment[]>([])
const notes = ref<string | null>(null)
const reviewedAt = ref<string | null>(null)
const loading = ref(true)
const saving = ref(false)
const pendingFiles = ref<File | File[] | null>(null)

const locked = computed<boolean>(() => props.isUnderReview || props.readonly)

const existingAttachmentFiles = computed(() =>
  attachments.value.map((a, index) => ({ id: index, name: a.original_name, url: a.url })),
)

function formatReviewedAt(value: string | null) {
  if (!value) return null
  try {
    return new Date(value).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return value
  }
}

async function fetchReview() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${props.idCustomer}/review`)
    reviewAnswers.value = data?.review_answers ?? []
    attachments.value = data?.review_attachments ?? []
    notes.value = data?.notes ?? null
    reviewedAt.value = data?.reviewed_at ?? null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat Sales Review.')
  } finally {
    loading.value = false
  }
}

async function saveReview() {
  if (locked.value) return

  saving.value = true
  try {
    const payload = {
      review_answers: reviewAnswers.value.map(({ question_code, answer }) => ({ question_code, answer })),
      notes: notes.value,
    }
    const { data } = await axios.post(`/api/customers/${props.idCustomer}/review`, payload)
    reviewAnswers.value = data?.review_answers ?? reviewAnswers.value
    notes.value = data?.notes ?? notes.value
    reviewedAt.value = data?.reviewed_at ?? reviewedAt.value
    success('Berhasil', 'Sales Review tersimpan.')
    emit('saved')
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Tab ini terkunci — KYC sudah di-forward.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan Sales Review.')
    }
  } finally {
    saving.value = false
  }
}

async function uploadAttachment(file: File) {
  if (locked.value) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await axios.post(`/api/customers/${props.idCustomer}/review-attachment`, formData)
    attachments.value.push({ path: data.path, url: data.url, original_name: data.original_name })
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Tab ini terkunci — KYC sudah di-forward.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengunggah lampiran.')
    }
  }
}

async function deleteAttachment(index: number) {
  if (locked.value) return

  try {
    await axios.delete(`/api/customers/${props.idCustomer}/review-attachment/${index}`)
    attachments.value.splice(index, 1)
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus lampiran.')
  }
}

async function handleFilesSelected(value: File | File[] | null) {
  const files = Array.isArray(value) ? value : value ? [value] : []
  for (const file of files) {
    await uploadAttachment(file)
  }
  pendingFiles.value = null
}

function handleRemoveExisting(file: { id?: string | number; name: string; url?: string }) {
  if (file.id === undefined || file.id === null) return
  deleteAttachment(Number(file.id))
}

onMounted(fetchReview)
</script>

<template>
  <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[220px] text-slate-500">
    <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
    <span class="text-body">Memuat Sales Review...</span>
  </div>

  <div v-else class="space-y-4">
    <Alert v-if="props.isUnderReview" variant="soft-warning">
      Tab ini terkunci, verifikasi sedang berjalan.
    </Alert>

    <div class="items-start gap-4 grid grid-cols-1 lg:grid-cols-3">
      <CardSection class="lg:col-span-2" title="Pertanyaan"
        description="Jawaban Marketing untuk 14 pertanyaan review KYC." icon="ClipboardEdit"
        icon-class="bg-primary/10 text-primary">
        <div class="overflow-x-auto">
          <Table bordered>
            <Table.Thead>
              <Table.Tr>
                <Table.Th>#</Table.Th>
                <Table.Th class="w-2/5">Pertanyaan</Table.Th>
                <Table.Th class="w-3/5">Jawaban</Table.Th>
              </Table.Tr>
            </Table.Thead>
            <Table.Tbody>
              <Table.Tr v-for="item in reviewAnswers" :key="item.question_code">
                <Table.Td class="py-4 align-top">{{ item.order }}.</Table.Td>
                <Table.Td class="py-4 align-top">{{ item.question }}</Table.Td>
                <Table.Td class="py-4 align-top">
                  <FormInput v-if="item.field_type === 'shorttext'" v-model="item.answer" :disabled="locked" />
                  <FormTextarea v-else v-model="item.answer" :disabled="locked" rows="2" :auto-resize="true" />
                </Table.Td>
              </Table.Tr>
            </Table.Tbody>
          </Table>
        </div>
      </CardSection>

      <div class="space-y-4">
        <CardSection title="Notes" description="Detail informasi tambahan tentang customer." icon="FileText"
          icon-class="bg-primary/10 text-primary">
          <FormTextarea v-model="notes" :disabled="locked" rows="6" :auto-resize="true"
            placeholder="Narasi hubungan customer, riwayat bisnis, dsb." />
        </CardSection>

        <CardSection title="Lampiran" description="Dokumen pendukung Sales Review." icon="Paperclip"
          icon-class="bg-primary/10 text-primary">
          <ul v-if="props.readonly" class="space-y-2">
            <li v-for="file in existingAttachmentFiles" :key="file.id"
              class="flex items-center gap-3 bg-slate-50 px-3 py-2 border border-slate-200 rounded-lg">
              <Lucide icon="FileText" class="w-5 h-5 text-slate-500 shrink-0" />
              <a :href="file.url" target="_blank"
                class="block flex-1 min-w-0 text-body !text-primary underline truncate">
                {{ file.name }}
              </a>
            </li>
            <li v-if="!existingAttachmentFiles.length"
              class="bg-slate-50 px-3 py-3 border border-slate-200 border-dashed rounded-lg text-body">
              Belum ada lampiran diunggah
            </li>
          </ul>
          <FileUploadField v-else :model-value="pendingFiles" multiple :existing-files="existingAttachmentFiles"
            :disabled="locked" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" choose-text="Pilih lampiran"
            empty-text="Belum ada lampiran diunggah" @update:model-value="handleFilesSelected"
            @remove-existing="handleRemoveExisting" @error="(msg: string) => notifyError('Gagal', msg)" />
        </CardSection>

        <div class="flex justify-between items-center gap-3 bg-white px-5 py-4 border border-slate-200 rounded-xl">
          <span v-if="reviewedAt" class="text-caption">Terakhir disimpan {{ formatReviewedAt(reviewedAt) }}</span>
          <span v-else />
          <Button v-if="!props.readonly" variant="primary" class="inline-flex items-center gap-2"
            :disabled="locked || saving" @click="saveReview">
            <Lucide v-if="saving" icon="Loader2" class="w-4 h-4 animate-spin" />
            Simpan Review
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
