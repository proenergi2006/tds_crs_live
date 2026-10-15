<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import { Menu } from '@/components/Base/Headless'
import TippyContent from '@/components/Base/TippyContent'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatDate, toDateKey, todayDateKey } from '@/utils/format'
import { actionErrorMessage } from '@/utils/http-error-route'
import {
  QUOTATION_BRANDS,
  QUOTATION_BRAND_FILTER_OPTIONS,
  canManageQuotationBrand,
  manageableBrands,
  quotationPermission,
  viewableBrands,
} from '@/utils/quotation-brand'
import type { ApiPaginatedResponse, ApiResponse, QuotationBrand, QuotationIndexRow } from '@/types'
import {
  QUOTATION_STATUS,
  quotationStatusBadgeClass,
  quotationStatusLabel,
} from './status'

const router = useRouter()
const { success, error } = useNotification()
const auth = useAuthStore()

const QUOTATION_API_BASE = '/api/quotations'

const quotations = ref<QuotationIndexRow[]>([])
const filterBrand = ref<QuotationBrand | ''>('')
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<{ id: number; nomor: string } | null>(null)

const showBrandFilter = computed(() => viewableBrands(auth.can).length > 1)
const activeFilterCount = computed(() => (filterBrand.value ? 1 : 0))
const canCreateQuotation = computed(() => manageableBrands(auth.can).length > 0)
const canViewAnyQuotation = computed(() =>
  QUOTATION_BRANDS.some((brand) => auth.can(quotationPermission(brand, 'viewAny'))),
)
const dataListColspan = computed(() => (canViewAnyQuotation.value ? 8 : 7))
const emptyDescription = computed(() =>
  filterBrand.value ? 'Tidak ada penawaran untuk filter ini.' : 'Belum ada penawaran untuk ditampilkan.',
)

watch(filterBrand, () => fetchData(1))
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true
  try {
    const res = await axios.get<ApiPaginatedResponse<QuotationIndexRow>>(QUOTATION_API_BASE, {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        brand: filterBrand.value || undefined,
      },
    })
    quotations.value = res.data.data
    currentPage.value = res.data.meta.current_page
    totalPages.value = res.data.meta.last_page
    totalRecords.value = res.data.meta.total
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  fetchData(page)
}

function openCreate() {
  router.push({ name: 'quotations.create' })
}

function isOwnRow(row: QuotationIndexRow): boolean {
  return Number(row.user_id) === Number(auth.user?.id)
}

function canModifyDraft(row: QuotationIndexRow): boolean {
  return row.status === QUOTATION_STATUS.Draft && isOwnRow(row) && canManageQuotationBrand(auth.can, row.brand)
}

function canCreatePoFrom(row: QuotationIndexRow): boolean {
  return row.status === QUOTATION_STATUS.Approved && isValidToday(row) && canManageQuotationBrand(auth.can, row.brand)
}

function resetFilters() {
  filterBrand.value = ''
}

function isValidToday(pen: { masa_berlaku?: string | null; sampai_dengan?: string | null }): boolean {
  const from = toDateKey(pen.masa_berlaku)
  const to = toDateKey(pen.sampai_dengan)
  if (!from || !to) return true
  const today = todayDateKey()
  return from <= today && today <= to
}

function openCreateSalesOrder(id: number) {
  router.push({ name: 'penawarans-po', query: { quotation_id: id } })
}

