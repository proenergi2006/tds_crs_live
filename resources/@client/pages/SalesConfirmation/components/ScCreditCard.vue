<template>
  <ScCard title="Posisi kredit">
    <template v-if="gateType !== 'cbd_cod'" #action>
      <Badge :variant="remaining < 0 ? 'soft-danger' : 'soft-success'">
        {{ remaining < 0 ? 'Melebihi limit' : 'Limit cukup' }}
      </Badge>
    </template>

    <div class="text-caption">Sisa limit setelah order ini</div>
    <div class="num-lg" :class="remaining < 0 ? '!text-danger' : '!text-emerald-700'">
      {{ formatCurrency(remaining) }}
    </div>

    <div class="relative flex bg-slate-100 mt-2 rounded-full h-2.5 overflow-hidden" role="img" :aria-label="barLabel">
      <span class="bg-primary h-full" :style="{ width: `${share(totalAr)}%` }" />
      <span class="bg-primary/40 h-full" :style="{ width: `${share(orderWithinLimit)}%` }" />
      <span class="h-full bg-[repeating-linear-gradient(135deg,#e11d48_0_4px,#fb7185_4px_8px)]"
        :style="{ width: `${share(overLimit)}%` }" />
      <span v-if="overLimit > 0" class="top-0 bottom-0 absolute bg-slate-800 w-0.5" :style="{ left: `${share(creditLimit)}%` }"
        title="Batas limit" />
    </div>
    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1.5 text-caption">
      <span class="inline-flex items-center gap-1.5"><i class="bg-primary rounded-sm w-2.5 h-2.5" />Piutang</span>
      <span class="inline-flex items-center gap-1.5"><i class="bg-primary/40 rounded-sm w-2.5 h-2.5" />Order ini</span>
      <span v-if="overLimit > 0" class="inline-flex items-center gap-1.5">
        <i class="bg-rose-500 rounded-sm w-2.5 h-2.5" />Melebihi limit
      </span>
    </div>

    <dl class="mt-3">
      <div v-for="row in rows" :key="row.label"
        class="flex justify-between gap-3 py-1.5 border-slate-100 border-t first:border-t-0 text-body">
        <dt>{{ row.label }}</dt>
        <dd class="num-sm text-right">{{ row.value }}</dd>
      </div>
    </dl>

    <div class="flex items-start gap-2.5 mt-3 p-3 border rounded-lg" :class="gate.box">
      <span class="flex justify-center items-center mt-0.5 rounded-full w-5 h-5 shrink-0" :class="gate.icon">
        <Lucide :icon="gate.lucide" class="w-3 h-3" />
      </span>
      <div class="min-w-0">
        <div class="text-body-strong">{{ gate.title }}</div>
        <div v-if="gate.caption" class="text-caption">{{ gate.caption }}</div>
        <p v-if="gate.reason" class="mt-0.5 text-body break-words">{{ gate.reason }}</p>
      </div>
    </div>

    <details class="group mt-3 border border-slate-200 rounded-lg">
      <summary class="flex justify-between items-center gap-2 px-3 py-2 text-body-strong cursor-pointer list-none">
        <span class="flex items-center gap-1.5">
          <Lucide icon="ChevronRight" class="w-4 h-4 text-slate-500 group-open:rotate-90 transition-transform" />
          Umur piutang
        </span>
        <span class="font-normal text-caption">{{ overdueSummary }}</span>
      </summary>

      <div class="px-3 pb-2.5">
        <div v-for="(bucket, index) in AR_BUCKETS" :key="bucket.key"
          class="items-center gap-2.5 grid grid-cols-[96px_minmax(0,1fr)_auto] py-1 text-body">
          <span class="text-caption">{{ bucket.label }}</span>
          <span class="bg-slate-100 rounded-full h-1.5 overflow-hidden">
            <span class="block h-full" :class="barTone(index, buckets[bucket.key])"
              :style="{ width: `${bucketShare(buckets[bucket.key])}%` }" />
          </span>
          <span class="num-sm text-right">{{ formatCurrency(buckets[bucket.key]) }}</span>
        </div>

        <div class="flex justify-between gap-3 mt-1 pt-1.5 border-slate-200 border-t">
          <span class="text-form-label">Total piutang</span>
          <span class="num-sm">{{ formatCurrency(totalAr) }}</span>
        </div>
      </div>
    </details>
  </ScCard>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Lucide from '@/components/Base/Lucide'
