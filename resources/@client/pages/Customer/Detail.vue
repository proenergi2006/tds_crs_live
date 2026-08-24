<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Tab } from '@/components/Base/Headless'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import CustomerDataTab from './components/CustomerDataTab.vue'
import SalesReviewDataTab from './components/SalesReviewDataTab.vue'
import LcrDataTab from './components/LcrDataTab.vue'
import CreditDataTab from './components/CreditDataTab.vue'

const route = useRoute()
const router = useRouter()
const { error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

/* State: load utama -- dipakai bareng header & CustomerDataTab biar GET customer cukup sekali */
const loading = ref(true)
const customerSummary = ref<any>({})

const tabItems = computed(() => [
  { label: 'Data Customer', description: 'Informasi lengkap customer', complete: !!customerSummary.value?.tab_completeness?.data_customer },
  { label: 'Sales Review', description: 'Review KYC dari Marketing', complete: !!customerSummary.value?.tab_completeness?.review },
  { label: 'Credit Application / TOP', description: 'Pengajuan limit kredit & TOP', complete: !!customerSummary.value?.tab_completeness?.credit },
  { label: 'LCR', description: 'Hasil survei customer site', complete: !!customerSummary.value?.tab_completeness?.lcr },
])

const hasParentCompany = computed(() => (customerSummary.value?.parent_company !== null || customerSummary.value?.parent_company !== null))
const kycStatus = computed(() => customerSummary.value?.latest_verification?.kyc_status ?? null)

// komputasi dari kelengkapan 4 tab aja, belum nempel ke endpoint apa pun -- masih indikator visual doang
const isVerified = computed(() =>
  tabItems.value.every(t => t.complete)
)

async function fetchCustomer() {
  try {
    const { data } = await axios.get(`/api/customers/${idCustomer}`)
    customerSummary.value = data || {}
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer.')
  }
}

async function loadCustomer() {
  loading.value = true
  await fetchCustomer()
  loading.value = false
}
onMounted(loadCustomer)

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
              <Button v-if="isVerified" variant="primary">
                <Lucide icon="ShieldCheck" class="mr-2 w-4 h-4" />
                Proses Verifikasi
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
                  :style="t.complete ? 'background-image: radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.18) 0%, rgba(255,255,255,0) 60%);' : ''"
                  :class="selected ? 'border-primary ring-1 ring-primary/40' : ''">
                  <div>
                    <div class="font-strong text-slate-800">{{ t.label }}</div>
                    <div class="font-caption text-slate-500">{{ t.description }}</div>
                  </div>
                  <Lucide icon="CheckCircle" class="w-5 h-5 shrink-0"
                    :class="t.complete ? 'text-emerald-500' : 'text-slate-300'" />
                </div>
              </Tab>
            </Tab.List>
          </template>
        </PageHeader>

        <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
          <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
          <span class="font-body">Memuat data...</span>
        </div>

        <Tab.Panels v-else class="mt-4">
          <Tab.Panel>
            <CustomerDataTab :id-customer="idCustomer" :customer="customerSummary" @updated="fetchCustomer" />
          </Tab.Panel>
          <Tab.Panel>
            <SalesReviewDataTab :id-customer="idCustomer" :kyc-status="kycStatus" />
          </Tab.Panel>
          <Tab.Panel>
            <CreditDataTab :id-customer="idCustomer" :kyc-status="kycStatus" />
          </Tab.Panel>
          <Tab.Panel>
            <LcrDataTab :id-customer="idCustomer" :customer-logistik="customerSummary?.logistik" />
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>
  </div>
</template>
