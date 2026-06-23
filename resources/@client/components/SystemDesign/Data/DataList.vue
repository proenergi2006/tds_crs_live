<script setup lang="ts">
import { computed } from 'vue';

import Table from '@/components/Base/Table';
import Lucide from '@/components/Base/Lucide';
import LoadingIcon from '@/components/Base/LoadingIcon';
import DataListToolbar from '@/components/SystemDesign/Page/DataListToolbar.vue';

const props = withDefaults(
  defineProps<{
    loading?: boolean;
    empty?: boolean;
    colspan: number;
    loadingText?: string;
    emptyTitle?: string;
    emptyDescription?: string;
    total?: number;
    currentPage?: number;
    perPage?: number;
    showFooter?: boolean;
    showToolbar?: boolean;
    search?: string;
    totalPages?: number;
    searchPlaceholder?: string;
    searchLabel?: string;
    perPageLabel?: string;
    paginationLabel?: string;
    activeFilterCount?: number;
  }>(),
  {
    loading: true,
    empty: false,
    loadingText: 'Memuat data...',
    emptyTitle: 'Data tidak ditemukan',
    emptyDescription: 'Belum ada data untuk ditampilkan.',
    total: 0,
    currentPage: 1,
    perPage: 10,
    showToolbar: false,
    search: '',
    totalPages: 1,
    searchPlaceholder: 'Search...',
    searchLabel: 'Cari',
    perPageLabel: 'Per Page',
    paginationLabel: 'Halaman',
    activeFilterCount: 0,
  },
);

defineEmits<{
  (e: 'update:search', value: string): void;
  (e: 'update:perPage', value: number): void;
  (e: 'page-change', page: number): void;
}>();

const startRecord = computed(() => {
  if (!props.total) return 0;

  return (props.currentPage - 1) * props.perPage + 1;
});

const endRecord = computed(() => {
  if (!props.total) return 0;

  return Math.min(
    props.currentPage * props.perPage,
    props.total,
  );
});
</script>

<template>
  <div class="overflow-visible rounded-xl border border-slate-200 bg-white shadow-sm">
    <div v-if="showToolbar" class="rounded-t-xl border-b border-slate-200 bg-white p-4">
      <DataListToolbar :search="search" :per-page="perPage" :current-page="currentPage" :total-pages="totalPages"
        :search-placeholder="searchPlaceholder" :search-label="searchLabel" :per-page-label="perPageLabel"
        :pagination-label="paginationLabel" :active-filter-count="activeFilterCount"
        :show-filters="$slots.filters ? true : false" embedded @update:search="$emit('update:search', $event)"
        @update:per-page="$emit('update:perPage', $event)" @page-change="$emit('page-change', $event)">
        <template #filters="{ close }">
          <slot name="filters" :close="close" />
        </template>
      </DataListToolbar>
    </div>

    <div v-else-if="$slots.toolbar" class="rounded-t-xl border-b border-slate-200 bg-white p-4">
      <slot name="toolbar" />
    </div>

    <div class="overflow-x-auto" :class="showFooter && total > 0 ? '' : 'rounded-b-xl'">
      <Table bordered class="min-w-full border-collapse">
        <Table.Thead class="bg-slate-50 font-label">
          <Table.Tr>
            <slot name="head" />
          </Table.Tr>
        </Table.Thead>

        <Table.Tbody>
          <Table.Tr v-if="loading">
            <Table.Td :colspan="colspan" class="py-14 text-center">
              <slot name="loading">
                <div class="flex flex-col items-center gap-3">
                  <LoadingIcon icon="three-dots" class="w-8 h-8" />
                  <span class="font-body">{{ loadingText }}</span>
                </div>
              </slot>
            </Table.Td>
          </Table.Tr>

          <Table.Tr v-else-if="empty">
            <Table.Td :colspan="colspan" class="py-14 text-center">
              <slot name="empty">
                <div class="flex flex-col items-center gap-3">
                  <div class="flex h-14 w-14 items-center justify-center rounded-full">
                    <Lucide icon="Inbox" class="h-7 w-7" />
                  </div>

                  <div class="font-strong">
                    {{ emptyTitle }}
                  </div>

                  <div class="font-body">
                    {{ emptyDescription }}
                  </div>
                </div>
              </slot>
            </Table.Td>
          </Table.Tr>

          <slot v-else name="body" />
        </Table.Tbody>
      </Table>
    </div>

    <div v-if="showFooter && total > 0"
      class="font-body rounded-b-xl border-t border-slate-200 bg-slate-50 px-4 py-3">
      Menampilkan
      <span class="font-num">
        {{ startRecord }}
      </span>
      -
      <span class="font-num">
        {{ endRecord }}
      </span>
      dari total
      <span class="font-num">
        {{ total }}
      </span>
      data
    </div>
  </div>
</template>
