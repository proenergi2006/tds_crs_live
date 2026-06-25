<script setup lang="ts">
import { ref } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';
import LoadingIcon from '@/components/Base/LoadingIcon';

const confirmButtonRef = ref<HTMLButtonElement | null>(null);

withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    loading?: boolean;
  }>(),
  {
    title: 'Hapus Data',
    description: 'Anda yakin ingin menghapus data ini?',
    confirmText: 'Hapus',
    loading: false,
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
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
          <Lucide icon="Trash2" class="h-8 w-8 text-red-600" />
        </div>

        <h3 class="font-header mt-5">
          {{ title }}
        </h3>

        <p class="font-body mt-2">
          {{ description }} <br />
          Tindakan ini tidak dapat dibatalkan.
        </p>
      </div>

      <div class="flex justify-center gap-3 border-t border-slate-200 px-6 py-4">
        <Button variant="outline-secondary" :disabled="loading" @click="$emit('close')">
          Batal
        </Button>

        <Button ref="confirmButtonRef" variant="danger" :disabled="loading" @click="$emit('confirm')">
          <Lucide v-if="loading" icon="Loader2" class="h-4 w-4 mr-1 animate-spin" />
          {{ confirmText }}
        </Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
