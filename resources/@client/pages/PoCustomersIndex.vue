<template>
    <div class="p-6 intro-y">
      <!-- Header -->
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-semibold">PO Customer</h2>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center gap-2 mb-4 intro-y sm:flex-nowrap">
        <FormInput v-model="q" placeholder="Keywords Customer / Nomor PO..." class="w-80 !box">
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
        <Button class="!box" @click="fetchData(1)">Cari</Button>
  
        <div class="ml-auto flex items-center gap-2 text-slate-500">
          <span>Show</span>
          <FormSelect v-model="perPage" class="w-24 !box">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </FormSelect>
          <span>Data</span>
        </div>
      </div>
  
      <!-- Table -->
      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <Table class="min-w-[1200px] divide-y divide-slate-200">
          <Table.Thead>
            <Table.Tr>
              <Table.Th class="w-12 text-center">No</Table.Th>
              <Table.Th class="w-44">Kode Dokumen</Table.Th>
              <Table.Th class="w-64">Customer</Table.Th>
              <Table.Th class="w-52">Cabang/ Marketing</Table.Th>
              <Table.Th class="w-64">Nomor / Tanggal PO</Table.Th>
              <Table.Th class="w-[260px]">Volume / Harga</Table.Th>
              <Table.Th class="w-40">Progress</Table.Th>
              <Table.Th class="w-28 text-center">Aksi</Table.Th>
            </Table.Tr>
          </Table.Thead>
  
          <Table.Tbody>
            <Table.Tr v-for="(row, i) in rows" :key="row.id_poc" class="align-top hover:bg-slate-50">
              <!-- No -->
              <Table.Td class="text-center">{{ (page - 1) * perPage + i + 1 }}</Table.Td>
  
              <!-- Kode Dokumen -->
              <Table.Td class="whitespace-nowrap">
                <div class="font-semibold">{{ docCode(row) }}</div>
                <div class="text-slate-500 text-xs">
                  {{ row.disposisi_text }}
                </div>
                <div v-if="row.disposisi_time" class="text-slate-500 text-xs">
                  {{ fmtDateTime(row.disposisi_time) }} WIB
                </div>
              </Table.Td>
  
              <!-- Customer -->
              <Table.Td class="whitespace-nowrap">
                <div class="font-semibold">{{ kode(row.customer?.kode_pelanggan) }}</div>
                <div class="text-slate-700">{{ row.customer?.nama_perusahaan || '-' }}</div>
              </Table.Td>
  
              <!-- Cabang/Marketing -->
              <Table.Td class="whitespace-nowrap">
                <div>{{ row.cabang_name || 'Jakarta' }}</div>
                <div class="italic text-slate-600">{{ row.marketing_name || '-' }}</div>
              </Table.Td>
  
              <!-- Nomor / Tanggal PO -->
              <Table.Td class="whitespace-nowrap">
                <div class="font-semibold">{{ row.nomor_poc || '-' }}</div>
                <div class="text-slate-500 text-xs">{{ fmtDate(row.tanggal_poc) }}</div>
              </Table.Td>
  
              <!-- Volume / Harga -->
              <Table.Td class="whitespace-nowrap">
                <div class="font-medium">
                  {{ fmtNumber(row.volume_liter) }} m³
                  <span class="text-slate-500"> ({{ fmtCurrency(row.harga_per_liter) }}/m³)</span>
                </div>
                <div class="text-xs text-slate-600">Terkirim&nbsp;&nbsp;{{ fmtNumber(row.shipped_liter || 0) }} m³</div>
                <div class="text-xs text-slate-600">Sisa Aktual {{ fmtNumber((row.volume_liter || 0) - (row.shipped_liter || 0)) }} m³</div>
                <div class="text-xs text-slate-600">Sisa Buku&nbsp;&nbsp;{{ fmtNumber(row.book_remaining_liter || 0) }} m³</div>
                <div class="text-xs text-slate-600">Vol Close PO {{ fmtNumber(row.close_volume_liter || 0) }} m³</div>
              </Table.Td>
  
              <!-- Progress -->
              <Table.Td class="whitespace-nowrap">
                <div class="w-36 h-4 bg-slate-200 rounded overflow-hidden">
                  <div class="h-full bg-sky-500" :style="{ width: progressPct(row) + '%' }"></div>
                </div>
                <div class="text-xs mt-1">{{ progressPct(row).toFixed(2) }}%</div>
              </Table.Td>
  
              <!-- Aksi -->
              <Table.Td class="text-center whitespace-nowrap">
                <!-- Planning pengiriman (hanya muncul bila disposisi BM) -->
                <button
    class="text-emerald-600 hover:text-emerald-800 mx-1"
    @click="plan(row)"
    title="Planning Pengiriman"
  >
    <Lucide icon="ClipboardList" class="w-5 h-5" />
  </button>
  
                <!-- Detail -->
                <button class="text-sky-600 hover:text-sky-800 mx-1"
                  @click="$router.push({ name: 'sales-confirmations-bm-detail-po', params: { id: row.id_poc } })"
                  title="Detail">
                  <Lucide icon="Info" class="w-5 h-5" />
                </button>
              </Table.Td>
            </Table.Tr>
  
            <Table.Tr v-if="!rows.length">
              <Table.Td colspan="8" class="text-center py-6 text-slate-500">Data tidak ditemukan.</Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
  
      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4 intro-y">
        <div class="text-slate-500">Page {{ page }} of {{ lastPage }}</div>
        <Pagination>
          <Pagination.Link :disabled="page === 1" @click="fetchData(page - 1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link v-for="p in lastPage" :key="p" :active="p === page" @click="fetchData(p)">
            {{ p }}
          </Pagination.Link>
          <Pagination.Link :disabled="page === lastPage" @click="fetchData(page + 1)">
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
  import Button from '@/components/Base/Button'
  import Table from '@/components/Base/Table'
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  
  const rows = ref<any[]>([])
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const lastPage = ref(1)
  
  function fmtDate(d?: string){ return d ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' }) : '-' }
  function fmtDateTime(d?: string){ if (!d) return '-'; const dt = new Date(d.replace(' ','T')); return dt.toLocaleString('id-ID', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' }) }
  function fmtNumber(n: number | string){ const x = Number(n ?? 0); return isNaN(x) ? '-' : x.toLocaleString('id-ID') }
  function fmtCurrency(n: number | string){ const x = Number(n ?? 0); return isNaN(x) ? 'Rp 0' : `Rp ${x.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` }
  function kode(k?: string|null){ return k ? `E-${k}`.replace(/^E-?/, 'E-') : '-' }
  function docCode(row:any){ return `PO-${String(row.id_poc || 0).padStart(5,'0')}` }
  
  function progressPct(row:any){
    const vol = Number(row.volume_liter || row.volume_poc || 0) // liter
    const ship = Number(row.shipped_liter || 0)
    if (vol <= 0) return 0
    const pct = (ship / vol) * 100
    return Math.max(0, Math.min(100, pct))
  }
  
  function plan(row:any) {
  router.push({ name: 'po-customer-plan', params: { id: row.id_poc } })
}
  
  async function fetchData(toPage = 1){
    try{
      const { data } = await axios.get('/api/sales-confirmations', {
        params: {
          page: toPage,
          per_page: perPage.value,
          search: q.value || undefined,
          disposisi: 2, // hanya yang Terverifikasi BM
        }
      })
      // siapkan field tampilan seperti tabel kiri
      rows.value = (data.data || []).map((r:any) => ({
        ...r,
        // pastikan liter & harga per liter tersedia
        volume_liter: Number(r.volume_poc_liter ?? r.volume_poc ?? 0), // backendmu mungkin kirim volume_m3 sebelumnya
        harga_per_liter: Number(r.harga_poc_per_liter ?? r.harga_poc ?? 0),
        shipped_liter: Number(r.shipped_liter ?? 0),
        book_remaining_liter: Number(r.book_remaining_liter ?? 0),
        close_volume_liter: Number(r.close_volume_liter ?? 0),
      }))
      page.value = data.current_page
      lastPage.value = data.last_page
    }catch(e:any){
      Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
    }
  }
  
  onMounted(() => fetchData(1))
  watch(perPage, () => fetchData(1))
  watch(q, debounce(() => fetchData(1), 300))
  </script>
  