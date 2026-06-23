<script setup lang="ts">
import { ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { Icon } from '@/components/Base/Lucide/Lucide.vue'

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    icon?: Icon;
    iconClass?: string;
    contentClass?: string;
    collapsible?: boolean;
    defaultOpen?: boolean;
  }>(),
  {
    description: '',
    icon: 'FileText',
    iconClass: 'bg-primary/10 text-primary',
    contentClass: '',
    collapsible: false,
    defaultOpen: true,
  },
)

const isOpen = ref(props.defaultOpen)
</script>

<template>
  <section class="rounded-lg bg-white shadow-sm" :class="collapsible && !isOpen ? 'pb-0' : ''">
    <div class="flex flex-col gap-3 p-6 sm:flex-row sm:items-center sm:justify-between"
      :class="[collapsible && isOpen ? 'pb-5' : '', collapsible ? 'cursor-pointer select-none' : '']"
      @click="collapsible && (isOpen = !isOpen)">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="iconClass">
          <Lucide :icon="icon" class="h-5 w-5" />
        </div>

        <div>
          <h2 class="text-lg font-semibold text-slate-800">{{ title }}</h2>
          <p v-if="description" class="text-sm text-slate-500">{{ description }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div v-if="$slots.action" @click.stop>
          <slot name="action" />
        </div>

        <Lucide v-if="collapsible" icon="ChevronDown"
          class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200"
          :class="isOpen ? 'rotate-180' : ''" />
      </div>
    </div>

    <Transition name="card-collapse">
      <div v-show="!collapsible || isOpen">
        <hr class="mb-4" />
        <div :class="collapsible ? 'overflow-hidden' : ''">
          <div class="px-6 pb-6" :class="contentClass">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </section>
</template>

<style scoped>
.card-collapse-enter-active,
.card-collapse-leave-active {
  display: grid;
  transition: grid-template-rows 0.25s ease, opacity 0.2s ease;
}

.card-collapse-enter-from,
.card-collapse-leave-to {
  grid-template-rows: 0fr;
  opacity: 0;
}

.card-collapse-enter-to,
.card-collapse-leave-from {
  grid-template-rows: 1fr;
  opacity: 1;
}
</style>
