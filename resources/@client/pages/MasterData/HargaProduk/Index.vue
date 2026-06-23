<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

import { Slideover } from '@/components/Base/Headless'
import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { createResourceApi } from '@/utils/resourceApi'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

// ─── Types ───────────────────────────────────────────────────────────────────

type PeriodeRow = {
  periode_awal: string
  periode_akhir: string
  label: string
  status: 'aktif' | 'berakhir'
  jumlah_data: number
  jumlah_cabang: number
  terakhir_diupdate: string | null
}

type HargaRow = {
  id_produk_harga: number
  id_cabang: number
  id_produk: number
  periode_awal: string
  periode_akhir: string
  harga_price_list: string | number | null
  harga_price_list_pe: string | number | null
  harga_bm: string | number | null
  harga_cogs: string | number | null
  harga_margin: string | number | null
  catatan: string | null
  cabang?: { id_cabang: number; nama_cabang: string }
  produk?: {
    id_produk: number
    nama_produk: string
    ukuran?: { nama_ukuran: string; satuan?: { nama_satuan: string } }
  }
}

// ─── Composables & APIs ──────────────────────────────────────────────────────

const auth = useAuthStore()
const periodeApi = createResourceApi('/produk-hargas/periode')
const hargaProdukApi = createResourceApi('/produk-hargas')
const cabangApi = createResourceApi('/cabangs')
const produkApi = createResourceApi('/produks')
const { success, error } = useNotification()

// ─── State: tabel utama (periode level, CSR) ─────────────────────────────────

const periodeList = ref<PeriodeRow[]>([])
const periodeLoading = ref(false)
const periodeSearch = ref('')
const periodeCurrentPage = ref(1)
const periodePerPage = ref(10)

// ─── State: SlideOver detail harga ───────────────────────────────────────────

const slideoverOpen = ref(false)
const selectedPeriode = ref<PeriodeRow | null>(null)

const allRows = ref<HargaRow[]>([])
const slideoverLoading = ref(false)
const slideoverSearch = ref('')
const slideoverFilterCabang = ref<string | number>('')
const slideoverFilterProduk = ref<string | number>('')
const slideoverCurrentPage = ref(1)
const slideoverPerPage = ref(10)

// ─── State: dropdown shared ───────────────────────────────────────────────────

const cabangs = ref<any[]>([])
const produks = ref<any[]>([])

// ─── State: delete ────────────────────────────────────────────────────────────

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

// ─── Auth ─────────────────────────────────────────────────────────────────────

const isRole5 = computed(() => Number(auth.user?.id_role) === 5)

// ─── Computed: tabel utama ────────────────────────────────────────────────────

const filteredPeriode = computed(() => {
  const q = periodeSearch.value.trim().toLowerCase()
  if (!q) return periodeList.value
  return periodeList.value.filter(r => r.label.toLowerCase().includes(q))
})

const periodeTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredPeriode.value.length / periodePerPage.value)),
)

const paginatedPeriode = computed(() => {
  const start = (periodeCurrentPage.value - 1) * periodePerPage.value
  return filteredPeriode.value.slice(start, start + periodePerPage.value)
})

const slideoverActiveFilterCount = computed(() =>
  [slideoverFilterCabang.value, slideoverFilterProduk.value].filter(Boolean).length,
)

const filteredRows = computed(() => {
  let rows = allRows.value

  const q = slideoverSearch.value.trim().toLowerCase()
  if (q) {
    rows = rows.filter(r =>
      (r.produk?.nama_produk ?? '').toLowerCase().includes(q) ||
      (r.cabang?.nama_cabang ?? '').toLowerCase().includes(q),
    )
  }

  if (slideoverFilterCabang.value) {
    rows = rows.filter(r => r.id_cabang === Number(slideoverFilterCabang.value))
  }

  if (slideoverFilterProduk.value) {
    rows = rows.filter(r => r.id_produk === Number(slideoverFilterProduk.value))
  }

  return rows
})

