<template>
    <div class="min-h-screen bg-slate-50">
      <div class="bg-green-700 text-white">
        <div class="max-w-6xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Detail Verifikasi — OM</div>
          <div class="text-white/80 text-sm">RC{{ id }}</div>
        </div>
      </div>
  
      <div class="max-w-6xl mx-auto px-5 py-6 space-y-5">
        <button class="px-3 py-2 rounded border" @click="goBack">Kembali</button>
  
        <!-- Identitas -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Rincian Customer</div>
          <div class="grid md:grid-cols-2 gap-4">
            <RO label="Customer" :value="identitas.nama_perusahaan || '-'" />
            <RO label="Business Type" :value="businessType" />
            <div class="md:col-span-2">
              <RO label="Alamat" :value="identitas.alamat_perusahaan || '-'" />
            </div>
          </div>
        </div>
  
        <!-- Evaluasi Admin (readonly) -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Evaluasi (Admin)</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="TOP" :value="ev.form?.top_text || '-'" />
            <RO label="Potential Volume (m³)" :value="ev.form?.potential_volume || '-'" />
            <RO label="Pengajuan Kredit Limit" :value="ev.form?.credit_limit_request || '-'" />
            <RO label="Financial Review" :value="ev.form?.financial_review || '-'" />
          </div>
        </div>
  
        <!-- Catatan Logistik (readonly) -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Catatan Logistik</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Logistik Summary" :value="ev.form?.approval?.logistik_summary || '-'" />
            <RO label="Assessment Result" :value="ev.form?.approval?.assessment_result || '-'" />
          </div>
        </div>
  
        <!-- Keputusan OM -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Keputusan OM</div>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="lbl">Hasil</label>
              <select class="form" v-model.number="om.result">
                <option :value="1">Approve</option>
                <option :value="0">Reject</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="lbl">Catatan / Summary</label>
              <textarea class="form" rows="4" v-model="om.summary" />
            </div>
          </div>
        </div>
  
        <div class="flex gap-2">
          <button class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700" :disabled="busy" @click="save">
            {{ busy ? 'Menyimpan…' : 'Simpan Verifikasi OM' }}
          </button>
          <button class="px-4 py-2 rounded border" @click="goBack">Batal</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, computed, onMounted, defineComponent, h } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  const RO = defineComponent({
    name: 'RO',
    props: { label: { type: String, required: true }, value: { type: [String, Number, Boolean, Object, Array, null] as any, default: '-' } },
    setup(props) {
      return () => h('div', { class: 'space-y-1' }, [
        h('div', { class: 'text-slate-500 text-sm' }, props.label),
        h('div', { class: 'text-slate-800' }, String(props.value ?? '-')),
      ])
    },
  })
  
  const route = useRoute()
  const router = useRouter()
  const id = Number(route.params.id)
  
  const identitas = reactive({ nama_perusahaan:'', alamat_perusahaan:'', ownership:'' })
  const customer = ref<any>({})
  const legal = ref<any>({})
  const ev = reactive<any>({ form: null })
  
  const businessType = computed(() =>
    legal.value?.corporate?.tipe_bisnis_text || customer.value?.tipe_bisnis_text || '-'
  )
  
  const om = reactive<{result:number; summary:string}>({ result: 1, summary: '' })
  const busy = ref(false)
  
  async function load() {
    // meta + evaluation
    const [metaRes, evRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${id}`),
      axios.get(`/api/review/customer-verifications/${id}/evaluation`).catch(() => ({ data: null })),
    ])
    const meta = metaRes.data || {}
    customer.value = meta.customer || {}
    legal.value    = meta.legal || {}
  
    identitas.nama_perusahaan   = meta.customer?.nama_perusahaan || meta.legal?.corporate?.nama || ''
    identitas.alamat_perusahaan = meta.customer?.alamat_perusahaan || meta.legal?.corporate?.alamat || ''
  
    ev.form = evRes?.data?.form || null
  }
  
  async function save(){
    busy.value = true
    try{
      await axios.patch(`/api/review/om/customer-verifications/${id}/verify`, {
        result: om.result,
        summary: om.summary,
      })
      await Swal.fire('Berhasil','Verifikasi OM tersimpan.','success')
      router.push({ name:'verify-data-customer-om' })
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan','error')
    }finally{
      busy.value = false
    }
  }
  
  function goBack(){ router.back() }
  onMounted(load)
  </script>
  
  <style scoped>
  .form { @apply w-full border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-green-200; }
  .lbl  { @apply block text-sm text-slate-600 mb-1; }
  </style>
  