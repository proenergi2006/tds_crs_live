<template>
  <table class="w-full table-fixed border-collapse">
    <thead>
      <tr class="border-slate-200 border-b">
        <th class="pb-1.5 pr-2 text-overline text-left">Produk · harga / {{ VOLUME_UNIT }}</th>
        <th class="px-2 pb-1.5 w-[30%] text-overline text-right">Volume</th>
        <th class="pb-1.5 pl-2 w-[34%] text-overline text-right">Subtotal (DPP)</th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="row in cells" :key="row.key" class="border-slate-100 last:border-b-0 border-b"
        :class="row.gone ? 'opacity-60' : ''">
        <td class="py-2 pr-2 h-[74px] align-top">
          <div class="text-body-strong break-words" :class="row.gone ? 'line-through' : ''">{{ row.name }}</div>
          <div v-if="row.meta" class="text-caption break-words">{{ row.meta }}</div>
          <template v-if="row.price">
            <div class="text-caption whitespace-nowrap">{{ row.price.unit }}</div>
            <div class="text-caption">
              <span class="whitespace-nowrap">{{ row.price.material }}</span>
              <template v-if="row.price.transport">
                +
                <span class="whitespace-nowrap">{{ row.price.transport }}</span>
              </template>
            </div>
          </template>
        </td>

        <td class="px-2 py-2 h-[74px] text-right align-top">
          <span v-if="row.gone" class="inline-block bg-slate-100 px-2 py-0.5 rounded text-caption">Tidak diambil</span>
          <template v-else-if="row.volume !== null">
            <div class="num-sm whitespace-nowrap">{{ formatVolume(row.volume) }}</div>
            <div v-if="row.partialOf !== null" class="text-caption">dari {{ formatNumber(row.partialOf) }} · sebagian</div>
          </template>
          <span v-else class="text-caption">—</span>
        </td>

        <td class="py-2 pl-2 h-[74px] text-right align-top">
          <span v-if="row.subtotal !== null" class="num-sm whitespace-nowrap">{{ formatCurrency(row.subtotal) }}</span>
          <span v-else class="text-caption">—</span>
        </td>
      </tr>

      <tr v-if="!cells.length">
        <td colspan="3" class="py-6 text-body text-center">Belum ada item.</td>
      </tr>
    </tbody>
  </table>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import { formatCurrency, formatNumber } from '@/utils/format'

import type { ItemRow } from '../comparison'
import { formatVolume, VOLUME_UNIT } from '../format'
import { joinMeta } from '../itemMeta'

interface PriceText {
  unit: string
  material: string
  transport: string | null
}

interface Cell {
  key: string
  name: string
  meta: string
  price: PriceText | null
  volume: number | null
  partialOf: number | null
  subtotal: number | null
  gone: boolean
}

const props = defineProps<{
  rows: ItemRow[]
  side: 'quotation' | 'po'
}>()

const cells = computed<Cell[]>(() => props.rows.map(row => (props.side === 'quotation' ? quotationCell(row) : poCell(row))))

function quotationCell(row: ItemRow): Cell {
  const item = row.quotation
  if (!item) return emptyCell(row)

  return {
    key: row.key,
    name: row.name,
    meta: sizeLabel(item.ukuran, item.satuan),
    price: priceText(item.final_price, item.unit_price, item.transport_price),
    volume: Number(item.quantity),
    partialOf: null,
    subtotal: Number(item.final_total),
    gone: false,
  }
}

function poCell(row: ItemRow): Cell {
  const item = row.po
  if (!item) {
    return { ...emptyCell(row), meta: sizeLabel(row.quotation?.ukuran, row.quotation?.satuan), gone: true }
  }

  return {
    key: row.key,
    name: row.name,
    meta: joinMeta(item.product?.size, item.source_branch?.name),
    price: priceText(item.final_price, item.unit_price, item.transport_price),
    volume: Number(item.volume),
    partialOf: row.state === 'partial' && row.quotation ? Number(row.quotation.quantity) : null,
    subtotal: Number(item.line_total),
    gone: false,
  }
}

function emptyCell(row: ItemRow): Cell {
  return { key: row.key, name: row.name, meta: '', price: null, volume: null, partialOf: null, subtotal: null, gone: false }
}

function priceText(final: number, material: number, transport: number): PriceText {
  const hasTransport = Number(transport) > 0

  return {
    unit: `${formatCurrency(final)}/${VOLUME_UNIT}`,
    material: hasTransport ? `material ${formatCurrency(material)}` : 'material saja (loco)',
    transport: hasTransport ? `transport ${formatCurrency(transport)}` : null,
  }
}

function sizeLabel(size: string | null | undefined, unit: string | null | undefined): string {
  return [size, unit].map(part => String(part ?? '').trim()).filter(Boolean).join(' ')
}
</script>
