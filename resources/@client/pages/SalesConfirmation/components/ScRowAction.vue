<template>
  <Button :as="to ? RouterLink : 'button'" :to="to" :type="to ? undefined : 'button'" variant="soft-secondary"
    class="!shadow-none !px-3 !py-0 !h-8 text-body whitespace-nowrap" @click="emit('click')">
    {{ label }}
  </Button>
</template>

<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router'

import Button from '@/components/Base/Button'

defineProps<{
  label: string
  to?: RouteLocationRaw
}>()

const emit = defineEmits<{
  (e: 'click'): void
}>()
</script>
