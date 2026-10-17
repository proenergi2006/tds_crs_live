<script setup lang="ts">
import Table from '@/components/Base/Table'
import Tippy from '@/components/Base/Tippy'
import { formatCurrency, formatNumber, formatPercentRate } from '@/utils/format'
import type { PoCustomerItem, PoCustomerTotals } from '@/types'

defineProps<{
  items: PoCustomerItem[]
  totals: PoCustomerTotals
}>()

function subtotalBreakdown(materialSubtotal: number, transportTotal: number): string {
  return `Subtotal Material: ${formatCurrency(materialSubtotal)}<br>Total Transport: ${formatCurrency(transportTotal)}`
}

function priceBreakdown(item: PoCustomerItem): string {
  return `Material/m³: ${formatCurrency(item.unit_price)}<br>Transport/m³: ${formatCurrency(item.transport_price)}`
}

function isNotTaken(item: PoCustomerItem): boolean {
  return Number(item.volume) <= 0
}
</script>

<template>
  <div>
    <div class="overflow-x-auto">
      <Table bordered sm class="text-body">
        <Table.Thead class="bg-slate-50">
          <Table.Tr>
            <Table.Th class="px-4 py-3 text-form-label text-left">Produk</Table.Th>
            <Table.Th class="px-4 py-3 text-form-label text-right">Qty Penawaran</Table.Th>
            <Table.Th class="px-4 py-3 text-form-label text-right">Volume</Table.Th>
            <Table.Th class="px-4 py-3 text-form-label text-right">Harga Final/m³</Table.Th>
            <Table.Th class="px-4 py-3 text-form-label text-right">Subtotal</Table.Th>
          </Table.Tr>
        </Table.Thead>

        <Table.Tbody class="bg-white">
          <Table.Tr v-for="item in items" :key="item.id" :class="isNotTaken(item) ? 'opacity-60' : ''">
            <Table.Td class="px-4 py-3">
              <span v-if="item.source_branch" class="inline-block bg-slate-100 mb-1 px-2 rounded-full text-caption">Source: {{ item.source_branch.name }}</span>
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-body-strong">{{ item.product?.name || '-' }}</span>
                <span v-if="isNotTaken(item)" class="inline-block bg-slate-100 px-2 rounded-full text-caption">Tidak diambil</span>
              </div>
              <div v-if="item.product?.size || item.product?.unit" class="text-caption">
                {{ [item.product?.size, item.product?.unit].filter(Boolean).join(' ') }}
              </div>
            </Table.Td>
            <Table.Td class="px-4 py-3 num-sm text-right">{{ formatNumber(item.quantity_quoted) }}</Table.Td>
            <Table.Td class="px-4 py-3 num-sm text-right whitespace-nowrap">
              {{ formatNumber(item.volume) }} m³
            </Table.Td>
            <Table.Td class="px-4 py-3 num-sm text-right whitespace-nowrap">
              <Tippy :content="priceBreakdown(item)" :options="{ allowHTML: true }" as="span"
                class="inline-flex items-center gap-1 border-slate-400 border-b border-dotted !cursor-help">
                {{ formatCurrency(item.final_price) }}
              </Tippy>
            </Table.Td>
            <Table.Td class="px-4 py-3 num-sm text-right whitespace-nowrap">{{ formatCurrency(item.line_total) }}</Table.Td>
          </Table.Tr>

          <Table.Tr v-if="!items.length">
            <Table.Td colspan="5" class="px-4 py-6 text-body text-center">Belum ada item PO.</Table.Td>
          </Table.Tr>
        </Table.Tbody>

        <Table.Tbody class="bg-slate-50">
          <Table.Tr>
            <Table.Td colspan="2" class="px-4 py-2 text-body-strong text-right">Total</Table.Td>
            <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">{{ formatNumber(totals.volume) }} m³</Table.Td>
            <Table.Td colspan="2" class="px-4 py-2"></Table.Td>
          </Table.Tr>
          <Table.Tr>
            <Table.Td colspan="4" class="px-4 py-2 text-body-strong text-right">Subtotal (DPP)</Table.Td>
            <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">
              <Tippy :content="subtotalBreakdown(totals.material_subtotal, totals.transport_total)" :options="{ allowHTML: true }" as="span"
                class="inline-flex items-center gap-1 border-slate-400 border-b border-dotted !cursor-help">
                {{ formatCurrency(totals.dpp) }}
              </Tippy>
            </Table.Td>
          </Table.Tr>
          <Table.Tr>
            <Table.Td colspan="4" class="px-4 py-2 text-body-strong text-right">PPN ({{ formatPercentRate(totals.ppn_rate) }})</Table.Td>
            <Table.Td class="px-4 py-2 num-sm text-right whitespace-nowrap">{{ formatCurrency(totals.ppn) }}</Table.Td>
          </Table.Tr>
          <Table.Tr>
            <Table.Td colspan="4" class="px-4 py-3 text-section-title text-right">Grand Total</Table.Td>
            <Table.Td class="px-4 py-3 num-lg text-right whitespace-nowrap">{{ formatCurrency(totals.grand_total) }}</Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
    </div>
  </div>
</template>