const slideoverTotalRecords = computed(() => filteredRows.value.length)

const slideoverTotalPages = computed(() =>
  Math.max(1, Math.ceil(filteredRows.value.length / slideoverPerPage.value)),
)

const paginatedRows = computed(() => {
  const start = (slideoverCurrentPage.value - 1) * slideoverPerPage.value
  return filteredRows.value.slice(start, start + slideoverPerPage.value)
})

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([
    fetchDropdowns(),
    auth.user ? Promise.resolve() : auth.fetchUser(),
  ])
  await fetchPeriode()
})

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(periodeSearch, () => { periodeCurrentPage.value = 1 })

watch(
  [slideoverSearch, slideoverFilterCabang, slideoverFilterProduk, slideoverPerPage],
  () => { slideoverCurrentPage.value = 1 },
)

// ─── Fetch ────────────────────────────────────────────────────────────────────

async function fetchDropdowns() {
  try {
    const [cabangRes, produkRes] = await Promise.all([
      cabangApi.getAll({ as_list: true }),
      produkApi.getAll({ as_list: true }),
    ])
    cabangs.value = cabangRes.data.data ?? cabangRes.data ?? []
    produks.value = produkRes.data.data ?? produkRes.data ?? []
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data pilihan')
  }
}

async function fetchPeriode() {
  periodeLoading.value = true
  try {
    const { data } = await periodeApi.getAll()
    periodeList.value = data.data ?? data ?? []
  } catch (e: any) {
    periodeList.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data periode')
  } finally {
    periodeLoading.value = false
  }
}

async function fetchSlideoverData() {
  if (!selectedPeriode.value) return

  slideoverLoading.value = true
  try {
    const { data } = await hargaProdukApi.getAll({
      as_list: true,
      periode_awal: selectedPeriode.value.periode_awal,
      periode_akhir: selectedPeriode.value.periode_akhir,
    })
    allRows.value = data.data ?? data ?? []
  } catch (e: any) {
    allRows.value = []
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data harga')
  } finally {
    slideoverLoading.value = false
  }
}

// ─── Action handlers ──────────────────────────────────────────────────────────

function openSlideover(row: PeriodeRow) {
  selectedPeriode.value = row
  slideoverSearch.value = ''
  slideoverFilterCabang.value = ''
  slideoverFilterProduk.value = ''
  slideoverCurrentPage.value = 1
  allRows.value = []
  slideoverOpen.value = true
  fetchSlideoverData()
}

function closeSlideover() {
  slideoverOpen.value = false
}

