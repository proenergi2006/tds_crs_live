<template>
  <div class="flex items-center gap-3 p-2.5 border border-slate-200 rounded-lg">
    <div class="flex justify-center items-center rounded-lg w-9 h-9 shrink-0" :class="kind.iconClass">
      <Lucide :icon="kind.icon" class="w-4 h-4" />
    </div>

    <div class="flex-1 min-w-0">
      <div class="text-body-strong truncate" :title="name">{{ name }}</div>
      <div class="text-caption">{{ kind.label }}</div>
    </div>

    <template v-if="url">
      <Button as="a" variant="outline-secondary" class="!shadow-none !p-0 !w-8 !h-8 shrink-0" title="Pratinjau"
        :href="url" target="_blank" rel="noopener">
        <Lucide icon="Eye" class="w-4 h-4" />
      </Button>
      <Button as="a" variant="primary" class="!shadow-none !p-0 !w-8 !h-8 shrink-0" title="Unduh" :href="url"
        :download="downloadName ?? name">
        <Lucide icon="Download" class="w-4 h-4" />
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

import { resolveAttachmentKind } from '../adminSubmission'

const props = defineProps<{
  name: string
  url: string | null
  downloadName?: string | null
}>()

const kind = computed(() => resolveAttachmentKind(props.name))
</script>
