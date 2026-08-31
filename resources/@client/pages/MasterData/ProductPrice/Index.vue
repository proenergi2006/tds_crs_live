<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'

import { Slideover } from '@/components/Base/Headless'
import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PeriodPricesPanel from './PeriodPricesPanel.vue'
import PeriodFormModal from './PeriodFormModal.vue'
import ProductPriceRowModal from './ProductPriceRowModal.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { createResourceApi } from '@/utils/resourceApi'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

type PeriodeCategory = 'active' | 'upcoming' | 'inactive'

type PeriodeRow = {
  id: number
  start_date: string
  end_date: string
  label: string
  category: PeriodeCategory
  masa_aktif: 'aktif' | 'nonaktif'
  status: 'lengkap' | 'belum_lengkap'
  jumlah_data: number
  jumlah_cabang: number
  jumlah_belum_lengkap: number
  terakhir_diupdate: string | null
}

type PeriodeAttachment = {
  path: string
  original_filename: string
  uploaded_at?: string
}

type ProductPriceRow = {
  id: number
  price_period_id: number
  branch_id: number
  product_id: number
  price_list: string | number | null
  price_list_pe: string | number | null
  bm_price: string | number | null
  cogs_price: string | number | null
  margin_amount: string | number | null
  om_price: string | number | null
  ceo_price: string | number | null
  cogs_basis: string | null
  notes: string | null
  branch?: { id: number; name: string }
  product?: {
    id_produk: number
    nama_produk: string
    ukuran?: { nama_ukuran: string; satuan?: { nama_satuan: string } }
  }
}

const TAB_META: Record<PeriodeCategory, { label: string; title: string; description: string }> = {
  active: {
    label: 'Aktif',
    title: 'Periode Harga Aktif',
    description: 'Daftar harga produk yang berlaku hari ini.',
  },
  upcoming: {
    label: 'Akan Datang',
    title: 'Periode Harga Akan Datang',
    description: 'Daftar harga produk untuk periode yang akan mulai berlaku.',
  },
  inactive: {
    label: 'Nonaktif',
    title: 'Periode Harga Nonaktif',
    description: 'Riwayat periode harga yang masa berlakunya sudah berakhir.',
  },
}

const TABS = (Object.keys(TAB_META) as PeriodeCategory[]).map(value => ({
  value,
  label: TAB_META[value].label,
}))

const auth = useAuthStore()
const periodeApi = createResourceApi('/price-periods')
const hargaProdukApi = createResourceApi('/product-prices')
const cabangApi = createResourceApi('/cabangs')
const produkApi = createResourceApi('/produks')
const { success, error } = useNotification()

const periodeList = ref<PeriodeRow[]>([])
const periodeLoading = ref(false)
const periodeSearch = ref('')
const periodeCurrentPage = ref(1)
const periodePerPage = ref(10)

const slideoverOpen = ref(false)
const selectedPeriode = ref<PeriodeRow | null>(null)
const periodeNotes = ref<string | null>(null)
const periodeAttachments = ref<PeriodeAttachment[]>([])
const periodeDetailLoading = ref(false)

const pricesRows = ref<ProductPriceRow[]>([])
const pricesLoading = ref(false)

const cabangs = ref<any[]>([])
const produks = ref<any[]>([])

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

const periodModalOpen = ref(false)
const periodModalMode = ref<'create' | 'edit'>('create')
const rowModalOpen = ref(false)
const rowModalMode = ref<'create' | 'edit'>('create')
const editingRow = ref<ProductPriceRow | null>(null)

const canManagePeriode = computed(() => auth.can('price-period.manage'))
const canCreatePrice = computed(() => auth.can('product-price.manage'))
const canVerifyPrice = computed(() => auth.can('product-price.verify'))
const canEditRow = computed(() => canCreatePrice.value || canVerifyPrice.value)

const tableView = computed<'procurement' | 'ceo'>(() =>
  canVerifyPrice.value && !canCreatePrice.value ? 'ceo' : 'procurement',
)