function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true
  try {
    await hargaProdukApi.destroy(deleteTarget.value)
    allRows.value = allRows.value.filter(
      item => item.id_produk_harga !== deleteTarget.value,
    )
    if (slideoverCurrentPage.value > slideoverTotalPages.value) {
      slideoverCurrentPage.value = slideoverTotalPages.value
    }
    deleteModal.value = false
    success('Berhasil', 'Harga produk berhasil dihapus.')
    await fetchPeriode()
  } catch (e: any) {
    error('Gagal menghapus', e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDateTime(dateStr: string | null) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function formatNumber(value: string | number | null | undefined) {
  if (value === null || value === undefined || value === '') return '-'
  const num = Number(value)
  return isNaN(num) ? '-' : num.toLocaleString('id-ID')
}

function produkText(row: HargaRow) {
  const nama = row.produk?.nama_produk ?? '-'
  const ukuran = row.produk?.ukuran?.nama_ukuran ?? '-'
  const satuan = row.produk?.ukuran?.satuan?.nama_satuan ?? ''
  return `${nama} · ${ukuran} ${satuan}`.trim()
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">

      <PageHeader title="Master Harga Produk"
        description="Kelola data harga produk per periode. Klik baris untuk melihat detail harga.">
        <template #action>
          <Button v-if="isRole5" :as="RouterLink" :to="{ name: 'produk-hargas-create' }" variant="white"
            class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Harga
          </Button>
        </template>
      </PageHeader>

      <!-- Tabel utama: Periode -->
      <DataList v-model:search="periodeSearch" v-model:per-page="periodePerPage" :loading="periodeLoading"
        :empty="filteredPeriode.length === 0" :colspan="6" :show-footer="true" :show-toolbar="true"
        :total="filteredPeriode.length" :current-page="periodeCurrentPage" :total-pages="periodeTotalPages"
        search-placeholder="Cari periode..." loading-text="Memuat data periode..."
        empty-description="Belum ada data periode harga produk." @page-change="(p) => { periodeCurrentPage = p }">
        <template #head>
          <Table.Th>Periode</Table.Th>
          <Table.Th class="w-28">Status</Table.Th>
          <Table.Th class="text-right w-32">Jumlah Cabang</Table.Th>
          <Table.Th class="text-right w-32">Jumlah Data</Table.Th>
          <Table.Th class="w-40">Diupdate</Table.Th>
          <Table.Th class="text-center w-24">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="row in paginatedPeriode" :key="`${row.periode_awal}__${row.periode_akhir}`"
            class="cursor-pointer transition hover:bg-slate-50" @click="openSlideover(row)">
            <Table.Td>
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                  <Lucide icon="CalendarDays" class="h-4 w-4" />
                </span>
                <span class="font-strong">{{ row.label }}</span>
              </div>
            </Table.Td>

            <Table.Td>
              <span class="font-label inline-flex items-center rounded-full px-2.5 py-1" :class="row.status === 'aktif'
                ? 'bg-success/10 text-success'
                : 'bg-slate-100 text-slate-500'">
                {{ row.status === 'aktif' ? 'Aktif' : 'Berakhir' }}
              </span>
            </Table.Td>

            <Table.Td class="font-num text-right">
              {{ row.jumlah_cabang }}
            </Table.Td>

            <Table.Td class="font-num text-right">
              {{ row.jumlah_data }}
            </Table.Td>

            <Table.Td class="font-body">
              {{ formatDateTime(row.terakhir_diupdate) }}
            </Table.Td>

            <Table.Td class="text-center" @click.stop>
              <Button variant="soft-dark" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Lihat Detail"
                @click="openSlideover(row)">
                <Lucide icon="Eye" class="h-4 w-4" />
              </Button>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- SlideOver: Detail harga per periode -->
      <Slideover size="xl" :open="slideoverOpen" @close="closeSlideover">
        <Slideover.Panel>
          <a href="#" class="absolute left-0 right-auto top-0 -ml-12 mt-4" @click.prevent="closeSlideover">
            <Lucide icon="X" class="h-8 w-8 text-slate-400" />
          </a>

          <Slideover.Title class="p-5">
            <div class="flex min-w-0 flex-col gap-1">
              <div class="flex items-center gap-2">
                <h2 class="font-header truncate">
                  {{ selectedPeriode?.label ?? '' }}
                </h2>
                <span v-if="selectedPeriode"
                  class="font-label inline-flex flex-shrink-0 items-center rounded-full px-2 py-0.5" :class="selectedPeriode.status === 'aktif'
                    ? 'bg-success/10 text-success'
                    : 'bg-slate-100 text-slate-500'">
                  {{ selectedPeriode.status === 'aktif' ? 'Aktif' : 'Berakhir' }}
                </span>
              </div>
              <p class="font-caption">
                {{ selectedPeriode?.jumlah_data }} data · {{ selectedPeriode?.jumlah_cabang }} cabang
              </p>
            </div>
          </Slideover.Title>

          <Slideover.Description class="p-5">
            <DataList v-model:search="slideoverSearch" v-model:per-page="slideoverPerPage" :loading="slideoverLoading"
              :empty="filteredRows.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true"
              :total="slideoverTotalRecords" :current-page="slideoverCurrentPage" :total-pages="slideoverTotalPages"
              :active-filter-count="slideoverActiveFilterCount" search-placeholder="Cari produk / cabang..."
              loading-text="Memuat data harga..." empty-description="Tidak ada data harga untuk periode dan filter ini."
              @page-change="(p) => { slideoverCurrentPage = p }">
              <template #filters>
                <div class="space-y-4 p-1">
                  <div>
                    <div class="font-section px-3 pb-2 pt-1">Cabang</div>
                    <FormSelect v-model="slideoverFilterCabang">
                      <option value="">Semua Cabang</option>
                      <option v-for="cabang in cabangs" :key="cabang.id_cabang" :value="cabang.id_cabang">
                        {{ cabang.nama_cabang }}
                      </option>
                    </FormSelect>
                  </div>
                  <div>
                    <div class="font-section px-3 pb-2">Produk</div>
                    <FormSelect v-model="slideoverFilterProduk">
                      <option value="">Semua Produk</option>
                      <option v-for="produk in produks" :key="produk.id_produk" :value="produk.id_produk">
                        {{ produk.nama_produk }}
                        <template v-if="produk.ukuran">
                          ({{ produk.ukuran.nama_ukuran }} {{ produk.ukuran.satuan?.nama_satuan }})
                        </template>
                      </option>
                    </FormSelect>
                  </div>
                  <div class="border-t border-slate-100 pt-3">
                    <Button type="button" variant="outline-secondary" class="w-full"
                      :disabled="slideoverActiveFilterCount === 0"
                      @click="slideoverFilterCabang = ''; slideoverFilterProduk = ''">
                      Clear Filter
                    </Button>
                  </div>
                </div>
              </template>

              <template #head>
                <Table.Th>Cabang</Table.Th>
                <Table.Th>Produk</Table.Th>
                <Table.Th class="text-right">COGS</Table.Th>
                <Table.Th class="text-right">Margin</Table.Th>
                <Table.Th class="text-right">Price List TDS</Table.Th>
                <Table.Th class="text-right">Price List PE</Table.Th>
                <Table.Th class="text-center">Aksi</Table.Th>
              </template>

              <template #body>
                <Table.Tr v-for="item in paginatedRows" :key="item.id_produk_harga"
                  class="transition hover:bg-slate-50">
                  <Table.Td class="font-body">
                    {{ item.cabang?.nama_cabang ?? '-' }}
                  </Table.Td>

                  <Table.Td class="font-body">
                    <div class="max-w-[220px]">{{ produkText(item) }}</div>
                  </Table.Td>

                  <Table.Td class="font-num text-right">
                    {{ formatNumber(item.harga_cogs) }}
                  </Table.Td>

                  <Table.Td class="font-num text-right">
                    {{ formatNumber(item.harga_margin) }}
                  </Table.Td>

                  <Table.Td class="font-num text-right">
                    {{ formatNumber(item.harga_price_list) }}
                  </Table.Td>

                  <Table.Td class="font-num text-right">
                    {{ formatNumber(item.harga_price_list_pe) }}
                  </Table.Td>

                  <Table.Td class="text-center">
                    <div class="inline-flex items-center justify-center gap-1.5">
                      <Button :as="RouterLink"
                        :to="{ name: 'produk-hargas-detail', params: { id: item.id_produk_harga } }" variant="soft-dark"
                        rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Detail">
                        <Lucide icon="Eye" class="h-4 w-4" />
                      </Button>

                      <Button :as="RouterLink"
                        :to="{ name: 'produk-hargas-edit', params: { id: item.id_produk_harga } }"
                        variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Edit">
                        <Lucide icon="Edit" class="h-4 w-4" />
                      </Button>

                      <Button v-if="isRole5" variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                        title="Hapus" @click="confirmDelete(item.id_produk_harga)">
                        <Lucide icon="Trash2" class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </Slideover.Description>
        </Slideover.Panel>
      </Slideover>

      <DeleteRecordDialog :open="deleteModal" title="Hapus Harga Produk" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />

    </div>
  </div>
</template>
