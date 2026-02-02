<template>
    <div class="p-6">
      <!-- Title -->
      <div class="text-xl font-semibold mb-4">Customer Link</div>
  
      <!-- Search bar -->
      <div class="flex items-center gap-2 mb-4">
        <div class="relative w-full max-w-xl">
          <input
            v-model="search"
            type="text"
            placeholder="Keywords..."
            class="w-full border rounded-lg px-4 py-2 pr-10 focus:outline-none focus:ring focus:ring-primary/30"
          />
          <Lucide icon="Search" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-500" />
        </div>
  
        <div class="ml-auto flex items-center gap-2">
          <span class="text-sm text-slate-600">Show</span>
          <select v-model.number="perPage" class="border rounded px-2 py-1">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
          <span class="text-sm text-slate-600">Data</span>
        </div>
      </div>
  
      <!-- Header pill -->
      <div class="mb-3">
  <RouterLink
    :to="{ name: 'link-customers' }"
    class="inline-flex items-center bg-sky-600 text-white px-4 py-2 rounded"
  >
    List Link
    <span class="ml-2 text-xs bg-white/20 px-2 py-0.5 rounded-full">{{ needUpdateTotal }}</span>
  </RouterLink>
</div>
      <!-- Table -->
      <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-slate-100 text-slate-700">
              <th class="px-3 py-2 text-left w-14">No</th>
              <th class="px-3 py-2 text-left">Kode Link</th>
              <th class="px-3 py-2 text-left">Kode Customer</th>
              <th class="px-3 py-2 text-left">Customer</th>
              <th class="px-3 py-2 text-left">Alamat</th>
              <th class="px-3 py-2 text-left">Disposisi</th>
              <th class="px-3 py-2 text-left w-36">Aksi</th>
            </tr>
          </thead>
  
          <tbody v-if="!loading">
            <tr
              v-for="(row, i) in rows"
              :key="row.id_verification"
              class="border-t align-top"
            >
              <!-- No -->
              <td class="px-3 py-3">
                {{ (page - 1) * perPage + i + 1 }}
              </td>
  
              <!-- Kode Link (contoh: LC{id}) -->
              <td class="px-3 py-3">
                <RouterLink
  :to="{ name: 'verify-customer', params: { token: row.token_verification } }"
  target="_blank"
  class="text-sky-600 hover:underline"
>
  {{ `LC${row.id_verification}` }}