function confirmDelete(id: number, nomor: string) {
  deleteTarget.value = { id, nomor }
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    const { data } = await axios.delete<ApiResponse<null>>(`${QUOTATION_API_BASE}/${deleteTarget.value.id}`)
    deleteModal.value = false
    success('Berhasil', data.message || 'Penawaran berhasil dihapus.')
    fetchData(currentPage.value)
  } catch (e: any) {
    error('Gagal', actionErrorMessage(e, 'Gagal menghapus penawaran.'))
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

function sizePills(item: any): string[] {
  const nama = item?.product?.ukuran?.nama_ukuran
  if (!nama) return []
  return String(nama).split(/,\s+/).map((s: string) => s.trim()).filter(Boolean)
}

function joinWithAmpersand(items: string[]): string {
  if (items.length <= 1) return items[0] || ''
  return `${items.slice(0, -1).join(', ')} & ${items[items.length - 1]}`
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Penawaran" description="Kelola data penawaran ke customer">
        <template #action>
          <Button v-if="canCreateQuotation" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="PlusCircle" class="w-4 h-4" />
            Tambah Penawaran
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="quotations.length === 0" :colspan="dataListColspan" :show-footer="true" :show-toolbar="true"
        :total="totalRecords" :current-page="currentPage" :total-pages="totalPages" :active-filter-count="activeFilterCount"
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
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Titik Serah Terima</Table.Th>
          <Table.Th v-if="canViewAnyQuotation">Marketing</Table.Th>
          <Table.Th>Tanggal</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="right-0 z-10 sticky bg-slate-50 text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(pen, idx) in quotations" :key="pen.id" class="group hover:bg-slate-50 transition">
            <Table.Td class="num-sm text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="num-sm whitespace-nowrap">
              <span class="hover:underline cursor-pointer" :data-tooltip="`produk-tooltip-${pen.id}`">
                {{ pen.nomor_penawaran }}
              </span>
              <div class="tooltip-content">
                <TippyContent :to="`produk-tooltip-${pen.id}`"
                  :options="{ placement: 'right', interactive: true }">
                  <div class="w-80 max-w-[85vw]">
                    <div class="flex justify-between items-center gap-2 mb-3">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="bg-primary rounded-full w-2 h-2 shrink-0"></span>
                        <span class="text-form-label text-slate-500 truncate tracking-wide">Daftar Produk Penawaran</span>
                      </div>
                      <span class="bg-slate-100 px-2 py-0.5 rounded-full text-caption text-slate-600 shrink-0">
                        {{ pen.items?.length || 0 }} Item{{ (pen.items?.length || 0) > 1 ? 's' : '' }}
                      </span>
                    </div>

                    <div v-if="!pen.items || pen.items.length === 0" class="text-caption text-slate-500">
                      Belum ada produk.
                    </div>

                    <div v-else class="flex flex-col gap-2 pr-1 max-h-72 overflow-y-auto">
                      <div v-for="it in pen.items" :key="it.id"
                        class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                        <div class="flex justify-between items-start gap-2">
                          <div class="min-w-0">
                            <div class="text-body-strong truncate">{{ it.product?.nama_produk || '-' }}</div>
                            <div class="text-caption text-primary truncate">{{ it.product?.jenis?.nama || '-' }}</div>
                          </div>
                          <span
                            class="bg-primary/10 px-2 py-0.5 rounded-full num-micro text-primary whitespace-nowrap shrink-0">
                            {{ Number(it.quantity ?? 0).toLocaleString('id-ID') }} m³
                          </span>
                        </div>

                        <div v-if="sizePills(it).length" class="mt-2 text-caption text-slate-600">
                          <span class="text-body-strong text-slate-700 text-xs">Komposisi:</span>
                          Ukuran {{ joinWithAmpersand(sizePills(it)) }}
                        </div>
                      </div>
                    </div>

                    <div v-if="pen.items && pen.items.length > 0"
                      class="flex justify-between items-center mt-3 pt-1 border-slate-100 border-t">
                      <span class="text-caption text-slate-500">Total Volume:</span>
                      <span class="text-body-strong">{{ Number(pen.total_volume ?? 0).toLocaleString('id-ID') }} m³</span>
                    </div>
                  </div>
                </TippyContent>
              </div>
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              {{ pen.customer?.company_name || '-' }}
            </Table.Td>
            <Table.Td class="max-w-[240px] truncate" :title="pen.keterangan || ''">
              {{ pen.keterangan || '-' }}
            </Table.Td>
            <Table.Td v-if="canViewAnyQuotation" class="whitespace-nowrap">
              {{ pen.marketing?.name || '-' }}
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              <div class="text-caption text-slate-500">Dibuat: {{ formatDate(pen.created_at) }}</div>
              <div class="text-caption text-slate-500">Berlaku hingga: {{ formatDate(pen.sampai_dengan) }}</div>
            </Table.Td>
            <Table.Td class="text-center whitespace-nowrap">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-form-label whitespace-nowrap"
                :class="quotationStatusBadgeClass(pen.status)">
                {{ pen.status_label || quotationStatusLabel(pen.status) }}
              </span>
            </Table.Td>
            <Table.Td class="right-0 z-10 sticky bg-white group-hover:bg-slate-50 w-[80px] text-center">
              <Menu>
                <Menu.Button :as="Button" variant="outline-secondary" class="px-2 py-1">
                  <Lucide icon="MoreVertical" class="w-4 h-4" />
                </Menu.Button>
                <Menu.Items class="w-52" placement="bottom-end">
                  <Menu.Item :as="RouterLink" :to="{ name: 'quotations.show', params: { id: pen.id } }">
                    <Lucide icon="Eye" class="mr-2 w-4 h-4" />
                    Detail
                  </Menu.Item>

                  <Menu.Item v-if="canModifyDraft(pen)" :as="RouterLink"
                    :to="{ name: 'quotations.edit', params: { id: pen.id } }">
                    <Lucide icon="Edit" class="mr-2 w-4 h-4" />
                    Edit
                  </Menu.Item>

                  <Menu.Item v-if="canCreatePoFrom(pen)"
                    @click="openCreateSalesOrder(pen.id)">
                    <Lucide icon="ShoppingCart" class="mr-2 w-4 h-4" />
                    Buat PO Customer
                  </Menu.Item>

                  <template v-if="canModifyDraft(pen)">
                    <Menu.Divider />
                    <Menu.Item class="!text-danger" @click="confirmDelete(pen.id, pen.nomor_penawaran ?? '')">
                      <Lucide icon="Trash2" class="mr-2 w-4 h-4" />
                      Hapus
                    </Menu.Item>
                  </template>
                </Menu.Items>
              </Menu>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" :title="`Hapus Penawaran ${deleteTarget?.nomor ?? ''}`"
        :loading="deleteLoading" @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
