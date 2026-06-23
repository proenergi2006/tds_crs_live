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
    suffix?: string;
    min?: number;
    max?: number;
    decimals?: number; // jumlah desimal yang diizinkan; 0 = bilangan bulat
  }>(),
  {
    label: '',
    placeholder: '0',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
    suffix: '',
    min: undefined,
    max: undefined,
    decimals: 2,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void;
}>()

const displayValue = ref('')

watch(
  () => props.modelValue,
  value => {
    // Jangan timpa tampilan kalau nilainya sama dengan yang sedang diketik
    // (mis. user baru mengetik "5," — biarkan koma menggantung).
    if (parseNumber(displayValue.value) !== toNumber(value)) {
      displayValue.value = isEmpty(value) ? '' : String(value).replace('.', ',')
    }
  },
  { immediate: true },
)

function isEmpty(value: unknown) {
  return value === null || value === undefined || value === ''
}

function toNumber(value: unknown): number {
  if (isEmpty(value)) return 0
  const n = typeof value === 'number' ? value : parseNumber(String(value))
  return Number.isFinite(n) ? n : 0
}

function parseNumber(text: string): number {
  if (!text) return 0
  const n = Number.parseFloat(text.replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

// Sisakan hanya digit dan satu pemisah desimal; titik dinormalisasi ke koma.
function sanitize(raw: string): string {
  let val = raw.replace(/[^\d.,]/g, '').replace(/\./g, ',')

  const parts = val.split(',')
  if (parts.length > 2) val = parts[0] + ',' + parts.slice(1).join('')

  if (props.decimals <= 0) {
    return val.split(',')[0]
  }

  if (val.includes(',')) {
    const [int, dec] = val.split(',')
    val = int + ',' + dec.slice(0, props.decimals)
  }
  return val
}

function handleInput(event: Event) {
  const input = event.target as HTMLInputElement
  const clean = sanitize(input.value)
  input.value = clean
  displayValue.value = clean
  emit('update:modelValue', parseNumber(clean))
}

function handleBlur() {
  let n = parseNumber(displayValue.value)
  if (props.min !== undefined && n < props.min) n = props.min
  if (props.max !== undefined && n > props.max) n = props.max
  emit('update:modelValue', n)
  displayValue.value = n ? String(n).replace('.', ',') : ''
}
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div class="relative">
      <FormInput
        :model-value="displayValue"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        class="text-right"
        :class="[error ? 'input-error' : '', suffix ? 'pr-9' : '']"
        @input="handleInput"
        @blur="handleBlur"
      />

      <div
        v-if="suffix"
        class="pointer-events-none absolute inset-y-0 right-0 z-10 flex items-center pr-3 text-xs font-semibold text-slate-400"
      >
        {{ suffix }}
      </div>
    </div>

    <small v-if="error && error.trim()" class="block input-error-text">
      {{ error }}
    </small>
  </div>
</template>
