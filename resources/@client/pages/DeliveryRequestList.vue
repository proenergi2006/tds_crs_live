<template>
    <div class="p-6 space-y-4">
      <div class="text-xl font-semibold">Delivery Request</div>
  
      <!-- FILTER -->
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium bg-sky-50">Filter</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
          <div class="md:col-span-2">
            <div class="text-xs text-slate-500 mb-1">Tanggal DR (dari)</div>
            <input v-model="from" type="date" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <div class="text-xs text-slate-500 mb-1">Sampai</div>
            <input v-model="to" type="date" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="md:col-span-2 flex gap-2">
            <Button class="!box" :disabled="loading" @click="fetchList(1)">
              <i class="fa fa-search mr-2"></i>{{ loading ? 'Memuat...' : 'Search' }}
            </Button>
            <Button class="!box" @click="exportCsv">
              <i class="fa fa-file-excel-o mr-2"></i>Export
            </Button>
          </div>
        </div>
      </div>
  
      <!-- LIST PR -->
      <div class="border rounded bg-white">
        <div class="p-0 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
              <tr>
                <th class="p-3 w-10"></th>
                <th class="p-3">Tanggal DR</th>
                <th class="p-3">Kode DR</th>
                <th class="p-3">Customer</th>
                <th class="p-3">Nomor PO</th>
                <th class="p-3">Disposisi</th>
                <th class="p-3 text-right">Volume</th>
                <th class="p-3 text-right">Sisa</th>
                <th class="p-3 text-center w-36">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="pr in list" :key="pr.id_pr">
                <tr class="border-t">
                  <td class="p-3 text-center">
                    <button class="text-sky-600" @click="toggle(pr)">{{ pr._open ? '−' : '+' }}</button>
                  </td>
                  <td class="p-3 whitespace-nowrap">{{ d(pr.tanggal_pr) }}</td>
                  <td class="p-3 font-mono">{{ pr.nomor_pr }}</td>
                  <td class="p-3 whitespace-pre-line">{{ pr.customers || '-' }}</td>
                  <td class="p-3 whitespace-pre-line font-mono">{{ pr.nomor_pos || '-' }}</td>
                  <td class="p-3">
                    <span class="px-2 py-1 rounded text-xs" :class="dispClass(pr.disposisi)">
                      {{ pr.disposisi_label }}
                    </span>
                  </td>
                  <td class="p-3 text-right">{{ num(pr.total_volume) }}</td>
                  <td class="p-3 text-right">{{ num(pr.total_sisa) }}</td>
                  <td class="p-3 text-center">
                    <RouterLink
                      class="inline-flex items-center px-3 py-1 rounded bg-sky-600 text-white hover:opacity-90"
                      :to="{ name: 'procurement-dr-detail', params: { id: pr.id_pr } }"
                    >
                      <i class="fa fa-info-circle mr-1"></i> Detail
                    </RouterLink>
                  </td>
                </tr>
  
                <!-- EXPANDED DETAIL -->
                <tr v-if="pr._open">
                  <td colspan="9" class="p-0">
                    <div class="bg-slate-50 p-3">
                      <table class="min-w-full text-xs">
                        <thead>
                          <tr class="text-slate-600">
                            <th class="p-2 text-left">Produk</th>
                            <th class="p-2 text-left">Customer</th>
                            <th class="p-2 text-left">Nomor PO</th>
                            <th class="p-2 text-right">Volume</th>
                            <th class="p-2 text-right">Dialokasi</th>
                            <th class="p-2 text-right">Sisa</th>
                            <th class="p-2">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="it in pr.items" :key="it.id_prd" class="border-t">
                            <td class="p-2">{{ it.produk }}</td>
                            <td class="p-2">{{ it.customer || '-' }}</td>
                            <td class="p-2 font-mono">{{ it.nomor_poc || '-' }}</td>
                            <td class="p-2 text-right">{{ num(it.volume) }}</td>
                            <td class="p-2 text-right">{{ num(it.allocated) }}</td>
                            <td class="p-2 text-right">{{ num(it.remain) }}</td>
                            <td class="p-2">
                              <button
                                class="px-2 py-1 rounded bg-emerald-600 text-white disabled:opacity-40"
                                :disabled="it.remain<=0"
                                @click="openAllocate(pr, it)"
                              >
                                Pilih Stok
                              </button>
                            </td>
                          </tr>
                          <tr v-if="!pr.items.length">
                            <td colspan="7" class="p-3 text-center text-slate-500">Tidak ada detail.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              </template>
  
              <tr v-if="!list.length">
                <td colspan="9" class="p-6 text-center text-slate-500">
                  {{ loading ? 'Memuat...' : 'Tidak ada data.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
  
        <!-- pagination -->
        <div class="p-4 flex justify-between items-center">
          <div class="text-xs text-slate-500">
            Halaman {{ meta.current_page }} / {{ meta.last_page }} • Total {{ num(meta.total) }}
          </div>
          <div class="flex gap-2">
            <Button class="!box" :disabled="meta.current_page<=1 || loading" @click="fetchList(meta.current_page-1)">Prev</Button>
            <Button class="!box" :disabled="meta.current_page>=meta.last_page || loading" @click="fetchList(meta.current_page+1)">Next</Button>
          </div>
        </div>
      </div>
  
      <!-- MODAL ALOKASI -->
      <div v-if="showAlloc" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeAlloc"></div>
        <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Alokasikan Stok • {{ currentIt?.produk }}</div>
  
          <div class="p-5 space-y-4">
            <div class="text-sm">
              Butuh dialokasikan: <b>{{ num(currentIt?.remain || 0) }}</b> dari volume <b>{{ num(currentIt?.volume || 0) }}</b>
            </div>
  
            <div class="flex gap-2 items-end">
              <input v-model.number="stockFilter.min" type="number" step="1" placeholder="Min volume" class="border rounded px-3 py-2 w-40">
              <input v-model="stockFilter.q" type="text" placeholder="Cari lot (PO/receive id)" class="border rounded px-3 py-2 flex-1">
              <Button class="!box" @click="loadStocks"><i class="fa fa-search mr-2"></i> Cari Stok</Button>
            </div>
  
            <div class="border rounded overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="p-2">Lot</th>
                    <th class="p-2">Tanggal</th>
                    <th class="p-2 text-right">Avail</th>
                    <th class="p-2 text-right">Alokasikan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="s in stocks" :key="s.id" class="border-t">
                    <td class="p-2 font-mono">PP:{{ s.po_produk_id || '-' }} RI:{{ s.receive_item_id || '-' }}</td>
                    <td class="p-2">{{ dt(s.created_at) }}</td>
                    <td class="p-2 text-right">{{ num(s.volume) }}</td>
                    <td class="p-2">
                      <input
                        type="number" min="0" :max="s.volume" step="1"
                        v-model.number="s._qty"
                        class="border rounded px-2 py-1 text-right w-40"
                      />
                    </td>
                  </tr>
                  <tr v-if="!stocks.length">
                    <td colspan="4" class="p-3 text-center text-slate-500">Tidak ada stok.</td>
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
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed } from 'vue'
  import { RouterLink } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  
  const list = ref<any[]>([])
  const meta  = ref<any>({current_page:1,last_page:1,total:0})
  const loading = ref(false)
  const from = ref<string>('')   // YYYY-MM-DD
  const to   = ref<string>('')   // YYYY-MM-DD
  
  async function fetchList(page=1){
    try{
      loading.value = true
      const params:any = { page, per_page: 25 }
      if (from.value) params.from = from.value
      if (to.value)   params.to   = to.value
      const { data } = await axios.get('/api/procurement/delivery-requests', { params })
      list.value = (data.data||[]).map((r:any)=>({ ...r, _open:false, items: [] }))
      meta.value = data.meta || meta.value
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal memuat', 'error')
    }finally{
      loading.value = false
    }
  }
  fetchList(1)
  
  // helpers
  function d(s?:string){ return s? new Date(s).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-' }
  function dt(s?:string){ return s? new Date(String(s)).toLocaleString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-' }
  function num(n:any){ const x=Number(n||0); return isNaN(x)?'0':x.toLocaleString('id-ID') }
  
  function dispClass(code:number){
    switch(Number(code)){
      case 1: return 'bg-emerald-100 text-emerald-700 border border-emerald-200'
      case 2: return 'bg-indigo-100 text-indigo-700 border border-indigo-200'
      default: return 'bg-amber-100 text-amber-700 border border-amber-200'
    }
  }
  
  // expand row → load detail
  async function toggle(pr:any){
    pr._open = !pr._open
    if (pr._open && (!pr.items || !pr.items.length)) {
      const { data } = await axios.get(`/api/procurement/delivery-requests/${pr.id_pr}`)
      pr.items = (data.items || []).map((it:any)=>({
        ...it,
        allocated: Number(it.allocated || 0),
        remain: Math.max(0, Number(it.volume || 0) - Number(it.allocated || 0)),
      }))
    }
  }
  
  /* ============ Allocation modal ============ */
  const showAlloc = ref(false)
  const currentPr = ref<any>(null)
  const currentIt = ref<any>(null)
  const stocks = ref<any[]>([])
  const stockFilter = ref<any>({ min: 0, q: '' })
  const saving = ref(false)
  const allocError = ref('')
  
  function openAllocate(pr:any, it:any){
    currentPr.value = pr
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
    const params:any = {
      produk_id: currentIt.value.produk_id || currentIt.value.id_produk || currentIt.value.produk_id_fk,
      per_page: 50
    }
    if (stockFilter.value.min) params.min = stockFilter.value.min
    if (stockFilter.value.q)   params.q = stockFilter.value.q
  
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
  
    const allocations = stocks.value
      .filter(s=>Number(s._qty||0)>0)
      .map(s=>({ stock_id: s.id, qty: Number(s._qty) }))
    if (!allocations.length){ allocError.value = 'Tidak ada baris alokasi'; return }
  
    try{
      saving.value = true
      await axios.post('/api/procurement/delivery-requests/allocate', {
        items: [{
          id_prd: currentIt.value.id_prd,
          produk_id: currentIt.value.produk_id || currentIt.value.id_produk || currentIt.value.produk_id_fk,
          allocations
        }]
      })
      // refresh detail row
      const { data } = await axios.get(`/api/procurement/delivery-requests/${currentPr.value.id_pr}`)
      currentPr.value.items = (data.items || []).map((it:any)=>({
        ...it,
        allocated: Number(it.allocated || 0),
        remain: Math.max(0, Number(it.volume || 0) - Number(it.allocated || 0)),
      }))
      currentPr.value.total_sisa = currentPr.value.items.reduce((sum:any, it:any)=> sum + (it.remain||0), 0)
      showAlloc.value = false
      Swal.fire({icon:'success', title:'Alokasi tersimpan', timer:1100, showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan alokasi', 'error')
    }finally{
      saving.value = false
    }
  }
  
  /* ============ Export ============ */
  function exportCsv(){
    const header = ['Tanggal','Kode DR','Customer','Nomor PO','Disposisi','Total Volume','Total Sisa']
    const body = list.value.map((r:any)=>[
      d(r.tanggal_pr),
      r.nomor_pr,
      (r.customers||'').replace(/\n/g,'; '),
      (r.nomor_pos||'').replace(/\n/g,'; '),
      r.disposisi_label,
      r.total_volume,
      r.total_sisa
    ])
    const csv = [header, ...body]
      .map(row=>row.map(x=>`"${String(x??'').replaceAll('"','""')}"`).join(','))
      .join('\n')
    const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'})
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a'); a.href = url; a.download = `delivery-requests-${new Date().toISOString().slice(0,10)}.csv`; a.click()
    URL.revokeObjectURL(url)
  }
  </script>
  