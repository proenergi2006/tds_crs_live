<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import ApprovalTimeline from '../components/ApprovalTimeline.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface TimelineCycle {
  id_approval: number
  status: 'in_progress' | 'approved' | 'rejected'
  steps: { step_order: number; step_name: string | null; status: string; decision_note: string | null }[]
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const id = Number(route.params.id)

const loading = ref(true)
const forwardLoading = ref(false)
const forwardDialogOpen = ref(false)

const customer = ref<any>({})
const legal = ref<any>({})
const finance = ref<any>({})
const logistik = ref<any>({})

const timelineRef = ref<InstanceType<typeof ApprovalTimeline> | null>(null)
const cycles = ref<TimelineCycle[]>([])

const latestCycle = computed<TimelineCycle | null>(() => cycles.value[0] ?? null)
const hasInProgressCycle = computed(() => latestCycle.value?.status === 'in_progress')
const rejectionNotice = computed(() => {
  if (latestCycle.value?.status !== 'rejected') return null
  const rejectedStep = latestCycle.value.steps.find(s => s.status === 'rejected')
  return {
    stepName: rejectedStep?.step_name || 'tahap sebelumnya',
    note: rejectedStep?.decision_note || null,
  }
})

function onTimelineLoaded(loaded: TimelineCycle[]) {
  cycles.value = loaded
}

/* Section: form review Marketing — dipetakan ke field saveReview() (kolom
   generik review1/review2/review3 & review_summary, sesuai konvensi lama). */
const form = reactive({
  credit_limit_proposed: 0,
  marketing_notes: '',
  flow_review: '',
  invoice_schedule: '',
  payment_authority: '',
  existing_vendor: '',
  history: '',
  depot_location: '',
  opportunities: '',
})

const attachments = ref<{ no_urut?: number; name: string; url: string }[]>([])
const newFile = ref<File | null>(null)
const uploadingAttachment = ref(false)

/* Section: Data Customer (read-only, dari JSON hasil submit form publik) */
const corporateFields = computed(() => {
  const c = legal.value?.corporate || {}
  return [
    { label: 'Nama Perusahaan', value: c.nama || customer.value?.nama_perusahaan },
    { label: 'Holding', value: c.holding },
    { label: 'Alamat NPWP', value: c.alamat, span: 2 },
    { label: 'Kelurahan', value: c.kelurahan },
    { label: 'Kecamatan', value: c.kecamatan },
    { label: 'Kota/Kabupaten', value: c.kota },
    { label: 'Provinsi', value: c.provinsi },
    { label: 'Kode Pos', value: c.postal_code },
    { label: 'Telepon', value: c.telepon || customer.value?.telepon },
    { label: 'Fax', value: c.fax || customer.value?.fax },
  ]
})

const picFields = computed(() => {
  const p = legal.value?.pic || {}
  return [
    { label: 'Director / Owner', value: p.owner },
    { label: 'Procurement', value: p.procurement },
    { label: 'Finance', value: p.finance },
    { label: 'Site / Fuelman PIC', value: p.fuelman },
  ]
})

const paymentFields = computed(() => {
  const p = finance.value?.payment || {}
  return [
    { label: 'Pricing Method', value: p.pricing_method },
    { label: 'Payment Method', value: p.payment_method },
    { label: 'Term of Payment', value: p.term },
    { label: 'Bank Name', value: p.bank_name },
    { label: 'Currency', value: p.currency },
    { label: 'Bank Address', value: p.bank_address },
    { label: 'Account Number', value: p.account_number },
    { label: 'Credit Facility', value: p.has_credit ? 'Ya' : 'Tidak' },
  ]
})

const supplyFields = computed(() => {
  const s = logistik.value?.supply || {}
  const jam = s.operational_from && s.operational_to ? `${s.operational_from} - ${s.operational_to}` : (s.operational_from || s.operational_to || null)
  return [
    { label: 'Scheme Details', value: s.scheme_details },
    { label: 'Specify Product', value: s.specify_product },
    { label: 'Volume per Bulan', value: s.volume_per_month },
    { label: 'Jam Operasional', value: jam },
    { label: 'INCO Terms', value: s.inco_terms },
  ]
})

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}

