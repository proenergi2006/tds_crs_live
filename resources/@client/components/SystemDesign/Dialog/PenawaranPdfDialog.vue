<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import { FormLabel } from '@/components/Base/Form'
import RadioCard from '@/components/SystemDesign/Form/RadioCard.vue'

export type PdfLang = 'id' | 'en'
export type PdfPriceFormat = 'dpp' | 'detail'

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
  (e: 'submit', payload: { lang: PdfLang; priceFormat: PdfPriceFormat }): void
}>()

const lang = ref<PdfLang>('id')
const priceFormat = ref<PdfPriceFormat>('dpp')

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    lang.value = 'id'
    priceFormat.value = 'dpp'
  }
})

function submit() {
  emit('submit', { lang: lang.value, priceFormat: priceFormat.value })
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
          <h3 class="font-header mt-5">Cetak PDF Penawaran</h3>
          <p class="font-body mt-2">Pilih bahasa dan format harga sebelum mencetak.</p>
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

          <div>
            <FormLabel class="mb-2 block">Tampilan Harga Penawaran</FormLabel>
            <div class="space-y-2">
              <RadioCard v-model="priceFormat" value="dpp" title="Harga DPP"
                description="Harga dasar + OAT ditampilkan sebagai satu nilai" :disabled="loading" />
              <RadioCard v-model="priceFormat" value="detail" title="Rinci Harga"
                description="Harga dasar dan OAT dipecah jadi baris tersendiri" :disabled="loading" />
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
