<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormSelect } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import BarChart from '@/components/SystemDesign/Data/BarChart.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue'
// import StockChart from '@/components/StockChart.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface StockRow {
  id: number
  po_produk_id?: number
  receive_item_id?: number
  produk_id: number
  produk_label: string
  nomor_po: string
  volume: number
  harga_tebus: number
  created_at: string
}

const { error } = useNotification()
const stockApi = createResourceApi('/stocks')

const stocks = ref<StockRow[]>([])
const selectedProductId = ref(0)
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const loading = ref(false)

onMounted(() => {
  fetchStocks()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch([selectedProductId, perPage], resetToFirstPage)

const productList = computed(() => {
  const products = new Map<number, string>()

  for (const row of stocks.value) {
    if (!products.has(row.produk_id)) {
      products.set(row.produk_id, row.produk_label)
    }
  }

  return Array.from(products.entries()).map(([id, name]) => ({
    produk_id: id,
    nama_produk: name,
  }))
})

const filteredStocks = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return stocks.value.filter(row => {
    const matchesProduct =
      selectedProductId.value === 0 ||
      row.produk_id === selectedProductId.value

    const matchesSearch =
      !query ||
      row.produk_label.toLowerCase().includes(query) ||
      row.nomor_po.toLowerCase().includes(query)

    return matchesProduct && matchesSearch
  })
})

const totalRecords = computed(() => filteredStocks.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const paginatedStocks = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredStocks.value.slice(start, start + perPage.value)
})

const activeFilterCount = computed(() => {
  return selectedProductId.value === 0 ? 0 : 1
})

const selectedProductLabel = computed(() => {
  return productList.value.find(
    item => item.produk_id === selectedProductId.value,
  )?.nama_produk
})

const totalVolume = computed(() => {
  return filteredStocks.value.reduce(
    (sum, row) => sum + Number(row.volume || 0),
    0,
  )
})

const totalValue = computed(() => {
  return filteredStocks.value.reduce((sum, row) => {
    return sum + Number(row.volume || 0) * Number(row.harga_tebus || 0)
  }, 0)
})

const latestStockDate = computed(() => {
  return filteredStocks.value[0]?.created_at || ''
})

const chartData = computed(() => {
  if (selectedProductId.value !== 0) {
    return filteredStocks.value.map(row => ({
      nama_produk: row.nomor_po,
      volume: row.volume,
    }))
  }

  const summaries = new Map<string, number>()

  for (const row of filteredStocks.value) {
    summaries.set(
      row.produk_label,
      (summaries.get(row.produk_label) || 0) + row.volume,
    )
  }

  return Array.from(summaries.entries()).map(([name, volume]) => ({
    nama_produk: name,
    volume,
  }))
})

const systemChartData = computed(() => {
  return chartData.value.map(row => ({
    label: row.nama_produk,
    value: row.volume,
  }))
})

async function fetchStocks() {
  loading.value = true

  try {
    const { data } = await stockApi.getAll()
    stocks.value = Array.isArray(data) ? data : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data stok')
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

function resetFilter() {
  selectedProductId.value = 0
}

function formatNumber(value: number | string = 0) {
  const numberValue =
    typeof value === 'string' ? Number.parseFloat(value) : value

  return Number.isNaN(numberValue)
    ? '-'
    : numberValue.toLocaleString('id-ID')
}

function formatCurrency(value: number | string = 0) {
  const numberValue =
    typeof value === 'string' ? Number.parseFloat(value) : value

  return Number.isNaN(numberValue)
    ? '-'
    : `Rp ${numberValue.toLocaleString('id-ID')}`
}

function formatDateTime(value: string) {
  if (!value) return '-'

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return '-'

  return `${date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })} ${date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })}`
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <div class="col-span-12 intro-y">
      <PageHeader title="Stock Inventory" description="Pantau stok masuk berdasarkan PO dan produk">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="fetchStocks">
            <Lucide icon="RefreshCw" class="h-4 w-4" />
            Refresh
          </Button>
        </template>
      </PageHeader>

      <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="box rounded-lg border p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Total Volume</p>
              <p class="mt-2 text-2xl font-semibold text-slate-800">
                {{ formatNumber(totalVolume) }}
              </p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <Lucide icon="Package" class="h-5 w-5" />
            </div>
          </div>
        </div>

        <div class="box rounded-lg border p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Nilai Stok</p>
              <p class="mt-2 text-2xl font-semibold text-slate-800">
                {{ formatCurrency(totalValue) }}
              </p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
              <Lucide icon="Wallet" class="h-5 w-5" />
            </div>
          </div>
        </div>

        <div class="box rounded-lg border p-5">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Stok Terbaru</p>
              <p class="mt-2 text-base font-semibold text-slate-800">
                {{ formatDateTime(latestStockDate) }}
              </p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
              <Lucide icon="Clock" class="h-5 w-5" />
            </div>
          </div>
        </div>
      </div>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="activeFilterCount" :total-pages="totalPages"
        search-placeholder="Cari nomor PO atau produk..." @page-change="goToPage">
        <template #filters>
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
              <FormLabel for="filter-produk" class="mb-1 text-sm font-medium text-slate-600">
                Produk
              </FormLabel>
              <FormSelect id="filter-produk" :model-value="String(selectedProductId)" class="w-full !box"
                @update:model-value="selectedProductId = Number($event)">
                <option value="0">Semua Produk</option>
                <option v-for="product in productList" :key="product.produk_id" :value="String(product.produk_id)">
                  {{ product.nama_produk }}
                </option>
              </FormSelect>
              <p v-if="selectedProductLabel" class="mt-2 text-xs text-slate-500">
                {{ selectedProductLabel }}
              </p>
            </div>

            <Button variant="outline-secondary" class="inline-flex items-center justify-center gap-2"
              :disabled="selectedProductId === 0" @click="resetFilter">
              <Lucide icon="RotateCcw" class="h-4 w-4" />
              Reset Filter
            </Button>
          </div>
        </template>
      </PageToolbar>

      <div v-if="chartData.length > 0" class="box mb-6 rounded-lg border p-5">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-slate-800">
              Grafik Volume Stok
            </h3>
            <p class="text-sm text-slate-500">
              {{ selectedProductId === 0 ? 'Ringkasan per produk' : 'Distribusi per PO' }}
            </p>
          </div>
        </div>

        <!-- <StockChart :data="chartData" /> -->
        <BarChart :data="systemChartData" dataset-label="Volume Stok" value-suffix=" L" :height="320"
          :y-axis-label-count="7" />
      </div>

      <DataList :loading="loading" :empty="filteredStocks.length === 0" :colspan="6" :show-footer="true"
        :total="totalRecords" :current-page="currentPage" :per-page="perPage" loading-text="Memuat data stok..."
        empty-description="Belum ada data stok untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Waktu Masuk</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Produk</Table.Th>
          <Table.Th class="text-right">Volume</Table.Th>
          <Table.Th class="text-right">Harga Tebus</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in paginatedStocks" :key="row.id" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="whitespace-nowrap text-slate-600">
              {{ formatDateTime(row.created_at) }}
            </Table.Td>
            <Table.Td>
              <span class="font-medium text-slate-800">
                {{ row.nomor_po || '-' }}
              </span>
            </Table.Td>
            <Table.Td>
              {{ row.produk_label || '-' }}
            </Table.Td>
            <Table.Td class="text-right font-medium text-slate-700">
              {{ formatNumber(row.volume) }}
            </Table.Td>
            <Table.Td class="text-right text-slate-700">
              {{ formatCurrency(row.harga_tebus) }}
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
