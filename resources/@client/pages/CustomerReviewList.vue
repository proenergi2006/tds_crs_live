<!-- src/views/CustomerVerification/ReviewDataCustomer.vue -->
<template>
    <div class="min-h-screen bg-slate-50">
      <!-- BAR -->
      <div class="bg-emerald-700 text-white">
        <div class="max-w-7xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Review Data Customer</div>
          <div class="text-white/80 text-sm">Validasi hasil update dari form publik</div>
        </div>
      </div>
  
      <div class="max-w-7xl mx-auto px-5 py-6 space-y-4">
        <!-- Tabs + badge -->
        <div class="flex items-center gap-2">
          <!-- Unreviewed -->
          <RouterLink
            :to="{ name: 'review-data-customer', query: { ...route.query, tab: 'unreviewed', page: 1 } }"
            class="px-3 py-2 rounded border"
            :class="tab==='unreviewed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white'"
          >
            Unreviewed Data
            <span
              class="ml-2 inline-flex h-5 min-w-[20px] px-1 items-center justify-center rounded-full bg-slate-700 text-white text-xs"
            >{{ unreviewCount }}</span>
          </RouterLink>
  
          <!-- Reviewed -->
          <button
            class="px-3 py-2 rounded border"
            :class="tab==='reviewed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white'"
            @click="setTab('reviewed')"
          >
            Reviewed
            <span
              class="ml-2 inline-flex h-5 min-w-[20px] px-1 items-center justify-center rounded-full bg-slate-700 text-white text-xs"
            >{{ stats.reviewed }}</span>
          </button>
  
          <div class="ml-auto flex items-center gap-2">
            <input
              v-model="q"
              type="text"
              placeholder="Customer keywords..."
              class="form w-72"
              @keyup.enter="applySearch"
            />
            <select v-model.number="perPage" class="form w-24" @change="applyPerPage">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <button
              class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
              @click="applySearch"
            >
              Cari
            </button>
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
                <th class="px-4 py-2 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in rows" :key="row.id_verification" class="border-t">
                <td class="px-4 py-2">{{ (currentPage-1)*perPage + i + 1 }}</td>
                <td class="px-4 py-2">RC{{ row.id_verification }}</td>
                <td class="px-4 py-2">
                  <RouterLink
                    v-if="row.token_verification"
                    :to="`/verify/${row.token_verification}`"
                    target="_blank"
                    class="text-sky-600 hover:underline"
                  >
                    LC{{ row.id_verification }}
                  </RouterLink>
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-2">{{ row.customer?.kode_pelanggan || '-------' }}</td>
                <td class="px-4 py-2">
                  <div class="font-semibold">{{ row.customer?.nama_perusahaan || '-' }}</div>
                  <div class="text-slate-500 text-xs">
                    {{ row.customer?.alamat_perusahaan || '-' }}
                  </div>
                </td>
                <td class="px-4 py-2">
                  <div class="flex gap-2">
                    <RouterLink
                      :to="{ name: 'CustomerUpdateForm', params: { id: row.id_verification } }"
                      class="px-3 py-1 rounded border hover:bg-slate-50"
                    >
                      Detail
                    </RouterLink>
  
                    <button
                      v-if="tab==='unreviewed'"
                      class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                      @click="markReviewed(row)"
                    >
                      Tandai Reviewed
                    </button>
                  </div>
                </td>
              </tr>
  
              <tr v-if="!rows.length">
                <td class="px-4 py-6 text-center text-slate-500" colspan="6">
                  Tidak ada data
                </td>
              </tr>
            </tbody>
          </table>
        </div>
  
        <!-- Pagination -->
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-600">
            Halaman {{ currentPage }} / {{ lastPage }} (total {{ total }})
          </div>
          <div class="flex gap-2">
            <button class="px-3 py-1 rounded border" :disabled="currentPage<=1" @click="goto(currentPage-1)">
              Prev
            </button>
            <button class="px-3 py-1 rounded border" :disabled="currentPage>=lastPage" @click="goto(currentPage+1)">
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch, computed } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  const route = useRoute()
  const router = useRouter()
  
  // state
  const rows = ref<any[]>([])
  const q = ref<string>('')
  const perPage = ref<number>(25)
  const currentPage = ref<number>(1)
  const lastPage = ref<number>(1)
  const total = ref<number>(0)
  const tab = ref<'unreviewed'|'reviewed'>('unreviewed')
  
  const stats = ref<{unreviewed:number; reviewed:number}>({ unreviewed: 0, reviewed: 0 })
  const unreviewCount = computed(() => stats.value.unreviewed ?? 0)
  
  // ---- data fetchers
  async function fetchStats() {
    const { data } = await axios.get('/api/review/customer-verifications/stats')
    stats.value = data
  }
  
  async function fetchList() {
    const { data } = await axios.get('/api/review/customer-verifications', {
  params: {
    status: tab.value,   // ← ini kalau backend tetap baca 'status'
    q: q.value,
    per_page: perPage.value,
    page: currentPage.value,
  }
})
  rows.value       = data.data
  currentPage.value= data.current_page
  lastPage.value   = data.last_page
  total.value      = data.total
}
  
  // ---- helpers to keep URL <-> state in sync
  function syncFromQuery() {
    tab.value = (route.query.tab as any) === 'reviewed' ? 'reviewed' : 'unreviewed'
    q.value = (route.query.q as string) || ''
    perPage.value = Number(route.query.per_page || 25)
    currentPage.value = Number(route.query.page || 1)
  }
  
  function pushQuery(partial: Record<string, any>) {
    router.replace({
      name: 'review-data-customer',
      query: {
        tab: tab.value,
        q: q.value || undefined,
        per_page: String(perPage.value),
        page: String(currentPage.value),
        ...partial,
      },
    })
  }
  
  // ---- UI actions
  function setTab(next: 'unreviewed'|'reviewed') {
    tab.value = next
    currentPage.value = 1
    pushQuery({ tab: next, page: 1 })
  }
  
  function applySearch() {
    currentPage.value = 1
    pushQuery({ q: q.value || undefined, page: 1 })
  }
  
  function applyPerPage() {
    currentPage.value = 1
    pushQuery({ per_page: String(perPage.value), page: 1 })
  }
  
  function goto(p: number) {
    currentPage.value = p
    pushQuery({ page: String(p) })
  }
  
  async function markReviewed(row: any) {
    const ok = await Swal.fire({
      icon: 'question',
      title: 'Tandai sebagai reviewed?',
      showCancelButton: true,
      confirmButtonText: 'Ya, simpan'
    })
    if (!ok.isConfirmed) return
    await axios.patch(`/api/customer-verifications/${row.id_verification}/set-reviewed`, { is_reviewed: 1 })
    await Promise.all([fetchStats(), fetchList()])
    Swal.fire('Tersimpan', 'Item sudah ditandai reviewed.', 'success')
  }
  
  // ---- lifecycle
  onMounted(async () => {
    syncFromQuery()
    await fetchStats()
    await fetchList()
  })
  
  // setiap query berubah (mis. via tombol, back/forward) → sync & reload
  watch(() => route.query, async () => {
    syncFromQuery()
    await fetchStats()
    await fetchList()
  })
  </script>
  
  <style scoped>
  .form { @apply border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-emerald-200; }
  </style>
  