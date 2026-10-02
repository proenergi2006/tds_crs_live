<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import UkuranFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const ukuranApi = createResourceApi('/ukurans')
const { success, error } = useNotification()

const allUkurans = ref<any[]>([])

const searchQuery = ref('')
const filterSatuan = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const loading = ref(false)

const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedUkuran = ref<any | null>(null)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
})

watch([searchQuery, filterSatuan], debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

const satuanOptions = computed(() => {
  const options = new Map<string, string>()

  allUkurans.value.forEach(item => {
    const id = item.satuan?.id_satuan
    const name = item.satuan?.nama_satuan

    if (id && name) {
      options.set(String(id), name)
    }
  })

  return Array.from(options.entries()).map(([id, name]) => ({
    id,
    name,
  }))
})

const activeFilterCount = computed(() => {
  return filterSatuan.value ? 1 : 0
})

const filteredUkurans = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return allUkurans.value.filter(item => {
    const matchesSatuan =
      !filterSatuan.value ||
      String(item.satuan?.id_satuan || '') === filterSatuan.value

    const matchesSearch =
      !query ||
      [
        item.nama_ukuran,
        item.satuan?.nama_satuan,
      ].some(value => String(value || '').toLowerCase().includes(query))

    return matchesSatuan && matchesSearch
  })
})

const totalRecords = computed(() => filteredUkurans.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const ukurans = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredUkurans.value.slice(start, start + perPage.value)
})

async function fetchData() {
  loading.value = true

  try {
    const { data } = await ukuranApi.getAll({
      as_list: true,
    })

    allUkurans.value = Array.isArray(data) ? data : []
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

function setFilterSatuan(value: string) {
  filterSatuan.value = value
}

function openCreate() {
  formMode.value = 'create'
  selectedUkuran.value = null
  formModal.value = true
}

function openEdit(target: any) {
  formMode.value = 'edit'
  selectedUkuran.value = target
  formModal.value = true
}

function handleFormSuccess(data: any, mode: 'create' | 'edit') {
  syncUkuran(data, mode)
  formModal.value = false
}

function syncUkuran(data: any, mode: 'create' | 'edit') {
  if (mode === 'create') {
    allUkurans.value.unshift(data)
    return
  }

  const index = allUkurans.value.findIndex(
    item => item.id_ukuran === data.id_ukuran,
  )

  if (index !== -1) {
    allUkurans.value[index] = data
  }
}

function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await ukuranApi.destroy(deleteTarget.value)

    allUkurans.value = allUkurans.value.filter(
      item => item.id_ukuran !== deleteTarget.value,
    )

    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }

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
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Master Ukuran" description="Kelola data ukuran produk">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <div>
        <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
          :empty="ukurans.length === 0" :colspan="4" :show-footer="true" :show-toolbar="true" :total="totalRecords"
          :current-page="currentPage" :total-pages="totalPages" :active-filter-count="activeFilterCount"
          search-placeholder="Cari ukuran..." loading-text="Memuat data ukuran..."
          empty-description="Belum ada ukuran untuk ditampilkan." @page-change="goToPage">
          <template #filters="{ close }">
            <div>
              <div class="px-3 pt-1 pb-2 text-overline">
                Satuan
              </div>

              <div class="space-y-1">
                <button type="button"
                  class="flex justify-between items-center px-3 py-2 rounded-md w-full text-body text-left transition"
                  :class="filterSatuan === '' ? 'bg-primary/10 font-semibold text-primary' : 'text-slate-600 hover:bg-slate-50'"
                  @click="setFilterSatuan(''); close()">
                  Semua Satuan
                  <Lucide v-if="filterSatuan === ''" icon="Check" class="w-4 h-4" />
                </button>

                <button v-for="satuan in satuanOptions" :key="satuan.id" type="button"
                  class="flex justify-between items-center px-3 py-2 rounded-md w-full text-body text-left transition"
                  :class="filterSatuan === satuan.id ? 'bg-primary/10 font-semibold text-primary' : 'text-slate-600 hover:bg-slate-50'"
                  @click="setFilterSatuan(satuan.id); close()">
                  {{ satuan.name }}
                  <Lucide v-if="filterSatuan === satuan.id" icon="Check" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </template>

          <template #head>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>Nama Ukuran</Table.Th>
            <Table.Th>Satuan</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="(item, idx) in ukurans" :key="item.id_ukuran" class="hover:bg-slate-50 transition">
              <Table.Td class="num-sm text-center">
                {{ (currentPage - 1) * perPage + idx + 1 }}.
              </Table.Td>
              <Table.Td>
                {{ item.nama_ukuran }}
              </Table.Td>
              <Table.Td class="text-slate-600">
                {{ item.satuan?.nama_satuan || '-' }}
              </Table.Td>
              <Table.Td class="text-center">
                <div class="inline-flex justify-center items-center gap-2">
                  <Button variant="soft-warning" rounded class="!shadow-none !p-0 !w-8 !h-8"
                    @click.prevent="openEdit(item)" title="Edit">
                    <Lucide icon="Edit" class="w-4 h-4" />
                  </Button>
                  <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                    @click="confirmDelete(item.id_ukuran)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </DataList>
      </div>

      <UkuranFormModal :open="formModal" :mode="formMode" :item="selectedUkuran" @close="formModal = false"
        @success="handleFormSuccess" />

      <DeleteRecordDialog :open="deleteModal" title="Hapus Ukuran" :loading="deleteLoading" @close="deleteModal = false"
        @confirm="submitDelete" />
    </div>
  </div>
</template>
