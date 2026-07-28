<script setup lang="ts">
import Button from '@/components/Base/Button/Button.vue';
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    label: string
    expandOnHover?: boolean
    class?: string
  }>(),
  {
    expandOnHover: true,
  },
)

const buttonClass = computed(() =>
  props.expandOnHover
    ? 'group overflow-hidden transition-all duration-150 hover:px-4'
    : '!h-8 !w-8 !p-0 !shadow-none',
)
</script>

<template>
  <Button v-bind="$attrs" :class="[buttonClass, props.class]">
    <slot />

    <span v-if="expandOnHover"
      class="text-xs ml-0 max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover:ml-2 group-hover:max-w-20 group-hover:opacity-100">
      {{ label }}
    </span>

    <span v-else class="ml-2">
      {{ label }}
    </span>
  </Button>
</template>
