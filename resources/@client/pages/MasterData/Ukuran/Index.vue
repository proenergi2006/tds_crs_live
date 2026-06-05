<script setup lang="ts">
import { reactive, ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue'
import UkuranFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const ukuranApi = createResourceApi('/ukurans')
const satuanApi = createResourceApi('/satuans')
const { success, error } = useNotification()

/* State: data & pagination */
const ukurans = ref<any[]>([])
const satuans = ref<any[]>([])
const currentUserName = ref('')

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const formLoading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id_ukuran: 0,
  nama_ukuran: '',
  id_satuan: '',
  created_by: '',
  lastupdate_by: '',
})

const fieldErrors = reactive({
  nama_ukuran: '',
  id_satuan: '',
})

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
  initDropdowns()
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

/* Data */
async function initDropdowns() {
  try {
    const { data: user } = await axios.get('/api/user')
    currentUserName.value = user.name
  } catch { }

  try {
    const { data } = await satuanApi.getAll({ per_page: 100 })
    satuans.value = data.data || data
  } catch { }
}

async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await ukuranApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    ukurans.value = data.data
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
  setForm({
    id_ukuran: 0,
    nama_ukuran: '',
    id_satuan: '',
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
  openFormModal()
}

function openEdit(target: any) {
  formMode.value = 'edit'
  setForm({
    id_ukuran: target.id_ukuran,
    nama_ukuran: target.nama_ukuran,
    id_satuan: target.id_satuan,
    created_by: '',
    lastupdate_by: currentUserName.value,
  })
  openFormModal()
}

function openFormModal() {
  resetFormErrors()
  formModal.value = true
}

function setForm(payload: Partial<typeof form>) {
  Object.assign(form, payload)
}

function resetFormErrors() {
  formError.value = null
  Object.assign(fieldErrors, {
    nama_ukuran: '',
    id_satuan: '',
  })
}

function validateForm() {
  resetFormErrors()

  if (!form.nama_ukuran.trim()) {
    fieldErrors.nama_ukuran = 'Nama Ukuran wajib diisi'
  }

  if (!form.id_satuan) {
    fieldErrors.id_satuan = 'Satuan wajib dipilih'
  }

  return !fieldErrors.nama_ukuran && !fieldErrors.id_satuan
}

function getFormPayload() {
  return {
    nama_ukuran: form.nama_ukuran,
    id_satuan: form.id_satuan,
    ...(formMode.value === 'create'
      ? { created_by: form.created_by }
      : { lastupdate_by: form.lastupdate_by }),
  }
}

async function submitForm() {
  if (!validateForm()) return

  formLoading.value = true

  try {
    const response =
      formMode.value === 'create'
        ? await ukuranApi.store(getFormPayload())
        : await ukuranApi.update(form.id_ukuran, getFormPayload())

    syncUkuran(response.data)
    formModal.value = false

    success(
      'Berhasil',
      formMode.value === 'create'
        ? 'Ukuran berhasil ditambahkan'
        : 'Ukuran berhasil diperbarui',
    )
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan')
  } finally {
    formLoading.value = false
  }
}

function syncUkuran(data: any) {
  if (formMode.value === 'create') {
    ukurans.value.unshift(data)
    return
  }

  const index = ukurans.value.findIndex(
    item => item.id_ukuran === data.id_ukuran,
  )

  if (index !== -1) {
    ukurans.value[index] = data
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
    await ukuranApi.destroy(deleteTarget.value)

    ukurans.value = ukurans.value.filter(
      item => item.id_ukuran !== deleteTarget.value,
    )

    deleteModal.value = false
    success('Berhasil', 'Ukuran berhasil dihapus.')
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
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <!-- Page Header -->
      <PageHeader title="Master Ukuran" description="Kelola data ukuran produk">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Toolbar: Search, Filter, Pagination -->
      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari ukuran..."
        @page-change="goToPage" />

      <!-- Data Table List -->
      <DataList :loading="loading" :empty="ukurans.length === 0" :colspan="4" :show-footer="true" :total="totalRecords"
        :current-page="currentPage" :per-page="perPage" loading-text="Memuat data ukuran..."
        empty-description="Belum ada ukuran untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Ukuran</Table.Th>
          <Table.Th>Satuan</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(u, idx) in ukurans" :key="u.id_ukuran" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td>
              {{ u.nama_ukuran }}
            </Table.Td>
            <Table.Td class="text-slate-600">
              {{ u.satuan?.nama_satuan || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-9 !w-9 !p-0 !shadow-none" @click.prevent="openEdit(u)">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                  @click="confirmDelete(u.id_ukuran)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <UkuranFormModal :open="formModal" :mode="formMode" :form="form" :satuans="satuans" :loading="formLoading"
        :error="formError" :field-errors="fieldErrors" @close="formModal = false" @submit="submitForm" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Ukuran" :loading="deleteLoading" @close="deleteModal = false"
        @confirm="submitDelete" />
    </div>
  </div>
</template>
