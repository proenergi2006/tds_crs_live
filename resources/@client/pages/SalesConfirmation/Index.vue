<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'
import axios from 'axios'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { salesConfirmationBadgeClass } from './status'

const router = useRouter()
const auth = useAuthStore()
const { error: notifyError } = useNotification()

const ROLE_BM = 8

const disposisiFilter = ref<number | null>(auth.hasRole(ROLE_BM) ? 2 : null)
const salesConfirmations = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const isBranchManager = computed(() => auth.hasRole(ROLE_BM))

watch(disposisiFilter, () => fetchData(1))
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get('/api/sales-confirmations', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        disposisi: disposisiFilter.value ?? undefined,
      },
    })
    salesConfirmations.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? salesConfirmations.value.length
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data Sales Confirmation.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function openDetail(id: number): void {
  router.push({ name: 'sales-confirmations-detail', params: { id } })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Sales Confirmation" description="Daftar Sales Confirmation dan status verifikasinya." />

      <div v-if="isBranchManager" class="inline-flex w-fit gap-1 rounded-lg border border-slate-200 bg-white p-1">
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium transition"
          :class="disposisiFilter === 2 ? 'bg-theme-1 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="disposisiFilter = 2">
          Antrean Saya
        </button>
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium transition"
          :class="disposisiFilter === null ? 'bg-theme-1 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="disposisiFilter = null">
          Semua
        </button>
      </div>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="salesConfirmations.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true"
        :total="totalRecords" :current-page="currentPage" :total-pages="totalPages"
        search-placeholder="Cari nomor PO atau customer..." loading-text="Memuat data Sales Confirmation..."
        empty-description="Belum ada Sales Confirmation." @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Marketing</Table.Th>
          <Table.Th>Nomor / Tanggal PO</Table.Th>
          <Table.Th class="text-center">Volume / Harga</Table.Th>
          <Table.Th class="text-center">Disposisi</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in salesConfirmations" :key="row.id_poc" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ row.customer?.customer_code || '-' }}</div>
              <div class="text-slate-500">{{ row.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              {{ row.marketing_name || '-' }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              <div class="font-strong">{{ row.nomor_poc || '-' }}</div>
              <div class="text-slate-500">{{ formatDate(row.tanggal_poc) }}</div>
            </Table.Td>

            <Table.Td class="text-center whitespace-nowrap">
              <div>{{ formatNumber(row.volume_poc) }} m³</div>
              <div class="text-slate-500">{{ formatCurrency(row.harga_poc) }} /m³</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-3 py-1"
                :class="salesConfirmationBadgeClass(row.disposisi)">
                {{ row.disposisi_text }}
              </span>
              <div v-if="row.disposisi_time" class="text-slate-500">{{ formatDateTime(row.disposisi_time) }} WIB</div>
            </Table.Td>

            <Table.Td class="text-center">
              <ExtendableButton variant="soft-dark" rounded label="Detail" @click="openDetail(row.id_poc)">
                <Lucide icon="Eye" class="w-4 h-4" />
              </ExtendableButton>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
