<template>
    <div class="min-h-screen bg-slate-50">
      <div class="bg-green-700 text-white">
        <div class="max-w-7xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Verifikasi Data Customer — BM</div>
          <div class="text-white/80 text-sm">is_reviewed = 1 & disposisi = BM</div>
        </div>
      </div>
  
      <div class="max-w-7xl mx-auto px-5 py-6 space-y-4">
        <div class="flex items-center gap-2">
          <input v-model="q" class="form w-80" placeholder="Customer keywords..." @keyup.enter="fetchList(1)"/>
          <span class="text-slate-500">Show</span>
          <select v-model.number="perPage" class="form w-24" @change="fetchList(1)">
            <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option>
          </select>
          <button class="px-3 py-2 rounded bg-green-600 text-white hover:bg-green-700" @click="fetchList(1)">Cari</button>
          <div class="ml-auto text-sm text-slate-600">Antrean BM: <span class="font-semibold">{{ stats.queue }}</span></div>
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
              <tr v-if="loading"><td colspan="10" class="px-4 py-6 text-center text-slate-500">Memuat…</td></tr>
  
              <tr v-for="(r,i) in rows" :key="r.id_verification" class="border-t">
                <td class="px-4 py-2">{{ (page-1)*perPage + i + 1 }}</td>
                <td class="px-4 py-2">RC{{ r.id_verification }}</td>
                <td class="px-4 py-2">
                  <RouterLink v-if="r.token_verification" :to="`/verify/${r.token_verification}`" target="_blank" class="text-sky-600 hover:underline">
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
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">BM</span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <RouterLink
                      :to="{ name: 'bm-customer-detail', params:{ id: r.id_verification } }"
                      class="px-3 py-1 rounded border hover:bg-slate-50"
                    >Detail</RouterLink>
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
  
  const q = ref('')
  const perPage = ref(25)
  const page = ref(1)
  const rows = ref<any[]>([])
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  
  const stats = ref<{queue:number}>({ queue: 0 })
  
  async function fetchStats(){
    const { data } = await axios.get('/api/review/bm/customer-verifications/stats')
    stats.value = data
  }
  async function fetchList(p=1){
    loading.value = true
    try{
      page.value = p
      const { data } = await axios.get('/api/review/bm/customer-verifications', {
        params:{ q:q.value, per_page:perPage.value, page:p }
      })
      rows.value = data.data
      total.value = data.total
      lastPage.value = data.last_page
    } finally { loading.value = false }
  }
  watch(perPage, () => fetchList(1))
  onMounted(async()=>{ await fetchStats(); await fetchList(1) })
  </script>
  
  <style scoped>
  .form { @apply border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-green-200; }
  </style>
  