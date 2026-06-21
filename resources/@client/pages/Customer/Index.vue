<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'

const customerApi = createResourceApi('/customers')
const { success, error } = useNotification()
const router = useRouter()

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
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? 0
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

function openEdit(id: number) {
  router.push({ name: 'customers-edit', params: { id } })
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

function getStatusLabel(status?: number) {
  if (status === 1) return 'Prospect'
  if (status === 2) return 'Tetap'
  return '-'
}

function getStatusClass(status?: number) {
  if (status === 1) return 'bg-amber-100 text-amber-700'
  if (status === 2) return 'bg-emerald-100 text-emerald-700'
  return 'bg-slate-100 text-slate-500'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Master Customers" description="Kelola data customer yang kamu tangani">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Customer
          </Button>
        </template>
      </PageHeader>

      <DataList
        v-model:search="searchQuery"
        v-model:per-page="perPage"
        :loading="loading"
        :empty="customers.length === 0"
        :colspan="7"
        :show-footer="true"
        :show-toolbar="true"
        :total="totalRecords"
        :current-page="currentPage"
        :total-pages="totalPages"
        search-placeholder="Cari nama perusahaan atau email..."
        loading-text="Memuat data customer..."
        empty-description="Belum ada customer yang ditambahkan."
        @page-change="goToPage"
      >
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Customer</Table.Th>
          <Table.Th>Alamat</Table.Th>
          <Table.Th>Kontak</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">LCR</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr
            v-for="(item, idx) in customers"
            :key="item.id_customer"
            class="transition hover:bg-slate-50"
          >
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <div class="font-medium text-slate-800">{{ item.nama_perusahaan || '-' }}</div>
              <div class="mt-0.5 text-xs text-slate-500">{{ item.user?.name || '-' }}</div>
            </Table.Td>
            <Table.Td>
              <div class="text-slate-700">{{ item.alamat_perusahaan || '-' }}</div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ item.cabang?.nama_cabang || '-' }}
              </div>
            </Table.Td>
            <Table.Td>
              <div class="text-slate-700">{{ item.telepon || '-' }}</div>
              <div class="mt-0.5 text-xs text-slate-500">Fax: {{ item.fax || '-' }}</div>
            </Table.Td>
            <Table.Td class="text-center">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="getStatusClass(item.status_customer)"
              >
                {{ getStatusLabel(item.status_customer) }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <Lucide
                v-if="item.has_lcr"
                icon="CheckCircle"
                class="mx-auto h-5 w-5 text-emerald-600"
              />
              <Lucide v-else icon="XCircle" class="mx-auto h-5 w-5 text-slate-300" />
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button
                  variant="soft-pending"
                  rounded
                  class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Edit"
                  @click="openEdit(item.id_customer)"
                >
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button
                  variant="soft-danger"
                  rounded
                  class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Hapus"
                  @click="confirmDelete(item.id_customer)"
                >
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog
        :open="deleteModal"
        title="Hapus Customer"
        :loading="deleteLoading"
        @close="deleteModal = false"
        @confirm="submitDelete"
      />
    </div>
  </div>
</template>
