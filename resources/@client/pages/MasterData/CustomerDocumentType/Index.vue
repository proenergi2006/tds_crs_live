<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CustomerDocumentTypeFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

type CustomerDocumentTypeRow = {
  id: number
  code: string
  name: string
  is_active: boolean
  requires_number: boolean
}

const customerDocumentTypeApi = createResourceApi('/customer-document-types')
const { success, error } = useNotification()

/* State: data & pagination */
const allDocumentTypes = ref<CustomerDocumentTypeRow[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedDocumentType = ref<CustomerDocumentTypeRow | null>(null)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

const filteredDocumentTypes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allDocumentTypes.value

  return allDocumentTypes.value.filter(item => {
    return [
      item.code,
      item.name,
      item.is_active ? 'active' : 'inactive',
    ].some(value => String(value || '').toLowerCase().includes(query))
  })
})

const totalRecords = computed(() => filteredDocumentTypes.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const documentTypes = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredDocumentTypes.value.slice(start, start + perPage.value)
})

/* Data */
async function fetchData() {
  loading.value = true

  try {
    const { data } = await customerDocumentTypeApi.getAll({
      as_list: true,
    })

    allDocumentTypes.value = Array.isArray(data) ? data : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data jenis dokumen customer')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

/* Form */
function openCreate() {
  formMode.value = 'create'
  selectedDocumentType.value = null
  formModal.value = true
}

function openEdit(target: CustomerDocumentTypeRow) {
  formMode.value = 'edit'
  selectedDocumentType.value = target
  formModal.value = true
}

function handleFormSuccess(data: CustomerDocumentTypeRow, mode: 'create' | 'edit') {
  syncDocumentType(data, mode)
  formModal.value = false
}

function syncDocumentType(data: CustomerDocumentTypeRow, mode: 'create' | 'edit') {
  if (mode === 'create') {
    allDocumentTypes.value.unshift(data)
    return
  }

  const index = allDocumentTypes.value.findIndex(item => item.id === data.id)

  if (index !== -1) {
    allDocumentTypes.value[index] = data
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
    await customerDocumentTypeApi.destroy(deleteTarget.value)

    allDocumentTypes.value = allDocumentTypes.value.filter(
      item => item.id !== deleteTarget.value,
    )

    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }

    deleteModal.value = false
    success('Berhasil', 'Jenis dokumen customer berhasil dihapus.')
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
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- Page Header -->
      <PageHeader title="Jenis Dokumen Customer" description="Kelola master jenis dokumen legal customer (NIB, NPWP, Sertifikat, dst).">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Data Table List -->
      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="documentTypes.length === 0" :colspan="6" :show-footer="true" :show-toolbar="true"
        :total="totalRecords" :current-page="currentPage" :total-pages="totalPages"
        search-placeholder="Cari kode / nama jenis dokumen..." loading-text="Memuat data jenis dokumen..."
        empty-description="Belum ada jenis dokumen untuk ditampilkan." @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Kode</Table.Th>
          <Table.Th>Nama Jenis Dokumen</Table.Th>
          <Table.Th class="text-center">No. Dokumen</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in documentTypes" :key="item.id" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="font-strong">
              {{ item.code }}
            </Table.Td>
            <Table.Td>
              {{ item.name }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1"
                :class="item.requires_number ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'">
                {{ item.requires_number ? 'Wajib' : 'Opsional' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1"
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
                  @click="confirmDelete(item.id)" title="Hapus">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create/Edit Modal -->
      <CustomerDocumentTypeFormModal :open="formModal" :mode="formMode" :item="selectedDocumentType"
        @close="formModal = false" @success="handleFormSuccess" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Jenis Dokumen Customer" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
