<template>
    <div class="p-6 intro-y">
      <!-- Header & Add New -->
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Customer LCR</h2>
        <RouterLink :to="{ name: 'lcr-create' }" class="ml-auto">
          <Button variant="primary">Add New LCR</Button>
        </RouterLink>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
        <div class="mx-auto text-slate-500">
          Page {{ currentPage }} of {{ totalPages }}
        </div>
        <FormInput
          v-model="searchQuery"
          placeholder="Search…"
          class="w-56 ml-auto pr-10 !box"
          @keyup.enter="fetchLcrs(1)"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
        <FormSelect v-model.number="perPage" class="w-20 ml-2 !box">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
  
      <!-- Data List -->
      <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
  <tr>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Kode LCR</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Customer</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Surveyor</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Lokasi</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Keterangan</th>
    <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
    <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
  </tr>
</thead>
<tbody class="divide-y divide-slate-200">
  <tr v-for="(row, idx) in lcrs" :key="row.id_lcr" class="hover:bg-slate-100">
    <!-- No -->
    <td class="px-4 py-3 whitespace-nowrap">
      {{ (currentPage - 1) * perPage + idx + 1 }}
    </td>

    <!-- Kode LCR -->
    <td class="px-4 py-3 whitespace-nowrap font-semibold">
      {{ kodeLcr(row.id_lcr) }}
    </td>

    <!-- Customer -->
    <td class="px-4 py-3">
      <div class="font-semibold">
        {{ row.customer?.kode ? row.customer.kode : `C-${row.id_customer}` }}
      </div>
      <div class="text-slate-600 text-sm">
        {{ row.customer?.nama_perusahaan || '-' }}
      </div>
    </td>

    <!-- Surveyor -->
    <td class="px-4 py-3">
      <div>• {{ (row.nama_surveyor && row.nama_surveyor[0]) ? row.nama_surveyor[0] : '-' }}</div>
      <div class="text-xs text-slate-500">Tanggal {{ row.tgl_survey || '-' }}</div>
    </td>

    <!-- Lokasi -->
    <td class="px-4 py-3">
      <div class="max-w-[360px] truncate" :title="row.alamat_survey">{{ row.alamat_survey || '-' }}</div>
      <div v-if="row.latitude_lokasi && row.longitude_lokasi" class="text-xs text-slate-500">
        {{ Number(row.latitude_lokasi).toFixed(6) }}, {{ Number(row.longitude_lokasi).toFixed(6) }}
      </div>
    </td>

    <!-- Keterangan -->
    <td class="px-4 py-3 text-sm">
      <div>Jarak Jetty : {{ row.jarak_depot || '-' }} Km</div>
      <div>Truck Max : {{ row.max_truk || '-' }}</div>
    </td>

    <!-- Status -->
    <td class="px-4 py-3">
      <span class="inline-flex items-center rounded px-2 py-0.5 text-xs" :class="statusClass(row)">
        {{ statusText(row) }}
      </span>
    </td>

    <!-- Aksi -->
    <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
  <!-- EDIT: selalu tampil -->
  <RouterLink
    :to="{ name: 'lcr-edit', params: { id: row.id_lcr } }"
    class="text-blue-600 hover:text-blue-800 mx-2"
  >
    <Lucide icon="Edit" class="w-5 h-5"/>
  </RouterLink>

  <span v-if="canDelete(row)" class="text-slate-300">|</span>

  <!-- DELETE: sembunyikan kalau sudah approved -->
  <button
    v-if="canDelete(row)"
    @click="confirmDelete(row.id_lcr)"
    class="text-red-600 hover:text-red-800 mx-2"
  >
    <Lucide icon="Trash2" class="w-5 h-5"/>
  </button>
</td>

  </tr>

  <tr v-if="!lcrs.length">
    <td colspan="8" class="text-center text-slate-500 py-6">Tidak ada data</td>
  </tr>
</tbody>

        </table>
      </div>
  
      <!-- Pagination -->
      <div class="flex justify-center mt-5 intro-y">
        <Pagination>
          <Pagination.Link :disabled="currentPage===1" @click="fetchLcrs(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchLcrs(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchLcrs(currentPage+1)">
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
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  const lcrs         = ref<any[]>([])
  const loading      = ref(false)
  const error        = ref<string|null>(null)
  const searchQuery  = ref('')
  const currentPage  = ref(1)
  const perPage      = ref(10)
  const totalPages   = ref(1)
  
  async function fetchLcrs(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const res = await axios.get('/api/customer-lcrs', {
        params: {
          page,
          per_page: perPage.value,
          q: searchQuery.value || undefined, // backend LCR pakai 'q'
        }
      })
      lcrs.value        = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value  = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Failed to load'
    } finally {
      loading.value = false
    }
  }
  
  async function confirmDelete(id: number) {
    const c = await Swal.fire({
      title: 'Delete this LCR?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete'
    })
    if (!c.isConfirmed) return
    await axios.delete(`/api/customer-lcrs/${id}`)
    lcrs.value = lcrs.value.filter((x) => x.id_lcr !== id)
    Swal.fire({ icon:'success', title:'Deleted', toast:true, position:'top-end', timer:1500 })
  }
  
  onMounted(() => fetchLcrs())
  
  watch(searchQuery, debounce(() => fetchLcrs(1), 300))
  watch(perPage, () => fetchLcrs(1))

  function kodeLcr(id: number) {
  return `LCR${String(id || 0).padStart(4, '0')}`
}
function statusText(r: any) {
  if (r.flag_disposisi === 2) return 'Disetujui Logistik'
  if (r.logistik_result === 1) return 'Verifikasi Logistik'
  if (r.logistik_result === 0) return 'Menunggu Verifikasi Logistik'
  return 'Draft'
}
function statusClass(r: any) {
  if (r.flag_approval === 1) return 'bg-emerald-100 text-emerald-700'
  if (r.logistik_result === 1) return 'bg-blue-100 text-blue-700'
  if (r.logistik_result === 0) return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-700'
}

function isApproved(r: any) {
  // anggap approved kalau flag_disposisi = 2 ATAU flag_approval = 1
  return r?.flag_disposisi === 2 || r?.flag_approval === 1
}
function canDelete(r: any) {
  return !isApproved(r)
}

  </script>
  