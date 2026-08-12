<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router'
import Lucide from '@/components/Base/Lucide'
import { Icon } from '@/components/Base/Lucide/Lucide.vue'

// mode ditentuin dari ada-gaknya `title` -- sengaja komponen sendiri (bukan nambah mode ke CardSection.vue) biar height-fix di sini gak ganggu 24 consumer CardSection lain
defineProps<{
  icon: Icon
  iconClass: string
  description?: string
  linkTo?: RouteLocationRaw
  linkLabel?: string
  label?: string
  value?: string | number
  title?: string
}>()
</script>

<template>
  <div class="flex flex-col shadow-sm rounded-2xl h-full" :class="title ? 'bg-white' : 'bg-slate-50 box'">
    <template v-if="title">
      <div class="flex flex-col gap-3 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex justify-center items-center rounded-full w-11 h-11 shrink-0" :class="iconClass">
            <Lucide :icon="icon" class="w-5 h-5" />
          </div>
          <div>
            <h2 class="font-header">{{ title }}</h2>
            <p v-if="description" class="font-body">{{ description }}</p>
          </div>
        </div>
        <div v-if="$slots.action" class="flex items-center gap-3">
          <slot name="action" />
        </div>
      </div>
      <div class="flex flex-col flex-1 min-h-0">
        <hr class="mb-4" />
        <div class="flex-1 min-h-0">
          <div class="px-6 pb-6 h-full">
            <slot />
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="flex justify-between items-start gap-3 bg-white shadow-sm p-5 border-b rounded-2xl">
        <div>
          <div class="font-section">{{ label }}</div>
          <div class="mt-1 font-num-display">{{ value }}</div>
          <div v-if="description" class="font-body text-xs">{{ description }}</div>
        </div>
        <div class="flex justify-center items-center rounded-xl w-9 h-9 shrink-0" :class="iconClass">
          <Lucide :icon="icon" class="w-4 h-4" />
        </div>
      </div>
      <!-- default-nya link tunggal, tapi bisa dioverride konsumen (mis. daftar quick-links) -->
      <slot name="action">
        <RouterLink v-if="linkTo" :to="linkTo"
          class="flex justify-end items-center gap-1 px-5 py-3 font-semibold text-primary text-xs hover:underline">
          {{ linkLabel ?? 'Lihat detail' }}
          <Lucide icon="ArrowRight" class="w-3 h-3" />
        </RouterLink>
      </slot>
    </template>
  </div>
</template>
