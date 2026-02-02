<script setup lang="ts">
import { ref, onMounted, watch, reactive, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink, useRouter } from 'vue-router'
import { Dialog } from '@/components/Base/Headless'
import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router        = useRouter()
const hargaList     = ref<any[]>([])
const cabangs       = ref<any[]>([])
const produks       = ref<any[]>([])
const searchQuery   = ref('')
const filterCabang  = ref<string|number>('')
const filterProduk  = ref<string|number>('')
const perPage       = ref(10)
const currentPage   = ref(1)
const totalPages    = ref(1)
const loading       = ref(false)
const error         = ref<string|null>(null)

// === USER LOGIN ===
const currentUser = ref<any>({ name: '', id_role: 0 })
const isRole5 = computed(() => currentUser.value.id_role === 5)

// === FETCH DROPDOWN ===
async function fetchDropdowns() {
  try {
    const [cRes, pRes] = await Promise.all([
      axios.get('/api/cabangs', { params: { per_page: 100 } }),
      axios.get('/api/produks', { params: { per_page: 100 } }),
    ])
    cabangs.value = cRes.data.data
    produks.value = pRes.data.data
  } catch {
    // ignore errors
  }
}

// === FETCH DATA ===
async function fetchHarga(page = 1) {
  loading.value = true
  error.value   = null
  try {
    const res = await axios.get('/api/produk-hargas', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        id_cabang: filterCabang.value || undefined,
        id_produk: filterProduk.value || undefined,
      },
    })
    hargaList.value   = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value  = res.data.last_page
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
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

  Swal.fire({
    title: 'Hapus data ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus'
  }).then(async res => {
    if (res.isConfirmed) {
      await axios.delete(`/api/produk-hargas/${id}`)
      hargaList.value = hargaList.value.filter(h => h.id_produk_harga !== id)
      Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
    }
  })
}

// === MARGIN MODAL ===
const showMarginModal = ref(false)
const marginForm = reactive({
  id_cabang: '',
  id_produk: '',
  periode_awal: '',
  periode_akhir: '',
  margin: 0
})

function openMarginModal() {
  showMarginModal.value = true
}

async function applyMargin() {
  if (!marginForm.id_cabang || !marginForm.id_produk || !marginForm.periode_awal || !marginForm.periode_akhir || !marginForm.margin) {
    return Swal.fire('Peringatan', 'Semua field wajib diisi', 'warning')
  }

  try {
    await axios.post('/api/produk-hargas/add-margin', marginForm)
    Swal.fire('Sukses', 'Margin berhasil diterapkan', 'success')
    showMarginModal.value = false
    fetchHarga() // refresh tabel
  } catch (e: any) {
    Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error')
  }
}

// === WATCHER & INIT ===
watch([searchQuery, filterCabang, filterProduk], debounce(() => fetchHarga(1), 300))
watch(perPage, () => fetchHarga(1))

onMounted(async () => {
  await fetchDropdowns()
  const { data: user } = await axios.get('/api/user')
  currentUser.value = user
  await fetchHarga()
})

// === HELPERS ===
function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric'
  })
}
function formatNumber(value: number|string = 0) {
  const num = typeof value === 'string' ? parseInt(value, 10) : value
  return isNaN(num) ? '-' : num.toLocaleString('id-ID')
}
</script>

