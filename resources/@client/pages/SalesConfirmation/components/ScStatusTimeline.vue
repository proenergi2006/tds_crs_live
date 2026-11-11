<template>
  <ScCard title="Status Sales Confirmation">
    <ol class="m-0 p-0 list-none">
      <li v-for="(step, index) in steps" :key="step.key" class="relative flex gap-3 pb-3.5 last:pb-0"
        :aria-current="step.state === 'current' ? 'step' : undefined">
        <span v-if="index < steps.length - 1" class="top-[26px] bottom-0.5 left-[10px] absolute bg-slate-200 w-px"
          aria-hidden="true" />
        <span class="z-[1] relative flex justify-center items-center rounded-full w-[22px] h-[22px] text-caption shrink-0"
          :class="MARKER_CLASS[step.state]">
          <Lucide v-if="step.state === 'done'" icon="Check" class="w-3 h-3" />
          <Lucide v-else-if="step.state === 'rejected'" icon="X" class="w-3 h-3" />
          <template v-else>{{ index + 1 }}</template>
        </span>

        <div class="space-y-0.5 min-w-0">
          <div class="text-body-strong" :class="step.state === 'todo' || step.state === 'skipped' ? '!text-slate-500' : ''">
            {{ step.title }}
          </div>
          <div v-for="(line, lineIndex) in step.lines" :key="lineIndex" class="text-caption">{{ line }}</div>
          <p v-if="step.note"
            class="bg-slate-50 mt-1 px-2.5 py-1.5 border-slate-300 border-l-[3px] rounded-r-md text-body break-words whitespace-pre-line">
            {{ step.note }}
          </p>
        </div>
      </li>
    </ol>
  </ScCard>
</template>

<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

import type { TimelineStep, TimelineStepState } from '../workflow'
import ScCard from './ScCard.vue'

defineProps<{
  steps: TimelineStep[]
}>()

const MARKER_CLASS: Record<TimelineStepState, string> = {
  done: 'bg-primary text-white',
  current: 'bg-white text-primary border-2 border-primary',
  todo: 'bg-slate-100 text-slate-500',
  rejected: 'bg-danger/10 text-danger',
  skipped: 'bg-slate-100 text-slate-400',
}
</script>
