<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatDate, formatDateTime } from '@/utils/format'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const auth = useAuthStore()

type Brand = 'tds' | 'proenergi'

const BRAND_CONFIG = {
  tds: {
    apiBase: '/api/penawarans',
    createRoute: 'penawarans-create',
    detailRoute: 'penawarans-detail',
    editRoute: 'penawarans-edit',
    title: 'Penawaran',
    description: 'Kelola data penawaran ke customer',
  },
  proenergi: {
    apiBase: '/api/penawarans-proenergi',
    createRoute: 'penawarans-create-proenergi',
    detailRoute: 'penawarans-detail-proenergi',
    editRoute: 'penawarans-edit-proenergi',
    title: 'Penawaran Proenergi',
    description: 'Kelola data penawaran ke customer (Proenergi)',
  },
}

// computed, bukan const: route TDS/Proenergi berbagi komponen ini tanpa remount
const brand = computed<Brand>(() => (route.meta.brand as Brand) === 'proenergi' ? 'proenergi' : 'tds')
const cfg = computed(() => BRAND_CONFIG[brand.value])

/* State: data & pagination */
const penawarans = ref<any[]>([])
const cabangs = ref<any[]>([])
const searchQuery = ref('')
const filterCabang = ref<string | number>('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<{ id: number; nomor: string } | null>(null)

const canManagePenawaran = computed(() => brand.value === 'proenergi' ? auth.can('penawaran.proenergi.manage') : auth.can('penawaran.manage'))
const canViewAnyPenawaran = computed(() => brand.value === 'proenergi' ? auth.can('penawaran.proenergi.viewAny') : auth.can('penawaran.viewAny'))

function canManageRow(pen: any) {
  return (
    canManagePenawaran.value &&
    (canViewAnyPenawaran.value || Number(pen.user_id) === Number(auth.user?.id))
  )
}

onMounted(() => fetchCabangs())

watch(() => cfg.value.apiBase, () => fetchData(1), { immediate: true })
watch([searchQuery, filterCabang], debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchCabangs() {
  try {
    const res = await axios.get('/api/cabangs', { params: { per_page: 200 } })
    cabangs.value = res.data.data || res.data
  } catch { }
}

async function fetchData(page = 1) {
  loading.value = true
  try {
    const res = await axios.get(cfg.value.apiBase, {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        id_cabang: filterCabang.value || undefined,
      },
    })
    penawarans.value = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value = res.data.last_page
    totalRecords.value = res.data.total
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  fetchData(page)
}

/* Actions */
function openCreate() {
  router.push({ name: cfg.value.createRoute })
}

function openDetail(id: number) {
  router.push({ name: cfg.value.detailRoute, params: { id } })
}

function openEdit(id: number) {
  router.push({ name: cfg.value.editRoute, params: { id } })
}

function openCreateSalesOrder(id: number) {
  router.push({ name: 'penawarans-po', query: { id_penawaran: id } })
}

function confirmDelete(id: number, nomor: string) {
  deleteTarget.value = { id, nomor }
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await axios.delete(`${cfg.value.apiBase}/${deleteTarget.value.id}`)
    deleteModal.value = false
    success('Berhasil', 'Penawaran berhasil dihapus.')
    fetchData(currentPage.value)
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal menghapus penawaran.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

/* Helpers */
function getDisposisiLabel(value: string | number): string {
  switch (String(value)) {
    case '1': return 'Draft'
    case '2': return 'Menunggu Verifikasi BM'
    case '3': return 'Menunggu Verifikasi OM'
    case '4': return 'Disetujui OM'
    case '5': return 'Ditolak BM'
    case '6': return 'Ditolak OM'
    default: return '-'
  }
}

function disposisiClass(v: string | number) {
  const val = String(v)
  return {
    'bg-slate-100 text-slate-600': val === '1',
    'bg-amber-100 text-amber-700': val === '2',
    'bg-orange-100 text-orange-700': val === '3',
    'bg-emerald-100 text-emerald-700': val === '4',
    'bg-rose-100 text-rose-700': val === '5' || val === '6',
  }
}

/* Timestamp disposisi (mis. "Approved BM: 23 Jun 2026 14:30"). */
function getDisposisiTanggal(pen: any): string {
  const d = String(pen.disposisi_penawaran)
  if (d === '3' && pen.bm_tanggal) return `Approved BM: ${formatDateTime(pen.bm_tanggal)}`
  if (d === '4' && pen.om_tanggal) return `Approved OM: ${formatDateTime(pen.om_tanggal)}`
  if (d === '5' && pen.bm_tanggal) return `Rejected BM: ${formatDateTime(pen.bm_tanggal)}`
  if (d === '6' && pen.om_tanggal) return `Rejected OM: ${formatDateTime(pen.om_tanggal)}`
  return ''
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="cfg.title" :description="cfg.description">
        <template #action>
          <Button v-if="canManagePenawaran" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="PlusCircle" class="h-4 w-4" />
            Tambah Penawaran
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="penawarans.length === 0" :colspan="8" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nomor atau customer..."
        loading-text="Memuat data penawaran..." empty-description="Belum ada penawaran untuk ditampilkan."
        @page-change="goToPage">
        <template #toolbar-extra>
          <FormSelect v-model="filterCabang" class="w-48 !box">
            <option value="">— Semua Cabang —</option>
            <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
              {{ c.nama_cabang }}
            </option>
          </FormSelect>
        </template>

        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Cabang Invoice</Table.Th>
          <Table.Th class="text-center">Masa Berlaku</Table.Th>
          <Table.Th class="text-right">Volume</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(pen, idx) in penawarans" :key="pen.id_penawaran" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="font-num whitespace-nowrap">
              {{ pen.nomor_penawaran }}
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              {{ pen.customer?.company_name || '-' }}
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              {{ pen.cabang?.nama_cabang || '-' }}
            </Table.Td>
            <Table.Td class="text-center whitespace-nowrap">
              {{ formatDate(pen.masa_berlaku) }} – {{ formatDate(pen.sampai_dengan) }}
            </Table.Td>
            <Table.Td class="text-right whitespace-nowrap">
              {{ Number(pen.total_volume ?? 0).toLocaleString('id-ID') }} m³
            </Table.Td>
            <Table.Td class="text-center">
              <div class="flex flex-col items-center gap-1">
                <span class="font-label inline-flex items-center rounded-full px-3 py-1"
                  :class="disposisiClass(pen.disposisi_penawaran)">
                  {{ getDisposisiLabel(pen.disposisi_penawaran) }}
                </span>
                <span v-if="getDisposisiTanggal(pen)" class="font-caption italic">
                  {{ getDisposisiTanggal(pen) }}
                </span>
              </div>
            </Table.Td>
            <Table.Td class="text-center w-[260px]">
              <div class="inline-flex items-center justify-center gap-1">
                <ExtendableButton variant="soft-dark" rounded label="Detail" @click="openDetail(pen.id_penawaran)">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </ExtendableButton>

                <ExtendableButton variant="soft-pending" rounded label="Edit" @click="openEdit(pen.id_penawaran)">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </ExtendableButton>

                <ExtendableButton
                  v-if="String(pen.disposisi_penawaran) === '1' || String(pen.disposisi_penawaran) === '2'"
                  variant="soft-danger" rounded label="Hapus"
                  @click="confirmDelete(pen.id_penawaran, pen.nomor_penawaran)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </ExtendableButton>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" :title="`Hapus Penawaran ${deleteTarget?.nomor ?? ''}`"
        :loading="deleteLoading" @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
