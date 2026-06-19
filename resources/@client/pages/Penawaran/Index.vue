<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
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

const router = useRouter()
const { success, error } = useNotification()

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

onMounted(() => {
  fetchCabangs()
  fetchData()
})

watch([searchQuery, filterCabang], debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchCabangs() {
  try {
    const res = await axios.get('/api/cabangs', { params: { per_page: 200 } })
    cabangs.value = res.data.data || res.data
  } catch {}
}

async function fetchData(page = 1) {
  loading.value = true
  try {
    const res = await axios.get('/api/penawarans', {
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
  router.push({ name: 'penawarans-create' })
}

function openDetail(id: number) {
  router.push({ name: 'penawarans-detail', params: { id } })
}

function openEdit(id: number) {
  router.push({ name: 'penawarans-edit', params: { id } })
}

function confirmDelete(id: number, nomor: string) {
  deleteTarget.value = { id, nomor }
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await axios.delete(`/api/penawarans/${deleteTarget.value.id}`)
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
    default:  return '-'
  }
}

function disposisiClass(v: string | number) {
  const val = String(v)
  return {
    'bg-slate-100 text-slate-600':   val === '1',
    'bg-amber-100 text-amber-700':   val === '2',
    'bg-orange-100 text-orange-700': val === '3',
    'bg-emerald-100 text-emerald-700': val === '4',
    'bg-rose-100 text-rose-700':     val === '5' || val === '6',
  }
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Penawaran" description="Kelola data penawaran ke customer">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="PlusCircle" class="h-4 w-4" />
            Tambah Penawaran
          </Button>
        </template>
      </PageHeader>

      <DataList
        v-model:search="searchQuery"
        v-model:per-page="perPage"
        :loading="loading"
        :empty="penawarans.length === 0"
        :colspan="8"
        :show-footer="true"
        :show-toolbar="true"
        :total="totalRecords"
        :current-page="currentPage"
        :total-pages="totalPages"
        search-placeholder="Cari nomor atau customer..."
        loading-text="Memuat data penawaran..."
        empty-description="Belum ada penawaran untuk ditampilkan."
        @page-change="goToPage"
      >
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
          <Table.Tr
            v-for="(pen, idx) in penawarans"
            :key="pen.id_penawaran"
            class="transition hover:bg-slate-50"
          >
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td class="whitespace-nowrap font-medium">
              {{ pen.nomor_penawaran }}
            </Table.Td>
            <Table.Td class="whitespace-nowrap">
              {{ pen.customer?.nama_perusahaan || '-' }}
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
              <span
                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                :class="disposisiClass(pen.disposisi_penawaran)"
              >
                {{ getDisposisiLabel(pen.disposisi_penawaran) }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-1">
                <Button
                  variant="soft-dark"
                  rounded
                  class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Detail"
                  @click="openDetail(pen.id_penawaran)"
                >
                  <Lucide icon="Eye" class="h-4 w-4" />
                </Button>

                <Button
                  variant="soft-pending"
                  rounded
                  class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Edit"
                  @click="openEdit(pen.id_penawaran)"
                >
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>

                <Button
                  v-if="String(pen.disposisi_penawaran) === '1' || String(pen.disposisi_penawaran) === '2'"
                  variant="soft-danger"
                  rounded
                  class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Hapus"
                  @click="confirmDelete(pen.id_penawaran, pen.nomor_penawaran)"
                >
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog
        :open="deleteModal"
        :title="`Hapus Penawaran ${deleteTarget?.nomor ?? ''}`"
        :loading="deleteLoading"
        @close="deleteModal = false"
        @confirm="submitDelete"
      />
    </div>
  </div>
</template>
