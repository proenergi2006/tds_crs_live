<script setup lang="ts">
defineProps<{
  id: string;
  title: string;
  description?: string;
}>();
</script>

<template>
  <section :id="id" class="scroll-mt-24">
    <div class="p-6 box">
      <h2 class="text-screen-title">{{ title }}</h2>
      <p v-if="description" class="mt-1 max-w-3xl text-body">{{ description }}</p>

      <div class="space-y-6 mt-6">
        <slot />
      </div>
    </div>
  </section>
</template>
