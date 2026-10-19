<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import Badge from '@/components/SystemDesign/Data/Badge.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import InfoTip from '@/components/SystemDesign/Data/InfoTip.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDateRangeShort, formatDateTime, todayDateKey } from '@/utils/format'
import type { TransportMarginPeriod, TransportMarginRow } from '@/types'

import { PRICE_FIELD_HINTS, francoTransportPrice } from '../ProductPrice/priceFieldHints'

const { success, error } = useNotification()

const periods = ref<TransportMarginPeriod[]>([])
const selectedPeriodId = ref<number | ''>('')
const rows = ref<TransportMarginRow[]>([])
const loading = ref(false)
const search = ref('')
const perPage = ref(25)
const currentPage = ref(1)

const drafts = reactive<Record<number, number | null>>({})
const savingId = ref<number | null>(null)

const selectedPeriod = computed(() => periods.value.find((p) => p.id === selectedPeriodId.value) ?? null)

const filteredRows = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return rows.value

  return rows.value.filter((r) =>
    [r.branch.name, r.product.nama_produk, r.product.ukuran].some((v) => String(v ?? '').toLowerCase().includes(q)),
  )
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / perPage.value)))

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredRows.value.slice(start, start + perPage.value)
})

watch([search, perPage], () => {
  currentPage.value = 1
})

watch(selectedPeriodId, (id) => {
  if (id) fetchRows(id)
})

onMounted(fetchPeriods)

async function fetchPeriods() {
  try {
    const { data } = await axios.get('/api/transport-margins/periods')
    periods.value = data.data
    const today = todayDateKey()
    const active = periods.value.find((p) => p.start_date <= today && p.end_date >= today)
    selectedPeriodId.value = active?.id ?? periods.value[0]?.id ?? ''
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat daftar periode.')
  }
}

async function fetchRows(periodId: number) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/transport-margins', { params: { price_period_id: periodId } })
    rows.value = data.data
    Object.keys(drafts).forEach((k) => delete drafts[Number(k)])
    rows.value.forEach((r) => {
      drafts[r.id] = r.margin_transport_amount !== null ? Number(r.margin_transport_amount) : null
    })
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat margin transport.')
  } finally {
    loading.value = false
  }
}

function isDirty(row: TransportMarginRow): boolean {
  const saved = row.margin_transport_amount !== null ? Number(row.margin_transport_amount) : null
  return drafts[row.id] !== saved
}

function draftFrancoTransportPrice(row: TransportMarginRow): number | null {
  return francoTransportPrice(row.cogs_transport_price, drafts[row.id])
}

async function save(row: TransportMarginRow) {
  savingId.value = row.id
  try {
    const { data } = await axios.patch(`/api/product-prices/${row.id}/transport-margin`, {
      margin_transport_amount: drafts[row.id],
    })
    const index = rows.value.findIndex((r) => r.id === row.id)
    if (index !== -1) rows.value[index] = data
    drafts[row.id] = data.margin_transport_amount !== null ? Number(data.margin_transport_amount) : null
    syncFilledCount()
    success('Tersimpan', `Margin transport ${row.product.nama_produk} (${row.branch.name}) diperbarui.`)
  } catch (e: any) {
    error('Gagal menyimpan', e.response?.data?.message ?? 'Terjadi kesalahan saat menyimpan.')
  } finally {
    savingId.value = null
  }
}

function syncFilledCount() {
  const period = selectedPeriod.value
  if (!period) return
  period.filled_count = rows.value.filter((r) => r.margin_transport_amount !== null).length
}

function periodLabel(p: TransportMarginPeriod): string {
  return `${formatDateRangeShort(p.start_date, p.end_date)} · ${p.filled_count}/${p.franco_count} terisi${p.is_ended ? ' · berakhir' : ''}`
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Margin Transport"
        description="Keuntungan transport untuk produk franco. Harga Transport (franco) = COGS transport + margin transport, menjadi harga transport terkunci di Penawaran." />

      <DataList v-model:search="search" v-model:per-page="perPage" :loading="loading"
        :empty="paginatedRows.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true"
        :total="filteredRows.length" :current-page="currentPage" :total-pages="totalPages"
        search-placeholder="Cari cabang / produk..." loading-text="Memuat margin transport..."
        empty-description="Tidak ada produk franco di periode ini." @page-change="(p) => { currentPage = p }">
        <template #toolbar>
          <div class="flex items-center gap-2">
            <span class="text-form-label whitespace-nowrap">Periode</span>
            <FormSelect v-model.number="selectedPeriodId" class="w-72">
              <option v-for="p in periods" :key="p.id" :value="p.id">{{ periodLabel(p) }}</option>
            </FormSelect>
            <Badge v-if="selectedPeriod?.is_ended" variant="soft-secondary">Periode berakhir</Badge>
          </div>
        </template>

        <template #head>
          <Table.Th>Cabang</Table.Th>
          <Table.Th>Produk</Table.Th>
          <Table.Th class="text-right whitespace-nowrap">
            COGS Transport
            <InfoTip :content="PRICE_FIELD_HINTS.cogs_transport_price" />
          </Table.Th>
          <Table.Th class="w-56 whitespace-nowrap">
            Margin Transport
            <InfoTip :content="PRICE_FIELD_HINTS.margin_transport_amount" />
          </Table.Th>
          <Table.Th class="text-right whitespace-nowrap">
            Harga Transport
            <InfoTip :content="PRICE_FIELD_HINTS.transport_price" />
          </Table.Th>
          <Table.Th>Diperbarui</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="row in paginatedRows" :key="row.id" class="hover:bg-slate-50 transition">
            <Table.Td class="text-body">{{ row.branch.name }}</Table.Td>
            <Table.Td>
              <span class="text-body-strong">{{ row.product.nama_produk }}</span>
              <span v-if="row.product.ukuran"
                class="inline-block bg-slate-50 ml-2 px-1.5 py-0.5 border border-slate-300 rounded-md text-slate-600 text-xs num-sm">
                {{ row.product.ukuran }}
              </span>
            </Table.Td>
            <Table.Td class="text-right whitespace-nowrap num-sm">
              <template v-if="row.cogs_transport_price !== null">{{ formatCurrency(row.cogs_transport_price)
                }}</template>
              <span v-else class="text-caption">Belum diisi Procurement</span>
            </Table.Td>
            <Table.Td>
              <CurrencyField :model-value="drafts[row.id]" :readonly="row.is_ended" placeholder="Belum diisi"
                @update:model-value="(v) => { drafts[row.id] = v }" />
            </Table.Td>
            <Table.Td class="text-body-strong text-right whitespace-nowrap num-sm">
              {{ draftFrancoTransportPrice(row) !== null ? formatCurrency(draftFrancoTransportPrice(row)) : '-' }}
            </Table.Td>
            <Table.Td class="text-caption whitespace-nowrap">
              <template v-if="row.updated_at">{{ formatDateTime(row.updated_at) }}<br>{{ row.updated_by ?? ''
                }}</template>
              <template v-else>-</template>
            </Table.Td>
            <Table.Td class="text-center">
              <Button v-if="!row.is_ended" variant="soft-success" rounded class="!shadow-none !p-0 !w-8 !h-8"
                title="Simpan" :disabled="!isDirty(row) || savingId === row.id" @click="save(row)">
                <Lucide :icon="savingId === row.id ? 'Loader2' : 'Save'" class="w-4 h-4"
                  :class="{ 'animate-spin': savingId === row.id }" />
              </Button>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
