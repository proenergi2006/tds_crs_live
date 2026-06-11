<script setup lang="ts">
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';
import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue';

export interface StepItem {
  title: string;
  description?: string;
  status: 'completed' | 'active' | 'pending';
  icon?: Icon;
  label?: string;
  statusText?: string;
}

interface StepperProps {
  steps: StepItem[];
  direction?: 'vertical' | 'horizontal';
  size?: 'sm' | 'md' | 'lg';
  showStatusBadge?: boolean;
}

const props = withDefaults(defineProps<StepperProps>(), {
  direction: 'vertical',
  size: 'md',
});

const effectiveShowBadge = computed(() => {
  if (props.showStatusBadge !== undefined) return props.showStatusBadge;
  return props.direction === 'horizontal';
});

const sz = computed(() => {
  const configs = {
    sm: {
      iconWrapV:   'h-8 w-8',
      iconWrapH:   'h-9 w-9',
      iconInner:   'h-4 w-4',
      gap:         'gap-3',
      contentPt:   'pt-0.5',
      stepPb:      'pb-4',
      connectorMt: 'mt-[18px]',
      labelText:   'text-[9px]',
      titleText:   'text-xs',
      descText:    'text-[10px]',
      badgePad:    'px-2 py-0.5',
      badgeText:   'text-[10px]',
    },
    md: {
      iconWrapV:   'h-10 w-10',
      iconWrapH:   'h-12 w-12',
      iconInner:   'h-5 w-5',
      gap:         'gap-4',
      contentPt:   'pt-0.5',
      stepPb:      'pb-6',
      connectorMt: 'mt-6',
      labelText:   'text-[10px]',
      titleText:   'text-sm',
      descText:    'text-xs',
      badgePad:    'px-2.5 py-0.5',
      badgeText:   'text-xs',
    },
    lg: {
      iconWrapV:   'h-12 w-12',
      iconWrapH:   'h-14 w-14',
      iconInner:   'h-6 w-6',
      gap:         'gap-5',
      contentPt:   'pt-1',
      stepPb:      'pb-8',
      connectorMt: 'mt-7',
      labelText:   'text-xs',
      titleText:   'text-base',
      descText:    'text-sm',
      badgePad:    'px-3 py-1',
      badgeText:   'text-xs',
    },
  } as const;

  return configs[props.size];
});

function getDefaultIcon(status: StepItem['status']): Icon {
  if (status === 'completed') return 'Check';
  if (status === 'active') return 'Loader2';
  return 'Clock';
}

function getStepLabel(step: StepItem, index: number): string {
  return step.label ?? `STEP ${index + 1}`;
}

function getStatusText(step: StepItem): string {
  if (step.statusText) return step.statusText;
  if (step.status === 'completed') return 'Selesai';
  if (step.status === 'active') return 'Berlangsung';
  return 'Menunggu';
}

function iconWrapClass(status: StepItem['status'], wrapSize: string): string {
  return twMerge(
    'flex shrink-0 items-center justify-center rounded-full',
    wrapSize,
    status === 'completed' && 'bg-success text-white',
    status === 'active'    && 'border-2 border-primary bg-primary/10 text-primary ring-2 ring-primary/20',
    status === 'pending'   && 'border-2 border-slate-200 bg-white text-slate-400',
  );
}
</script>

<template>
  <!-- Vertical Stepper -->
  <div v-if="direction === 'vertical'" class="flex flex-col">
    <div
      v-for="(step, index) in steps"
      :key="index"
      class="flex"
      :class="sz.gap"
    >
      <!-- Icon column: stretch to content height so connector fills the gap -->
      <div class="flex flex-col items-center self-stretch">
        <div :class="iconWrapClass(step.status, sz.iconWrapV)">
          <Lucide :icon="step.icon ?? getDefaultIcon(step.status)" :class="sz.iconInner" />
        </div>
        <div
          v-if="index < steps.length - 1"
          class="mt-1 w-0 flex-1 border-l-2 border-dashed"
          :class="step.status === 'completed' ? 'border-success' : 'border-slate-200'"
        />
      </div>

      <!-- Content -->
      <div :class="twMerge(sz.contentPt, index < steps.length - 1 && sz.stepPb)">
        <p :class="twMerge('font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
          {{ getStepLabel(step, index) }}
        </p>
        <p
          :class="twMerge('mt-0.5 font-bold', sz.titleText,
            step.status === 'completed' && 'text-success',
            step.status === 'active'    && 'text-primary',
            step.status === 'pending'   && 'text-slate-400',
          )"
        >
          {{ step.title }}
        </p>
        <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
          {{ step.description }}
        </p>
        <span
          v-if="effectiveShowBadge"
          :class="twMerge(
            'mt-1.5 inline-flex rounded-full font-medium',
            sz.badgeText, sz.badgePad,
            step.status === 'completed' && 'bg-emerald-100 text-emerald-700',
            step.status === 'active'    && 'bg-primary/10 text-primary',
            step.status === 'pending'   && 'bg-slate-100 text-slate-500',
          )"
        >
          {{ getStatusText(step) }}
        </span>
      </div>
    </div>
  </div>

  <!-- Horizontal Stepper -->
  <div v-else class="flex w-full items-start">
    <template v-for="(step, index) in steps" :key="index">
      <div class="flex flex-col items-center">
        <div :class="iconWrapClass(step.status, sz.iconWrapH)">
          <Lucide :icon="step.icon ?? getDefaultIcon(step.status)" :class="sz.iconInner" />
        </div>

        <div class="mt-2 text-center">
          <p :class="twMerge('font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
            {{ getStepLabel(step, index) }}
          </p>
          <p
            :class="twMerge('mt-0.5 font-bold', sz.titleText,
              step.status === 'completed' && 'text-slate-700',
              step.status === 'active'    && 'text-primary',
              step.status === 'pending'   && 'text-slate-400',
            )"
          >
            {{ step.title }}
          </p>
          <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
            {{ step.description }}
          </p>
          <span
            v-if="effectiveShowBadge"
            :class="twMerge(
              'mt-1.5 inline-flex rounded-full font-medium',
              sz.badgeText, sz.badgePad,
              step.status === 'completed' && 'bg-emerald-100 text-emerald-700',
              step.status === 'active'    && 'bg-primary/10 text-primary',
              step.status === 'pending'   && 'bg-slate-100 text-slate-500',
            )"
          >
            {{ getStatusText(step) }}
          </span>
        </div>
      </div>

      <!-- Connector line -->
      <div
        v-if="index < steps.length - 1"
        class="h-0 flex-1 border-t-2"
        :class="twMerge(
          sz.connectorMt,
          step.status === 'completed' ? 'border-success' : 'border-slate-200',
        )"
      />
    </template>
  </div>
</template>
