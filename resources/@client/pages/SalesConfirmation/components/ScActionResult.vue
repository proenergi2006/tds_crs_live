<template>
  <section class="space-y-1 p-4 border rounded-lg" aria-live="polite"
    :class="tone === 'danger' ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'">
    <div class="text-overline" :class="tone === 'danger' ? '!text-rose-700' : '!text-emerald-700'">{{ title }}</div>
    <div class="text-body-strong">{{ message }}</div>
    <div v-if="detail" class="text-caption">{{ detail }}</div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  tone: 'success' | 'danger'
  title: string
  message: string
  detail?: string
}>()
</script>
