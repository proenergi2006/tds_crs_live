<template>
  <section aria-label="Ringkasan Sales Confirmation"
    class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
    <div v-if="refreshing" class="relative bg-slate-100 h-[3px] overflow-hidden" role="progressbar"
      aria-label="Memperbarui data">
      <div class="sc-refresh-bar top-0 bottom-0 absolute bg-primary w-[35%]" />
    </div>

    <div class="flex flex-wrap justify-between items-start gap-x-6 gap-y-3 px-5 py-4">
      <div class="flex flex-col flex-1 gap-1 basis-[320px] min-w-0">
        <span class="text-overline">Sales Confirmation</span>
        <h1 class="text-screen-title !text-slate-800 break-words">{{ detail.poc.nomor_poc ?? '-' }}</h1>
        <div class="text-body-lg !text-slate-700">
          {{ detail.customer.company_name || '-' }}
          <span v-if="detail.customer.customer_code" class="text-caption">· {{ detail.customer.customer_code }}</span>
        </div>
      </div>

      <div class="flex flex-col items-end gap-0.5 text-right">
        <span class="text-caption">Nilai PO Customer (termasuk PPN)</span>
        <span class="num-display !text-primary">{{ formatCurrency(detail.poc.totals.grand_total) }}</span>
        <span class="text-caption">
          {{ formatVolume(detail.poc.totals.volume) }} · {{ takenCount }} dari {{ quotationCount }} produk Quotation
        </span>
      </div>
    </div>

    <div v-if="rejection" role="alert"
      class="flex items-start gap-2.5 bg-rose-50 px-5 py-2.5 border-rose-200 border-t text-body !text-rose-800">
      <Lucide icon="XCircle" class="mt-0.5 w-4 h-4 shrink-0" />
      <div class="min-w-0">
        <strong class="font-semibold">
          Ditolak {{ rejection.step_name || '-' }} · {{ rejection.actor_name || '-' }} ·
          {{ formatDateTime(rejection.acted_at) || '-' }}
        </strong>
        <div v-if="rejection.decision_note" class="break-words whitespace-pre-line">{{ rejection.decision_note }}</div>
      </div>
    </div>

    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 bg-slate-50 px-5 py-2.5 border-slate-100 border-t">
      <span class="text-overline">Tahap saat ini</span>
      <strong class="text-body-strong">{{ stage.label }}</strong>
      <span v-if="stage.since" class="text-caption">{{ stage.since }}</span>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Lucide from '@/components/Base/Lucide'
import { formatCurrency, formatDateTime } from '@/utils/format'
import type { SalesConfirmationApprovalStep, SalesConfirmationDetail } from '@/types'

import { formatVolume } from '../format'
import type { Stage } from '../workflow'

const props = defineProps<{
  detail: SalesConfirmationDetail
  stage: Stage
  rejection: SalesConfirmationApprovalStep | null
  refreshing: boolean
}>()

const quotationCount = computed<number>(() => props.detail.quotation?.items?.length ?? 0)

const takenCount = computed<number>(
  () => props.detail.poc.items.filter(item => Number(item.volume) > 0).length,
)
</script>

<style scoped>
.sc-refresh-bar {
  animation: sc-refresh-slide 1s linear infinite;
}

@keyframes sc-refresh-slide {
  from {
    left: -35%;
  }

  to {
    left: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .sc-refresh-bar {
    left: 0;
    width: 100%;
    opacity: 0.4;
    animation: none;
  }
}
</style>
