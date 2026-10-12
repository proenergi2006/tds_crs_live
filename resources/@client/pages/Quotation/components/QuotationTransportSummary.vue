<script setup lang="ts">
import type { QuotationTransport, TransportBasis, TransportMode } from '@/types'
import { transportLegLabel } from '../status'

withDefaults(
  defineProps<{
    basis: TransportBasis | null
    mode: TransportMode | null
    transports: QuotationTransport[]
    showInternals?: boolean
  }>(),
  { showInternals: false },
)

function areaLabel(area: QuotationTransport['transport_area']): string | null {
  if (!area) return null
  const parts = [
    area.province?.name || area.provinsi?.nama_provinsi,
    area.regency?.name || area.kabupaten?.nama_kabupaten,
    area.name,
  ].filter(Boolean)
  return parts.length ? parts.join(' - ') : null
}

function formatMoney(v: string | number | null | undefined): string {
  if (v === null || v === undefined || v === '') return '-'
  return `Rp. ${Number(v).toLocaleString('id-ID')}`
}
</script>

<template>
  <div class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
    <div class="mb-2 text-form-label">Transport</div>

    <div v-if="basis === 'loco' && mode === 'pickup'" class="text-body">
      Tanpa biaya transport
    </div>
    <template v-else>
      <div v-if="transports.length === 0" class="text-caption text-slate-500">Tidak ada tarif angkutan.</div>
      <div v-for="row in transports" :key="row.id"
        class="bg-white mb-2 last:mb-0 px-4 py-3 border border-slate-200 rounded-xl">
        <div class="gap-4 grid grid-cols-12">
          <div class="col-span-6 md:col-span-2">
            <div class="text-form-label">Moda</div>
            <div class="mt-1 text-body">{{ transportLegLabel(row.transport_type) }}</div>
          </div>
          <div class="col-span-6 md:col-span-3">
            <div class="text-form-label">Transportir</div>
            <div class="mt-1 text-body">{{ row.transporter?.company_name ?? '-' }}</div>
          </div>
          <div class="col-span-12 md:col-span-3">
            <div class="text-form-label">Wilayah Angkut</div>
            <div class="mt-1 text-body">{{ areaLabel(row.transport_area) ?? '-' }}</div>
          </div>
          <div class="col-span-6 md:col-span-1 text-right">
            <div class="text-form-label">Volume</div>
            <div class="mt-1 text-body">{{ row.volume?.volume ?? '-' }}</div>
          </div>
          <template v-if="showInternals">
            <div class="col-span-6 md:col-span-1 text-right">
              <div class="text-form-label">COGS/m³</div>
              <div class="mt-1 text-body">{{ formatMoney(row.rate) }}</div>
            </div>
            <div class="col-span-6 md:col-span-1 text-right">
              <div class="text-form-label">Margin/m³</div>
              <div class="mt-1 text-body">{{ formatMoney(row.margin) }}</div>
            </div>
          </template>
          <div class="col-span-6 text-right" :class="showInternals ? 'md:col-span-1' : 'md:col-span-3'">
            <div class="text-form-label">Harga/m³</div>
            <div class="mt-1 text-body">{{ formatMoney(row.price) }}</div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
