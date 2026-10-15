<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import { FormSelect } from '@/components/Base/Form'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'
import {
  priceTierBadgeClass,
  priceTierLabel,
  quotationStatusBadgeClass,
  quotationStatusLabel,
} from '@/pages/Quotation/status'
import { formatDate } from '@/utils/format'
import { QUOTATION_BRAND_FILTER_OPTIONS, verifiableBrands } from '@/utils/quotation-brand'
import type { ApiPaginatedResponse, QuotationBrand, QuotationPriceTier, QuotationQueueRow } from '@/types'

import { PRICE_TIER_FILTERS, QUOTATION_VERIFICATION_INDEX } from './config'

const auth = useAuthStore()
const { error } = useNotification()

const quotations = ref<QuotationQueueRow[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)
const priceTier = ref<QuotationPriceTier | null>(null)
const filterBrand = ref<QuotationBrand | ''>('')

const showBrandFilter = computed(() => verifiableBrands(auth.can).length > 1)
const activeFilterCount = computed(() => (filterBrand.value ? 1 : 0))
const emptyDescription = computed(() =>
  filterBrand.value
    ? 'Tidak ada penawaran untuk filter ini.'
    : 'Belum ada penawaran yang sesuai dengan filter pencarian.',
)

watch(priceTier, () => fetchData(1))
watch(filterBrand, () => fetchData(1))
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true

  try {
    const api = createResourceApi(QUOTATION_VERIFICATION_INDEX.endpoint)
    const { data }: { data: ApiPaginatedResponse<QuotationQueueRow> } = await api.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      price_tier: priceTier.value ?? undefined,
      brand: filterBrand.value || undefined,
    })

    quotations.value = data.data ?? []
    currentPage.value = data.meta.current_page ?? 1
    totalPages.value = data.meta.last_page ?? 1
    totalRecords.value = data.meta.total ?? quotations.value.length
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data penawaran.')
  } finally {
    loading.value = false
  }
}

function selectPriceTier(value: QuotationPriceTier | null) {
  priceTier.value = value
}

function resetFilters() {
  filterBrand.value = ''
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="QUOTATION_VERIFICATION_INDEX.title" :description="QUOTATION_VERIFICATION_INDEX.description" />

      <div class="inline-flex w-fit gap-1 rounded-lg border border-slate-200 bg-white p-1">
        <button v-for="f in PRICE_TIER_FILTERS" :key="f.value ?? 'all'" type="button"
          class="rounded-md px-3 py-1.5 text-sm font-medium transition"
          :class="priceTier === f.value ? 'bg-theme-1 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="selectPriceTier(f.value)">
          {{ f.label }}
        </button>
      </div>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="quotations.length === 0" :colspan="8" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" :active-filter-count="activeFilterCount"
        search-placeholder="Cari nomor atau customer..." loading-text="Memuat data penawaran..."
        :empty-description="emptyDescription" @page-change="goToPage">
        <template v-if="showBrandFilter" #filters>
          <div class="space-y-4 p-1">
            <div>
              <div class="px-3 pt-1 pb-2 text-overline">
                Brand
              </div>

              <FormSelect v-model="filterBrand">
                <option v-for="opt in QUOTATION_BRAND_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </FormSelect>
            </div>

            <div class="pt-3 border-slate-100 border-t">
              <Button type="button" variant="outline-secondary" class="w-full" :disabled="activeFilterCount === 0"
                @click="resetFilters">
                Reset filter
              </Button>
            </div>
          </div>
        </template>

        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Cabang</Table.Th>
          <Table.Th class="text-center">Masa Berlaku</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Tier</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(pen, idx) in quotations" :key="pen.id" class="transition hover:bg-slate-50">
            <Table.Td class="num-sm text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="text-body-strong">{{ pen.nomor_penawaran || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="text-body-strong">{{ pen.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="text-slate-600">{{ pen.branch?.nama_cabang || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="whitespace-nowrap text-slate-600">{{ formatDate(pen.masa_berlaku) }} s/d
                {{ formatDate(pen.sampai_dengan) }}</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="text-form-label inline-flex items-center rounded-full px-3 py-1"
                :class="quotationStatusBadgeClass(pen.status)">
                {{ quotationStatusLabel(pen.status) }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <span v-if="pen.price_tier" class="text-form-label inline-flex items-center rounded-full px-3 py-1"
                :class="priceTierBadgeClass(pen.price_tier)">
                {{ priceTierLabel(pen.price_tier) }}
              </span>
              <span v-else>-</span>
            </Table.Td>

            <Table.Td class="text-center">
              <RouterLink :to="{ name: QUOTATION_VERIFICATION_INDEX.detailRouteName, params: { id: pen.id } }"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100 hover:text-blue-700"
                title="Lihat Detail">
                <Lucide icon="Eye" class="h-5 w-5" />
              </RouterLink>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
