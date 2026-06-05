<script setup lang="ts">
import { computed } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Icon } from '@/components/Base/Lucide/Lucide.vue'

type PageSize = 'md' | 'lg' | 'xl' | 'full'

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    loading?: boolean;
    error?: string | null;
    size?: PageSize;
    submitText?: string;
    cancelText?: string;
    submitIcon?: Icon;
    cancelIcon?: Icon;
    disableSubmit?: boolean;
    disableCancel?: boolean;
    showFooter?: boolean;
  }>(),
  {
    loading: false,
    error: null,
    size: 'lg',
    submitText: 'Simpan',
    cancelText: 'Batal',
    submitIcon: 'Save',
    cancelIcon: 'X',
    disableSubmit: false,
    disableCancel: false,
    showFooter: true,
  },
)

const emit = defineEmits<{
  (e: 'cancel'): void;
  (e: 'submit'): void;
}>()

const sizeClass = computed(() => {
  return {
    md: 'max-w-2xl',
    lg: 'max-w-4xl',
    xl: 'max-w-6xl',
    full: 'max-w-none',
  }[props.size]
})

function handleCancel() {
  if (props.loading || props.disableCancel) return

  emit('cancel')
}

function handleSubmit() {
  if (props.loading || props.disableSubmit) return

  emit('submit')
}
</script>

<template>
  <form class="grid grid-cols-12 gap-6" @submit.prevent="handleSubmit">
    <div class="col-span-12 mt-4 intro-y">
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-slate-800">
            {{ title }}
          </h2>

          <p v-if="description" class="mt-1 text-sm text-slate-500">
            {{ description }}
          </p>
        </div>

        <div v-if="$slots.action">
          <slot name="action" />
        </div>
      </div>

      <div :class="['box overflow-hidden p-0', sizeClass]">
        <div v-if="$slots.header" class="border-b border-slate-200 px-6 py-5">
          <slot name="header" />
        </div>

        <div class="px-6 py-5">
          <div v-if="error" class="mb-4 whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ error }}
          </div>

          <slot />
        </div>

        <div v-if="showFooter" class="flex flex-col-reverse gap-2 border-t border-slate-200 px-6 py-4 sm:flex-row sm:justify-end">
          <slot name="footer">
            <Button type="button" variant="outline-secondary" class="inline-flex items-center justify-center gap-2"
              :disabled="loading || disableCancel" @click="handleCancel">
              <Lucide :icon="cancelIcon" class="h-4 w-4" />
              {{ cancelText }}
            </Button>

            <Button type="submit" variant="primary" class="inline-flex items-center justify-center gap-2"
              :disabled="loading || disableSubmit">
              <Lucide v-if="loading" icon="Loader2" class="h-4 w-4 animate-spin" />
              <Lucide v-else :icon="submitIcon" class="h-4 w-4" />
              {{ submitText }}
            </Button>
          </slot>
        </div>
      </div>
    </div>
  </form>
</template>
