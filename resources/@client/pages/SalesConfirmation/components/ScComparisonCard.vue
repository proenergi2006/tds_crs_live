<template>
  <section aria-label="Perbandingan Quotation dan PO Customer"
    class="grid grid-cols-1 md:grid-cols-2 bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden">
    <div class="grid min-w-0 md:[grid-row:span_5] md:[grid-template-rows:subgrid]">
      <header class="flex justify-between items-center gap-2 bg-slate-50 px-4 py-3">
        <h2 class="text-card-title">Quotation</h2>
        <a v-if="quotationHref" :href="quotationHref" target="_blank" rel="noopener"
          class="inline-flex items-center gap-1 text-caption !text-primary hover:underline">
          Buka Quotation
          <Lucide icon="ExternalLink" class="w-3.5 h-3.5" />
        </a>
      </header>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScDetailList :items="quotationDetailItems">
          <template #value-payment>
            {{ quotationPaymentText }}
            <span v-if="quotationPaymentRaw" class="font-normal text-caption">({{ quotationPaymentRaw }})</span>
          </template>
          <template #value-location="{ item }">
            <span class="whitespace-pre-line">{{ item.value ?? '-' }}</span>
          </template>
        </ScDetailList>
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScItemsTable :rows="comparison.rows" side="quotation" />
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScTotals :totals="quotationSummary" />
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <template v-if="quotationApprovals.length">
          <span class="block mb-1.5 text-form-label">Persetujuan Quotation</span>
          <ul class="space-y-1.5">
            <li v-for="step in quotationApprovals" :key="step.label"
              class="flex items-start gap-2 p-2 border rounded-lg"
              :class="step.status === 'approved' ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50'">
              <Lucide :icon="step.status === 'approved' ? 'CheckCircle2' : step.status === 'rejected' ? 'XCircle' : 'Clock'"
                class="mt-0.5 w-4 h-4 shrink-0"
                :class="step.status === 'approved' ? 'text-emerald-600' : step.status === 'rejected' ? 'text-rose-600' : 'text-slate-400'" />
              <div class="min-w-0">
                <div class="text-body-strong">{{ step.label }}</div>
                <div class="text-caption">{{ step.text }}</div>
              </div>
            </li>
          </ul>
        </template>
      </div>
    </div>

    <div class="grid min-w-0 md:border-slate-300 md:border-l md:[grid-row:span_5] md:[grid-template-rows:subgrid]">
      <header class="flex justify-between items-center gap-2 bg-slate-50 px-4 py-3">
        <h2 class="text-card-title">PO Customer</h2>
        <a v-if="poCustomerHref" :href="poCustomerHref" target="_blank" rel="noopener"
          class="inline-flex items-center gap-1 text-caption !text-primary hover:underline">
          Lihat PO Customer
          <Lucide icon="ExternalLink" class="w-3.5 h-3.5" />
        </a>
      </header>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScDetailList :items="poCustomerDetailItems">
          <template #value-payment>
            <template v-if="comparison.paymentDiffers">
              <span class="bg-amber-50 rounded-sm ring-[3px] ring-amber-50">{{ poPaymentText }}</span>
              <span class="bg-amber-50 ml-1.5 px-1.5 py-0.5 rounded text-caption !text-amber-800">≠ Quotation</span>
            </template>
            <template v-else>{{ poPaymentText }}</template>
          </template>
        </ScDetailList>
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScItemsTable :rows="comparison.rows" side="po" />
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <ScTotals :totals="poSummary" />
      </div>

      <div class="px-4 py-3 border-slate-100 border-t min-w-0">
        <span class="block mb-1.5 text-form-label">File PO customer</span>
        <ScFileRow v-if="poFileUrl" :name="poFileName" :url="poFileUrl"
          :download-name="detail.poc.lampiran_poc_ori" />
        <div v-else class="p-3 border border-slate-300 border-dashed rounded-lg text-body text-center">
          Tidak ada lampiran
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Lucide from '@/components/Base/Lucide'
import { formatDate, formatDateTime } from '@/utils/format'
import type { SalesConfirmationDetail } from '@/types'

import { formatPayment, poTotals, quotationTotals, type Comparison, type ScTotals as ScTotalsData } from '../comparison'
import ScDetailList from './ScDetailList.vue'
import ScFileRow from './ScFileRow.vue'
import ScItemsTable from './ScItemsTable.vue'
import ScTotals from './ScTotals.vue'

