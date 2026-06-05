<script setup lang="ts">
import { ref } from "vue";

import { FormInput, FormLabel, FormSelect } from "@/components/Base/Form";
import Lucide from "@/components/Base/Lucide";

withDefaults(
  defineProps<{
    search: string;
    perPage: number;
    currentPage: number;
    totalPages: number;
    searchPlaceholder?: string;
    searchLabel?: string;
    perPageLabel?: string;
    paginationLabel?: string;
    activeFilterCount?: number;
  }>(),
  {
    searchPlaceholder: "Search...",
    searchLabel: "Cari",
    perPageLabel: "Per Page",
    paginationLabel: "Halaman",
    activeFilterCount: 0,
  },
);

defineEmits<{
  (e: "update:search", value: string): void;
  (e: "update:perPage", value: number): void;
  (e: "page-change", page: number): void;
}>();

const showFilters = ref(false);
const searchInputId = "page-toolbar-search";
const perPageSelectId = "page-toolbar-per-page";
const paginationLabelId = "page-toolbar-pagination";
</script>

<template>
  <div class="mt-5 intro-y">
    <div :class="[
      'relative mt-5 intro-y',
      'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
    ]">
      <div class="mb-6 rounded-2xl box p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="w-full sm:w-80">
              <FormLabel :for="searchInputId" class="mb-1 text-sm font-medium text-slate-600">
                {{ searchLabel }}
              </FormLabel>

              <FormInput :id="searchInputId" :model-value="search" :placeholder="searchPlaceholder"
                class="w-full pr-10 !box" @update:model-value="$emit('update:search', String($event))">
                <template #icon>
                  <Lucide icon="Search" class="w-4 h-4" />
                </template>
              </FormInput>
            </div>

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

          <div class="flex items-end justify-between gap-3 sm:justify-end">
            <div class="flex flex-col items-start">
              <FormLabel :for="perPageSelectId" class="mb-1 text-sm font-medium text-slate-600">
                {{ perPageLabel }}
              </FormLabel>

              <FormSelect :id="perPageSelectId" :model-value="perPage" class="w-28 !box" @update:model-value="
                $emit('update:perPage', Number($event))
                ">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </FormSelect>
            </div>

            <div class="flex flex-col items-start">
              <FormLabel :id="paginationLabelId" class="mb-1 text-sm font-medium text-slate-600">
                {{ paginationLabel }}
              </FormLabel>

              <div class="flex h-[38px] items-center gap-2" :aria-labelledby="paginationLabelId">
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
        </div>

        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
          <div v-if="$slots.filters && showFilters" class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <slot name="filters" />
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>
