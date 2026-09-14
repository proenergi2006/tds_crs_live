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
const { success, error: notifyError } = useNotification()

const idCustomer = Number(route.params.id)

const VERIFICATION_GROUP_LABELS: Record<string, string> = {
  data_customer: 'Data Customer',
  review: 'Sales Review',
  credit: 'Credit Application / TOP',
}

const loading = ref(true)
const submitting = ref(false)
const customerSummary = ref<any>({})

const tabItems = computed(() => [
  { label: 'Data Customer', description: 'Informasi lengkap customer', complete: !!customerSummary.value?.tab_completeness?.data_customer },
  { label: 'Sales Review', description: 'Review KYC dari Marketing', complete: !!customerSummary.value?.tab_completeness?.review },
  { label: 'Credit Application / TOP', description: 'Pengajuan limit kredit & TOP', complete: !!customerSummary.value?.tab_completeness?.credit },
  { label: 'LCR', description: 'Hasil survei customer site', complete: !!customerSummary.value?.tab_completeness?.lcr },
])

const hasParentCompany = computed(() => !!customerSummary.value?.parent_company)
const isUnderReview = computed<boolean>(() => customerSummary.value?.latest_verification?.status === 'in_review')

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
    if (customerSummary.value) customerSummary.value.tab_completeness = data
  } catch {}
}

async function submitVerification(): Promise<void> {
  submitting.value = true
  try {
    await axios.post(`/api/customers/${idCustomer}/verification`, {})
    success('Berhasil', 'Verifikasi customer berhasil diajukan.')
    await fetchCustomer()
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Verifikasi customer ini sedang dalam review.')
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
              <Button v-if="!isUnderReview" variant="primary" :disabled="submitting" @click="submitVerification">
                <Lucide v-if="submitting" icon="Loader2" class="mr-2 w-4 h-4 animate-spin" />
                <Lucide v-else icon="ShieldCheck" class="mr-2 w-4 h-4" />
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
            <SalesReviewDataTab :id-customer="idCustomer" :is-under-review="isUnderReview" @saved="refreshCompleteness" />
          </Tab.Panel>
          <Tab.Panel>
            <CreditDataTab :id-customer="idCustomer" :is-under-review="isUnderReview"
              :latest-approved-verification="customerSummary?.latest_approved_verification ?? null"
              @saved="refreshCompleteness" />
          </Tab.Panel>
          <Tab.Panel>
            <LcrDataTab :id-customer="idCustomer" :customer-logistik="customerSummary?.logistik"
              @saved="refreshCompleteness" />
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>
  </div>
</template>
