<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'
import axios from 'axios'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const { error } = useNotification()
const api = createResourceApi('/review/customer-verifications')

const rows = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const queueCount = ref(0)
const loading = ref(false)

async function fetchStats() {
  try {
    const { data } = await axios.get('/api/review/customer-verifications/stats')
    queueCount.value = data.forwarded ?? 0
  } catch {
    // stats gagal dimuat tidak menghalangi list utama
  }
}

async function fetchData(page = 1) {
  loading.value = true
  try {
    const { data } = await api.getAll({
      page,
      per_page: perPage.value,
      q: searchQuery.value || undefined,
      tab: 'forwarded',
    })

    rows.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? rows.value.length
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat antrean Admin Finance.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

onMounted(async () => {
  await Promise.all([fetchStats(), fetchData(1)])
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Review Data Customer — Admin Finance"
        :description="`Evaluasi kelayakan customer sebelum diteruskan ke BM. Antrean saat ini: ${queueCount}.`" />

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading" :empty="rows.length === 0"
        :colspan="6" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
        :total-pages="totalPages" search-placeholder="Cari nama, kode, atau alamat customer..."
        loading-text="Memuat antrean Admin Finance..." empty-description="Tidak ada verifikasi yang menunggu keputusan Admin Finance."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Kode Link</Table.Th>
          <Table.Th>Kode Customer</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Alamat</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in rows" :key="row.id_verification" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <RouterLink v-if="row.token_verification" :to="{ name: 'customer-onboarding', params: { token: row.token_verification } }"
                target="_blank" class="font-strong !text-primary underline">
                LC{{ row.id_verification }}
              </RouterLink>
              <span v-else class="font-strong">LC{{ row.id_verification }}</span>
            </Table.Td>

            <Table.Td class="text-slate-700">{{ row.customer?.customer_code || '-' }}</Table.Td>

            <Table.Td>
              <div class="font-strong">{{ row.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td class="text-slate-600">{{ row.customer?.company_address || '-' }}</Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-1">
                <RouterLink :to="{ name: 'review-data-customer-admin-detail', params: { id: row.id_verification } }"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                  title="Verifikasi">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </RouterLink>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
