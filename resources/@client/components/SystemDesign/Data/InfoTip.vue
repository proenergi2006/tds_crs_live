<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'
import Tippy from '@/components/Base/Tippy'

defineProps<{
  content: string
}>()
</script>

<template>
  <Tippy :content="content" as="span" class="inline-flex align-middle ml-1 text-slate-400 hover:text-slate-600 cursor-help">
    <Lucide icon="Info" class="w-3.5 h-3.5" />
  </Tippy>
</template>
