<template>
  <div class="p-6 intro-y">
    <!-- Header -->
    <div
      class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-theme-1 via-theme-2 to-slate-700 px-6 py-6 text-white shadow-lg"
    >
      <div class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
      <div class="absolute -bottom-8 left-10 h-24 w-24 rounded-full bg-white/10 blur-2xl"></div>

      <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Daftar Vendor PO</h2>
          <p class="mt-1 text-sm text-white/80">
            Kelola Purchase Order vendor, filter data, dan akses aksi dengan cepat.
          </p>
        </div>

        <RouterLink :to="{ name: 'vendor-pos-create' }">
          <Button variant="secondary" class="border-0 bg-white text-slate-800 hover:bg-slate-100">
            <Lucide icon="Plus" class="mr-2 h-4 w-4" />
            Tambah PO
          </Button>
        </RouterLink>
      </div>
    </div>

    <!-- Summary -->
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="box rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-slate-500">Total Data</div>
            <div class="mt-1 text-2xl font-bold">{{ totalRows }}</div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Lucide icon="FileText" class="h-6 w-6" />
          </div>
        </div>
      </div>

      <div class="box rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-slate-500">Halaman</div>
            <div class="mt-1 text-2xl font-bold">{{ currentPage }} / {{ totalPages }}</div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10 text-success">
            <Lucide icon="LayoutGrid" class="h-6 w-6" />
          </div>
        </div>
      </div>

      <div class="box rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-slate-500">Per Page</div>
            <div class="mt-1 text-2xl font-bold">{{ perPage }}</div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10 text-warning">
            <Lucide icon="ListFilter" class="h-6 w-6" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="box mt-6 rounded-2xl p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-700">Filter PO</h3>
          <p class="text-sm text-slate-500">Filter berdasarkan tanggal, terminal, vendor, dan pencarian umum.</p>
        </div>

        <Button variant="outline-secondary" @click="resetFilter">
          <Lucide icon="RotateCcw" class="mr-2 h-4 w-4" />
          Reset
        </Button>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Tanggal Dari</label>
          <FormInput v-model="filterDateFrom" type="date" class="!box" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Tanggal Sampai</label>
          <FormInput v-model="filterDateTo" type="date" class="!box" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Terminal</label>
          <FormSelect v-model="filterTerminal" class="!box">
            <option value="">— Semua Terminal —</option>
            <option v-for="t in terminals" :key="t.id_terminal" :value="t.id_terminal">
              {{ t.nama_terminal }}
            </option>
          </FormSelect>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Vendor</label>
          <FormSelect v-model="filterVendor" class="!box">
            <option value="">— Semua Vendor —</option>
            <option v-for="v in vendors" :key="v.id_vendor" :value="v.id_vendor">
              {{ v.nama_vendor }}
            </option>
          </FormSelect>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Search</label>
          <FormInput
            v-model="searchQuery"
            placeholder="Nomor PO / keterangan..."
            class="pr-10 !box"
          >
            <template #icon>
              <Lucide icon="Search" />
            </template>
          </FormInput>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-600">Per Page</label>
          <FormSelect v-model="perPage" class="!box">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </FormSelect>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider text-slate-600">No</th>
            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider text-slate-600">Nomor PO</th>
            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider text-slate-600">Tanggal PO</th>
            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider text-slate-600">Vendor</th>
            <th class="px-4 py-3 text-xs font-semibold text-left uppercase tracking-wider text-slate-600">Terminal</th>
            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider text-slate-600">Status</th>
            <th class="px-4 py-3 text-xs font-semibold text-center uppercase tracking-wider text-slate-600">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(po, idx) in vendorPos"
            :key="po.id_po"
            class="transition-colors hover:bg-slate-50"
          >
            <td class="px-4 py-4 whitespace-nowrap text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </td>

            <td class="px-4 py-4 whitespace-nowrap">
              <div class="font-semibold text-slate-700">{{ po.nomor_po }}</div>
              
            </td>

            <td class="px-4 py-4 whitespace-nowrap text-slate-700">
              {{ formatDate(po.tanggal_inven) }}
            </td>

            <td class="px-4 py-4 whitespace-nowrap text-slate-700">
              {{ po.vendor?.nama_vendor || '-' }}
            </td>

            <td class="px-4 py-4 whitespace-nowrap text-slate-700">
              {{ po.terminal?.nama_terminal || '-' }}
            </td>

            <td class="px-4 py-4 text-center whitespace-nowrap">
              <span
                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusBadgeClass(po.disposisi_po)"
              >
                {{ statusLabel(po.disposisi_po) }}
              </span>
            </td>

            <td class="px-4 py-4 whitespace-nowrap text-center">
              <div class="inline-flex items-center gap-2">
                <!-- Cetak -->
                <button
                  v-if="po.disposisi_po === 4"
                  @click="preview(po.id_po)"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 transition hover:bg-green-100 hover:text-green-800"
                  title="Cetak"
                >
                  <Lucide icon="Printer" class="h-5 w-5" />
                </button>

                <!-- Receive Item -->
                <button
                  v-if="po.disposisi_po === 4"
                  @click="goReceiveItem(po.id_po)"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition hover:bg-indigo-100 hover:text-indigo-800"
                  title="Receive Item"
                >
                  <Lucide icon="Package" class="h-5 w-5" />
                </button>

                <!-- Delete -->
                <button
                  v-if="po.disposisi_po === 0"
                  @click="confirmDelete(po.nomor_po, po.id_po)"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 hover:text-red-800"
                  title="Delete"
                >
                  <Lucide icon="Trash2" class="h-5 w-5" />
                </button>

                <!-- Detail -->
                <RouterLink
                  :to="{ name: 'vendor-pos-detail', params: { id: po.id_po } }"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-100 hover:text-blue-800"
                  title="Detail"
                >
                  <Lucide icon="Eye" class="h-5 w-5" />
                </RouterLink>

                <!-- Edit -->
                <RouterLink
                  :to="{ name: 'vendor-pos-edit', params: { id: po.id_po } }"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600 transition hover:bg-yellow-100 hover:text-yellow-800"
                  title="Edit"
                >
                  <Lucide icon="Edit" class="h-5 w-5" />
                </RouterLink>
              </div>
            </td>
          </tr>

          <tr v-if="!vendorPos.length">
            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
              Tidak ada data PO ditemukan
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-5 flex justify-center intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchData(currentPage - 1)"
        >
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

        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchData(currentPage + 1)"
        >
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
import { useRouter, RouterLink } from 'vue-router'
import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router = useRouter()

