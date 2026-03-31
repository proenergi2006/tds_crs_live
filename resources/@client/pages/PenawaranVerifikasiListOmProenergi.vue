<template>
  <div class="p-6 intro-y">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">
          Daftar Verifikasi Penawaran Proenergi
        </h2>
        <p class="mt-1 text-slate-500">
          Monitor penawaran yang menunggu verifikasi Operational Manager.
        </p>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:w-auto">
        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
          <div class="text-sm font-medium uppercase tracking-wide text-slate-400">Total Data</div>
          <div class="mt-2 text-4xl font-bold text-slate-800">{{ totalData }}</div>
        </div>

        <div class="rounded-2xl border border-orange-200 bg-orange-50 px-6 py-5 shadow-sm">
          <div class="text-sm font-medium uppercase tracking-wide text-orange-500">Menunggu OM</div>
          <div class="mt-2 text-4xl font-bold text-orange-600">{{ totalMenungguOm }}</div>
        </div>

        <div class="rounded-2xl border border-green-200 bg-green-50 px-6 py-5 shadow-sm">
          <div class="text-sm font-medium uppercase tracking-wide text-green-500">Disetujui OM</div>
          <div class="mt-2 text-4xl font-bold text-green-600">{{ totalDisetujuiOm }}</div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="text-sm text-slate-500">
          Page <span class="font-semibold text-slate-700">{{ currentPage }}</span>
          of
          <span class="font-semibold text-slate-700">{{ totalPages }}</span>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <FormInput
            v-model="searchQuery"
            placeholder="Cari nomor atau customer..."
            class="w-full sm:w-80"
          >
            <template #icon><Lucide icon="Search" class="h-4 w-4" /></template>
          </FormInput>

          <FormSelect v-model="perPage" class="w-full sm:w-32">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
          </FormSelect>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div v-if="loading" class="flex items-center justify-center px-6 py-16 text-slate-500">
        Sedang memuat data...
      </div>

      <div v-else-if="penawarans.length === 0" class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
          <Lucide icon="Inbox" class="h-7 w-7 text-slate-400" />
        </div>
        <h3 class="text-lg font-semibold text-slate-700">Data tidak ditemukan</h3>
        <p class="mt-1 text-sm text-slate-500">Belum ada penawaran yang sesuai dengan pencarian.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <Table class="min-w-full">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                No
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                Nomor Penawaran
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                Customer
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                Cabang
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                Masa Berlaku
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                Status
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                Disposisi
              </Table.Th>
              <Table.Th class="whitespace-nowrap border-b border-slate-200 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                Aksi
              </Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody>
            <Table.Tr
              v-for="(pen, idx) in penawarans"
              :key="pen.id_penawaran"
              class="transition hover:bg-slate-50"
            >
              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top text-sm font-semibold text-slate-700">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top">
                <div class="font-bold text-slate-800">{{ pen.nomor_penawaran || '-' }}</div>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top">
                <div class="font-semibold text-slate-700">{{ pen.customer?.nama_perusahaan || '-' }}</div>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top">
                <div class="text-slate-700">{{ pen.cabang?.nama_cabang || '-' }}</div>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top text-center">
                <div class="text-slate-700">
                  {{ formatDate(pen.masa_berlaku) }} – {{ formatDate(pen.sampai_dengan) }}
                </div>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top text-center">
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold',
                    statusBadgeClass(pen.status)
                  ]"
                >
                  {{ getStatusLabel(pen.status) }}
                </span>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top text-center">
                <div class="flex flex-col items-center gap-1">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold',
                      disposisiBadgeClass(pen.disposisi_penawaran)
                    ]"
                  >
                    {{ getDisposisiLabel(pen.disposisi_penawaran) }}
                  </span>

                  <span
                    v-if="getDisposisiTanggal(pen)"
                    class="text-[11px] italic text-slate-500"
                  >
                    {{ getDisposisiTanggal(pen) }}
                  </span>
                </div>
              </Table.Td>

              <Table.Td class="border-b border-slate-100 px-6 py-5 align-top text-center">
                <RouterLink
                  :to="{ name: 'penawarans-verifikasi-om-detail-proenergi', params: { id: pen.id_penawaran } }"
                  class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100 hover:text-blue-700"
                  title="Detail"
                >
                  <Lucide icon="Eye" class="h-5 w-5" />
                </RouterLink>
              </Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && penawarans.length > 0" class="mt-6 flex justify-center intro-y">
      <Pagination>
        <Pagination.Link :disabled="currentPage === 1" @click="fetchData(currentPage - 1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>

        <Pagination.Link
          v-for="p in totalPages"
          :key="p"
          :active="p === currentPage"
          @click="fetchData(p)"
        >
          {{ p }}
        </Pagination.Link>

        <Pagination.Link :disabled="currentPage === totalPages" @click="fetchData(currentPage + 1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink } from 'vue-router'