<template>
  <div class="p-6 intro-y">
    <!-- Header & Add New -->
    <div class="flex items-center">
      <h2 class="text-lg font-medium">Master Harga Produk</h2>

      <!-- Tombol Tambah hanya untuk Role 5 -->
      <RouterLink
        v-if="isRole5"
        :to="{ name: 'produk-hargas-create' }"
        class="ml-auto"
      >
        <Button variant="primary" class="inline-flex items-center gap-2">
          <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
          <span>Tambah Harga</span>
        </Button>
      </RouterLink>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap space-x-4">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
      <FormSelect v-model="filterCabang" class="w-40 !box">
        <option value="">— Semua Cabang —</option>
        <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
          {{ c.nama_cabang }}
        </option>
      </FormSelect>
      <FormSelect v-model="filterProduk" class="w-40 !box">
        <option value="">— Semua Produk —</option>
        <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
          {{ p.nama_produk }}
        </option>
      </FormSelect>
      <FormInput
        v-model="searchQuery"
        placeholder="Search…"
        class="w-56 pr-10 !box"
      >
        <template #icon><Lucide icon="Search" /></template>
      </FormInput>
      <FormSelect v-model="perPage" class="w-20 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
      </FormSelect>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Periode Awal</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Periode Akhir</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Cabang</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Produk</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Cogs</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Margin</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Price List</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Harga BM</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Harga OM</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Harga CEO</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Catatan</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(h, idx) in hargaList"
            :key="h.id_produk_harga"
            class="hover:bg-slate-100 transition-colors"
          >
            <td class="px-4 py-3">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
            <td class="px-4 py-3">{{ formatDate(h.periode_awal) }}</td>
            <td class="px-4 py-3">{{ formatDate(h.periode_akhir) }}</td>
            <td class="px-4 py-3">{{ h.cabang?.nama_cabang || '-' }}</td>
            <td class="px-4 py-3">
              {{ h.produk?.nama_produk || '-' }} - Uk: {{ h.produk?.ukuran?.nama_ukuran || '-' }}
              - {{ h.produk?.ukuran?.satuan?.nama_satuan || '-' }}
            </td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_cogs) }}</td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_margin) }}</td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_price_list) }}</td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_bm) }}</td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_om) }}</td>
            <td class="px-4 py-3 text-right">{{ formatNumber(h.harga_ceo) }}</td>
            <td class="px-4 py-3">{{ h.catatan || '-' }}</td>
            <td class="px-4 py-3 text-center flex justify-center items-center">
              <!-- Tombol Edit: semua role bisa -->
              <RouterLink
                :to="{ name: 'produk-hargas-edit', params: { id: h.id_produk_harga } }"
                class="text-blue-600 hover:text-blue-800 mx-2"
              >
                <Lucide icon="Edit" class="w-5 h-5"/>
              </RouterLink>

              <!-- Tombol Delete: hanya Role 5 -->
              <button
                v-if="isRole5"
                @click="confirmDelete(h.id_produk_harga)"
                class="text-red-600 hover:text-red-800 mx-2"
              >
                <Lucide icon="Trash2" class="w-5 h-5"/>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-5 intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage===1"
          @click="fetchHarga(currentPage-1)"
        >
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page===currentPage"
          @click="fetchHarga(page)"
        >
          {{ page }}
        </Pagination.Link>
        <Pagination.Link
          :disabled="currentPage===totalPages"
          @click="fetchHarga(currentPage+1)"
        >
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>

    <!-- Modal Margin -->
    <Dialog :open="showMarginModal" @close="showMarginModal = false">
      <Dialog.Panel class="p-6 w-[420px]">
        <Dialog.Title class="text-lg font-semibold mb-4 border-b pb-2">
          Tambah Margin Harga
        </Dialog.Title>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Cabang</label>
            <FormSelect v-model="marginForm.id_cabang" class="w-full">
              <option value="">Pilih Cabang</option>
              <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                {{ c.nama_cabang }}
              </option>
            </FormSelect>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Produk</label>
            <FormSelect v-model="marginForm.id_produk" class="w-full">
              <option value="">Pilih Produk</option>
              <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
                {{ p.nama_produk }} – Uk: {{ p.ukuran?.nama_ukuran }} {{ p.ukuran?.satuan?.nama_satuan }}
              </option>
            </FormSelect>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Periode Awal</label>
              <FormInput v-model="marginForm.periode_awal" type="date" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Periode Akhir</label>
              <FormInput v-model="marginForm.periode_akhir" type="date" class="w-full" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Nominal Margin (Rp)</label>
            <FormInput v-model.number="marginForm.margin" type="number" placeholder="contoh: 5000" class="w-full" />
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2 border-t pt-4">
          <Button variant="outline-secondary" @click="showMarginModal = false">Batal</Button>
          <Button variant="primary" @click="applyMargin">Terapkan</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>
