<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { debounce } from 'lodash'
import { RouterLink } from 'vue-router'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

type PeriodGroup = {
  key: string
  label: string
  rows: any[]
}

const auth = useAuthStore()
const hargaProdukApi = createResourceApi('/produk-hargas')
const cabangApi = createResourceApi('/cabangs')
const produkApi = createResourceApi('/produks')
const { success, error } = useNotification()

const hargaList = ref<any[]>([])
const cabangs = ref<any[]>([])
const produks = ref<any[]>([])

const searchQuery = ref('')
const filterCabang = ref<string | number>('')
const filterProduk = ref<string | number>('')
const filterPeriodeAwal = ref('')
const filterPeriodeAkhir = ref('')

const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

const currentUser = computed(() => auth.user)
const isRole5 = computed(() => Number(currentUser.value?.id_role) === 5)

const activeFilterCount = computed(() =>
  [
    filterCabang.value,
    filterProduk.value,
    filterPeriodeAwal.value,
    filterPeriodeAkhir.value,
  ].filter(Boolean).length,
)

const groupedHargaList = computed<PeriodGroup[]>(() => {
  const groups = new Map<string, PeriodGroup>()

  hargaList.value.forEach(row => {
    const key = `${row.periode_awal || '-'}__${row.periode_akhir || '-'}`

    if (!groups.has(key)) {
      groups.set(key, {
        key,
        label: periodeText(row),
        rows: [],
      })
    }

    const group = groups.get(key)!
    group.rows.push(row)
  })

  return Array.from(groups.values())
})

onMounted(async () => {
  await Promise.all([
    fetchDropdowns(),
    auth.user ? Promise.resolve() : auth.fetchUser(),
  ])

  await fetchData()
})

watch(
  [searchQuery, filterCabang, filterProduk, filterPeriodeAwal, filterPeriodeAkhir],
  debounce(() => fetchData(1), 350),
)
watch(perPage, () => fetchData(1))

async function fetchDropdowns() {
  try {
    const [cabangRes, produkRes] = await Promise.all([
      cabangApi.getAll({ as_list: true }),
      produkApi.getAll({ as_list: true }),
    ])

    cabangs.value = cabangRes.data.data || cabangRes.data || []
    produks.value = produkRes.data.data || produkRes.data || []
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data pilihan')
  }
}

async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await hargaProdukApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      id_cabang: filterCabang.value || undefined,
      id_produk: filterProduk.value || undefined,
      periode_awal: filterPeriodeAwal.value || undefined,
      periode_akhir: filterPeriodeAkhir.value || undefined,
    })

    hargaList.value = data.data || []
    currentPage.value = data.current_page || 1
    totalPages.value = data.last_page || 1
    totalRecords.value = data.total || 0
  } catch (e: any) {
    hargaList.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data harga produk')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function resetFilter() {
  searchQuery.value = ''
  filterCabang.value = ''
  filterProduk.value = ''
  filterPeriodeAwal.value = ''
  filterPeriodeAkhir.value = ''
  fetchData(1)
}

function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await hargaProdukApi.destroy(deleteTarget.value)
    hargaList.value = hargaList.value.filter(
      item => item.id_produk_harga !== deleteTarget.value,
    )
    deleteModal.value = false
    success('Berhasil', 'Harga produk berhasil dihapus.')
    fetchData(currentPage.value)
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

