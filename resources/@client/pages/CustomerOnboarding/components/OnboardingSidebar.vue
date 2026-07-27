<script setup lang="ts">
import { computed } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import logoUrl from '@/assets/images/putih-tulisan-atas.png'
import logoUrl2 from '@/assets/images/tds-crs-new.png'

interface StepMeta {
  title: string
  hasError?: boolean
}

const props = withDefaults(
  defineProps<{
    steps: StepMeta[]
    currentStep: number
    isLastStep: boolean
    submitting?: boolean
  }>(),
  {
    submitting: false,
  },
)

defineEmits<{
  (e: 'back'): void
  (e: 'next'): void
  (e: 'submit'): void
}>()

type StepStatus = 'completed' | 'active' | 'pending'

function stepStatus(idx: number): StepStatus {
  if (idx + 1 < props.currentStep) return 'completed'
  if (idx + 1 === props.currentStep) return 'active'
  return 'pending'
}

const progressPercent = computed(() => {
  if (props.steps.length <= 1) return props.currentStep >= props.steps.length ? 100 : 0
  return ((props.currentStep - 1) / (props.steps.length - 1)) * 100
})
</script>

<template>
  <div class="flex h-full flex-col">
    <div class="flex items-center justify-between gap-3 bg-slate-900 px-5 py-4">
      <img :src="logoUrl" alt="TDS" class="h-14 w-auto" />
      <img :src="logoUrl2" alt="CRS" class="h-14 w-auto" />
    </div>

    <div class="p-5">
      <div class="mb-6">
        <p class="font-header text-lg !text-white">Customer Onboarding Form</p>
        <p class="font-caption mt-1 !text-slate-400">Lengkapi data perusahaan Anda</p>
      </div>

      <ul class="flex-1 space-y-1">
        <li v-for="(step, idx) in props.steps" :key="step.title" class="flex gap-3">
          <div class="flex flex-col items-center">
            <span
              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-300"
              :class="[
                step.hasError && 'border-danger bg-danger/10 text-danger',
                !step.hasError && stepStatus(idx) === 'completed' && 'border-white bg-white text-slate-900',
                !step.hasError && stepStatus(idx) === 'active' && 'border-white bg-transparent text-white',
                !step.hasError && stepStatus(idx) === 'pending' && 'border-slate-700 bg-transparent text-slate-500',
              ]">
              <Lucide v-if="step.hasError" icon="AlertTriangle" class="h-3.5 w-3.5" />
              <Lucide v-else-if="stepStatus(idx) === 'completed'" icon="Check" class="h-3.5 w-3.5" />
              <span v-else class="font-barlow text-xs font-bold">{{ idx + 1 }}</span>
            </span>
            <div v-if="idx < props.steps.length - 1" class="w-0.5 flex-1 py-1"
              :class="stepStatus(idx) === 'completed' ? 'bg-white' : 'bg-slate-700'" style="min-height: 1.25rem" />
          </div>

          <div class="flex-1 pb-4 pt-0.5">
            <span class="font-body block" :class="[
              step.hasError && 'text-danger',
              !step.hasError && stepStatus(idx) === 'pending' && '!text-slate-500',
              !step.hasError && stepStatus(idx) !== 'pending' && '!text-white',
            ]">
              {{ step.title }}
            </span>
            <span v-if="step.hasError" class="font-label mt-0.5 inline-block text-danger">Periksa kembali isian</span>
          </div>
        </li>
      </ul>
    </div>

    <div class="mt-auto border-t border-slate-800 p-5">
      <div class="mb-4">
        <div class="mb-1.5 flex items-center justify-between">
          <p class="font-caption !text-slate-400">Step {{ props.currentStep }} of {{ props.steps.length }}</p>
        </div>
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-700">
          <div class="h-full rounded-full bg-white transition-all duration-300"
            :style="{ width: `${progressPercent}%` }" />
        </div>
      </div>

      <div class="flex flex-col gap-2">
        <Button type="button" variant="outline-secondary"
          class="inline-flex items-center justify-center gap-2 !border-slate-600 !text-white hover:!bg-white/10"
          :disabled="props.currentStep === 1" @click="$emit('back')">
          <Lucide icon="ChevronLeft" class="h-4 w-4" />
          Back
        </Button>

        <Button v-if="!props.isLastStep" type="button" variant="white"
          class="inline-flex items-center justify-center gap-2" @click="$emit('next')">
          Next
          <Lucide icon="ChevronRight" class="h-4 w-4" />
        </Button>

        <Button v-else type="button" variant="white" class="inline-flex items-center justify-center gap-2"
          :disabled="props.submitting" @click="$emit('submit')">
          <Lucide icon="Check" class="h-4 w-4" />
          {{ props.submitting ? 'Mengirim…' : 'Submit' }}
        </Button>
      </div>
    </div>
  </div>
</template>
