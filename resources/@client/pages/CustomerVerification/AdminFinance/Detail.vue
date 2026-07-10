<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormCheck, FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import ApprovalTimeline from '../components/ApprovalTimeline.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface TimelineCycle {
  id_approval: number
  status: 'in_progress' | 'approved' | 'rejected'
  steps: { step_order: number; step_name: string | null; status: string }[]
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const id = Number(route.params.id)

const loading = ref(true)
const decisionLoading = ref(false)
const approveDialogOpen = ref(false)
const rejectDialogOpen = ref(false)
const rejectNote = ref('')

const customer = ref<any>({})

const cycles = ref<TimelineCycle[]>([])
const latestCycle = computed<TimelineCycle | null>(() => cycles.value[0] ?? null)
const canDecide = computed(() => {
  if (!latestCycle.value) return false
  if (latestCycle.value.status !== 'in_progress') return false
  const step1 = latestCycle.value.steps.find(s => s.step_order === 1)
  return step1?.status === 'pending'
})

function onTimelineLoaded(loaded: TimelineCycle[]) {
  cycles.value = loaded
}

/* Section: form evaluasi Admin Finance — dipetakan 1:1 ke validasi saveEvaluation() */
const form = reactive({
  top_text: '',
  potential_volume: '',
  jenis_data: 'SEBELUM' as 'SEBELUM' | 'SETELAH',
  financial_review: '',
  evaluation_numbers: [] as string[],
})

const kycRows = ref<{ label: string; name?: string; path?: string; url?: string }[]>([])
const newKycFile = ref<File | null>(null)
const newKycLabel = ref('')
const uploadingKyc = ref(false)

const approval = reactive({
  approval_credit_limit: 0,
  payment_type: 'CREDIT' as 'CREDIT' | 'CASH',
  top_days: '30',
  top_basis: 'After Invoice Receive',
  group_company: '',
  docs: {
    customer_db: false,
    siup: false,
    notarial: false,
    lcr: false,
    npwp: false,
    finstat: false,
    top: false,
    customer_review: false,
    others: false,
  },
  docs_others_text: '',
  other_document: '',
  logistik_summary: '',
  logistik_result: '',
  assessment_result: '',
})

function addEvaluationNumber() {
  form.evaluation_numbers.push('')
}
function removeEvaluationNumber(idx: number) {
  form.evaluation_numbers.splice(idx, 1)
}

