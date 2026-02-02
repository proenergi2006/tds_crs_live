<template>
  <div class="p-6 intro-y">
    <!-- Header & Add -->
    <div class="flex items-center mb-4">
      <h2 class="text-lg font-medium">Daftar Penawaran</h2>

      <div class="ml-auto flex gap-2">
        <RouterLink :to="{ name: 'penawarans-create' }">
          <Button variant="primary" class="inline-flex items-center gap-2">
            <Lucide icon="PlusCircle" class="w-4 h-4" aria-hidden="true" />
            <span>Tambah Penawaran</span>
          </Button>
        </RouterLink>
        <!-- Contoh jika butuh tombol lain
        <RouterLink :to="{ name: 'penawarans-create-lubricant' }">
          <Button variant="outline-primary" class="inline-flex items-center gap-2">
            <Lucide icon="Droplets" class="w-4 h-4" aria-hidden="true" />
            <span>Tambah Lubricant</span>
          </Button>
        </RouterLink> -->
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mb-4 intro-y sm:flex-nowrap gap-2">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>

      <!-- Filter Cabang -->
      <FormSelect v-model="filterCabang" class="w-48 !box">
        <option value="">— Semua Cabang —</option>
        <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
          {{ c.nama_cabang }}
        </option>
      </FormSelect>

      <!-- Filter Nama Customer (ketik) -->
      <FormInput
        v-model="filterCustomerName"
        placeholder="Filter nama customer…"
        class="w-56 pr-10 !box"
      >
        <template #icon><Lucide icon="User" /></template>
      </FormInput>

      <!-- Search umum (nomor/kepada/nama di penawaran) -->
      <FormInput
        v-model="searchQuery"
        placeholder="Cari nomor atau customer…"
        class="w-56 pr-10 !box ml-auto"
      >
        <template #icon><Lucide icon="Search" /></template>
      </FormInput>

      <FormSelect v-model="perPage" class="w-20 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
      </FormSelect>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <Table class="min-w-full divide-y divide-slate-200">
        <Table.Thead>
          <Table.Tr>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>Nomor Penawaran</Table.Th>
            <Table.Th>Customer</Table.Th>
            <Table.Th>Cabang Invoice</Table.Th>
            <Table.Th class="text-center">Masa Berlaku</Table.Th>
            <Table.Th class="text-center">Volume</Table.Th>
            <Table.Th class="text-center">Disposisi</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr
            v-for="(pen, idx) in penawarans"
            :key="pen.id_penawaran"
            class="hover:bg-slate-100 transition-colors"
          >
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ pen.nomor_penawaran }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ pen.customer?.nama_perusahaan || '-' }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ pen.cabang?.nama_cabang || '-' }}
            </Table.Td>
            <Table.Td class="px-4 py-3 text-center whitespace-nowrap">
              {{ formatDate(pen.masa_berlaku) }} – {{ formatDate(pen.sampai_dengan) }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap text-right">
              {{ Number(pen.total_volume ?? 0).toLocaleString('id-ID') }} m³
            </Table.Td>
            <Table.Td class="px-4 py-3 text-center whitespace-nowrap">
              <span
                :class="disposisiClass(String(pen.disposisi_penawaran))"
                class="capitalize font-medium"
              >
                {{ getDisposisiLabel(pen.disposisi_penawaran) }}
              </span>
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap text-center flex justify-center">
              <!-- Detail -->
              <RouterLink
                :to="{ name: 'penawarans-detail', params: { id: pen.id_penawaran } }"
                class="text-blue-600 hover:text-blue-800 mx-1 inline-flex items-center"
                title="Detail"
              >
                <Lucide icon="Eye" class="w-5 h-5"/>
              </RouterLink>
              <RouterLink
                  :to="{ name: 'penawarans-edit', params: { id: pen.id_penawaran } }"
                  class="text-emerald-600 hover:text-emerald-800 mx-1 inline-flex items-center"
                  title="Edit"
                >
                  <Lucide icon="Edit" class="w-5 h-5" />
                </RouterLink>

              <!-- Edit + Hapus bila disposisi 1 / 2 (Draft / Menunggu BM) -->
              <template v-if="String(pen.disposisi_penawaran) === '1' || String(pen.disposisi_penawaran) === '2'">
                
                <button
                  @click="confirmDelete(pen.id_penawaran, pen.nomor_penawaran)"
                  class="text-red-600 hover:text-red-800 mx-1 inline-flex items-center"
                  title="Hapus"
                >
                  <Lucide icon="Trash2" class="w-5 h-5" />
                </button>
              </template>

              <!-- Buat PO bila disposisi 4 (Disetujui OM) -->
              <template v-if="String(pen.disposisi_penawaran) === '4'">
                <!-- <RouterLink
                  :to="{ name: 'penawarans-edit', params: { id: pen.id_penawaran } }"
                  class="text-emerald-600 hover:text-emerald-800 mx-1 inline-flex items-center"
                  title="Edit"
                >
                  <Lucide icon="Edit" class="w-5 h-5" />
                </RouterLink> -->
                <!-- <RouterLink
                  :to="{
                    name: 'penawarans-po',
                    query: { id_penawaran: pen.id_penawaran, id_customer: pen.id_customer }
                  }"
                  class="text-orange-600 hover:text-orange-800 mx-1 inline-flex items-center"
                  title="Buat PO"
                >
                  <Lucide icon="Pencil" class="w-5 h-5" />
                </RouterLink> -->
              </template>
            </Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-4 intro-y">
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
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink } from 'vue-router'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const penawarans = ref<any[]>([])
const searchQuery = ref('')
const filterCustomerName = ref('')  // <— filter nama customer (free text)
const filterCabang = ref<string|number>('') // <— filter cabang (dropdown)
const cabangs = ref<any[]>([])

const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)

async function fetchCabangs() {
  try {
    const res = await axios.get('/api/cabangs', { params: { per_page: 200 } })
    cabangs.value = res.data.data || res.data
  } catch {}
}

async function fetchData(page = 1) {
  try {
    const res = await axios.get('/api/penawarans', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        customer_name: filterCustomerName.value || undefined, // <— kirim ke API
        id_cabang: filterCabang.value || undefined            // <— kirim ke API
      }
    })
    penawarans.value   = res.data.data
    currentPage.value  = res.data.current_page
    totalPages.value   = res.data.last_page
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  }
}

function confirmDelete(id: number, nomor: string) {
  Swal.fire({
    title: `Hapus Penawaran “${nomor}”?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
  }).then(async result => {
    if (!result.isConfirmed) return
    try {
      await axios.delete(`/api/penawarans/${id}`)
      Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
      fetchData(currentPage.value)
    } catch (e: any) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal menghapus', 'error')
    }
  })
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
function disposisiClass(v: string) {
  return {
    'text-blue-600':   v === '1',
    'text-yellow-600': v === '2',
    'text-orange-600': v === '3',
    'text-green-600':  v === '4',
    'text-rose-600':   v === '5' || v === '6',
  }
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' })
    : '-'
}

onMounted(async () => {
  await fetchCabangs()
  fetchData()
})
watch([searchQuery, filterCustomerName, filterCabang], debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>