const vendorPos = ref<any[]>([])
const vendors = ref<any[]>([])
const terminals = ref<any[]>([])

const searchQuery = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterTerminal = ref('')
const filterVendor = ref('')

const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRows = ref(0)

function formatDate(value: string | null | undefined) {
  if (!value) return '-'
  const date = new Date(value)
  if (isNaN(date.getTime())) return value

  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function statusLabel(disposisi: number) {
  return disposisi === 0
    ? 'Draft'
    : disposisi === 1
    ? 'Menunggu Verifikasi CFO'
    : disposisi === 2
    ? 'Menunggu Verifikasi CEO'
    : disposisi === 4
    ? 'Verified'
    : '-'
}

function statusBadgeClass(disposisi: number) {
  return disposisi === 0
    ? 'bg-yellow-100 text-yellow-700'
    : disposisi === 1
    ? 'bg-orange-100 text-orange-700'
    : disposisi === 2
    ? 'bg-red-100 text-red-700'
    : disposisi === 4
    ? 'bg-green-100 text-green-700'
    : 'bg-slate-100 text-slate-600'
}

async function fetchVendors() {
  try {
    const res = await axios.get('/api/vendors', {
      params: { per_page: 200 }
    })
    vendors.value = res.data.data || res.data || []
  } catch {
    vendors.value = []
  }
}

async function fetchTerminals() {
  try {
    const res = await axios.get('/api/terminals', {
      params: { per_page: 200 }
    })
    terminals.value = res.data.data || res.data || []
  } catch {
    terminals.value = []
  }
}

/** Ambil data PO */
async function fetchData(page = 1) {
  try {
    const res = await axios.get('/api/vendor-pos', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        tanggal_dari: filterDateFrom.value || undefined,
        tanggal_sampai: filterDateTo.value || undefined,
        id_terminal: filterTerminal.value || undefined,
        id_vendor: filterVendor.value || undefined,
      }
    })

    vendorPos.value = res.data.data || []
    currentPage.value = res.data.current_page || 1
    totalPages.value = res.data.last_page || 1
    totalRows.value = res.data.total || 0
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  }
}

function resetFilter() {
  searchQuery.value = ''
  filterDateFrom.value = ''
  filterDateTo.value = ''
  filterTerminal.value = ''
  filterVendor.value = ''
  fetchData(1)
}

/** Buka PDF di tab baru */
async function preview(id: number) {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    Swal.fire('Error', 'Gagal membuka PDF', 'error')
  }
}

function goReceiveItem(idPo: number) {
  router.push({
    name: 'receive-item-list',
    params: { id: idPo }
  })
}

function confirmDelete(nomorPo: string, id: number) {
  Swal.fire({
    title: `Hapus PO ${nomorPo}?`,
    text: 'Data yang dihapus tidak dapat dikembalikan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal'
  }).then(async res => {
    if (!res.isConfirmed) return

    try {
      await axios.delete(`/api/vendor-pos/${id}`)
      Swal.fire({
        icon: 'success',
        title: `PO ${nomorPo} terhapus`,
        toast: true,
        position: 'top-end',
        timer: 1500,
        showConfirmButton: false
      })
      fetchData(currentPage.value)
    } catch (e: any) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal menghapus PO', 'error')
    }
  })
}

onMounted(async () => {
  await Promise.all([
    fetchVendors(),
    fetchTerminals(),
  ])
  fetchData()
})

watch(
  [searchQuery, filterDateFrom, filterDateTo, filterTerminal, filterVendor],
  debounce(() => fetchData(1), 300)
)

watch(perPage, () => fetchData(1))
</script>