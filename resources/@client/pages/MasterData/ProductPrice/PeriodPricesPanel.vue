<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'

type ProductPriceRow = {
  id: number
  branch_id: number
  product_id: number
  price_list: string | number | null
  price_list_pe: string | number | null
  bm_price: string | number | null
  om_price: string | number | null
  ceo_price: string | number | null
  cogs_price: string | number | null
  margin_amount: string | number | null
  notes: string | null
  branch?: { id: number; name: string }
  product?: {
    id_produk: number
    nama_produk: string
    ukuran?: { nama_ukuran: string; satuan?: { nama_satuan: string } }
  }
}

const props = defineProps<{
  rows: ProductPriceRow[]
  loading: boolean
  cabangs: any[]
  produks: any[]
  canEdit: boolean
  canDelete: boolean
  view: 'procurement' | 'ceo'
}>()

const emit = defineEmits<{
  (e: 'delete', id: number): void
  (e: 'edit', id: number): void
}>()

const search = ref('')
const filterCabang = ref<string | number>('')
const filterProduk = ref<string | number>('')
const currentPage = ref(1)
const perPage = ref(10)

const activeFilterCount = computed(() =>
  [filterCabang.value, filterProduk.value].filter(Boolean).length,
)

const filteredRows = computed(() => {
  let rows = props.rows

  const q = search.value.trim().toLowerCase()
  if (q) {
    rows = rows.filter(r =>
      (r.product?.nama_produk ?? '').toLowerCase().includes(q) ||
      (r.branch?.name ?? '').toLowerCase().includes(q),
    )
  }

  if (filterCabang.value) {
    rows = rows.filter(r => r.branch_id === Number(filterCabang.value))
  }

  if (filterProduk.value) {
    rows = rows.filter(r => r.product_id === Number(filterProduk.value))
  }

  return rows
})

const totalRecords = computed(() => filteredRows.value.length)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredRows.value.length / perPage.value)),
)

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredRows.value.slice(start, start + perPage.value)
})

watch(
  [search, filterCabang, filterProduk, perPage, () => props.rows],
  () => { currentPage.value = 1 },
)

function formatNumber(value: string | number | null | undefined) {
  if (value === null || value === undefined || value === '') return '-'
  const num = Number(value)
  return isNaN(num) ? '-' : 'Rp ' + num.toLocaleString('id-ID')
}

function zeroClass(value: string | number | null | undefined) {
  if (value === null || value === undefined || value === '') return ''
  return Number(value) === 0 ? 'text-danger' : ''
}

function ukuranText(row: ProductPriceRow) {
  const ukuran = row.product?.ukuran?.nama_ukuran ?? ''
  const satuan = row.product?.ukuran?.satuan?.nama_satuan ?? ''
  return `${ukuran} ${satuan}`.trim()
}
</script>

