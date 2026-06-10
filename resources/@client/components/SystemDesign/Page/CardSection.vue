<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'
import { Icon } from '@/components/Base/Lucide/Lucide.vue'

withDefaults(
  defineProps<{
    title: string;
    description?: string;
    icon?: Icon;
    iconClass?: string;
    contentClass?: string;
  }>(),
  {
    description: '',
    icon: 'FileText',
    iconClass: 'bg-primary/10 text-primary',
    contentClass: '',
  },
)
</script>

<template>
  <section class="rounded-lg bg-white p-6 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="iconClass">
          <Lucide :icon="icon" class="h-5 w-5" />
        </div>

        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            {{ title }}
          </h2>

          <p v-if="description" class="text-sm text-slate-500">
            {{ description }}
          </p>
        </div>
      </div>

      <div v-if="$slots.action">
        <slot name="action" />
      </div>
    </div>

    <div :class="contentClass">
      <slot />
    </div>
  </section>
</template>
