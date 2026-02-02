<template>
    <div class="p-6 intro-y">
      <!-- Header -->
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Verifikasi LCR – Logistik</h2>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap gap-2">
        <FormSelect v-model="status" class="w-56 !box">
          <option value="menunggu">Menunggu verifikasi</option>
          <option value="disetujui">Disetujui logistik</option>
          <option value="ditolak">Ditolak logistik</option>
          <option value="all">Semua status</option>
        </FormSelect>
  
        <FormInput
          v-model="searchQuery"
          placeholder="Search…"
          class="w-64 ml-auto pr-10 !box"
          @keyup.enter="fetchData(1)"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
  
        <FormSelect v-model.number="perPage" class="w-20 !box">
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
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Customer</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Alamat</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Tgl Survey</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(row, idx) in rows" :key="row.id_lcr" class="hover:bg-slate-50">
              <td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ row.customer?.nama_perusahaan || ('#' + row.id_customer) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ row.alamat_survey || '-' }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ formatDate(row.tgl_survey) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="px-2 py-1 rounded text-xs font-semibold"
                  :class="statusBadgeClassByFlag(row.flag_disposisi)"
                >
                  {{ statusLabelByFlag(row.flag_disposisi) }}
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
  <div class="flex items-center justify-center gap-2">
    <!-- Edit hanya saat MENUNGGU (1) -->
    <!-- <RouterLink
      v-if="row.flag_disposisi === 1"
      :to="{ name: 'lcr-edit', params: { id: row.id_lcr } }"
      class="text-blue-600 hover:text-blue-800 mx-2"
      title="Ubah LCR"
    >
      <Lucide icon="Edit" class="w-5 h-5"/>
    </RouterLink> -->
    <RouterLink
  :to="{ name: 'logistik-lcr-detail', params: { id: row.id_lcr } }"
  class="btn btn-outline-secondary"
  title="Lihat Detail LCR"
>
  <Lucide icon="Eye" class="w-4 h-4 mr-1" /> 
</RouterLink>
    <!-- TOMBOL VERIFIKASI hanya saat MENUNGGU (1) -->
    <template v-if="row.flag_disposisi === 1">
      <Button size="sm" :disabled="loading" @click="openVerify(row.id_lcr, 2)">
        <Lucide icon="CheckCircle" class="w-4 h-4 mr-1" /> Approve
      </Button>
      <Button size="sm" variant="danger" :disabled="loading" @click="openVerify(row.id_lcr, 3)">
        <Lucide icon="XCircle" class="w-4 h-4 mr-1" /> Reject
      </Button>
      <!-- <Button size="sm" variant="outline-secondary" :disabled="loading" @click="openVerify(row.id_lcr, 1)">
        <Lucide icon="HelpCircle" class="w-4 h-4 mr-1" /> Pending
      </Button> -->
      <!-- <Button size="sm" variant="outline-secondary" :disabled="loading" @click="resetVerify(row.id_lcr)">
        <Lucide icon="RotateCcw" class="w-4 h-4" />
      </Button> -->
    </template>

   
  </div>
