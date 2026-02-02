<template>
    <div class="p-6 space-y-4">
      <div class="text-xl font-semibold">PO Customer Plan</div>
  
      <!-- HEADER -->
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium"></div>
        <div class="p-4">
          <table class="text-sm">
            <tbody>
              <tr><td class="w-52 py-1 pr-6">Kode Dokumen</td><td>: {{ h.doc_code || '-' }}</td></tr>
              <tr><td class="py-1 pr-6">Kode Penawaran</td><td>: {{ h.penawaran_code || '-' }}</td></tr>
              <tr><td class="py-1 pr-6">Periode Penawaran</td><td>: {{ h.periode || '-' }}</td></tr>
              <tr><td class="py-1 pr-6">Customer</td><td>: {{ h.customer_name || '-' }}</td></tr>
  
              <tr><td class="py-1 pr-6">TOP Customer</td><td>: {{ h.top || '-' }}</td></tr>
              <tr><td class="py-1 pr-6">Nomor PO</td><td>: {{ h.nomor_poc || '-' }}</td></tr>
              <tr><td class="py-1 pr-6">Tanggal PO</td><td>: {{ d(h.tanggal_poc) }}</td></tr>
              <tr><td class="py-1 pr-6">Tgl Pengiriman</td><td>: {{ d(h.supply_date) }}</td></tr>
              <tr><td class="py-1 pr-6">Produk</td><td>: {{ h.produk || '-' }}</td></tr>
  
              <tr><td class="py-1 pr-6">Harga/m³</td><td>: {{ money(h.harga_per_liter) }}</td></tr>
              <tr><td class="py-1 pr-6">Jumlah Volume</td><td>: {{ num(h.jumlah_volume_liter) }} m³</td></tr>
              <tr><td class="py-1 pr-6">Total Order</td><td>: {{ money(h.total_order_rp) }}</td></tr>
  
              <tr><td class="py-1 pr-6">Total Order</td><td>: {{ num(h.total_liter) }} m³</td></tr>
              <tr><td class="py-1 pr-6">Terkirim</td><td>: {{ num(h.shipped_liter) }} m³</td></tr>
              <tr><td class="py-1 pr-6">Sisa Buku</td><td>: {{ num(h.book_remaining_liter) }} m³</td></tr>
              <tr><td class="py-1 pr-6">Close PO</td><td>: {{ num(h.close_po_liter) }} m³</td></tr>
            </tbody>
          </table>
  
          <div class="mt-4">
            <Button class="!box" @click="openForm">+ Tambah Data</Button>
          </div>
        </div>
      </div>
  
      <!-- TABEL RENCANA -->
      <div class="border rounded bg-white">
        <div class="p-0 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
              <tr>
                <th class="p-3 w-10 text-center">No</th>
                <th class="p-3">Tanggal Issued</th>
                <th class="p-3">Tanggal Kirim</th>
                <th class="p-3">Alamat/Catatan</th>
                <th class="p-3 text-right">Volume (m³)</th>
                <th class="p-3 text-right">Realisasi (m³)</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-center w-20">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, i) in rows" :key="r.id" class="border-t">
                <td class="p-3 text-center">{{ i + 1 }}</td>
                <td class="p-3 whitespace-nowrap">{{ dt(r.issued_at) }}</td>
                <td class="p-3 whitespace-nowrap">{{ d(r.ship_date) }}</td>
                <td class="p-3 min-w-[320px] whitespace-pre-line">{{ r.address }}</td>
                <td class="p-3 text-right whitespace-nowrap">{{ num(r.volume_liter) }}</td>
                <td class="p-3 text-right whitespace-nowrap">{{ num(r.real_liter) }}</td>
                <td class="p-3">{{ r.status || '-' }}</td>
                <td class="p-3 text-center">
                  <button class="text-red-600 hover:text-red-800 mx-1" @click="delRow(r)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td colspan="8" class="text-center p-6 text-slate-500">Belum ada data.</td>
              </tr>
            </tbody>
          </table>
        </div>
  
        <div class="p-4">
          <Button class="!box" @click="router.back()">Kembali</Button>
        </div>
      </div>
  
      <!-- MODAL TAMBAH DATA -->
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="close"></div>
        <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-lg">
          <div class="px-5 py-3 border-b font-medium">Tambah Data PO Plan</div>
  
          <div class="p-5 space-y-4 max-h-[80vh] overflow-auto">
            <!-- header kecil di modal -->
            <table class="w-full text-sm border">
              <tbody>
                <tr class="bg-slate-100"><td class="p-2 font-semibold">Kode Dokumen {{ h.doc_code }}</td><td></td></tr>
                <tr><td class="p-2 w-48">Kode Penawaran</td><td class="p-2">: {{ h.penawaran_code || '-' }}</td></tr>
                <tr><td class="p-2">Periode Penawaran</td><td class="p-2">: {{ h.periode || '-' }}</td></tr>
                <tr><td class="p-2">Nama Customer</td><td class="p-2">: {{ h.customer_name || '-' }}</td></tr>
                <tr><td class="p-2">Nomor PO</td><td class="p-2">: {{ h.nomor_poc || '-' }}</td></tr>
                <tr><td class="p-2">Tanggal PO</td><td class="p-2">: {{ d(h.tanggal_poc) }}</td></tr>
                <tr><td class="p-2">Total Order</td><td class="p-2">: {{ num(h.total_liter) }} m³</td></tr>
                <tr><td class="p-2">Tanggal Kirim</td><td class="p-2">: {{ d(h.supply_date) }}</td></tr>
                <tr><td class="p-2">Total Kirim</td><td class="p-2">: {{ num(h.shipped_liter) }} m³</td></tr>
                <tr><td class="p-2">Sisa Aktual</td><td class="p-2">: {{ num(remaining) }} m³</td></tr>
                <tr><td class="p-2">Sisa Buku</td><td class="p-2">: {{ num(h.book_remaining_liter) }} m³</td></tr>
              </tbody>
            </table>
  
            <!-- form -->
            <div class="space-y-3">
              <div>
                <div class="mb-1 text-slate-600">Alamat Kirim *</div>
                <select v-if="addresses.length" v-model="form.address" class="w-full border rounded px-3 py-2">
                  <option disabled value="">Pilih salah satu</option>
                  <option v-for="(a,idx) in addresses" :key="idx" :value="a">{{ a }}</option>
                </select>
                <textarea v-else v-model="form.address" class="w-full border rounded px-3 py-2" placeholder="Tulis alamat kirim"></textarea>
              </div>
  
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <div class="mb-1 text-slate-600">Tanggal Kirim *</div>
                  <input type="date" v-model="form.ship_date" class="w-full border rounded px-3 py-2" />
                </div>
                <div>
                  <div class="mb-1 text-slate-600">Volume *</div>
                  <div class="flex">
                    <input type="number" min="1" step="1" v-model.number="form.volume_liter" class="flex-1 border rounded-l px-3 py-2 text-right" />
                    <span class="border rounded-r px-3 py-2 bg-slate-50">m³</span>
                  </div>
                  <div v-if="volError" class="text-red-600 text-xs mt-1">{{ volError }}</div>
                </div>
              </div>
  
              <div>
                <div class="mb-1 text-slate-600">Catatan</div>
                <textarea v-model="form.notes" class="w-full border rounded px-3 py-2"></textarea>
              </div>
            </div>
          </div>
  
          <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button class="border rounded px-3 py-2" @click="close">Batal</button>
            <button class="bg-sky-600 text-white rounded px-4 py-2" @click="save">Save</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, computed } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  import Lucide from '@/components/Base/Lucide'
  
  const route = useRoute()
  const router = useRouter()
  const idPoc = Number(route.params.id)
  
  const h = ref<any>({})
  const rows = ref<any[]>([])
  const addresses = ref<string[]>([])
  
  // modal + form
  const show = ref(false)
  const form = ref<{address:string; ship_date:string; volume_liter:number|null; notes:string}>({
    address: '', ship_date: '', volume_liter: null, notes: ''
  })
  
  const remaining = computed(() =>
    Math.max(0, Number(h.value.total_liter || 0) - Number(h.value.shipped_liter || 0))
  )
  const volError = computed(() => {
    const v = Number(form.value.volume_liter || 0)
    if (!v) return ''
    if (v < 1) return 'Minimal 1 liter'
    if (v > remaining.value) return 'Volume melebihi sisa aktual'
    return ''
  })
  
  // helpers
  function d(s?: string){ return s ? new Date(s).toLocaleDateString('id-ID',{ day:'2-digit', month:'long', year:'numeric' }) : '-' }
  function dt(s?: string){ return s ? new Date(String(s).replace(' ','T')).toLocaleString('id-ID',{ day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' }) : '-' }
  function num(n:any){ const x=Number(n||0); return isNaN(x)?'0':x.toLocaleString('id-ID') }
  function money(n:any){ const x=Number(n||0); return `Rp. ${x.toLocaleString('id-ID',{ minimumFractionDigits:2, maximumFractionDigits:2 })}` }
  
  // ambil header dari halaman DETAIL (agar selalu terisi)
  async function loadHeaderFromDetail(){
    const { data } = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    const poc  = data.poc || {}
    const cust = data.customer || {}
    const pen  = data.penawaran || {}
    const awal = pen.periode_awal || pen.start_date || pen.tanggal_mulai
    const akhir= pen.periode_akhir|| pen.end_date   || pen.tanggal_selesai
    const periode = (awal && akhir)
      ? new Date(awal).toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'}) + ' - ' +
        new Date(akhir).toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'})
      : '-'
    const vol = Number(poc.volume_poc || 0), harga = Number(poc.harga_poc || 0)
    h.value = {
      doc_code: `PO-${String(poc.id_poc || idPoc).padStart(5,'0')}`,
      penawaran_code: pen.nomor_penawaran || '-',
      periode,
      customer_name: cust.nama_perusahaan || '-',
      top: pen.top || '-',
      nomor_poc: poc.nomor_poc || '-',
      tanggal_poc: poc.tanggal_poc || null,
      supply_date: poc.supply_date || null,
      produk: poc.produk_poc || pen.produk_name || '-',
      harga_per_liter: harga,
      jumlah_volume_liter: vol,
      total_order_rp: vol*harga,
      total_liter: vol, shipped_liter: 0, book_remaining_liter: vol, close_po_liter: 0,
    }
    // pakai alamat perusahaan sebagai opsi default (kalau ada)
    if (cust.alamat_perusahaan) addresses.value = [cust.alamat_perusahaan]
  }
  
  // ambil items dari tabel po_customer_plan
  async function loadItems(){
    try{
      const { data } = await axios.get(`/api/po-customers/${idPoc}/plan`)
      rows.value = data.items || []
      if (data.header){
        h.value.shipped_liter        = Number(data.header.shipped_liter || 0)
        h.value.book_remaining_liter = Number(data.header.book_remaining_liter || (h.value.total_liter - h.value.shipped_liter))
      }
    }catch{ rows.value = [] }
  }
  
  async function load(){ await loadHeaderFromDetail(); await loadItems() }
  onMounted(load)
  
  function openForm(){
    form.value = { address: addresses.value[0] || '', ship_date: '', volume_liter: null, notes: '' }
    show.value = true
  }
  function close(){ show.value = false }
  
  async function save(){
    if (!form.value.address || !form.value.ship_date || !form.value.volume_liter){
      Swal.fire('Validasi', 'Alamat, tanggal kirim, dan volume wajib diisi.', 'warning'); return
    }
    if (volError.value){ Swal.fire('Validasi', volError.value, 'warning'); return }
  
    try{
      const { data } = await axios.post(`/api/po-customers/${idPoc}/plan`, {
        address: form.value.address || null,          // disimpan ke status_jadwal
        ship_date: form.value.ship_date,
        volume_kirim: Number(form.value.volume_liter),// nama kolom di tabel
        notes: form.value.notes || null,
        is_urgent: false,
      })
      if (data?.header_patch){
        h.value.shipped_liter        = data.header_patch.shipped_liter
        h.value.book_remaining_liter = data.header_patch.book_remaining_liter
      }
      show.value = false
      await loadItems()
      Swal.fire({icon:'success', title:'Plan tersimpan', timer:1300, showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan plan', 'error')
    }
  }
  
  async function delRow(r:any){
    const ok = await Swal.fire({ title: 'Hapus data?', icon: 'warning', showCancelButton: true })
    if (!ok.isConfirmed) return
    try{
      const { data } = await axios.delete(`/api/po-customers/${idPoc}/plan/${r.id}`)
      if (data?.header_patch){
        h.value.shipped_liter        = data.header_patch.shipped_liter
        h.value.book_remaining_liter = data.header_patch.book_remaining_liter
      }
      await loadItems()
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menghapus', 'error')
    }
  }
  </script>
  