async function loadAll() {
  loading.value = true
  try {
    const [metaRes, reviewRes] = await Promise.all([
      axios.get(`/api/review/customer-verifications/${id}`),
      axios.get(`/api/review/customer-verifications/${id}/review`).catch(() => ({ data: null })),
    ])

    const meta = metaRes.data || {}
    customer.value = meta.customer || {}
    legal.value = meta.legal || {}
    finance.value = meta.finance || {}
    logistik.value = meta.logistik || {}

    const review = reviewRes?.data?.review
    if (review) {
      form.credit_limit_proposed = Number(review.review1) || 0
      form.marketing_notes = review.review_summary || ''
      form.flow_review = review.alur_proses_periksaan || ''
      form.invoice_schedule = review.jadwal_penerimaan || ''
      form.payment_authority = review.review2 || ''
      form.existing_vendor = review.review3 || ''
      form.history = review.background_bisnis || ''
      form.depot_location = review.lokasi_depo || ''
      form.opportunities = review.opportunity_bisnis || ''
    }

    attachments.value = Array.isArray(reviewRes?.data?.attachments)
      ? reviewRes.data.attachments.map((a: any) => ({ no_urut: a.no_urut, name: a.review_attach_ori, url: a.url }))
      : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data verifikasi customer.')
  } finally {
    loading.value = false
  }
}

