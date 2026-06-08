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
    placeholder?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    options?: Partial<ILPConfiguration>;
  }>(),
  {
    label: '',
    placeholder: 'Pilih tanggal',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
    options: () => ({}),
  },
)

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()

const pickerOptions = computed(() => ({
  autoApply: true,
  format: 'YYYY-MM-DD',
  dropdowns: {
    minYear: 1990,
    maxYear: null,
    months: true,
    years: true,
  },
  ...props.options,
}))
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div class="relative">
      <div class="pointer-events-none absolute inset-y-0 left-0 z-10 flex w-10 items-center justify-center text-slate-400">
        <Lucide icon="CalendarDays" class="h-4 w-4" />
      </div>

      <Litepicker
        :model-value="modelValue"
        :options="pickerOptions"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        class="pl-10"
        :class="error ? 'border-rose-500' : ''"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>

    <small v-if="error" class="mt-1 block text-rose-600">
      {{ error }}
    </small>
  </div>
</template>
