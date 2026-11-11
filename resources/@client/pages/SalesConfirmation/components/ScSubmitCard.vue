<template>
  <ScCard :title="resubmit ? 'Ajukan ulang Sales Confirmation' : 'Ajukan Sales Confirmation'">
    <div class="space-y-4">
      <div>
        <FormLabel htmlFor="sc-admin-summary">Catatan Admin Finance</FormLabel>
        <FormTextarea id="sc-admin-summary" v-model="summary" :rows="4"
          placeholder="Catatan / analisa Admin Finance (opsional)" :disabled="busy" />
      </div>

      <FileUploadField v-model="files" label="Lampiran" hint="Opsional. PDF/JPG/PNG maks 2MB per file."
        accept=".pdf,.jpg,.jpeg,.png" multiple :max-size-mb="2" :existing-files="existingFiles" :disabled="busy"
        @remove-existing="file => emit('remove-existing', file)" />

      <div class="flex flex-col gap-2">
        <Button type="button" variant="primary" class="inline-flex justify-center items-center gap-2 w-full"
          :disabled="busy" @click="emit('submit')">
          <Lucide v-if="submitting" icon="Loader2" class="w-4 h-4 animate-spin" />
          <Lucide v-else icon="Send" class="w-4 h-4" />
          {{ resubmit ? 'Ajukan ulang' : 'Ajukan' }}
        </Button>
        <Button type="button" variant="outline-danger" class="inline-flex justify-center items-center gap-2 w-full"
          :disabled="busy" @click="emit('return')">
          <Lucide icon="Undo2" class="w-4 h-4" />
          Kembalikan ke Marketing
        </Button>
      </div>
    </div>
  </ScCard>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'

import ScCard from './ScCard.vue'

interface ExistingFile {
  id?: string | number
  name: string
  url?: string
  size?: number
}

const props = defineProps<{
  resubmit: boolean
  submitting: boolean
  returning: boolean
  disabled: boolean
  existingFiles: ExistingFile[]
}>()

const emit = defineEmits<{
  (e: 'submit'): void
  (e: 'return'): void
  (e: 'remove-existing', file: ExistingFile): void
}>()

const summary = defineModel<string>('summary', { required: true })
const files = defineModel<File[]>('files', { required: true })

const busy = computed<boolean>(() => props.submitting || props.returning || props.disabled)
</script>
