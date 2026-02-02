<!-- src/views/CustomerVerification/ReviewCustomerDetail.vue -->
<template>
    <div class="min-h-screen bg-slate-50">
      <!-- Header -->
      <div class="bg-emerald-700 text-white">
        <div class="max-w-6xl mx-auto px-5 py-4">
          <div class="text-2xl font-semibold">Review Data Customer</div>
          <div class="text-white/80 text-sm">Validasi hasil update dari form publik</div>
        </div>
      </div>
  
      <div class="max-w-6xl mx-auto px-5 py-6 space-y-5">
        <button class="px-3 py-2 rounded border" @click="goBack">Kembali</button>
  
        <!-- Tabs -->
        <div class="flex gap-2">
          <button :class="tab==='evaluation' ? 'tab-active' : 'tab'" @click="tab='evaluation'">
            Evaluasi Calon Customer
          </button>
          <button :class="tab==='review' ? 'tab-active' : 'tab'" @click="tab='review'">
            Review Customer
          </button>
          <button :class="tab==='data' ? 'tab-active' : 'tab'" @click="tab='data'">
            Data Customer
          </button>
        </div>
  
        <!-- ================== TAB: EVALUATION ================== -->
        <div v-if="tab==='evaluation'" class="space-y-5">
          <!-- Form Evaluasi (header) -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-3">Form Evaluasi</div>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="lbl">Customer Name</label>
                <input class="form" :value="evaluation.customer_name" disabled />
              </div>
              <div>
                <label class="lbl">Business Type</label>
                <input class="form" :value="evaluation.business_type" disabled />
              </div>
            </div>
          </div>
  
          <!-- Pengajuan -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-2">Pengajuan</div>
  
            <div>
              <label class="lbl">TOP</label>
              <input class="form" v-model="evaluation.top_text" placeholder="CREDIT 30 days After Invoice Receive" />
            </div>
  
            <div class="grid md:grid-cols-[1fr,160px] gap-4 mt-4">
              <div>
                <label class="lbl">Potential Volume</label>
                <input class="form" v-model="evaluation.potential_volume" placeholder="0" />
              </div>
              <div>
                <label class="lbl">&nbsp;</label>
                <input class="form" value="m³" disabled />
              </div>
            </div>
  
            <div class="mt-4">
              <label class="lbl">Pengajuan Kredit Limit</label>
              <div class="flex items-center gap-2">
                <span class="inline-block border rounded px-3 py-2 select-none">Rp.</span>
                <!-- READ-ONLY dari meta/review -->
                <input class="form" :value="evaluation.credit_limit_request" disabled />
              </div>
            </div>
  
            <div class="mt-4">
              <label class="lbl">Jenis Data</label>
              <select class="form" v-model="evaluation.jenis_data">
                <option value="SEBELUM">Sebelum Persetujuan Komite</option>
                <option value="SETELAH">Setelah Persetujuan Komite</option>
              </select>
            </div>
  
            <div class="mt-4">
              <label class="lbl">Financial Review*</label>
              <textarea class="form" rows="5" v-model="evaluation.financial_review" />
            </div>
          </div>
  
          <!-- Persetujuan (muncul hanya setelah komite) -->
          <div v-if="evaluation.jenis_data==='SETELAH'" class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-2">Persetujuan</div>
  
            <div class="grid md:grid-cols-[auto,1fr] items-center gap-3">
              <label class="lbl">Persetujuan Kredit Limit</label>
              <div class="flex items-center gap-2">
                <span class="inline-block border rounded px-3 py-2 select-none">Rp.</span>
                <input class="form" v-model="approval.approval_credit_limit" placeholder="0" />
              </div>
  
              <label class="lbl">Payment Type</label>
              <select class="form" v-model="approval.payment_type">
                <option value="CREDIT">CREDIT</option>
                <option value="CASH">CASH</option>
              </select>
  
              <label class="lbl">TOP (Days)</label>
              <input class="form" v-model="approval.top_days" placeholder="30" />
  
              <label class="lbl">TOP Basis</label>
              <select class="form" v-model="approval.top_basis">
                <option value="After Invoice Receive">After Invoice Receive</option>
                <option value="After Delivery">After Delivery</option>
              </select>
  
              <label class="lbl">Group of Company*</label>
              <input class="form" v-model="approval.group_company" placeholder="-" />
            </div>
  
            <div class="mt-6">
              <div class="lbl mb-2">Verification Document*</div>
              <div class="grid md:grid-cols-2 gap-y-2">
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.customer_db" /> Customer Data Base</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.customer_review" /> Customer Review</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.siup" /> SIUP</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.top" /> TOP</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.notarial" /> Notarial Deed</label>
                <div class="flex items-center gap-2">
                  <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.others" /> Others</label>
                  <input class="form" v-model="approval.docs_others_text" placeholder="Sebutkan" />
                </div>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.lcr" /> LCR</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.npwp" /> NPWP</label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" v-model="approval.docs.finstat" /> Financial Statement</label>
              </div>
            </div>
  
            <div class="mt-4">
              <label class="lbl">Dokumen Lainnya</label>
              <input class="form" v-model="approval.other_document" placeholder="-" />
            </div>
  
            <!-- Lampiran Dokumen KYC -->
            <div class="mt-8">
              <div class="font-semibold text-slate-700 mb-2">Lampiran Dokumen KYC</div>
  
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm border">
                  <thead class="bg-slate-100 text-slate-700">
                    <tr>
                      <th class="px-3 py-2 text-left w-24">No Urut</th>
                      <th class="px-3 py-2 text-left">Nama Lampiran</th>
                      <th class="px-3 py-2 text-left">File Lampiran</th>
                      <th class="px-3 py-2 w-24"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(r,idx) in kycRows" :key="idx" class="border-t">
                      <td class="px-3 py-2">{{ idx+1 }}</td>
                      <td class="px-3 py-2">
                        <input class="form" v-model="r.label" placeholder="Nama file / keterangan" />
                      </td>
                      <td class="px-3 py-2">
                        <div v-if="r.url" class="flex items-center gap-2">
                          <a :href="r.url" target="_blank" class="text-sky-600 hover:underline">{{ r.name || 'Lihat File' }}</a>
                          <span class="text-slate-500">({{ r.path }})</span>
                        </div>
                        <div v-else class="flex items-center gap-2">
                          <input type="file" @change="onKycFile($event, idx)" />
                          <button class="px-2 py-1 rounded bg-slate-200" :disabled="!r.file || uploadingKyc" @click="uploadKyc(idx)">
                            {{ uploadingKyc ? 'Mengunggah…' : 'Unggah File' }}
                          </button>
                        </div>
                      </td>
                      <td class="px-3 py-2 text-right">
                        <button class="px-2 py-1 rounded bg-rose-500 text-white" @click="removeKycRow(idx)">✕</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <div class="mt-3">
                <button class="px-3 py-2 rounded bg-sky-600 text-white hover:bg-sky-700" @click="addKycRow">+ Tambah Baris</button>
              </div>
            </div>
  
            <div class="mt-6 grid md:grid-cols-2 gap-6">
              <div>
                <label class="lbl">Logistik Summary</label>
                <textarea class="form" rows="5" v-model="approval.logistik_summary" />
                <div class="text-xs text-slate-500 mt-1" v-if="approval.logistik_summary_updated_by">
                  {{ approval.logistik_summary_updated_by }}
                </div>
              </div>
              <div>
                <label class="lbl">Logistik Result</label>
                <input class="form" v-model="approval.logistik_result" placeholder="Supply Delivery / With Note / etc" />
                <div class="mt-4">
                  <div class="lbl mb-1">Assessment Result *</div>
                  <label class="block"><input type="radio" value="Supply Delivery" v-model="approval.assessment_result" /> <span class="ml-2">Supply Delivery</span></label>
                  <label class="block mt-1"><input type="radio" value="Supply Delivery With Note" v-model="approval.assessment_result" /> <span class="ml-2">Supply Delivery With Note</span></label>
                  <label class="block mt-1"><input type="radio" value="Revised and Resubmitted" v-model="approval.assessment_result" /> <span class="ml-2">Revised and Resubmitted</span></label>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Actions Evaluation -->
          <div class="flex gap-2">
  <button
    class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
    @click="saveEvaluation"
   
  >
    {{ busyEvaluation ? 'Menyimpan…' : 'Simpan' }}
  </button>
  <button class="px-4 py-2 rounded border" @click="goBack">Batal</button>
