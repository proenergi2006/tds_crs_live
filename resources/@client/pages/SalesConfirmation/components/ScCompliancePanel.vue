<template>
  <ScCard title="Kesesuaian dengan Quotation" content-class="!p-0">
    <template #action>
      <Badge :variant="differences.length ? 'soft-secondary' : 'soft-success'">
        {{ differences.length ? `${differences.length} perbedaan` : 'Sesuai Quotation' }}
      </Badge>
    </template>

    <div v-if="!differences.length" class="flex items-start gap-2.5 px-4 py-3.5">
      <span class="flex justify-center items-center bg-emerald-50 mt-0.5 border border-emerald-200 rounded-full w-5 h-5 text-emerald-700 shrink-0">
        <Lucide icon="Check" class="w-3 h-3" />
      </span>
      <div class="min-w-0">
        <div class="text-body-strong">Tidak ada perbedaan dengan Quotation</div>
        <div class="text-caption">{{ checkedText }} tidak berbeda dari Quotation.</div>
      </div>
    </div>

    <template v-else>
      <ul class="divide-y divide-slate-100">
        <li v-for="difference in differences" :key="difference.key"
          class="gap-x-2.5 grid grid-cols-[20px_72px_minmax(0,1fr)] px-4 py-2.5">
          <span class="flex justify-center items-center bg-slate-100 mt-0.5 border border-slate-200 rounded-full w-5 h-5 text-slate-600 text-caption shrink-0"
            aria-hidden="true">≠</span>
          <span class="pt-0.5 text-form-label">{{ difference.topic }}</span>
          <p class="text-body break-words">
            <template v-for="(part, index) in difference.parts" :key="index">
              <strong v-if="part.strong" class="font-semibold">{{ part.text }}</strong>
              <template v-else>{{ part.text }}</template>
            </template>
          </p>
        </li>
      </ul>

      <div class="flex items-start gap-2 bg-slate-50 px-4 py-2.5 border-slate-100 border-t rounded-b-[9px]">
        <Lucide icon="Check" class="mt-0.5 w-3.5 h-3.5 text-emerald-600 shrink-0" />
        <span class="text-caption">Harga per item disalin dari Quotation saat PO Customer dibuat.</span>
      </div>
    </template>
  </ScCard>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Lucide from '@/components/Base/Lucide'
import Badge from '@/components/SystemDesign/Data/Badge.vue'

import type { Difference } from '../comparison'
import ScCard from './ScCard.vue'

const props = defineProps<{
  differences: Difference[]
  checked: string[]
}>()

const checkedText = computed<string>(() => {
  const names = props.checked
  const last = names[names.length - 1]
  const text = names.length > 1 ? `${names.slice(0, -1).join(', ')}, dan ${last}` : last
  return text.charAt(0).toUpperCase() + text.slice(1)
})
</script>
