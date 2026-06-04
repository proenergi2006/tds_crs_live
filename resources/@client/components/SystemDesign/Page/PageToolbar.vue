<script setup lang="ts">
import { ref } from "vue";

import { FormInput, FormSelect } from "@/components/Base/Form";
import Lucide from "@/components/Base/Lucide";

withDefaults(
  defineProps<{
    search: string;
    perPage: number;
    currentPage: number;
    totalPages: number;
    searchPlaceholder?: string;
    activeFilterCount?: number;
  }>(),
  {
    searchPlaceholder: "Search...",
    activeFilterCount: 0,
  },
);

defineEmits<{
  (e: "update:search", value: string): void;
  (e: "update:perPage", value: number): void;
  (e: "page-change", page: number): void;
}>();

const showFilters = ref(false);
</script>

<template>
  <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <FormInput :model-value="search" :placeholder="searchPlaceholder" class="w-full sm:w-80 pr-10 !box"
          @update:model-value="$emit('update:search', String($event))">
          <template #icon>
            <Lucide icon="Search" class="w-4 h-4" />
          </template>
        </FormInput>

        <button v-if="$slots.filters" type="button"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
          @click="showFilters = !showFilters">
          <Lucide icon="SlidersHorizontal" class="h-4 w-4" />
          Filter
          <span v-if="activeFilterCount > 0"
            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-semibold text-white">
            {{ activeFilterCount }}
          </span>
          <Lucide icon="ChevronDown" class="h-4 w-4 transition" :class="{ 'rotate-180': showFilters }" />
        </button>
      </div>

      <div class="flex items-center justify-between gap-3 sm:justify-end">
        <FormSelect :model-value="perPage" class="w-20 !box" @update:model-value="
          $emit('update:perPage', Number($event))
          ">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </FormSelect>

        <div class="flex items-center gap-2">
          <button type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage <= 1" @click="$emit('page-change', currentPage - 1)">
            <Lucide icon="ChevronLeft" class="h-4 w-4" />
          </button>

          <div class="min-w-[72px] text-center text-sm text-slate-600">
            <span class="font-semibold text-slate-800">
              {{ currentPage }}
            </span>
            /
            <span>{{ totalPages }}</span>
          </div>

          <button type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
            :disabled="currentPage >= totalPages" @click="$emit('page-change', currentPage + 1)">
            <Lucide icon="ChevronRight" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
      <div v-if="$slots.filters && showFilters" class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <slot name="filters" />
      </div>
    </Transition>
  </div>
</template>