<template>
  <DataList v-model:search="search" v-model:per-page="perPage" :loading="loading" :empty="filteredRows.length === 0"
    :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
    :total-pages="totalPages" :active-filter-count="activeFilterCount" search-placeholder="Cari produk / cabang..."
    loading-text="Memuat data harga..." empty-description="Tidak ada data harga untuk filter ini."
    @page-change="(p) => { currentPage = p }">
    <template #filters>
      <div class="space-y-4 p-1">
        <div>
          <div class="px-3 pt-1 pb-2 font-section">Cabang</div>
          <FormSelect v-model="filterCabang">
            <option value="">Semua Cabang</option>
            <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
              {{ cabang.nama_cabang }}
            </option>
          </FormSelect>
        </div>
        <div>
          <div class="px-3 pb-2 font-section">Produk</div>
          <FormSelect v-model="filterProduk">
            <option value="">Semua Produk</option>
            <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
              {{ produk.nama_produk }}
              <template v-if="produk.ukuran">
                ({{ produk.ukuran.nama_ukuran }} {{ produk.ukuran.satuan?.nama_satuan }})
              </template>
            </option>
          </FormSelect>
        </div>
        <div class="pt-3 border-slate-100 border-t">
          <Button type="button" variant="outline-secondary" class="w-full" :disabled="activeFilterCount === 0"
            @click="filterCabang = ''; filterProduk = ''">
            Clear Filter
          </Button>
        </div>
      </div>
    </template>

    <template #head>
      <Table.Th>Cabang</Table.Th>
      <Table.Th>Produk</Table.Th>
      <Table.Th class="text-right">COGS</Table.Th>
      <Table.Th class="text-right">Margin</Table.Th>
      <template v-if="view === 'ceo'">
        <Table.Th class="text-right">Price List</Table.Th>
        <Table.Th class="text-right">Approval Tier</Table.Th>
      </template>
      <template v-else>
        <Table.Th class="text-right">Price List TDS</Table.Th>
        <Table.Th class="text-right">Price List PE</Table.Th>
      </template>
      <Table.Th class="text-center">Aksi</Table.Th>
    </template>

    <template #body>
      <Table.Tr v-for="item in paginatedRows" :key="item.id" class="hover:bg-slate-50 transition">
        <Table.Td class="font-body">
          {{ item.branch?.name ?? '-' }}
        </Table.Td>

        <Table.Td class="font-body">
          <div class="flex flex-wrap items-start gap-x-2 gap-y-1">
            <span class="font-strong">{{ item.product?.nama_produk ?? '-' }}</span>
            <span v-if="ukuranText(item)"
              class="inline-block bg-slate-50 px-1.5 py-0.5 border border-slate-300 rounded-md font-num text-slate-600 text-xs break-words whitespace-normal">
              {{ ukuranText(item) }}
            </span>
          </div>
        </Table.Td>

        <Table.Td class="font-num font-strong text-base text-right" :class="zeroClass(item.cogs_price)">
          {{ formatNumber(item.cogs_price) }}
        </Table.Td>

        <Table.Td class="font-num font-strong text-base text-right" :class="zeroClass(item.margin_amount)">
          {{ formatNumber(item.margin_amount) }}
        </Table.Td>

        <template v-if="view === 'ceo'">
          <Table.Td class="font-num text-right">
            <div class="flex justify-between items-baseline gap-3 whitespace-nowrap">
              <span class="font-caption text-slate-400">TDS</span>
              <span class="font-strong text-base" :class="zeroClass(item.price_list)">{{ formatNumber(item.price_list)
                }}</span>
            </div>
            <div class="flex justify-between items-baseline gap-3 whitespace-nowrap">
              <span class="font-caption text-slate-400">PE</span>
              <span class="font-strong text-base" :class="zeroClass(item.price_list_pe)">{{
                formatNumber(item.price_list_pe) }}</span>
            </div>
          </Table.Td>

          <Table.Td class="font-num text-right">
            <div class="flex justify-between items-baseline gap-3 whitespace-nowrap">
              <span class="font-caption text-slate-400">BM</span>
              <span class="font-strong text-base" :class="zeroClass(item.bm_price)">{{ formatNumber(item.bm_price)
                }}</span>
            </div>
            <div class="flex justify-between items-baseline gap-3 whitespace-nowrap">
              <span class="font-caption text-slate-400">OM</span>
              <span class="font-strong text-base" :class="zeroClass(item.om_price)">{{ formatNumber(item.om_price)
                }}</span>
            </div>
            <div class="flex justify-between items-baseline gap-3 whitespace-nowrap">
              <span class="font-caption text-slate-400">CEO</span>
              <span class="font-strong text-base" :class="zeroClass(item.ceo_price)">{{ formatNumber(item.ceo_price)
                }}</span>
            </div>
          </Table.Td>
        </template>

        <template v-else>
          <Table.Td class="font-num font-strong text-base text-right" :class="zeroClass(item.price_list)">
            {{ formatNumber(item.price_list) }}
          </Table.Td>

          <Table.Td class="font-num font-strong text-base text-right" :class="zeroClass(item.price_list_pe)">
            {{ formatNumber(item.price_list_pe) }}
          </Table.Td>
        </template>

        <Table.Td class="text-center">
          <div class="inline-flex justify-center items-center gap-1.5">
            <Button v-if="canEdit" variant="soft-pending" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Edit"
              @click="emit('edit', item.id)">
              <Lucide icon="Edit" class="w-4 h-4" />
            </Button>

            <Button v-if="canDelete" variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
              @click="emit('delete', item.id)">
              <Lucide icon="Trash2" class="w-4 h-4" />
            </Button>
          </div>
        </Table.Td>
      </Table.Tr>
    </template>
  </DataList>
</template>
