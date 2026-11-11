<template>
  <dl class="gap-x-4 gap-y-1 grid grid-cols-[minmax(0,1fr)_auto] text-body">
    <dt class="!text-slate-600">Volume</dt>
    <dd class="num-sm text-right">{{ formatVolume(totals.volume) }}</dd>

    <dt class="!text-slate-600">
      Subtotal (DPP)
      <span class="block text-caption">
        material {{ formatCurrency(totals.material) }} · transport {{ formatCurrency(totals.transport) }}
      </span>
    </dt>
    <dd class="num-sm text-right">{{ formatCurrency(totals.dpp) }}</dd>

    <dt class="!text-slate-600">PPN {{ formatPercentRate(totals.ppnRate) }}</dt>
    <dd class="num-sm text-right">{{ formatCurrency(totals.ppn) }}</dd>

    <dt class="mt-0.5 pt-1.5 border-slate-200 border-t text-body-strong">Total (termasuk PPN)</dt>
    <dd class="mt-0.5 pt-1.5 border-slate-200 border-t num-md text-right">{{ formatCurrency(totals.total) }}</dd>
  </dl>
</template>

<script setup lang="ts">
import { formatCurrency, formatPercentRate } from '@/utils/format'

import type { ScTotals } from '../comparison'
import { formatVolume } from '../format'

defineProps<{
  totals: ScTotals
}>()
</script>
