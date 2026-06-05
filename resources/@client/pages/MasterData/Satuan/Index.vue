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
import SatuanFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const satuanApi = createResourceApi('/satuans')
const { success, error } = useNotification()

/* State: data & pagination */
const satuans = ref<any[]>([])
const currentUserName = ref('')

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(1);
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const formLoading = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  id_satuan: 0,
  nama_satuan: '',
  deskripsi: '',
  is_active: true,
  created_by: '',
  lastupdate_by: ''
})

const fieldErrors = reactive({
  nama_satuan: '',
  deskripsi: '',
})

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
  axios.get('/api/user')
    .then(r => currentUserName.value = r.data.name)
    .catch(() => { });
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

/* Data */
async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await satuanApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    satuans.value = data.data
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
    id_satuan: 0,
    nama_satuan: '',
    deskripsi: '',
    is_active: true,
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
  openFormModal()
}

function openEdit(target: any) {
  formMode.value = 'edit'
  setForm({
    id_satuan: target.id_satuan,
    nama_satuan: target.nama_satuan,
    deskripsi: target.deskripsi,
    is_active: target.is_active,
    created_by: target.created_by,
    lastupdate_by: target.lastupdate_by,
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
    nama_satuan: '',
    deskripsi: '',
  })
}

function validateForm() {
  resetFormErrors()

  if (!form.nama_satuan.trim()) {
    fieldErrors.nama_satuan = 'Nama Satuan wajib diisi'
  }

  return !fieldErrors.nama_satuan && !fieldErrors.deskripsi
}

function getFormPayload() {
  return {
    nama_satuan: form.nama_satuan,
    deskripsi: form.deskripsi,
    is_active: form.is_active,
    ...(formMode.value === 'create'
      ? { created_by: form.created_by }
      : { lastupdate_by: currentUserName.value }),
  }
}

async function submitForm() {
  if (!validateForm()) return

  formLoading.value = true

  try {
    const response =
      formMode.value === 'create'
        ? await satuanApi.store(getFormPayload())
        : await satuanApi.update(form.id_satuan, getFormPayload())

    syncSatuan(response.data)
    formModal.value = false

    success(
      'Berhasil',
      formMode.value === 'create'
        ? 'Satuan berhasil ditambahkan'
        : 'Satuan berhasil diperbarui',
    )
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan')
  } finally {
    formLoading.value = false
  }
}

function syncSatuan(data: any) {
  if (formMode.value === 'create') {
    satuans.value.unshift(data)
    return
  }

  const index = satuans.value.findIndex(
    item => item.id_satuan === data.id_satuan,
  )

  if (index !== -1) {
    satuans.value[index] = data
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
    await satuanApi.destroy(deleteTarget.value)

    satuans.value = satuans.value.filter(
      item => item.id_satuan !== deleteTarget.value,
    )

    deleteModal.value = false
    success('Berhasil', 'Satuan berhasil dihapus.')
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
      <PageHeader title="Master Satuan" description="Kelola data satuan produk">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Toolbar: Search, Filter, Pagination -->
      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari satuan..."
        @page-change="goToPage" />

      <!-- Data Table List -->
      <DataList :loading="loading" :empty="satuans.length === 0" :colspan="5" :show-footer="true" :total="totalRecords"
        :current-page="currentPage" :per-page="perPage" loading-text="Memuat data satuan..."
        empty-description="Belum ada satuan untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Satuan</Table.Th>
          <Table.Th>Deskripsi</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(u, idx) in satuans" :key="u.id_satuan" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              {{ u.nama_satuan }}
            </Table.Td>
            <Table.Td class="text-slate-600">
              {{ u.deskripsi || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="u.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ u.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-9 !w-9 !p-0 !shadow-none" @click.prevent="openEdit(u)">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                  @click="confirmDelete(u.id_satuan)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <SatuanFormModal :open="formModal" :mode="formMode" :form="form" :satuans="satuans" :loading="formLoading"
        :error="formError" :field-errors="fieldErrors" @close="formModal = false" @submit="submitForm" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Satuan" :loading="deleteLoading" @close="deleteModal = false"
        @confirm="submitDelete" />
    </div>
  </div>
</template>
