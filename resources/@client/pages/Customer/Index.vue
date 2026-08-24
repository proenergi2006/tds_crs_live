<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'

const customerApi = createResourceApi('/customers')
const { success, error } = useNotification()
const router = useRouter()
const auth = useAuthStore()

const isProenergi = [13, 14].includes(Number(auth.user?.id_role))

/* State: data & pagination */
const customers = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalRecords = ref(0)
const totalPages = ref(1)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

const canManageCustomer = computed(() => auth.can('customer.manage'))
const canViewAnyCustomer = computed(() => auth.can('customer.viewAny'))

function canManageRow(item: any) {
  return (
    canManageCustomer.value &&
    (canViewAnyCustomer.value || Number(item.id_user) === Number(auth.user?.id))
  )
}

/* Computed: summary cards (Proenergi only) */
const totalPenawaran = computed(() =>
  customers.value.reduce((sum, c) => sum + Number(c.quotation_count ?? 0), 0)
)

onMounted(() => fetchData())

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchData(page = currentPage.value) {
  loading.value = true
  try {
    const { data } = await customerApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })
    customers.value = data.data ?? []
    currentPage.value = data.meta?.current_page ?? 1
    totalPages.value = data.meta?.last_page ?? 1
    totalRecords.value = data.meta?.total ?? 0
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  fetchData(page)
}

function openCreate() {
  router.push({ name: 'customers-create' })
}

function openReview(idCustomer: number) {
  router.push({ name: 'customer-detail', params: { id: idCustomer } })
}

function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await customerApi.destroy(deleteTarget.value)
    deleteModal.value = false
    success('Berhasil', 'Customer berhasil dihapus.')
    fetchData(currentPage.value)
  } catch (e: any) {
    error('Gagal menghapus', e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

// Status verifikasi disederhanakan ke UI: hanya "Verified"/"Unverified", backend tetap kirim 6 state.
function getVerificationBadgeLabel(item: any) {
  return item.verification_badge === 'verified' ? 'Verified' : 'Unverified'
}

function getVerificationBadgeClass(badge?: string) {
  return badge === 'verified' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-700'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="isProenergi ? 'Master Customers Proenergi' : 'Master Customers'"
        :description="isProenergi ? 'Kelola data customer Proenergi yang kamu tangani' : 'Kelola data customer yang kamu tangani'">
        <template #action>
          <Button v-if="canManageCustomer" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Customer
          </Button>
        </template>
      </PageHeader>

      <!-- Summary Cards (Proenergi only) -->
      <div v-if="isProenergi" class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="box p-4">
          <div class="font-label">Total Customer</div>
          <div class="font-num-display mt-1">{{ totalRecords }}</div>
        </div>
        <!-- status_customer belum tersedia di API, ditampilkan sebagai placeholder -->
        <div class="box p-4">
          <div class="font-label">Prospect</div>
          <div class="font-num-display mt-1 text-slate-300"
            title="Belum tersedia — akan disambungkan ke customer_status">-
          </div>
        </div>
        <div class="box p-4">
          <div class="font-label">Customer Tetap</div>
          <div class="font-num-display mt-1 text-slate-300"
            title="Belum tersedia — akan disambungkan ke customer_status">-
          </div>
        </div>
        <div class="box p-4">
          <div class="font-label">Total Penawaran</div>
          <div class="font-num-display mt-1 !text-primary">{{ totalPenawaran }}</div>
        </div>
      </div>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="customers.length === 0" :colspan="8" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nama perusahaan atau email..."
        loading-text="Memuat data customer..." empty-description="Belum ada customer yang ditambahkan."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th class="w-[20%]">Nama Customer</Table.Th>
          <Table.Th class="w-[30%]">Alamat</Table.Th>
          <Table.Th>Kontak</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">LCR</Table.Th>
          <Table.Th class="text-center">Quotations</Table.Th>
          <Table.Th class="text-center w-[240px]">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in customers" :key="row.id_customer" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <div class="font-strong">{{ row.company_name || '-' }}</div>
              <div class="font-caption mt-0.5">{{ row.email || '-' }}</div>
            </Table.Td>
            <Table.Td>
              <div class="font-body">{{ row.village ? row.village + ', ' : '' }}{{ row.district ? row.district + ', '
                : '' }}{{ row.regency }}</div>
              <div class="font-body">{{ row.province + ', ' + row.postal_code }}</div>
            </Table.Td>
            <Table.Td>
              <div class="font-body">{{ row.phone || '-' }}</div>
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                :class="getVerificationBadgeClass(row.verification_badge)">
                {{ getVerificationBadgeLabel(row) }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <Lucide v-if="row.has_lcr" icon="CheckCircle" class="mx-auto h-5 w-5 text-emerald-600" />
              <Lucide v-else icon="XCircle" class="mx-auto h-5 w-5 text-slate-300" />
            </Table.Td>
            <Table.Td class="font-num text-center">
              {{ row.quotation_count ?? 0 }}
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <ExtendableButton variant="soft-dark" rounded label="Detail" @click="openReview(row.id_customer)">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </ExtendableButton>
                <ExtendableButton v-if="canManageRow(row)" variant="soft-danger" rounded label="Hapus"
                  @click="confirmDelete(row.id_customer)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </ExtendableButton>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" title="Hapus Customer" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
