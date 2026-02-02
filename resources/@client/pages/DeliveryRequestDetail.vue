<template>
    <div class="p-6 space-y-4">
      <div class="text-xl font-semibold">Delivery Request Detail</div>
  
      <!-- Header -->
      <div class="border rounded bg-white p-4">
        <div class="grid md:grid-cols-4 gap-4 text-sm items-start">
          <div>
            <div class="text-slate-500">Kode DR</div>
            <div class="font-mono">{{ header.nomor_pr }}</div>
          </div>
          <div>
            <div class="text-slate-500">Tanggal</div>
            <div>{{ d(header.tanggal_pr) }}</div>
          </div>
          <div>
            <div class="text-slate-500">Disposisi</div>
            <span class="px-2 py-1 rounded text-xs" :class="dispClass(header.disposisi)">
              {{ header.disposisi_label }}
            </span>
          </div>
  
          <!-- Aksi kanan -->
          <div class="md:text-right space-x-2">
            <button
              v-if="Number(header.disposisi) !== 1"
              class="inline-flex items-center px-3 py-2 rounded bg-emerald-600 text-white disabled:opacity-40"
              :disabled="loading"
              @click="openVerify()"
            >
              <i class="fa fa-check mr-2"></i> Verifikasi DR (Simpan)
            </button>
          </div>
        </div>
      </div>
  
      <!-- Items -->
      <div class="border rounded bg-white">
        <div class="p-0 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
              <tr>
                <th class="p-3">Customer</th>
                <th class="p-3">Alamat Kirim</th>
                <th class="p-3">Produk</th>
                <th class="p-3">Nomor PO</th>
                <th class="p-3 text-right">Vol</th>
                <th class="p-3 text-right">Dialok.</th>
                <th class="p-3 text-right">Sisa</th>
                <th class="p-3 text-right">Harga Beli</th>
                <th class="p-3 text-right">Harga Jual</th>
                <th class="p-3 text-right">NP/m3</th>
                <th class="p-3 text-right">NP Total</th>
                <th class="p-3">Catatan</th>
                <th class="p-3 w-40">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in items" :key="it.id_prd" class="border-t align-top">
                <td class="p-3">{{ it.customer }}</td>
                <td class="p-3 whitespace-pre-line">{{ it.alamat_kirim }}</td>
                <td class="p-3">
                  <div>{{ it.produk }}</div>
                  <div class="text-xs text-slate-500" v-if="it.ukuran">{{ it.ukuran }}</div>
                </td>
                <td class="p-3 font-mono">{{ it.nomor_poc || '-' }}</td>
                <td class="p-3 text-right">{{ num(it.volume) }}</td>
                <td class="p-3 text-right">{{ num(it.allocated) }}</td>
                <td class="p-3 text-right">{{ num(it.remain) }}</td>
                <td class="p-3 text-right">{{ money(it.harga_beli) }}</td>
                <td class="p-3 text-right">{{ money(it.harga_jual) }}</td>
                <td class="p-3 text-right">{{ money(it.net_profit_per_liter) }}</td>
                <td class="p-3 text-right">{{ money(it.net_profit_total) }}</td>
                <td class="p-3 whitespace-pre-line text-xs">{{ it.catatan || '-' }}</td>
                <td class="p-3">
                  <button class="px-3 py-1 rounded bg-emerald-600 text-white disabled:opacity-40"
                          :disabled="it.remain<=0"
                          @click="openAllocate(it)">
                    Alokasikan Stok
                  </button>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="13" class="p-6 text-center text-slate-500">Tidak ada detail.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  
      <!-- Modal Alokasi -->
      <div v-if="showAlloc" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeAlloc"></div>
        <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">
            Alokasikan Stok • {{ currentIt?.produk }} — Target {{ num(currentIt?.remain||0) }}
          </div>
  
          <div class="p-5 space-y-4">
            <div class="flex gap-2 items-end">
              <input v-model.number="stockFilter.min" type="number" step="1" placeholder="Min volume" class="border rounded px-3 py-2 w-40">
              <input v-model="stockFilter.q" type="text" placeholder="Cari (nomor_po / PP / RI)" class="border rounded px-3 py-2 flex-1">
              <Button class="!box" @click="loadStocks"><i class="fa fa-search mr-2"></i> Cari</Button>
            </div>
  
            <div class="border rounded overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="p-2">Nomor PO (Vendor)</th>
                    <th class="p-2">Lot</th>
                    <th class="p-2">Tanggal</th>
                    <th class="p-2 text-right">Avail</th>
                    <th class="p-2 text-right">Harga Tebus</th>
                    <th class="p-2 text-right">Alokasikan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in stocks" :key="s.id" class="border-t">
                    <td class="p-2 font-mono">{{ s.nomor_po || '-' }}</td>
                    <td class="p-2 font-mono">PP:{{ s.po_produk_id || '-' }} • RI:{{ s.receive_item_id || '-' }}</td>
                    <td class="p-2">{{ dt(s.created_at) }}</td>
                    <td class="p-2 text-right">{{ num(s.volume) }}</td>
                    <td class="p-2 text-right">{{ money(s.harga_tebus) }}</td>
                    <td class="p-2">
                      <input type="number" min="0" :max="s.volume" step="1" v-model.number="s._qty"
                             class="border rounded px-2 py-1 text-right w-32" />
                    </td>
                  </tr>
                  <tr v-if="!stocks.length">
                    <td colspan="6" class="p-3 text-center text-slate-500">Tidak ada stok.</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="text-right text-sm">
              Total alokasi: <b>{{ num(totalAlloc) }}</b>
              • Sisa target: <b>{{ num((currentIt?.remain||0)-totalAlloc) }}</b>
              <div class="text-rose-600 text-xs" v-if="allocError">{{ allocError }}</div>
            </div>
          </div>
  
          <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button class="border rounded px-3 py-2" @click="closeAlloc">Batal</button>
            <button class="bg-emerald-600 text-white rounded px-4 py-2" :disabled="saving" @click="saveAllocation">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
  
      <!-- Modal Verifikasi -->
      <div v-if="showVerify" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeVerify"></div>
        <div class="relative w-full max-w-lg bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Verifikasi Delivery Request</div>
          <div class="p-5 space-y-3">
            <div class="text-sm text-slate-600">
              DR <b class="font-mono">{{ header.nomor_pr }}</b> akan ditandai <b>Terverifikasi Purchasing</b>
              dan kolom Purchasing di PR akan terisi.
            </div>
            <div>
              <div class="text-xs text-slate-500 mb-1">Ringkasan (opsional)</div>
              <textarea v-model="verifySummary" rows="4" class="w-full border rounded px-3 py-2"></textarea>
            </div>
          </div>
          <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button class="border rounded px-3 py-2" @click="closeVerify">Batal</button>
            <button class="bg-emerald-600 text-white rounded px-4 py-2" :disabled="verifying" @click="doVerify">
              {{ verifying ? 'Menyimpan...' : 'Verifikasi' }}
            </button>
          </div>
        </div>
      </div>
  
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted } from 'vue'
  import { useRoute } from 'vue-router'
  import axios from 'axios'
  import Button from '@/components/Base/Button'
  import Swal from 'sweetalert2'
  
  const route = useRoute()
  const id = Number(route.params.id)
  
  const header = ref<any>({})
  const items  = ref<any[]>([])
  const loading = ref(false)
  
  onMounted(loadDetail)
  async function loadDetail(){
    loading.value = true
    const { data } = await axios.get(`/api/procurement/delivery-requests/${id}`)
    header.value = data.header
    items.value  = data.items || []
    loading.value = false
  }
  
  function d(s?:string){ return s? new Date(s).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-' }
  function dt(s?:string){ return s? new Date(String(s)).toLocaleString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-' }
  function num(n:any){ const x=Number(n||0); return isNaN(x)?'0':x.toLocaleString('id-ID') }
  function money(n:any){ const x=Number(n||0); return x.toLocaleString('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}) }
  function dispClass(code:number){
    switch(Number(code)){
      case 1: return 'bg-emerald-100 text-emerald-700 border border-emerald-200'
      case 2: return 'bg-indigo-100 text-indigo-700 border border-indigo-200'
      default: return 'bg-amber-100 text-amber-700 border border-amber-200'
    }
  }
  
  /* ===== Alokasi (tetap) ===== */
  const showAlloc = ref(false)
  const currentIt = ref<any>(null)
  const stocks = ref<any[]>([])
  const stockFilter = ref<any>({ min: 0, q: '' })
  const saving = ref(false)
  const allocError = ref('')
  
  function openAllocate(it:any){
    currentIt.value = it
    stocks.value = []
    stockFilter.value = { min: it.remain, q: '' }
    allocError.value = ''
    showAlloc.value = true
    loadStocks()
  }
  function closeAlloc(){ showAlloc.value = false }
  
  async function loadStocks(){
    if (!currentIt.value) return
    const params:any = { produk_id: currentIt.value.produk_id, per_page: 50 }
    if (stockFilter.value.min) params.min = stockFilter.value.min
    if (stockFilter.value.q)   params.q   = stockFilter.value.q
    const { data } = await axios.get('/api/procurement/stocks', { params })
    const rows = Array.isArray(data) ? data : (data.data || [])
    stocks.value = rows.map((s:any)=>({ ...s, _qty: 0 }))
  }
  const totalAlloc = computed(()=> stocks.value.reduce((a,s)=>a + Number(s._qty||0), 0))
  
  async function saveAllocation(){
    allocError.value = ''
    const remain = Number(currentIt.value?.remain || 0)
    const tot = Number(totalAlloc.value || 0)
    if (tot <= 0){ allocError.value = 'Isi qty alokasi terlebih dahulu'; return }
    if (tot - remain > 1e-6){ allocError.value = `Jumlah alokasi melebihi sisa (${num(remain)})`; return }
  
    const allocations = stocks.value.filter(s=>Number(s._qty||0)>0).map(s=>({ stock_id: s.id, qty: Number(s._qty) }))
    if (!allocations.length){ allocError.value = 'Tidak ada baris alokasi'; return }
  
    try{
      saving.value = true
      await axios.post('/api/procurement/delivery-requests/allocate', {
        items: [{
          id_prd: currentIt.value.id_prd,
          produk_id: currentIt.value.produk_id,
          allocations
        }]
      })
      await loadDetail()
      showAlloc.value = false
      Swal.fire({icon:'success', title:'Alokasi tersimpan', timer:1100, showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan alokasi', 'error')
    }finally{
      saving.value = false
    }
  }
  
  /* ===== Verifikasi DR ===== */
  const showVerify = ref(false)
  const verifying  = ref(false)
  const verifySummary = ref('')
  
  function openVerify(){
    verifySummary.value = ''
    showVerify.value = true
  }
  function closeVerify(){ showVerify.value = false }
  
  async function doVerify(){
    try{
      verifying.value = true
      await axios.post(`/api/procurement/delivery-requests/${id}/verify`, {
        summary: verifySummary.value || null
      })
      showVerify.value = false
      await loadDetail()
      Swal.fire({icon:'success', title:'DR terverifikasi', timer:1200, showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Verifikasi gagal', 'error')
    }finally{
      verifying.value = false
    }
  }
  </script>
  