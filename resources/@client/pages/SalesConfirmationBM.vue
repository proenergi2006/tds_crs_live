<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-semibold">Sales Confirmation — BM</h2>
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
        <Table class="min-w-[1100px] divide-y divide-slate-200">
          <Table.Thead>
            <Table.Tr>
              <Table.Th class="w-12 text-center">No</Table.Th>
              <Table.Th>Nama Customer</Table.Th>
              <Table.Th>Marketing</Table.Th>
              <Table.Th>Nomor / Tanggal PO</Table.Th>
              <Table.Th>Volume / Harga</Table.Th>
              <Table.Th>Disposisi</Table.Th>
              <Table.Th class="text-center w-16">Aksi</Table.Th>
            </Table.Tr>
          </Table.Thead>
  
          <Table.Tbody>
            <Table.Tr v-for="(row, i) in rows" :key="row.id_poc" class="hover:bg-slate-50">
              <Table.Td class="text-center">{{ (page - 1) * perPage + i + 1 }}</Table.Td>
  
              <Table.Td class="whitespace-nowrap">
                <span class="font-semibold">{{ kode(row.customer?.kode_pelanggan) }}</span>
                &nbsp;-&nbsp; {{ row.customer?.nama_perusahaan || '-' }}
              </Table.Td>
  
              <Table.Td class="whitespace-nowrap">{{ row.marketing_name || '-' }}</Table.Td>
  
              <Table.Td class="whitespace-nowrap">
                <div class="font-semibold">{{ row.nomor_poc || '-' }}</div>
                <div class="text-slate-500 text-xs">{{ fmtDate(row.tanggal_poc) }}</div>
              </Table.Td>
  
              <Table.Td class="whitespace-nowrap">
                <div>
                  {{ fmtNumber(row.volume_poc) }} m³
                  <span class="text-slate-500">({{ fmtCurrency(row.harga_poc) }}/m³)</span>
                </div>
              </Table.Td>
  
              <Table.Td class="whitespace-nowrap">
                <div class="font-medium" :class="badgeClass(row.disposisi)">
                  {{ row.disposisi_text }}
                </div>
                <div v-if="row.disposisi_time" class="text-slate-500 text-xs">
                  {{ fmtDateTime(row.disposisi_time) }} WIB
                </div>
              </Table.Td>
  
              <Table.Td class="text-center">
                <button class="text-sky-600 hover:text-sky-800"
  @click="$router.push({name:'sales-confirmations-bm-detail', params:{id: row.id_poc}})">
  <Lucide icon="Info" class="w-5 h-5"/>
</button>
              </Table.Td>
            </Table.Tr>
  
            <Table.Tr v-if="!rows.length">
              <Table.Td colspan="7" class="text-center py-6 text-slate-500">Data tidak ditemukan.</Table.Td>
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
          <Pagination.Link v-for="p in lastPage" :key="p" :active="p === page" @click="fetchData(p)">{{ p }}</Pagination.Link>
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
  
  const rows = ref<any[]>([])
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const lastPage = ref(1)
  
  function fmtDate(d?: string){ return d ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' }) : '-' }
  function fmtDateTime(d?: string){
    if (!d) return '-'
    const dt = new Date(d.replace(' ', 'T'))
    return dt.toLocaleString('id-ID', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' })
  }
  function fmtNumber(n: number | string){ const x = Number(n ?? 0); return isNaN(x) ? '-' : x.toLocaleString('id-ID') }
  function fmtCurrency(n: number | string){ const x = Number(n ?? 0); return isNaN(x) ? 'Rp 0' : `Rp ${x.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` }
  function kode(k?: string|null){ return k ? `E-${k}`.replace(/^E-?/, 'E-') : '-' }
  function badgeClass(v: number | string){
    const s = String(v)
    return {
      'text-slate-600': s === '0',
      'text-emerald-600': s === '1', // Menunggu Verifikasi Admin
      'text-orange-600': s === '2' || s === '3',
      'text-emerald-700': s === '4',
      'text-red-600': s === '5',
    }
  }
  
  async function fetchData(toPage = 1){
    try{
      const { data } = await axios.get('/api/sales-confirmations', {
        params: {
          page: toPage,
          per_page: perPage.value,
          search: q.value || undefined,
          disposisi: 2, // ⬅️ hanya BM
        }
      })
      rows.value = data.data
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
  