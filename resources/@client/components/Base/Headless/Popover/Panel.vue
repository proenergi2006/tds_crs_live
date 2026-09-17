<script lang="ts">
export default {
  inheritAttrs: false,
};

export interface PanelProps
  extends /* @vue-ignore */ ExtractProps<typeof HeadlessPopoverPanel> {
  as?: string | object;
  placement?:
    | "top-start"
    | "top"
    | "top-end"
    | "right-start"
    | "right"
    | "right-end"
    | "bottom-end"
    | "bottom"
    | "bottom-start"
    | "left-start"
    | "left"
    | "left-end";
}
</script>

<script setup lang="ts">
import { computed, inject, ref, useAttrs } from "vue";
import _ from "lodash";
import { twMerge } from "tailwind-merge";
import {
  PopoverPanel as HeadlessPopoverPanel,
  TransitionRoot,
} from "@headlessui/vue";
import { useFloatingPanel } from "@/composables/useFloatingPanel";
import { PopoverTriggerKey } from "./context";

const { as = "div", placement = "bottom-end" } = defineProps<PanelProps>();

const attrs = useAttrs();
const computedClass = computed(() =>
  twMerge([
    "p-2 shadow-[0px_3px_20px_#0000000b] bg-white border-transparent rounded-md dark:bg-darkmode-600 dark:border-transparent",
    typeof attrs.class === "string" && attrs.class,
  ])
);

const triggerElRef = inject(PopoverTriggerKey);
const panelElRef = ref<HTMLElement | null>(null);
const { floatingStyles, isPositioned } = useFloatingPanel(
  triggerElRef ?? ref(null),
  panelElRef,
  computed(() => placement),
);
</script>

<template>
  <Teleport to="body">
    <TransitionRoot
      as="template"
      enter="transition ease-linear duration-150"
      enterFrom="opacity-0 scale-95"
      enterTo="opacity-100 scale-100"
      leave="transition ease-linear duration-150"
      leaveFrom="opacity-100 scale-100"
      leaveTo="opacity-0 scale-95"
    >
      <div
        ref="panelElRef"
        class="z-[9999]"
        :style="[floatingStyles, !isPositioned ? { visibility: 'hidden' } : {}]"
      >
        <HeadlessPopoverPanel
          :as="as"
          :class="computedClass"
          v-bind="_.omit(attrs, 'class')"
        >
          <slot></slot>
        </HeadlessPopoverPanel>
      </div>
    </TransitionRoot>
  </Teleport>
</template>