</RouterLink>
              </td>
  
              <!-- Kode Customer -->
              <td class="px-3 py-3">
                {{ row.customer?.kode_pelanggan ?? '-------' }}
              </td>
  
              <!-- Customer (nama + email) -->
              <td class="px-3 py-3">
                <div class="font-semibold uppercase">
                  {{ row.customer?.nama_perusahaan || '-' }}
                </div>
                <div class="text-slate-600">
                  {{ row.customer?.email || '-' }}
                </div>
              </td>
  
              <!-- Alamat -->
              <td class="px-3 py-3">
                <div class="whitespace-pre-line">
                  {{ fullAlamat(row) }}
                </div>
              </td>
  
              <!-- Disposisi -->
              <td class="px-3 py-3">
                <span
                  class="inline-flex items-center px-2 py-1 text-xs font-medium rounded"
                  :class="statusBadgeClass(row)"
                >
                  {{ statusLabel(row) }}
                </span>
              </td>
  
              <!-- Aksi -->
              <td class="px-3 py-3">
                <div class="flex gap-2">
                  <button
                    class="px-3 py-1 rounded bg-sky-500 text-white hover:bg-sky-600"
                    @click="sendEmail(row)"
                    :disabled="sendingId === row.id_verification"
                  >
                    {{ sendingId === row.id_verification ? 'Sending…' : 'Email' }}
                  </button>
  
                  <!-- <RouterLink
                    :to="{ name: 'customer-verifications-edit', params: { id: row.id_verification } }"
                    class="px-3 py-1 rounded bg-slate-200 text-slate-700 hover:bg-slate-300"
                  >
                    Revisi
                  </RouterLink> -->
                </div>
              </td>
            </tr>
  
            <tr v-if="rows.length === 0">
              <td colspan="7" class="px-3 py-6 text-center text-slate-500">
                Tidak ada data.
              </td>
            </tr>
          </tbody>
  
          <!-- Skeleton rows saat loading -->
          <tbody v-else>
            <tr v-for="n in 5" :key="n" class="border-t">
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-8"></div></td>
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-24"></div></td>
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-24"></div></td>
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-48"></div></td>
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-64"></div></td>
              <td class="px-3 py-3"><div class="h-4 bg-slate-200 rounded w-28"></div></td>
              <td class="px-3 py-3">
                <div class="flex gap-2">
                  <div class="h-8 bg-slate-200 rounded w-16"></div>
                  <div class="h-8 bg-slate-200 rounded w-16"></div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <!-- Pagination -->
      <div class="flex items-center justify-between mt-4 text-sm text-slate-600">
        <div>Page {{ page }} of {{ lastPage }}</div>
        <div class="flex gap-1">
          <button
            class="px-3 py-1 border rounded disabled:opacity-50"
            :disabled="page === 1 || loading"
            @click="go(page - 1)"
          >
            Prev
          </button>
          <button
            class="px-3 py-1 border rounded disabled:opacity-50"
            :disabled="page === lastPage || loading"
            @click="go(page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, watch, onMounted } from 'vue'
  import axios from 'axios'
  import { debounce } from 'lodash'
  import { RouterLink } from 'vue-router'
  import Lucide from '@/components/Base/Lucide'
  
  // state
  const rows      = ref<any[]>([])
  const page      = ref(1)
  const perPage   = ref(10)
  const lastPage  = ref(1)
  const needUpdateTotal = ref(0) // <-- ini
  const search    = ref('')
  const loading   = ref(false)
  const sendingId = ref<number | null>(null)
  
  // fetch
  async function fetchList(p = 1) {
    loading.value = true
    try {
      const { data } = await axios.get('/api/customer-verifications', {
        params: {
          page: p,
          per_page: perPage.value,
          search: search.value || undefined,
        }
      })
      rows.value     = data.data ?? []
      page.value     = data.current_page ?? 1
      lastPage.value = data.last_page ?? 1
      needUpdateTotal.value = data.need_update_count ?? 0 // <-- ambil angka
    } finally {
      loading.value = false
    }
  }
  
  function go(p: number) {
    if (p < 1 || p > lastPage.value) return
    fetchList(p)
  }
  
  watch(perPage, () => fetchList(1))
  watch(search, debounce(() => fetchList(1), 350))
  
  onMounted(() => fetchList(1))
  
  // helpers
  function kodeLink(row: any) {
    // contoh format: LC{ID}
    return `LC${row.id_verification}`
  }
  
  function fullAlamat(row: any) {
    // ambil dari relasi customer, fallback '-'
    const c = row.customer || {}
    // sesuaikan jika nama kolom alamat berbeda
    return c.alamat_perusahaan || '-'
  }
  
  function statusLabel(row: any) {
    // mapping sederhana dari disposisi_result
    const d = Number(row.disposisi_result ?? 0)
    if (d === 0) return 'Terdaftar'
    if (d === 1) return 'Tahap Verifikasi'
    if (d === 2) return 'Verifikasi OM'
    if (d === 3) return 'Ditolak OM'
    if (d === 4) return 'Disetujui OM'
    return '-'
  }
  function statusBadgeClass(row: any) {
    const d = Number(row.disposisi_result ?? 0)
    if (d === 0) return 'bg-slate-100 text-slate-700'
    if (d === 1) return 'bg-amber-100 text-amber-700'
    if (d === 2) return 'bg-blue-100 text-blue-700'
    if (d === 3) return 'bg-rose-100 text-rose-700'
    if (d === 4) return 'bg-emerald-100 text-emerald-700'
    return 'bg-slate-100 text-slate-700'
  }
  
  // actions
  async function sendEmail(row: any) {
    if (sendingId.value) return
    sendingId.value = row.id_verification
    try {
      // SESUAIKAN endpoint jika berbeda
      await axios.post(`/api/customer-verifications/${row.id_verification}/send-email`)
      alert('Email terkirim.')
    } catch (e:any) {
      alert(e?.response?.data?.message || 'Gagal mengirim email (sesuaikan endpoint).')
    } finally {
      sendingId.value = null
    }
  }
  </script>
  