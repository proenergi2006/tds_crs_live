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
    suffix?: string;
    min?: number;
    max?: number;
    decimals?: number;
  }>(),
  {
    label: '',
    placeholder: '0',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
    prefix: '',
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
      displayValue.value = isEmpty(value) ? '' : formatDisplay(toNumber(value))
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
  // Strip thousand separator dots, then convert decimal comma to dot
  const n = Number.parseFloat(text.replace(/\./g, '').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

function addThousandSep(intPart: string): string {
  return intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

function formatDisplay(n: number): string {
  const str = String(n).replace('.', ',')
  const [intPart, decPart] = str.split(',')
  return decPart ? addThousandSep(intPart) + ',' + decPart : addThousandSep(intPart)
}

function sanitize(raw: string): string {
  // Sisain digit & koma doang, sisanya dibuang
  let val = raw.replace(/[^\d,]/g, '')

  const parts = val.split(',')
  if (parts.length > 2) val = parts[0] + ',' + parts.slice(1).join('')

  let intPart = val.split(',')[0]
  // Strip leading zeros: "00123" → "123", tapi "0" sendiri tetap "0"
  intPart = intPart.replace(/^0+(\d)/, '$1')

  if (props.decimals <= 0) {
    return addThousandSep(intPart)
  }

  if (val.includes(',')) {
    const decPart = val.split(',')[1].slice(0, props.decimals)
    return addThousandSep(intPart) + ',' + decPart
  }

  return addThousandSep(intPart)
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
  displayValue.value = n ? formatDisplay(n) : ''
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
        v-if="prefix"
        class="font-caption pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-slate-500"
      >
        {{ prefix }}
      </div>

      <FormInput
        :model-value="displayValue"
        type="text"
        inputmode="decimal"
        autocomplete="off"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        class="text-right"
        :class="[error ? 'input-error' : '', suffix ? 'pr-9' : '', prefix ? 'pl-10' : '']"
        @input="handleInput"
        @blur="handleBlur"
      />

      <div
        v-if="suffix"
        class="font-caption pointer-events-none absolute inset-y-0 right-0 z-10 flex items-center pr-3"
      >
        {{ suffix }}
      </div>
    </div>

    <small v-if="error && error.trim()" class="block input-error-text">
      {{ error }}
    </small>
  </div>
</template>
