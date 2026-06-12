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
      iconWrapV: 'h-6 w-6',
      iconWrapH: 'h-7 w-7',
      iconInner: 'h-3 w-3',
      gap: 'gap-3',
      contentPt: 'pt-0.5',
      stepPb: 'pb-4',
      labelText: 'text-[9px]',
      titleText: 'text-xs',
      descText: 'text-[10px]',
      badgePad: 'px-2 py-0.5',
      badgeText: 'text-[10px]',
    },
    md: {
      iconWrapV: 'h-8 w-8',
      iconWrapH: 'h-9 w-9',
      iconInner: 'h-4 w-4',
      gap: 'gap-4',
      contentPt: 'pt-0.5',
      stepPb: 'pb-6',
      labelText: 'text-[10px]',
      titleText: 'text-sm',
      descText: 'text-xs',
      badgePad: 'px-2.5 py-0.5',
      badgeText: 'text-xs',
    },
    lg: {
      iconWrapV: 'h-10 w-10',
      iconWrapH: 'h-12 w-12',
      iconInner: 'h-5 w-5',
      gap: 'gap-5',
      contentPt: 'pt-1',
      stepPb: 'pb-8',
      labelText: 'text-xs',
      titleText: 'text-base',
      descText: 'text-sm',
      badgePad: 'px-3 py-1',
      badgeText: 'text-xs',
    },
  } as const;

  return configs[props.size];
});

// Half of iconWrapH (h-9/h-12/h-14) → circle center offset for horizontal track
const halfCircleH = computed(() => ({ sm: 14, md: 18, lg: 24 } as const)[props.size]);

// Half of iconWrapV (h-8/h-10/h-12) → circle center offset for vertical track
const halfCircleV = computed(() => ({ sm: 12, md: 16, lg: 20 } as const)[props.size]);

const lastCompletedIndex = computed(() => {
  let last = -1;
  props.steps.forEach((step, i) => {
    if (step.status === 'completed') last = i;
  });
  return last;
});

const progressFraction = computed(() => {
  if (props.steps.length < 2) return 0;

  const activeIndex = props.steps.findIndex(s => s.status === 'active');
  const lastCompleted = props.steps.reduce((last, s, i) =>
    s.status === 'completed' ? i : last, -1);

  const targetIndex = activeIndex >= 0 ? activeIndex : lastCompleted;

  if (targetIndex < 0) return 0;
  return targetIndex / (props.steps.length - 1);
});

// f × (100% − 2×halfCircle) = fraction of track from circle-center-0 to circle-center-(n-1)
const progressWidth = computed(() => {
  if (progressFraction.value === 0) return '0px';
  const f = progressFraction.value;
  const h = halfCircleH.value;
  return `calc(${halfCircleH.value}px + ${(f * 100).toFixed(2)}% - ${(f * 2 * h).toFixed(2)}px)`;
});

const progressHeight = computed(() => {
  if (progressFraction.value === 0) return '0px';
  const f = progressFraction.value;
  const v = halfCircleV.value;
  return `calc(${(f * 100).toFixed(2)}% - ${(f * 2 * v).toFixed(2)}px)`;
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
    status === 'active' && 'border-2 border-primary/20 bg-primary/10 text-primary ring-2 ring-primary/20',
    status === 'pending' && 'border-2 border-slate-200 bg-white text-slate-400',
  );
}

// Solid-bg override so the circle fully masks the absolute track line behind it.
// active overrides semi-transparent bg-primary/10 → solid bg-primary.
function solidIconWrapClass(status: StepItem['status'], wrapSize: string): string {
  return twMerge(
    iconWrapClass(status, wrapSize),
    'bg-white',
    status === 'completed' && 'bg-success',
    status === 'active' && 'border-transparent bg-primary text-white ring-0',
  );
}
</script>

