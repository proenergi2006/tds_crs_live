<script lang="ts">
export default {
  inheritAttrs: false,
};

export interface ButtonProps
  extends /* @vue-ignore */ ExtractProps<typeof HeadlessPopoverButton> {
  as?: string | object;
}
</script>

<script setup lang="ts">
import { type ComponentPublicInstance, computed, inject, ref, useAttrs, watchEffect } from "vue";
import _ from "lodash";
import { twMerge } from "tailwind-merge";
import { PopoverButton as HeadlessPopoverButton } from "@headlessui/vue";
import { PopoverTriggerKey } from "./context";

const { as = "div" } = defineProps<ButtonProps>();

const attrs = useAttrs();
const computedClass = computed(() =>
  twMerge(["cursor-pointer", typeof attrs.class === "string" && attrs.class])
);

const triggerElRef = inject(PopoverTriggerKey);
const rootEl = ref<HTMLElement | ComponentPublicInstance | null>(null);

watchEffect(() => {
  if (!triggerElRef) return;
  const el = rootEl.value;
  triggerElRef.value = (el as ComponentPublicInstance)?.$el ?? (el as HTMLElement) ?? null;
});
</script>

<template>
  <HeadlessPopoverButton
    ref="rootEl"
    :is="as"
    :class="computedClass"
    v-bind="_.omit(attrs, 'class')"
  >
    <slot></slot>
  </HeadlessPopoverButton>
</template>
