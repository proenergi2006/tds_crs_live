<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatCurrency, formatDate, formatNumber } from '@/utils/format'

import { poCustomerStatusBadgeClass } from './status'

const router = useRouter()
const { error: notifyError } = useNotification()
const poCustomerApi = createResourceApi('/customer-pos')

const poCustomers = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1): Promise<void> {
  loading.value = true
  try {
    const { data } = await poCustomerApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })
    poCustomers.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? poCustomers.value.length
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

function openDetail(id: number): void {
  router.push({ name: 'po-customers-detail', params: { id } })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="PO Customer" description="Daftar PO Customer dan status verifikasinya." />

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="poCustomers.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nomor PO atau customer..."
        loading-text="Memuat data PO Customer..." empty-description="Belum ada PO Customer."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th class="text-center">Volume / Harga</Table.Th>
          <Table.Th class="text-center">Disposisi</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in poCustomers" :key="row.id_poc" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ row.nomor_poc || '-' }}</div>
              <div class="text-slate-500">{{ formatDate(row.tanggal_poc) }}</div>
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ row.customer?.customer_code || '-' }}</div>
              <div class="text-slate-500">{{ row.customer?.company_name || '-' }}</div>
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              {{ row.penawaran?.nomor_penawaran || '-' }}
            </Table.Td>

            <Table.Td class="text-center whitespace-nowrap">
              <div>{{ formatNumber(row.volume_poc) }} m³</div>
              <div class="text-slate-500">{{ formatCurrency(row.harga_poc) }} /m³</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-3 py-1"
                :class="poCustomerStatusBadgeClass(row.status_key)">
                {{ row.status_label }}
              </span>
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
