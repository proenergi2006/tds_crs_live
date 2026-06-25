<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'
import { formatDate } from '@/utils/format'

const router = useRouter()
const { error } = useNotification()
const poVerificationApi = createResourceApi('/po-verification')

const vendorPos = ref<any[]>([])
const vendors = ref<any[]>([])
const terminals = ref<any[]>([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

const searchQuery = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterTerminal = ref('')
const filterVendor = ref('')
const perPage = ref(10)

const activeFilterCount = computed(() =>
  [
    filterDateFrom.value,
    filterDateTo.value,
    filterTerminal.value,
    filterVendor.value,
  ].filter(Boolean).length,
)

onMounted(async () => {
  await Promise.all([fetchVendors(), fetchTerminals()])
  fetchData(1)
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true
  try {
    const { data } = await poVerificationApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      tanggal_dari: filterDateFrom.value || undefined,
      tanggal_sampai: filterDateTo.value || undefined,
      id_terminal: filterTerminal.value || undefined,
      id_vendor: filterVendor.value || undefined,
    })
    vendorPos.value = data.data || []
    meta.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || 0,
    }
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

async function fetchVendors() {
  try {
    const { data } = await axios.get('/api/vendors', { params: { per_page: 200 } })
    vendors.value = data.data || data || []
  } catch {
    vendors.value = []
  }
}

async function fetchTerminals() {
  try {
    const { data } = await axios.get('/api/terminals', { params: { per_page: 200 } })
    terminals.value = data.data || data || []
  } catch {
    terminals.value = []
  }
}

function goToPage(page: number) {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

function resetFilter() {
  filterDateFrom.value = ''
  filterDateTo.value = ''
  filterTerminal.value = ''
  filterVendor.value = ''
  fetchData(1)
}

function goDetail(id: number) {
  router.push({ name: 'po-verification-detail', params: { id } })
}

function statusLabel(statusPo?: { key: string; label: string }) {
  return statusPo?.label ?? '-'
}

function statusBadgeClass(statusPo?: { key: string; label: string }) {
  const map: Record<string, string> = {
    Draft: 'bg-slate-100 text-slate-600',
    WaitingCeo: 'bg-blue-100 text-blue-700',
    Approved: 'bg-emerald-100 text-emerald-700',
    DitolakCfo: 'bg-red-100 text-red-700',
    DitolakCeo: 'bg-red-100 text-red-700',
  }
  return map[statusPo?.key ?? ''] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">

      <PageHeader title="Verifikasi PO Supplier" description="Daftar purchase order yang menunggu persetujuan." />

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="vendorPos.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="meta.total"
        :current-page="meta.current_page" :total-pages="meta.last_page" :active-filter-count="activeFilterCount"
        search-placeholder="Cari Nomor PO..." loading-text="Memuat data PO..."
        empty-description="Belum ada data PO untuk ditampilkan." @page-change="goToPage">
        <template #filters="{ close }">
          <div class="space-y-4 p-1">
            <div>
              <div class="font-section px-3 pb-2 pt-1">Tanggal Dari</div>
              <FormInput v-model="filterDateFrom" type="date" class="!box" />
            </div>
            <div>
              <div class="font-section px-3 pb-2">Tanggal Sampai</div>
              <FormInput v-model="filterDateTo" type="date" class="!box" />
            </div>
            <div>
              <div class="font-section px-3 pb-2">Terminal</div>
              <FormSelect v-model="filterTerminal" class="!box">
                <option value="">— Semua Terminal —</option>
                <option v-for="t in terminals" :key="t.id_terminal" :value="t.id_terminal">
                  {{ t.nama_terminal }}
                </option>
              </FormSelect>
            </div>
            <div>
              <div class="font-section px-3 pb-2">Vendor</div>
              <FormSelect v-model="filterVendor" class="!box">
                <option value="">— Semua Vendor —</option>
                <option v-for="v in vendors" :key="v.id_vendor" :value="v.id_vendor">
                  {{ v.nama_vendor }}
                </option>
              </FormSelect>
            </div>
            <div class="flex gap-2 border-t border-slate-100 pt-3">
              <Button type="button" variant="primary" class="inline-flex flex-1 items-center justify-center gap-2"
                :disabled="loading" @click="() => { fetchData(1); close() }">
                <Lucide icon="Search" class="h-4 w-4" />
                Cari
              </Button>
              <Button type="button" variant="outline-secondary" class="flex-1" :disabled="activeFilterCount === 0"
                @click="() => { resetFilter(); close() }">
                Reset
              </Button>
            </div>
          </div>
        </template>

        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Tanggal PO</Table.Th>
          <Table.Th>Vendor</Table.Th>
          <Table.Th>Terminal</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(po, idx) in vendorPos" :key="po.id_po" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (meta.current_page - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <div class="font-strong">{{ po.nomor_po }}</div>
            </Table.Td>
            <Table.Td class="whitespace-nowrap text-slate-700">{{ formatDate(po.tanggal_inven) }}</Table.Td>
            <Table.Td class="text-slate-700">{{ po.vendor?.nama_vendor || '-' }}</Table.Td>
            <Table.Td class="text-slate-700">{{ po.terminal?.nama_terminal || '-' }}</Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1" :class="statusBadgeClass(po.status_po)">
                {{ statusLabel(po.status_po) }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-1">
                <Button variant="soft-dark" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Detail"
                  @click="goDetail(po.id_po)">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

    </div>
  </div>
</template>
