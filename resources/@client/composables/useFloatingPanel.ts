import { type Ref, type ComputedRef } from "vue";
import { useFloating, offset, flip, shift, autoUpdate, type Placement } from "@floating-ui/vue";

export function useFloatingPanel(
  triggerEl: Ref<HTMLElement | null>,
  panelEl: Ref<HTMLElement | null>,
  placement: ComputedRef<Placement> | Ref<Placement>,
) {
  return useFloating(triggerEl, panelEl, {
    placement,
    middleware: [offset(4), flip(), shift({ padding: 8 })],
    whileElementsMounted: autoUpdate,
    transform: false,
  });
}