import Table from '@/components/Base/Table'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const penawarans = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const loading = ref(false)

async function fetchData(page = 1) {
  loading.value = true

  try {
    const res = await axios.get('/api/penawarans-proenergi/om', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
      },
    })

    penawarans.value = res.data.data || []
    currentPage.value = res.data.current_page || 1
    totalPages.value = res.data.last_page || 1
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  } finally {
    loading.value = false
  }
}

const totalData = computed(() => penawarans.value.length)

const totalMenungguOm = computed(() =>
  penawarans.value.filter((pen: any) => String(pen.disposisi_penawaran) === '3').length
)

const totalDisetujuiOm = computed(() =>
  penawarans.value.filter((pen: any) => String(pen.disposisi_penawaran) === '4').length
)

function getStatusLabel(status: string | null | undefined): string {
  switch (status) {
    case 'draft':
      return 'Draft'
    case 'waiting_branch_manager':
      return 'Waiting BM'
    case 'approved_bm':
      return 'Approved BM'
    case 'approved_om':
      return 'Approved OM'
    case 'rejected_bm':
      return 'Rejected BM'
    case 'rejected_om':
      return 'Rejected OM'
    default:
      return status || '-'
  }
}

function statusBadgeClass(status: string | null | undefined) {
  switch (status) {
    case 'draft':
      return 'bg-blue-100 text-blue-700'
    case 'waiting_branch_manager':
      return 'bg-yellow-100 text-yellow-700'
    case 'approved_bm':
      return 'bg-indigo-100 text-indigo-700'
    case 'approved_om':
      return 'bg-green-100 text-green-700'
    case 'rejected_bm':
    case 'rejected_om':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

function getDisposisiLabel(value: string | number): string {
  switch (String(value)) {
    case '1':
      return 'Draft'
    case '2':
      return 'Menunggu Verifikasi BM'
    case '3':
      return 'Menunggu Verifikasi OM'
    case '4':
      return 'Disetujui OM'
    case '5':
      return 'Ditolak BM'
    case '6':
      return 'Ditolak OM'
    default:
      return '-'
  }
}

function disposisiBadgeClass(value: string | number) {
  switch (String(value)) {
    case '1':
      return 'bg-blue-100 text-blue-700'
    case '2':
      return 'bg-yellow-100 text-yellow-700'
    case '3':
      return 'bg-orange-100 text-orange-700'
    case '4':
      return 'bg-green-100 text-green-700'
    case '5':
    case '6':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

function formatDate(d?: string | null) {
  if (!d) return '-'

  return new Date(d).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function formatDateTime(value?: string | null) {
  if (!value) return ''

  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getDisposisiTanggal(pen: any): string {
  const disposisi = String(pen.disposisi_penawaran)

  if (disposisi === '3' && pen.bm_tanggal) {
    return `Approved BM: ${formatDateTime(pen.bm_tanggal)}`
  }

  if (disposisi === '4' && pen.om_tanggal) {
    return `Approved OM: ${formatDateTime(pen.om_tanggal)}`
  }

  if (disposisi === '5' && pen.bm_tanggal) {
    return `Rejected BM: ${formatDateTime(pen.bm_tanggal)}`
  }

  if (disposisi === '6' && pen.om_tanggal) {
    return `Rejected OM: ${formatDateTime(pen.om_tanggal)}`
  }

  return ''
}

onMounted(() => fetchData())

watch(
  searchQuery,
  debounce(() => fetchData(1), 300)
)

watch(perPage, () => fetchData(1))
</script>