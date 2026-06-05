<script setup lang="ts">
import { type HTMLAttributes, provide } from "vue";

export type ProvideBeradcrumb = {
  light?: boolean;
};

interface BreadcrumbProps extends /* @vue-ignore */ HTMLAttributes {
  light?: boolean;
}

const { light } = defineProps<BreadcrumbProps>();

provide<ProvideBeradcrumb>("breadcrumb", {
  light: light,
});
</script>

<template>
  <nav class="flex" aria-label="breadcrumb">
    <ol
      :class="[
        'flex items-center text-primary dark:text-slate-300',
        '[&>li+li]:relative [&>li+li]:ml-5 [&>li+li]:pl-0.5',
        !light && '[&>li+li]:before:absolute [&>li+li]:before:inset-y-0 [&>li+li]:before:my-auto [&>li+li]:before:-ml-[1.125rem] [&>li+li]:before:h-[14px] [&>li+li]:before:w-[14px] [&>li+li]:before:rotate-[-90deg] [&>li+li]:before:bg-chevron-black [&>li+li]:before:bg-[length:100%] [&>li+li]:before:content-[\'\'] dark:[&>li+li]:before:bg-chevron-white',
        light && '[&>li+li]:before:absolute [&>li+li]:before:inset-y-0 [&>li+li]:before:my-auto [&>li+li]:before:-ml-[1.125rem] [&>li+li]:before:h-[14px] [&>li+li]:before:w-[14px] [&>li+li]:before:rotate-[-90deg] [&>li+li]:before:bg-chevron-white [&>li+li]:before:bg-[length:100%] [&>li+li]:before:content-[\'\']',
        { 'text-white/90': light },
      ]"
    >
      <slot />
    </ol>
  </nav>
</template>
