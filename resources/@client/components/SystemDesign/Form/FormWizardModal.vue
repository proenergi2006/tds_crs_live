<script setup lang="ts">
import { computed } from 'vue';

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import { Dialog } from '@/components/Base/Headless';
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue';

type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

// Gabungan dari FormModal.vue (dialog/backdrop/footer chrome) + Stepper.vue
// (step indicator, components/SystemDesign/Stepper/Stepper.vue), ditambah
// navigasi Back/Next/Save bertahap. Bagian navigasinya baru di sini --
// FormModal maupun Stepper sendiri-sendiri belum punya.
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
  }>(),
  {
    loading: false,
    error: null,
    size: 'lg',
    submitText: 'Simpan',
    staticBackdrop: false,
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
      <Dialog.Title class="flex-col items-stretch gap-4 border-b border-slate-200 px-6 py-5">
        <div>
          <h2 class="font-header">
            {{ title }}
          </h2>

          <p v-if="description" class="font-body mt-1">
            {{ description }}
          </p>
        </div>
        <div class="absolute top-0 right-0 mt-5 mr-6">
          <button type="button" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            @click="handleClose">
            <Lucide icon="X" class="h-5 w-5" />
          </button>
        </div>

        <Stepper :steps="derivedSteps" direction="horizontal" size="sm" :show-status-badge="false" />
      </Dialog.Title>

      <Dialog.Description class="h-[calc(70vh-88px)] overflow-y-auto px-6 py-5">
        <div v-if="error" class="font-body mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 !text-rose-700">
          {{ error }}
        </div>

        <slot />
      </Dialog.Description>

      <Dialog.Footer class="flex items-center justify-between border-t border-slate-200 px-6 py-4">
        <div class="flex items-center gap-2">
          <slot name="footer-start" />
        </div>

        <div class="flex items-center gap-2">
          <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" :disabled="loading"
            @click="handleClose">
            <Lucide icon="X" class="h-4 w-4" />
            Batal
          </Button>

          <Button v-if="currentStep > 0" type="button" variant="outline-secondary"
            class="inline-flex items-center gap-2" :disabled="loading" @click="$emit('back')">
            <Lucide icon="ChevronLeft" class="h-4 w-4" />
            Kembali
          </Button>

          <Button v-if="currentStep < steps.length - 1" type="button" variant="primary"
            class="inline-flex items-center gap-2" @click="$emit('next')">
            Lanjut
            <Lucide icon="ChevronRight" class="h-4 w-4" />
          </Button>

          <Button v-if="currentStep === steps.length - 1" type="button" variant="primary"
            class="inline-flex items-center gap-2" :disabled="loading" @click="$emit('submit')">
            <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
            <Lucide v-else icon="Save" class="h-4 w-4" />
            {{ submitText }}
          </Button>
        </div>
      </Dialog.Footer>
    </Dialog.Panel>
  </Dialog>
</template>
