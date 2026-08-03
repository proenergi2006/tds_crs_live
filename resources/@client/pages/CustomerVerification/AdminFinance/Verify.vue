<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { Tab } from '@/components/Base/Headless'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RichTextField from '@/components/SystemDesign/Form/RichTextField.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import CustomerDataTab from '@/pages/Customer/components/CustomerDataTab.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDate, formatNumber } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

// 5 fetch terpisah, sengaja gak ada endpoint agregasi -- biar logic shaping
// data gak dobel di 2 tempat. Close KYC gak bisa di-undo, jadi wajib lewat
// dialog konfirmasi eksplisit.

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

/* State: bootstrap (reviewShow()) */
const idVerification = Number(route.params.id)
const loading = ref(true)
const bootstrapError = ref<string | null>(null)
const idCustomer = ref<number | null>(null)
const kycStatus = ref<string | null>(null)
const customer = ref<any>(null)

/* State: Tab 1 -- Data Customer, reuse Customer/Detail.vue::CustomerDataTab.
   Butuh shape customer lengkap, jadi pakai GET /api/customers/{id} yang sama,
   bukan proyeksi 6-kolom dari reviewShow() yang cuma cukup buat page title. */
const fullCustomer = ref<any>(null)
const fullCustomerLoading = ref(true)

/* State: Tab 2 -- Sales Review */
const review = ref<any>(null)
const reviewLoading = ref(true)
const reviewError = ref<string | null>(null)

/* State: Tab 3 -- LCR */
const lcrSites = ref<any[]>([])
const lcrLoading = ref(true)
const lcrError = ref<string | null>(null)

/* State: Tab 4 -- Credit Application */
const submission = ref<any>(null)
const submissionLoading = ref(true)
const submissionError = ref<string | null>(null)

/* State: Penawaran Lookup */
const penawarans = ref<any[]>([])
const penawaranLoading = ref(true)
const penawaranError = ref<string | null>(null)

/* Tab layout diselaraskan dengan Customer/Detail.vue (4 tab + tambahan Penawaran) */
const tabItems = [
  { label: 'Data Customer' },
  { label: 'Sales Review' },
  { label: 'Credit Application / TOP' },
  { label: 'LCR' },
  { label: 'Penawaran' },
]

/* State: Close KYC */
const closeDialogOpen = ref(false)
const closeLoading = ref(false)
const creditLimitApproval = ref<number>(0)
const topApproval = ref<number>(0)
const financialReview = ref<string>('')

/* Computed */
const kycStatusLabel = computed(() => {
  return { draft: 'Draft', forwarded: 'Diteruskan ke Admin Finance', closed: 'Ditutup' }[kycStatus.value ?? ''] ?? '-'
})
const pageTitle = computed(() => customer.value?.company_name ? `Verifikasi KYC — ${customer.value.company_name}` : 'Verifikasi KYC Customer')
const pageDescription = computed(() => `Status KYC saat ini: ${kycStatusLabel.value}.`)
// Falsy-safe: kycStatus null (misal bootstrap gagal) tetap dianggap
// draft/terkunci, bukan malah diam-diam mengizinkan aksi.
const printDisabled = computed(() => kycStatus.value !== 'forwarded' && kycStatus.value !== 'closed')
const closeFormDisabled = computed(() => kycStatus.value !== 'forwarded')
const hasOnboardingData = computed(() => fullCustomer.value?.latest_verification?.is_submitted === true)

/* Fetch */
async function fetchAll() {
  loading.value = true
  bootstrapError.value = null
  try {
    const { data } = await axios.get(`/api/review/customer-verifications/${idVerification}`)
    idCustomer.value = data.id_customer
    kycStatus.value = data.kyc_status
    customer.value = data.customer
  } catch (e: any) {
    bootstrapError.value = e.response?.data?.message ?? 'Gagal memuat data verifikasi KYC.'
    loading.value = false
    return
  }
  loading.value = false

  await Promise.all([fetchFullCustomer(), fetchReview(), fetchLcrSites(), fetchSubmission(), fetchPenawarans()])
}

async function fetchFullCustomer() {
  fullCustomerLoading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer.value}`)
    fullCustomer.value = data || {}
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat Data Customer.')
  } finally {
    fullCustomerLoading.value = false
  }
}

async function fetchReview() {
  reviewLoading.value = true
  reviewError.value = null
  try {
    const { data } = await axios.get(`/api/review/customer-verifications/${idVerification}/review`)
    review.value = data
  } catch (e: any) {
    reviewError.value = e.response?.data?.message ?? 'Gagal memuat Sales Review.'
  } finally {
    reviewLoading.value = false
  }
}

async function fetchLcrSites() {
  lcrLoading.value = true
  lcrError.value = null
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer.value}/lcr-sites`)
    lcrSites.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    lcrError.value = e.response?.data?.message ?? 'Gagal memuat data LCR.'
  } finally {
    lcrLoading.value = false
  }
}