import Badge from '@/components/SystemDesign/Data/Badge.vue'
import { formatCurrency, formatDateTime } from '@/utils/format'
import type { ArBucketKey, SalesConfirmationDetail } from '@/types'

import { AR_BUCKETS } from '../credit'
import { formatCurrencyShort } from '../format'
import ScCard from './ScCard.vue'

type Credit = SalesConfirmationDetail['credit']

const props = defineProps<{
  credit: Credit
  creditLimit: number
  buckets: Record<ArBucketKey, number>
}>()

const GATE_STYLE = {
  ok: { box: 'border-emerald-200 bg-emerald-50', icon: 'bg-emerald-100 text-emerald-700' },
  warn: { box: 'border-amber-200 bg-amber-50', icon: 'bg-amber-100 text-amber-700' },
  mute: { box: 'border-slate-200 bg-slate-50', icon: 'bg-slate-100 text-slate-600' },
} as const

const gateType = computed<Credit['gate']['type']>(() => props.credit.gate?.type ?? 'auto')

const remaining = computed<number>(() => Number(props.credit.sisa_limit_setelah_order ?? 0))

const orderValue = computed<number>(() => Number(props.credit.nilai_order_ppn ?? 0))

const totalAr = computed<number>(() => AR_BUCKETS.reduce((sum, bucket) => sum + props.buckets[bucket.key], 0))

const overLimit = computed<number>(() => Math.max(0, totalAr.value + orderValue.value - props.creditLimit))

const orderWithinLimit = computed<number>(() => orderValue.value - overLimit.value)

const barScale = computed<number>(() => Math.max(props.creditLimit, totalAr.value + orderValue.value, 1))

const barLabel = computed<string>(
  () =>
    `Piutang ${formatCurrency(totalAr.value)}, order ini ${formatCurrency(orderValue.value)}, limit ${formatCurrency(props.creditLimit)}` +
    (overLimit.value > 0 ? `, melebihi ${formatCurrency(overLimit.value)}` : ''),
)

const rows = computed(() => [
  { label: 'Limit kredit (hasil verifikasi)', value: formatCurrency(props.creditLimit) },
  { label: 'Total piutang', value: formatCurrency(totalAr.value) },
  { label: 'Order ini (termasuk PPN)', value: formatCurrency(orderValue.value) },
  { label: 'TOP disetujui', value: props.credit.approved_top != null ? `${props.credit.approved_top} hari` : '-' },
])

const gate = computed(() => {
  const info = props.credit.gate

  if (info?.type === 'unblock') {
    return {
      ...GATE_STYLE.warn,
      lucide: 'AlertTriangle' as const,
      title: 'Limit dibuka lewat Unblock',
      caption: [info.unblock?.requested_by_name, formatDateTime(info.unblock?.requested_at)].filter(Boolean).join(' · '),
      reason: info.unblock?.reason ?? '',
    }
  }

  if (info?.type === 'cbd_cod') {
    return {
      ...GATE_STYLE.mute,
      lucide: 'Info' as const,
      title: 'Tanpa pemeriksaan limit',
      caption: 'Pembayaran CBD/COD',
      reason: '',
    }
  }

  return {
    ...GATE_STYLE.ok,
    lucide: 'Check' as const,
    title: 'Limit cukup',
    caption: 'Lolos pemeriksaan limit kredit otomatis',
    reason: '',
  }
})

const overdueSummary = computed<string>(() => {
  const overdue = AR_BUCKETS.slice(1).reduce((sum, bucket) => sum + props.buckets[bucket.key], 0)
  return overdue > 0
    ? `lewat jatuh tempo ${formatCurrencyShort(overdue)}`
    : 'tidak ada yang lewat jatuh tempo'
})

const maxBucket = computed<number>(() => Math.max(...AR_BUCKETS.map(bucket => props.buckets[bucket.key]), 1))

function share(value: number): string {
  return Math.max(0, Math.min(100, (value / barScale.value) * 100)).toFixed(2)
}

function bucketShare(value: number): string {
  return ((value / maxBucket.value) * 100).toFixed(1)
}

function barTone(index: number, value: number): string {
  if (value <= 0 || index === 0) return 'bg-slate-500'
  return index === 1 ? 'bg-amber-500' : 'bg-rose-500'
}
</script>
