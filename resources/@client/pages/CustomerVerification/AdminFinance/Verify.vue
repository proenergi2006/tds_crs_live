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
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

import CustomerDataTab from '@/pages/Customer/components/CustomerDataTab.vue'

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

const LCR_STATUS_LABELS: Record<string, string> = {
  in_progress: 'Dalam Proses',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  cancelled: 'Dibatalkan',
}

const loading = ref(true)
const bootstrapError = ref<string | null>(null)
const detail = ref<any>(null)

const decisionMode = ref<'approve' | 'reject'>('approve')
const approvedLimit = ref<number | null>(null)
const approvedTop = ref<number | null>(null)
const financialReview = ref<string>('')
const rejectNote = ref<string>('')
const submitting = ref(false)
const confirmOpen = ref(false)

const penawarans = ref<any[]>([])
const penawaranLoading = ref(true)
const penawaranError = ref<string | null>(null)

const pageTitle = computed(() =>
  detail.value?.customer?.company_name ? `Verifikasi Customer — ${detail.value.customer.company_name}` : 'Verifikasi Customer',
)
const pageDescription = computed(() =>
  detail.value?.status_label ? `Status verifikasi saat ini: ${detail.value.status_label}.` : undefined,
)
const isInReview = computed<boolean>(() => detail.value?.status === 'in_review')
const lcrAllApproved = computed<boolean>(() => detail.value?.lcr?.all_approved === true)
const approveBlocked = computed<boolean>(() => !lcrAllApproved.value)
const canDownloadDocument = computed<boolean>(() => detail.value?.status === 'in_review' || detail.value?.status === 'approved')
const decisionSubmitDisabled = computed<boolean>(() =>
  submitting.value || (decisionMode.value === 'approve' ? approveBlocked.value : !rejectNote.value.trim()),
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

async function openDocument(): Promise<void> {
  // window.open() langsung ke URL gak bisa -- bukan lewat axios jadi gak kebaca middleware auth:sanctum, makanya pola blob+tab pre-open (lihat pdfPreviewTab.ts)
  const tab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/api/review/customer-verifications/${idVerification}/document`, {
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

async function submitDecision(): Promise<void> {
  submitting.value = true
  try {
    const payload = decisionMode.value === 'approve'
      ? {
          action: 'approve',
          approved_limit: approvedLimit.value,
          approved_top: approvedTop.value,
          financial_review: financialReview.value,
        }
      : { action: 'reject', reject_note: rejectNote.value }

    await axios.patch(`/api/customer-verifications/${idVerification}/decision`, payload)

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
        notifyError('Gagal', 'LCR belum diverifikasi Logistik — Approve tidak bisa dilakukan.')
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

function lcrStatusLabel(status?: string | null): string {
  return LCR_STATUS_LABELS[status ?? ''] ?? 'Belum ada approval'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="pageTitle" :description="pageDescription" variant="flat">
        <template #action>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </template>

        <template #body>
          <div v-if="detail" class="flex flex-col gap-3 border-t border-slate-200 pt-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                  :class="statusBadgeClass(detail.status)">
                  {{ detail.status_label }}
                </span>
                <span class="font-caption text-slate-500">Diajukan {{ formatDateTime(detail.submitted_at) ?? '-' }} · {{ detail.submitted_by?.name ?? '-' }}</span>
                <span v-if="detail.reviewed_at" class="font-caption text-slate-500">Direview {{ formatDateTime(detail.reviewed_at) }} · {{ detail.reviewed_by?.name ?? '-' }}</span>
              </div>
              <Button v-if="canDownloadDocument" variant="outline-primary" class="inline-flex items-center gap-2"
                @click="openDocument">
                <Lucide icon="Printer" class="h-4 w-4" />
                Cetak Dokumen (Gabungan)
              </Button>
            </div>
            <div v-if="detail.status === 'rejected'" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
              <div class="font-label !text-rose-700">Alasan Penolakan</div>
              <div class="font-body !text-rose-700">{{ detail.reject_note || '-' }}</div>
            </div>
          </div>
        </template>
      </PageHeader>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data verifikasi...</span>
      </div>

      <div v-else-if="bootstrapError"
        class="whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
        {{ bootstrapError }}
      </div>

    <Tab.Group v-else-if="detail !== null" :default-index="2">
      <Tab.List variant="link-tabs" class="gap-1 border-b border-slate-200">
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
          <CustomerDataTab :id-customer="detail.customer.id_customer" :customer="detail.customer" />
        </Tab.Panel>

        <Tab.Panel>
          <CardSection title="Sales Review" icon="ClipboardCheck" icon-class="bg-emerald-100 text-emerald-600">
            <div v-if="!detail.review?.length"
              class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
              <div class="font-body">Belum ada jawaban Sales Review.</div>
            </div>

            <div v-else class="space-y-4">
              <div v-for="qa in detail.review" :key="qa.question_code">
                <div class="font-label">{{ qa.question }}</div>
                <div
                  class="font-body mt-1 whitespace-pre-line rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                  {{ qa.answer || '-' }}
                </div>
              </div>
            </div>
          </CardSection>
        </Tab.Panel>

        <Tab.Panel>
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:items-start">
            <CardSection title="Credit Application" icon="CreditCard" icon-class="bg-violet-100 text-violet-600">
              <div v-if="!detail.credit_request"
                class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
                <div class="font-body">Belum ada pengajuan credit untuk customer ini.</div>
              </div>

              <template v-else>
                <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">Credit Limit Diajukan</span>
                    <span class="font-strong text-right">{{ formatCurrency(detail.credit_request.requested_limit) }}</span>
                  </div>
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">TOP Diajukan</span>
                    <span class="font-strong text-right">{{ detail.credit_request.requested_top ?? '-' }} hari</span>
                  </div>
                </dl>
              </template>
            </CardSection>

            <div class="space-y-4">
              <CardSection v-if="isInReview" title="Keputusan" description="Putuskan approve atau reject siklus ini."
                icon="Gavel" icon-class="bg-blue-100 text-blue-600">
                <div class="space-y-3">
                  <div class="flex gap-2">
                    <Button type="button" class="flex-1 items-center justify-center gap-2"
                      :variant="decisionMode === 'approve' ? 'primary' : 'outline-secondary'"
                      @click="decisionMode = 'approve'">
                      Approve
                    </Button>
                    <Button type="button" class="flex-1 items-center justify-center gap-2"
                      :variant="decisionMode === 'reject' ? 'danger' : 'outline-secondary'"
                      @click="decisionMode = 'reject'">
                      Reject
                    </Button>
                  </div>

                  <template v-if="decisionMode === 'approve'">
                    <CurrencyField v-model="approvedLimit" label="Credit Limit Disetujui" required
                      :disabled="approveBlocked || submitting" />
                    <NumberField v-model="approvedTop" label="TOP Disetujui" suffix="hari" :decimals="0" required
                      :disabled="approveBlocked || submitting" />
                    <RichTextField v-model="financialReview" label="Financial Review"
                      :disabled="approveBlocked || submitting" />

                    <Alert v-if="approveBlocked" variant="soft-warning">
                      LCR belum diverifikasi Logistik — Approve tidak bisa dilakukan.
                    </Alert>
                  </template>

                  <template v-else>
                    <FormLabel>Alasan Penolakan</FormLabel>
                    <FormTextarea v-model="rejectNote" rows="3" :disabled="submitting" />
                  </template>

                  <Button class="inline-flex w-full items-center justify-center gap-2"
                    :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :disabled="decisionSubmitDisabled"
                    @click="confirmOpen = true">
                    <Lucide icon="Send" class="h-4 w-4" />
                    Ajukan Keputusan
                  </Button>
                </div>
              </CardSection>

              <CardSection v-if="detail.status === 'approved'" title="Hasil Keputusan Admin Finance" icon="BadgeCheck"
                icon-class="bg-emerald-100 text-emerald-600">
                <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">Credit Limit Disetujui</span>
                    <span class="font-strong text-right">{{ formatCurrency(detail.approved_limit) }}</span>
                  </div>
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">TOP Disetujui</span>
                    <span class="font-strong text-right">{{ detail.approved_top ?? '-' }} hari</span>
                  </div>
                </dl>
                <div class="mt-3">
                  <div class="font-label">Financial Review</div>
                  <div v-if="detail.financial_review"
                    class="font-body rich-text-content mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                    v-html="detail.financial_review" />
                  <div v-else class="font-body mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">-</div>
                </div>
              </CardSection>
            </div>
          </div>
        </Tab.Panel>

        <Tab.Panel>
          <CardSection title="LCR" icon="MapPin" icon-class="bg-amber-100 text-amber-600">
            <template #action>
              <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                :class="lcrAllApproved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                {{ lcrAllApproved ? 'Disetujui Semua' : 'Belum Lengkap' }}
              </span>
            </template>

            <div v-if="!detail.lcr?.sites?.length"
              class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
              <div class="font-body">Belum ada data LCR tercatat.</div>
            </div>

            <div v-else class="overflow-x-auto">
              <Table bordered sm class="font-body">
                <Table.Thead class="bg-slate-50">
                  <Table.Th class="font-label">Nama Site</Table.Th>
                  <Table.Th class="font-label">Status</Table.Th>
                </Table.Thead>
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="site in detail.lcr.sites" :key="site.id_lcr">
                    <Table.Td class="font-strong">{{ site.site_name || '-' }}</Table.Td>
                    <Table.Td>{{ lcrStatusLabel(site.approval_status) }}</Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </CardSection>
        </Tab.Panel>

        <Tab.Panel>
          <CardSection title="Penawaran" description="Daftar Penawaran milik customer ini." icon="FileText"
            icon-class="bg-cyan-100 text-cyan-600">
            <div v-if="penawaranLoading" class="flex min-h-[100px] items-center justify-center gap-3 text-slate-500">
              <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
              <span class="font-body">Memuat Penawaran...</span>
            </div>

            <div v-else-if="penawaranError"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
              {{ penawaranError }}
            </div>

            <div v-else-if="penawarans.length === 0"
              class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
              <div class="font-body">Belum ada Penawaran tercatat.</div>
            </div>

            <div v-else class="overflow-x-auto">
              <Table bordered sm class="font-body">
                <Table.Thead class="bg-slate-50">
                  <Table.Th class="font-label">Nomor Penawaran</Table.Th>
                  <Table.Th class="font-label">Masa Berlaku</Table.Th>
                  <Table.Th class="font-label text-right">Volume</Table.Th>
                  <Table.Th class="font-label text-right">Harga Dasar</Table.Th>
                  <Table.Th class="font-label text-right">Ongkos Angkut</Table.Th>
                </Table.Thead>
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="p in penawarans" :key="p.id_penawaran">
                    <Table.Td class="font-strong">{{ p.nomor_penawaran || '-' }}</Table.Td>
                    <Table.Td>{{ formatDate(p.masa_berlaku) }} &ndash; {{ formatDate(p.sampai_dengan) }}</Table.Td>
                    <Table.Td class="font-num text-right">{{ formatNumber(p.total_volume) }}</Table.Td>
                    <Table.Td class="font-num text-right">{{ formatCurrency(p.harga_dasar) }}</Table.Td>
                    <Table.Td class="font-num text-right">{{ formatCurrency(p.oat) }}</Table.Td>
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
</template>
