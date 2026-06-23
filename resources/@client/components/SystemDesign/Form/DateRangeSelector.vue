<script setup lang="ts">
import { computed } from 'vue'
import type { ILPConfiguration } from 'litepicker/dist/types/interfaces.d'

import Litepicker from '@/components/Base/Litepicker'
import Lucide from '@/components/Base/Lucide'
import { FormLabel } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

const props = withDefaults(
  defineProps<{
    start: string;
    end: string;
    label?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    options?: Partial<ILPConfiguration>;
  }>(),
  {
    label: '',
    error: '',
    required: false,
    disabled: false,
    options: () => ({}),
  },
)

const emit = defineEmits<{
  (e: 'update:start', value: string): void;
  (e: 'update:end', value: string): void;
}>()

const rangeValue = computed({
  get() {
    if (!props.start && !props.end) return ''
    return `${props.start || ''} - ${props.end || ''}`
  },
  set(value: string) {
    const [start = '', end = ''] = value.split(' - ')

    emit('update:start', start)
    emit('update:end', end)
  },
})

const pickerOptions = computed(() => ({
  autoApply: true,
  format: 'YYYY-MM-DD',
  singleMode: false,
  numberOfColumns: 2,
  numberOfMonths: 2,
  dropdowns: {
    minYear: 1990,
    maxYear: null,
    months: true,
    years: true,
  },
  ...props.options,
}))

const startLabel = computed(() => formatDisplayDate(props.start) || 'Tanggal awal')
const endLabel = computed(() => formatDisplayDate(props.end) || 'Tanggal akhir')

function formatDisplayDate(value: string) {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div
      class="relative overflow-hidden rounded-xl border bg-white px-4 py-3 shadow-sm transition"
      :class="[
        error ? 'input-error' : 'border-slate-200 hover:border-primary/40',
        disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer',
      ]"
    >
      <div class="pointer-events-none flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Lucide icon="CalendarDays" class="h-5 w-5" />
          </div>

          <div class="min-w-0">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Periode
            </div>
            <div class="mt-0.5 text-sm font-semibold text-slate-800">
              {{ startLabel }}
            </div>
          </div>
        </div>

        <div class="hidden flex-1 items-center gap-3 sm:flex">
          <div class="h-px flex-1 bg-slate-200"></div>
          <Lucide icon="ArrowRight" class="h-4 w-4 text-slate-400" />
          <div class="h-px flex-1 bg-slate-200"></div>
        </div>

        <div class="flex items-center justify-between gap-3 sm:min-w-[220px]">
          <div class="min-w-0">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Sampai
            </div>
            <div class="mt-0.5 text-sm font-semibold text-slate-800">
              {{ endLabel }}
            </div>
          </div>

          <span class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600">
            Ubah
          </span>
        </div>
      </div>

      <Litepicker
        v-model="rangeValue"
        :options="pickerOptions"
        :disabled="disabled"
        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
      />
    </div>

    <small v-if="error" class="block input-error-text">
      {{ error }}
    </small>
  </div>
</template>
