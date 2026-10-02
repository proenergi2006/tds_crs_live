<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

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
const prefixRef = ref<HTMLElement | null>(null);
const suffixRef = ref<HTMLElement | null>(null);
const prefixWidth = ref(0);
const suffixWidth = ref(0);

let prefixObserver: ResizeObserver | null = null;
let suffixObserver: ResizeObserver | null = null;

const inputPaddingLeft = computed<string | undefined>(() =>
  props.prefix && prefixWidth.value > 0 ? `${prefixWidth.value + 8}px` : undefined,
);
const inputPaddingRight = computed<string | undefined>(() =>
  props.suffix && suffixWidth.value > 0 ? `${suffixWidth.value + 8}px` : undefined,
);

watch(
  () => props.modelValue,
  value => {
    const isDisplayValueInSync = parseNumber(displayValue.value) === toNumber(value)
    if (!isDisplayValueInSync) {
      displayValue.value = isEmpty(value) ? '' : formatDisplay(toNumber(value))
    }
  },
  { immediate: true },
)

watch(
  () => props.prefix,
  async () => {
    await nextTick();
    observePrefix();
  },
);

watch(
  () => props.suffix,
  async () => {
    await nextTick();
    observeSuffix();
  },
);

onMounted(() => {
  observePrefix();
  observeSuffix();
});

onBeforeUnmount(() => {
  prefixObserver?.disconnect();
  suffixObserver?.disconnect();
});

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
  const normalized = text.replace(/\./g, '').replace(',', '.')
  const n = Number.parseFloat(normalized)
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

function stripLeadingZeros(text: string): string {
  return text.replace(/^0+(\d)/, '$1')
}

function sanitize(raw: string): string {
  let digitsAndCommaOnly = raw.replace(/[^\d,]/g, '')

  const parts = digitsAndCommaOnly.split(',')
  if (parts.length > 2) digitsAndCommaOnly = parts[0] + ',' + parts.slice(1).join('')

  const intPart = stripLeadingZeros(digitsAndCommaOnly.split(',')[0])

  if (props.decimals <= 0) {
    return addThousandSep(intPart)
  }

  if (digitsAndCommaOnly.includes(',')) {
    const decPart = digitsAndCommaOnly.split(',')[1].slice(0, props.decimals)
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

function observePrefix(): void {
  prefixObserver?.disconnect();
  if (!prefixRef.value) return;
  prefixObserver = new ResizeObserver((entries) => {
    prefixWidth.value = (entries[0]?.target as HTMLElement | undefined)?.offsetWidth ?? 0;
  });
  prefixObserver.observe(prefixRef.value);
}

function observeSuffix(): void {
  suffixObserver?.disconnect();
  if (!suffixRef.value) return;
  suffixObserver = new ResizeObserver((entries) => {
    suffixWidth.value = (entries[0]?.target as HTMLElement | undefined)?.offsetWidth ?? 0;
  });
  suffixObserver.observe(suffixRef.value);
}
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <div class="relative">
      <div v-if="prefix" ref="prefixRef"
        class="left-0 z-10 absolute inset-y-0 flex items-center pl-3 text-caption text-slate-500 pointer-events-none">
        {{ prefix }}
      </div>

      <FormInput :model-value="displayValue" type="text" inputmode="decimal" autocomplete="off"
        :placeholder="placeholder" :disabled="disabled" :readonly="readonly" class="text-right"
        :class="[error ? 'input-error' : '', suffix ? 'pr-9' : '', prefix ? 'pl-10' : '']"
        :style="{ paddingLeft: inputPaddingLeft, paddingRight: inputPaddingRight }" @input="handleInput"
        @blur="handleBlur" />

      <div v-if="suffix" ref="suffixRef"
        class="right-0 z-10 absolute inset-y-0 flex items-center pr-3 text-caption pointer-events-none">
        {{ suffix }}
      </div>
    </div>

    <small v-if="error && error.trim()" class="block input-error-text">
      {{ error }}
    </small>
  </div>
</template>
