<template>
    <div class="p-6 bg-slate-100 min-h-screen">
      <div class="max-w-5xl mx-auto space-y-6">
  
        <!-- Judul Halaman -->
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-semibold text-slate-700">Stock Inventory</h1>
        </div>
  
        <!-- Filter Produk -->
        <div class="bg-white shadow rounded-lg p-4 flex items-center space-x-4">
          <label class="font-medium text-slate-600">Filter Produk:</label>
          <select
  v-model="selectedProductId"
  class="border rounded px-3 py-2"
>
            <option :value="0">— Semua Produk —</option>
            <option
              v-for="p in productList"
              :key="p.produk_id"
              :value="p.produk_id"
            >
              {{ p.nama_produk }}
            </option>
          </select>
          <button
            @click="resetFilter"
            class="ml-auto text-sm text-blue-600 hover:underline"
          >Reset Filter</button>
        </div>
  
        <!-- Grafik Bar (per Produk terpilih) -->
        <StockChart
          v-if="filteredStocks.length > 0"
          :data="chartData"
          class="bg-white shadow rounded-lg p-4"
        />
  
        <!-- Tabel Stock -->
        <div class="bg-white shadow-lg rounded-lg p-6">
          <h2 class="text-lg font-medium mb-4">Daftar Stok</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                  <th class="px-4 py-2 text-left">Waktu Masuk</th>
                  <th class="px-4 py-2 text-left">Nomor PO</th>
                  <th class="px-4 py-2 text-left">Produk</th>
                  <th class="px-4 py-2 text-right">Volume</th>
                  <th class="px-4 py-2 text-right">Harga Tebus</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 text-sm">
                <tr v-for="row in filteredStocks" :key="row.id" class="hover:bg-slate-100">
                  <td class="px-4 py-2">{{ formatDateTime(row.created_at) }}</td>
                  <td class="px-4 py-2">{{ row.nomor_po }}</td>
                  <td class="px-4 py-2">{{ row.produk_label }}</td>
                  <td class="px-4 py-2 text-right">{{ formatNumber(row.volume) }}</td>
                  <td class="px-4 py-2 text-right">{{ formatCurrency(row.harga_tebus) }}</td>
                </tr>
                <tr v-if="filteredStocks.length === 0">
                  <td class="px-4 py-2 text-center text-gray-500" colspan="5">
                    Tidak ada data stok untuk produk ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
  
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted } from 'vue'
  import axios from 'axios'
  import StockChart from '@/components/StockChart.vue' // komponen chart yang sudah dibuat
  import Swal from 'sweetalert2'
  
  interface StockRow {
    id: number
    produk_id: number
    nama_produk: string
    produk_label: string  
    nomor_po: string
    volume: number
    harga_tebus: number
    created_at: string
  }
  
  const stocks = ref<StockRow[]>([])
  const selectedProductId = ref<number>(0)
  const productList = ref<{ produk_id: number; nama_produk: string }[]>([])
  const loading = ref(false)
  
  onMounted(fetchStocks)
  
  async function fetchStocks() {
    loading.value = true
    try {
      const res = await axios.get('/api/stocks')
      stocks.value = res.data
  
      // Hitung daftar produk unik untuk dropdown
      const mapProd = new Map<number, string>()
for (const r of stocks.value) {
  if (!mapProd.has(r.produk_id)) {
    mapProd.set(r.produk_id, r.produk_label)
  }
}
      productList.value = Array.from(mapProd.entries()).map(([id, name]) => ({
        produk_id: id,
        nama_produk: name
      }))
    } catch (err: any) {
      Swal.fire('Error', err.response?.data?.message || 'Gagal memuat stok', 'error')
    } finally {
      loading.value = false
    }
  }
  
  // Data yang sudah difilter berdasarkan produk terpilih
  const filteredStocks = computed<StockRow[]>(() => {
    if (selectedProductId.value === 0) {
      return stocks.value
    }
    return stocks.value.filter(r => r.produk_id === selectedProductId.value)
  })
  
  // Data untuk Chart.js: kita tampilkan total volume per produk terpilih
  const chartData = computed(() => {
  if (selectedProductId.value === 0) {
    const sums = new Map<string, number>()
    for (const r of filteredStocks.value) {
      const key = r.produk_label
      sums.set(key, (sums.get(key) || 0) + r.volume)
    }
    return Array.from(sums.entries()).map(([name, vol]) => ({
      nama_produk: name,
      volume: vol
    }))
  } else {
    return filteredStocks.value.map(r => ({
      nama_produk: r.produk_label,
      volume: r.volume
    }))
  }
})

  
  // Reset filter
  function resetFilter() {
    selectedProductId.value = 0
  }
  
  // Jika dropdown berubah, chart & tabel otomatis recalculated berkat computed()
  
  // Format angka ribuan
  function formatNumber(v: number | string = 0) {
    const n = typeof v === 'string' ? parseFloat(v) : v
    return isNaN(n) ? '-' : n.toLocaleString('id-ID')
  }
  // Format mata uang
  function formatCurrency(v: number | string = 0) {
    const n = typeof v === 'string' ? parseFloat(v) : v
    return isNaN(n) ? '-' : `Rp. ${n.toLocaleString('id-ID')}`
  }
  // Format tanggal + jam
  function formatDateTime(v: string) {
    if (!v) return '-'
    const d = new Date(v)
    return d.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    }) + '   ' + 
           d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' })
  }
  </script>
  