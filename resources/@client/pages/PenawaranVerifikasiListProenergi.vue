<template>
    <div class="p-6 intro-y">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-800">
          Daftar Verifikasi Penawaran Proenergi
        </h2>
        <p class="mt-1 text-sm text-slate-500">
          Monitor penawaran, status verifikasi, disposisi, dan masa berlaku penawaran customer.
        </p>
      </div>
  
      <!-- Summary -->
      <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 xl:grid-cols-4">
        <div class="p-5 bg-white border shadow-sm rounded-xl">
          <div class="text-sm text-slate-500">Total Penawaran</div>
          <div class="mt-2 text-2xl font-semibold text-slate-800">
            {{ totalRecords }}
          </div>
        </div>
  
        <div class="p-5 bg-white border shadow-sm rounded-xl">
          <div class="text-sm text-slate-500">Menunggu BM</div>
          <div class="mt-2 text-2xl font-semibold text-slate-700">
            {{ totalMenungguBm }}
          </div>
        </div>
  
        <div class="p-5 bg-white border shadow-sm rounded-xl">
          <div class="text-sm text-slate-500">Menunggu OM</div>
          <div class="mt-2 text-2xl font-semibold text-amber-600">
            {{ totalMenungguOm }}
          </div>
        </div>
  
        <div class="p-5 bg-white border shadow-sm rounded-xl">
          <div class="text-sm text-slate-500">Disetujui OM</div>
          <div class="mt-2 text-2xl font-semibold text-emerald-600">
            {{ totalApprovedOm }}
          </div>
        </div>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-col gap-3 p-4 mb-6 bg-white border shadow-sm rounded-xl md:flex-row md:items-center">
        <div class="text-sm text-slate-500">
          Page <span class="font-medium text-slate-700">{{ currentPage }}</span>
          of
          <span class="font-medium text-slate-700">{{ totalPages }}</span>
        </div>
  
        <div class="flex flex-col gap-3 ml-auto sm:flex-row sm:items-center">
          <FormInput
            v-model="searchQuery"
            placeholder="Cari nomor atau customer..."
            class="w-full sm:w-80"
          >
            <template #icon>
              <Lucide icon="Search" class="w-4 h-4" />
            </template>
          </FormInput>
  
          <FormSelect v-model="perPage" class="w-full sm:w-24">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
          </FormSelect>
        </div>
      </div>
  
      <!-- Error -->
      <div
        v-if="error"
        class="px-4 py-3 mb-6 text-sm border rounded-lg bg-danger/10 border-danger/20 text-danger"
      >
        {{ error }}
      </div>
  
      <!-- Table -->
      <div class="overflow-hidden bg-white border shadow-sm rounded-xl">
        <div class="overflow-x-auto">
          <Table class="min-w-full">
            <Table.Thead class="border-b bg-slate-50">
              <Table.Tr>
                <Table.Th class="w-16 px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">
                  No
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">
                  Nomor Penawaran
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">
                  Customer
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">
                  Cabang
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">
                  Masa Berlaku
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">
                  Status
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">
                  Disposisi
                </Table.Th>
                <Table.Th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">
                  Aksi
                </Table.Th>
              </Table.Tr>
            </Table.Thead>
  
            <Table.Tbody>
              <Table.Tr v-if="loading">
                <Table.Td colspan="8" class="px-5 py-10 text-center text-slate-500">
                  Loading data penawaran...
                </Table.Td>
              </Table.Tr>
  
              <Table.Tr v-else-if="penawarans.length === 0">
                <Table.Td colspan="8" class="px-5 py-10 text-center text-slate-500">
                  Data verifikasi penawaran tidak ditemukan.
                </Table.Td>
              </Table.Tr>
  
              <Table.Tr
                v-for="(pen, idx) in penawarans"
                :key="pen.id_penawaran"
                v-else
                class="transition hover:bg-slate-50"
              >
                <Table.Td class="px-5 py-4 align-top whitespace-nowrap text-slate-700">
                  {{ (currentPage - 1) * perPage + idx + 1 }}
                </Table.Td>
  
                <Table.Td class="px-5 py-4 align-top whitespace-nowrap">
                  <div class="font-medium text-slate-800">
                    {{ pen.nomor_penawaran || '-' }}
                  </div>
                </Table.Td>
  
                <Table.Td class="px-5 py-4 align-top whitespace-nowrap">
                  <div class="font-medium text-slate-800">
                    {{ pen.customer?.nama_perusahaan || '-' }}
                  </div>
                </Table.Td>
  
                <Table.Td class="px-5 py-4 align-top whitespace-nowrap text-slate-700">
                  {{ pen.cabang?.nama_cabang || '-' }}
                </Table.Td>
  
                <Table.Td class="px-5 py-4 text-center align-top whitespace-nowrap text-slate-700">
                  {{ formatDate(pen.masa_berlaku) }} – {{ formatDate(pen.sampai_dengan) }}
                </Table.Td>
  
                <Table.Td class="px-5 py-4 text-center align-top whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full"
                    :class="statusBadgeClass(pen.status)"
                  >
                    {{ formatStatus(pen.status) }}
                  </span>
                </Table.Td>
  
                <Table.Td class="px-5 py-4 text-center align-top whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                      disposisiBadgeClass(pen.disposisi_penawaran)
                    ]"
                  >
                    {{ getDisposisiLabel(pen.disposisi_penawaran) }}
                  </span>
                </Table.Td>
  
                <Table.Td class="px-5 py-4 text-center align-top whitespace-nowrap">
                  <div class="flex items-center justify-center">
                    <RouterLink
                      :to="{ name: 'penawarans-verifikasi-detail-proenergi', params: { id: pen.id_penawaran } }"
                      class="inline-flex items-center justify-center w-9 h-9 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50"
                      title="Lihat Detail"
                    >
                      <Lucide icon="Eye" class="w-4 h-4" />
                    </RouterLink>
                  </div>
                </Table.Td>
              </Table.Tr>
            </Table.Tbody>
          </Table>
        </div>
      </div>
  
      <!-- Pagination -->
      <div class="flex justify-center mt-6 intro-y" v-if="totalPages > 1">
        <Pagination>
          <Pagination.Link :disabled="currentPage === 1" @click="fetchData(currentPage - 1)">
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
  
          <Pagination.Link :disabled="currentPage === totalPages" @click="fetchData(currentPage + 1)">
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
  import { RouterLink } from 'vue-router'
  
  import Table from '@/components/Base/Table'
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  type PenawaranItem = {
    id_penawaran: number
    nomor_penawaran?: string
    masa_berlaku?: string
    sampai_dengan?: string
    status?: string
    disposisi_penawaran?: string | number
    customer?: {
      nama_perusahaan?: string
    }
    cabang?: {
      nama_cabang?: string
    }
  }
  
  const penawarans = ref<PenawaranItem[]>([])
  const searchQuery = ref('')
  const perPage = ref(10)
  const currentPage = ref(1)
  const totalPages = ref(1)
  const totalRecords = ref(0)
  const loading = ref(false)
  const error = ref('')
  
  async function fetchData(page = 1) {
    loading.value = true
    error.value = ''
  
    try {
      const res = await axios.get('/api/penawarans-proenergi/bm', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
  
      penawarans.value = res.data.data ?? []
      currentPage.value = res.data.current_page ?? 1
      totalPages.value = res.data.last_page ?? 1
      totalRecords.value = res.data.total ?? penawarans.value.length
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data verifikasi penawaran.'
      penawarans.value = []
    } finally {
      loading.value = false
    }
  }
  
  function formatDate(d?: string) {
    return d
      ? new Date(d).toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'long',
          year: 'numeric'
        })
      : '-'
  }
  
  function formatStatus(status?: string) {
    if (!status) return '-'
    return status.replaceAll('_', ' ')
  }
  
  function statusBadgeClass(status?: string): string {
    switch (status) {
      case 'draft':
        return 'bg-slate-100 text-slate-700'
      case 'menunggu':
        return 'bg-amber-100 text-amber-700'
      case 'approved bm':
      case 'approved om':
      case 'approved_bm':
      case 'approved_om':
        return 'bg-emerald-100 text-emerald-700'
      case 'rejected bm':
      case 'rejected om':
      case 'rejected_bm':
      case 'rejected_om':
        return 'bg-rose-100 text-rose-700'
      default:
        return 'bg-slate-100 text-slate-700'
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
  
  function disposisiBadgeClass(value: string | number): string {
    switch (String(value)) {
      case '1': return 'bg-slate-100 text-slate-700'
      case '2': return 'bg-gray-200 text-gray-700'
      case '3': return 'bg-amber-100 text-amber-800'
      case '4': return 'bg-emerald-100 text-emerald-700'
      case '5': return 'bg-rose-100 text-rose-700'
      case '6': return 'bg-rose-100 text-rose-700'
      default: return 'bg-slate-100 text-slate-700'
    }
  }
  
  const totalMenungguBm = computed(() =>
    penawarans.value.filter((p) => String(p.disposisi_penawaran) === '2').length
  )
  
  const totalMenungguOm = computed(() =>
    penawarans.value.filter((p) => String(p.disposisi_penawaran) === '3').length
  )
  
  const totalApprovedOm = computed(() =>
    penawarans.value.filter((p) => String(p.disposisi_penawaran) === '4').length
  )
  
  onMounted(() => fetchData())
  
  watch(
    searchQuery,
    debounce(() => fetchData(1), 300)
  )
  
  watch(perPage, () => fetchData(1))
  </script>