const props = defineProps<{
  detail: SalesConfirmationDetail
  comparison: Comparison
  quotationHref: string | null
  poCustomerHref: string | null
}>()

const MONTH_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des']

const quotationSummary = computed<ScTotalsData>(() => quotationTotals(props.detail.quotation))

const poSummary = computed<ScTotalsData>(() => poTotals(props.detail.poc.totals))

const priceValidity = computed<string>(() => {
  const quotation = props.detail.quotation
  if (!quotation?.masa_berlaku || !quotation?.sampai_dengan) return '-'

  const from = new Date(quotation.masa_berlaku)
  const to = new Date(quotation.sampai_dengan)
  const part = (date: Date, withYear: boolean) =>
    `${String(date.getDate()).padStart(2, '0')} ${MONTH_SHORT[date.getMonth()]}${withYear ? ` ${date.getFullYear()}` : ''}`

  return `${part(from, from.getFullYear() !== to.getFullYear())} - ${part(to, true)}`
})

const deliveryLabel = computed<string>(() => {
  const quotation = props.detail.quotation
  const type = String(quotation?.type_pengiriman ?? '').trim().toLowerCase()
  return type.charAt(0).toUpperCase() + type.slice(1) || '-'
})

const rawQuotationPayment = computed<string>(() => {
  const quotation = props.detail.quotation
  const type = String(quotation?.tipe_pembayaran ?? '').trim()
  if (!type) return ''
  return Number(quotation?.repayment_hari) > 0 && type.toUpperCase() === 'CUSTOM'
    ? `${type} ${quotation?.repayment_hari} hari`
    : type
})

const quotationPaymentText = computed<string>(() => {
  const payment = props.comparison.quotationPayment
  return payment ? formatPayment(payment) : rawQuotationPayment.value || '-'
})

const quotationPaymentRaw = computed<string>(() =>
  props.comparison.quotationPayment && rawQuotationPayment.value !== quotationPaymentText.value
    ? rawQuotationPayment.value
    : '',
)

const poPaymentText = computed<string>(() => {
  const payment = props.comparison.poPayment
  if (payment) return formatPayment(payment)
  return props.detail.poc.tipe_bayar_label ?? props.detail.poc.tipe_bayar ?? '-'
})

const quotationDetailItems = computed(() => {
  const quotation = props.detail.quotation
  return [
    { label: 'No. Quotation', value: quotation?.nomor_penawaran || '-' },
    { label: 'Marketing', value: quotation?.marketing_name || '-' },
    { label: 'Masa berlaku', value: priceValidity.value },
    { key: 'payment', label: 'Pembayaran', value: quotationPaymentText.value },
    { label: 'Pengiriman', value: deliveryLabel.value },
    { key: 'location', label: 'Lokasi', value: quotation?.lokasi_pengiriman || '-' },
  ]
})

const poCustomerDetailItems = computed(() => {
  const order = props.detail.poc
  return [
    { label: 'No. PO customer', value: order.nomor_poc || '-' },
    { label: 'Tanggal PO', value: formatDate(order.tanggal_poc) },
    { label: 'Dibuat oleh', value: [order.created_by, formatDateTime(order.created_time)].filter(Boolean).join(' · ') || '-' },
    { key: 'payment', label: 'Pembayaran', value: poPaymentText.value },
    { label: 'Tanggal pasok', value: formatDate(order.supply_date) },
  ]
})

const quotationApprovals = computed<Array<{ label: string; text: string; status: string }>>(() =>
  (props.detail.quotation?.approvals ?? []).map(step => {
    const label = step.step_name || `Langkah ${step.step_order}`
    const status = step.status ?? ''
    if (status === 'approved') {
      const date = formatDate(step.acted_at)
      return { label, text: step.actor_name ? `${step.actor_name} · ${date}` : date, status }
    }
    if (status === 'rejected') return { label, text: 'Ditolak', status }
    return { label, text: 'Menunggu', status }
  }),
)

const poFileUrl = computed<string | null>(() => {
  const path = props.detail.poc.lampiran_poc
  return path ? `/storage/${path}` : null
})

const poFileName = computed<string>(() => props.detail.poc.lampiran_poc_ori || props.detail.poc.lampiran_poc || '')
</script>
