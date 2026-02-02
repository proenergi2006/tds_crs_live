<template>
    <div class="p-6 space-y-4">
      <div class="text-xl font-semibold">Detail PO Customer</div>
  
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium">Data PO Customer</div>
  
        <!-- body tabel 2 kolom -->
        <div class="p-0">
          <table class="w-full text-sm">
            <tbody>
              <tr class="bg-slate-100/70">
                <td class="w-64 p-3 font-semibold">Kode Dokumen {{ docCode }}</td>
                <td class="p-3"></td>
              </tr>
  
              <tr><td class="p-3">Nama Customer</td>
                <td class="p-3">: {{ h.customer?.nama_perusahaan || '-' }}</td></tr>
  
              <tr><td class="p-3">TOP Customer</td>
                <td class="p-3">: {{ h.penawaran?.top || '-' }}</td></tr>
  
              <tr><td class="p-3">Penawaran</td>
                <td class="p-3">: {{ h.penawaran?.nomor_penawaran || '-' }}</td></tr>
  
              <tr><td class="p-3">Volume Penawaran</td>
                <td class="p-3">: {{ fmtNumber(h.poc?.volume_poc) }} m³</td></tr>
  
              <tr><td class="p-3">Harga Penawaran</td>
                <td class="p-3">: {{ money(h.poc?.harga_poc) }}</td></tr>
  
              <tr class="bg-slate-100/70"><td class="p-3">Nomor PO</td>
                <td class="p-3">: {{ h.poc?.nomor_poc || '-' }}</td></tr>
  
              <tr><td class="p-3">Tanggal PO</td>
                <td class="p-3">: {{ dmy(h.poc?.tanggal_poc) }}</td></tr>
  
              <tr><td class="p-3">Tgl Pengiriman</td>
                <td class="p-3">: {{ dmy(h.poc?.supply_date) }}</td></tr>
  
              <tr><td class="p-3">Produk</td>
                <td class="p-3">: {{ produkLabel }}</td></tr>
  
              <tr><td class="p-3">Harga/m³</td>
                <td class="p-3">: {{ money(h.poc?.harga_poc) }}</td></tr>
  
              <tr><td class="p-3">Jumlah Volume</td>
                <td class="p-3">: {{ fmtNumber(h.poc?.volume_poc) }} m³</td></tr>
  
              <tr><td class="p-3">Total Order</td>
                <td class="p-3">: {{ money(totalOrder) }}</td></tr>
  
              <tr><td class="p-3">Lampiran</td>
                <td class="p-3">:
                  <template v-if="h.sc_header?.lampiran_unblock">
                    <a :href="fileUrl(h.sc_header.lampiran_unblock)" target="_blank"
                       class="text-sky-600 underline">
                      {{ h.sc_header.lampiran_unblock_ori || 'Lampiran' }}
                    </a>
                  </template>
                  <template v-else>-</template>
                </td>
              </tr>
  
              <tr class="align-top">
                <td class="p-3">Disposisi</td>
                <td class="p-3">:
                  <div class="font-medium" :class="badgeClass(h.sc?.disposisi)">
                    {{ h.sc?.disposisi_label || '-' }}
                  </div>
                  <div v-if="h.sc?.disposisi_time" class="text-slate-500 text-xs">
                    {{ dispTime(h.sc.disposisi_time) }} WIB
                  </div>
  
                  <div v-if="h.approval" class="mt-3">
                    <div class="text-slate-600 mb-1">Catatan BM</div>
                    <div class="border rounded p-3 bg-slate-50" v-html="h.approval.bm_summary || '-'"></div>
                    <div class="text-xs italic text-slate-500 mt-1" v-if="h.approval.bm_result_date">
                      {{ h.approval.bm_pic || '-' }} - {{ dispTime(h.approval.bm_result_date) }} WIB
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
  
        <!-- tombol bawah -->
        <div class="p-3 border-t flex gap-2">
          <button class="btn" @click="router.back()">Kembali</button>
          <button class="btn-warning" @click="editNomorPo">Edit Nomor PO</button>
          <button class="btn-success" @click="gotoPlan" :disabled="h.sc?.disposisi !== 2">Jadwal Kirim</button>
          <button class="btn-success" @click="closePo">Close PO</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  const route = useRoute()
  const router = useRouter()
  const idPoc = Number(route.params.id)
  
  const h = ref<any>({})
  
  const docCode = computed(() => `PO-${String(h.value?.poc?.id_poc || idPoc || 0).padStart(5,'0')}`)
  const totalOrder = computed(() => Number(h.value?.poc?.harga_poc || 0) * Number(h.value?.poc?.volume_poc || 0))
  const produkLabel = computed(() =>
    h.value?.poc?.produk_poc || h.value?.penawaran?.produk_name || '-'
  )
  
  function dmy(d?:string){ return d ? new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-' }
  function dispTime(d?:string){ if(!d) return '-'; const t = new Date(d.replace(' ','T')); const p=(x:number)=>String(x).padStart(2,'0'); return `${p(t.getDate())}/${p(t.getMonth()+1)}/${t.getFullYear()} ${p(t.getHours())}.${p(t.getMinutes())}.${p(t.getSeconds())}` }
  function fmtNumber(n:any){ const x = Number(n||0); return isNaN(x)?'-':x.toLocaleString('id-ID') }
  function money(n:any){ const x = Number(n||0); return `Rp. ${x.toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}` }
  function fileUrl(path:string){ return `/storage/${String(path).replace(/^\/?storage\//,'')}` }
  function badgeClass(v?:number|string){ const s=String(v??''); return {'text-emerald-600':s==='2','text-slate-600':s==='1'||s==='0','text-orange-600':s==='3','text-emerald-700':s==='4','text-red-600':s==='5'} }
  
  async function load(){
    const { data } = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    h.value = data
  }
  onMounted(load)
  
  async function editNomorPo(){
    const { value: nomor } = await Swal.fire({
      title: 'Edit Nomor PO',
      input: 'text',
      inputValue: h.value?.poc?.nomor_poc || '',
      showCancelButton: true,
      confirmButtonText: 'Simpan',
    })
    if (!nomor) return
    try{
      await axios.put(`/api/po-customers/${idPoc}/nomor`, { nomor_poc: nomor })
      await load()
      Swal.fire({icon:'success',title:'Nomor PO diperbarui',timer:1300,showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal update nomor PO', 'error')
    }
  }
  
  function gotoPlan(){
    // arahkan ke halaman perencanaan pengiriman
    router.push({ name: 'po-customer-plan', params: { id: idPoc } })
  }
  
  async function closePo(){
    const ok = await Swal.fire({title:'Close PO?', text:'Menandai PO ini selesai.', icon:'question', showCancelButton:true})
    if (!ok.isConfirmed) return
    try{
      await axios.post(`/api/po-customers/${idPoc}/close`)
      await load()
      Swal.fire({icon:'success',title:'PO ditutup',timer:1300,showConfirmButton:false})
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Tidak dapat close PO', 'error')
    }
  }
  </script>
  
  <style scoped>
  .btn{ @apply border rounded px-3 py-2; }
  .btn-success{ @apply bg-emerald-600 text-white rounded px-3 py-2; }
  .btn-warning{ @apply bg-amber-500 text-white rounded px-3 py-2; }
  </style>
  