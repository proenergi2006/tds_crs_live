<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue'
import TerminalFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const terminalApi = createResourceApi('/terminals')
const { success, error } = useNotification()

/* State: data & pagination */
const terminals = ref<any[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedTerminal = ref<any | null>(null)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await terminalApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    terminals.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
    totalRecords.value = data.total
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data terminal')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

/* Form */
function openCreate() {
  formMode.value = 'create'
  selectedTerminal.value = null
  formModal.value = true
}

function openEdit(target: any) {
  formMode.value = 'edit'
  selectedTerminal.value = target
  formModal.value = true
}

function handleFormSuccess(data: any, mode: 'create' | 'edit') {
  syncTerminal(data, mode)
  formModal.value = false
}

function syncTerminal(data: any, mode: 'create' | 'edit') {
  if (mode === 'create') {
    terminals.value.unshift(data)
    return
  }

  const index = terminals.value.findIndex(
    item => item.id_terminal === data.id_terminal,
  )

  if (index !== -1) {
    terminals.value[index] = data
  }
}

/* Delete */
function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await terminalApi.destroy(deleteTarget.value)

    terminals.value = terminals.value.filter(
      item => item.id_terminal !== deleteTarget.value,
    )

    deleteModal.value = false
    success('Berhasil', 'Terminal berhasil dihapus.')
  } catch (e: any) {
    error(
      'Gagal menghapus',
      e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.',
    )
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <div class="col-span-12 intro-y">
      <PageHeader title="Master Terminal" description="Kelola data terminal">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari terminal..."
        @page-change="goToPage" />

      <DataList :loading="loading" :empty="terminals.length === 0" :colspan="8" :show-footer="true"
        :total="totalRecords" :current-page="currentPage" :per-page="perPage" loading-text="Memuat data terminal..."
        empty-description="Belum ada terminal untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Terminal</Table.Th>
          <Table.Th>Cabang</Table.Th>
          <Table.Th>Kategori</Table.Th>
          <Table.Th>Inisial</Table.Th>
          <Table.Th>Lokasi</Table.Th>
          <Table.Th>Telepon</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in terminals" :key="item.id_terminal" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <span class="font-medium">{{ item.nama_terminal }}</span>
              <p v-if="item.alamat" class="text-sm text-slate-500">{{ item.alamat }}</p>
            </Table.Td>
            <Table.Td>
              {{ item.cabang?.nama_cabang || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.kategori_terminal || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.inisial || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.lokasi || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.telp_terminal || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                  @click.prevent="openEdit(item)" title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                  @click="confirmDelete(item.id_terminal)" title="Hapus">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <TerminalFormModal :open="formModal" :mode="formMode" :item="selectedTerminal" @close="formModal = false"
        @success="handleFormSuccess" />

      <DeleteRecordDialog :open="deleteModal" title="Hapus Terminal" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
