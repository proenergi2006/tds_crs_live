<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface LogEntry {
  id: string
  timestamp: string | null
  environment: string | null
  level: string | null
  message: string
  trace: string
  raw: string
}

const { error } = useNotification()

/* State: data & pagination */
const allLogs = ref<LogEntry[]>([])
const searchQuery = ref('')
const filterLevel = ref('')
const perPage = ref(25)
const currentPage = ref(1)
const loading = ref(false)

/* State: expanded trace rows */
const expandedIds = ref(new Set<string>())

const levelOptions = computed(() => {
  const seen = new Set<string>()
  allLogs.value.forEach(log => { if (log.level) seen.add(log.level) })
  return Array.from(seen).sort()
})

const activeFilterCount = computed(() => filterLevel.value ? 1 : 0)

const filteredLogs = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return allLogs.value.filter(log => {
    const matchesLevel = !filterLevel.value || log.level === filterLevel.value
    const matchesSearch =
      !query ||
      log.message.toLowerCase().includes(query) ||
      log.raw.toLowerCase().includes(query)
    return matchesLevel && matchesSearch
  })
})

const totalRecords = computed(() => filteredLogs.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / perPage.value)))

const paginatedLogs = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredLogs.value.slice(start, start + perPage.value)
})

onMounted(fetchData)
watch([searchQuery, filterLevel], debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

async function fetchData() {
  loading.value = true
  expandedIds.value = new Set()

  try {
    const { data } = await axios.get('/api/logs', { params: { limit: 200 } })
    allLogs.value = Array.isArray(data.data) ? [...data.data].reverse() : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat log aplikasi')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

function toggleTrace(id: string) {
  const next = new Set(expandedIds.value)
  next.has(id) ? next.delete(id) : next.add(id)
  expandedIds.value = next
}

function levelClass(level: string | null): string {
  switch (level) {
    case 'EMERGENCY':
    case 'ALERT':
    case 'CRITICAL':
    case 'ERROR':
      return 'bg-red-100 text-red-700'
    case 'WARNING':
      return 'bg-amber-100 text-amber-700'
    case 'NOTICE':
    case 'INFO':
      return 'bg-blue-100 text-blue-700'
    case 'DEBUG':
      return 'bg-slate-100 text-slate-500'
    default:
      return 'bg-slate-100 text-slate-500'
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">

      <PageHeader title="Application Logs" description="Log terbaru dari aplikasi">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" :disabled="loading" @click="fetchData">
            <Lucide icon="RefreshCw" class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
            Refresh
          </Button>
        </template>
      </PageHeader>

      <div>
        <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
          :empty="paginatedLogs.length === 0" :colspan="5" :show-footer="true" :show-toolbar="true"
          :total="totalRecords" :current-page="currentPage" :total-pages="totalPages"
          :active-filter-count="activeFilterCount" search-placeholder="Cari pesan atau keyword..."
          loading-text="Memuat log aplikasi..." empty-description="Tidak ada log yang sesuai dengan filter."
          @page-change="goToPage">
          <template #filters="{ close }">
            <div>
              <div class="font-section px-3 pb-2 pt-1">Level</div>
              <div class="space-y-1">
                <button type="button"
                  class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left font-body transition"
                  :class="filterLevel === '' ? 'bg-primary/10 font-semibold text-primary' : 'text-slate-600 hover:bg-slate-50'"
                  @click="filterLevel = ''; close()">
                  Semua Level
                  <Lucide v-if="filterLevel === ''" icon="Check" class="h-4 w-4" />
                </button>
                <button v-for="lvl in levelOptions" :key="lvl" type="button"
                  class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left font-body transition"
                  :class="filterLevel === lvl ? 'bg-primary/10 font-semibold text-primary' : 'text-slate-600 hover:bg-slate-50'"
                  @click="filterLevel = lvl; close()">
                  {{ lvl }}
                  <Lucide v-if="filterLevel === lvl" icon="Check" class="h-4 w-4" />
                </button>
              </div>
            </div>
          </template>

          <template #head>
            <Table.Th class="w-14 text-center">No</Table.Th>
            <Table.Th class="w-44">Waktu</Table.Th>
            <Table.Th class="w-28">Level</Table.Th>
            <Table.Th class="w-24">Env</Table.Th>
            <Table.Th>Pesan</Table.Th>
          </template>

          <template #body>
            <template v-for="(log, idx) in paginatedLogs" :key="log.id">

              <Table.Tr class="transition hover:bg-slate-50" :class="log.trace ? 'cursor-pointer' : ''"
                @click="log.trace ? toggleTrace(log.id) : undefined">
                <Table.Td class="font-num text-center text-slate-400">
                  {{ (currentPage - 1) * perPage + idx + 1 }}
                </Table.Td>
                <Table.Td class="font-caption whitespace-nowrap text-slate-500">
                  {{ log.timestamp ?? '-' }}
                </Table.Td>
                <Table.Td>
                  <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide"
                    :class="levelClass(log.level)">
                    {{ log.level ?? '-' }}
                  </span>
                </Table.Td>
                <Table.Td class="font-caption text-slate-400">
                  {{ log.environment ?? '-' }}
                </Table.Td>
                <Table.Td>
                  <div class="flex items-start justify-between gap-2">
                    <span class="font-body break-words">{{ log.message || '-' }}</span>
                    <Lucide v-if="log.trace" :icon="expandedIds.has(log.id) ? 'ChevronUp' : 'ChevronDown'"
                      class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                  </div>
                </Table.Td>
              </Table.Tr>

              <Table.Tr v-if="log.trace && expandedIds.has(log.id)" :key="`${log.id}-trace`">
                <Table.Td :colspan="5" class="bg-slate-50 p-0">
                  <pre
                    class="overflow-x-auto whitespace-pre-wrap break-all px-4 py-3 font-caption text-xs leading-relaxed text-slate-600">{{ log.trace }}</pre>
                </Table.Td>
              </Table.Tr>

            </template>
          </template>
        </DataList>
      </div>

    </div>
  </div>
</template>