const notInactive = computed(() => selectedPeriode.value?.category !== 'inactive')

const incompleteNotice = computed(() => {
  if (!canVerifyPrice.value) return null
  const p = selectedPeriode.value
  if (!p || p.category === 'inactive') return null
  if (p.status !== 'belum_lengkap' || p.jumlah_belum_lengkap <= 0) return null
  return { count: p.jumlah_belum_lengkap, total: p.jumlah_data }
})
const canEditRowNow = computed(() => canEditRow.value && notInactive.value)
const canDeleteRowNow = computed(() => canCreatePrice.value && notInactive.value)
const canEditPeriodeNow = computed(() => canManagePeriode.value && notInactive.value)

const activeTab = ref<PeriodeCategory>('active')
const activeCategoryMeta = computed(() => TAB_META[activeTab.value])

const periodeByCategory = computed<Record<PeriodeCategory, PeriodeRow[]>>(() => ({
  active: periodeList.value.filter(r => r.category === 'active'),
  upcoming: periodeList.value.filter(r => r.category === 'upcoming'),
  inactive: periodeList.value.filter(r => r.category === 'inactive'),
}))

const isSinglePeriodTab = computed(() => activeTab.value !== 'inactive')

const focusPeriode = computed<PeriodeRow | null>(() =>
  isSinglePeriodTab.value ? (periodeByCategory.value[activeTab.value][0] ?? null) : null,
)

const addButtonLabel = computed(() =>
  isSinglePeriodTab.value && focusPeriode.value ? 'Tambah Harga' : 'Buat Periode Harga',
)

const emptyStateText = computed(() =>
  activeTab.value === 'upcoming'
    ? {
      title: 'Belum ada periode harga akan datang',
      desc: 'Belum ada periode yang dijadwalkan mulai berlaku setelah hari ini.',
    }
    : {
      title: 'Belum ada periode harga aktif',
      desc: 'Tidak ada periode yang rentang tanggalnya mencakup hari ini.',
    },
)

const filteredPeriode = computed(() => {
  const base = periodeByCategory.value[activeTab.value]
  const q = periodeSearch.value.trim().toLowerCase()
  if (!q) return base
  return base.filter(r => r.label.toLowerCase().includes(q))
})

const periodeTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredPeriode.value.length / periodePerPage.value)),
)

const paginatedPeriode = computed(() => {
  const start = (periodeCurrentPage.value - 1) * periodePerPage.value
  return filteredPeriode.value.slice(start, start + periodePerPage.value)
})

onMounted(async () => {
  await Promise.all([
    fetchDropdowns(),
    auth.user ? Promise.resolve() : auth.fetchUser(),
  ])
  await fetchPeriode()
})

watch(periodeSearch, () => { periodeCurrentPage.value = 1 })
watch(activeTab, () => { periodeCurrentPage.value = 1 })

watch([activeTab, focusPeriode], () => {
  if (!isSinglePeriodTab.value) return

  slideoverOpen.value = false

  const target = focusPeriode.value
  if (!target) {
    selectedPeriode.value = null
    pricesRows.value = []
    periodeNotes.value = null
    periodeAttachments.value = []
    return
  }

  // skip refetch periode yang sama, rate limit api 60/menit gampang kena pas pindah-pindah tab
  if (selectedPeriode.value?.id === target.id) return

  selectedPeriode.value = target
  fetchProductPrices()
  fetchPeriodeDetail()
})

async function fetchDropdowns() {
  try {
    const [cabangRes, produkRes] = await Promise.all([
      cabangApi.getAll({ as_list: true }),
      produkApi.getAll({ as_list: true }),
    ])
    cabangs.value = cabangRes.data.data ?? cabangRes.data ?? []
    produks.value = produkRes.data.data ?? produkRes.data ?? []
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data pilihan')
  }
}

async function fetchPeriode() {
  periodeLoading.value = true
  try {
    const { data } = await periodeApi.getAll()
    periodeList.value = data.data ?? data ?? []
  } catch (e: any) {
    periodeList.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data periode')
  } finally {
    periodeLoading.value = false
  }
}

