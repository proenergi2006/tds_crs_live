<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Alert from '@/components/Base/Alert'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

// Header-only (credit_limit_request + top_request + notes) -- limit kredit
// itu agregat, bukan per-produk, jadi tidak ada UI list item produk.
// credit_limit_approval/top_approval read-only, cuma tampil kalau sudah
// closed -- tidak pernah diisi lewat form Marketing.

const props = defineProps<{
  idCustomer: number
  kycStatus?: string | null
}>()

const { success, error: notifyError } = useNotification()

const creditSubmissionsApi = createResourceApi(`/customers/${props.idCustomer}/credit-submissions`)

/* State: pengajuan kredit -- endpoint CRUD multi-row secara teknis, tapi Tab
   4 KYC cuma butuh 1 row "current" (index 0 dari response, sudah
   orderByDesc di backend). */
const loading = ref(true)
const saving = ref(false)
const submissionId = ref<number | null>(null)
const creditLimitRequest = ref<number | null>(null)
const topRequest = ref<number | null>(null)
const notes = ref<string>('')
const creditLimitApproval = ref<number | null>(null)
const topApproval = ref<number | null>(null)

const locked = computed(() => !!props.kycStatus && props.kycStatus !== 'draft')

function applySubmission(row: any) {
  submissionId.value = row?.id ?? null
  creditLimitRequest.value = row?.credit_limit_request ?? null
  topRequest.value = row?.top_request ?? null
  notes.value = row?.notes ?? ''
  creditLimitApproval.value = row?.credit_limit_approval ?? null
  topApproval.value = row?.top_approval ?? null
}

function formatCurrency(value: number | null): string {
  if (value === null || value === undefined) return '-'
  return `Rp ${value.toLocaleString('id-ID')}`
}

async function fetchSubmission() {
  loading.value = true
  try {
    const { data } = await creditSubmissionsApi.getAll()
    const rows = Array.isArray(data) ? data : []
    if (rows.length > 0) {
      applySubmission(rows[0])
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat pengajuan kredit.')
  } finally {
    loading.value = false
  }
}

async function saveSubmission() {
  saving.value = true
  try {
    const payload = {
      submission_type: 'new_customer',
      credit_limit_request: creditLimitRequest.value,
      top_request: topRequest.value,
      notes: notes.value,
    }

    const { data } = submissionId.value === null
      ? await creditSubmissionsApi.store(payload)
      : await creditSubmissionsApi.update(submissionId.value, payload)

    applySubmission(data)
    success('Berhasil', 'Pengajuan kredit tersimpan.')
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Tab ini terkunci — KYC sudah di-forward.')
    } else if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      notifyError('Gagal', (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan pengajuan kredit.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchSubmission)
</script>

<template>
  <div class="grid gap-6">
    <Alert v-if="locked" variant="soft-warning">
      Tab ini terkunci — KYC sudah di-forward. Pengajuan kredit tidak bisa diubah dari halaman ini.
    </Alert>

    <CardSection title="Credit Application"
      description="Pengajuan limit kredit &amp; term of payment (TOP) untuk customer ini." icon="Wallet"
      icon-class="bg-emerald-100 text-emerald-600">
      <div v-if="loading" class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
        <span class="font-body">Memuat pengajuan kredit...</span>
      </div>

      <template v-else>
        <div class="grid gap-4 sm:grid-cols-2">
          <CurrencyField v-model="creditLimitRequest" label="Credit Limit Request" required :disabled="locked" />
          <NumberField v-model="topRequest" label="TOP Request" suffix="hari" :decimals="0" :disabled="locked" />
          <div class="sm:col-span-2">
            <FormLabel>Catatan</FormLabel>
            <FormTextarea v-model="notes" rows="3" :auto-resize="true" :disabled="locked" />
          </div>
        </div>

        <div class="mt-5 flex justify-end">
          <Button variant="primary" class="inline-flex items-center gap-2" :disabled="locked || saving"
            @click="saveSubmission">
            <Lucide v-if="saving" icon="Loader2" class="h-4 w-4 animate-spin" />
            Simpan
          </Button>
        </div>
      </template>
    </CardSection>

    <CardSection v-if="creditLimitApproval !== null" title="Hasil Final Credit Limit"
      description="Nilai final yang disetujui setelah KYC closed." icon="CheckCircle2"
      icon-class="bg-blue-100 text-blue-600">
      <div class="grid gap-y-3 gap-x-8 sm:grid-cols-2">
        <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
          <span class="font-label">Credit Limit Approval</span>
          <span class="font-strong text-right">{{ formatCurrency(creditLimitApproval) }}</span>
        </div>
        <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
          <span class="font-label">TOP Approval</span>
          <span class="font-strong text-right">{{ topApproval !== null ? `${topApproval} hari` : '-' }}</span>
        </div>
      </div>
    </CardSection>
  </div>
</template>
