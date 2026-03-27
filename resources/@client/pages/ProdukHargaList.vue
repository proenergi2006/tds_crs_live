<script setup lang="ts">
import { ref, onMounted, watch, reactive, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink, useRouter } from 'vue-router'
import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router = useRouter()

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
const totalData = ref(0)

const loading = ref(false)
const error = ref<string | null>(null)

// === USER LOGIN ===
const currentUser = ref<any>({ name: '', id_role: 0 })
const isRole5 = computed(() => Number(currentUser.value?.id_role) === 5)

// === FETCH DROPDOWN ===
async function fetchDropdowns() {
  try {
    const [cRes, pRes] = await Promise.all([
      axios.get('/api/cabangs', { params: { per_page: 100 } }),
      axios.get('/api/produks', { params: { per_page: 100 } }),
    ])

    cabangs.value = cRes.data.data || []
    produks.value = pRes.data.data || []
  } catch {
    cabangs.value = []
    produks.value = []
  }
}

// === FETCH DATA ===
async function fetchHarga(page = 1) {
  loading.value = true
  error.value = null

  try {
    const res = await axios.get('/api/produk-hargas', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        id_cabang: filterCabang.value || undefined,
        id_produk: filterProduk.value || undefined,
        periode_awal: filterPeriodeAwal.value || undefined,
        periode_akhir: filterPeriodeAkhir.value || undefined,
      },
    })

    hargaList.value = res.data.data || []
    currentPage.value = res.data.current_page || 1
    totalPages.value = res.data.last_page || 1
    totalData.value = res.data.total || 0
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
    hargaList.value = []
  } finally {
    loading.value = false
  }
}

// === DELETE DATA ===
async function confirmDelete(id: number) {
  if (!isRole5.value) {
    Swal.fire('Akses Ditolak', 'Hanya role 5 yang dapat menghapus data', 'error')
    return
  }

  const res = await Swal.fire({
    title: 'Hapus data ini?',
    text: 'Data yang dihapus tidak bisa dikembalikan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal',
  })

  if (!res.isConfirmed) return

  try {
    await axios.delete(`/api/produk-hargas/${id}`)
    hargaList.value = hargaList.value.filter(h => h.id_produk_harga !== id)

    Swal.fire({
      icon: 'success',
      title: 'Data berhasil dihapus',
      toast: true,
      position: 'top-end',
      timer: 1500,
      showConfirmButton: false,
    })

    fetchHarga(currentPage.value)
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan saat menghapus', 'error')
  }
}

function resetFilter() {
  searchQuery.value = ''
  filterCabang.value = ''
  filterProduk.value = ''
  filterPeriodeAwal.value = ''
  filterPeriodeAkhir.value = ''
  fetchHarga(1)
}

// === WATCHER & INIT ===
watch(
  [searchQuery, filterCabang, filterProduk, filterPeriodeAwal, filterPeriodeAkhir],
  debounce(() => fetchHarga(1), 350)
)

watch(perPage, () => fetchHarga(1))

onMounted(async () => {
  await fetchDropdowns()

  try {
    const { data: user } = await axios.get('/api/user')
    currentUser.value = user
  } catch {
    currentUser.value = { name: '', id_role: 0 }
  }

  await fetchHarga()
})

// === HELPERS ===
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
</script>

