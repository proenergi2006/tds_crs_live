<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatDate } from '@/utils/format'

const { success, error } = useNotification()

const list = ref<any[]>([])
const meta = ref<any>({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const from = ref('')
const to = ref('')

const showAlloc = ref(false)
const currentPr = ref<any>(null)
const currentIt = ref<any>(null)
const stocks = ref<any[]>([])
const stockFilter = ref<any>({ min: 0, q: '' })
const saving = ref(false)
const allocError = ref('')

const searchQuery = ref('')
const perPage = ref(25)
const activeFilterCount = computed(() => [from.value, to.value].filter(Boolean).length)

watch(perPage, debounce(() => fetchList(1), 300))

const totalAlloc = computed(() =>
  stocks.value.reduce((amount, stock) => amount + Number(stock._qty || 0), 0),
)

fetchList(1)

async function fetchList(page = 1) {
  loading.value = true

  try {
    const params: any = { page, per_page: perPage.value }

    if (from.value) params.from = from.value
    if (to.value) params.to = to.value

    const { data } = await axios.get('/api/procurement/delivery-requests', { params })

    list.value = (data.data || []).map((row: any) => ({
      ...row,
      _open: false,
      items: [],
    }))
    meta.value = data.meta || meta.value
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal memuat delivery request')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > meta.value.last_page) return
  fetchList(page)
}

function resetFilter() {
  from.value = ''
  to.value = ''
  fetchList(1)
}

function formatDateTime(value?: string) {
  return value
    ? new Date(String(value)).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '-'
}

function formatNumber(value: any) {
  const numberValue = Number(value || 0)
  return Number.isNaN(numberValue) ? '0' : numberValue.toLocaleString('id-ID')
}

function disposisiClass(code: number) {
  switch (Number(code)) {
    case 1:
      return 'bg-emerald-100 text-emerald-700 border border-emerald-200'
    case 2:
      return 'bg-indigo-100 text-indigo-700 border border-indigo-200'
    default:
      return 'bg-amber-100 text-amber-700 border border-amber-200'
  }
}

async function toggle(pr: any) {
  pr._open = !pr._open

  if (!pr._open || pr.items?.length) return

  try {
    const { data } = await axios.get(`/api/procurement/delivery-requests/${pr.id_pr}`)

    pr.items = (data.items || []).map((item: any) => ({
      ...item,
      allocated: Number(item.allocated || 0),
      remain: Math.max(0, Number(item.volume || 0) - Number(item.allocated || 0)),
    }))
  } catch (e: any) {
    pr._open = false
    error('Gagal', e?.response?.data?.message || 'Gagal memuat detail delivery request')
  }
}

function openAllocate(pr: any, item: any) {
  currentPr.value = pr
  currentIt.value = item
  stocks.value = []
  stockFilter.value = { min: item.remain, q: '' }
  allocError.value = ''
  showAlloc.value = true
  loadStocks()
}

function closeAlloc() {
  if (saving.value) return
  showAlloc.value = false
}

async function loadStocks() {
  if (!currentIt.value) return

  const params: any = {
    produk_id:
      currentIt.value.produk_id
      || currentIt.value.id_produk
      || currentIt.value.produk_id_fk,
    per_page: 50,
  }

  if (stockFilter.value.min) params.min = stockFilter.value.min
  if (stockFilter.value.q) params.q = stockFilter.value.q

  try {
    const { data } = await axios.get('/api/procurement/stocks', { params })
    const rows = Array.isArray(data) ? data : (data.data || [])
    stocks.value = rows.map((stock: any) => ({ ...stock, _qty: 0 }))
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal memuat stok')
  }
}

async function saveAllocation() {
  allocError.value = ''

  const remain = Number(currentIt.value?.remain || 0)
  const total = Number(totalAlloc.value || 0)

  if (total <= 0) {
    allocError.value = 'Isi qty alokasi terlebih dahulu'
    return
  }

  if (total - remain > 1e-6) {
    allocError.value = `Jumlah alokasi melebihi sisa (${formatNumber(remain)})`
    return
  }

  const allocations = stocks.value
    .filter(stock => Number(stock._qty || 0) > 0)
    .map(stock => ({ stock_id: stock.id, qty: Number(stock._qty) }))

  if (!allocations.length) {
    allocError.value = 'Tidak ada baris alokasi'
    return
  }

  saving.value = true

  try {
    await axios.post('/api/procurement/delivery-requests/allocate', {
      items: [
        {
          id_prd: currentIt.value.id_prd,
          produk_id:
            currentIt.value.produk_id
            || currentIt.value.id_produk
            || currentIt.value.produk_id_fk,
          allocations,
        },
      ],
    })

    const { data } = await axios.get(
      `/api/procurement/delivery-requests/${currentPr.value.id_pr}`,
    )

    currentPr.value.items = (data.items || []).map((item: any) => ({
      ...item,
      allocated: Number(item.allocated || 0),
      remain: Math.max(0, Number(item.volume || 0) - Number(item.allocated || 0)),
    }))
    currentPr.value.total_sisa = currentPr.value.items.reduce(
      (sum: number, item: any) => sum + (item.remain || 0),
      0,
    )

    showAlloc.value = false
    success('Berhasil', 'Alokasi tersimpan')
  } catch (e: any) {
    error('Gagal', e?.response?.data?.message || 'Gagal menyimpan alokasi')
  } finally {
    saving.value = false
  }
}

function exportCsv() {
  const header = [
    'Tanggal',
    'Kode DR',
    'Customer',
    'Nomor PO',
    'Disposisi',
    'Total Volume',
    'Total Sisa',
  ]
  const body = list.value.map((row: any) => [
    formatDate(row.tanggal_pr),
    row.nomor_pr,
    (row.customers || '').replace(/\n/g, '; '),
    (row.nomor_pos || '').replace(/\n/g, '; '),
    row.disposisi_label,
    row.total_volume,
    row.total_sisa,
  ])
  const csv = [header, ...body]
    .map(row =>
      row.map(value => `"${String(value ?? '').replaceAll('"', '""')}"`).join(','),
    )
    .join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = url
  anchor.download = `delivery-requests-${new Date().toISOString().slice(0, 10)}.csv`
  anchor.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Delivery Request"
        description="Daftar delivery request procurement untuk persiapan alokasi stok dan pemenuhan permintaan.">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" :disabled="loading" @click="exportCsv">
            <Lucide icon="Download" class="h-4 w-4" />
            Export
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="list.length === 0" :colspan="9" :show-footer="true" :show-toolbar="true" :total="meta.total"
        :current-page="meta.current_page" :total-pages="meta.last_page" :active-filter-count="activeFilterCount"
        search-placeholder="Cari delivery request..." loading-text="Memuat delivery request..."
        empty-description="Belum ada delivery request untuk ditampilkan." @page-change="goToPage">
        <template #filters="{ close }">
          <div class="space-y-4 p-1">
            <div>
              <div class="px-3 pb-2 pt-1 font-section">Tanggal DR Dari</div>
              <FormInput v-model="from" type="date" class="!box" />
            </div>

            <div>
              <div class="px-3 pb-2 font-section">Sampai</div>
              <FormInput v-model="to" type="date" class="!box" />
            </div>

            <div class="flex gap-2 border-t border-slate-100 pt-3">
              <Button type="button" variant="primary" class="inline-flex flex-1 items-center justify-center gap-2"
                :disabled="loading" @click="() => { fetchList(1); close() }">
                <Lucide icon="Search" class="h-4 w-4" />
                Cari
              </Button>
              <Button type="button" variant="outline-secondary" class="flex-1"
                :disabled="activeFilterCount === 0" @click="() => { resetFilter(); close() }">
                Reset
              </Button>
            </div>
          </div>
        </template>
        <template #head>
          <Table.Th class="w-14"></Table.Th>
          <Table.Th>Tanggal DR</Table.Th>
          <Table.Th>Kode DR</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Disposisi</Table.Th>
          <Table.Th class="text-right">Volume</Table.Th>
          <Table.Th class="text-right">Sisa</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <template v-for="pr in list" :key="pr.id_pr">
            <Table.Tr class="transition hover:bg-slate-50">
              <Table.Td class="text-center">
                <Button variant="soft-secondary" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Toggle detail"
                  @click="toggle(pr)">
                  <Lucide :icon="pr._open ? 'Minus' : 'Plus'" class="h-4 w-4" />
                </Button>
              </Table.Td>

              <Table.Td class="font-body whitespace-nowrap">
                {{ formatDate(pr.tanggal_pr) }}
              </Table.Td>
              <Table.Td class="font-num">
                {{ pr.nomor_pr }}
              </Table.Td>
              <Table.Td class="font-body whitespace-pre-line">
                {{ pr.customers || '-' }}
              </Table.Td>
              <Table.Td class="font-num whitespace-pre-line">
                {{ pr.nomor_pos || '-' }}
              </Table.Td>
              <Table.Td>
                <span class="font-label rounded px-2 py-1" :class="disposisiClass(pr.disposisi)">
                  {{ pr.disposisi_label }}
                </span>
              </Table.Td>
              <Table.Td class="font-num text-right">
                {{ formatNumber(pr.total_volume) }}
              </Table.Td>
              <Table.Td class="font-num text-right">
                {{ formatNumber(pr.total_sisa) }}
              </Table.Td>
              <Table.Td class="text-center">
                <Button :as="RouterLink" :to="{ name: 'procurement-dr-detail', params: { id: pr.id_pr } }"
                  variant="soft-info" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Detail">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </Button>
              </Table.Td>
            </Table.Tr>

            <Table.Tr v-if="pr._open">
              <Table.Td colspan="9" class="bg-slate-50 p-3">
                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                  <Table class="min-w-full font-body">
                    <Table.Thead class="bg-slate-50 font-label">
                      <Table.Tr>
                        <Table.Th>Produk</Table.Th>
                        <Table.Th>Customer</Table.Th>
                        <Table.Th>Nomor PO</Table.Th>
                        <Table.Th class="text-right">Volume</Table.Th>
                        <Table.Th class="text-right">Dialokasi</Table.Th>
                        <Table.Th class="text-right">Sisa</Table.Th>
                        <Table.Th class="text-center">Aksi</Table.Th>
                      </Table.Tr>
                    </Table.Thead>
                    <Table.Tbody>
                      <Table.Tr v-for="item in pr.items" :key="item.id_prd">
                        <Table.Td>{{ item.produk }}</Table.Td>
                        <Table.Td>{{ item.customer || '-' }}</Table.Td>
                        <Table.Td class="font-num">{{ item.nomor_poc || '-' }}</Table.Td>
                        <Table.Td class="font-num text-right">{{ formatNumber(item.volume) }}</Table.Td>
                        <Table.Td class="font-num text-right">{{ formatNumber(item.allocated) }}</Table.Td>
                        <Table.Td class="font-num text-right">{{ formatNumber(item.remain) }}</Table.Td>
                        <Table.Td class="text-center">
                          <Button variant="soft-success" size="sm" class="inline-flex items-center gap-2"
                            :disabled="item.remain <= 0" @click="openAllocate(pr, item)">
                            <Lucide icon="PackageCheck" class="h-4 w-4" />
                            Pilih Stok
                          </Button>
                        </Table.Td>
                      </Table.Tr>
                      <Table.Tr v-if="!pr.items.length">
                        <Table.Td colspan="7" class="font-body py-6 text-center">
                          Tidak ada detail.
                        </Table.Td>
                      </Table.Tr>
                    </Table.Tbody>
                  </Table>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </template>
      </DataList>
    </div>

    <div v-if="showAlloc" class="fixed inset-0 z-[60] flex items-center justify-center px-4">
      <div class="absolute inset-0 bg-black/40" @click="closeAlloc"></div>
      <div class="relative w-full max-w-3xl rounded-lg bg-white shadow-xl">
        <div class="border-b border-slate-200 px-5 py-4">
          <div class="font-strong">Alokasikan Stok</div>
          <div class="font-body mt-1">{{ currentIt?.produk }}</div>
        </div>

        <div class="space-y-4 p-5">
          <div class="font-body rounded-lg bg-slate-50 p-3">
            Butuh dialokasikan:
            <span class="font-num">{{ formatNumber(currentIt?.remain || 0) }}</span>
            dari volume
            <span class="font-num">{{ formatNumber(currentIt?.volume || 0) }}</span>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-[160px_minmax(0,1fr)_auto] md:items-end">
            <div>
              <FormLabel for="stock-min">Min Volume</FormLabel>
              <FormInput id="stock-min" v-model.number="stockFilter.min" type="number" step="1" class="!box" />
            </div>

            <div>
              <FormLabel for="stock-search">Cari Lot</FormLabel>
              <FormInput id="stock-search" v-model="stockFilter.q" placeholder="PO / receive id" class="!box" />
            </div>

            <Button variant="primary" class="inline-flex items-center gap-2" @click="loadStocks">
              <Lucide icon="Search" class="h-4 w-4" />
              Cari Stok
            </Button>
          </div>

          <div class="overflow-x-auto rounded-lg border border-slate-200">
            <Table class="min-w-full">
              <Table.Thead class="bg-slate-50 font-label">
                <Table.Tr>
                  <Table.Th>Lot</Table.Th>
                  <Table.Th>Tanggal</Table.Th>
                  <Table.Th class="text-right">Avail</Table.Th>
                  <Table.Th class="text-right">Alokasikan</Table.Th>
                </Table.Tr>
              </Table.Thead>
              <Table.Tbody>
                <Table.Tr v-for="stock in stocks" :key="stock.id">
                  <Table.Td class="font-num">
                    PP:{{ stock.po_produk_id || '-' }} RI:{{ stock.receive_item_id || '-' }}
                  </Table.Td>
                  <Table.Td class="font-body">{{ formatDateTime(stock.created_at) }}</Table.Td>
                  <Table.Td class="font-num text-right">{{ formatNumber(stock.volume) }}</Table.Td>
                  <Table.Td>
                    <FormInput v-model.number="stock._qty" type="number" min="0" :max="stock.volume" step="1"
                      class="ml-auto max-w-[160px] text-right" />
                  </Table.Td>
                </Table.Tr>
                <Table.Tr v-if="!stocks.length">
                  <Table.Td colspan="4" class="font-body py-6 text-center">
                    Tidak ada stok.
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </div>

          <div class="font-body text-right">
            Total alokasi:
            <span class="font-num">{{ formatNumber(totalAlloc) }}</span>
            <span class="mx-1">/</span>
            Sisa target:
            <span class="font-semibold text-slate-800">
              {{ formatNumber((currentIt?.remain || 0) - totalAlloc) }}
            </span>
            <div v-if="allocError" class="font-caption mt-1 !text-danger">{{ allocError }}</div>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
          <Button variant="outline-secondary" :disabled="saving" @click="closeAlloc">Batal</Button>
          <Button variant="primary" :disabled="saving" @click="saveAllocation">
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
