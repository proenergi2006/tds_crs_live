<script setup lang="ts">
import { ref } from 'vue';

import Button from '@/components/Base/Button';
import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue';
import { Dialog } from '@/components/Base/Headless';

const confirmButtonRef = ref<HTMLButtonElement | null>(null);

withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    icon?: Icon;
    iconClass?: string;
    variant?: 'primary' | 'danger' | 'success' | 'warning';
    loading?: boolean;
    confirmDisabled?: boolean;
    showCancel?: boolean;
    // diteruskan ke Dialog dasar — klik luar/ESC gak nutup dialog kalau true
    staticBackdrop?: boolean;
  }>(),
  {
    title: 'Konfirmasi',
    description: 'Apakah Anda yakin?',
    confirmText: 'Ya, lanjutkan',
    cancelText: 'Batal',
    icon: 'AlertCircle',
    iconClass: 'bg-primary/10 text-primary',
    variant: 'primary',
    loading: false,
    confirmDisabled: false,
    showCancel: true,
    staticBackdrop: false,
  },
);

defineEmits<{
  (e: 'close'): void;
  (e: 'confirm'): void;
}>();
</script>

<template>
  <Dialog :open="open" :staticBackdrop="staticBackdrop" @close="$emit('close')" :initialFocus="confirmButtonRef">
    <Dialog.Panel>
      <div class="p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" :class="iconClass">
          <Lucide :icon="icon" class="h-8 w-8" />
        </div>

        <h3 class="font-header mt-5">
          {{ title }}
        </h3>

        <p class="font-body mt-2">
          {{ description }}
        </p>

        <div v-if="$slots.default" class="mt-4 text-left">
          <slot />
        </div>
      </div>

      <div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4">
        <!-- showCancel=false dipakai dialog yang cuma boleh ditutup lewat tombol konfirmasi -->
        <template v-if="showCancel">
          <Button variant="outline-secondary" :disabled="loading" @click="$emit('close')">
            {{ cancelText }}
          </Button>
        </template>

        <Button ref="confirmButtonRef" :variant="variant" :disabled="loading || confirmDisabled" @click="$emit('confirm')">
          <Lucide v-if="loading" icon="Loader2" class="mr-1 h-4 w-4 animate-spin" />
          {{ confirmText }}
        </Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
