<script setup lang="ts">
import { computed } from 'vue'
import type { ILPConfiguration } from 'litepicker/dist/types/interfaces.d'

import Litepicker from '@/components/Base/Litepicker'
import Lucide from '@/components/Base/Lucide'
import { FormLabel } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

const props = withDefaults(
  defineProps<{
    modelValue: string;
    label?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    autoDefault?: boolean;
    options?: Partial<ILPConfiguration>;
  }>(),
  {
    label: '',
    error: '',
    required: false,
    disabled: false,
    autoDefault: true,
    options: () => ({}),
  },
)

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()

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

const rangeParts = computed(() => {
  const [start = '', end = ''] = props.modelValue.split(' - ')

  return {
    start: start || 'Tanggal awal',
    end: end || 'Tanggal akhir',
  }
})
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div
      class="relative overflow-hidden rounded-xl border bg-white p-4 shadow-sm transition"
      :class="[
        error ? 'border-rose-500' : 'border-slate-200 hover:border-primary/40',
        disabled ? 'cursor-not-allowed opacity-70' : 'cursor-pointer',
      ]"
    >
      <div class="pointer-events-none flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
          <Lucide icon="CalendarRange" class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            Rentang Tanggal
          </div>

          <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
            <div>
              <div class="text-xs text-slate-400">Dari</div>
              <div class="truncate text-sm font-semibold text-slate-800">
                {{ rangeParts.start }}
              </div>
            </div>

            <Lucide icon="ArrowRight" class="hidden h-4 w-4 text-slate-400 sm:block" />

            <div>
              <div class="text-xs text-slate-400">Sampai</div>
              <div class="truncate text-sm font-semibold text-slate-800">
                {{ rangeParts.end }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <Litepicker
        :model-value="modelValue"
        :options="pickerOptions"
        :disabled="disabled"
        :auto-default="autoDefault"
        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>

    <small v-if="error" class="mt-1 block text-rose-600">
      {{ error }}
    </small>
  </div>
</template>
