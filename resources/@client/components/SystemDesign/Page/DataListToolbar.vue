<script setup lang="ts">
import { FormInput, FormSelect } from "@/components/Base/Form";
import Popover from "@/components/Base/Headless/Popover";
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
    embedded?: boolean;
    showFilters?: boolean;
  }>(),
  {
    searchPlaceholder: "Search...",
    searchLabel: "Cari",
    perPageLabel: "Per Page",
    paginationLabel: "Halaman",
    activeFilterCount: 0,
    embedded: false,
    showFilters: true,
  },
);

defineEmits<{
  (e: "update:search", value: string): void;
  (e: "update:perPage", value: number): void;
  (e: "page-change", page: number): void;
}>();

const searchInputId = "page-toolbar-search";
const perPageSelectId = "page-toolbar-per-page";
const paginationLabelId = "page-toolbar-pagination";
</script>

<template>
  <div :class="embedded ? '' : 'mt-5 intro-y'">
    <div :class="embedded
      ? ''
      : [
        'relative mt-5 intro-y',
        'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
      ]">
      <div :class="embedded ? '' : 'mb-6 rounded-lg box p-4'">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="w-full sm:w-80">
              <FormInput :id="searchInputId" :model-value="search" :placeholder="searchPlaceholder"
                :aria-label="searchLabel" class="w-full pr-10 !box"
                @update:model-value="$emit('update:search', String($event))">
                <template #icon>
                  <Lucide icon="Search" class="w-4 h-4" />
                </template>
              </FormInput>
            </div>

            <Popover v-if="showFilters && $slots.filters" class="inline-block" v-slot="{ close }">
              <Popover.Button as="button" type="button"
                class="inline-flex h-[38px] items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none">
                <Lucide icon="SlidersHorizontal" class="h-4 w-4" />
                Filter
                <span v-if="activeFilterCount > 0"
                  class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-semibold text-white">
                  {{ activeFilterCount }}
                </span>
                <Lucide icon="ChevronDown" class="h-4 w-4" />
              </Popover.Button>

              <Popover.Panel placement="bottom-start"
                class="z-50 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                <slot name="filters" :close="close" />
              </Popover.Panel>
            </Popover>
          </div>

          <div class="flex items-end justify-between gap-3 sm:justify-end">
            <div class="flex items-center">
              <FormSelect :id="perPageSelectId" :model-value="perPage" class="w-16 !box" @update:model-value="
                $emit('update:perPage', Number($event))
                " :aria-label="perPageLabel">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </FormSelect>
            </div>

            <div class="flex items-center">
              <div class="flex h-[38px] items-center gap-2" :aria-labelledby="paginationLabelId">
                <span :id="paginationLabelId" class="sr-only">
                  {{ paginationLabel }}
                </span>

                <button type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                  :disabled="currentPage <= 1" @click="$emit('page-change', currentPage - 1)">
                  <Lucide icon="ChevronLeft" class="h-4 w-4" />
                </button>

                <div class="min-w-[48px] text-center text-sm text-slate-600">
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

      </div>
    </div>
  </div>
</template>
