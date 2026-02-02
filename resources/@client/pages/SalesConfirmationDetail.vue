<template>
    <div class="p-6 space-y-6">
      <div class="text-lg font-semibold">Sales Confirmation</div>
  
      <!-- Header tanggal -->
      <div class="text-sm text-slate-700">
        <div>Date/Periode: <span class="font-medium">{{ fmtDate(header.poc?.tanggal_poc) }}</span></div>
        <div>Supply Date : <span class="font-medium">{{ fmtDate(header.poc?.supply_date) }}</span></div>
      </div>
  
      <!-- Header customer -->
      <div class="border rounded bg-white">
        <div class="grid grid-cols-6 gap-2 border-b p-3 font-medium text-center">
          <div class="col-span-2">Customer Code</div>
          <div class="col-span-2">Customer Name</div>
          <div>TOP</div>
          <div>Credit Limit</div>
        </div>
        <div class="grid grid-cols-6 gap-2 p-3 text-center">
          <div class="col-span-2">{{ header.customer?.kode_pelanggan || '-' }}</div>
          <div class="col-span-2">{{ header.customer?.nama_perusahaan || '-' }}</div>
          <div>{{ header.penawaran?.top || '-' }}</div>
          <div>{{ money(header.customer?.credit_limit) }}</div>
        </div>
      </div>
  
      <!-- Balance AR -->
      <div class="border rounded bg-white">
        <div class="px-4 py-2 border-b font-medium">Balance AR</div>
  
        <div class="grid grid-cols-6 gap-3 p-4 text-center font-medium text-slate-700">
          <div>Not Yet</div>
          <div>Overdue 1-7 Days</div>
          <div>Overdue 8-30 Days</div>
          <div>Overdue 31-60 Days</div>
          <div>Overdue 61-90 Days</div>
          <div>Overdue &gt; 90 Days</div>
        </div>
  
        <div class="grid grid-cols-6 gap-3 px-4 pb-4">
          <input v-model.number="form.not_yet"        class="input !text-right" />
          <input v-model.number="form.ov_up_07"       class="input !text-right" />
          <input v-model.number="form.ov_under_30"    class="input !text-right" />
          <input v-model.number="form.ov_under_60"    class="input !text-right" />
          <input v-model.number="form.ov_under_90"    class="input !text-right" />
          <input v-model.number="form.ov_up_90"       class="input !text-right" />
        </div>
  
        <div class="flex justify-end items-center gap-4 px-4 pb-4">
          <div class="text-slate-600">Remaining</div>
          <div class="w-64">
            <input :value="money(remaining)" readonly class="input !text-right bg-slate-50" />
          </div>
        </div>
      </div>
  
      <!-- PO & Proposal -->
      <div class="border rounded bg-white p-4 space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <div>
            <div class="mb-1 text-slate-600">PO Type</div>
            <label class="mr-6"><input type="radio" value="new"     v-model="form.po_status" /> New PO</label>
            <label><input type="radio" value="partial" v-model="form.po_status" /> Partial PO / Contract</label>
          </div>
  
          <div>
            <div class="mb-1 text-slate-600">Volume (Liter)</div>
            <input v-model.number="form.po_volume" type="number" step="1" class="input !text-right" />
          </div>
  
          <div>
            <div class="mb-1 text-slate-600">Amount</div>
            <input :value="amountDisplay" @input="onAmountInput" inputmode="numeric" class="input !text-right" />
          </div>
        </div>
  
        <div>
          <div class="mb-1 text-slate-600">Payment Type</div>
          <label class="mr-6"><input type="radio" value="1" v-model="form.type_customer" /> Customer Commitment</label>
          <label><input type="radio" value="2" v-model="form.type_customer" /> Customer Collateral</label>
        </div>
  
        <div v-if="form.type_customer == 1" class="grid grid-cols-3 gap-4">
          <div>
            <div class="mb-1 text-slate-600">Commitment Amount</div>
            <input v-model.number="form.customer_amount" class="input !text-right" />
          </div>
          <div>
            <div class="mb-1 text-slate-600">Commitment Date</div>
            <input type="date" v-model="form.customer_date" class="input" />
          </div>
        </div>
  
        <div v-if="form.type_customer == 2">
          <div class="mb-2 text-slate-600">Collateral Items</div>
          <div v-for="(c, i) in collateral" :key="i" class="grid grid-cols-3 gap-2 mb-2">
            <input v-model="c.item"   placeholder="Item"   class="input" />
            <input v-model.number="c.amount" placeholder="Amount" class="input !text-right" />
            <input type="date" v-model="c.date" class="input" />
          </div>
          <button class="btn" @click="collateral.push({item:'',amount:0,date:''})">+ Add Row</button>
        </div>
  
        <div>
          <div class="mb-1 text-slate-600">Schedule Payment</div>
          <label class="mr-6"><input type="radio" :value="0" v-model.number="form.proposed_status" /> Not Proposed</label>
          <label><input type="radio" :value="1" v-model.number="form.proposed_status" /> Proposed</label>
        </div>
  
        <div v-if="form.proposed_status === 1" class="grid grid-cols-3 gap-4">
          <div>
            <div class="mb-1 text-slate-600">Attachment Unblock (PDF/JPG/PNG ≤2MB)</div>
            <input type="file" @change="onFile" />
          </div>
          <div>
            <label><input type="checkbox" v-model="form.add_top" /> Add TOP</label>
          </div>
          <div>
            <label><input type="checkbox" v-model="form.add_cl" /> Add CL</label>
          </div>
        </div>
  
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-2">
            <div class="mb-1 text-slate-600">Catatan Admin Finance</div>
            <textarea v-model="form.admin_summary" class="input h-24"></textarea>
          </div>
          <div>
            <div class="mb-1 text-slate-600">Persetujuan</div>
            <label class="block mb-1"><input type="radio" :value="1" v-model.number="form.approval" /> Ya</label>
            <label class="block"><input type="radio" :value="2" v-model.number="form.approval" /> Tidak</label>
          </div>
        </div>
  
        <div class="flex gap-2">
          <button class="btn-primary" @click="save">Simpan</button>
          <button class="btn" @click="router.back()">Batal</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted, watch } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRoute, useRouter } from 'vue-router'
  
  const route = useRoute()
  const router = useRouter()
  const idPoc = Number(route.params.id)
  
  const header = ref<any>({})
  const pricePerLiter = ref<number>(0)
  
  const form = ref<any>({
    credit_limit: 0,
    not_yet: 0, ov_up_07: 0, ov_under_30: 0, ov_under_60: 0, ov_under_90: 0, ov_up_90: 0,
    po_status: 'new',
    po_volume: 0,     // LITER (apa adanya)
    po_amount: 0,     // total rupiah
    reminding: '',
    proposed_status: 0, add_top: 0, add_cl: 0,
    type_customer: null, customer_amount: 0, customer_date: '',
    approval: 0, admin_summary: '',
  })
  const collateral = ref<Array<{item:string, amount:number, date:string}>>([])
  const fileRef = ref<File|null>(null)
  
  const amountDisplay = ref('')
  
  const remaining = computed(() => {
    const cl = Number(header.value?.customer?.credit_limit ?? 0)
    const t =
      Number(form.value.not_yet || 0) +
      Number(form.value.ov_up_07 || 0) +
      Number(form.value.ov_under_30 || 0) +
      Number(form.value.ov_under_60 || 0) +
      Number(form.value.ov_under_90 || 0) +
      Number(form.value.ov_up_90 || 0)
    return cl - t
  })
  
  function money(n:any){
    const x = Number(n || 0)
    return x.toLocaleString('id-ID')
  }
  function fmtDate(d?: string){
    if(!d) return '-'
    return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' })
  }
  function onFile(e:any){ fileRef.value = e.target.files?.[0] ?? null }
  
  // Format input Amount dengan pemisah ribuan
  function onAmountInput(e:any){
    const raw = String(e.target.value)
    const sanitized = raw.replace(/[^\d]/g,'') // angka saja
    const num = Number(sanitized || '0')
    form.value.po_amount = num
    amountDisplay.value = num.toLocaleString('id-ID')
  }
  
  async function load(){
    const { data } = await axios.get(`/api/sales-confirmations/po/${idPoc}`)
    header.value = data
    pricePerLiter.value = Number(data?.poc?.harga_poc ?? 0)
  
    Object.assign(form.value, {
      credit_limit: data.customer?.credit_limit ?? 0,
      not_yet: data.arnya?.not_yet ?? 0,
      ov_up_07: data.arnya?.ov_up_07 ?? 0,
      ov_under_30: data.arnya?.ov_under_30 ?? 0,
      ov_under_60: data.arnya?.ov_under_60 ?? 0,
      ov_under_90: data.arnya?.ov_under_90 ?? 0,
      ov_up_90: data.arnya?.ov_up_90 ?? 0,
  
      // Volume ditampilkan apa adanya dalam Liter
      po_volume: Number(data.poc?.volume_poc ?? 0),
  
      // Amount = harga/liter × volume (bila harga tersedia)
      po_amount: pricePerLiter.value * Number(data.poc?.volume_poc ?? 0),
    })
  
    amountDisplay.value = form.value.po_amount.toLocaleString('id-ID')
  }
  
  // Recalc amount saat volume berubah
  watch(() => form.value.po_volume, v => {
    const total = pricePerLiter.value * Number(v || 0)
    form.value.po_amount = total
    amountDisplay.value = total.toLocaleString('id-ID')
  })
  
  async function save(){
    try{
      const fd = new FormData()
      for (const k in form.value) {
        const val = (form.value as any)[k]
        fd.append(k, val == null ? '' : val)
      }
      // kirim volume DALAM LITER (apa adanya)
      fd.set('po_volume', String(Number(form.value.po_volume || 0)))
  
      if (fileRef.value) fd.append('lampiran_unblock', fileRef.value)
  
      // collateral arrays
      collateral.value.forEach((c,i)=>{
        fd.append(`item_coll[${i}]`, c.item || '')
        fd.append(`customer_amount_coll[${i}]`, String(c.amount || 0))
        fd.append(`customer_date_coll[${i}]`, c.date || '')
      })
  
      await axios.post(`/api/sales-confirmations/po/${idPoc}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
  
      Swal.fire({ icon:'success', title:'Tersimpan', timer:1400, showConfirmButton:false })
    }catch(e:any){
      Swal.fire('Gagal', e?.response?.data?.message || 'Gagal menyimpan', 'error')
    }
  }
  
  onMounted(load)
  </script>
  
  <style scoped>
  .input{ @apply w-full border rounded px-3 py-2; }
  .btn{ @apply border rounded px-3 py-2; }
  .btn-primary{ @apply bg-sky-600 text-white rounded px-4 py-2; }
  </style>
  