</div>
        </div>
  
        <!-- ================== TAB: REVIEW ================== -->
        <div v-else-if="tab==='review'" class="space-y-5">
          <!-- Ringkasan -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-3">1. Rincian Customer</div>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="lbl">Customer*</label>
                <input class="form" :value="identitas.nama_perusahaan || '-'" disabled>
              </div>
              <div>
                <label class="lbl">Company Status</label>
                <input class="form" :value="companyStatus" disabled>
              </div>
              <div>
                <label class="lbl">Company Type</label>
                <input class="form" :value="ownershipText" disabled>
              </div>
              <div class="md:col-span-2">
                <label class="lbl">Alamat</label>
                <textarea class="form" :value="identitas.alamat_perusahaan || '-'" disabled rows="3"></textarea>
              </div>
            </div>
          </div>
  
          <!-- Informasi Umum -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-3">2. Informasi Umum</div>
            <div class="mt-2 grid grid-cols-12 gap-x-6 gap-y-4">
              <FormRow label="2.7 Alur pemeriksaan/review kelengkapan dokumen & rata-rata waktu yang dibutuhkan sampai proses pembayaran (gambarkan)" v-model="form.info.flow_review" />
              <FormRow label="2.8 Jadwal penerimaan invoice & jadwal pembayaran tagihan (jika ada, detailkan)" v-model="form.info.invoice_schedule" />
              <FormRow label="2.9 Siapa pemilik Authority terkait pembayaran? (Nama, Posisi, No. HP)" v-model="form.info.payment_authority" rows="2" />
              <FormRow label="2.10 Existing fuel vendor (Nama, credit term)" v-model="form.info.existing_vendor" rows="2" />
              <FormRow label="2.11 Historical/background bisnis/group (jika ada)" v-model="form.info.history" />
              <FormRow label="2.12 Lokasi depo sumber produk (terminal)" v-model="form.info.depot_location" rows="2" />
              <FormRow label="2.13 Opportunity business apa saja yang bisa dilakukan dengan perusahaan itu" v-model="form.info.opportunities" />
            </div>
          </div>
  
          <!-- Informasi detail -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-3">
              3. Informasi detail tentang pelanggan yang disesuaikan dengan industrial type (Opportunity vs Risk)
            </div>
              <div class="grid md:grid-cols-[auto,1fr] items-center gap-3">
                <label class="lbl">Credit Limit Proposed</label>
                <div class="flex items-center gap-2">
                  <span class="inline-block border rounded px-3 py-2 select-none">Rp.</span>
                  <input class="form" v-model="form.detail.credit_limit_proposed" placeholder="0" />
                </div>
  
                <label class="lbl self-start mt-2">Catatan Marketing/Key Account</label>
                <textarea class="form" v-model="form.detail.marketing_notes" rows="4"></textarea>
              </div>
          </div>
  
          <!-- Data lama (opsional) -->
          <div class="bg-white rounded shadow p-4">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-slate-700">4. Informasi Data Lama (Optional)</div>
            </div>
            <textarea class="form mt-3" rows="3" v-model="form.old_info" placeholder="Tulis ringkasan data lama bila perlu"></textarea>
          </div>
  
          <!-- Lampiran review -->
          <div class="bg-white rounded shadow p-4">
            <div class="font-semibold text-slate-700 mb-3">5. Lampiran</div>
            <div class="mb-3">
              <button class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                      @click="addFileRow" :disabled="disabledReviewActions">
                + Tambah File
              </button>
            </div>
            <div v-if="fileRows.length===0" class="text-slate-500 text-sm">Belum ada baris file.</div>
  
            <div v-for="(row,idx) in fileRows" :key="idx" class="flex items-center gap-2 border rounded px-3 py-2 mb-2">
              <div class="w-10 text-center">{{ idx+1 }}</div>
              <input type="file" class="grow" @change="onFile($event, idx)" :disabled="disabledReviewActions"/>
              <button class="px-2 py-2 rounded bg-slate-200" @click="removeFileRow(idx)" :disabled="disabledReviewActions">✕</button>
              <button class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                      :disabled="!row.file || uploading || disabledReviewActions"
                      @click="upload(idx)">
                {{ row.uploaded_url ? 'Terupload' : (uploading ? 'Mengunggah…' : 'Upload') }}
              </button>
            </div>
  
            <div v-if="form.attachments.length" class="mt-3">
              <div class="font-medium mb-2">File tersimpan:</div>
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="(f,i) in form.attachments" :key="i">
                  <a :href="f.url" target="_blank" class="text-sky-600 hover:underline">{{ f.name }}</a>
                </li>
              </ul>
            </div>
          </div>
  
          <!-- Actions -->
          <div class="flex gap-2">
            <button class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
                    @click="save" :disabled="busy || disabledReviewActions">
              {{ busy ? 'Menyimpan…' : 'Simpan' }}
            </button>
            <button class="px-4 py-2 rounded border" @click="markReviewed" :disabled="busy || disabledReviewActions">
              Tandai Reviewed
            </button>
            <span v-if="disabledReviewActions" class="text-sm text-amber-700 self-center">
              Persetujuan sudah berjalan (disposisi &gt; 0). Edit/unggah dinonaktifkan.
            </span>
          </div>
        </div>
  
        <!-- ================== TAB: DATA (READONLY) ================== -->
        <div v-else class="bg-white rounded shadow p-4">
          <div class="font-semibold text-slate-700 mb-4">1. Corporate Details</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Company Name" :value="legal?.corporate?.nama || customerName" />
            <RO label="Holding" :value="legal?.corporate?.holding || '-'" />
            <RO label="NPWP Address" class="md:col-span-2" :value="legal?.corporate?.alamat || '-'" />
            <RO label="Kelurahan" :value="legal?.corporate?.kelurahan || '-'" />
            <RO label="Kecamatan" :value="legal?.corporate?.kecamatan || '-'" />
            <RO label="Kota/Kabupaten" :value="legal?.corporate?.kota || '-'" />
            <RO label="Provinsi" :value="legal?.corporate?.provinsi || '-'" />
            <RO label="Kode Pos" :value="legal?.corporate?.postal_code || '-'" />
            <RO label="Telepon" :value="legal?.corporate?.telepon || customerTelp" />
            <RO label="Fax" :value="legal?.corporate?.fax || customerFax" />
          </div>
  
          <div class="mt-8 font-semibold text-slate-700 mb-3">1.5 Product Delivery & Invoice</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Delivery Address 1" :value="legal?.delivery?.alamat1 || '-'" />
            <RO label="Delivery Address 2" :value="legal?.delivery?.alamat2 || '-'" />
            <RO label="Delivery Address 3" :value="legal?.delivery?.alamat3 || '-'" />
            <RO label="Invoice Address" :value="finance?.invoice?.delivery_address || '-'" />
            <RO label="PIC Invoice" :value="finance?.invoice?.pic?.name || '-'" />
            <RO label="Mobile" :value="finance?.invoice?.pic?.mobile || '-'" />
            <RO label="Email" :value="finance?.invoice?.pic?.email || '-'" />
          </div>
  
          <div class="mt-8 font-semibold text-slate-700 mb-3">2. Person in Charge</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Director / Owner" :value="legal?.pic?.owner || '-'" />
            <RO label="Procurement" :value="legal?.pic?.procurement || '-'" />
            <RO label="Finance" :value="legal?.pic?.finance || '-'" />
            <RO label="Site / Fuelman PIC" :value="legal?.pic?.fuelman || '-'" />
          </div>
  
          <div class="mt-8 font-semibold text-slate-700 mb-3">3. Payment Term & Banking Detail</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Pricing Method" :value="finance?.payment?.pricing_method || '-'" />
            <RO label="Payment Method" :value="finance?.payment?.payment_method || '-'" />
            <RO label="Term of Payment" :value="finance?.payment?.term || '-'" />
            <RO label="Bank Name" :value="finance?.payment?.bank_name || '-'" />
            <RO label="Currency" :value="finance?.payment?.currency || '-'" />
            <RO label="Bank Address" :value="finance?.payment?.bank_address || '-'" />
            <RO label="Account Number" :value="finance?.payment?.account_number || '-'" />
            <RO label="Credit Facility" :value="finance?.payment?.has_credit ? 'Yes' : 'No'" />
          </div>
  
          <div class="mt-8 font-semibold text-slate-700 mb-3">4. Supply Scheme</div>
          <div class="grid md:grid-cols-2 gap-x-8 gap-y-3">
            <RO label="Scheme Details" :value="logistik?.supply?.scheme_details || '-'" />
            <RO label="Specify Product" :value="logistik?.supply?.specify_product || '-'" />
            <RO label="Volume per Month" :value="logistik?.supply?.volume_per_month || '-'" />
            <RO label="Operational Hour" :value="fmtRange(logistik?.supply?.operational_from, logistik?.supply?.operational_to)" />
            <RO label="INCO terms" :value="logistik?.supply?.inco_terms || '-'" />
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted, computed, defineComponent, h } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  /* -------- Readonly row (tanpa JSX) -------- */
  const RO = defineComponent({
    name: 'RO',
    props: { label: { type: String, required: true }, value: { type: [String, Number, Boolean, Object, Array, null] as any, default: '-' } },
    setup(props, { attrs }) {
      return () =>
        h('div', { class: ['space-y-1', (attrs as any).class] }, [
          h('div', { class: 'text-slate-500 text-sm' }, props.label),
          h('div', { class: 'text-slate-800' }, String(props.value ?? '-')),
        ])
    },
  })
  
  /* -------- Textarea row (tanpa JSX) -------- */
  const FormRow = defineComponent({
    name: 'FormRow',
    props: { label: { type: String, required: true }, modelValue: { type: String, default: '' }, rows: { type: [Number, String], default: 3 } },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
      const onInput = (e: Event) => emit('update:modelValue', (e.target as HTMLTextAreaElement).value)
      return () => [
        h('div', { class: 'col-span-12 md:col-span-4' }, [ h('label', { class: 'block text-sm text-slate-700' }, props.label) ]),
        h('div', { class: 'col-span-12 md:col-span-8' }, [ h('textarea', { class: 'form w-full', rows: Number(props.rows), value: props.modelValue, onInput }) ]),
      ]
    },
  })
  
  const disabledEvaluation = computed(() => lockedByDisposisi.value)
  const route  = useRoute()
  const router = useRouter()
  const id     = Number(route.params.id)
  
  const tab = ref<'evaluation'|'review'|'data'>('evaluation')
  
  // ======== STATE SHARED =========
  const companyStatus = 'Manufacture' // placeholder
  const identitas = reactive({ nama_perusahaan: '', ownership: '', alamat_perusahaan: '' })
  const customer = ref<any>({})
  const customerName = computed(() => identitas.nama_perusahaan || '-')
  const customerTelp = computed(() => customer.value?.telepon || '-')
  const customerFax  = computed(() => customer.value?.fax || '-')
  const ownershipText = computed(() => identitas.ownership || '-')
  const legal    = ref<any>({})
  const finance  = ref<any>({})
  const logistik = ref<any>({})
  
  function fmtRange(a?: string, b?: string){
    if(!a && !b) return '-'
    if(a && b) return `${a} - ${b}`
    return a || b || '-'
  }
  
  const lockedByDisposisi = ref(false)
  const disabledReviewActions = computed(()=>lockedByDisposisi.value)
  
  // ======== EVALUATION =========
  const evaluation = reactive<any>({
    customer_name: '-',           // read-only (prefill)
    business_type: '-',           // read-only (prefill)
    credit_limit_request: '',     // read-only (prefill)
    top_text: '',
    potential_volume: '',
    jenis_data: 'SEBELUM',        // SEBELUM / SETELAH
    financial_review: '',
  })
  
  const approval = reactive<any>({
    approval_credit_limit: '',
    payment_type: 'CREDIT',
    top_days: '30',
    top_basis: 'After Invoice Receive',
    group_company: '',
    docs: { customer_db:false, siup:false, notarial:false, lcr:false, npwp:false, finstat:false, customer_review:false, top:false, others:false },
    docs_others_text: '',
    other_document: '',
    logistik_summary: '',
    logistik_summary_updated_by: '',
    logistik_result: 'Supply Delivery With Note',
    assessment_result: '',
  })
  
  type KycRow = { label?: string; file?: File|null; url?: string; name?: string; path?: string }
  const kycRows = ref<KycRow[]>([{ label: '' }])
  const uploadingKyc = ref(false)
  
  function addKycRow(){ kycRows.value.push({ label: '' }) }
  function removeKycRow(i:number){ kycRows.value.splice(i,1); if (kycRows.value.length===0) kycRows.value.push({label:''}) }
  function onKycFile(e:Event, i:number){ const f=(e.target as HTMLInputElement).files?.[0]||null; kycRows.value[i].file=f }
  
  async function uploadKyc(i:number){
    const r = kycRows.value[i]; if(!r.file) return
    const fd = new FormData(); fd.append('file', r.file); fd.append('label', r.label || '')
    uploadingKyc.value = true
    try{
      const { data } = await axios.post(`/api/review/customer-verifications/${id}/evaluation-attachment`, fd, { headers:{'Content-Type':'multipart/form-data'} })
      kycRows.value[i] = { label: r.label, url: data.url, name: data.name, path: data.path, file: null }
      Swal.fire('OK','File KYC terupload','success')
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal upload KYC','error')
    }finally{
      uploadingKyc.value = false
    }
  }
  
  const busyEvaluation = ref(false)
  async function saveEvaluation(){
  busyEvaluation.value = true
  try{
    await axios.post(`/api/review/customer-verifications/${id}/evaluation`, {
      form: {
        customer_name: evaluation.customer_name,
        business_type: evaluation.business_type,
        credit_limit_request: evaluation.credit_limit_request,

        top_text: evaluation.top_text,
        potential_volume: evaluation.potential_volume,
        jenis_data: evaluation.jenis_data,
        financial_review: evaluation.financial_review,

        approval: approval,
        kyc_rows: kycRows.value.map(k=>({ label:k.label, url:k.url, name:k.name, path:k.path })),
      }
    })
    await loadAll() // ⬅️ refresh dari server
    Swal.fire('Tersimpan','Evaluasi calon customer disimpan.','success')
  }catch(e:any){
    Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan evaluasi','error')
  }finally{
    busyEvaluation.value = false
  }
}

  
  // ======== REVIEW =========
  const form = reactive<any>({
    info: {
      flow_review: '',
      invoice_schedule: '',
      payment_authority: '',
      existing_vendor: '',
      history: '',
      depot_location: '',
      opportunities: '',
    },
    detail: { credit_limit_proposed:'', marketing_notes:'' },
    old_info: '',
    attachments: [] as {no_urut?:number;name:string;url:string;path:string}[],
  })
  
  const fileRows = ref<{file:File|null, uploaded_url?:string}[]>([])
  const uploading = ref(false)
  function addFileRow(){ fileRows.value.push({file:null}) }
  function removeFileRow(i:number){ fileRows.value.splice(i,1) }
  function onFile(e:Event, i:number){ const input=e.target as HTMLInputElement; fileRows.value[i].file=input.files?.[0] || null }
  
  async function upload(i:number){
    const row = fileRows.value[i]
    if (!row.file) return
    const fd = new FormData()
    fd.append('file', row.file)
    uploading.value = true
    try {
      const { data } = await axios.post(`/api/review/customer-verifications/${id}/review-attachment`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      form.attachments.push({ no_urut: data.no_urut, name: data.name, url: data.url, path: data.path })
      row.uploaded_url = data.url
      Swal.fire('OK', 'File terupload', 'success')
    } catch (e:any) {
      Swal.fire('Error', e?.response?.data?.message || 'Gagal upload', 'error')
    } finally {
      uploading.value = false
    }
  }
  
  const busy = ref(false)
  async function save() {
    busy.value = true
    try {
      await axios.post(`/api/review/customer-verifications/${id}/review`, {
        review1:                form.detail.credit_limit_proposed,
        review_summary:         form.detail.marketing_notes,
        alur_proses_periksaan:  form.info.flow_review,
        jadwal_penerimaan:      form.info.invoice_schedule,
        background_bisnis:      form.info.history,
        lokasi_depo:            form.info.depot_location,
        opportunity_bisnis:     form.info.opportunities,
        review2:                form.info.payment_authority,
        review3:                form.info.existing_vendor,
      })
      Swal.fire('Tersimpan', 'Data review disimpan.', 'success')
    } catch (e:any) {
      Swal.fire('Error', e?.response?.data?.message || 'Gagal menyimpan', 'error')
    } finally {
      busy.value = false
    }
  }
  
  async function markReviewed(){
    const ok = await Swal.fire({ icon:'question', title:'Tandai reviewed?', showCancelButton:true, confirmButtonText:'Ya' })
    if (!ok.isConfirmed) return
    try{
      await save()
      await axios.patch(`/api/customer-verifications/${id}/set-reviewed`, { is_reviewed:1 })
      Swal.fire('OK','Item ditandai reviewed','success')
        .then(()=>router.push({name:'review-data-customer', query:{ tab:'unreviewed', page:1 }}))
    }catch(e:any){
      Swal.fire('Error', e?.response?.data?.message || 'Gagal set reviewed', 'error')
    }
  }
  
  // ======== LOAD ALL =========
  async function loadAll() {
    const [metaRes, rvRes, evRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${id}`),
      axios.get(`/api/review/customer-verifications/${id}/review`).catch(() => ({ data: null })),
      axios.get(`/api/review/customer-verifications/${id}/evaluation`).catch(() => ({ data: null })),
    ])
  
    const data = metaRes.data

    lockedByDisposisi.value = (data?.disposisi_result ?? 0) > 0
    customer.value = data?.customer || {}
    identitas.nama_perusahaan   = data?.customer?.nama_perusahaan || data?.legal?.corporate?.nama || ''
    identitas.ownership         = data?.legal?.corporate?.ownership || ''
    identitas.alamat_perusahaan = data?.customer?.alamat_perusahaan || data?.legal?.corporate?.alamat || ''
    legal.value    = data?.legal    || {}
    finance.value  = data?.finance  || {}
    logistik.value = data?.logistik || {}
    lockedByDisposisi.value = (data?.disposisi_result ?? 0) > 0
  
    // === Prefill Evaluation read-only ===
    evaluation.customer_name = identitas.nama_perusahaan || '-'
    evaluation.business_type = data?.legal?.corporate?.tipe_bisnis_text
                            ?? data?.customer?.tipe_bisnis_text
                            ?? companyStatus
  
    const rv = rvRes?.data
    const clReq =
      data?.customer?.credit_limit_diajukan ??
      rv?.review?.review1 ??
      ''
    evaluation.credit_limit_request = String(clReq)
  
    // ==== isi form review dari tabel ====
    if (rv?.review) {
      const r = rv.review
      form.detail.credit_limit_proposed = r.review1 || ''
      form.detail.marketing_notes       = r.review_summary || ''
      form.info.flow_review       = r.alur_proses_periksaan || ''
      form.info.invoice_schedule  = r.jadwal_penerimaan || ''
      form.info.payment_authority = r.review2 || ''
      form.info.existing_vendor   = r.review3 || ''
      form.info.history           = r.background_bisnis || ''
      form.info.depot_location    = r.lokasi_depo || ''
      form.info.opportunities     = r.opportunity_bisnis || ''
    }
    form.attachments = Array.isArray(rv?.attachments)
      ? rv.attachments.map((a:any)=>({ no_urut:a.no_urut, name:a.review_attach_ori, url:a.url, path:a.review_attach }))
      : []
  
    // ==== evaluation saved sebelumnya (opsional) ====
    const ev = evRes?.data
    if (ev?.form) {
      const { customer_name, business_type, credit_limit_request, ...rest } = ev.form
      Object.assign(evaluation, rest) // tiga field read-only tetap dari meta di atas
      // restore approval & KYC jika ada
      if (rest.approval) Object.assign(approval, rest.approval)
      if (Array.isArray(rest.kyc_rows) && rest.kyc_rows.length) {
        kycRows.value = rest.kyc_rows.map((f:any)=>({ label:f.label, url:f.url, name:f.name, path:f.path }))
      }
    }
  }
  
  function goBack(){ router.back() }
  onMounted(loadAll)
  </script>
  
  <style scoped>
  .form { @apply w-full border rounded px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-emerald-200; }
  .lbl  { @apply block text-sm text-slate-600 mb-1; }
  .tab  { @apply px-3 py-2 rounded border bg-white text-slate-700; }
  .tab-active { @apply px-3 py-2 rounded bg-emerald-600 text-white border-emerald-600; }
  </style>
  