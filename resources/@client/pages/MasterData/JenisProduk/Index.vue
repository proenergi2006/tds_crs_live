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
import FormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const jenisProdukApi = createResourceApi('/jenis-produks')
const { success, error } = useNotification()

/* State: data & pagination */
const jenisProduks = ref<any[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedJenisProduk = ref<any | null>(null)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

/* Data */
async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await jenisProdukApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    jenisProduks.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
    totalRecords.value = data.total
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
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
  selectedJenisProduk.value = null
  formModal.value = true
}

function openEdit(target: any) {
  formMode.value = 'edit'
  selectedJenisProduk.value = target
  formModal.value = true
}

function handleFormSuccess(data: any, mode: 'create' | 'edit') {
  syncJenisProduk(data, mode)
  formModal.value = false
}

function syncJenisProduk(data: any, mode: 'create' | 'edit') {
  if (mode === 'create') {
    jenisProduks.value.unshift(data)
    return
  }

  const index = jenisProduks.value.findIndex(
    item => item.id_jenis === data.id_jenis,
  )

  if (index !== -1) {
    jenisProduks.value[index] = data
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
    await jenisProdukApi.destroy(deleteTarget.value)

    jenisProduks.value = jenisProduks.value.filter(
      item => item.id_jenis !== deleteTarget.value,
    )

    deleteModal.value = false
    success('Berhasil', 'Jenis Produk berhasil dihapus.')
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
      <!-- Page Header -->
      <PageHeader title="Master Jenis Produk" description="Kelola data jenis produk">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Toolbar: Search, Filter, Pagination -->
      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari jenis..." @page-change="goToPage" />

      <!-- Data Table List -->
      <DataList :loading="loading" :empty="jenisProduks.length === 0" :colspan="5" :show-footer="true"
        :total="totalRecords" :current-page="currentPage" :per-page="perPage" loading-text="Memuat data jenis..."
        empty-description="Belum ada jenis untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Jenis Produk</Table.Th>
          <Table.Th>Deskripsi</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in jenisProduks" :key="item.id_jenis" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              {{ item.nama }}
            </Table.Td>
            <Table.Td class="text-slate-600">
              {{ item.deskripsi || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" @click.prevent="openEdit(item)"
                  title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="confirmDelete(item.id_jenis)" title="Hapus">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <FormModal :open="formModal" :mode="formMode" :item="selectedJenisProduk" @close="formModal = false"
        @success="handleFormSuccess" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Jenis Produk" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
