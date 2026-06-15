<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import { FormSelect } from '@/components/Base/Form'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import FormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const produkApi = createResourceApi('/produks')
const { success, error } = useNotification()

/* State: data & pagination */
const allProduks = ref<any[]>([])

const searchQuery = ref('')
const filterJenis = ref('')
const filterUkuran = ref('')
const perPage = ref(10)
const currentPage = ref(1)
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

watch([searchQuery, filterJenis, filterUkuran], debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

const jenisOptions = computed(() => {
  const options = new Map<string, string>()

  allProduks.value.forEach(item => {
    const id = item.jenis?.id_jenis
    const name = item.jenis?.nama

    if (id && name) {
      options.set(String(id), name)
    }
  })

  return Array.from(options.entries()).map(([id, name]) => ({
    id,
    name,
  }))
})

const ukuranOptions = computed(() => {
  const options = new Map<string, string>()

  allProduks.value.forEach(item => {
    const id = item.ukuran?.id_ukuran
    const name = item.ukuran?.nama_ukuran
    const satuan = item.ukuran?.satuan?.nama_satuan

    if (id && name) {
      options.set(String(id), satuan ? `${name} ${satuan}` : name)
    }
  })

  return Array.from(options.entries()).map(([id, name]) => ({
    id,
    name,
  }))
})

const activeFilterCount = computed(() => {
  return [filterJenis.value, filterUkuran.value].filter(Boolean).length
})

const filteredProduks = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return allProduks.value.filter(item => {
    const matchesJenis =
      !filterJenis.value ||
      String(item.jenis?.id_jenis || '') === filterJenis.value

    const matchesUkuran =
      !filterUkuran.value ||
      String(item.ukuran?.id_ukuran || '') === filterUkuran.value

    const matchesSearch =
      !query ||
      [
        item.nama_produk,
        item.merk_dagang,
        item.deskripsi,
        item.ukuran?.nama_ukuran,
        item.ukuran?.satuan?.nama_satuan,
        item.jenis?.nama,
        item.is_active ? 'active' : 'inactive',
      ].some(value => String(value || '').toLowerCase().includes(query))

    return matchesJenis && matchesUkuran && matchesSearch
  })
})

const totalRecords = computed(() => filteredProduks.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const produks = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredProduks.value.slice(start, start + perPage.value)
})

/* Data */
async function fetchData() {
  loading.value = true

  try {
    const { data } = await produkApi.getAll({
      as_list: true,
    })

    allProduks.value = Array.isArray(data) ? data : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
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

function resetFilters() {
  filterJenis.value = ''
  filterUkuran.value = ''
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
    allProduks.value.unshift(data)
    return
  }

  const index = allProduks.value.findIndex(
    item => item.id_produk === data.id_produk,
  )

  if (index !== -1) {
    allProduks.value[index] = data
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

    allProduks.value = allProduks.value.filter(
      item => item.id_produk !== deleteTarget.value,
    )

    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }

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
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- Page Header -->
      <PageHeader title="Master Produk" description="Kelola data produk">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <!-- Data Table List -->
      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="produks.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" :active-filter-count="activeFilterCount"
        search-placeholder="Cari produk..." loading-text="Memuat data produk..."
        empty-description="Belum ada produk untuk ditampilkan." @page-change="goToPage">
        <template #filters>
          <div class="space-y-4 p-1">
            <div>
              <div class="px-3 pb-2 pt-1 text-xs font-semibold uppercase text-slate-500">
                Jenis
              </div>

              <FormSelect v-model="filterJenis">
                <option value="">Semua Jenis</option>
                <option v-for="jenis in jenisOptions" :key="jenis.id" :value="jenis.id">
                  {{ jenis.name }}
                </option>
              </FormSelect>
            </div>

            <div>
              <div class="px-3 pb-2 text-xs font-semibold uppercase text-slate-500">
                Ukuran
              </div>

              <FormSelect v-model="filterUkuran">
                <option value="">Semua Ukuran</option>
                <option v-for="ukuran in ukuranOptions" :key="ukuran.id" :value="ukuran.id">
                  {{ ukuran.name }}
                </option>
              </FormSelect>
            </div>

            <div class="border-t border-slate-100 pt-3">
              <Button
                type="button"
                variant="outline-secondary"
                class="w-full"
                :disabled="activeFilterCount === 0"
                @click="resetFilters"
              >
                Clear Filter
              </Button>
            </div>
          </div>
        </template>

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
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" @click.prevent="openEdit(item)"
                  title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
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
