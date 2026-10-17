<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { Menu } from '@/components/Base/Headless'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi'
import { formatCurrency, formatDate, formatNumber } from '@/utils/format'
import { actionErrorMessage } from '@/utils/http-error-route'
import { manageableBrands } from '@/utils/quotation-brand'
import type { ApiPaginatedResponse, ApiResponse, PoCustomerIndexRow } from '@/types'

import { poCustomerStatusBadgeClass } from './status'

const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()
const poCustomerApi = createResourceApi('/customer-pos')

const poCustomers = ref<PoCustomerIndexRow[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
  target: ref<PoCustomerIndexRow | null>(null),
}

const canCreatePo = computed<boolean>(() => manageableBrands(auth.can).length > 0)

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1): Promise<void> {
  loading.value = true
  try {
    const { data }: { data: ApiPaginatedResponse<PoCustomerIndexRow> } = await poCustomerApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })
    poCustomers.value = data.data ?? []
    currentPage.value = data.meta.current_page ?? 1
    totalPages.value = data.meta.last_page ?? 1
    totalRecords.value = data.meta.total ?? poCustomers.value.length
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data PO Customer.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function canModifyRow(row: PoCustomerIndexRow): boolean {
  return row.can_update && ['awaiting_process', 'blocked'].includes(row.status_key)
}

function confirmDelete(row: PoCustomerIndexRow): void {
  deleteDialog.target.value = row
  deleteDialog.isOpen.value = true
}

async function submitDelete(): Promise<void> {
  const target = deleteDialog.target.value
  if (!target) return

  deleteDialog.isLoading.value = true
  try {
    const { data }: { data: ApiResponse<null> } = await poCustomerApi.destroy(target.id_poc)
    deleteDialog.isOpen.value = false
    success('Berhasil', data.message || 'PO Customer berhasil dihapus.')
    const isLastRowOfPage = poCustomers.value.length === 1 && currentPage.value > 1
    await fetchData(isLastRowOfPage ? currentPage.value - 1 : currentPage.value)
  } catch (e: any) {
    deleteDialog.isOpen.value = false
    notifyError('Gagal', actionErrorMessage(e, 'Gagal menghapus PO Customer.'))
  } finally {
    deleteDialog.isLoading.value = false
    deleteDialog.target.value = null
  }
}

function goToCreate(): void {
  router.push({ name: 'penawarans-po' })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="PO Customer" description="Daftar PO Customer dan status verifikasinya.">
        <template #action>
          <Button v-if="canCreatePo" variant="white" class="inline-flex items-center gap-2" @click="goToCreate">
            <Lucide icon="PlusCircle" class="w-4 h-4" />
            Buat PO Customer
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="poCustomers.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nomor PO"
        loading-text="Memuat data PO Customer..." empty-description="Belum ada PO Customer."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th class="text-center">Total Volume / Total Nilai (DPP)</Table.Th>
          <Table.Th class="text-center">Disposisi</Table.Th>
          <Table.Th class="right-0 z-10 sticky bg-slate-50 text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in poCustomers" :key="row.id_poc" class="group transition hover:bg-slate-50">
            <Table.Td class="num-sm text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="text-body-strong">{{ row.nomor_poc || '-' }}</div>
              <div class="text-slate-500">{{ formatDate(row.tanggal_poc) }}</div>
            </Table.Td>

            <Table.Td>
              <div class="text-body-strong">{{ row.customer?.customer_code || '-' }}</div>
              <div class="text-slate-500">{{ row.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              {{ row.quotation?.nomor_penawaran || '-' }}
            </Table.Td>

            <Table.Td class="text-center whitespace-nowrap">
              <div>{{ formatNumber(row.total_volume) }} m³</div>
              <div class="text-slate-500">{{ formatCurrency(row.total_value) }}</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="text-form-label inline-flex items-center rounded-full px-3 py-1"
                :class="poCustomerStatusBadgeClass(row.status_key)">
                {{ row.status_label }}
              </span>
            </Table.Td>

            <Table.Td class="right-0 z-10 sticky bg-white group-hover:bg-slate-50 w-[80px] text-center">
              <Menu>
                <Menu.Button :as="Button" variant="outline-secondary" class="px-2 py-1">
                  <Lucide icon="MoreVertical" class="w-4 h-4" />
                </Menu.Button>
                <Menu.Items class="w-52" placement="bottom-end">
                  <Menu.Item :as="RouterLink" :to="{ name: 'po-customers-detail', params: { id: row.id_poc } }">
                    <Lucide icon="Eye" class="mr-2 w-4 h-4" />
                    Detail
                  </Menu.Item>

                  <Menu.Item v-if="canModifyRow(row)" :as="RouterLink"
                    :to="{ name: 'po-customers-edit', params: { id: row.id_poc } }">
                    <Lucide icon="Pencil" class="mr-2 w-4 h-4" />
                    Edit
                  </Menu.Item>

                  <template v-if="canModifyRow(row)">
                    <Menu.Divider />
                    <Menu.Item class="!text-danger" @click="confirmDelete(row)">
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

      <DeleteRecordDialog :open="deleteDialog.isOpen.value" :title="`Hapus PO ${deleteDialog.target.value?.nomor_poc ?? ''}`"
        description="PO Customer beserta itemnya dan lampirannya akan dihapus." :loading="deleteDialog.isLoading.value"
        @close="deleteDialog.isOpen.value = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
