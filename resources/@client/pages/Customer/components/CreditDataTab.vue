<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Alert from '@/components/Base/Alert'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDateTime } from '@/utils/format'

const props = defineProps<{
  idCustomer: number
  isUnderReview: boolean
  latestApprovedVerification: {
    approved_limit: number | null
    approved_top: number | null
    financial_review: string | null
    reviewed_at: string | null
  } | null
}>()

const emit = defineEmits<{ (e: 'saved'): void }>()

const { success, error: notifyError } = useNotification()

const loading = ref(true)
const saving = ref(false)
const requestedLimit = ref<number | null>(null)
const requestedTop = ref<number | null>(null)

const locked = computed<boolean>(() => props.isUnderReview)

onMounted(fetchCreditRequest)

async function fetchCreditRequest(): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${props.idCustomer}/credit-request`)
    if (data) {
      requestedLimit.value = data.requested_limit
      requestedTop.value = data.requested_top
    }
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat pengajuan kredit.')
  } finally {
    loading.value = false
  }
}

async function saveCreditRequest(): Promise<void> {
  saving.value = true
  try {
    const { data } = await axios.put(`/api/customers/${props.idCustomer}/credit-request`, {
      requested_limit: requestedLimit.value,
      requested_top: requestedTop.value,
    })
    requestedLimit.value = data.requested_limit
    requestedTop.value = data.requested_top
    success('Berhasil', 'Pengajuan kredit tersimpan.')
    emit('saved')
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', 'Data terkunci, verifikasi sedang berjalan.')
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
</script>

<template>
  <div class="gap-6 grid">
    <Alert v-if="locked" variant="soft-warning">
      Verifikasi sedang berjalan. Pengajuan kredit tidak bisa diubah sampai Admin Finance memberi keputusan.
    </Alert>

    <div class="gap-6 grid" :class="props.latestApprovedVerification !== null ? 'sm:grid-cols-2' : ''">
      <CardSection title=" Credit Application"
        description="Pengajuan limit kredit &amp; term of payment (TOP) untuk customer ini." icon="Wallet"
        icon-class="bg-emerald-100 text-emerald-600">
        <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[120px] text-slate-500">
          <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
          <span class="font-body">Memuat pengajuan kredit...</span>
        </div>

        <template v-else>
          <div class="gap-4 grid sm:grid-cols-2">
            <CurrencyField v-model="requestedLimit" label="Credit Limit Request" required :disabled="locked" />
            <NumberField v-model="requestedTop" label="TOP Request" suffix="hari" :decimals="0" :disabled="locked" />
          </div>

          <div class="flex justify-end mt-5">
            <Button variant="primary" class="inline-flex items-center gap-2" :disabled="locked || saving"
              @click="saveCreditRequest">
              <Lucide v-if="saving" icon="Loader2" class="w-4 h-4 animate-spin" />
              Simpan
            </Button>
          </div>
        </template>
      </CardSection>

      <CardSection v-if="props.latestApprovedVerification !== null" title="Hasil Final Credit Limit"
        description="Nilai final yang disetujui." icon="CheckCircle2" icon-class="bg-blue-100 text-blue-600">
        <div class="gap-x-8 gap-y-3 grid sm:grid-cols-2">
          <div class="flex justify-between gap-4 pb-1.5 border-slate-100 border-b">
            <span class="font-label">Credit Limit Approval</span>
            <span class="font-strong text-right">{{ formatCurrency(props.latestApprovedVerification?.approved_limit) }}</span>
          </div>
          <div class="flex justify-between gap-4 pb-1.5 border-slate-100 border-b">
            <span class="font-label">TOP Approval</span>
            <span class="font-strong text-right">{{ props.latestApprovedVerification?.approved_top !== null ? `${props.latestApprovedVerification?.approved_top} hari` : '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 pb-1.5 border-slate-100 border-b">
            <span class="font-label">Disetujui Pada</span>
            <span class="font-strong text-right">{{ formatDateTime(props.latestApprovedVerification?.reviewed_at) ?? '-' }}</span>
          </div>
        </div>
        <div class="mt-3">
          <div class="font-label">Financial Review</div>
          <div v-if="props.latestApprovedVerification?.financial_review"
            class="bg-slate-50 mt-1 px-3 py-2 border border-slate-200 rounded-lg font-body rich-text-content"
            v-html="props.latestApprovedVerification.financial_review" />
          <div v-else class="bg-slate-50 mt-1 px-3 py-2 border border-slate-200 rounded-lg font-body">-</div>
        </div>
      </CardSection>
    </div>
  </div>
</template>
