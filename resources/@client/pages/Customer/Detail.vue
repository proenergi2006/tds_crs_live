<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Tab, Dialog } from '@/components/Base/Headless'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

import CustomerDataTab from './components/CustomerDataTab.vue'
import SalesReviewDataTab from './components/SalesReviewDataTab.vue'
import LcrDataTab from './components/LcrDataTab.vue'
import CreditDataTab from './components/CreditDataTab.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

/* State: load utama, dipakai bareng oleh header & CustomerDataTab (supaya
   tidak fetch GET /api/customers/{id} dua kali). */
const loading = ref(true)
const customerSummary = ref<any>({})

/* State: Forward ke Admin Finance */
const forwarding = ref(false)
const incompleteTabsOpen = ref(false)
const incompleteTabs = ref<string[]>([])
const incompleteTabLabels: Record<string, string> = {
  review: 'Sales Review',
  credit: 'Credit Application',
}

const tabItems = [
  { label: 'Data Customer', icon: 'FileText' as const },
  { label: 'Sales Review', icon: 'ClipboardEdit' as const },
  { label: 'Credit Application / TOP', icon: 'Wallet' as const },
  { label: 'LCR', icon: 'ClipboardList' as const },
]

const customerInitial = computed(() => (customerSummary.value?.company_name || '?').trim().charAt(0).toUpperCase() || '?')
const hasOnboardingData = computed(() => customerSummary.value?.latest_verification?.is_submitted === true)
const hasParentCompany = computed(() => (customerSummary.value?.parent_company !== null || customerSummary.value?.parent_company !== null))
const kycStatus = computed(() => customerSummary.value?.latest_verification?.kyc_status ?? null)
const idVerification = computed(() => customerSummary.value?.latest_verification?.id_verification ?? null)
const canForward = computed(() =>
  kycStatus.value === 'draft' &&
  auth.can('customer.manage') &&
  (auth.can('customer.viewAny') || Number(customerSummary.value?.id_user) === Number(auth.user?.id))
)

async function loadCustomer() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}`)
    customerSummary.value = data || {}
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer.')
  } finally {
    loading.value = false
  }
}
onMounted(loadCustomer)

function goBack() {
  router.push({ name: 'customers-list' })
}

async function submitForward() {
  if (!idVerification.value || forwarding.value) return

  forwarding.value = true
  try {
    await axios.post(`/api/review/customer-verifications/${idVerification.value}/forward`)
    await loadCustomer()
    success('Berhasil', 'KYC berhasil di-forward ke Admin Finance.')
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422 && e.response?.data?.incomplete_tabs) {
      incompleteTabs.value = (e.response.data.incomplete_tabs as string[]).map(
        (k) => incompleteTabLabels[k] ?? k,
      )
      incompleteTabsOpen.value = true
    } else if (status === 409) {
      notifyError('Gagal', 'KYC sudah pernah di-forward.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal forward KYC ke Admin Finance.')
    }
  } finally {
    forwarding.value = false
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div
        class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          <div
            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-violet-600 text-xl font-strong text-white">
            {{ customerInitial }}
          </div>
          <div>
            <h2 class="font-display">{{ customerSummary.company_name || 'Detail Customer' }}</h2>
            <span v-if="hasParentCompany">Part of: {{ customerSummary.parent_company }}</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="canForward" variant="primary" :disabled="forwarding" @click="submitForward">
            <Lucide v-if="forwarding" icon="Loader2" class="mr-2 h-4 w-4 animate-spin" />
            <Lucide v-else icon="Send" class="mr-2 h-4 w-4" />
            Forward ke Admin Finance
          </Button>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </div>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data...</span>
      </div>

      <Tab.Group v-else>
        <Tab.List variant="link-tabs" class="gap-1 border-b border-slate-200">
          <Tab v-for="t in tabItems" :key="t.label" :full-width="false" v-slot="{ selected }">
            <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
              ? 'text-primary border-b-primary font-medium'
              : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
              <!-- <Lucide :icon="t.icon" class="h-4 w-4" /> -->
              <span>{{ t.label }}</span>
            </Tab.Button>
          </Tab>
        </Tab.List>

        <Tab.Panels class="mt-4">
          <Tab.Panel>
            <CustomerDataTab :id-customer="idCustomer" :customer="customerSummary"
              :has-onboarding-data="hasOnboardingData" />
          </Tab.Panel>
          <Tab.Panel>
            <SalesReviewDataTab :id-customer="idCustomer" :id-verification="idVerification" :kyc-status="kycStatus" />
          </Tab.Panel>
          <Tab.Panel>
            <CreditDataTab :id-customer="idCustomer" :kyc-status="kycStatus" />
          </Tab.Panel>
          <Tab.Panel>
            <LcrDataTab :id-customer="idCustomer" />
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>

    <Dialog :open="incompleteTabsOpen" size="md" @close="incompleteTabsOpen = false">
      <Dialog.Panel>
        <div class="p-6">
          <div class="border-b border-slate-200 pb-4">
            <h3 class="font-header">Tab Belum Lengkap</h3>
            <p class="font-caption mt-1 text-slate-500">
              Lengkapi tab berikut sebelum forward ke Admin Finance.
            </p>
          </div>
          <ul class="mt-4 list-disc space-y-1 pl-5">
            <li v-for="t in incompleteTabs" :key="t" class="font-body">{{ t }}</li>
          </ul>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
          <Button variant="outline-secondary" @click="incompleteTabsOpen = false">Tutup</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>
