<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: string;
    value: string;
    title: string;
    description?: string;
    disabled?: boolean;
  }>(),
  {
    description: '',
    disabled: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()
</script>

<template>
  <label
    class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
    :class="[
      modelValue === value ? 'border-primary bg-primary/5' : 'border-slate-200 hover:bg-slate-50',
      disabled ? 'cursor-not-allowed opacity-60 hover:bg-transparent' : '',
    ]"
  >
    <input
      type="radio"
      class="sr-only"
      :value="value"
      :checked="modelValue === value"
      :disabled="disabled"
      @change="emit('update:modelValue', value)"
    />

    <span
      class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition"
      :class="modelValue === value ? 'border-primary' : 'border-slate-300'"
    >
      <span v-if="modelValue === value" class="h-2 w-2 rounded-full bg-primary" />
    </span>

    <span class="text-sm">
      <span class="block font-medium" :class="modelValue === value ? 'text-slate-900' : 'text-slate-700'">
        {{ title }}
      </span>
      <span v-if="description" class="text-slate-500">{{ description }}</span>
    </span>
  </label>
</template>
