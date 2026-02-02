<template>
    <div class="p-6 space-y-4">
      <div class="text-xl font-semibold">PO Customer Plan</div>
  
      <!-- Filter -->
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium bg-sky-50">Pencarian Data</div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
          <div class="md:col-span-2">
            <div class="text-xs text-slate-500 mb-1">Tanggal Kirim</div>
            <input v-model="from" type="date" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <div class="text-xs text-slate-500 mb-1">Sampai dengan</div>
            <input v-model="to" type="date" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="md:col-span-2 flex gap-2">
            <Button class="!box" :disabled="loading" @click="fetchData(1)">
              <i class="mr-2 fa fa-search"></i> {{ loading ? 'Memuat...' : 'Cari' }}
            </Button>
            <Button class="!box" @click="goCalendar">
              <i class="mr-2 fa fa-calendar"></i> Lihat Jadwal
            </Button>
            <Button class="!box" @click="exportCsv">
              <i class="mr-2 fa fa-file-excel-o"></i> Export Data
            </Button>
          </div>
        </div>
      </div>
  
      <!-- Tabel -->
      <div class="border rounded bg-white">
        <div class="p-0 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
              <tr>
                <th class="p-3 w-16 text-center">Split</th>
                <th class="p-3 w-10 text-center">
                  <input type="checkbox" v-model="checkAll" @change="toggleAll" />
                </th>
                <th class="p-3 w-10 text-center">No</th>
                <th class="p-3 w-72">Nama & PO Customer</th>
                <th class="p-3 w-[420px]">Area/ Alamat/ Wilayah OA</th>
                <th class="p-3 w-64">
                  <div class="text-center">Quantity</div>
                  <div class="grid grid-cols-2 gap-1 text-xs text-slate-600 mt-1">
                    <div class="text-right pr-2">Volume (m3)</div>
                    <div class="text-right pr-2">Edit (m3)</div>
                  </div>
                </th>
                <th class="p-3 w-72">Catatan</th>
                <th class="p-3 w-44">Tanggal Issued</th>
              </tr>
            </thead>
  
            <tbody>
              <tr v-for="(r,i) in rows" :key="r.id" class="border-t align-top">
                <!-- Split -->
                <td class="p-3 text-center">
                  <button class="px-2 py-1 rounded bg-sky-600 text-white" @click="openSplit(r)">+</button>
                </td>
  
                <!-- Checkbox -->
                <td class="p-3 text-center">
                  <input type="checkbox" v-model="selected" :value="r.id" />
                </td>
  
                <!-- No -->
                <td class="p-3 text-center">{{ (meta.current_page-1)*perPage + i + 1 }}</td>
  
                <!-- Nama & PO -->
                <td class="p-3">
                  <div class="font-semibold">{{ r.customer || '-' }}</div>
                  <div class="italic text-slate-600" v-if="r.pic_name">{{ r.pic_name }}</div>
                  <div class="mt-1">
                    <div class="font-mono text-xs">{{ r.nomor_poc || '-' }}</div>
                    <div class="text-xs text-slate-600" v-if="r.produk">{{ r.produk }}</div>
                    <div class="mt-1" v-if="r.products && r.products.length">
                      <button class="text-xs text-sky-700 underline" @click="openProduk(r)">
                        Lihat semua produk ({{ r.products.length }})
                      </button>
                    </div>
                  </div>
                </td>
  
                <!-- Area/Alamat/Wilayah -->
                <td class="p-3">
                  <div class="font-semibold">{{ r.area_label || '-' }}</div>
                  <div class="text-slate-700 whitespace-pre-line text-sm">{{ r.address }}</div>
                  <div class="text-xs text-slate-500 mt-1" v-if="r.wilayah_oa">Wilayah OA : {{ r.wilayah_oa }}</div>
                </td>
  
                <!-- Quantity -->
                <td class="p-3">
                  <div class="grid grid-cols-2 gap-1 items-center">
                    <div class="text-right pr-2 font-medium">{{ num(r.volume_liter) }}</div>
                    <div>
                      <input
                        type="number" min="1" step="1"
                        class="w-full border rounded px-2 py-1 text-right"
                        v-model.number="r._editVolume"
                        @keydown.enter.prevent="saveVolume(r)"
                        @blur="maybeSaveVolume(r)"
                      />
                      <div class="text-[11px] text-rose-600 mt-1" v-if="volumeError(r)">{{ volumeError(r) }}</div>
                    </div>
                  </div>
                </td>
  
                <!-- Catatan -->
                <td class="p-3 whitespace-pre-line">{{ r.notes || '-' }}</td>
  
                <!-- Tanggal Issued -->
                <td class="p-3">
                  <div>{{ dt(r.issued_at) }}</div>
                  <div class="text-xs text-slate-600">WIB</div>
                </td>
              </tr>
  
              <tr v-if="!rows.length">
                <td colspan="8" class="p-6 text-center text-slate-500">
                  {{ loading ? 'Memuat data...' : 'Tidak ada data.' }}
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
            <Button class="!box" :disabled="meta.current_page<=1 || loading" @click="fetchData(meta.current_page-1)">Prev</Button>
            <Button class="!box" :disabled="meta.current_page>=meta.last_page || loading" @click="fetchData(meta.current_page+1)">Next</Button>
          </div>
        </div>
      </div>
  
      <!-- Toolbar Simpan PR -->
      <div class="flex justify-end">
        <Button class="!box bg-emerald-600 text-white"
                :disabled="selected.length===0 || loading"
                @click="openPrModal">
          <i class="mr-2 fa fa-save"></i> Simpan ke PR ({{ selected.length }})
        </Button>
      </div>
  
      <!-- Modal Split -->
      <div v-if="showSplit" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeSplit"></div>
        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Split Plan</div>
          <div class="p-5 space-y-3">
            <div class="text-sm">Sisa yang dapat di-split: <b>{{ num(splitRemain) }}</b> m3</div>
            <div>
              <div class="text-sm mb-1">Volume Split</div>
              <input type="number" min="1" step="1" v-model.number="splitVolume" class="w-full border rounded px-3 py-2 text-right" />
            </div>
            <div class="text-xs text-rose-600" v-if="splitError">{{ splitError }}</div>
          </div>
          <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button class="border rounded px-3 py-2" @click="closeSplit">Batal</button>
            <button class="bg-sky-600 text-white rounded px-4 py-2" @click="doSplit">Split</button>
          </div>
        </div>
      </div>
  
      <!-- Modal Daftar Produk -->
      <div v-if="showProduk" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeProduk"></div>
        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Produk pada Penawaran</div>
          <div class="p-5 space-y-2 max-h-[70vh] overflow-auto">
            <div v-for="(p,idx) in produkList" :key="idx" class="text-sm">• {{ p.nama_produk }}</div>
          </div>
          <div class="px-5 py-3 border-t flex justify-end">
            <button class="border rounded px-3 py-2" @click="closeProduk">Tutup</button>
          </div>
        </div>
      </div>
  
      <!-- Modal Simpan ke PR -->
      <div v-if="showPr" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closePr"></div>
        <div class="relative w-full max-w-md bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Buat PR</div>
          <div class="p-5 space-y-3">
            <div class="text-sm">
              Buat PR dari <b>{{ selected.length }}</b> plan terpilih dengan tanggal & nomor otomatis?
            </div>
          </div>
          <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button class="border rounded px-3 py-2" @click="closePr">Batal</button>
            <button class="bg-emerald-600 text-white rounded px-4 py-2" @click="savePr">Simpan</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import axios from 'axios'
  import Button from '@/components/Base/Button'
  import Swal from 'sweetalert2'
  
  const router = useRouter()
  const rows = ref<any[]>([])
  const meta  = ref<any>({ current_page:1, last_page:1, total:0 })
  const perPage = 25
  const loading = ref(false)
  
  // filter
  const from = ref<string>(''); const to = ref<string>('')
  
  // selection
  const selected = ref<number[]>([])
  const checkAll = ref(false)
  function toggleAll(){ selected.value = checkAll.value ? rows.value.map(r=>r.id) : [] }
  
  // fetch
  async function fetchData(page=1){
    try{
      loading.value = true
      const params:any = { page, per_page: perPage, status: 0 } // ⬅️ hanya status 0
      if (from.value) params.from = from.value
      if (to.value)   params.to   = to.value
  
      const { data } = await axios.get('/api/logistics/delivery-plans', { params })
      rows.value = (data.data || []).map((r:any)=>({
        ...r,
        _editVolume: r.volume_liter,
        produk: r.produk ?? null,
        products: r.products ?? [],
        pic_name: r.pic_name ?? null,
        area_label: r.area_label ?? (r.wilayah_oa ? r.wilayah_oa : null),
      }))
      meta.value = data.meta || { current_page:1,last_page:1,total:0 }
      checkAll.value = false
      selected.value = []
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal memuat data', 'error')
    }finally{
      loading.value = false
    }
  }
  onMounted(()=>fetchData(1))
  
  // modal daftar produk
  const showProduk = ref(false)
  const produkList = ref<any[]>([])
  function openProduk(r:any){ produkList.value = r.products || []; showProduk.value = true }
  function closeProduk(){ showProduk.value = false }
  
  // helpers
  function d(s?: string){ return s ? new Date(s).toLocaleDateString('id-ID',{ day:'2-digit', month:'long', year:'numeric' }) : '-' }
  function dt(s?: string){ return s ? new Date(String(s).replace(' ','T')).toLocaleString('id-ID',{ day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '-' }
  function num(n:any){ const x=Number(n||0); return isNaN(x)?'0':x.toLocaleString('id-ID') }
  
  // inline edit volume
  function volumeError(r:any){
    const v = Number(r._editVolume||0)
    if (v<1) return 'Minimal 1 liter'
    if (v < Number(r.real_liter||0)) return `Tidak boleh < realisasi (${num(r.real_liter)})`
    return ''
  }
  async function saveVolume(r:any){
    const err = volumeError(r)
    if (err){ Swal.fire('Validasi', err, 'warning'); return }
    if (Number(r._editVolume) === Number(r.volume_liter)) return
    await axios.patch(`/api/logistics/delivery-plans/${r.id}/volume`, { volume_kirim: Number(r._editVolume) })
    r.volume_liter = Number(r._editVolume)
    Swal.fire({icon:'success', title:'Volume disimpan', timer:900, showConfirmButton:false})
  }
  function maybeSaveVolume(r:any){
    if (Number(r._editVolume) !== Number(r.volume_liter)) saveVolume(r)
  }
  
  // split
  const showSplit = ref(false)
  const splitRow:any = ref(null)
  const splitVolume = ref<number|null>(null)
  const splitRemain = ref(0)
  const splitError = ref('')
  function openSplit(r:any){
    splitRow.value = r
    splitVolume.value = null
    splitError.value = ''
    splitRemain.value = Math.max(0, Number(r.volume_liter||0) - Number(r.real_liter||0))
    showSplit.value = true
  }
  function closeSplit(){ showSplit.value = false }
  async function doSplit(){
    const vol = Number(splitVolume.value||0)
    if (!vol){ splitError.value = 'Isi volume split'; return }
    if (vol < 1){ splitError.value = 'Minimal 1 liter'; return }
    if (vol >= splitRemain.value){ splitError.value = `Harus < ${num(splitRemain.value)}`; return }
    await axios.post(`/api/logistics/delivery-plans/${splitRow.value.id}/split`, { volume: vol })
    showSplit.value = false
    await fetchData(meta.value.current_page)
    Swal.fire({icon:'success', title:'Berhasil split', timer:1000, showConfirmButton:false})
  }
  
  // PR modal
  const showPr = ref(false)
  function openPrModal(){ showPr.value = true }
  function closePr(){ showPr.value = false }
  async function savePr(){
    if (selected.value.length === 0){
      Swal.fire('Validasi', 'Pilih minimal 1 plan', 'warning'); return
    }
    try{
      loading.value = true
      const items = selected.value.map(id => {
        const r = rows.value.find(x=>x.id===id)
        const volume = Number(r?._editVolume ?? r?.volume_liter ?? 0)
        return { id_plan: id, volume }
      })
      const payload:any = { items }
  
      const { data } = await axios.post('/api/logistics/pr', payload)
      showPr.value = false
      // hapus dari list (status sudah jadi 1 di server)
      rows.value = rows.value.filter(r => !selected.value.includes(r.id))
      selected.value = []
      Swal.fire({icon:'success', title:`PR dibuat (#${data.id_pr} / ${data.nomor_pr})`, timer:1400, showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal membuat PR', 'error')
    }finally{
      loading.value = false
    }
  }
  
  // header buttons
  function goCalendar(){
    const q:any = {}
    if (from.value) q.from = from.value
    if (to.value)   q.to   = to.value
    router.push({ name: 'calendar', query: q })
  }
  function exportCsv(){
    const header = ['Doc','Customer','Nomor PO','Tanggal Kirim','Alamat','Volume','Realisasi','Status','Issued']
    const body = rows.value.map((r:any)=>[
      r.doc_code, r.customer, r.nomor_poc, d(r.ship_date), (r.address||'').replace(/\n/g,' '),
      r.volume_liter, r.real_liter, r.status_label, dt(r.issued_at)
    ])
    const csv = [header, ...body].map(row=>row.map(x=>`"${String(x??'').replaceAll('"','""')}"`).join(',')).join('\n')
    const blob = new Blob([csv],{type:'text/csv;charset=utf-8;'})
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a'); a.href = url; a.download = `delivery-plan-${new Date().toISOString().slice(0,10)}.csv`; a.click()
    URL.revokeObjectURL(url)
  }
  </script>
  