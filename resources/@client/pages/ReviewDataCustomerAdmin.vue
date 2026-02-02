<template>
    <div class="min-h-screen bg-slate-50">
      <!-- Header -->
      <div class="bg-emerald-700 text-white">
        <div class="max-w-7xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Review Data Customer — Admin</div>
          <div class="text-white/80 text-sm">
            Menampilkan data dengan status: is_reviewed = 1 & disposisi = Admin
          </div>
        </div>
      </div>
  
      <div class="max-w-7xl mx-auto px-5 py-6 space-y-4">
        <!-- Toolbar -->
        <div class="flex items-center gap-2">
          <input
            v-model="q"
            class="form w-80"
            placeholder="Customer keywords..."
            @keyup.enter="fetchList(1)"
          />
          <span class="text-slate-500">Show</span>
          <select v-model.number="perPage" class="form w-24" @change="fetchList(1)">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <button class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700" @click="fetchList(1)">
            Cari
          </button>
          <div class="ml-auto text-sm text-slate-600">
            Antrean Admin: <span class="font-semibold">{{ stats.queue }}</span>
          </div>
        </div>
  
        <!-- Table -->
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
                <!-- Kolom baru -->
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
                  <RouterLink
                    v-if="r.token_verification"
                    :to="`/verify/${r.token_verification}`"
                    class="text-sky-600 hover:underline"
                    target="_blank"
                  >
                    LC{{ r.id_verification }}
                  </RouterLink>
                  <span v-else>-</span>
                </td>
  
                <td class="px-4 py-2">{{ r.customer?.kode_pelanggan || '-------' }}</td>
                <td class="px-4 py-2">{{ r.customer?.nama_perusahaan || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.alamat_perusahaan || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.telepon || '-' }}</td>
                <td class="px-4 py-2">{{ r.customer?.fax || '-' }}</td>
  
                <!-- Disposisi -->
                <td class="px-4 py-2">
                  <span :class="badgeClass(disposisiValue(r))">
                    {{ stageText(disposisiValue(r)) }}
                  </span>
                </td>
  
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <RouterLink
                      :to="{ name: 'review-customer-detail', params: { id: r.id_verification } }"
                      class="px-3 py-1 rounded border hover:bg-slate-50"
                    >
                      Detail
                    </RouterLink>
                    <!-- Jika ingin tombol kirim ke Logistik tetap tersedia -->
                    <!--
                    <button class="px-3 py-1 rounded bg-sky-600 text-white hover:bg-sky-700"
                            v-if="disposisiValue(r) === 1" @click="toLogistik(r)">
                      Ke Logistik
                    </button>
                    -->
                  </div>
                </td>
              </tr>
  
              <tr v-if="!loading && rows.length===0">
                <td colspan="10" class="px-4 py-6 text-center text-slate-500">Tidak ada data</td>
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
  
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const rows = ref<any[]>([])
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  
  const stats = ref<{queue:number}>({ queue: 0 })
  
  async function fetchStats(){
    const { data } = await axios.get('/api/review/admin/customer-verifications/stats')
    stats.value = data
  }
  
  async function fetchList(p=1) {
    loading.value = true
    try {
      page.value = p
      const { data } = await axios.get('/api/review/admin/customer-verifications', {
        params: { q: q.value, per_page: perPage.value, page: p }
      })
      rows.value = data.data
      total.value = data.total
      lastPage.value = data.last_page
    } finally {
      loading.value = false
    }
  }
  
  async function toLogistik(r:any){
    const ok = await Swal.fire({
      icon: 'question',
      title: 'Teruskan ke Logistik?',
      text: 'Disposisi akan diubah ke tahap Logistik.',
      showCancelButton: true,
      confirmButtonText: 'Ya, teruskan',
    })
    if (!ok.isConfirmed) return
    await axios.patch(`/api/review/admin/customer-verifications/${r.id_verification}/set-disposisi`, { to: 2 })
    await Promise.all([fetchStats(), fetchList(page.value)])
    Swal.fire('Berhasil', 'Data diteruskan ke Logistik.', 'success')
  }
  
  /* ====== utils untuk kolom Disposisi ====== */
  function disposisiValue(row:any): number {
    // pakai 'disposisi_result' kalau ada, kalau tidak coba 'disposisi'
    const v = row?.disposisi_result ?? row?.disposisi
    return Number.isFinite(+v) ? +v : 0
  }
  
  function stageText(v:number){
    switch (Number(v)) {
      case 1: return 'Admin'
      case 2: return 'Logistik'
      case 3: return 'BM'
      case 4: return 'OM'
      default: return '-'
    }
  }
  
  function badgeClass(v:number){
    const base = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium'
    switch (Number(v)) {
      case 1: return `${base} bg-emerald-100 text-emerald-700`
      case 2: return `${base} bg-sky-100 text-sky-700`
      case 3: return `${base} bg-amber-100 text-amber-700`
      case 4: return `${base} bg-violet-100 text-violet-700`
      default: return `${base} bg-slate-100 text-slate-600`
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
  