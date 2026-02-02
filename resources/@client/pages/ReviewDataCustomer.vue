<template>
    <div class="min-h-screen bg-slate-50">
      <!-- Header -->
      <div class="bg-emerald-700 text-white">
        <div class="max-w-7xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Review Data Customer</div>
          <div class="text-white/80 text-sm">Validasi hasil update dari form publik</div>
        </div>
      </div>
  
      <div class="max-w-7xl mx-auto px-5 py-6 space-y-4">
        <!-- Tabs -->
        <div class="flex items-center gap-2">
          <button
            class="px-3 py-2 rounded border"
            :class="status==='unreviewed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white'"
            @click="setStatus('unreviewed')"
          >
            Unreviewed Data
            <span class="ml-2 inline-flex h-5 min-w-[20px] px-1 items-center justify-center rounded-full bg-slate-700 text-white text-xs">
              {{ stats.unreviewed }}
            </span>
          </button>
  
          <button
            class="px-3 py-2 rounded border"
            :class="status==='reviewed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white'"
            @click="setStatus('reviewed')"
          >
            Reviewed
            <span class="ml-2 inline-flex h-5 min-w-[20px] px-1 items-center justify-center rounded-full bg-slate-700 text-white text-xs">
              {{ stats.reviewed }}
            </span>
          </button>
  
          <div class="ml-auto flex items-center gap-2">
            <input v-model="q" class="form w-80" placeholder="Customer keywords..." @keyup.enter="fetchList(1)" />
            <span class="text-slate-500">Show</span>
            <select v-model.number="perPage" class="form w-24" @change="fetchList(1)">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <button class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700" @click="fetchList(1)">Cari</button>
          </div>
        </div>
  
        <!-- Tabel -->
        <div class="bg-white rounded shadow overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-slate-700">
              <tr>
                <th class="px-4 py-2 text-left">No</th>
                <th class="px-4 py-2 text-left">Kode Review</th>
                <th class="px-4 py-2 text-left">Kode Link</th>
                <th class="px-4 py-2 text-left">Kode Customer</th>
                <th class="px-4 py-2 text-left">Customer</th>
                <th class="px-4 py-2 text-left">Alamat</th>
                <th class="px-4 py-2 text-left">Telp</th>
                <th class="px-4 py-2 text-left">Fax</th>
                <th class="px-4 py-2 text-left">Disposisi</th>
                <th class="px-4 py-2 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Memuat…</td>
              </tr>
  
              <tr v-for="(r, i) in rows" :key="r.id_verification" class="border-t">
                <td class="px-4 py-2">{{ (page-1)*perPage + i + 1 }}</td>
                <td class="px-4 py-2">RC{{ r.id_verification }}</td>
                <td class="px-4 py-2">
                  <RouterLink v-if="r.token_verification" :to="`/verify/${r.token_verification}`" class="text-sky-600 hover:underline" target="_blank">
                    LC{{ r.id_verification }}
                  </RouterLink>
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-2">{{ r.customer?.kode_pelanggan || '-------' }}</td>
                <td class="px-4 py-2">{{ r.customer?.nama_perusahaan || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.alamat_perusahaan || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.telepon || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.fax || '-' }}</td>
                <td class="px-4 py-2">
            <span
              v-if="Number(r.disposisi_result) > 0"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700"
            >
              {{ stageLabel(r.disposisi_result) }}
            </span>
            <span v-else class="text-slate-400">-</span>
          </td>
  
                <!-- Aksi -->
                <td class="px-4 py-2">
            <div class="flex gap-2">
              <!-- Unreviewed: tetap tombol Review -->
              <RouterLink
                v-if="status==='unreviewed'"
                :to="{ name: 'review-customer-detail', params: { id: r.id_verification } }"
                class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"
              >
                Review
              </RouterLink>

              <!-- Reviewed: tombol Persetujuan, disabled jika disposisi > 0 -->
              <button
                v-else
                class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="Number(r.disposisi_result) > 0"
                :title="Number(r.disposisi_result) > 0 ? 'Sudah diproses di ' + stageLabel(r.disposisi_result) : 'Kirim persetujuan'"
                @click="approve(r)"
              >
                Persetujuan
              </button>
            </div>
          </td>
              </tr>
  
              <tr v-if="!loading && rows.length===0">
                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
  
        <!-- Pagination -->
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-600">Halaman {{ page }} / {{ lastPage }} (total {{ total }})</div>
          <div class="flex gap-2">
            <button class="px-3 py-1 rounded border" :disabled="page<=1" @click="fetchList(page-1)">Prev</button>
            <button class="px-3 py-1 rounded border" :disabled="page>=lastPage" @click="fetchList(page+1)">Next</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'    
  
  const STAGE_MAP: Record<number,string> = {
  1: 'Tahap Verifikasi Admin',
  2: 'Tahap Verifikasi Logistik',
  3: 'Tahap Verifikasi BM',
  4: 'Tahap Verifikasi OM',
}
function stageLabel(v: any){
  const n = Number(v) || 0
  return STAGE_MAP[n] || `Tahap ${n}`
}
  
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const rows = ref<any[]>([])
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  const status = ref<'unreviewed'|'reviewed'>('unreviewed')
  
  // badge count
  const stats = ref<{unreviewed:number; reviewed:number}>({ unreviewed: 0, reviewed: 0 })
  
  function setStatus(s:'unreviewed'|'reviewed'){
    if (status.value !== s){
      status.value = s
      fetchList(1)
    }
  }
  
  async function fetchStats(){
    const { data } = await axios.get('/api/review/customer-verifications/stats')
    stats.value = data
  }
  
  async function fetchList(p=1) {
    loading.value = true
    try {
      page.value = p
      const { data } = await axios.get('/api/review/customer-verifications', {
        params: {
          status: status.value,
          q: q.value,
          per_page: perPage.value,
          page: p,
        }
      })
      rows.value = data.data
      total.value = data.total
      lastPage.value = data.last_page
    } finally {
      loading.value = false
    }
  }
  
  // === tombol Persetujuan (tab Reviewed) ===
  async function approve(r:any){
    const ok = await Swal.fire({
      icon: 'question',
      title: 'Kirim Persetujuan?',
      text: 'Apakah Anda yakin ingin mengirim data persetujuan?',
      showCancelButton: true,
      confirmButtonText: 'Ya, kirim',
      cancelButtonText: 'Batal',
    })
    if (!ok.isConfirmed) return
  
    try {
      await axios.patch(`/api/review/customer-verifications/${r.id_verification}/approve`)
      await fetchStats()
      await fetchList(page.value) // tetap di halaman yang sama
      Swal.fire('Berhasil', 'Persetujuan sudah dikirim.', 'success')
    } catch (e:any) {
      Swal.fire('Error', e?.response?.data?.message || 'Gagal mengirim persetujuan', 'error')
    }
  }
  
  watch(perPage, () => fetchList(1))
  onMounted(async () => {
    await fetchStats()
    await fetchList(1)
  })
  </script>
  
  <style scoped>
  .form { @apply border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-emerald-200; }
  </style>
  