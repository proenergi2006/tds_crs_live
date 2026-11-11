<template>
  <section class="flex flex-col bg-primary/[0.04] p-1 border border-primary/10 rounded-xl">
    <div class="flex justify-between items-center gap-3 py-[7px] pr-2.5 pl-3">
      <h2 class="min-w-0 text-overline !text-primary/70">{{ title }}</h2>

      <div v-if="$slots.action" class="flex items-center gap-2">
        <slot name="action" />
      </div>
    </div>

    <div class="bg-white px-4 py-3.5 border border-primary/10 rounded-[9px]" :class="contentClass">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    contentClass?: string
  }>(),
  { contentClass: '' },
)
</script>
