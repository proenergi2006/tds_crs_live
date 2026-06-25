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
  timestamp?: string;
}

interface StepperProps {
  steps: StepItem[];
  direction?: 'vertical' | 'horizontal';
  size?: 'sm' | 'md' | 'lg';
  /** Tampilkan badge status (Selesai/Berlangsung/Menunggu) di tiap step. */
  showStatusBadge?: boolean;
  /** Tampilkan label "STEP 1" dst. di atas judul. Default off. */
  showLabel?: boolean;
}

const props = withDefaults(defineProps<StepperProps>(), {
  direction: 'vertical',
  size: 'md',
  showStatusBadge: true,
  showLabel: false,
});

const sz = computed(() => {
  const configs = {
    sm: {
      iconWrapV: 'h-6 w-6',
      iconWrapH: 'h-7 w-7',
      iconInner: 'h-3 w-3',
      dot: 'h-1.5 w-1.5',
      numText: 'text-[10px]',
      gap: 'gap-3',
      contentPt: 'pt-0.5',
      stepPb: 'pb-6',
      labelText: 'text-[9px]',
      titleText: 'text-xs',
      descText: 'text-[10px]',
      badgePad: 'px-1.5 py-0.5',
      badgeText: 'text-[9px]',
    },
    md: {
      iconWrapV: 'h-8 w-8',
      iconWrapH: 'h-9 w-9',
      iconInner: 'h-4 w-4',
      dot: 'h-2 w-2',
      numText: 'text-xs',
      gap: 'gap-4',
      contentPt: 'pt-0.5',
      stepPb: 'pb-8',
      labelText: 'text-[10px]',
      titleText: 'text-sm',
      descText: 'text-xs',
      badgePad: 'px-2 py-0.5',
      badgeText: 'text-[10px]',
    },
    lg: {
      iconWrapV: 'h-10 w-10',
      iconWrapH: 'h-12 w-12',
      iconInner: 'h-5 w-5',
      dot: 'h-2.5 w-2.5',
      numText: 'text-sm',
      gap: 'gap-5',
      contentPt: 'pt-1',
      stepPb: 'pb-10',
      labelText: 'text-xs',
      titleText: 'text-base',
      descText: 'text-sm',
      badgePad: 'px-2.5 py-1',
      badgeText: 'text-xs',
    },
  } as const;

  return configs[props.size];
});

// Half of iconWrapH (h-7/h-9/h-12) → circle center offset for horizontal track
const halfCircleH = computed(() => ({ sm: 14, md: 18, lg: 24 } as const)[props.size]);

// Half of iconWrapV (h-6/h-8/h-10) → circle center offset for vertical track
const halfCircleV = computed(() => ({ sm: 12, md: 16, lg: 20 } as const)[props.size]);

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

function getStepLabel(step: StepItem, index: number): string {
  return step.label ?? `STEP ${index + 1}`;
}

function getStatusText(step: StepItem): string {
  if (step.statusText) return step.statusText;
  if (step.status === 'completed') return 'DONE';
  if (step.status === 'active') return 'ACTIVE';
  return 'PENDING';
}

// Solid bg agar lingkaran menutup garis track yang ada di belakangnya.
function circleClass(status: StepItem['status'], wrapSize: string): string {
  return twMerge(
    'flex shrink-0 items-center justify-center rounded-full border-2',
    wrapSize,
    status === 'completed' && 'border-success bg-success text-white',
    status === 'active' && 'relative border-success bg-white text-success',
    status === 'pending' && 'border-slate-200 bg-white text-slate-400',
  );
}

function titleClass(status: StepItem['status']): string {
  return twMerge(
    'font-barlow font-bold',
    sz.value.titleText,
    status === 'completed' && 'text-slate-700',
    status === 'active' && 'text-slate-800',
    status === 'pending' && 'text-slate-400',
  );
}

function badgeClass(status: StepItem['status']): string {
  return twMerge(
    'inline-flex items-center gap-1 rounded font-barlow font-semibold uppercase tracking-wide',
    sz.value.badgePad,
    sz.value.badgeText,
    status === 'completed' && 'bg-emerald-100 text-emerald-700',
    status === 'active' && 'bg-primary/10 text-success',
    status === 'pending' && 'bg-slate-100 text-slate-500',
  );
}
</script>

