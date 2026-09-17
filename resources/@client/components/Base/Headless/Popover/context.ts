import type { InjectionKey, Ref } from "vue";

export const PopoverTriggerKey: InjectionKey<Ref<HTMLElement | null>> = Symbol("PopoverTriggerKey");
