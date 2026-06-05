<script setup lang="ts">
import { computed } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';
import LoadingIcon from '@/components/Base/LoadingIcon';
import { Icon } from '@/components/Base/Lucide/Lucide.vue';

type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

const props = withDefaults(
  defineProps<{
    open: boolean;
    title: string;
    description?: string;
    loading?: boolean;
    error?: string | null;
    size?: ModalSize;
    submitText?: string;
    cancelText?: string;
    submitIcon?: Icon;
    closeOnLoading?: boolean;
  }>(),
  {
    loading: false,
    error: null,
    size: 'md',
    submitText: 'Simpan',
    cancelText: 'Batal',
    submitIcon: 'Save',
    closeOnLoading: false,
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'submit'): void;
}>();

const sizeClass = computed(() => {
  return {
    sm: 'w-[420px]',
    md: 'w-[560px]',
    lg: 'w-[760px]',
    xl: 'w-[960px]',
  }[props.size];
});

function handleClose() {
  if (props.loading && !props.closeOnLoading) return;

  emit('close');
}
</script>

<template>
  <Dialog :open="open" @close="handleClose">
    <Dialog.Panel :class="['overflow-hidden p-0', sizeClass]">
      <Dialog.Title class="border-b border-slate-200 px-6 py-5">
        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            {{ title }}
          </h2>

          <p v-if="description" class="mt-1 text-sm text-slate-500">
            {{ description }}
          </p>
        </div>
        <div class="absolute top-0 right-0 mt-5 mr-6">
          <button type="button" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            @click="handleClose">
            <Lucide icon="X" class="h-5 w-5" />
          </button>
        </div>
      </Dialog.Title>

      <Dialog.Description class="max-h-[70vh] overflow-y-auto px-6 py-5">
        <div v-if="error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {{ error }}
        </div>

        <slot />
      </Dialog.Description>

      <Dialog.Footer class="border-t border-slate-200 px-6 py-4">
        <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2 mr-2"
          :disabled="loading" @click="handleClose">
          <Lucide icon="X" class="h-4 w-4" />
          {{ cancelText }}
        </Button>

        <Button type="button" variant="primary" class="inline-flex items-center gap-2" :disabled="loading"
          @click="$emit('submit')">
          <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
          <Lucide v-else :icon="submitIcon" class="h-4 w-4" />
          {{ submitText }}
        </Button>
      </Dialog.Footer>
    </Dialog.Panel>
  </Dialog>
</template>
