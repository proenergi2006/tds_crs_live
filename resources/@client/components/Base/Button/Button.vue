<script lang="ts">
export default {
  inheritAttrs: false,
};

type Variant =
  | "primary"
  | "secondary"
  | "success"
  | "warning"
  | "danger"
  | "dark"
  | "white"
  | "info"
  | "outline-primary"
  | "outline-secondary"
  | "outline-success"
  | "outline-warning"
  | "outline-danger"
  | "outline-dark"
  | "outline-white"
  | "outline-info"
  | "soft-primary"
  | "soft-secondary"
  | "soft-success"
  | "soft-warning"
  | "soft-danger"
  | "soft-dark"
  | "soft-white"
  | "soft-info";

type Elevated = boolean;
type Size = "sm" | "lg";
type Rounded = boolean;

interface ButtonProps extends /* @vue-ignore */ ButtonHTMLAttributes {
  as?: string | object;
  variant?: Variant;
  elevated?: Elevated;
  size?: Size;
  rounded?: Rounded;
}
</script>

<script setup lang="ts">
import _ from "lodash";
import { twMerge } from "@/utils/tw-merge";
import { computed, type ButtonHTMLAttributes, useAttrs } from "vue";

const {
  as = "button",
  size,
  variant,
  elevated,
  rounded,
} = defineProps<ButtonProps>();

const attrs = useAttrs();

const generalStyles = [
  "transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer",
  "focus:ring-4 focus:ring-primary focus:ring-opacity-20",
  "focus-visible:outline-none",
  "dark:focus:ring-slate-700 dark:focus:ring-opacity-50",
  "[&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90",
  "[&:not(button)]:text-center",
  "disabled:opacity-70 disabled:cursor-not-allowed",
];

const small = ["text-xs py-1.5 px-2"];
const large = ["text-lg py-1.5 px-4"];

const primary = [
  "bg-primary border-primary text-white dark:border-primary",
];
const secondary = [
  "bg-secondary/70 border-secondary/70 text-slate-500",
  "dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100",
  "[&:hover:not(:disabled)]:dark:border-darkmode-300/80 [&:hover:not(:disabled)]:dark:bg-darkmode-300/80",
];
const success = [
  "bg-success border-success text-white",
  "dark:border-success",
];
const warning = [
  "bg-warning border-warning text-slate-900",
  "dark:border-warning",
];
const danger = [
  "bg-danger border-danger text-white",
  "dark:border-danger",
];
const dark = [
  "bg-dark border-dark text-white",
  "dark:bg-darkmode-800 dark:border-transparent dark:text-slate-300",
  "[&:hover:not(:disabled)]:dark:dark:bg-darkmode-800/70",
];
const white = [
  "bg-white border-0 text-slate-800",
  "dark:bg-darkmode-100/20 dark:border-darkmode-100/30 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-slate-100",
  "[&:hover:not(:disabled)]:dark:bg-darkmode-100/10",
];
const info = [
  "bg-info border-info text-white",
  "dark:border-info",
];

const outlinePrimary = [
  "border-primary text-primary",
  "dark:border-primary",
  "[&:hover:not(:disabled)]:bg-primary/10",
];
const outlineSecondary = [
  "border-secondary text-slate-500",
  "dark:border-darkmode-100/40 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-secondary/20",
  "[&:hover:not(:disabled)]:dark:bg-darkmode-100/10",
];
const outlineSuccess = [
  "border-success text-success",
  "dark:border-success",
  "[&:hover:not(:disabled)]:bg-success/10",
];
const outlineWarning = [
  "border-warning text-warning",
  "dark:border-warning",
  "[&:hover:not(:disabled)]:bg-warning/10",
];
const outlineDanger = [
  "border-danger text-danger",
  "dark:border-danger",
  "[&:hover:not(:disabled)]:bg-danger/10",
];
const outlineDark = [
  "border-dark text-dark",
  "dark:border-darkmode-800 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-darkmode-800/30",
  "[&:hover:not(:disabled)]:dark:bg-opacity-30",
];
const outlineWhite = [
  "border-white text-white",
  "[&:hover:not(:disabled)]:bg-white/10",
];
const outlineInfo = [
  "border-info text-info",
  "dark:border-info",
  "[&:hover:not(:disabled)]:bg-info/10",
];

const softPrimary = [
  "bg-primary/10 border-primary/20 text-primary",
  "dark:bg-primary/10 dark:border-primary/30",
  "[&:hover:not(:disabled)]:bg-primary/25",
];
const softSecondary = [
  "bg-slate-100 border-slate-300 text-slate-500",
  "dark:bg-darkmode-100/20 dark:border-darkmode-100/30 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-slate-200",
  "[&:hover:not(:disabled)]:dark:bg-darkmode-100/10",
];
const softSuccess = [
  "bg-success/10 border-success/20 text-success",
  "dark:bg-success/10 dark:border-success/30",
  "[&:hover:not(:disabled)]:bg-success/25",
];
const softWarning = [
  "bg-warning/10 border-warning/20 text-warning",
  "dark:bg-warning/10 dark:border-warning/30",
  "[&:hover:not(:disabled)]:bg-warning/25",
];
const softDanger = [
  "bg-danger/10 border-danger/20 text-danger",
  "dark:bg-danger/10 dark:border-danger/30",
  "[&:hover:not(:disabled)]:bg-danger/25",
];
const softDark = [
  "bg-slate-100 border-slate-300 text-slate-700",
  "dark:bg-darkmode-800/30 dark:border-darkmode-800/60 dark:text-slate-300",
  "[&:hover:not(:disabled)]:bg-slate-200",
  "[&:hover:not(:disabled)]:dark:bg-darkmode-800/50",
];
const softWhite = [
  "bg-white/10 border-white/20 text-white",
  "[&:hover:not(:disabled)]:bg-white/25",
];
const softInfo = [
  "bg-info/10 border-info/20 text-info",
  "dark:bg-info/10 dark:border-info/30",
  "[&:hover:not(:disabled)]:bg-info/25",
];

const computedClass = computed(() =>
  twMerge([
    generalStyles,
    size == "sm" && small,
    size == "lg" && large,
    variant == "primary" && primary,
    variant == "secondary" && secondary,
    variant == "success" && success,
    variant == "warning" && warning,
    variant == "danger" && danger,
    variant == "dark" && dark,
    variant == "white" && white,
    variant == "info" && info,
    variant == "outline-primary" && outlinePrimary,
    variant == "outline-secondary" && outlineSecondary,
    variant == "outline-success" && outlineSuccess,
    variant == "outline-warning" && outlineWarning,
    variant == "outline-danger" && outlineDanger,
    variant == "outline-dark" && outlineDark,
    variant == "outline-white" && outlineWhite,
    variant == "outline-info" && outlineInfo,
    variant == "soft-primary" && softPrimary,
    variant == "soft-secondary" && softSecondary,
    variant == "soft-success" && softSuccess,
    variant == "soft-warning" && softWarning,
    variant == "soft-danger" && softDanger,
    variant == "soft-dark" && softDark,
    variant == "soft-white" && softWhite,
    variant == "soft-info" && softInfo,
    rounded && "rounded-full",
    elevated && "shadow-md",
    typeof attrs.class === "string" && attrs.class,
  ])
);
</script>

<template>
  <component :is="as" :class="computedClass" v-bind="_.omit(attrs, 'class')">
    <slot></slot>
  </component>
</template>
