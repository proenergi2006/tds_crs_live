<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import { FormLabel } from '@/components/Base/Form'

export type PdfLang = 'id' | 'en'

const props = withDefaults(
  defineProps<{
    open: boolean
    loading?: boolean
  }>(),
  {
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', payload: { lang: PdfLang }): void
}>()

const lang = ref<PdfLang>('id')

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    lang.value = 'id'
  }
})

function submit() {
  emit('submit', { lang: lang.value })
}
</script>

<template>
  <Dialog :open="open" @close="$emit('close')">
    <Dialog.Panel>
      <div class="p-6">
        <div class="text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Lucide icon="Printer" class="h-8 w-8" />
          </div>
          <h3 class="text-section-title mt-5">Cetak PDF Penawaran</h3>
          <p class="text-body mt-2">Pilih bahasa sebelum mencetak.</p>
        </div>

        <div class="mt-6 space-y-5 text-left">
          <div>
            <FormLabel class="mb-2 block">Bahasa</FormLabel>
            <div class="flex gap-2">
              <button type="button" class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition"
                :class="lang === 'id' ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                :disabled="loading" @click="lang = 'id'">
                Bahasa Indonesia
              </button>
              <button type="button" class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition"
                :class="lang === 'en' ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                :disabled="loading" @click="lang = 'en'">
                English
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
        <Button variant="outline-secondary" :disabled="loading" @click="$emit('close')">
          Batal
        </Button>
        <Button variant="primary" class="inline-flex items-center gap-2" :disabled="loading" @click="submit">
          <Lucide v-if="loading" icon="Loader2" class="h-4 w-4 animate-spin" />
          Cetak
        </Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
