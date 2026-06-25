<script setup lang="ts">
import { type RouterLinkProps } from "vue-router";
import { computed, type LiHTMLAttributes, inject } from "vue";
import { type ProvideBeradcrumb } from "./Breadcrumb.vue";

interface LinkProps extends /* @vue-ignore */ LiHTMLAttributes {
  to?: RouterLinkProps["to"];
  active?: boolean;
  disabled?: boolean;
}

const { to = "", active = false, disabled = false } = defineProps<LinkProps>();

const breadcrumb = inject<ProvideBeradcrumb>("breadcrumb");

const computedClass = computed(() => [
  breadcrumb &&
    !breadcrumb.light &&
    active &&
    "text-slate-800 cursor-text dark:text-slate-400",
  breadcrumb && breadcrumb.light && active && "text-white/70",
]);
</script>

<template>
  <li :class="computedClass">
    <span v-if="disabled">
      <slot></slot>
    </span>
    <RouterLink v-else :to="to">
      <slot></slot>
    </RouterLink>
  </li>
</template>