function formatDate(dateStr: string) {
  if (!dateStr) return '-'

  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function formatNumber(value: number | string | null = 0) {
  if (value === null || value === undefined || value === '') return '-'

  const num = typeof value === 'string' ? Number(value) : value
  return isNaN(num) ? '-' : Number(num).toLocaleString('id-ID')
}

function periodeText(row: any) {
  return `${formatDate(row.periode_awal)} - ${formatDate(row.periode_akhir)}`
}

function produkText(row: any) {
  const namaProduk = row.produk?.nama_produk || '-'
  const ukuran = row.produk?.ukuran?.nama_ukuran || '-'
  const satuan = row.produk?.ukuran?.satuan?.nama_satuan || '-'

  return `${namaProduk} - Uk: ${ukuran} ${satuan}`
}

function rowNumber(row: any) {
  const index = hargaList.value.findIndex(
    item => item.id_produk_harga === row.id_produk_harga,
  )

  return (currentPage.value - 1) * perPage.value + index + 1
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <div class="col-span-12 intro-y">
      <PageHeader title="Master Harga Produk"
        description="Kelola data harga produk, filter berdasarkan cabang, produk, dan periode.">
        <template #action>
          <Button v-if="isRole5" :as="RouterLink" :to="{ name: 'produk-hargas-create' }" variant="white"
            class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Harga
          </Button>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :total-pages="totalPages" :active-filter-count="activeFilterCount" search-placeholder="Cari produk / cabang..."
        @page-change="goToPage">
        <template #filters>
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div>
              <FormLabel for="filter-cabang">Cabang</FormLabel>
              <FormSelect id="filter-cabang" v-model="filterCabang" class="!box">
                <option value="">Semua Cabang</option>
                <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
                  {{ cabang.nama_cabang }}
                </option>
              </FormSelect>
            </div>

            <div>
              <FormLabel for="filter-produk">Produk</FormLabel>
              <FormSelect id="filter-produk" v-model="filterProduk" class="!box">
                <option value="">Semua Produk</option>
                <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
                  {{ produk.nama_produk }} ({{ produk.ukuran?.nama_ukuran }} {{ produk.ukuran?.satuan?.nama_satuan }})
                </option>
              </FormSelect>
            </div>

            <div>
              <FormLabel for="filter-periode-awal">Periode Awal</FormLabel>
              <FormInput id="filter-periode-awal" v-model="filterPeriodeAwal" type="date" class="!box" />
            </div>

            <div>
              <FormLabel for="filter-periode-akhir">Periode Akhir</FormLabel>
              <div class="flex gap-2">
                <FormInput id="filter-periode-akhir" v-model="filterPeriodeAkhir" type="date" class="!box" />
                <Button variant="outline-secondary" class="inline-flex items-center gap-2" @click="resetFilter">
                  <Lucide icon="RotateCcw" class="h-4 w-4" />
                  Reset
                </Button>
              </div>
            </div>
          </div>
        </template>
      </PageToolbar>

      <DataList :loading="loading" :empty="hargaList.length === 0" :colspan="9" :show-footer="true"
        :total="totalRecords" :current-page="currentPage" :per-page="perPage" loading-text="Memuat data harga produk..."
        empty-description="Belum ada harga produk untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-16">No</Table.Th>
          <Table.Th>Cabang</Table.Th>
          <Table.Th>Produk</Table.Th>
          <Table.Th class="text-right">COGS</Table.Th>
          <Table.Th class="text-right">Margin</Table.Th>
          <Table.Th class="text-right">Price List TDS</Table.Th>
          <Table.Th class="text-right">Price List PE</Table.Th>
          <Table.Th>Catatan</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <template v-for="group in groupedHargaList" :key="group.key">
            <Table.Tr class="bg-slate-100/80">
              <Table.Td colspan="9" class="px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-primary shadow-sm">
                    <Lucide icon="CalendarDays" class="h-4 w-4" />
                  </span>
                  <span class="text-sm font-semibold text-slate-800">
                    {{ group.label }}
                  </span>
                  <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-500 shadow-sm">
                    {{ group.rows.length }} data
                  </span>
                </div>
              </Table.Td>
            </Table.Tr>

            <Table.Tr v-for="item in group.rows" :key="item.id_produk_harga" class="transition hover:bg-slate-50">
              <Table.Td class="text-sm font-medium text-slate-700">
                {{ rowNumber(item) }}.
              </Table.Td>

              <Table.Td class="text-sm text-slate-700">
                {{ item.cabang?.nama_cabang || '-' }}
              </Table.Td>

              <Table.Td class="text-sm text-slate-700">
                <div class="max-w-[360px]">
                  {{ produkText(item) }}
                </div>
              </Table.Td>

              <Table.Td class="text-right text-sm text-slate-700">
                {{ formatNumber(item.harga_cogs) }}
              </Table.Td>

              <Table.Td class="text-right text-sm text-slate-700">
                {{ formatNumber(item.harga_margin) }}
              </Table.Td>

              <Table.Td class="text-right text-sm font-medium text-slate-800">
                {{ formatNumber(item.harga_price_list) }}
              </Table.Td>

              <Table.Td class="text-right text-sm font-medium text-slate-800">
                {{ formatNumber(item.harga_price_list_pe) }}
              </Table.Td>

              <Table.Td class="text-sm text-slate-600">
                {{ item.catatan || '-' }}
              </Table.Td>

              <Table.Td class="text-center">
                <div class="inline-flex items-center justify-center gap-2">
                  <Button :as="RouterLink" :to="{ name: 'produk-hargas-detail', params: { id: item.id_produk_harga } }"
                    variant="soft-info" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Detail">
                    <Lucide icon="Eye" class="h-4 w-4" />
                  </Button>

                  <Button :as="RouterLink" :to="{ name: 'produk-hargas-edit', params: { id: item.id_produk_harga } }"
                    variant="soft-warning" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Edit">
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </Button>

                  <Button v-if="isRole5" variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Hapus"
                    @click="confirmDelete(item.id_produk_harga)">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" title="Hapus Harga Produk" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
