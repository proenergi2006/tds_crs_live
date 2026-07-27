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

// Compute all default options
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

// Referensi clone dipegang langsung lewat closure (bukan di-query ulang dari
// DOM via data-id) supaya cleanup di unmounted() tidak bergantung pada elemen
// masih bisa ditemukan lewat selector saat itu. Query-based lookup terbukti
// tidak reliable untuk clonedEl.TomSelect.dropdown yang di-append ke <body>
// (dropdownParent: 'body') -- dropdown itu hidup di luar subtree yang di-unmount
// Vue, jadi satu-satunya jalan cleanup-nya adalah lewat destroy() di sini, dan
// itu butuh referensi clone yang pasti benar, bukan hasil query yang bisa gagal
// match / kena elemen basi kalau ada leftover.
let clonedElRef: TomSelectElement | undefined;

const vSelectDirective = {
  mounted(el: TomSelectElement) {
    // Clone the select element to prevent tom select remove the original element
    const clonedEl = el.cloneNode(true) as TomSelectElement;

    // Save initial classnames (dibaca lagi oleh updateValue() di tom-select.ts)
    const classNames = el?.getAttribute("class");
    classNames && clonedEl.setAttribute("data-initial-class", classNames);

    // Hide original element
    el?.parentNode && el?.parentNode.appendChild(clonedEl);
    el.setAttribute("hidden", "true");

    clonedElRef = clonedEl;

    // Initialize tom select
    setValue(clonedEl, props);
    init(el, clonedEl, props, computedOptions.value, emit);
  },
  updated(el: TomSelectElement) {
    if (!clonedElRef) return;
    const value = props.modelValue;
    updateValue(el, clonedElRef, value, props, computedOptions.value, emit);
  },
  unmounted() {
    // `mounted()` clones `el` and appends the clone as a raw DOM sibling
    // (outside Vue's render tree) so tom-select.js can take over that clone
    // without Vue fighting it for control of `el` itself. Vue only knows how
    // to clean up `el` on unmount — the manually-appended clone (the thing
    // actually visible/interactive to the user) is invisible to Vue's own
    // unmount cleanup and was leaking as an orphaned node whenever this
    // component unmounts (e.g. via :key-forced remount, or an ancestor v-if),
    // leaving a dead TomSelect (and, with dropdownParent: 'body', an orphaned
    // dropdown stuck on <body>) behind.
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
