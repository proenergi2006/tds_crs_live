<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import type { Icon } from "@/components/Base/Lucide/Lucide.vue";
import { Tab } from '@/components/Base/Headless'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import DocumentDownloadModal, { type DownloadableDocument } from '@/components/SystemDesign/Dialog/DocumentDownloadModal.vue'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

import CustomerDataTab from './components/CustomerDataTab.vue'
import SalesReviewDataTab from './components/SalesReviewDataTab.vue'
import LcrDataTab from './components/LcrDataTab.vue'
import CreditDataTab from './components/CreditDataTab.vue'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

const VERIFICATION_GROUP_LABELS: Record<string, string> = {
  data_customer: 'Data Customer',
  review: 'Sales Review',
  credit: 'Credit Application / TOP',
}

type TabStatus = "empty" | "in_progress" | "complete" | "rejected";

const TAB_STATUS_STYLES: Record<TabStatus, { icon: Icon; iconClass: string; gradient: string }> = {
  empty: { icon: "CheckCircle", iconClass: "text-slate-300", gradient: "" },
  in_progress: {
    icon: "Clock",
    iconClass: "text-amber-500",
    gradient: "background-image: radial-gradient(circle at 100% 100%, rgba(245, 158, 11, 0.18) 0%, rgba(255,255,255,0) 60%);",
  },
  complete: {
    icon: "CheckCircle",
    iconClass: "text-emerald-500",
    gradient: "background-image: radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.18) 0%, rgba(255,255,255,0) 60%);",
  },
  rejected: {
    icon: "XCircle",
    iconClass: "text-rose-500",
    gradient: "background-image: radial-gradient(circle at 100% 100%, rgba(244, 63, 94, 0.18) 0%, rgba(255,255,255,0) 60%);",
  },
};

const loading = ref(true)
const submitting = ref(false)
const customerSummary = ref<any>({})
const downloadModalOpen = ref(false)
const downloadingDocument = ref(false)

const tabItems = computed(() => [
  { label: "Data Customer", description: "Informasi lengkap customer", status: (customerSummary.value?.tab_status?.data_customer ?? "empty") as TabStatus },
  { label: "Sales Review", description: "Review KYC dari Marketing", status: (customerSummary.value?.tab_status?.review ?? "empty") as TabStatus },
  { label: "Credit Application / TOP", description: "Pengajuan limit kredit & TOP", status: (customerSummary.value?.tab_status?.credit ?? "empty") as TabStatus },
  { label: "LCR", description: "Hasil survei customer site", status: (customerSummary.value?.tab_status?.lcr ?? "empty") as TabStatus },
])

const hasParentCompany = computed(() => !!customerSummary.value?.parent_company)
const isUnderReview = computed<boolean>(() => customerSummary.value?.latest_verification?.status === 'in_review')
const isVerified = computed<boolean>(() => !!customerSummary.value?.is_verified)
const needsReverification = computed<boolean>(() => !!customerSummary.value?.needs_reverification)
const canSubmitVerification = computed<boolean>(() => !isUnderReview.value && (!isVerified.value || needsReverification.value))
const canDownloadDocument = computed<boolean>(() => ['in_review', 'approved'].includes(customerSummary.value?.latest_verification?.status))
const isEditLocked = computed<boolean>(() => !!customerSummary.value?.is_edit_locked)

async function loadCustomer() {
  loading.value = true
  await fetchCustomer()
  loading.value = false
}
onMounted(loadCustomer)

async function fetchCustomer() {
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}`)
    customerSummary.value = data || {}
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer.')
  }
}

async function refreshCompleteness() {
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}/tab-completeness`)
    if (!customerSummary.value) return;
    const { tab_status, ...tabCompleteness } = data;
    customerSummary.value.tab_completeness = tabCompleteness;
    customerSummary.value.tab_status = tab_status;
  } catch { }
}

async function submitVerification(): Promise<void> {
  submitting.value = true
  try {
    await axios.post(`/api/customers/${idCustomer}/verification`, {})
    success('Berhasil', 'Verifikasi customer berhasil diajukan.')
    await fetchCustomer()
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Verifikasi customer ini sedang dalam review.')
    } else if (e.response?.status === 422) {
      const incompleteGroups = e.response?.data?.incomplete_groups ?? []
      const labels = incompleteGroups.map((group: string) => VERIFICATION_GROUP_LABELS[group] ?? group).join(', ')
      notifyError('Gagal', `Data belum lengkap: ${labels}`)
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal memproses verifikasi.')
    }
  } finally {
    submitting.value = false
  }
}