async function fetchProductPrices() {
  if (!selectedPeriode.value) return

  pricesLoading.value = true
  try {
    const { data } = await hargaProdukApi.getAll({
      as_list: true,
      price_period_id: selectedPeriode.value.id,
    })
    pricesRows.value = data.data ?? data ?? []
  } catch (e: any) {
    pricesRows.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data harga')
  } finally {
    pricesLoading.value = false
  }
}

async function fetchPeriodeDetail() {
  if (!selectedPeriode.value) return

  periodeDetailLoading.value = true
  try {
    const { data } = await periodeApi.getById(selectedPeriode.value.id)
    const item = data.data ?? data
    periodeNotes.value = item.notes ?? null
    periodeAttachments.value = item.attachments ?? []
  } catch (e: any) {
    periodeNotes.value = null
    periodeAttachments.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat detail periode')
  } finally {
    periodeDetailLoading.value = false
  }
}

function openSlideover(row: PeriodeRow) {
  selectedPeriode.value = row
  periodeNotes.value = null
  periodeAttachments.value = []
  pricesRows.value = []
  slideoverOpen.value = true
  fetchProductPrices()
  fetchPeriodeDetail()
}

function closeSlideover() {
  slideoverOpen.value = false
}

function handleAddClick() {
  if (isSinglePeriodTab.value && focusPeriode.value && canCreatePrice.value) {
    editingRow.value = null
    rowModalMode.value = 'create'
    rowModalOpen.value = true
    return
  }

  if (canManagePeriode.value) {
    openCreatePeriodModal()
  }
}

function handleEditRow(id: number) {
  if (!canEditRowNow.value) return

  const row = pricesRows.value.find(r => r.id === id)
  if (!row) return

  editingRow.value = row
  rowModalMode.value = 'edit'
  rowModalOpen.value = true
}

function openCreatePeriodModal() {
  periodModalMode.value = 'create'
  periodModalOpen.value = true
}

function openEditPeriodModal() {
  if (!selectedPeriode.value) return
  periodModalMode.value = 'edit'
  periodModalOpen.value = true
}

function closePeriodModal() {
  periodModalOpen.value = false
}

async function handlePeriodSaved(savedId: number) {
  await fetchPeriode()
  const updated = periodeList.value.find(p => p.id === savedId)
  if (!updated) return

  activeTab.value = updated.category

  // watcher bakal skip refresh (id sama), jadi panggil manual di sini
  if (selectedPeriode.value?.id === savedId) {
    selectedPeriode.value = updated
    fetchProductPrices()
    fetchPeriodeDetail()
  }
}

async function handleRowSaved() {
  rowModalOpen.value = false
  await fetchPeriode()

  if (selectedPeriode.value) {
    const fresh = periodeList.value.find(p => p.id === selectedPeriode.value?.id) ?? null
    selectedPeriode.value = fresh
    if (fresh) {
      fetchProductPrices()
      fetchPeriodeDetail()
    }
  }
}

