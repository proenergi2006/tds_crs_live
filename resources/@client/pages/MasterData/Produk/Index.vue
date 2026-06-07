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

const produkApi = createResourceApi('/produks')
const { success, error } = useNotification()

/* State: data & pagination */
const produks = ref<any[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedProduk = ref<any | null>(null)

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
    const { data } = await produkApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    produks.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
    totalRecords.value = data.total
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
    console.error('Gagal memuat data produk:', e)
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
  selectedProduk.value = null
  formModal.value = true
}

function openEdit(target: any) {
  formMode.value = 'edit'
  selectedProduk.value = target
  formModal.value = true
}

function handleFormSuccess(data: any, mode: 'create' | 'edit') {
  syncProduk(data, mode)
  formModal.value = false
}

function syncProduk(data: any, mode: 'create' | 'edit') {
  if (mode === 'create') {
    produks.value.unshift(data)
    return
  }

  const index = produks.value.findIndex(
    item => item.id_produk === data.id_produk,
  )

  if (index !== -1) {
    produks.value[index] = data
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
    await produkApi.destroy(deleteTarget.value)

    produks.value = produks.value.filter(
      item => item.id_produk !== deleteTarget.value,
    )

    deleteModal.value = false
    success('Berhasil', 'Produk berhasil dihapus.')
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
      <PageHeader title="Master Produk" description="Kelola data produk">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Toolbar: Search, Filter, Pagination -->
      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari produk..."
        @page-change="goToPage" />

      <!-- Data Table List -->
      <DataList :loading="loading" :empty="produks.length === 0" :colspan="7" :show-footer="true" :total="totalRecords"
        :current-page="currentPage" :per-page="perPage" loading-text="Memuat data produk..."
        empty-description="Belum ada produk untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Produk</Table.Th>
          <Table.Th>Merk Dagang</Table.Th>
          <Table.Th>Ukuran</Table.Th>
          <Table.Th>Jenis</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in produks" :key="item.id_produk" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <span class="font-medium">{{ item.nama_produk }}</span>
              <p v-if="item.deskripsi" class="text-sm text-slate-500">{{ item.deskripsi }}</p>
            </Table.Td>
            <Table.Td>
              {{ item.merk_dagang || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.ukuran?.nama_ukuran || '-' }}
            </Table.Td>
            <Table.Td>
              {{ item.jenis?.nama || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-9 !w-9 !p-0 !shadow-none" @click.prevent="openEdit(item)"
                  title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                  @click="confirmDelete(item.id_produk)" title="Hapus">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <FormModal :open="formModal" :mode="formMode" :item="selectedProduk" @close="formModal = false"
        @success="handleFormSuccess" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Produk" :loading="deleteLoading" @close="deleteModal = false"
        @confirm="submitDelete" />
    </div>
  </div>
</template>
