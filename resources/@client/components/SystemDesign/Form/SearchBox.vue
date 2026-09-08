<script setup lang="ts">
import { FormInput, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

withDefaults(
  defineProps<{
    modelValue: string;
    label?: string;
    placeholder?: string;
    disabled?: boolean;
    clearable?: boolean;
  }>(),
  {
    label: '',
    placeholder: 'Cari…',
    disabled: false,
    clearable: true,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()

function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}

function clear() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div>
    <FormLabel v-if="label">{{ label }}</FormLabel>

    <div class="relative">
      <Lucide icon="Search"
        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />

      <FormInput
        :model-value="modelValue"
        type="text"
        autocomplete="off"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="['pl-9', clearable && modelValue ? 'pr-9' : '']"
        @input="onInput"
      />

      <button
        v-if="clearable && modelValue"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
        title="Bersihkan"
        @click="clear"
      >
        <Lucide icon="X" class="h-3.5 w-3.5" />
      </button>
    </div>
  </div>
</template>