function confirmDelete(id: number) {
  if (!canDeleteRowNow.value) return
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true
  try {
    await hargaProdukApi.destroy(deleteTarget.value)
    pricesRows.value = pricesRows.value.filter(item => item.id !== deleteTarget.value)
    deleteModal.value = false
    success('Berhasil', 'Harga produk berhasil dihapus.')
    await fetchPeriode()
  } catch (e: any) {
    error('Gagal menghapus', e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

function formatDateTime(dateStr: string | null) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">

      <PageHeader :title="activeCategoryMeta.title" :description="activeCategoryMeta.description">
        <template #action>
          <Button v-if="isSinglePeriodTab && ((focusPeriode && canCreatePrice) || (!focusPeriode && canManagePeriode))"
            variant="white" class="inline-flex items-center gap-2" @click="handleAddClick">
            <Lucide icon="Plus" class="w-4 h-4" />
            {{ addButtonLabel }}
          </Button>
        </template>

        <template #body>
          <div class="inline-flex gap-1 bg-white/10 backdrop-blur-sm p-1 border border-white/20 rounded-lg w-fit">
            <button v-for="tab in TABS" :key="tab.value" type="button"
              class="px-3 py-1.5 rounded-md font-medium text-sm transition"
              :class="activeTab === tab.value ? 'bg-white text-theme-1 shadow-sm' : 'text-white/80 hover:bg-white/10'"
              @click="activeTab = tab.value">
              {{ tab.label }}
            </button>
          </div>
        </template>
      </PageHeader>
      <template v-if="isSinglePeriodTab">
        <div v-if="!focusPeriode" class="flex flex-col items-center gap-2 px-6 py-12 text-center box">
          <span class="inline-flex justify-center items-center bg-slate-100 rounded-full w-12 h-12 text-slate-400">
            <Lucide icon="CalendarDays" class="w-6 h-6" />
          </span>
          <p class="font-strong">{{ emptyStateText.title }}</p>
          <p class="font-body text-slate-500">
            {{ emptyStateText.desc }}
            <template v-if="canManagePeriode"> Buat periode baru untuk mulai mengisi harga.</template>
          </p>
        </div>

        <template v-else>
          <div class="flex flex-col gap-3 p-4 box">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-header">{{ focusPeriode.label }}</span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label whitespace-nowrap"
                :class="focusPeriode.category === 'active' ? 'bg-success/10 text-success' : 'bg-blue-50 text-blue-600'">
                {{ TAB_META[focusPeriode.category].label }}
              </span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label whitespace-nowrap" :class="focusPeriode.status === 'lengkap'
                ? 'bg-success/10 text-success'
                : 'bg-amber-100 text-amber-600'">
                {{ focusPeriode.status === 'lengkap' ? 'Lengkap' : 'Belum Lengkap' }}
              </span>
              <button v-if="canEditPeriodeNow" type="button" title="Edit Periode"
                class="hover:bg-slate-100 ml-1 p-1 rounded-full text-slate-400 hover:text-slate-600 transition"
                @click="openEditPeriodModal">
                <Lucide icon="Pencil" class="w-4 h-4" />
              </button>
            </div>

            <div class="gap-3 grid grid-cols-1 sm:grid-cols-2">
              <div class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-1.5 mb-1 font-section text-slate-500">
                  <Lucide icon="StickyNote" class="w-4 h-4" />
                  Catatan
                </div>
                <p class="font-body text-slate-700">
                  {{ periodeDetailLoading ? 'Memuat...' : (periodeNotes || '-') }}
                </p>
              </div>
              <div class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-1.5 mb-1 font-section text-slate-500">
                  <Lucide icon="Paperclip" class="w-4 h-4" />
                  Lampiran
                </div>
                <div v-if="periodeDetailLoading" class="font-body text-slate-400">Memuat...</div>
                <div v-else-if="periodeAttachments.length === 0" class="font-body text-slate-400">
                  Belum ada lampiran
                </div>
                <ul v-else class="space-y-1">
                  <li v-for="(att, idx) in periodeAttachments" :key="idx">
                    <a :href="`/storage/${att.path}`" target="_blank" rel="noopener"
                      class="flex items-center gap-1.5 font-body text-blue-600 hover:underline">
                      <Lucide icon="FileText" class="flex-shrink-0 w-3.5 h-3.5" />
                      <span class="flex-1 min-w-0 truncate">{{ att.original_filename }}</span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div v-if="incompleteNotice"
            class="flex items-start gap-2 bg-amber-50 px-4 py-3 border border-amber-200 rounded-lg font-body text-amber-700">
            <Lucide icon="AlertTriangle" class="flex-shrink-0 mt-0.5 w-4 h-4" />
            <span>
              <span class="font-strong">{{ incompleteNotice.count }} dari {{ incompleteNotice.total }} harga
                produk</span>
              di periode ini belum lengkap — nilai yang ditandai merah (Margin, Price List, Approval BM/OM/CEO) masih
              perlu diisi agar periode siap dipakai.
            </span>
          </div>

          <PeriodPricesPanel :rows="pricesRows" :loading="pricesLoading" :cabangs="cabangs" :produks="produks"
            :view="tableView" :can-edit="canEditRowNow" :can-delete="canDeleteRowNow" @edit="handleEditRow"
            @delete="confirmDelete" />
        </template>
      </template>
      <DataList v-else v-model:search="periodeSearch" v-model:per-page="periodePerPage" :loading="periodeLoading"
        :empty="filteredPeriode.length === 0" :colspan="6" :show-footer="true" :show-toolbar="true"
        :total="filteredPeriode.length" :current-page="periodeCurrentPage" :total-pages="periodeTotalPages"
        search-placeholder="Cari periode..." loading-text="Memuat data periode..."
        :empty-description="`Tidak ada periode pada kategori ${activeCategoryMeta.label}.`"
        @page-change="(p) => { periodeCurrentPage = p }">
        <template #head>
          <Table.Th>Periode</Table.Th>
          <Table.Th class="w-44">Status</Table.Th>
          <Table.Th class="w-24 text-right">Jumlah Cabang</Table.Th>
          <Table.Th class="w-24 text-right">Jumlah Data</Table.Th>
          <Table.Th class="w-40">Diupdate</Table.Th>
          <Table.Th class="w-24 text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="row in paginatedPeriode" :key="row.id" class="hover:bg-slate-50 transition cursor-pointer"
            @click="openSlideover(row)">
            <Table.Td>
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex flex-shrink-0 justify-center items-center bg-slate-100 rounded-lg w-8 h-8 text-slate-500">
                  <Lucide icon="CalendarDays" class="w-4 h-4" />
                </span>
                <span class="font-strong">{{ row.label }}</span>
              </div>
            </Table.Td>

            <Table.Td>
              <div class="flex justify-between items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label whitespace-nowrap" :class="row.status === 'lengkap'
                  ? 'bg-success/10 text-success'
                  : 'bg-amber-100 text-amber-600'">
                  {{ row.status === 'lengkap' ? 'Lengkap' : 'Belum Lengkap' }}
                </span>
                <span v-if="row.status === 'belum_lengkap'" class="font-caption whitespace-nowrap">
                  ({{ row.jumlah_belum_lengkap }}/{{ row.jumlah_data }})
                </span>
              </div>
            </Table.Td>

            <Table.Td class="font-num text-right">
              {{ row.jumlah_cabang }}
            </Table.Td>

            <Table.Td class="font-num text-right">
              {{ row.jumlah_data }}
            </Table.Td>

            <Table.Td class="font-body">
              {{ formatDateTime(row.terakhir_diupdate) }}
            </Table.Td>

            <Table.Td class="text-center" @click.stop>
              <Button variant="soft-dark" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Lihat Detail"
                @click="openSlideover(row)">
                <Lucide icon="Eye" class="w-4 h-4" />
              </Button>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
      <Slideover size="xl" :open="slideoverOpen" @close="closeSlideover">
        <Slideover.Panel>
          <a href="#" class="top-0 right-auto left-0 absolute mt-4 -ml-12" @click.prevent="closeSlideover">
            <Lucide icon="X" class="w-8 h-8 text-slate-400" />
          </a>

          <Slideover.Title class="p-5">
            <div class="flex flex-col gap-1 min-w-0">
              <div class="flex items-center gap-2">
                <h2 class="font-header truncate">
                  {{ selectedPeriode?.label ?? '' }}
                </h2>
                <span v-if="selectedPeriode"
                  class="inline-flex flex-shrink-0 items-center px-2 py-0.5 rounded-full font-label whitespace-nowrap"
                  :class="selectedPeriode.category === 'active'
                    ? 'bg-success/10 text-success'
                    : selectedPeriode.category === 'upcoming'
                      ? 'bg-blue-50 text-blue-600'
                      : 'bg-slate-100 text-slate-500'">
                  {{ TAB_META[selectedPeriode.category].label }}
                </span>
                <span v-if="selectedPeriode"
                  class="inline-flex flex-shrink-0 items-center px-2 py-0.5 rounded-full font-label whitespace-nowrap"
                  :class="selectedPeriode.status === 'lengkap'
                    ? 'bg-success/10 text-success'
                    : 'bg-amber-100 text-amber-600'">
                  {{ selectedPeriode.status === 'lengkap' ? 'Lengkap' : 'Belum Lengkap' }}
                </span>
                <button v-if="canEditPeriodeNow && selectedPeriode" type="button" title="Edit Periode"
                  class="hover:bg-slate-100 ml-1 p-1 rounded-full text-slate-400 hover:text-slate-600 transition"
                  @click="openEditPeriodModal">
                  <Lucide icon="Pencil" class="w-4 h-4" />
                </button>
              </div>
              <p class="font-caption">
                {{ selectedPeriode?.jumlah_data }} data · {{ selectedPeriode?.jumlah_cabang }} cabang
              </p>
            </div>
          </Slideover.Title>

          <Slideover.Description class="p-5">
            <div class="gap-3 grid grid-cols-1 sm:grid-cols-2 mb-4">
              <div class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-1.5 mb-1 font-section text-slate-500">
                  <Lucide icon="StickyNote" class="w-4 h-4" />
                  Catatan
                </div>
                <p class="font-body text-slate-700">
                  {{ periodeDetailLoading ? 'Memuat...' : (periodeNotes || '-') }}
                </p>
              </div>
              <div class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                <div class="flex items-center gap-1.5 mb-1 font-section text-slate-500">
                  <Lucide icon="Paperclip" class="w-4 h-4" />
                  Lampiran
                </div>
                <div v-if="periodeDetailLoading" class="font-body text-slate-400">Memuat...</div>
                <div v-else-if="periodeAttachments.length === 0" class="font-body text-slate-400">
                  Belum ada lampiran
                </div>
                <ul v-else class="space-y-1">
                  <li v-for="(att, idx) in periodeAttachments" :key="idx">
                    <a :href="`/storage/${att.path}`" target="_blank" rel="noopener"
                      class="flex items-center gap-1.5 font-body text-blue-600 hover:underline">
                      <Lucide icon="FileText" class="flex-shrink-0 w-3.5 h-3.5" />
                      <span class="flex-1 min-w-0 truncate">{{ att.original_filename }}</span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>

            <div v-if="incompleteNotice"
              class="flex items-start gap-2 bg-amber-50 mb-4 px-4 py-3 border border-amber-200 rounded-lg font-body text-amber-700">
              <Lucide icon="AlertTriangle" class="flex-shrink-0 mt-0.5 w-4 h-4" />
              <span>
                <span class="font-strong">{{ incompleteNotice.count }} dari {{ incompleteNotice.total }} harga
                  produk</span>
                di periode ini belum lengkap — nilai yang ditandai merah masih perlu diisi.
              </span>
            </div>

            <PeriodPricesPanel :rows="pricesRows" :loading="pricesLoading" :cabangs="cabangs" :produks="produks"
              :view="tableView" :can-edit="canEditRowNow" :can-delete="canDeleteRowNow" @edit="handleEditRow"
              @delete="confirmDelete" />
          </Slideover.Description>
        </Slideover.Panel>
      </Slideover>

      <DeleteRecordDialog :open="deleteModal" title="Hapus Harga Produk" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />

      <PeriodFormModal :open="periodModalOpen" :mode="periodModalMode" :period-id="selectedPeriode?.id ?? null"
        @close="closePeriodModal" @saved="handlePeriodSaved" />

      <ProductPriceRowModal :open="rowModalOpen" :mode="rowModalMode" :product-price="editingRow"
        :price-period-id="selectedPeriode?.id ?? focusPeriode?.id ?? null"
        :period-label="selectedPeriode?.label ?? focusPeriode?.label" :cabangs="cabangs" :produks="produks"
        @close="rowModalOpen = false" @saved="handleRowSaved" />

    </div>
  </div>
</template>
