<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
}>();
</script>

<template>
  <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-start lg:justify-between">
    <div>
      <h2 class="text-2xl font-semibold text-slate-800">
        {{ title }}
      </h2>

      <p v-if="description" class="mt-1 text-sm text-slate-500">
        {{ description }}
      </p>
    </div>

    <div v-if="$slots.action">
      <slot name="action" />
    </div>
  </div>
</template>