async function fetchSubmission() {
  submissionLoading.value = true
  submissionError.value = null
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer.value}/credit-submissions`)
    submission.value = Array.isArray(data) && data.length > 0 ? data[0] : null
  } catch (e: any) {
    submissionError.value = e.response?.data?.message ?? 'Gagal memuat Credit Application.'
  } finally {
    submissionLoading.value = false
  }
}

async function fetchPenawarans() {
  penawaranLoading.value = true
  penawaranError.value = null
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer.value}/penawarans`)
    penawarans.value = Array.isArray(data?.data) ? data.data : []
  } catch (e: any) {
    penawaranError.value = e.response?.data?.message ?? 'Gagal memuat Penawaran.'
  } finally {
    penawaranLoading.value = false
  }
}

/* Actions */
// window.open() langsung ke URL API gak bisa dipakai di sini. Itu jadi
// navigasi browser biasa (bukan lewat axios), gak dikenali middleware
// auth:sanctum sebagai request stateful SPA, jadi dianggap unauthenticated
// dan diarahkan ke route 'login' yang memang gak ada di app API-only ini
// (RouteNotFoundException). Pola blob+tab pre-open ini sudah dipakai di
// 10+ halaman lain buat kasus yang sama, lihat utils/pdfPreviewTab.ts.
async function openDocument() {
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
    // responseType: 'blob' bikin e.response.data ikut jadi Blob saat error juga
    // (bukan ke-parse jadi JSON), jadi .message-nya gak kebaca langsung -- pakai
    // pesan generic saja, sama seperti preview() di Penawaran/Detail.vue.
    tab?.close()
    notifyError('Gagal', 'Gagal membuka dokumen KYC.')
  }
}