</td>

            </tr>
  
            <tr v-if="!rows.length">
              <td colspan="6" class="text-center text-slate-500 py-6">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <!-- Pagination -->
      <div class="flex items-center mt-5 intro-y">
        <div class="text-slate-500 mr-auto">
          Page {{ currentPage }} of {{ totalPages }}
        </div>
        <Pagination>
          <Pagination.Link :disabled="currentPage===1 || loading" @click="fetchData(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            :disabled="loading"
            @click="fetchData(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages || loading" @click="fetchData(currentPage+1)">
            <Lucide icon="ChevronRight" />
          </Pagination.Link>
        </Pagination>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { debounce } from 'lodash'
  import { RouterLink } from 'vue-router'
  
  import Button from '@/components/Base/Button'
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  type Flag = 1 | 2 | 3
  type StatusFilter = 'menunggu' | 'disetujui' | 'ditolak' | 'all'
  
  interface LcrRow {
    id_lcr: number
    id_customer: number
    alamat_survey: string | null
    tgl_survey: string | null
    flag_disposisi: Flag | null
    customer?: { id_customer: number; nama_perusahaan: string }
  }
  
  const rows = ref<LcrRow[]>([])
  const loading = ref(false)
  const searchQuery = ref('')
  const status = ref<StatusFilter>('all')   // default "Semua status"
  const currentPage = ref(1)
  const perPage = ref(10)
  const totalPages = ref(1)
  
  function statusLabelByFlag(v: number | null | undefined) {
    if (v === 2) return 'Disetujui Logistik'
    if (v === 3) return 'Ditolak Logistik'
    return 'Menunggu Verifikasi' // 1 atau null
  }
  function statusBadgeClassByFlag(v: number | null | undefined) {
    if (v === 2) return 'bg-green-100 text-green-700'
    if (v === 3) return 'bg-red-100 text-red-700'
    return 'bg-amber-100 text-amber-700'
  }
  function formatDate(d?: string | null) {
    if (!d) return '-'
    const dt = new Date(d)
    return Number.isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString()
  }
  
  async function fetchData(page = 1) {
    loading.value = true
    try {
      const { data } = await axios.get('/api/customer-lcrs', {
        params: {
          page,
          per_page: perPage.value,
          q: searchQuery.value || undefined,
          status: status.value || undefined, // menunggu|disetujui|ditolak|all
        }
      })
      rows.value        = data.data
      currentPage.value = data.current_page
      totalPages.value  = data.last_page
    } catch (e: any) {
      const msg: string =
        (typeof e?.response?.data?.message === 'string' && e.response.data.message) ||
        (typeof e?.message === 'string' && e.message) ||
        'Failed to load'
      await Swal.fire({ icon: 'error', title: 'Gagal memuat', text: msg })
    } finally {
      loading.value = false
    }
  }
  
  /** Verifikasi (flag: 1=Menunggu, 2=Disetujui, 3=Ditolak) */
  async function openVerify(id: number, flag: Flag) {
    const titleMap: Record<Flag, string> = {
      1: 'Tandai Menunggu',
      2: 'Setujui LCR',
      3: 'Tolak LCR',
    }
    const { value: summary, isConfirmed } = await Swal.fire({
      title: titleMap[flag],
      input: 'textarea',
      inputLabel: 'Catatan (opsional)',
      inputPlaceholder: 'Tuliskan ringkasan/verdict…',
      inputAttributes: { 'aria-label': 'Catatan' },
      showCancelButton: true,
      confirmButtonText: 'Kirim',
      cancelButtonText: 'Batal',
    })
    if (!isConfirmed) return
  
    try {
      await axios.patch(`/api/customer-lcrs/${id}/set-flag`, {
        flag_disposisi: flag,
        logistik_summary: summary || null,
      })
      await Swal.fire({ icon: 'success', title: 'Tersimpan' })
      fetchData(currentPage.value)
    } catch (e: any) {
      const msg: string =
        (typeof e?.response?.data?.message === 'string' && e.response.data.message) ||
        (typeof e?.message === 'string' && e.message) ||
        'Gagal menyimpan'
      await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: msg })
    }
  }
  
  async function resetVerify(id: number) {
    const c = await Swal.fire({
      title: 'Reset ke Menunggu?',
      text: 'Status verifikasi akan dikembalikan ke Menunggu.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, reset',
      cancelButtonText: 'Batal',
    })
    if (!c.isConfirmed) return
  
    try {
      await axios.patch(`/api/customer-lcrs/${id}/reset-flag`)
      await Swal.fire({ icon: 'success', title: 'Status direset' })
      fetchData(currentPage.value)
    } catch (e: any) {
      const msg: string =
        (typeof e?.response?.data?.message === 'string' && e.response.data.message) ||
        (typeof e?.message === 'string' && e.message) ||
        'Gagal reset'
      await Swal.fire({ icon: 'error', title: 'Gagal reset', text: msg })
    }
  }
  
  onMounted(() => fetchData())
  watch(searchQuery, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  watch(status, () => fetchData(1))
  </script>
  