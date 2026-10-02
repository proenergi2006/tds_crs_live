<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'
import axios from 'axios'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import Badge from '@/components/SystemDesign/Data/Badge.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import { formatDate } from '@/utils/format'

const { error: notifyError } = useNotification()

type LcrStatusTab = 'pending' | 'disetujui' | 'ditolak' | 'all'

const STATUS_TABS: { value: LcrStatusTab; label: string }[] = [
  { value: 'pending', label: 'Menunggu' },
  { value: 'disetujui', label: 'Disetujui' },
  { value: 'ditolak', label: 'Ditolak' },
  { value: 'all', label: 'Semua' },
]

const activeStatusTab = ref<LcrStatusTab>('pending')
const stats = ref<{ pending: number; disetujui: number; ditolak: number }>({ pending: 0, disetujui: 0, ditolak: 0 })
const rows = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

watch(activeStatusTab, () => fetchData(1))
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(async () => {
  await Promise.all([fetchStats(), fetchData(1)])
})

async function fetchStats(): Promise<void> {
  try {
    const { data } = await axios.get('/api/review/lcr-sites/stats')
    stats.value = data
  } catch { }
}

async function fetchData(page = 1): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get('/api/review/lcr-sites', {
      params: {
        page,
        per_page: perPage.value,
        q: searchQuery.value || undefined,
        status: activeStatusTab.value,
      },
    })

    rows.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? rows.value.length
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat antrean review LCR.')
    rows.value = []
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function tabCount(tab: LcrStatusTab): number {
  return tab === 'all' ? 0 : stats.value[tab]
}

function approvalBadgeVariant(status?: string | null): 'soft-pending' | 'soft-success' | 'soft-danger' | 'soft-dark' {
  if (status === 'in_progress') return 'soft-pending'
  if (status === 'approved') return 'soft-success'
  if (status === 'rejected') return 'soft-danger'
  return 'soft-dark'
}

function approvalBadgeLabel(row: any): string {
  return row.approval?.status_label ?? 'Belum ada approval'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Review LCR — Logistik"
        description="Verifikasi hasil survei site customer dan putuskan approve atau reject.">
        <template #body>
          <div class="inline-flex gap-1 bg-white/10 backdrop-blur-sm p-1 border border-white/20 rounded-lg w-fit">
            <button v-for="tab in STATUS_TABS" :key="tab.value" type="button"
              class="px-3 py-1.5 rounded-md font-medium text-sm transition"
              :class="activeStatusTab === tab.value ? 'bg-white text-theme-1 shadow-sm' : 'text-white/80 hover:bg-white/10'"
              @click="activeStatusTab = tab.value">
              {{ tab.label }}<template v-if="tab.value !== 'all'"> ({{ tabCount(tab.value) }})</template>
            </button>
          </div>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading" :empty="rows.length === 0"
        :colspan="6" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
        :total-pages="totalPages" search-placeholder="Cari nama site, alamat, atau customer..."
        loading-text="Memuat antrean review LCR..." empty-description="Tidak ada data LCR untuk filter ini."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Nama Site</Table.Th>
          <Table.Th>Tgl Survey</Table.Th>
          <Table.Th>Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in rows" :key="row.id_lcr" class="hover:bg-slate-50 transition">
            <Table.Td class="num-sm text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="text-body-strong">{{ row.customer?.nama_perusahaan || ('#' + row.id_customer) }}</div>
            </Table.Td>

            <Table.Td>{{ row.site_name || '-' }}</Table.Td>

            <Table.Td>{{ formatDate(row.survey_date) }}</Table.Td>

            <Table.Td>
              <Badge :variant="approvalBadgeVariant(row.approval?.status)">
                {{ approvalBadgeLabel(row) }}
              </Badge>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex justify-center items-center gap-1">
                <RouterLink :to="{ name: 'logistik-lcr-detail', params: { id: row.id_lcr } }"
                  class="inline-flex justify-center items-center bg-white hover:bg-slate-100 border border-slate-200 rounded-full w-8 h-8 text-slate-500 hover:text-slate-700 transition"
                  title="Detail">
                  <Lucide icon="Eye" class="w-4 h-4" />
                </RouterLink>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
