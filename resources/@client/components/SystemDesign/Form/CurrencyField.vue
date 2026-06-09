<script setup lang="ts">
import { ref, watch } from 'vue'

import { FormInput, FormLabel } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

const props = withDefaults(
  defineProps<{
    modelValue: number | string | null;
    label?: string;
    placeholder?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    prefix?: string;
  }>(),
  {
    label: '',
    placeholder: '0',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
    prefix: 'Rp',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void;
}>()

const displayValue = ref('')

watch(
  () => props.modelValue,
  value => {
    displayValue.value = formatCurrency(value)
  },
  { immediate: true },
)

function parseCurrency(value: unknown) {
  if (value === null || value === undefined || value === '') return 0
  if (typeof value === 'number') return Math.trunc(value)

  const text = String(value).trim()

  if (/^\d+\.\d{1,2}$/.test(text)) {
    return Math.trunc(Number.parseFloat(text))
  }

  const digits = text.replace(/[^\d]/g, '')
  return digits ? Number.parseInt(digits, 10) : 0
}

function formatCurrency(value: unknown) {
  const amount = parseCurrency(value)
  return amount ? amount.toLocaleString('id-ID') : ''
}

function handleInput(event: Event) {
  const input = event.target as HTMLInputElement
  const amount = parseCurrency(input.value)
  const formatted = formatCurrency(amount)

  input.value = formatted
  displayValue.value = formatted
  emit('update:modelValue', amount)
}

function handleBlur() {
  displayValue.value = formatCurrency(props.modelValue)
}
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div class="relative">
      <div
        class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center gap-1 pl-3 text-xs font-semibold text-slate-400">
        <span>{{ prefix }}</span>
      </div>

      <FormInput :model-value="displayValue" type="text" inputmode="numeric" autocomplete="off" :placeholder="placeholder"
        :disabled="disabled" :readonly="readonly" class="pl-10 text-right" :class="error ? 'border-rose-500' : ''"
        @input="handleInput" @blur="handleBlur" />
    </div>

    <small v-if="error" class="mt-1 block text-rose-600">
      {{ error }}
    </small>
  </div>
</template>
