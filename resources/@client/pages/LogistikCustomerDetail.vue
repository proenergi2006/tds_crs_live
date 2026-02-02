<template>
    <div class="min-h-screen bg-slate-50">
      <!-- Header -->
      <div class="bg-sky-700 text-white">
        <div class="max-w-6xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Verifikasi Data Customer — Logistik</div>
          <div class="text-white/80 text-sm">Hanya kolom logistik yang bisa diubah</div>
        </div>
      </div>
  
      <div class="max-w-6xl mx-auto px-5 py-6 space-y-5">
        <button class="px-3 py-2 rounded border" @click="router.back()">Kembali</button>
  
        <!-- Ringkasan (read-only) -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Rincian Customer</div>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="lbl">Customer Name</label>
              <input class="form" :value="header.nama_perusahaan" disabled>
            </div>
            <div>
              <label class="lbl">Business Type</label>
              <input class="form" :value="header.business_type" disabled>
            </div>
            <div class="md:col-span-2">
              <label class="lbl">Alamat</label>
              <textarea class="form" :value="header.alamat" rows="2" disabled></textarea>
            </div>
          </div>
        </div>
  
        <!-- Bagian Logistik (editable) -->
        <div class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-3">Verifikasi Logistik</div>
  
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="lbl">Logistik Summary</label>
              <textarea class="form" rows="8" v-model="form.logistik_summary" />
            </div>
  
            <div>
              <label class="lbl">Logistik Result</label>
              <input class="form" v-model="form.logistik_result" placeholder="Supply Delivery / With Note / ..."/>
  
              <div class="mt-4">
                <div class="lbl mb-1">Assessment Result *</div>
                <label class="block">
                  <input type="radio" value="Supply Delivery" v-model="form.assessment_result" />
                  <span class="ml-2">Supply Delivery</span>
                </label>
                <label class="block mt-1">
                  <input type="radio" value="Supply Delivery With Note" v-model="form.assessment_result" />
                  <span class="ml-2">Supply Delivery With Note</span>
                </label>
                <label class="block mt-1">
                  <input type="radio" value="Revised and Resubmitted" v-model="form.assessment_result" />
                  <span class="ml-2">Revised and Resubmitted</span>
                </label>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Actions -->
        <div class="flex gap-2">
          <button class="px-4 py-2 rounded bg-sky-600 text-white hover:bg-sky-700"
                  :disabled="busy" @click="save">
            {{ busy ? 'Menyimpan…' : 'Simpan' }}
          </button>
          <button class="px-4 py-2 rounded border" @click="router.back()">Batal</button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import {ref, reactive, onMounted} from 'vue'
  import {useRoute, useRouter} from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  const route = useRoute()
  const router = useRouter()
  const id = Number(route.params.id)
  
  const header = reactive({
    nama_perusahaan: '-',
    business_type: '-',
    alamat: '-',
  })
  
  const form = reactive({
    logistik_summary: '',
    logistik_result: 'Supply Delivery With Note',
    assessment_result: '',
  })
  
  const busy = ref(false)
  
  async function load() {
    // meta untuk header + nilai logistik tersimpan
    const { data } = await axios.get(`/api/review/logistik/customer-verifications/${id}`)
    header.nama_perusahaan = data.customer?.nama_perusahaan || '-'
    header.business_type   = data.business_type || '-'
    header.alamat          = data.customer?.alamat_perusahaan || '-'
  
    if (data.form) {
      form.logistik_summary  = data.form.logistik_summary ?? ''
      form.logistik_result   = data.form.logistik_result ?? 'Supply Delivery With Note'
      form.assessment_result = data.form.assessment_result ?? ''
    }
  }
  
  async function save(){
    busy.value = true
    try{
      await axios.patch(`/api/review/logistik/customer-verifications/${id}`, {
        logistik_summary:  form.logistik_summary,
        logistik_result:   form.logistik_result,
        assessment_result: form.assessment_result,
      })
      Swal.fire('Berhasil', 'Data logistik tersimpan.', 'success')
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan', 'error')
    }finally{
      busy.value = false
    }
  }
  
  onMounted(load)
  </script>
  
  <style scoped>
  .form { @apply w-full border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-sky-200; }
  .lbl  { @apply block text-sm text-slate-600 mb-1; }
  </style>
  