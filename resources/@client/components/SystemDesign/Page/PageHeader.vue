<script setup lang="ts">
type Variant = "gradient" | "flat";

const { variant = "gradient" } = defineProps<{
  title: string;
  description?: string;
  variant?: Variant;
}>();
</script>

<template>
  <div class="relative px-6 py-6 rounded-lg overflow-hidden"
    :class="variant === 'gradient' ? 'bg-gradient-to-r from-theme-1 via-theme-2 to-slate-700 text-white shadow-lg' : 'box'">
    <template v-if="variant === 'gradient'">
      <div class="-top-8 -right-8 absolute bg-white/10 blur-2xl rounded-full w-32 h-32"></div>
      <div class="-bottom-8 left-10 absolute bg-white/10 blur-2xl rounded-full w-24 h-24"></div>
    </template>

    <div class="z-10 relative flex flex-row justify-between items-center gap-4 p-2">
      <div>
        <h2 class="font-display" :class="variant === 'gradient' ? '!text-white' : '!text-slate-800'">
          {{ title }}
        </h2>

        <p v-if="description" class="mt-1 font-lead"
          :class="variant === 'gradient' ? '!text-white/80' : '!text-slate-500'">
          {{ description }}
        </p>
      </div>

      <div v-if="$slots.action">
        <slot name="action" />
      </div>
    </div>

    <div v-if="$slots.body" class="z-10 relative mt-4">
      <slot name="body" class="z-10 relative mt-4" />
    </div>
  </div>
</template>
