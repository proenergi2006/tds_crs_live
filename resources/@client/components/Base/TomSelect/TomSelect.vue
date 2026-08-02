<script setup lang="ts">
import "@/assets/css/vendors/tom-select.css";
import _ from "lodash";
import { setValue, init, updateValue } from "./tom-select";
import {
  type TomSettings,
  type RecursivePartial,
} from "tom-select/src/types/index";
import TomSelectPlugin from "tom-select";
import {
  computed,
  type SelectHTMLAttributes,
  onMounted,
  inject,
  ref,
} from "vue";

export interface TomSelectElement extends HTMLSelectElement {
  TomSelect: TomSelectPlugin;
}

export interface TomSelectProps extends /* @vue-ignore */ SelectHTMLAttributes {
  modelValue: string | string[];
  options?: RecursivePartial<TomSettings>;
  refKey?: string;
}

export interface TomSelectEmit {
  (e: "update:modelValue", value: string | string[]): void;
  (e: "optionAdd", value: string | number): void;
}

export type ProvideTomSelect = (el: TomSelectElement) => void;

const props = withDefaults(defineProps<TomSelectProps>(), {});

const emit = defineEmits<TomSelectEmit>();

const tomSelectRef = ref<TomSelectElement>();

const computedOptions = computed(() => {
  let options: TomSelectProps["options"] = {
    ...props.options,
    plugins: {
      dropdown_input: {},
      ...props.options?.plugins,
    },
  };

  if (Array.isArray(props.modelValue)) {
    options = {
      persist: false,
      create: true,
      onDelete: function (values: string[]) {
        return confirm(
          values.length > 1
            ? "Are you sure you want to remove these " +
                values.length +
                " items?"
            : 'Are you sure you want to remove "' + values[0] + '"?'
        );
      },
      ...options,
      plugins: {
        remove_button: {
          title: "Remove this item",
        },
        ...options.plugins,
      },
    };
  }

  return options;
});

// Simpan referensi clone lewat closure, bukan query ulang by data-id --
// dropdown TomSelect (dropdownParent: 'body') gak selalu ketemu lagi lewat
// selector pas cleanup, apalagi kalau ada leftover elemen basi. Kenapa
// cleanup-nya harus manual sama sekali, lihat penjelasan di unmounted().
let clonedElRef: TomSelectElement | undefined;

const vSelectDirective = {
  mounted(el: TomSelectElement) {
    // Clone the select element to prevent tom select remove the original element
    const clonedEl = el.cloneNode(true) as TomSelectElement;

    // Save initial classnames (dibaca lagi oleh updateValue() di tom-select.ts)
    const classNames = el?.getAttribute("class");
    classNames && clonedEl.setAttribute("data-initial-class", classNames);

    // Hide the original element. Clone goes in right after `el` via
    // insertAdjacentElement, not parentNode.appendChild -- appendChild always
    // lands at the end of the parent, so a sibling TomSelect that remounts
    // later via :key (a cascading region dropdown forced to remount by its
    // parent value, say) gets its clone pushed past siblings mounted
    // earlier. Every select after it visibly shifts by one grid slot.
    el?.insertAdjacentElement("afterend", clonedEl);
    el.setAttribute("hidden", "true");

    clonedElRef = clonedEl;

    setValue(clonedEl, props);
    init(el, clonedEl, props, computedOptions.value, emit);
  },
  updated(el: TomSelectElement) {
    if (!clonedElRef) return;
    const value = props.modelValue;
    updateValue(el, clonedElRef, value, props, computedOptions.value, emit);
  },
  unmounted() {
    // mounted() clones `el` and drops the clone in as a raw DOM sibling,
    // outside Vue's render tree, so tom-select.js can own it without Vue
    // fighting for control of `el`. Problem is, Vue only ever cleans up `el`
    // on unmount. The clone -- the actual visible/interactive element -- is
    // invisible to that cleanup, so it leaked as an orphaned node on every
    // unmount (:key-forced remount, ancestor v-if, etc), leaving a dead
    // TomSelect behind and, with dropdownParent: 'body', an orphaned
    // dropdown stuck on <body> too.
    clonedElRef?.TomSelect?.destroy();
    clonedElRef?.remove();
    clonedElRef = undefined;
  },
};

const bindInstance = (el: TomSelectElement) => {
  if (props.refKey) {
    const bind = inject<ProvideTomSelect>(`bind[${props.refKey}]`);
    if (bind) {
      bind(el);
    }
  }
};

onMounted(() => {
  if (tomSelectRef.value) {
    bindInstance(tomSelectRef.value);
  }
});
</script>

<template>
  <select
    ref="tomSelectRef"
    :value="props.modelValue"
    @change="
      (event) => {
        emit('update:modelValue', (event.target as HTMLSelectElement).value);
      }
    "
    v-select-directive
    class="tom-select"
  >
    <slot></slot>
  </select>
</template>
