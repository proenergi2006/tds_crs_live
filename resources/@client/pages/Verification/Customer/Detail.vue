<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { Tab } from '@/components/Base/Headless'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RichTextField from '@/components/SystemDesign/Form/RichTextField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import DocumentDownloadModal, { type DownloadableDocument } from '@/components/SystemDesign/Dialog/DocumentDownloadModal.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

import CustomerDataTab from '@/pages/Customer/components/CustomerDataTab.vue'
import SalesReviewDataTab from '@/pages/Customer/components/SalesReviewDataTab.vue'
import CreditDataTab from '@/pages/Customer/components/CreditDataTab.vue'
import LcrDataTab from '@/pages/Customer/components/LcrDataTab.vue'
import type { FinanceAttachment } from '@/pages/Customer/types'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idVerification = Number(route.params.id)

const TAB_ITEMS = [
  { label: 'Data Customer' },
  { label: 'Sales Review' },
  { label: 'Credit Application / TOP' },
  { label: 'LCR' },
  { label: 'Penawaran' },
]

const loading = ref(true)
const bootstrapError = ref<string | null>(null)
const detail = ref<any>(null)

const decisionMode = ref<'approve' | 'reject'>('approve')
const approvedLimit = ref<number | null>(null)
const approvedTop = ref<number | null>(null)
const notes = ref<string>('')
const attachments = ref<File[]>([])
const rejectNote = ref<string>('')
const submitting = ref(false)
const confirmOpen = ref(false)

const downloadModalOpen = ref(false)
const downloadingDocument = ref(false)

const penawarans = ref<any[]>([])
const penawaranLoading = ref(true)
const penawaranError = ref<string | null>(null)

const pageTitle = computed(() =>
  detail.value?.customer?.company_name ? `Verifikasi Customer — ${detail.value.customer.company_name}` : 'Verifikasi Customer',
)
const isInReview = computed<boolean>(() => detail.value?.status === 'in_review')
const lcrAllApproved = computed<boolean>(() => detail.value?.lcr?.all_approved === true)
const approvedResult = computed(() =>
  detail.value?.status === 'approved'
    ? {
      approved_limit: detail.value.approved_limit,
      approved_top: detail.value.approved_top,
      financial_review_snapshot: detail.value.financial_review_snapshot,
      notes: detail.value.notes,
      finance_attachments: (detail.value.finance_attachments ?? []) as FinanceAttachment[],
      reviewed_at: detail.value.reviewed_at,
    }
    : null,
)
const creditRequestSnapshot = computed(() =>
  detail.value
    ? {
      requested_limit: detail.value.requested_limit_snapshot,
      requested_top: detail.value.requested_top_snapshot,
      requested_qty: detail.value.requested_qty_snapshot,
      product_category: detail.value.product_category_snapshot,
      financial_review: detail.value.financial_review_snapshot,
    }
    : null,
)
const canDownloadDocument = computed<boolean>(() => detail.value?.status === 'in_review' || detail.value?.status === 'approved')
const decisionSubmitDisabled = computed<boolean>(() =>
  submitting.value || (decisionMode.value === 'reject' && !rejectNote.value.trim()),
)

onMounted(async () => {
  await fetchDetail()
  if (detail.value) {
    fetchPenawarans()
  }
})

async function fetchDetail(): Promise<void> {
  loading.value = true
  bootstrapError.value = null
  try {
    const { data } = await axios.get(`/api/review/customer-verifications/${idVerification}`)
    detail.value = data
  } catch (e: any) {
    bootstrapError.value = e.response?.data?.message ?? 'Gagal memuat data verifikasi.'
  } finally {
    loading.value = false
  }
}

async function fetchPenawarans(): Promise<void> {
  penawaranLoading.value = true
  penawaranError.value = null
  try {
    const { data } = await axios.get(`/api/customers/${detail.value.customer.id_customer}/penawarans`)
    penawarans.value = Array.isArray(data?.data) ? data.data : []
  } catch (e: any) {
    penawaranError.value = e.response?.data?.message ?? 'Gagal memuat Penawaran.'
  } finally {
    penawaranLoading.value = false
  }
}

