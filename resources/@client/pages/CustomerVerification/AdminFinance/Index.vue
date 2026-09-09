<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'
import axios from 'axios'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import { createResourceApi } from '@/utils/resourceApi.js'
import { formatDateTime } from '@/utils/format'

const { error } = useNotification()
const api = createResourceApi('/review/customer-verifications')

type VerificationStatus = 'in_review' | 'approved' | 'rejected'

const STATUS_TABS: { value: VerificationStatus; label: string }[] = [
  { value: 'in_review', label: 'Menunggu Review' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'rejected', label: 'Ditolak' },
]

const EMPTY_DESCRIPTIONS: Record<VerificationStatus, string> = {
  in_review: 'Tidak ada verifikasi menunggu keputusan.',
  approved: 'Belum ada verifikasi yang disetujui.',
  rejected: 'Belum ada verifikasi yang ditolak.',
}

const activeStatusTab = ref<VerificationStatus>('in_review')
const stats = ref<Record<VerificationStatus, number>>({ in_review: 0, approved: 0, rejected: 0 })
const rows = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const emptyDescription = computed(() => EMPTY_DESCRIPTIONS[activeStatusTab.value])

watch(activeStatusTab, () => fetchData(1))
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(async () => {
  await Promise.all([fetchStats(), fetchData(1)])
})

async function fetchStats() {
  try {
    const { data } = await axios.get('/api/review/customer-verifications/stats')
    stats.value = data
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
      search: searchQuery.value || undefined,
      status: activeStatusTab.value,
    })

    rows.value = data.data ?? []
    currentPage.value = data.meta?.current_page ?? 1
    totalPages.value = data.meta?.last_page ?? 1
    totalRecords.value = data.meta?.total ?? rows.value.length
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

function statusBadgeClass(status?: string) {
  if (status === 'in_review') return 'bg-amber-100 text-amber-700'
  if (status === 'approved') return 'bg-emerald-100 text-emerald-700'
  if (status === 'rejected') return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-700'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Review Data Customer — Admin Finance"
        description="Evaluasi kelayakan customer dan putuskan approve atau reject.">
        <template #body>
          <div class="inline-flex w-fit gap-1 rounded-lg border border-white/20 bg-white/10 p-1 backdrop-blur-sm">
            <button v-for="tab in STATUS_TABS" :key="tab.value" type="button"
              class="rounded-md px-3 py-1.5 text-sm font-medium transition"
              :class="activeStatusTab === tab.value ? 'bg-white text-theme-1 shadow-sm' : 'text-white/80 hover:bg-white/10'"
              @click="activeStatusTab = tab.value">
              {{ tab.label }} ({{ stats[tab.value] }})
            </button>
          </div>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading" :empty="rows.length === 0"
        :colspan="6" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
        :total-pages="totalPages" search-placeholder="Cari nama atau kode customer..."
        loading-text="Memuat antrean Admin Finance..." :empty-description="emptyDescription"
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Status</Table.Th>
          <Table.Th>Diajukan</Table.Th>
          <Table.Th>Direview</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in rows" :key="row.id_verification" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="font-caption text-slate-500">{{ row.customer?.customer_code || '-' }}</div>
              <div class="font-strong">{{ row.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                :class="statusBadgeClass(row.status)">
                {{ row.status_label }}
              </span>
            </Table.Td>

            <Table.Td>
              <div class="font-body">{{ formatDateTime(row.submitted_at) ?? '-' }}</div>
              <div class="font-caption text-slate-500">{{ row.submitted_by?.name ?? '-' }}</div>
            </Table.Td>

            <Table.Td>
              <template v-if="row.reviewed_at">
                <div class="font-body">{{ formatDateTime(row.reviewed_at) }}</div>
                <div class="font-caption text-slate-500">{{ row.reviewed_by?.name ?? '-' }}</div>
              </template>
              <template v-else>-</template>
            </Table.Td>

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
