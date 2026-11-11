<template>
  <ScCard title="Catatan Admin Finance">
    <template v-if="author" #action>
      <span class="text-caption">{{ author }}</span>
    </template>

    <div class="space-y-2">
      <p v-if="summary" class="text-body break-words whitespace-pre-line">{{ summary }}</p>

      <ScFileRow v-for="file in attachments" :key="file.path" :name="adminAttachmentName(file)" :url="file.url" />
    </div>
  </ScCard>
</template>

<script setup lang="ts">
import type { SalesConfirmationAdminAttachment } from '@/types'

import { adminAttachmentName } from '../adminSubmission'
import ScCard from './ScCard.vue'
import ScFileRow from './ScFileRow.vue'

defineProps<{
  summary: string
  author: string | null
  attachments: SalesConfirmationAdminAttachment[]
}>()
</script>
