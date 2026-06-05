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
import FormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const produkApi = createResourceApi('/produks')
const ukuranApi = createResourceApi('/ukurans')
const jenisApi = createResourceApi('/jenis-produks')
const { success, error } = useNotification()

/* State: data & pagination */
const produks = ref<any[]>([])
const ukurans = ref<any[]>([])
const jenisProduks = ref<any[]>([])
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
  id_produk: 0,
  nama_produk: '',
  merk_dagang: '',
  deskripsi: '',
  id_ukuran: '',
  id_jenis: '',
  is_active: true,
  created_by: '',
  lastupdate_by: ''
})

const fieldErrors = reactive({
  nama_produk: '',
  merk_dagang: '',
  id_ukuran: '',
  id_jenis: '',
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
  fetchUkurans()
  fetchJenisProduks()
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

async function fetchUkurans() {
  try {
    const { data } = await ukuranApi.getAll({ per_page: 100 })
    ukurans.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
    console.error('Gagal memuat data ukuran:', e)
  }
}

async function fetchJenisProduks() {
  try {
    const { data } = await jenisApi.getAll({ per_page: 100 })
    jenisProduks.value = data.data || data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
    console.error('Gagal memuat data jenis produk:', e)
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
    id_produk: 0,
    nama_produk: '',
    merk_dagang: '',
    deskripsi: '',
    id_ukuran: '',
    id_jenis: '',
    is_active: true,
    created_by: currentUserName.value,
    lastupdate_by: '',
  })
  openFormModal()
}

function openEdit(target: any) {
  formMode.value = 'edit'
  setForm({
    id_produk: target.id_produk,
    nama_produk: target.nama_produk,
    merk_dagang: target.merk_dagang,
    deskripsi: target.deskripsi,
    id_ukuran: target.id_ukuran,
    id_jenis: target.id_jenis,
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
    nama_produk: '',
    merk_dagang: '',
    id_ukuran: '',
    id_jenis: '',
  })
}

function validateForm() {
  resetFormErrors()

  if (!form.nama_produk.trim()) {
    fieldErrors.nama_produk = 'Nama Produk wajib diisi'
  }

  if (!form.merk_dagang.trim()) {
    fieldErrors.merk_dagang = 'Merk Dagang wajib diisi'
  }

  if (!form.id_ukuran) {
    fieldErrors.id_ukuran = 'Ukuran wajib dipilih'
  }

  if (!form.id_jenis) {
    fieldErrors.id_jenis = 'Jenis Produk wajib dipilih'
  }

  return !fieldErrors.nama_produk && !fieldErrors.merk_dagang && !fieldErrors.id_ukuran && !fieldErrors.id_jenis
}

function getFormPayload() {
  return {
    nama_produk: form.nama_produk,
    merk_dagang: form.merk_dagang,
    deskripsi: form.deskripsi,
    id_ukuran: form.id_ukuran,
    id_jenis: form.id_jenis,
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
        ? await produkApi.store(getFormPayload())
        : await produkApi.update(form.id_produk, getFormPayload())

    syncProduk(response.data)
    formModal.value = false

    success(
      'Berhasil',
      formMode.value === 'create'
        ? 'Produk berhasil ditambahkan'
        : 'Produk berhasil diperbarui',
    )
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan')
  } finally {
    formLoading.value = false
  }
}

function syncProduk(data: any) {
  if (formMode.value === 'create') {
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
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <!-- Page Header -->
      <PageHeader title="Master Produk" description="Kelola data produk">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" @click="openCreate">
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
          <Table.Tr v-for="(u, idx) in produks" :key="u.id_produk" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <span class="font-medium">{{ u.nama_produk }}</span>
              <p v-if="u.deskripsi" class="text-sm text-slate-500">{{ u.deskripsi }}</p>
            </Table.Td>
            <Table.Td>
              {{ u.merk_dagang || '-' }}
            </Table.Td>
            <Table.Td>
              {{ u.ukuran?.nama_ukuran || '-' }}
            </Table.Td>
            <Table.Td>
              {{ u.jenis?.nama || '-' }}
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
                  @click="confirmDelete(u.id_produk)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <FormModal :open="formModal" :mode="formMode" :form="form" :ukurans="ukurans" :jenisProduks="jenisProduks"
        :loading="formLoading" :error="formError" :field-errors="fieldErrors" @close="formModal = false"
        @submit="submitForm" />

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Produk" :loading="deleteLoading" @close="deleteModal = false"
        @confirm="submitDelete" />
    </div>
  </div>
</template>