async function uploadAttachment() {
  if (!newFile.value) return
  uploadingAttachment.value = true
  try {
    const fd = new FormData()
    fd.append('file', newFile.value)
    const { data } = await axios.post(`/api/review/customer-verifications/${id}/review-attachment`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    attachments.value.push({ no_urut: data.no_urut, name: data.name, url: data.url })
    newFile.value = null
    success('Berhasil', 'Lampiran berhasil diunggah.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal mengunggah lampiran.')
  } finally {
    uploadingAttachment.value = false
  }
}

async function removeAttachment(noUrut?: number) {
  if (!noUrut) return
  try {
    await axios.delete(`/api/review/customer-verifications/${id}/review-attachment/${noUrut}`)
    attachments.value = attachments.value.filter(a => a.no_urut !== noUrut)
    success('Berhasil', 'Lampiran dihapus.')
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus lampiran.')
  }
}

async function submitForward() {
  forwardLoading.value = true
  try {
    await axios.post(`/api/review/customer-verifications/${id}/review`, {
      credit_limit_diajukan: form.credit_limit_proposed || undefined,
      review1: form.credit_limit_proposed || undefined,
      review_summary: form.marketing_notes || undefined,
      alur_proses_periksaan: form.flow_review || undefined,
      jadwal_penerimaan: form.invoice_schedule || undefined,
      review2: form.payment_authority || undefined,
      review3: form.existing_vendor || undefined,
      background_bisnis: form.history || undefined,
      lokasi_depo: form.depot_location || undefined,
      opportunity_bisnis: form.opportunities || undefined,
    })

    forwardDialogOpen.value = false
    success('Berhasil', 'Data direview & diforward ke Admin Finance.')
    router.push({ name: 'review-data-customer' })
  } catch (e: any) {
    forwardDialogOpen.value = false
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memforward data ke Admin Finance.')
  } finally {
    forwardLoading.value = false
  }
}

function goBack() {
  router.back()
}

onMounted(loadAll)
</script>

<template>
  <FormPage title="Review Data Customer" description="Validasi data customer sebelum diforward ke Admin Finance."
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
      <div v-if="rejectionNotice" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
        <p class="font-strong !text-rose-700">Pengajuan ini pernah ditolak di tahap {{ rejectionNotice.stepName }}.</p>
        <p v-if="rejectionNotice.note" class="font-body mt-1 whitespace-pre-line !text-rose-700">
          Catatan: {{ rejectionNotice.note }}
        </p>
        <p class="font-caption mt-1 !text-rose-600">Perbaiki data di bawah lalu forward ulang sebagai siklus baru.</p>
      </div>

      <CardSection title="Data Customer" description="Data legal, finansial, dan logistik dari hasil submit form publik"
        icon="FileText" icon-class="bg-indigo-100 text-indigo-600" :collapsible="true">
        <div class="space-y-6">
          <div>
            <div class="font-section mb-2">Corporate Details</div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div v-for="f in corporateFields" :key="f.label" :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <div class="font-section mb-2">Person in Charge</div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div v-for="f in picFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <div class="font-section mb-2">Payment Term & Banking Detail</div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div v-for="f in paymentFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </div>

          <div>
            <div class="font-section mb-2">Supply Scheme</div>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
              <div v-for="f in supplyFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </CardSection>

      <CardSection title="Review & Catatan Marketing" description="Diisi Marketing sebelum forward ke Admin Finance"
        icon="ClipboardEdit" icon-class="bg-emerald-100 text-emerald-600">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
          <CurrencyField v-model="form.credit_limit_proposed" label="Credit Limit Diajukan" />

          <div class="sm:col-span-2">
            <FormLabel>Catatan Marketing / Key Account</FormLabel>
            <FormTextarea v-model="form.marketing_notes" :rows="3" placeholder="Ringkasan hasil review data customer..." />
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Alur pemeriksaan/review dokumen & rata-rata waktu sampai pembayaran</FormLabel>
            <FormTextarea v-model="form.flow_review" :rows="2" />
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Jadwal penerimaan invoice & pembayaran tagihan</FormLabel>
            <FormTextarea v-model="form.invoice_schedule" :rows="2" />
          </div>

          <div>
            <FormLabel>Pemilik Authority Pembayaran</FormLabel>
            <FormTextarea v-model="form.payment_authority" :rows="2" placeholder="Nama, Posisi, No. HP" />
          </div>

          <div>
            <FormLabel>Existing Fuel Vendor</FormLabel>
            <FormTextarea v-model="form.existing_vendor" :rows="2" placeholder="Nama, credit term" />
          </div>

          <div class="sm:col-span-2">
            <FormLabel>Historical/Background Bisnis/Group</FormLabel>
            <FormTextarea v-model="form.history" :rows="2" />
          </div>

          <div>
            <FormLabel>Lokasi Depo Sumber Produk (Terminal)</FormLabel>
            <FormTextarea v-model="form.depot_location" :rows="2" />
          </div>

          <div>
            <FormLabel>Opportunity Bisnis</FormLabel>
            <FormTextarea v-model="form.opportunities" :rows="2" />
          </div>
        </div>
      </CardSection>

      <CardSection title="Lampiran" description="Dokumen pendukung hasil review" icon="Paperclip"
        icon-class="bg-amber-100 text-amber-600">
        <div class="space-y-3">
          <div v-for="a in attachments" :key="a.no_urut"
            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <a :href="a.url" target="_blank" class="font-body !text-primary underline">{{ a.name }}</a>
            <button type="button" class="text-slate-400 transition hover:text-rose-600" @click="removeAttachment(a.no_urut)">
              <Lucide icon="X" class="h-4 w-4" />
            </button>
          </div>

          <p v-if="attachments.length === 0" class="font-body">Belum ada lampiran.</p>

          <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center">
            <input type="file" class="text-sm" @change="(e: any) => newFile = e.target.files?.[0] || null" />
            <Button type="button" variant="outline-primary" size="sm" :disabled="!newFile || uploadingAttachment"
              @click="uploadAttachment">
              <Lucide v-if="uploadingAttachment" icon="Loader2" class="mr-2 h-4 w-4 animate-spin" />
              Unggah
            </Button>
          </div>
        </div>
      </CardSection>
    </div>

    <template #sidebar>
      <ApprovalTimeline v-if="!loading" ref="timelineRef" :id="id" @loaded="onTimelineLoaded" />

      <CardSection title="Forward ke Admin Finance" icon="Send" icon-class="bg-primary/10 text-primary">
        <div class="space-y-3">
          <p v-if="hasInProgressCycle" class="font-body rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 !text-amber-700">
            Verifikasi ini masih punya siklus persetujuan yang berjalan (Admin Finance/BM belum memutuskan) — tidak bisa forward ulang.
          </p>

          <Button v-else variant="primary" class="inline-flex w-full items-center justify-center gap-2"
            @click="forwardDialogOpen = true">
            <Lucide icon="Send" class="h-4 w-4" />
            Forward ke Admin Finance
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <ConfirmDialog :open="forwardDialogOpen" title="Forward data ke Admin Finance?"
    description="Data review akan disimpan dan siklus persetujuan Admin Finance → BM akan dimulai."
    confirm-text="Ya, Forward" icon="Send" icon-class="bg-primary/10 text-primary" variant="primary"
    :loading="forwardLoading" @close="forwardDialogOpen = false" @confirm="submitForward" />
</template>
