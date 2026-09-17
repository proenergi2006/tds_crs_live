<script lang="ts">
export default {
  inheritAttrs: false,
};

export interface ButtonProps
  extends /* @vue-ignore */ ExtractProps<typeof HeadlessMenuButton> {
  as?: string | object;
}
</script>

<script setup lang="ts">
import { type ComponentPublicInstance, computed, inject, ref, useAttrs, watchEffect } from "vue";
import _ from "lodash";
import { twMerge } from "tailwind-merge";
import { MenuButton as HeadlessMenuButton } from "@headlessui/vue";
import { MenuTriggerKey } from "./context";

const { as = "div" } = defineProps<ButtonProps>();

const attrs = useAttrs();
const computedClass = computed(() =>
  twMerge(["cursor-pointer", typeof attrs.class === "string" && attrs.class])
);

const triggerElRef = inject(MenuTriggerKey);
const rootEl = ref<HTMLElement | ComponentPublicInstance | null>(null);

watchEffect(() => {
  if (!triggerElRef) return;
  const el = rootEl.value;
  triggerElRef.value = (el as ComponentPublicInstance)?.$el ?? (el as HTMLElement) ?? null;
});
</script>

<template>
  <HeadlessMenuButton as="template">
    <component :is="as" ref="rootEl" :class="computedClass" v-bind="_.omit(attrs, 'class')">
      <slot></slot
    ></component>
  </HeadlessMenuButton>
</template>