<template>
  <!-- Vertical Stepper -->
  <div v-if="direction === 'vertical'" class="relative flex flex-col">
    <!-- Background track -->
    <div class="absolute z-0 w-1.5 bg-slate-200"
      :style="{ top: `${halfCircleV}px`, bottom: `${halfCircleV * 2}px`, left: `${halfCircleV - 3}px` }" />

    <!-- Progress -->
    <div class="absolute z-0 w-1.5 bg-success transition-all duration-500" :style="{
      top: `${halfCircleV}px`,
      left: `${halfCircleV - 2}px`,
      height: progressHeight,
    }" />

    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex"
      :class="[sz.gap, index < steps.length - 1 && sz.stepPb]">
      <!-- Icon -->
      <div class="shrink-0">
        <div :class="circleClass(step.status, sz.iconWrapV)">
          <span v-if="step.status === 'active'" class="step-arc text-success" aria-hidden="true" />
          <Lucide v-if="step.icon" :icon="step.icon" :class="sz.iconInner" />
          <Lucide v-else-if="step.status === 'completed'" icon="CheckCheck" :class="sz.iconInner" />
          <Lucide v-else-if="step.status === 'active'" icon="Loader2" :class="sz.iconInner" />
          <!-- <span v-else-if="step.status === 'active'"
            :class="twMerge('rounded-full bg-success animate-pulse', sz.dot)" /> -->
          <span v-else :class="twMerge('font-barlow font-bold', sz.numText)">{{ index + 1 }}</span>
        </div>
      </div>

      <!-- Content -->
      <div :class="['flex flex-1 items-start justify-between gap-3', sz.contentPt]">
        <div class="min-w-0">
          <p v-if="showLabel"
            :class="twMerge('mb-0.5 font-barlow font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
            {{ getStepLabel(step, index) }}
          </p>

          <div class="flex flex-wrap items-center gap-2">
            <p :class="titleClass(step.status)">{{ step.title }}</p>

            <span v-if="showStatusBadge" :class="badgeClass(step.status)">
              <Lucide v-if="step.status === 'completed'" icon="Check" class="h-3 w-3" />
              <span v-else-if="step.status === 'active'" class="h-1.5 w-1.5 rounded-full bg-current" />
              {{ getStatusText(step) }}
            </span>
          </div>

          <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
            {{ step.description }}
          </p>
        </div>

        <span v-if="step.timestamp" :class="twMerge('shrink-0 whitespace-nowrap pt-0.5 text-slate-400', sz.descText)">
          {{ step.timestamp }}
        </span>
      </div>
    </div>
  </div>

  <!-- Horizontal Stepper -->
  <div v-else class="relative flex w-full justify-between">
    <!-- Background track -->
    <div class="absolute z-0 h-0.5 bg-slate-200"
      :style="{ top: `${halfCircleH}px`, left: `${halfCircleH * 2}px`, right: `${halfCircleH}px` }" />

    <!-- Progress -->
    <div class="absolute z-0 h-0.5 bg-success transition-all duration-500" :style="{
      top: `${halfCircleH}px`,
      left: `${halfCircleH * 2}px`,
      width: progressWidth,
    }" />

    <div v-for="(step, index) in steps" :key="index" class="relative z-10 flex flex-col items-center">
      <div :class="circleClass(step.status, sz.iconWrapH)">
        <span v-if="step.status === 'active'" class="step-arc text-success" aria-hidden="true" />
        <Lucide v-if="step.icon" :icon="step.icon" :class="sz.iconInner" />
        <Lucide v-else-if="step.status === 'completed'" icon="Check" :class="sz.iconInner" />
        <span v-else-if="step.status === 'active'" :class="twMerge('rounded-full bg-success animate-pulse', sz.dot)" />
        <span v-else :class="twMerge('font-barlow font-bold', sz.numText)">{{ index + 1 }}</span>
      </div>

      <div class="mt-2 text-center">
        <p v-if="showLabel" :class="twMerge('font-barlow font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
          {{ getStepLabel(step, index) }}
        </p>
        <p :class="titleClass(step.status)">{{ step.title }}</p>
        <p v-if="step.description" :class="twMerge('mt-0.5 text-slate-500', sz.descText)">
          {{ step.description }}
        </p>
        <span v-if="step.timestamp" :class="twMerge('mt-0.5 block text-slate-400', sz.descText)">
          {{ step.timestamp }}
        </span>
        <span v-if="showStatusBadge" :class="twMerge('mt-1.5', badgeClass(step.status))">
          <Lucide v-if="step.status === 'completed'" icon="Check" class="h-3 w-3" />
          <span v-else-if="step.status === 'active'" class="h-1.5 w-1.5 rounded-full bg-current" />
          {{ getStatusText(step) }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Ring parsial (busur ~70%) untuk step aktif — kesan "sedang berlangsung".
   conic-gradient mewarnai busur, mask radial menyisakannya jadi cincin tipis. */
.step-arc {
  position: absolute;
  inset: -4px;
  border-radius: 9999px;
  background: conic-gradient(currentColor 0deg 250deg, transparent 250deg 360deg);
  -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2.5px));
  mask: radial-gradient(farthest-side, transparent calc(100% - 2.5px), #000 calc(100% - 2.5px));
  animation: step-arc-spin 1.4s linear infinite;
}

@keyframes step-arc-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .step-arc {
    animation: none;
  }
}
</style>
