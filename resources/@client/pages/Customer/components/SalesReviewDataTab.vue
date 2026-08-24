<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormLabel, FormInput, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

// Form inline, 14 pertanyaan dari CustomerReviewQuestionCode enum; question_code dikirim, question di-strip sebelum kirim.

// ReviewAnswer server-derive dari CustomerReviewQuestionCode -- FE cuma menampilkan, tidak membangun daftar sendiri.
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

const props = defineProps<{
  idCustomer: number
  kycStatus?: string | null
}>()

const { success, error: notifyError } = useNotification()

/* State */
const reviewAnswers = ref<ReviewAnswer[]>([])
const attachments = ref<ReviewAttachment[]>([])
const reviewedAt = ref<string | null>(null)
const loading = ref(true)
const saving = ref(false)
const pendingFiles = ref<File | File[] | null>(null)

// undefined/null kycStatus dianggap TIDAK terkunci (falsy-safe), bukan cuma saat kycStatus === 'draft'.
const locked = computed(() => !!props.kycStatus && props.kycStatus !== 'draft')

// Dipetakan dari shape {path,url,original_name} ke ExistingFile ({id,name,url}) yang diharap FileUploadField; index dipakai sebagai id.
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

/* Fetch */
async function fetchReview() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${props.idCustomer}/review`)
    reviewAnswers.value = data?.review_answers ?? []
    attachments.value = data?.review_attachments ?? []
    reviewedAt.value = data?.reviewed_at ?? null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat Sales Review.')
  } finally {
    loading.value = false
  }
}

/* Submit */
async function saveReview() {
  if (locked.value) return

  saving.value = true
  try {
    const payload = {
      review_answers: reviewAnswers.value.map(({ question_code, answer }) => ({ question_code, answer })),
    }
    const { data } = await axios.post(`/api/customers/${props.idCustomer}/review`, payload)
    reviewAnswers.value = data?.review_answers ?? reviewAnswers.value
    reviewedAt.value = data?.reviewed_at ?? reviewedAt.value
    success('Berhasil', 'Sales Review tersimpan.')
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

// Lampiran diupload langsung saat file dipilih (bukan ditahan sampai Simpan), konsisten pola CustomerDataTab.vue.
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

// multiple=true bisa emit File[] sekaligus -- tiap file diupload satu-satu, selection lokal direset setelahnya.
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
    <span class="font-body">Memuat Sales Review...</span>
  </div>

  <CardSection v-else title="Sales Review" description="Jawaban Marketing untuk 14 pertanyaan review KYC."
    icon="ClipboardEdit" icon-class="bg-primary/10 text-primary">
    <p v-if="locked" class="bg-amber-50 mb-4 px-3 py-2.5 border border-amber-200 rounded-lg font-body !text-amber-700">
      Tab ini terkunci, KYC sudah di-forward.
    </p>

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

    <div class="mt-6 pt-5">
      <FormLabel>Lampiran</FormLabel>
      <FileUploadField :model-value="pendingFiles" multiple :existing-files="existingAttachmentFiles" :disabled="locked"
        accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" choose-text="Pilih lampiran"
        empty-text="Belum ada lampiran diunggah" @update:model-value="handleFilesSelected"
        @remove-existing="handleRemoveExisting" @error="(msg: string) => notifyError('Gagal', msg)" />
    </div>

    <div class="flex justify-between items-center gap-3 mt-6 pt-5 border-slate-100 border-t">
      <span v-if="reviewedAt" class="font-caption">Terakhir disimpan {{ formatReviewedAt(reviewedAt) }}</span>
      <span v-else />
      <Button variant="primary" class="inline-flex items-center gap-2" :disabled="locked || saving" @click="saveReview">
        <Lucide v-if="saving" icon="Loader2" class="w-4 h-4 animate-spin" />
        Simpan Review
      </Button>
    </div>
  </CardSection>
</template>
