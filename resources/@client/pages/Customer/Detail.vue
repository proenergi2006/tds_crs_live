<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Tab } from '@/components/Base/Headless'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import CustomerDataTab from './components/CustomerDataTab.vue'
import SalesReviewDataTab from './components/SalesReviewDataTab.vue'
import LcrDataTab from './components/LcrDataTab.vue'
import CreditDataTab from './components/CreditDataTab.vue'

const route = useRoute()
const router = useRouter()
const { error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

/* State: load utama, dipakai bareng oleh header & CustomerDataTab (supaya
   tidak fetch GET /api/customers/{id} dua kali). Tab 2/3/4 untuk sementara
   jadi placeholder (lihat components/*DataTab.vue) -- state/logic lama
   masing-masing (Sales Review, LCR, Credit) sudah dihapus dari sini, akan
   disusun ulang di pekerjaan berikutnya. */
const loading = ref(true)
const customerSummary = ref<any>({})

const tabItems = [
  { label: '1. Data Customer', icon: 'FileText' as const },
  { label: '2. Sales Review', icon: 'ClipboardEdit' as const },
  { label: '3. LCR', icon: 'ClipboardList' as const },
  { label: '4. Credit Application / TOP', icon: 'Wallet' as const },
]

const customerInitial = computed(() => (customerSummary.value?.company_name || '?').trim().charAt(0).toUpperCase() || '?')
const hasOnboardingData = computed(() => customerSummary.value?.latest_verification?.is_submitted === true)

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
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-violet-600 text-xl font-strong text-white">
            {{ customerInitial }}
          </div>
          <div>
            <h2 class="font-display">{{ customerSummary.company_name || 'Detail Customer' }}</h2>
            <span v-if="hasOnboardingData"
              class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
              <Lucide icon="CheckCircle2" class="h-3.5 w-3.5" /> Formulir Onboarding Terkirim
            </span>
            <span v-else
              class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
              Belum Submit Onboarding
            </span>
          </div>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
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
              <Lucide :icon="t.icon" class="h-4 w-4" />
              <span>{{ t.label }}</span>
            </Tab.Button>
          </Tab>
        </Tab.List>

        <Tab.Panels class="mt-4">
          <Tab.Panel>
            <CustomerDataTab :id-customer="idCustomer" :customer="customerSummary" :has-onboarding-data="hasOnboardingData" />
          </Tab.Panel>
          <Tab.Panel>
            <SalesReviewDataTab :id-customer="idCustomer" />
          </Tab.Panel>
          <Tab.Panel>
            <LcrDataTab :id-customer="idCustomer" />
          </Tab.Panel>
          <Tab.Panel>
            <CreditDataTab :id-customer="idCustomer" />
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>
  </div>
</template>
