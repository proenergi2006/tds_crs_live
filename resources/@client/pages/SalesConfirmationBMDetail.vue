<template>
    <div class="p-6 space-y-6">
      <div class="text-lg font-semibold">Sales Confirmation — Branch Manager</div>
  
      <!-- tanggal -->
      <div class="text-sm text-slate-700">
        <div>Date/Periode: <span class="font-medium">{{ fmtDate(h.poc?.tanggal_poc) }}</span></div>
        <div>Supply Date : <span class="font-medium">{{ fmtDate(h.poc?.supply_date) }}</span></div>
      </div>
  
      <!-- customer -->
      <div class="border rounded bg-white">
        <div class="grid grid-cols-6 gap-2 border-b p-3 font-medium text-center">
          <div class="col-span-2">Customer Code</div>
          <div class="col-span-2">Customer Name</div>
          <div>TOP</div>
          <div>Credit Limit</div>
        </div>
        <div class="grid grid-cols-6 gap-2 p-3 text-center">
          <div class="col-span-2">{{ h.customer?.kode_pelanggan || '-' }}</div>
          <div class="col-span-2">{{ h.customer?.nama_perusahaan || '-' }}</div>
          <div>{{ h.penawaran?.top || '-' }}</div>
          <div>{{ money(h.customer?.credit_limit) }}</div>
        </div>
      </div>
  
      <!-- disposisi -->
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium">Disposisi</div>
        <div class="p-4">
          <div :class="['font-medium', dispClass(h.sc?.disposisi)]">
            {{ h.sc?.disposisi_label || '-' }}
          </div>
          <div v-if="h.sc?.disposisi_time" class="text-slate-500 text-sm">
            {{ fmtDispTime(h.sc.disposisi_time) }} WIB
          </div>
        </div>
      </div>
  
      <!-- ringkasan input Admin (read-only) -->
      <div class="border rounded bg-white p-4 space-y-3">
        <div class="font-medium">Ringkasan Admin</div>
        <div class="grid grid-cols-3 gap-4">
          <div><div class="text-slate-600 mb-1">PO Type</div>
            <input :value="h.sc_header?.po_status || '-'" class="input bg-slate-50" readonly />
          </div>
          <div><div class="text-slate-600 mb-1">Volume (Liter)</div>
            <input :value="num(h.sc_header?.po_volume)" class="input !text-right bg-slate-50" readonly />
          </div>
          <div><div class="text-slate-600 mb-1">Amount</div>
            <input :value="money(h.sc_header?.po_amount)" class="input !text-right bg-slate-50" readonly />
          </div>
          <div><div class="text-slate-600 mb-1">Schedule Payment</div>
            <input :value="(h.sc_header?.proposed_status===1?'Proposed':'Not Proposed')" class="input bg-slate-50" readonly />
          </div>
          <div><div class="text-slate-600 mb-1">Add TOP</div>
            <input :value="h.sc_header?.add_top ? 'Ya' : 'Tidak'" class="input bg-slate-50" readonly />
          </div>
          <div><div class="text-slate-600 mb-1">Add CL</div>
            <input :value="h.sc_header?.add_cl ? 'Ya' : 'Tidak'" class="input bg-slate-50" readonly />
          </div>
        </div>
  
        <div class="grid grid-cols-3 gap-4">
          <div>
            <div class="text-slate-600 mb-1">Payment Type</div>
            <input :value="ptLabel(h.sc_header?.type_customer)" class="input bg-slate-50" readonly />
          </div>
          <div v-if="h.sc_header?.type_customer==1">
            <div class="text-slate-600 mb-1">Commitment Amount</div>
            <input :value="money(h.sc_header?.customer_amount)" class="input !text-right bg-slate-50" readonly />
          </div>
          <div v-if="h.sc_header?.type_customer==1">
            <div class="text-slate-600 mb-1">Commitment Date</div>
            <input :value="fmtDate(h.sc_header?.customer_date)" class="input bg-slate-50" readonly />
          </div>
        </div>
  
        <div v-if="h.sc_header?.lampiran_unblock">
          <a :href="storageUrl(h.sc_header.lampiran_unblock)" target="_blank" class="text-sky-600 underline">
            Lihat Lampiran Unblock ({{ h.sc_header.lampiran_unblock_ori }})
          </a>
        </div>
  
        <div class="grid grid-cols-6 gap-3 pt-3">
          <div class="col-span-6 font-medium">Balance AR</div>
          <input :value="money(h.sc_header?.not_yet)"        class="input !text-right bg-slate-50" readonly />
          <input :value="money(h.sc_header?.ov_up_07)"       class="input !text-right bg-slate-50" readonly />
          <input :value="money(h.sc_header?.ov_under_30)"    class="input !text-right bg-slate-50" readonly />
          <input :value="money(h.sc_header?.ov_under_60)"    class="input !text-right bg-slate-50" readonly />
          <input :value="money(h.sc_header?.ov_under_90)"    class="input !text-right bg-slate-50" readonly />
          <input :value="money(h.sc_header?.ov_up_90)"       class="input !text-right bg-slate-50" readonly />
        </div>
  
        <div v-if="h.approval">
          <div class="mt-3 text-slate-600">Catatan Admin Finance</div>
          <div class="p-3 border rounded bg-slate-50" v-html="h.approval.adm_summary || '-'"></div>
        </div>
      </div>
  
      <!-- Form Keputusan BM -->
      <div class="border rounded bg-white p-4 space-y-4">
        <div class="font-medium">Keputusan Branch Manager</div>
        <div>
          <label class="mr-6"><input type="radio" :value="1" v-model.number="bm.result" /> Setuju</label>
          <label><input type="radio" :value="2" v-model.number="bm.result" /> Tidak</label>
        </div>
        <div>
          <div class="mb-1 text-slate-600">Catatan BM</div>
          <textarea v-model="bm.summary" class="input h-28"></textarea>
        </div>
        <div class="flex gap-2">
          <button class="btn-primary" @click="save">Simpan</button>
          <button class="btn" @click="router.back()">Batal</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import {ref, onMounted} from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRoute, useRouter } from 'vue-router'
  
  const route = useRoute(); const router = useRouter();
  const idPoc = Number(route.params.id)
  
  const h = ref<any>({}) // header payload from API
  const bm = ref<{result:number|null, summary:string}>({ result: null, summary: '' })
  
  function fmtDate(d?:string){ return d? new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-' }
  function fmtDispTime(d?:string){ if(!d) return '-'; const t=new Date(d.replace(' ','T')); const p=(n:number)=>String(n).padStart(2,'0'); return `${p(t.getDate())}/${p(t.getMonth()+1)}/${t.getFullYear()}, ${p(t.getHours())}.${p(t.getMinutes())}.${p(t.getSeconds())}` }
  function money(n:any){ return Number(n||0).toLocaleString('id-ID') }
  function num(n:any){ return Number(n||0).toLocaleString('id-ID') }
  function ptLabel(v:any){ return v==1?'Customer Commitment': v==2?'Customer Collateral':'-' }
  function dispClass(v?:number){ const s=String(v??''); return {'text-emerald-600':s==='1','text-orange-600':s==='2'||s==='3','text-emerald-700':s==='4','text-red-600':s==='5','text-slate-600':!['1','2','3','4','5'].includes(s)} }
  function storageUrl(path:string){ return path ? `/storage/${path.replace(/^\/?storage\//,'')}` : '#' }
  
  async function load(){
    const {data} = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    h.value = data
    // prefilling BM jika pernah isi
    if (data.approval) {
      bm.value.result = data.approval.bm_result || null
      bm.value.summary = (data.approval.bm_summary || '').replace(/<br\s*\/?>/gi, '\n')
    }
  }
  onMounted(load)
  
  async function save(){
    if (bm.value.result == null) {
      Swal.fire('Validasi', 'Pilih persetujuan BM (Setuju/Tidak).', 'warning'); return
    }
    try{
      await axios.post(`/api/sales-confirmations/po/${idPoc}/bm`, {
        bm_result: bm.value.result,
        bm_summary: bm.value.summary
      })
      Swal.fire({icon:'success', title:'Keputusan BM tersimpan', timer:1400, showConfirmButton:false})
      router.back()
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan keputusan BM', 'error')
    }
  }
  </script>
  
  <style scoped>
  .input{ @apply w-full border rounded px-3 py-2; }
  .btn{ @apply border rounded px-3 py-2; }
  .btn-primary{ @apply bg-sky-600 text-white rounded px-4 py-2; }
  </style>
  