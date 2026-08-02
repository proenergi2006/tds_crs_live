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
  showStatusBadge?: boolean;
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
      connectorGap: 'min-h-6',
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
      connectorGap: 'min-h-8',
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
      connectorGap: 'min-h-10',
      labelText: 'text-xs',
      titleText: 'text-base',
      descText: 'text-sm',
      badgePad: 'px-2.5 py-1',
      badgeText: 'text-xs',
    },
  } as const;

  return configs[props.size];
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
    'flex shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-300',
    wrapSize,
    status === 'completed' && 'border-success bg-success text-white step-pop',
    status === 'active' && 'relative border-success bg-white text-success step-pop',
    status === 'pending' && 'border-slate-200 bg-white text-slate-400',
  );
}

function titleClass(status: StepItem['status']): string {
  return twMerge(
    'font-barlow font-bold transition-colors duration-300',
    sz.value.titleText,
    status === 'completed' && 'text-slate-700',
    status === 'active' && 'text-slate-800',
    status === 'pending' && 'text-slate-400',
  );
}

function badgeClass(status: StepItem['status']): string {
  return twMerge(
    'inline-flex items-center gap-1 rounded font-barlow font-semibold uppercase tracking-wide transition-colors duration-300',
    sz.value.badgePad,
    sz.value.badgeText,
    status === 'completed' && 'bg-emerald-100 text-emerald-700',
    status === 'active' && 'bg-primary/10 text-success',
    status === 'pending' && 'bg-slate-100 text-slate-500',
  );
}

function isConnectorFilled(step: StepItem): boolean {
  return step.status === 'completed';
}
</script>

<template>
  <div v-if="direction === 'vertical'" class="flex flex-col">
    <div v-for="(step, index) in steps" :key="index" class="flex" :class="sz.gap">
      <div class="flex flex-col items-center">
        <div :class="circleClass(step.status, sz.iconWrapV)">
          <span v-if="step.status === 'active'" class="step-pulse-ring text-success" aria-hidden="true" />
          <Lucide v-if="step.icon" :icon="step.icon" :class="sz.iconInner" />
          <Lucide v-else-if="step.status === 'completed'" icon="CheckCheck" :class="sz.iconInner" />
          <Lucide v-else-if="step.status === 'active'" icon="Loader2" :class="sz.iconInner" />
          <span v-else :class="twMerge('font-barlow font-bold', sz.numText)">{{ index + 1 }}</span>
        </div>

        <div v-if="index < steps.length - 1" class="w-1.5 flex-1 transition-colors duration-500"
          :class="[sz.connectorGap, isConnectorFilled(step) ? 'bg-success' : 'bg-slate-200']" />
      </div>

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

  <div v-else class="flex w-full items-start">
    <div v-for="(step, index) in steps" :key="index" class="flex flex-1 min-w-0 flex-col items-center">
      <div class="flex w-full items-center">
        <!-- left half: represents the connector coming FROM the previous step -->
        <div v-if="index > 0" class="h-0.5 flex-1 transition-colors duration-500"
          :class="isConnectorFilled(steps[index - 1]) ? 'bg-success' : 'bg-slate-200'" />
        <div v-else class="flex-1" />

        <div :class="circleClass(step.status, sz.iconWrapH)">
          <span v-if="step.status === 'active'" class="step-pulse-ring text-success" aria-hidden="true" />
          <Lucide v-if="step.icon" :icon="step.icon" :class="sz.iconInner" />
          <Lucide v-else-if="step.status === 'completed'" icon="Check" :class="sz.iconInner" />
          <span v-else-if="step.status === 'active'" :class="twMerge('rounded-full bg-success', sz.dot)" />
          <span v-else :class="twMerge('font-barlow font-bold', sz.numText)">{{ index + 1 }}</span>
        </div>

        <!-- right half: represents the connector going TO the next step -->
        <div v-if="index < steps.length - 1" class="h-0.5 flex-1 transition-colors duration-500"
          :class="isConnectorFilled(step) ? 'bg-success' : 'bg-slate-200'" />
        <div v-else class="flex-1" />
      </div>

      <div class="mt-2 text-center">
        <p v-if="showLabel"
          :class="twMerge('font-barlow font-bold uppercase tracking-widest text-slate-400', sz.labelText)">
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
/* Cincin putus-putus buat step aktif -- kesan "lagi berlangsung" tanpa
   animasi berputar terus (spinner versi sebelumnya kerasa ganggu). Efek
   "napas": ring membesar dikit sambil memudar, terus balik lagi. */
.step-pulse-ring {
  position: absolute;
  inset: -4px;
  border-radius: 9999px;
  border: 2px dashed currentColor;
  animation: step-pulse 1.8s ease-in-out infinite;
}

@keyframes step-pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.9;
  }
  50% {
    transform: scale(1.12);
    opacity: 0.35;
  }
}

/* Pop sekali pas circle-nya baru pindah status (pending → active / active →
   completed). Class ini baru muncul di DOM tepat saat status berubah, jadi
   animasinya otomatis ke-trigger ulang tiap kali circleClass() ngehasilin
   string yang beda. */
.step-pop {
  animation: step-pop 0.35s ease-out;
}

@keyframes step-pop {
  0% {
    transform: scale(0.85);
  }
  60% {
    transform: scale(1.08);
  }
  100% {
    transform: scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .step-pulse-ring,
  .step-pop {
    animation: none;
  }
}
</style>
