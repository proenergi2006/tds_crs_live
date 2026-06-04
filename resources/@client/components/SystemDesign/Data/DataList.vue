<script setup lang="ts">
import Table from '@/components/Base/Table';
import Lucide from '@/components/Base/Lucide';

withDefaults(
  defineProps<{
    loading?: boolean;
    empty?: boolean;
    colspan: number;
    loadingText?: string;
    emptyTitle?: string;
    emptyDescription?: string;
  }>(),
  {
    loading: false,
    empty: false,
    loadingText: 'Memuat data...',
    emptyTitle: 'Data tidak ditemukan',
    emptyDescription: 'Belum ada data untuk ditampilkan.',
  },
);
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <Table class="min-w-full">
        <Table.Thead class="bg-slate-50 uppercase">
          <Table.Tr>
            <slot name="head" />
          </Table.Tr>
        </Table.Thead>

        <Table.Tbody>
          <Table.Tr v-if="loading">
            <Table.Td :colspan="colspan" class="py-14 text-center">
              <slot name="loading">
                <div class="flex flex-col items-center gap-3 text-slate-500">
                  <Lucide icon="Loader" class="h-8 w-8 animate-spin" />
                  <span class="text-sm">{{ loadingText }}</span>
                </div>
              </slot>
            </Table.Td>
          </Table.Tr>

          <Table.Tr v-else-if="empty">
            <Table.Td :colspan="colspan" class="py-14 text-center">
              <slot name="empty">
                <div class="flex flex-col items-center gap-3 text-slate-500">
                  <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="Inbox" class="h-7 w-7" />
                  </div>

                  <div class="text-base font-medium text-slate-700">
                    {{ emptyTitle }}
                  </div>

                  <div class="text-sm">
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
  </div>
</template>