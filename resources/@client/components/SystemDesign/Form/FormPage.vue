<script setup lang="ts">
import { computed } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Icon } from '@/components/Base/Lucide/Lucide.vue'

type PageSize = 'md' | 'lg' | 'xl' | 'full'
type PageSurface = 'boxed' | 'plain'
type PageLayout = 'default' | 'sidebar'
type FooterPlacement = 'bottom' | 'sidebar'

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
    disableEnterSubmit?: boolean;
    showFooter?: boolean;
    surface?: PageSurface;
    layout?: PageLayout;
    footerPlacement?: FooterPlacement;
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
    disableEnterSubmit: true,
    showFooter: true,
    surface: 'boxed',
    layout: 'default',
    footerPlacement: 'bottom',
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

const useBoxedSurface = computed(() => props.surface === 'boxed')
const useSidebarLayout = computed(() => props.layout === 'sidebar')
const useBottomFooter = computed(() => props.footerPlacement === 'bottom')
const useSidebarFooter = computed(() => props.footerPlacement === 'sidebar')

function handleCancel() {
  if (props.loading || props.disableCancel) return

  emit('cancel')
}

function handleSubmit() {
  if (props.loading || props.disableSubmit) return

  emit('submit')
}

// Cegah implicit submit saat user menekan Enter di dalam input.
// Textarea (butuh newline) dan tombol (Enter = klik) tetap dibiarkan normal.
function handleKeydown(event: KeyboardEvent) {
  if (!props.disableEnterSubmit) return
  if (event.key !== 'Enter') return

  const target = event.target as HTMLElement | null
  const tag = target?.tagName

  if (tag === 'TEXTAREA' || tag === 'BUTTON') return

  event.preventDefault()
}
</script>

<template>
  <div class="page-content-wrapper">
    <form class="intro-x flex flex-col gap-4" @submit.prevent="handleSubmit" @keydown="handleKeydown">
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

      <div v-if="!useSidebarLayout" :class="[useBoxedSurface ? 'box overflow-hidden p-0' : '', sizeClass]">
        <template v-if="useBoxedSurface">
          <div v-if="$slots.header" class="border-b border-slate-200 px-6 py-5">
            <slot name="header" />
          </div>

          <div class="px-6 py-5">
            <div v-if="error"
              class="mb-4 whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ error }}
            </div>

            <slot />
          </div>

          <div v-if="showFooter && useBottomFooter"
            class="flex flex-col-reverse gap-2 border-t border-slate-200 px-6 py-4 sm:flex-row sm:justify-end">
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
        </template>

        <template v-else>
          <div v-if="$slots.header" class="mb-5">
            <slot name="header" />
          </div>

          <div v-if="error"
            class="mb-4 whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ error }}
          </div>

          <slot />

          <div v-if="showFooter && useBottomFooter"
            class="mt-6 flex flex-col-reverse gap-2 rounded-lg bg-white p-4 shadow-sm sm:flex-row sm:justify-end">
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
        </template>
      </div>

      <div v-else :class="['grid grid-cols-1 gap-6 xl:grid-cols-3', sizeClass]">
        <div class="space-y-6 xl:col-span-2">
          <template v-if="useBoxedSurface">
            <div class="box overflow-hidden p-0">
              <div v-if="$slots.header" class="border-b border-slate-200 px-6 py-5">
                <slot name="header" />
              </div>

              <div class="px-6 py-5">
                <div v-if="error"
                  class="mb-4 whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                  {{ error }}
                </div>

                <slot />
              </div>

              <div v-if="showFooter && useBottomFooter"
                class="flex flex-col-reverse gap-2 border-t border-slate-200 px-6 py-4 sm:flex-row sm:justify-end">
                <slot name="footer">
                  <Button type="button" variant="outline-secondary"
                    class="inline-flex items-center justify-center gap-2" :disabled="loading || disableCancel"
                    @click="handleCancel">
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
          </template>

          <template v-else>
            <div v-if="$slots.header">
              <slot name="header" />
            </div>

            <div v-if="error"
              class="whitespace-pre-line rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ error }}
            </div>

            <slot />

            <div v-if="showFooter && useBottomFooter"
              class="flex flex-col-reverse gap-2 rounded-lg bg-white p-4 shadow-sm sm:flex-row sm:justify-end">
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
          </template>
        </div>

        <div class="xl:col-span-1">
          <div class="sticky top-20 space-y-6">
            <slot name="sidebar" />

            <div v-if="showFooter && useSidebarFooter" class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm">
              <slot name="footer">
                <Button type="submit" variant="primary" class="inline-flex items-center justify-center gap-2"
                  :disabled="loading || disableSubmit">
                  <Lucide v-if="loading" icon="Loader2" class="h-4 w-4 animate-spin" />
                  <Lucide v-else :icon="submitIcon" class="h-4 w-4" />
                  {{ submitText }}
                </Button>

                <Button type="button" variant="outline-secondary" class="inline-flex items-center justify-center gap-2"
                  :disabled="loading || disableCancel" @click="handleCancel">
                  <Lucide :icon="cancelIcon" class="h-4 w-4" />
                  {{ cancelText }}
                </Button>
              </slot>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>
