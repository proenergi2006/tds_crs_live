<template>
  <div class="p-6 intro-y">
    <!-- Header -->
    <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">Daftar Verifikasi Penawaran</h2>
        <p class="mt-1 text-sm text-slate-500">
          Monitor penawaran yang menunggu verifikasi Operational Manager.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 w-full lg:w-auto">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm min-w-[180px]">
          <div class="text-xs font-semibold tracking-wide uppercase text-slate-400">Total Data</div>
          <div class="mt-2 text-3xl font-bold text-slate-800">{{ totalData }}</div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm min-w-[180px]">
          <div class="text-xs font-semibold tracking-wide uppercase text-amber-500">Menunggu OM</div>
          <div class="mt-2 text-3xl font-bold text-amber-600">{{ totalMenungguOm }}</div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm min-w-[180px]">
          <div class="text-xs font-semibold tracking-wide uppercase text-emerald-500">Disetujui OM</div>
          <div class="mt-2 text-3xl font-bold text-emerald-600">{{ totalDisetujuiOm }}</div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="text-sm text-slate-500">
          Page <span class="font-semibold text-slate-700">{{ currentPage }}</span>
          of
          <span class="font-semibold text-slate-700">{{ totalPages }}</span>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <FormInput
            v-model="searchQuery"
            placeholder="Cari nomor atau customer…"
            class="w-full sm:w-80 pr-10 !box"
          >
            <template #icon><Lucide icon="Search" class="w-4 h-4" /></template>
          </FormInput>

          <FormSelect v-model="perPage" class="w-full sm:w-24 !box">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </FormSelect>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <Table class="min-w-full">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="w-16 text-center">No</Table.Th>
              <Table.Th>Nomor Penawaran</Table.Th>
              <Table.Th>Customer</Table.Th>
              <Table.Th>Cabang</Table.Th>
              <Table.Th class="text-center">Masa Berlaku</Table.Th>
              <Table.Th class="text-center">Status</Table.Th>
              <Table.Th class="text-center">Disposisi</Table.Th>
              <Table.Th class="text-center">Aksi</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody>
            <!-- Loading -->
            <Table.Tr v-if="loading">
              <Table.Td colspan="8" class="py-14 text-center">
                <div class="flex flex-col items-center gap-3 text-slate-500">
                  <Lucide icon="LoaderCircle" class="w-8 h-8 animate-spin" />
                  <span class="text-sm">Memuat data penawaran...</span>
                </div>
              </Table.Td>
            </Table.Tr>

            <!-- Empty -->
            <Table.Tr v-else-if="!penawarans.length">
              <Table.Td colspan="8" class="py-14 text-center">
                <div class="flex flex-col items-center gap-3 text-slate-500">
                  <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="Inbox" class="w-7 h-7" />
                  </div>
                  <div class="text-base font-medium text-slate-700">Data tidak ditemukan</div>
                  <div class="text-sm">Belum ada penawaran untuk verifikasi OM.</div>
                </div>
              </Table.Td>
            </Table.Tr>

            <!-- Data -->
            <Table.Tr
              v-for="(pen, idx) in penawarans"
              v-else
              :key="pen.id_penawaran"
              class="transition hover:bg-slate-50"
            >
              <Table.Td class="text-center font-medium text-slate-700">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </Table.Td>

              <Table.Td>
                <div class="font-semibold text-slate-800">
                  {{ pen.nomor_penawaran }}
                </div>
              </Table.Td>

              <Table.Td>
                <div class="font-medium text-slate-700">
                  {{ pen.customer?.nama_perusahaan || '-' }}
                </div>
              </Table.Td>

              <Table.Td>
                <div class="text-slate-600">
                  {{ pen.cabang?.nama_cabang || '-' }}
                </div>
              </Table.Td>

              <Table.Td class="text-center">
                <div class="text-slate-600 whitespace-nowrap">
                  {{ formatDate(pen.masa_berlaku) }}
                </div>
                <div class="text-slate-400 text-xs">s/d</div>
                <div class="text-slate-600 whitespace-nowrap">
                  {{ formatDate(pen.sampai_dengan) }}
                </div>
              </Table.Td>

              <Table.Td class="text-center">
                <span
                  class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                  :class="statusBadgeClass(pen.status)"
                >
                  {{ formatStatusLabel(pen.status) }}
                </span>
              </Table.Td>

              <Table.Td class="text-center">
                <div class="flex flex-col items-center gap-1">
                  <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                    :class="disposisiBadgeClass(pen.disposisi_penawaran)"
                  >
                    {{ getDisposisiLabel(pen.disposisi_penawaran) }}
                  </span>

                  <span
                    v-if="getDisposisiTanggal(pen)"
                    class="text-[11px] italic text-slate-400"
                  >
                    {{ getDisposisiTanggal(pen) }}
                  </span>
                </div>
              </Table.Td>

              <Table.Td class="text-center">
                <RouterLink
                  :to="{ name: 'penawarans-verifikasi-om-detail', params: { id: pen.id_penawaran } }"
                  class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100 hover:text-blue-700"
                  title="Lihat Detail"
                >
                  <Lucide icon="Eye" class="w-5 h-5" />
                </RouterLink>
              </Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-6 intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchData(currentPage - 1)"
        >
          <Lucide icon="ChevronLeft" class="w-4 h-4" />
        </Pagination.Link>

        <Pagination.Link
          v-for="p in totalPages"
          :key="p"
          :active="p === currentPage"
          @click="fetchData(p)"
        >
          {{ p }}
        </Pagination.Link>

        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchData(currentPage + 1)"
        >
          <Lucide icon="ChevronRight" class="w-4 h-4" />
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
    const res = await axios.get('/api/penawarans/om', {
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
  penawarans.value.filter((item: any) => String(item.disposisi_penawaran) === '3').length
)

const totalDisetujuiOm = computed(() =>
  penawarans.value.filter((item: any) => String(item.disposisi_penawaran) === '4').length
)

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

function disposisiBadgeClass(value: string | number): string {
  switch (String(value)) {
    case '1': return 'bg-slate-100 text-slate-700'
    case '2': return 'bg-blue-100 text-blue-700'
    case '3': return 'bg-amber-100 text-amber-800'
    case '4': return 'bg-emerald-100 text-emerald-700'
    case '5': return 'bg-rose-100 text-rose-700'
    case '6': return 'bg-rose-100 text-rose-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}

function statusBadgeClass(status: string): string {
  switch ((status || '').toLowerCase()) {
    case 'draft':
      return 'bg-slate-100 text-slate-700'
    case 'waiting_branch_manager':
      return 'bg-blue-100 text-blue-700'
    case 'approved_bm':
      return 'bg-indigo-100 text-indigo-700'
    case 'approved_om':
      return 'bg-emerald-100 text-emerald-700'
    case 'rejected_bm':
    case 'rejected_om':
      return 'bg-rose-100 text-rose-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

function formatStatusLabel(status: string): string {
  switch ((status || '').toLowerCase()) {
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

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      })
    : '-'
}

function formatDateTime(d?: string | null) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', {
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
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>