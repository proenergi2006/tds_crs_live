<script setup lang="ts">
type BadgeVariant =
  | "soft-primary"
  | "soft-secondary"
  | "soft-success"
  | "soft-info"
  | "soft-warning"
  | "soft-pending"
  | "soft-danger"
  | "soft-dark"
  | "dark";

const props = withDefaults(
  defineProps<{
    variant?: BadgeVariant;
  }>(),
  {
    variant: "soft-secondary",
  },
);

const VARIANT_CLASSES: Record<BadgeVariant, string> = {
  "soft-primary": "bg-primary/10 text-primary dark:bg-primary/10",
  "soft-secondary": "bg-slate-100 text-slate-500 dark:bg-darkmode-100/20 dark:text-slate-300",
  "soft-success": "bg-success/10 text-success dark:bg-success/10",
  "soft-info": "bg-info/10 text-info dark:bg-info/10",
  "soft-warning": "bg-warning/10 text-warning dark:bg-warning/10",
  "soft-pending": "bg-pending/10 text-pending dark:bg-pending/10",
  "soft-danger": "bg-danger/10 text-danger dark:bg-danger/10",
  "soft-dark": "bg-slate-100 text-slate-700 dark:bg-darkmode-800/30 dark:text-slate-300",
  dark: "bg-dark text-white dark:bg-darkmode-800",
};
</script>

<template>
  <span class="inline-flex px-3 py-1 rounded-full text-form-label" :class="VARIANT_CLASSES[props.variant]">
    <slot />
  </span>
</template>
