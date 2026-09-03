<script setup lang="ts">
import { computed } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';
import { Icon } from '@/components/Base/Lucide/Lucide.vue';

type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'xxl';

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
    submitDisabled?: boolean;
  }>(),
  {
    loading: false,
    error: null,
    size: 'md',
    submitText: 'Simpan',
    cancelText: 'Batal',
    submitIcon: 'Save',
    closeOnLoading: false,
    submitDisabled: false,
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'submit'): void;
}>();

const sizeClass = computed(() => {
  return {
    sm: 'sm:w-[420px]',
    md: 'sm:w-[560px]',
    lg: 'sm:w-[90%] lg:w-[760px]',
    xl: 'sm:w-[90%] lg:w-[960px]',
    xxl: 'sm:w-[90%] lg:w-[1260px]',
  }[props.size];
});

const dialogSize = computed(() => (props.size === 'xxl' ? 'xl' : props.size));

function handleClose() {
  if (props.loading && !props.closeOnLoading) return;

  emit('close');
}
</script>

<template>
  <Dialog :open="open" :size="dialogSize" @close="handleClose">
    <Dialog.Panel :class="['overflow-hidden p-0', sizeClass]">
      <Dialog.Title class="px-6 py-5 border-slate-200 border-b">
        <div>
          <h2 class="font-header">
            {{ title }}
          </h2>

          <p v-if="description" class="mt-1 font-body">
            {{ description }}
          </p>
        </div>
        <div class="top-0 right-0 absolute mt-5 mr-6">
          <button type="button" class="hover:bg-slate-100 p-1 rounded-lg text-slate-400 hover:text-slate-600 transition"
            @click="handleClose">
            <Lucide icon="X" class="w-5 h-5" />
          </button>
        </div>
      </Dialog.Title>

      <Dialog.Description class="px-6 py-5 max-h-[70vh] overflow-y-auto">
        <div v-if="error" class="bg-rose-50 mb-4 px-4 py-3 border border-rose-200 rounded-lg font-body !text-rose-700">
          {{ error }}
        </div>

        <slot />
      </Dialog.Description>

      <Dialog.Footer class="px-6 py-4 border-slate-200 border-t">
        <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2 mr-2"
          :disabled="loading" @click="handleClose">
          <Lucide icon="X" class="w-4 h-4" />
          {{ cancelText }}
        </Button>

        <Button type="button" variant="primary" class="inline-flex items-center gap-2" :disabled="loading || submitDisabled"
          @click="$emit('submit')">
          <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
          <Lucide v-else :icon="submitIcon" class="w-4 h-4" />
          {{ submitText }}
        </Button>
      </Dialog.Footer>
    </Dialog.Panel>
  </Dialog>
</template>
