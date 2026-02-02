<template>
    <div class="min-h-screen bg-slate-50">
      <div class="bg-green-700 text-white">
        <div class="max-w-7xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Verifikasi Data Customer — Logistik</div>
          <div class="text-white/80 text-sm">Menampilkan data dengan status: is_reviewed = 1 & disposisi = Logistik</div>
        </div>
      </div>
  
      <div class="max-w-7xl mx-auto px-5 py-6 space-y-4">
        <div class="flex items-center gap-2">
          <input v-model="q" class="form w-80" placeholder="Customer keywords..." @keyup.enter="fetchList(1)" />
          <span class="text-slate-500">Show</span>
          <select v-model.number="perPage" class="form w-24" @change="fetchList(1)">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <button class="px-3 py-2 rounded bg-green-600 text-white hover:bg-green-700" @click="fetchList(1)">Cari</button>
          <div class="ml-auto text-sm text-slate-600">Antrean Logistik: <span class="font-semibold">{{ stats.queue }}</span></div>
        </div>
  
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
                <td colspan="10" class="px-4 py-6 text-center text-slate-500">Memuat…</td>
              </tr>
  
              <tr v-for="(r, i) in rows" :key="r.id_verification" class="border-t">
                <td class="px-4 py-2">{{ (page-1)*perPage + i + 1 }}</td>
                <td class="px-4 py-2">RC{{ r.id_verification }}</td>
  
                <td class="px-4 py-2">
                  <RouterLink v-if="r.token_verification" :to="`/verify/${r.token_verification}`" class="text-green-600 hover:underline" target="_blank">
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
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                    Logistik
                  </span>
                </td>
  
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <!-- arahkan ke halaman detail logistik -->
                    <RouterLink
                      :to="{ name: 'review-customer-detail-logistik', params: { id: r.id_verification } }"
                      class="px-3 py-1 rounded border hover:bg-slate-50"
                    >
                      Detail
                    </RouterLink>
  
                    <button class="px-3 py-1 rounded bg-amber-600 text-white hover:bg-amber-700" title="Teruskan ke Branch Manager" @click="toBM(r)">
                      Ke BM
                    </button>
  
                    <button class="px-3 py-1 rounded bg-slate-200 hover:bg-slate-300" title="Kembalikan ke Admin" @click="toAdmin(r)">
                      Ke Admin
                    </button>
                  </div>
                </td>
              </tr>
  
              <tr v-if="!loading && rows.length===0">
                <td colspan="10" class="px-4 py-6 text-center text-slate-500">Tidak ada data</td>
              </tr>
            </tbody>
          </table>
        </div>
  
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
  
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const rows = ref<any[]>([])
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  
  const stats = ref<{queue:number}>({ queue: 0 })
  
  async function fetchStats(){
    const { data } = await axios.get('/api/review/logistik/customer-verifications/stats')
    stats.value = data
  }
  
  async function fetchList(p=1) {
    loading.value = true
    try {
      page.value = p
      const { data } = await axios.get('/api/review/logistik/customer-verifications', {
        params: { q: q.value, per_page: perPage.value, page: p }
      })
      rows.value = data.data
      total.value = data.total
      lastPage.value = data.last_page
    } finally {
      loading.value = false
    }
  }
  
  async function toBM(r:any){
    const ok = await Swal.fire({
      icon: 'question',
      title: 'Teruskan ke BM?',
      text: 'Disposisi akan diubah ke tahap Branch Manager.',
      showCancelButton: true,
      confirmButtonText: 'Ya, teruskan',
    })
    if (!ok.isConfirmed) return
    await axios.patch(`/api/review/logistik/customer-verifications/${r.id_verification}/set-disposisi`, { to: 3 })
    await Promise.all([fetchStats(), fetchList(page.value)])
    Swal.fire('Berhasil', 'Data diteruskan ke BM.', 'success')
  }
  
  async function toAdmin(r:any){
    const ok = await Swal.fire({
      icon: 'question',
      title: 'Kembalikan ke Admin?',
      showCancelButton: true,
      confirmButtonText: 'Ya',
    })
    if (!ok.isConfirmed) return
    await axios.patch(`/api/review/logistik/customer-verifications/${r.id_verification}/set-disposisi`, { to: 1 })
    await Promise.all([fetchStats(), fetchList(page.value)])
    Swal.fire('Berhasil', 'Data dikembalikan ke Admin.', 'success')
  }
  
  watch(perPage, () => fetchList(1))
  onMounted(async () => {
    await fetchStats()
    await fetchList(1)
  })
  </script>
  
  <style scoped>
  .form { @apply border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-green-200; }
  </style>
  