async function openDataCustomerDocument() {
  const tab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/api/review/customer-verifications/${idVerification}/document/data-customer`, {
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
    tab?.close()
    notifyError('Gagal', 'Gagal membuka dokumen Data Customer.')
  }
}

async function submitClose() {
  closeLoading.value = true
  try {
    const { data } = await axios.post(`/api/review/customer-verifications/${idVerification}/close`, {
      credit_limit_approval: creditLimitApproval.value,
      top_approval: topApproval.value,
      financial_review: financialReview.value,
    })

    kycStatus.value = data.kyc_status
    closeDialogOpen.value = false
    success('Berhasil', 'KYC berhasil ditutup.')
    router.push({ name: 'review-data-customer-admin' })
  } catch (e: any) {
    if (e.response?.status === 422) {
      notifyError('Gagal', e.response?.data?.message ?? 'Belum ada pengajuan credit untuk customer ini.')
    } else if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'KYC belum di-forward atau sudah ditutup.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menutup KYC.')
    }
  } finally {
    closeLoading.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(fetchAll)
</script>

<template>
  <FormPage :title="pageTitle" :description="pageDescription" size="full" layout="sidebar" surface="plain"
    :show-footer="false" :loading="loading" :error="bootstrapError" @cancel="goBack">
    <template #action>
      <Button variant="outline-secondary" @click="goBack">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali
      </Button>
    </template>

    <Tab.Group v-if="!loading && idCustomer !== null">
      <Tab.List variant="link-tabs" class="gap-1 border-b border-slate-200">
        <Tab v-for="t in tabItems" :key="t.label" :full-width="false" v-slot="{ selected }">
          <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
            ? 'text-primary border-b-primary font-medium'
            : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
            <span>{{ t.label }}</span>
          </Tab.Button>
        </Tab>
      </Tab.List>

      <Tab.Panels class="mt-4">
        <Tab.Panel>
          <div v-if="fullCustomerLoading" class="flex min-h-[200px] items-center justify-center gap-3 text-slate-500">
            <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
            <span class="font-body">Memuat Data Customer...</span>
          </div>
          <CustomerDataTab v-else :id-customer="idCustomer!" :customer="fullCustomer"
            :has-onboarding-data="hasOnboardingData" />
        </Tab.Panel>

        <Tab.Panel>
          <CardSection title="Sales Review" icon="ClipboardCheck" icon-class="bg-emerald-100 text-emerald-600">
            <div v-if="reviewLoading" class="flex min-h-[100px] items-center justify-center gap-3 text-slate-500">
              <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
              <span class="font-body">Memuat Sales Review...</span>
            </div>

            <div v-else-if="reviewError"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
              {{ reviewError }}
            </div>

            <div v-else class="space-y-4">
              <div v-for="qa in review?.review_answers ?? []" :key="qa.question_code">
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
          <CardSection title="Credit Application" icon="CreditCard" icon-class="bg-violet-100 text-violet-600">
            <div v-if="submissionLoading" class="flex min-h-[100px] items-center justify-center gap-3 text-slate-500">
              <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
              <span class="font-body">Memuat Credit Application...</span>
            </div>

            <div v-else-if="submissionError"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
              {{ submissionError }}
            </div>

            <div v-else-if="!submission"
              class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
              <div class="font-body">Belum ada pengajuan credit untuk customer ini.</div>
            </div>

            <template v-else>
              <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
                <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                  <span class="font-label">Jenis Pengajuan</span>
                  <span class="font-strong text-right">{{ submission.submission_type_label || '-' }}</span>
                </div>
                <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                  <span class="font-label">Credit Limit Diajukan</span>
                  <span class="font-strong text-right">{{ formatCurrency(submission.credit_limit_request) }}</span>
                </div>
                <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                  <span class="font-label">TOP Diajukan</span>
                  <span class="font-strong text-right">{{ submission.top_request ?? '-' }} hari</span>
                </div>
              </dl>

              <div v-if="submission.credit_limit_approval !== null" class="mt-5 border-t border-slate-100 pt-4">
                <div class="font-section mb-3">Hasil Keputusan Admin Finance</div>
                <dl class="grid grid-cols-1 gap-y-3 gap-x-8 sm:grid-cols-2">
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">Credit Limit Disetujui</span>
                    <span class="font-strong text-right">{{ formatCurrency(submission.credit_limit_approval) }}</span>
                  </div>
                  <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                    <span class="font-label">TOP Disetujui</span>
                    <span class="font-strong text-right">{{ submission.top_approval ?? '-' }} hari</span>
                  </div>
                </dl>
                <div class="mt-3">
                  <div class="font-label">Financial Review</div>
                  <div v-if="submission.financial_review"
                    class="font-body rich-text-content mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                    v-html="submission.financial_review" />
                  <div v-else class="font-body mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">-</div>
                </div>
              </div>
            </template>
          </CardSection>
        </Tab.Panel>

        <Tab.Panel>
          <CardSection title="LCR" icon="MapPin" icon-class="bg-amber-100 text-amber-600">
            <div v-if="lcrLoading" class="flex min-h-[100px] items-center justify-center gap-3 text-slate-500">
              <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
              <span class="font-body">Memuat data LCR...</span>
            </div>

            <div v-else-if="lcrError"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-body !text-rose-700">
              {{ lcrError }}
            </div>

            <div v-else-if="lcrSites.length === 0"
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
                  <Table.Tr v-for="site in lcrSites" :key="site.id_lcr">
                    <Table.Td class="font-strong">{{ site.site_name || '-' }}</Table.Td>
                    <Table.Td>{{ site.approval?.status_label || 'Belum ada approval' }}</Table.Td>
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

    <template #sidebar>
      <CardSection title="Cetak Dokumen" description="Ringkasan KYC untuk rapat management (offline)." icon="Printer"
        icon-class="bg-slate-100 text-slate-600">
        <div class="space-y-2">
          <Button variant="outline-primary" class="inline-flex w-full items-center justify-center gap-2"
            :disabled="printDisabled" @click="openDocument">
            <Lucide icon="Printer" class="h-4 w-4" />
            Cetak Dokumen (Gabungan)
          </Button>
          <Button variant="outline-secondary" class="inline-flex w-full items-center justify-center gap-2"
            :disabled="printDisabled" @click="openDataCustomerDocument">
            <Lucide icon="Printer" class="h-4 w-4" />
            Cetak Data Customer
          </Button>
        </div>
        <p v-if="printDisabled" class="font-caption mt-2">
          Dokumen hanya bisa dicetak setelah KYC di-forward.
        </p>
      </CardSection>

      <CardSection title="Tutup KYC" description="Keputusan final Admin Finance." icon="ShieldCheck"
        icon-class="bg-primary/10 text-primary">
        <div class="space-y-3">
          <RichTextField v-model="financialReview" label="Financial Review" :disabled="closeFormDisabled" />
          <CurrencyField v-model="creditLimitApproval" label="Credit Limit Disetujui" :disabled="closeFormDisabled" />
          <NumberField v-model="topApproval" label="TOP Disetujui" suffix="hari" :decimals="0"
            :disabled="closeFormDisabled" />

          <p v-if="kycStatus === 'draft'"
            class="font-body rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 !text-amber-700">
            KYC belum di-forward, form ini belum bisa diisi.
          </p>
          <p v-else-if="kycStatus === 'closed'"
            class="font-body rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 !text-emerald-700">
            KYC sudah ditutup, keputusan tidak bisa diubah lagi.
          </p>

          <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2"
            :disabled="closeFormDisabled" @click="closeDialogOpen = true">
            <Lucide icon="Lock" class="h-4 w-4" />
            Tutup KYC
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <ConfirmDialog :open="closeDialogOpen" title="Tutup KYC customer ini?"
    description="Tindakan ini TIDAK BISA dibatalkan. Keputusan credit limit & TOP akan menjadi final."
    confirm-text="Ya, Tutup KYC" icon="AlertTriangle" icon-class="bg-danger/10 text-danger" variant="danger"
    :loading="closeLoading" @close="closeDialogOpen = false" @confirm="submitClose" />
</template>
