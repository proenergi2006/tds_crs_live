<script setup lang="ts">
import { ref } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';

const confirmButtonRef = ref<HTMLButtonElement | null>(null);

withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    loading?: boolean;
    showConfirm?: boolean;
    cancelText?: string;
  }>(),
  {
    title: 'Hapus Data',
    description: 'Anda yakin ingin menghapus data ini?',
    confirmText: 'Hapus',
    loading: false,
    showConfirm: true,
    cancelText: "Batal",
  },
);

defineEmits<{
  (e: 'close'): void;
  (e: 'confirm'): void;
}>();
</script>

<template>
  <Dialog :open="open" @close="$emit('close')" :initialFocus="confirmButtonRef">
    <Dialog.Panel>
      <div class="p-6 text-center">
        <div class="flex justify-center items-center bg-red-100 mx-auto rounded-full w-16 h-16">
          <Lucide icon="Trash2" class="w-8 h-8 text-red-600" />
        </div>

        <h3 class="mt-5 text-section-title">
          {{ title }}
        </h3>

        <slot>
          <p class="mt-2 text-body">
            {{ description }} <br />
            Tindakan ini tidak dapat dibatalkan.
          </p>
        </slot>
      </div>

      <div class="flex justify-center gap-3 px-6 py-4 border-slate-200 border-t">
        <Button variant="outline-secondary" :disabled="loading" @click="$emit('close')">
          {{ cancelText }}
        </Button>

        <template v-if="showConfirm">
          <Button ref="confirmButtonRef" variant="danger" :disabled="loading" @click="$emit('confirm')">
            <Lucide v-if="loading" icon="Loader2" class="mr-1 w-4 h-4 animate-spin" />
            {{ confirmText }}
          </Button>
        </template>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
