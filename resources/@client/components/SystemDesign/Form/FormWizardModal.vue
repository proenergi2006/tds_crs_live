<script setup lang="ts">
import { computed } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue';

type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

const props = withDefaults(
  defineProps<{
    open: boolean;
    title: string;
    description?: string;
    steps: { title: string; description?: string }[];
    currentStep: number;
    loading?: boolean;
    error?: string | null;
    size?: ModalSize;
    submitText?: string;
    staticBackdrop?: boolean;
    hideSubmit?: boolean;
    cancelText?: string;
  }>(),
  {
    loading: false,
    error: null,
    size: 'lg',
    submitText: 'Simpan',
    staticBackdrop: false,
    hideSubmit: false,
    cancelText: 'Batal',
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'back'): void;
  (e: 'next'): void;
  (e: 'submit'): void;
}>();

const sizeClass = computed(() => {
  return {
    sm: 'sm:w-[420px]',
    md: 'sm:w-[560px]',
    lg: 'sm:w-[90%] lg:w-[760px]',
    xl: 'sm:w-[90%] lg:w-[960px]',
  }[props.size];
});

const derivedSteps = computed<StepItem[]>(() =>
  props.steps.map((step, index) => ({
    title: step.title,
    description: step.description,
    status: index < props.currentStep ? 'completed' : index === props.currentStep ? 'active' : 'pending',
  })),
);

function handleClose() {
  if (props.loading) return;

  emit('close');
}
</script>

<template>
  <Dialog :open="open" :size="size" :static-backdrop="staticBackdrop" @close="handleClose">
    <Dialog.Panel :class="['overflow-hidden p-0', sizeClass]">
      <Dialog.Title class="flex-col items-stretch gap-4 px-6 py-5 border-slate-200 border-b">
        <div>
          <h2 class="text-section-title">
            {{ title }}
          </h2>

          <p v-if="description" class="mt-1 text-body">
            {{ description }}
          </p>
        </div>
        <div class="top-0 right-0 absolute mt-5 mr-6">
          <button type="button" class="hover:bg-slate-100 p-1 rounded-lg text-slate-400 hover:text-slate-600 transition"
            @click="handleClose">
            <Lucide icon="X" class="w-5 h-5" />
          </button>
        </div>

        <Stepper :steps="derivedSteps" direction="horizontal" size="sm" :show-status-badge="false" />
      </Dialog.Title>

      <Dialog.Description class="px-6 py-5 h-[calc(70vh-88px)] overflow-y-auto">
        <div v-if="error" class="bg-rose-50 mb-4 px-4 py-3 border border-rose-200 rounded-lg text-body !text-rose-700">
          {{ error }}
        </div>

        <slot />
      </Dialog.Description>

      <Dialog.Footer class="flex justify-between items-center px-6 py-4 border-slate-200 border-t">
        <div class="flex items-center gap-2">
          <slot name="footer-start" />
        </div>

        <div class="flex items-center gap-2">
          <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" :disabled="loading"
            @click="handleClose">
            <Lucide icon="X" class="w-4 h-4" />
            {{ cancelText }}
          </Button>

          <Button v-if="currentStep > 0" type="button" variant="outline-secondary"
            class="inline-flex items-center gap-2" :disabled="loading" @click="$emit('back')">
            <Lucide icon="ChevronLeft" class="w-4 h-4" />
            Kembali
          </Button>

          <Button v-if="currentStep < steps.length - 1" type="button" variant="primary"
            class="inline-flex items-center gap-2" @click="$emit('next')">
            Lanjut
            <Lucide icon="ChevronRight" class="w-4 h-4" />
          </Button>

          <Button v-if="currentStep === steps.length - 1 && !hideSubmit" type="button" variant="primary"
            class="inline-flex items-center gap-2" :disabled="loading" @click="$emit('submit')">
            <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
            <Lucide v-else icon="Save" class="w-4 h-4" />
            {{ submitText }}
          </Button>
        </div>
      </Dialog.Footer>
    </Dialog.Panel>
  </Dialog>
</template>
