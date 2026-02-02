<template>
  <div class="min-h-screen bg-slate-50">
    <div class="bg-green-700 text-white">
      <div class="max-w-6xl mx-auto px-5 py-4">
        <div class="text-2xl font-semibold">Detail Verifikasi — BM</div>
        <div class="text-white/80 text-sm">Lihat evaluasi Admin & hasil Logistik, lalu putuskan.</div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-5 py-6 space-y-5">
      <button class="px-3 py-2 rounded border" @click="goBack">Kembali</button>

      <!-- Header identitas -->
      <div class="bg-white rounded shadow p-4">
        <div class="font-semibold text-slate-700 mb-3">Rincian Customer</div>
        <div class="grid md:grid-cols-2 gap-4">
          <div><div class="lbl">Customer</div><input class="form" :value="identitas.nama_perusahaan" disabled/></div>
          <div><div class="lbl">Business Type</div><input class="form" :value="businessType" disabled/></div>
          <div class="md:col-span-2"><div class="lbl">Alamat</div><textarea class="form" :value="identitas.alamat_perusahaan" rows="2" disabled/></div>
        </div>
      </div>

      <!-- Evaluasi Admin (readonly) -->
      <div class="bg-white rounded shadow p-4">
        <div class="font-semibold text-slate-700 mb-3">Evaluasi (Admin)</div>
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <div class="lbl">TOP</div>
            <input class="form" :value="evaluation.top_text || '-'" disabled>
          </div>
          <div>
            <div class="lbl">Potential Volume</div>
            <input class="form" :value="evaluation.potential_volume || '-'" disabled>
          </div>
          <div class="md:col-span-2">
            <div class="lbl">Pengajuan Kredit Limit</div>
            <div class="flex items-center gap-2">
              <span class="inline-block border rounded px-3 py-2 select-none">Rp.</span>
              <input class="form" :value="evaluation.credit_limit_request || '-'" disabled>
            </div>
          </div>
          <div class="md:col-span-2">
            <div class="lbl">Financial Review</div>
            <textarea class="form" :value="evaluation.financial_review || '-'" rows="3" disabled/>
          </div>
        </div>
      </div>

      <!-- Hasil Logistik (readonly) -->
      <div class="bg-white rounded shadow p-4">
        <div class="font-semibold text-slate-700 mb-3">Hasil Verifikasi (Logistik)</div>
        <div class="grid md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <div class="lbl">Logistik Summary</div>
            <textarea class="form" :value="logi.summary || '-'" rows="3" disabled/>
          </div>
          <div>
            <div class="lbl">Logistik Result</div>
            <input class="form" :value="logi.result_text || (logi.result_flag===1 ? 'OK' : '-')" disabled>
          </div>
          <div>
            <div class="lbl">Assessment Result</div>
            <input class="form" :value="logi.assessment || '-'" disabled>
          </div>
        </div>
      </div>

      <!-- Keputusan BM -->
      <div class="bg-white rounded shadow p-4">
        <div class="font-semibold text-slate-700 mb-3">Keputusan BM</div>
        <div class="grid md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <div class="lbl">Catatan BM</div>
            <textarea class="form" rows="4" v-model="bm.notes"></textarea>
          </div>
          <div class="md:col-span-2">
            <div class="lbl mb-1">Decision</div>
            <label class="block"><input type="radio" value="APPROVE" v-model="bm.decision"> <span class="ml-2">Approve (kirim ke OM)</span></label>
            <label class="block mt-1"><input type="radio" value="REVISE"  v-model="bm.decision"> <span class="ml-2">Revisi (kembalikan ke Logistik)</span></label>
            <label class="block mt-1"><input type="radio" value="REJECT"  v-model="bm.decision"> <span class="ml-2">Reject (tahan di BM)</span></label>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <button class="px-4 py-2 rounded bg-violet-600 text-white hover:bg-green-700" :disabled="busy" @click="saveBm">
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </button>
          <button class="px-4 py-2 rounded border" @click="goBack">Batal</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const identitas = reactive({ nama_perusahaan:'-', alamat_perusahaan:'-' })
const businessType = ref('-')

// evaluasi admin (readonly)
const evaluation = reactive<any>({
  top_text:'', potential_volume:'', credit_limit_request:'', financial_review:''
})

// hasil logistik (readonly)
const logi = reactive<any>({
  summary:'', result_text:'', result_flag:0, assessment:''
})

// form BM
const bm = reactive({ notes:'', decision:'APPROVE' })
const busy = ref(false)

async function load() {
  const [metaRes, adminRes] = await Promise.all([
    axios.get(`/api/review/customer-verifications/${id}`),
    axios.get(`/api/review/customer-verifications/${id}/admin-evaluation`).catch(() => ({ data: null }))
  ])

  const data = metaRes.data || {}
  identitas.nama_perusahaan   = data?.customer?.nama_perusahaan || data?.legal?.corporate?.nama || '-'
  identitas.alamat_perusahaan = data?.customer?.alamat_perusahaan || data?.legal?.corporate?.alamat || '-'
  businessType.value          = data?.legal?.corporate?.tipe_bisnis_text || 'Manufacture'

  // logistik
  logi.summary     = data?.logistik_summary || ''
  logi.result_flag = data?.logistik_result ?? 0
  logi.result_text = data?.logistik_result_text || ''
  logi.assessment  = data?.assessment_result || ''

  // admin evaluation dari API baru
  const ev = adminRes.data
  evaluation.top_text             = ev?.top_text || '-'
  evaluation.potential_volume     = ev?.potential_volume || '-'
  evaluation.credit_limit_request = ev?.credit_limit_request || '-'
  evaluation.financial_review     = ev?.financial_review || '-'
}

async function saveBm(){
  busy.value = true
  try {
    await axios.patch(`/api/review/bm/customer-verifications/${id}/verify`, {
      notes: bm.notes,
      decision: bm.decision
    })
    await Swal.fire('Tersimpan', 'Keputusan BM berhasil disimpan.', 'success')
    router.push({ name: 'verify-data-customer-bm' })
  } catch (e:any) {
    Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
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