async function openDocument(category: DownloadableDocument): Promise<void> {
  const tab = openPdfLoadingTab()
  try {
    const response = await axios.get(
      `/api/review/customer-verifications/${customerSummary.value.latest_verification.id_verification}/document/${category}`,
      { responseType: 'blob' },
    )
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (tab) {
      tab.location.href = url
    } else {
      window.open(url, '_blank')
    }
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch {
    // responseType: 'blob' bikin error response ikut jadi Blob, .message gak kebaca -- pesan generic aja
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

function goBack() {
  router.push({ name: 'customers-list' })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-x">

      <Tab.Group>
        <PageHeader variant="flat" :title="customerSummary.company_name || 'Detail Customer'"
          :description="hasParentCompany ? `Part of: ${customerSummary.parent_company}` : 'Detail data dan verifikasi customer'">
          <template #action>
            <div class="flex items-center gap-2">
              <Button v-if="canSubmitVerification" variant="primary" :disabled="submitting" @click="submitVerification">
                <Lucide v-if="submitting" icon="Loader2" class="mr-2 w-4 h-4 animate-spin" />
                <Lucide v-else icon="ShieldCheck" class="mr-2 w-4 h-4" />
                Proses Verifikasi
              </Button>
              <Button v-if="canDownloadDocument" variant="outline-primary" @click="downloadModalOpen = true">
                <Lucide icon="Download" class="mr-2 w-4 h-4" />
                Unduh Dokumen
              </Button>
              <Button variant="outline-secondary" @click="goBack">
                <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
                Kembali
              </Button>
            </div>
          </template>
          <template #body>
            <Tab.List variant="link-tabs" class="gap-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
              <Tab v-for="t in tabItems" :key="t.label" :full-width="false" v-slot="{ selected }">
                <div class="flex justify-between items-center gap-3 px-4 py-3 transition cursor-pointer box"
                  :style="TAB_STATUS_STYLES[t.status].gradient"
                  :class="selected ? 'border-primary ring-1 ring-primary/40' : ''">
                  <div>
                    <div class="text-body-strong text-slate-800">{{ t.label }}</div>
                    <div class="text-caption text-slate-500">{{ t.description }}</div>
                  </div>
                  <Lucide :icon="TAB_STATUS_STYLES[t.status].icon" class="w-5 h-5 shrink-0"
                    :class="TAB_STATUS_STYLES[t.status].iconClass" />
                </div>
              </Tab>
            </Tab.List>
          </template>
        </PageHeader>

        <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
          <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
          <span class="text-body">Memuat data...</span>
        </div>

        <template v-else>
          <Alert v-if="isEditLocked && !isUnderReview" variant="soft-primary" class="mt-4">
            Customer sudah terverifikasi. Data tidak dapat diubah.
          </Alert>

          <Tab.Panels class="mt-4">
            <Tab.Panel>
              <CustomerDataTab :id-customer="idCustomer" :customer="customerSummary" :readonly="isEditLocked"
                @updated="fetchCustomer" />
            </Tab.Panel>
            <Tab.Panel>
              <SalesReviewDataTab :id-customer="idCustomer" :is-under-review="isUnderReview" :readonly="isEditLocked"
                @saved="refreshCompleteness" />
            </Tab.Panel>
            <Tab.Panel>
              <CreditDataTab :id-customer="idCustomer" :is-under-review="isUnderReview" :readonly="isEditLocked"
                :latest-approved-verification="customerSummary?.latest_approved_verification ?? null"
                @saved="refreshCompleteness" />
            </Tab.Panel>
            <Tab.Panel>
              <LcrDataTab :id-customer="idCustomer" :customer-logistik="customerSummary?.logistik"
                :readonly="isEditLocked" @saved="refreshCompleteness" />
            </Tab.Panel>
          </Tab.Panels>
        </template>
      </Tab.Group>
    </div>

    <DocumentDownloadModal :open="downloadModalOpen" :loading="downloadingDocument" @close="downloadModalOpen = false"
      @select="handleDocumentSelect" />
  </div>
</template>