async function loadAll() {
  loading.value = true
  try {
    const [metaRes, adminEvalRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${id}`),
      axios.get(`/api/review/customer-verifications/${id}/admin-evaluation`).catch(() => ({ data: null })),
    ])

    customer.value = metaRes.data?.customer || {}

    const ev = adminEvalRes?.data
    if (ev) {
      form.top_text = ev.top_text && ev.top_text !== '-' ? ev.top_text : ''
      form.potential_volume = ev.potential_volume && ev.potential_volume !== '-' ? ev.potential_volume : ''
      form.financial_review = ev.financial_review && ev.financial_review !== '-' ? ev.financial_review : ''
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data verifikasi customer.')
  } finally {
    loading.value = false
  }
}

async function uploadKyc() {
  if (!newKycFile.value) return
  uploadingKyc.value = true
  try {
    const fd = new FormData()
    fd.append('file', newKycFile.value)
    fd.append('name', newKycLabel.value || newKycFile.value.name)
    fd.append('kind', 'approval_file')

    const { data } = await axios.post(`/api/review/customer-verifications/${id}/evaluation-attachment`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    kycRows.value.push({ label: newKycLabel.value || data.name, name: data.name, path: data.path, url: data.url })
    newKycFile.value = null
    newKycLabel.value = ''
    success('Berhasil', 'Dokumen KYC berhasil diunggah.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengunggah dokumen KYC.')
  } finally {
    uploadingKyc.value = false
  }
}

function removeKycRow(idx: number) {
  kycRows.value.splice(idx, 1)
}

function buildPayload(decision: 'APPROVE' | 'REJECT') {
  return {
    decision,
    notes: decision === 'REJECT' ? rejectNote.value : undefined,
    form: {
      top_text: form.top_text || undefined,
      potential_volume: form.potential_volume || undefined,
      jenis_data: form.jenis_data,
      financial_review: form.financial_review || undefined,
      evaluation_numbers: form.evaluation_numbers.filter(Boolean),
      kyc_rows: kycRows.value.map(r => ({ label: r.label, name: r.name, path: r.path, url: r.url })),
      approval: form.jenis_data === 'SETELAH' ? {
        approval_credit_limit: String(approval.approval_credit_limit || 0),
        payment_type: approval.payment_type,
        top_days: approval.top_days,
        top_basis: approval.top_basis,
        group_company: approval.group_company || undefined,
        docs: approval.docs,
        docs_others_text: approval.docs_others_text || undefined,
        other_document: approval.other_document || undefined,
        logistik_summary: approval.logistik_summary || undefined,
        logistik_result: approval.logistik_result || undefined,
        assessment_result: approval.assessment_result || undefined,
      } : undefined,
    },
  }
}

async function submitDecision(decision: 'APPROVE' | 'REJECT') {
  decisionLoading.value = true
  try {
    await axios.post(`/api/review/customer-verifications/${id}/evaluation`, buildPayload(decision))

    approveDialogOpen.value = false
    rejectDialogOpen.value = false

    if (decision === 'APPROVE') {
      success('Berhasil', 'Evaluasi disetujui & diteruskan ke BM.')
    } else {
      success('Berhasil', 'Pengajuan ditolak & dikembalikan ke Marketing.')
    }

    router.push({ name: 'review-data-customer-admin' })
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan evaluasi.')
  } finally {
    decisionLoading.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(loadAll)
</script>

<template>
  <FormPage title="Evaluasi Admin Finance" description="Evaluasi kelayakan kredit customer sebelum diteruskan ke BM."
    size="xl" layout="sidebar" surface="plain" :show-footer="false" :loading="loading" @cancel="goBack">
    <template #action>
      <Button variant="outline-secondary" @click="goBack">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali
      </Button>
    </template>

    <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
      <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
      <span class="font-body">Memuat data...</span>
    </div>

    <div v-else class="space-y-6">
      <CardSection title="Customer" icon="Building2" icon-class="bg-indigo-100 text-indigo-600">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
          <div>
            <dt class="font-label">Nama Perusahaan</dt>
            <dd class="font-strong mt-1">{{ customer.nama_perusahaan || '-' }}</dd>
          </div>
          <div>
            <dt class="font-label">Alamat</dt>
            <dd class="font-strong mt-1">{{ customer.alamat_perusahaan || '-' }}</dd>
          </div>
        </dl>
      </CardSection>

      <CardSection title="Form Evaluasi" description="TOP, potensi volume, dan financial review"
        icon="ClipboardCheck" icon-class="bg-emerald-100 text-emerald-600">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <div>
            <FormLabel>TOP (Term of Payment)</FormLabel>
            <FormInput v-model="form.top_text" placeholder="CREDIT 30 days After Invoice Receive" />
          </div>

          <div>
            <FormLabel>Potential Volume</FormLabel>
            <FormInput v-model="form.potential_volume" placeholder="0 m3" />
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Jenis Data</FormLabel>
            <FormSelect v-model="form.jenis_data" class="!box">
              <option value="SEBELUM">Sebelum Persetujuan Komite</option>
              <option value="SETELAH">Setelah Persetujuan Komite</option>
            </FormSelect>
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Financial Review</FormLabel>
            <FormTextarea v-model="form.financial_review" :rows="4" />
          </div>
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">
          <div class="font-section mb-2">Nomor Evaluasi</div>
          <div v-for="(_, idx) in form.evaluation_numbers" :key="idx" class="mb-2 flex items-center gap-2">
            <FormInput v-model="form.evaluation_numbers[idx]" class="flex-1" />
            <button type="button" class="text-slate-400 transition hover:text-rose-600" @click="removeEvaluationNumber(idx)">
              <Lucide icon="X" class="h-4 w-4" />
            </button>
          </div>
          <Button type="button" variant="outline-secondary" size="sm" @click="addEvaluationNumber">
            <Lucide icon="Plus" class="mr-1 h-4 w-4" />
            Tambah Nomor
          </Button>
        </div>
      </CardSection>

      <CardSection title="Lampiran Dokumen KYC" icon="Paperclip" icon-class="bg-amber-100 text-amber-600">
        <div class="space-y-3">
          <div v-for="(r, idx) in kycRows" :key="idx"
            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <div>
              <div class="font-strong">{{ r.label }}</div>
              <a v-if="r.url" :href="r.url" target="_blank" class="font-caption !text-primary underline">{{ r.name }}</a>
            </div>
            <button type="button" class="text-slate-400 transition hover:text-rose-600" @click="removeKycRow(idx)">
              <Lucide icon="X" class="h-4 w-4" />
            </button>
          </div>

          <p v-if="kycRows.length === 0" class="font-body">Belum ada dokumen KYC.</p>

          <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center">
            <FormInput v-model="newKycLabel" placeholder="Nama / keterangan dokumen" class="sm:w-64" />
            <input type="file" class="text-sm" @change="(e: any) => newKycFile = e.target.files?.[0] || null" />
            <Button type="button" variant="outline-primary" size="sm" :disabled="!newKycFile || uploadingKyc"
              @click="uploadKyc">
              <Lucide v-if="uploadingKyc" icon="Loader2" class="mr-2 h-4 w-4 animate-spin" />
              Unggah
            </Button>
          </div>
        </div>
      </CardSection>

      <CardSection v-if="form.jenis_data === 'SETELAH'" title="Persetujuan"
        description="Detail persetujuan setelah komite" icon="ShieldCheck" icon-class="bg-success/10 text-success">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <CurrencyField v-model="approval.approval_credit_limit" label="Persetujuan Credit Limit" />

          <div>
            <FormLabel>Payment Type</FormLabel>
            <FormSelect v-model="approval.payment_type" class="!box">
              <option value="CREDIT">CREDIT</option>
              <option value="CASH">CASH</option>
            </FormSelect>
          </div>

          <div>
            <FormLabel>TOP (Days)</FormLabel>
            <FormInput v-model="approval.top_days" />
          </div>

          <div>
            <FormLabel>TOP Basis</FormLabel>
            <FormSelect v-model="approval.top_basis" class="!box">
              <option value="After Invoice Receive">After Invoice Receive</option>
              <option value="After Delivery">After Delivery</option>
            </FormSelect>
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Group of Company</FormLabel>
            <FormInput v-model="approval.group_company" />
          </div>
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">
          <div class="font-section mb-2">Verification Document</div>
          <div class="grid grid-cols-1 gap-y-2 sm:grid-cols-2">
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.customer_db" /><FormCheck.Label>Customer Data Base</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.customer_review" /><FormCheck.Label>Customer Review</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.siup" /><FormCheck.Label>SIUP</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.top" /><FormCheck.Label>TOP</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.notarial" /><FormCheck.Label>Notarial Deed</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.lcr" /><FormCheck.Label>LCR</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.npwp" /><FormCheck.Label>NPWP</FormCheck.Label></FormCheck>
            <FormCheck><FormCheck.Input type="checkbox" v-model="approval.docs.finstat" /><FormCheck.Label>Financial Statement</FormCheck.Label></FormCheck>
            <FormCheck class="sm:col-span-2">
              <FormCheck.Input type="checkbox" v-model="approval.docs.others" />
              <FormCheck.Label class="mr-2">Others</FormCheck.Label>
              <FormInput v-model="approval.docs_others_text" class="flex-1" placeholder="Sebutkan" />
            </FormCheck>
          </div>

          <div class="mt-4">
            <FormLabel>Dokumen Lainnya</FormLabel>
            <FormInput v-model="approval.other_document" />
          </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 border-t border-slate-100 pt-4 sm:grid-cols-2">
          <div>
            <FormLabel>Logistik Summary</FormLabel>
            <FormTextarea v-model="approval.logistik_summary" :rows="3" />
          </div>
          <div>
            <FormLabel>Logistik Result</FormLabel>
            <FormInput v-model="approval.logistik_result" placeholder="Supply Delivery / With Note / etc" />

            <div class="mt-3">
              <FormLabel>Assessment Result</FormLabel>
              <FormCheck class="mt-1">
                <FormCheck.Input type="radio" value="Supply Delivery" v-model="approval.assessment_result" />
                <FormCheck.Label>Supply Delivery</FormCheck.Label>
              </FormCheck>
              <FormCheck class="mt-1">
                <FormCheck.Input type="radio" value="Supply Delivery With Note" v-model="approval.assessment_result" />
                <FormCheck.Label>Supply Delivery With Note</FormCheck.Label>
              </FormCheck>
              <FormCheck class="mt-1">
                <FormCheck.Input type="radio" value="Revised and Resubmitted" v-model="approval.assessment_result" />
                <FormCheck.Label>Revised and Resubmitted</FormCheck.Label>
              </FormCheck>
            </div>
          </div>
        </div>
      </CardSection>
    </div>

    <template #sidebar>
      <ApprovalTimeline v-if="!loading" :id="id" @loaded="onTimelineLoaded" />

      <CardSection title="Keputusan Admin Finance" icon="Gavel" icon-class="bg-primary/10 text-primary">
        <div class="space-y-3">
          <p v-if="!canDecide" class="font-body rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 !text-amber-700">
            Verifikasi ini tidak sedang menunggu keputusan Admin Finance (mungkin sudah diputuskan atau belum diforward Marketing).
          </p>

          <div v-else class="flex flex-col gap-2">
            <Button variant="danger" class="inline-flex w-full items-center justify-center gap-2"
              @click="rejectDialogOpen = true">
              <Lucide icon="X" class="h-4 w-4" />
              Tolak
            </Button>
            <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2"
              @click="approveDialogOpen = true">
              <Lucide icon="Check" class="h-4 w-4" />
              Setujui & Teruskan ke BM
            </Button>
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <ConfirmDialog :open="approveDialogOpen" title="Setujui evaluasi ini?"
    description="Verifikasi akan diteruskan ke BM untuk keputusan final." confirm-text="Ya, Setujui" icon="CheckCircle"
    icon-class="bg-success/10 text-success" variant="success" :loading="decisionLoading"
    @close="approveDialogOpen = false" @confirm="() => submitDecision('APPROVE')" />

  <ConfirmDialog :open="rejectDialogOpen" title="Tolak pengajuan ini?"
    description="Verifikasi akan dikembalikan ke Marketing untuk direvisi ulang." confirm-text="Ya, Tolak" icon="XCircle"
    icon-class="bg-danger/10 text-danger" variant="danger" :loading="decisionLoading"
    @close="rejectDialogOpen = false; rejectNote = ''" @confirm="() => submitDecision('REJECT')">
    <FormLabel>Alasan Penolakan</FormLabel>
    <FormTextarea v-model="rejectNote" placeholder="Jelaskan alasan penolakan..." :rows="3" />
  </ConfirmDialog>
</template>
