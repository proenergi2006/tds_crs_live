<script setup lang="ts">
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'

export type DownloadableDocument = 'data-customer' | 'sales-review' | 'credit-application' | 'lcr' | 'bulk'

withDefaults(
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
  (e: 'select', document: DownloadableDocument): void
}>()

const documentButtons: { key: DownloadableDocument; label: string; icon: 'FileText' }[] = [
  { key: 'data-customer', label: 'Data Customer', icon: 'FileText' },
  { key: 'sales-review', label: 'Sales Review', icon: 'FileText' },
  { key: 'credit-application', label: 'Credit Application', icon: 'FileText' },
  { key: 'lcr', label: 'LCR', icon: 'FileText' },
]
</script>

<template>
  <Dialog :open="open" size="xl" @close="$emit('close')">
    <Dialog.Panel>
      <div class="flex justify-between items-center px-6 py-4 border-slate-200 border-b">
        <h3 class="font-header">Download Dokumen</h3>
        <button type="button" class="text-slate-400 hover:text-slate-600" :disabled="loading" @click="$emit('close')">
          <Lucide icon="X" class="w-5 h-5" />
        </button>
      </div>

      <div class="gap-0 grid grid-cols-1 sm:grid-cols-5">
        <div class="space-y-2 sm:col-span-2 p-6">
          <p class="mb-3 font-caption text-slate-500">Cetak dokumen secara terpisah</p>
          <Button v-for="btn in documentButtons" :key="btn.key" variant="outline-primary"
            class="justify-start items-center gap-2 w-full" :disabled="loading" @click="$emit('select', btn.key)">
            <Lucide icon="Printer" class="w-4 h-4" />
            {{ btn.label }}
          </Button>

          <div class="pt-3 border-slate-100 border-t">
            <p class="mb-2 font-caption text-slate-500">Atau gabungkan semua jadi satu file</p>
            <Button variant="primary" class="justify-start items-center gap-2 w-full" :disabled="loading"
              @click="$emit('select', 'bulk')">
              <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Layers" class="w-4 h-4" />
              Cetak Semua (Gabungan)
            </Button>
          </div>
        </div>

        <div
          class="flex flex-col justify-center items-center gap-3 bg-slate-50 sm:col-span-3 p-6 sm:border-slate-200 sm:border-l text-center">
          <div class="flex justify-center items-center bg-slate-200 rounded-full w-20 h-20 text-slate-400">
            <Lucide icon="FileText" class="w-10 h-10" />
          </div>
          <p class="font-body text-slate-500">Pilih salah satu dokumen di sebelah kiri untuk mulai mencetak.</p>
        </div>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
