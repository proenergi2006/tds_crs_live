import type { InjectionKey, Ref } from "vue";

export const MenuTriggerKey: InjectionKey<Ref<HTMLElement | null>> = Symbol("MenuTriggerKey");