async function openDocument(category: DownloadableDocument): Promise<void> {
  // window.open() langsung ke URL gak bisa -- bukan lewat axios jadi gak kebaca middleware auth:sanctum, makanya pola blob+tab pre-open (lihat pdfPreviewTab.ts)
  const tab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/api/review/customer-verifications/${idVerification}/document/${category}`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (tab) {
      tab.location.href = url
    } else {
      window.open(url, '_blank')
    }
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch {
    // responseType: 'blob' bikin error response ikut jadi Blob juga, .message gak kebaca -- pesan generic aja
    tab?.close()
    notifyError('Gagal', 'Gagal membuka dokumen KYC.')
  }
}

async function handleDocumentSelect(category: DownloadableDocument): Promise<void> {
  downloadingDocument.value = true
  try {
    await openDocument(category)
  } finally {
    downloadingDocument.value = false
    downloadModalOpen.value = false
  }
}

async function submitDecision(): Promise<void> {
  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('_method', 'PATCH')
    formData.append('action', decisionMode.value)
    if (decisionMode.value === 'approve') {
      formData.append('approved_limit', String(approvedLimit.value ?? ''))
      formData.append('approved_top', String(approvedTop.value ?? ''))
      if (notes.value.trim()) formData.append('notes', notes.value)
      attachments.value.forEach((file) => formData.append('attachments[]', file))
    } else {
      formData.append('reject_note', rejectNote.value)
    }

    await axios.post(`/api/customer-verifications/${idVerification}/decision`, formData)

    success('Berhasil', decisionMode.value === 'approve' ? 'Verifikasi customer berhasil disetujui.' : 'Verifikasi customer berhasil ditolak.')
    router.push({ name: 'review-data-customer-admin' })
  } catch (e: any) {
    const status = e.response?.status
    if (status === 409) {
      notifyError('Gagal', 'Siklus ini sudah selesai.')
      router.push({ name: 'review-data-customer-admin' })
    } else if (status === 422) {
      const errors = e.response?.data?.errors
      if (errors) {
        notifyError('Gagal', (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.')
      } else {
        notifyError('Gagal', e.response?.data?.message ?? 'Periksa kembali input Anda.')
      }
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal memproses keputusan verifikasi.')
    }
  } finally {
    submitting.value = false
    confirmOpen.value = false
  }
}

function goBack(): void {
  router.back()
}

function statusBadgeClass(status?: string): string {
  if (status === 'in_review') return 'bg-amber-100 text-amber-700'
  if (status === 'approved') return 'bg-emerald-100 text-emerald-700'
  if (status === 'rejected') return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-700'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader :title="pageTitle" variant="flat">
        <template #action>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
            Kembali
          </Button>
        </template>

        <template #body>
          <div v-if="detail" class="flex flex-col gap-3 pt-3 border-slate-200 border-t">
            <div class="flex flex-wrap justify-between items-center gap-3">
              <div class="flex flex-col items-center gap-x-4 gap-y-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-form-label"
                  :class="statusBadgeClass(detail.status)">
                  {{ detail.status_label }}
                </span>
                <span class="text-caption text-slate-500">Diajukan {{ formatDateTime(detail.submitted_at) ?? '-' }} · {{
                  detail.submitted_by?.name ?? '-' }}</span>
                <span v-if="detail.reviewed_at" class="text-caption text-slate-500">Direview {{
                  formatDateTime(detail.reviewed_at) }} · {{ detail.reviewed_by?.name ?? '-' }}</span>
              </div>
              <div v-if="canDownloadDocument" class="flex flex-wrap gap-2">
                <Button variant="outline-primary" size="sm" class="inline-flex items-center gap-2"
                  @click="downloadModalOpen = true">
                  <Lucide icon="Download" class="w-4 h-4" />
                  Download
                </Button>
              </div>
            </div>
            <div v-if="detail.status === 'rejected'" class="bg-rose-50 px-3 py-2 border border-rose-200 rounded-lg">
              <div class="text-form-label !text-rose-700">Alasan Penolakan</div>
              <div class="text-body !text-rose-700">{{ detail.reject_note || '-' }}</div>
            </div>
          </div>
        </template>
      </PageHeader>

      <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
        <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
        <span class="text-body">Memuat data verifikasi...</span>
      </div>

      <div v-else-if="bootstrapError"
        class="bg-rose-50 px-4 py-3 border border-rose-200 rounded-lg text-body !text-rose-700 whitespace-pre-line">
        {{ bootstrapError }}
      </div>

      <Tab.Group v-else-if="detail !== null" :default-index="2">
        <Tab.List variant="link-tabs" class="gap-1 border-slate-200 border-b">
          <Tab v-for="t in TAB_ITEMS" :key="t.label" :full-width="false" v-slot="{ selected }">
            <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
              ? 'text-primary border-b-primary font-medium'
              : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
              <span>{{ t.label }}</span>
            </Tab.Button>
          </Tab>
        </Tab.List>

        <Tab.Panels class="mt-4">
          <Tab.Panel>
            <CustomerDataTab :id-customer="detail.customer.id_customer" :customer="detail.customer"
              :show-onboarding-link="false" readonly />
          </Tab.Panel>

          <Tab.Panel>
            <SalesReviewDataTab :id-customer="detail.customer.id_customer" :is-under-review="false" readonly />
          </Tab.Panel>

          <Tab.Panel>
            <div class="items-start gap-4 grid grid-cols-1"
              :class="isInReview ? 'lg:grid-cols-[minmax(0,1fr)_380px]' : ''">
              <div class="min-w-0">
                <CreditDataTab :id-customer="detail.customer.id_customer" :is-under-review="false" readonly
                  :latest-approved-verification="approvedResult" :snapshot="creditRequestSnapshot">
                  <template v-if="isInReview && decisionMode === 'approve'" #approval-limit>
                    <CurrencyField v-model="approvedLimit" :disabled="submitting" />
                  </template>
                  <template v-if="isInReview && decisionMode === 'approve'" #approval-top>
                    <NumberField v-model="approvedTop" suffix="hari" :decimals="0" :disabled="submitting" />
                  </template>
                  <template v-if="isInReview && decisionMode === 'approve'" #finance-notes>
                    <RichTextField v-model="notes" :disabled="submitting" />
                    <div class="mt-3">
                      <FileUploadField v-model="attachments" multiple accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="2"
                        label="Lampiran" hint="Opsional, mendukung multiple file (2MB per file)."
                        :disabled="submitting" />
                    </div>
                  </template>
                </CreditDataTab>
              </div>

              <CardSection v-if="isInReview" title="Keputusan" description="Putuskan approve atau reject siklus ini."
                icon="Gavel" icon-class="bg-blue-100 text-blue-600">
                <div class="space-y-3">
                  <div class="flex gap-2">
                    <Button type="button" class="flex-1 justify-center items-center gap-2"
                      :variant="decisionMode === 'approve' ? 'primary' : 'outline-secondary'"
                      @click="decisionMode = 'approve'">
                      Approve
                    </Button>
                    <Button type="button" class="flex-1 justify-center items-center gap-2"
                      :variant="decisionMode === 'reject' ? 'danger' : 'outline-secondary'"
                      @click="decisionMode = 'reject'">
                      Reject
                    </Button>
                  </div>

                  <Alert v-if="decisionMode === 'approve' && !lcrAllApproved" variant="soft-warning"
                    class="flex items-start gap-3">
                    <Lucide icon="AlertTriangle" class="mt-0.5 w-4 h-4 shrink-0" />
                    <span>LCR belum diverifikasi Logistik — silakan koordinasikan dengan tim Logistik. Verifikasi ini
                      tetap
                      bisa dilanjutkan.</span>
                  </Alert>

                  <template v-if="decisionMode === 'reject'">
                    <FormLabel>Alasan Penolakan</FormLabel>
                    <FormTextarea v-model="rejectNote" rows="3" :disabled="submitting" />
                  </template>

                  <Button class="inline-flex justify-center items-center gap-2 w-full"
                    :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :disabled="decisionSubmitDisabled"
                    @click="confirmOpen = true">
                    <Lucide icon="Send" class="w-4 h-4" />
                    Ajukan Keputusan
                  </Button>
                </div>
              </CardSection>
            </div>
          </Tab.Panel>

          <Tab.Panel>
            <LcrDataTab :id-customer="detail.customer.id_customer" readonly />
          </Tab.Panel>

          <Tab.Panel>
            <CardSection title="Penawaran" description="Daftar Penawaran milik customer ini." icon="FileText"
              icon-class="bg-cyan-100 text-cyan-600">
              <div v-if="penawaranLoading" class="flex justify-center items-center gap-3 min-h-[100px] text-slate-500">
                <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
                <span class="text-body">Memuat Penawaran...</span>
              </div>

              <div v-else-if="penawaranError"
                class="bg-rose-50 px-4 py-3 border border-rose-200 rounded-lg text-body !text-rose-700">
                {{ penawaranError }}
              </div>

              <div v-else-if="penawarans.length === 0"
                class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
                <Lucide icon="Inbox" class="w-6 h-6 text-slate-400" />
                <div class="text-body">Belum ada Penawaran tercatat.</div>
              </div>

              <div v-else class="overflow-x-auto">
                <Table bordered sm class="text-body">
                  <Table.Thead class="bg-slate-50">
                    <Table.Th class="text-form-label">Nomor Penawaran</Table.Th>
                    <Table.Th class="text-form-label">Masa Berlaku</Table.Th>
                    <Table.Th class="text-form-label text-right">Volume</Table.Th>
                    <Table.Th class="text-form-label text-right">Harga Dasar</Table.Th>
                    <Table.Th class="text-form-label text-right">Ongkos Angkut</Table.Th>
                  </Table.Thead>
                  <Table.Tbody class="bg-white">
                    <Table.Tr v-for="p in penawarans" :key="p.id_penawaran">
                      <Table.Td class="text-body-strong">{{ p.nomor_penawaran || '-' }}</Table.Td>
                      <Table.Td>{{ formatDate(p.masa_berlaku) }} &ndash; {{ formatDate(p.sampai_dengan) }}</Table.Td>
                      <Table.Td class="num-sm text-right">{{ formatNumber(p.total_volume) }}</Table.Td>
                      <Table.Td class="num-sm text-right">{{ formatCurrency(p.harga_dasar) }}</Table.Td>
                      <Table.Td class="num-sm text-right">{{ formatCurrency(p.oat) }}</Table.Td>
                    </Table.Tr>
                  </Table.Tbody>
                </Table>
              </div>
            </CardSection>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>
  </div>

  <ConfirmDialog :open="confirmOpen"
    :title="decisionMode === 'approve' ? 'Setujui verifikasi customer ini?' : 'Tolak verifikasi customer ini?'"
    :description="decisionMode === 'approve'
      ? 'Credit limit dan TOP yang disetujui akan berlaku efektif setelah ini.'
      : 'Marketing akan melihat alasan penolakan dan bisa mengajukan ulang verifikasi.'"
    :confirm-text="decisionMode === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
    :icon="decisionMode === 'approve' ? 'Check' : 'X'"
    :icon-class="decisionMode === 'approve' ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
    :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :loading="submitting" @close="confirmOpen = false"
    @confirm="submitDecision" />

  <DocumentDownloadModal :open="downloadModalOpen" :loading="downloadingDocument" @close="downloadModalOpen = false"
    @select="handleDocumentSelect" />
</template>