<template>
  <!-- Vertical Stepper -->
  <div v-if="direction === 'vertical'" class="relative flex flex-col">
    <!-- Background track: w-0.5 -->
    <div class="absolute z-0 w-0.5 bg-slate-200"
      :style="{ top: `${halfCircleV}px`, bottom: `${halfCircleV}px`, left: `${halfCircleV - 1}px` }" />

    <!-- Progress: w-1.5, di-center terhadap track -->
    <div class="absolute z-0 w-1.5 bg-success transition-all duration-300" :style="{
      top: `${halfCircleV}px`,
      left: `${halfCircleV}px`,
      height: progressHeight,
      transform: 'translateX(-50%)',
      maskImage: 'linear-gradient(to bottom, black 60%, transparent 100%)',
      WebkitMaskImage: 'linear-gradient(to bottom, black 60%, transparent 100%)',
    }" />
    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex"
      :class="[sz.gap, index < steps.length - 1 && sz.stepPb]">
      <!-- Icon -->
      <div class="shrink-0">
        <div :class="solidIconWrapClass(step.status, sz.iconWrapV)">
          <Lucide :icon="step.icon ?? getDefaultIcon(step.status)" :class="sz.iconInner" />
        </div>
      </div>

      <!-- Content -->
      <div :class="sz.contentPt">
        <p :class="twMerge('font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
          {{ getStepLabel(step, index) }}
        </p>
        <p :class="twMerge('mt-0.5 font-bold', sz.titleText,
          step.status === 'completed' && 'text-success',
          step.status === 'active' && 'text-primary',
          step.status === 'pending' && 'text-slate-400',
        )">
          {{ step.title }}
        </p>
        <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
          {{ step.description }}
        </p>
        <span v-if="effectiveShowBadge" :class="twMerge(
          'mt-1.5 inline-flex rounded-full font-medium',
          sz.badgeText, sz.badgePad,
          step.status === 'completed' && 'bg-emerald-100 text-emerald-700',
          step.status === 'active' && 'bg-primary/10 text-primary',
          step.status === 'pending' && 'bg-slate-100 text-slate-500',
        )">
          {{ getStatusText(step) }}
        </span>
      </div>
    </div>
  </div>

  <!-- Horizontal Stepper -->
  <!-- justify-between: circle 0 left=0 (center=halfCircleH), circle n-1 right=0 (center=W−halfCircleH) -->
  <!-- bg line left/right=halfCircleH → spans exactly center-to-center -->
  <div v-else class="relative flex w-full justify-between">
    <!-- Background track -->
    <div class="absolute z-0 h-0.5 bg-slate-200"
      :style="{ top: `${halfCircleH}px`, left: `${halfCircleH * 2}px`, right: `${halfCircleH}px` }" />

    <!-- Progress: h-1.5, di-center terhadap track -->
    <div class="absolute z-0 h-1.5 bg-success transition-all duration-300" :style="{
      top: `${halfCircleH}px`,
      left: `${halfCircleH * 2}px`,
      width: progressWidth,
      transform: 'translateY(-50%)',
      maskImage: 'linear-gradient(to right, black 60%, transparent 100%)',
      WebkitMaskImage: 'linear-gradient(to right, black 60%, transparent 100%)',
    }" />
    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
      <div :class="solidIconWrapClass(step.status, sz.iconWrapH)">
        <Lucide :icon="step.icon ?? getDefaultIcon(step.status)" :class="sz.iconInner" />
      </div>

      <div class="mt-2 text-center">
        <p :class="twMerge('font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
          {{ getStepLabel(step, index) }}
        </p>
        <p :class="twMerge('mt-0.5 font-bold', sz.titleText,
          step.status === 'completed' && 'text-slate-700',
          step.status === 'active' && 'text-primary',
          step.status === 'pending' && 'text-slate-400',
        )">
          {{ step.title }}
        </p>
        <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
          {{ step.description }}
        </p>
        <span v-if="effectiveShowBadge" :class="twMerge(
          'mt-1.5 inline-flex rounded-full font-medium',
          sz.badgeText, sz.badgePad,
          step.status === 'completed' && 'bg-emerald-100 text-emerald-700',
          step.status === 'active' && 'bg-primary/10 text-primary',
          step.status === 'pending' && 'bg-slate-100 text-slate-500',
        )">
          {{ getStatusText(step) }}
        </span>
      </div>
    </div>
  </div>
</template>