<template>
  <div class="p-6 intro-y">
    <!-- HEADER -->
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">Master Harga Produk</h2>
        <p class="mt-1 text-sm text-slate-500">
          Kelola data harga produk, filter berdasarkan cabang, produk, dan periode.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <RouterLink
          v-if="isRole5"
          :to="{ name: 'produk-hargas-create' }"
        >
          <Button variant="primary" class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            <span>Tambah Harga</span>
          </Button>
        </RouterLink>
      </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Total Data</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ totalData }}</h3>
          </div>
          <div class="rounded-full bg-primary/10 p-3 text-primary">
            <Lucide icon="Database" class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Halaman Aktif</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ currentPage }}</h3>
          </div>
          <div class="rounded-full bg-success/10 p-3 text-success">
            <Lucide icon="FileText" class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Total Halaman</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ totalPages }}</h3>
          </div>
          <div class="rounded-full bg-warning/10 p-3 text-warning">
            <Lucide icon="Layers3" class="h-5 w-5" />
          </div>
        </div>
      </div>
    </div>

    <!-- FILTER -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid grid-cols-1 gap-4 xl:grid-cols-6">
        <FormSelect v-model="filterCabang" class="!box">
          <option value="">— Semua Cabang —</option>
          <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
            {{ c.nama_cabang }}
          </option>
        </FormSelect>

        <FormSelect v-model="filterProduk" class="!box">
          <option value="">— Semua Produk —</option>
          <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
            {{ p.nama_produk }}
          </option>
        </FormSelect>

        <FormInput
          v-model="filterPeriodeAwal"
          type="date"
          class="!box"
        />

        <FormInput
          v-model="filterPeriodeAkhir"
          type="date"
          class="!box"
        />

        <FormInput
          v-model="searchQuery"
          placeholder="Cari produk / cabang..."
          class="pr-10 !box"
        >
          <template #icon>
            <Lucide icon="Search" />
          </template>
        </FormInput>

        <div class="flex gap-2">
          <FormSelect v-model="perPage" class="w-24 !box">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
          </FormSelect>

          <Button variant="outline-secondary" class="inline-flex items-center gap-2" @click="resetFilter">
            <Lucide icon="RotateCcw" class="h-4 w-4" />
            <span>Reset</span>
          </Button>
        </div>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <!-- TABLE -->
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">No</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Periode</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Cabang</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Produk</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">COGS</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Margin</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Price List TDS</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Price List PE</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Catatan</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Aksi</th>
            </tr>
          </thead>

          <tbody v-if="!loading" class="divide-y divide-slate-200">
            <tr
              v-for="(h, idx) in hargaList"
              :key="h.id_produk_harga"
              class="transition hover:bg-slate-50"
            >
              <td class="px-4 py-4 text-sm text-slate-700">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <td class="px-4 py-4 text-sm text-slate-700">
                <div class="font-medium">{{ periodeText(h) }}</div>
              </td>

              <td class="px-4 py-4 text-sm text-slate-700">
                {{ h.cabang?.nama_cabang || '-' }}
              </td>

              <td class="px-4 py-4 text-sm text-slate-700">
                <div class="max-w-[320px]">
                  {{ produkText(h) }}
                </div>
              </td>

              <td class="px-4 py-4 text-right text-sm text-slate-700">
                {{ formatNumber(h.harga_cogs) }}
              </td>

              <td class="px-4 py-4 text-right text-sm text-slate-700">
                {{ formatNumber(h.harga_margin) }}
              </td>

              <td class="px-4 py-4 text-right text-sm font-medium text-slate-800">
                {{ formatNumber(h.harga_price_list) }}
              </td>

              <td class="px-4 py-4 text-right text-sm font-medium text-slate-800">
                {{ formatNumber(h.harga_price_list_pe) }}
              </td>

              <td class="px-4 py-4 text-sm text-slate-600">
                {{ h.catatan || '-' }}
              </td>

              <td class="px-4 py-4 text-center">
                <div class="flex items-center justify-center gap-2">
                  <RouterLink
                    :to="{ name: 'produk-hargas-detail', params: { id: h.id_produk_harga } }"
                    class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-2 text-slate-700 transition hover:bg-slate-200"
                    title="Detail"
                  >
                    <Lucide icon="Eye" class="h-4 w-4" />
                  </RouterLink>

                  <RouterLink
                    :to="{ name: 'produk-hargas-edit', params: { id: h.id_produk_harga } }"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-2 py-2 text-blue-600 transition hover:bg-blue-100"
                    title="Edit"
                  >
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </RouterLink>

                  <button
                    v-if="isRole5"
                    @click="confirmDelete(h.id_produk_harga)"
                    class="inline-flex items-center rounded-lg bg-red-50 px-2 py-2 text-red-600 transition hover:bg-red-100"
                    title="Hapus"
                  >
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!hargaList.length">
              <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-500">
                Tidak ada data ditemukan.
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="text-sm text-slate-500">Memuat data...</div>
        </div>
      </div>
    </div>

    <!-- PAGINATION -->
    <div class="mt-5 flex justify-center intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchHarga(currentPage - 1)"
        >
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>

        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page === currentPage"
          @click="fetchHarga(page)"
        >
          {{ page }}
        </Pagination.Link>

        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchHarga(currentPage + 1)"
        >
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>