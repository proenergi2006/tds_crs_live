<script setup lang="ts">
defineProps<{
  title: string;
  description?: string;
}>();
</script>

<template>
  <div
    class="relative overflow-hidden rounded-lg bg-gradient-to-r from-theme-1 via-theme-2 to-slate-700 px-6 py-6 text-white shadow-lg">
    <div class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-8 left-10 h-24 w-24 rounded-full bg-white/10 blur-2xl"></div>

    <div class="relative z-10 p-2 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="font-display !text-white">
          {{ title }}
        </h2>

        <p v-if="description" class="font-lead mt-1 !text-white/80">
          {{ description }}
        </p>
      </div>

      <div v-if="$slots.action">
        <slot name="action" />
      </div>
    </div>
  </div>
</template>
