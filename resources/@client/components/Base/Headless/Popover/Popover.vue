<script lang="ts">
export default {
  inheritAttrs: false,
};

export interface PopoverProps
  extends /* @vue-ignore */ ExtractProps<typeof HeadlessPopover> {
  as?: string | object;
}
</script>

<script setup lang="ts">
import { computed, provide, ref, useAttrs } from "vue";
import _ from "lodash";
import { twMerge } from "tailwind-merge";
import { Popover as HeadlessPopover } from "@headlessui/vue";
import { PopoverTriggerKey } from "./context";

const { as = "div" } = defineProps<PopoverProps>();

const attrs = useAttrs();
const computedClass = computed(() =>
  twMerge(["relative", typeof attrs.class === "string" && attrs.class])
);

const triggerElRef = ref<HTMLElement | null>(null);
provide(PopoverTriggerKey, triggerElRef);
</script>

<template>
  <HeadlessPopover
    :is="as"
    :class="computedClass"
    v-bind="_.omit(attrs, 'class')"
    v-slot="{ close }"
  >
    <slot :close="close"></slot>
  </HeadlessPopover>
</template>
