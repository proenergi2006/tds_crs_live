<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { formatDate } from '@/utils/format'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'
import TippyContent from '@/components/Base/TippyContent'

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

const penawarans = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<{ id: number; nomor: string } | null>(null)

const canManagePenawaran = computed(() => brand.value === 'proenergi' ? auth.can('penawaran.proenergi.manage') : auth.can('penawaran.manage'))
const canViewAnyPenawaran = computed(() => brand.value === 'proenergi' ? auth.can('penawaran.proenergi.viewAny') : auth.can('penawaran.viewAny'))
const dataListColspan = computed(() => (canViewAnyPenawaran.value ? 8 : 7))

function canManageRow(pen: any) {
  return (
    canManagePenawaran.value &&
    (canViewAnyPenawaran.value || Number(pen.user_id) === Number(auth.user?.id))
  )
}

watch(() => cfg.value.apiBase, () => fetchData(1), { immediate: true })
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true
  try {
    const res = await axios.get(cfg.value.apiBase, {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
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

function openCreate() {
  router.push({ name: cfg.value.createRoute })
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

function sizePills(item: any): string[] {
  const nama = item?.produk?.ukuran?.nama_ukuran
  if (!nama) return []
  return String(nama).split(/,\s+/).map((s: string) => s.trim()).filter(Boolean)
}

function joinWithAmpersand(items: string[]): string {
  if (items.length <= 1) return items[0] || ''
  return `${items.slice(0, -1).join(', ')} & ${items[items.length - 1]}`
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader :title="cfg.title" :description="cfg.description">
        <template #action>
          <Button v-if="canManagePenawaran" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="PlusCircle" class="w-4 h-4" />
            Tambah Penawaran
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="penawarans.length === 0" :colspan="dataListColspan" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nomor atau customer..."
        loading-text="Memuat data penawaran..." empty-description="Belum ada penawaran untuk ditampilkan."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th v-if="canViewAnyPenawaran">Marketing</Table.Th>
          <Table.Th class="text-center">Tanggal Dibuat</Table.Th>
          <Table.Th class="text-center">Masa Berlaku</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(pen, idx) in penawarans" :key="pen.id_penawaran" class="hover:bg-slate-50 transition">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="font-num whitespace-nowrap">
              <span class="hover:underline cursor-pointer" :data-tooltip="`produk-tooltip-${pen.id_penawaran}`">
                {{ pen.nomor_penawaran }}
              </span>
              <div class="tooltip-content">
                <TippyContent :to="`produk-tooltip-${pen.id_penawaran}`"
                  :options="{ placement: 'right', interactive: true }">
                  <div class="w-80 max-w-[85vw]">
                    <div class="flex justify-between items-center gap-2 mb-3">
                      <div class="flex items-center gap-2 min-w-0">
                        <span class="bg-primary rounded-full w-2 h-2 shrink-0"></span>
                        <span class="font-label text-slate-500 truncate tracking-wide">Daftar Produk Penawaran</span>
                      </div>
                      <span class="bg-slate-100 px-2 py-0.5 rounded-full font-caption text-slate-600 shrink-0">
                        {{ pen.items?.length || 0 }} Item{{ (pen.items?.length || 0) > 1 ? 's' : '' }}
                      </span>
                    </div>

                    <div v-if="!pen.items || pen.items.length === 0" class="font-caption text-slate-500">
                      Belum ada produk.
                    </div>

                    <div v-else class="flex flex-col gap-2 pr-1 max-h-72 overflow-y-auto">
                      <div v-for="it in pen.items" :key="it.id_penawaran_item"
                        class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                        <div class="flex justify-between items-start gap-2">
                          <div class="min-w-0">
                            <div class="font-strong truncate">{{ it.produk?.nama_produk || '-' }}</div>
                            <div class="font-caption text-primary truncate">{{ it.produk?.jenis?.nama || '-' }}</div>
                          </div>
                          <span
                            class="bg-primary/10 px-2 py-0.5 rounded-full font-num-sm text-primary whitespace-nowrap shrink-0">
                            {{ Number(it.volume_order ?? 0).toLocaleString('id-ID') }} m³
                          </span>
                        </div>

                        <div v-if="sizePills(it).length" class="mt-2 font-caption text-slate-600">
                          <span class="font-strong text-slate-700 text-xs">Komposisi:</span>
                          Ukuran {{ joinWithAmpersand(sizePills(it)) }}
                        </div>
                      </div>
                    </div>

                    <div v-if="pen.items && pen.items.length > 0"
                      class="flex justify-between items-center mt-3 pt-1 border-slate-100 border-t">
                      <span class="font-caption text-slate-500">Total Volume:</span>
                      <span class="font-strong">{{ Number(pen.total_volume ?? 0).toLocaleString('id-ID') }} m³</span>
                    </div>
                  </div>
                </TippyContent>
              </div>
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              {{ pen.customer?.company_name || '-' }}
            </Table.Td>
            <Table.Td v-if="canViewAnyPenawaran" class="whitespace-nowrap">
              {{ pen.marketing?.name || '-' }}
            </Table.Td>
            <Table.Td class="text-center whitespace-nowrap">
              {{ formatDate(pen.created_at) }}
            </Table.Td>
            <Table.Td class="text-center whitespace-nowrap">
              {{ formatDate(pen.masa_berlaku) }} – {{ formatDate(pen.sampai_dengan) }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex items-center px-3 py-1 rounded-full font-label"
                :class="disposisiClass(pen.disposisi_penawaran)">
                {{ getDisposisiLabel(pen.disposisi_penawaran) }}
              </span>
            </Table.Td>
            <Table.Td class="w-[260px] text-center">
              <div class="inline-flex justify-center items-center gap-1">
                <ExtendableButton variant="soft-dark" rounded label="Detail" :as="RouterLink"
                  :to="{ name: cfg.detailRoute, params: { id: pen.id_penawaran } }">
                  <Lucide icon="Eye" class="w-4 h-4" />
                </ExtendableButton>

                <ExtendableButton variant="soft-pending" rounded label="Edit" :as="RouterLink"
                  :to="{ name: cfg.editRoute, params: { id: pen.id_penawaran } }">
                  <Lucide icon="Edit" class="w-4 h-4" />
                </ExtendableButton>

                <ExtendableButton
                  v-if="String(pen.disposisi_penawaran) === '1' || String(pen.disposisi_penawaran) === '2'"
                  variant="soft-danger" rounded label="Hapus"
                  @click="confirmDelete(pen.id_penawaran, pen.nomor_penawaran)">
                  <Lucide icon="Trash2" class="w-4 h-4" />
                </ExtendableButton>

                <ExtendableButton v-if="String(pen.disposisi_penawaran) === '4'" variant="soft-success" rounded
                  label="Buat PO Customer" @click="openCreateSalesOrder(pen.id_penawaran)">
                  <Lucide icon="ShoppingCart" class="w-4 h-4" />
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
