<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { debounce } from "lodash";

import Button from "@/components/Base/Button";
import Table from "@/components/Base/Table";
import Lucide from "@/components/Base/Lucide";
import TomSelect from "@/components/Base/TomSelect";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DeleteRecordDialog from "@/components/SystemDesign/Dialog/DeleteRecordDialog.vue";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";
import { formatNumber } from "@/utils/format";

import VesselFormModal from "./Form.vue";
import type { Vessel } from "./types";
import type { FormMode } from "../types";

const vesselApi = createResourceApi("/vessels");
const { success, error } = useNotification();

const table = {
  allRows: ref<Vessel[]>([]),
  searchQuery: ref(""),
  perPage: ref(10),
  currentPage: ref(1),
  loading: ref(false),
};

const filters = {
  transporterId: ref(""),
};

const formModal = {
  isOpen: ref(false),
  mode: ref<FormMode>("create"),
  selectedItem: ref<Vessel | null>(null),
};

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
  targetId: ref<number | null>(null),
};

const transporterOptions = computed(() => {
  const options = new Map<string, string>();

  table.allRows.value.forEach((item) => {
    if (item.transporter) {
      options.set(String(item.transporter.id), item.transporter.company_name);
    }
  });

  return Array.from(options.entries()).map(([id, name]) => ({ id, name }));
});

const activeFilterCount = computed(() => (filters.transporterId.value ? 1 : 0));

const filteredRows = computed(() => {
  const query = table.searchQuery.value.trim().toLowerCase();

  return table.allRows.value.filter((item) => {
    const matchesTransporter =
      !filters.transporterId.value || String(item.transporter?.id ?? "") === filters.transporterId.value;
    const matchesSearch =
      !query ||
      [item.name, item.type, item.transporter?.company_name].some((value) => String(value ?? "").toLowerCase().includes(query));

    return matchesTransporter && matchesSearch;
  });
});

const totalRecords = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / table.perPage.value)));

const paginatedRows = computed(() => {
  const start = (table.currentPage.value - 1) * table.perPage.value;
  return filteredRows.value.slice(start, start + table.perPage.value);
});

watch([table.searchQuery, filters.transporterId], debounce(resetToFirstPage, 300));
watch(table.perPage, resetToFirstPage);

onMounted(() => {
  fetchData();
});

async function fetchData() {
  table.loading.value = true;

  try {
    const { data } = await vesselApi.getAll({ as_list: true });
    table.allRows.value = data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data vessel");
  } finally {
    table.loading.value = false;
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return;
  table.currentPage.value = page;
}

function resetToFirstPage() {
  table.currentPage.value = 1;
}

function setFilterTransporter(value: string) {
  filters.transporterId.value = value;
}

function resetFilters() {
  filters.transporterId.value = "";
}

function openCreate() {
  formModal.mode.value = "create";
  formModal.selectedItem.value = null;
  formModal.isOpen.value = true;
}

function openEdit(item: Vessel) {
  formModal.mode.value = "edit";
  formModal.selectedItem.value = item;
  formModal.isOpen.value = true;
}

function handleFormSuccess(data: Vessel, mode: FormMode) {
  syncRow(data, mode);
  formModal.isOpen.value = false;
}

function syncRow(data: Vessel, mode: FormMode) {
  if (mode === "create") {
    table.allRows.value.unshift(data);
    return;
  }

  const index = table.allRows.value.findIndex((item) => item.id === data.id);
  if (index !== -1) table.allRows.value[index] = data;
}

function confirmDelete(id: number) {
  deleteDialog.targetId.value = id;
  deleteDialog.isOpen.value = true;
}

async function submitDelete() {
  if (!deleteDialog.targetId.value) return;

  deleteDialog.isLoading.value = true;

  try {
    await vesselApi.destroy(deleteDialog.targetId.value);

    table.allRows.value = table.allRows.value.filter((item) => item.id !== deleteDialog.targetId.value);

    if (table.currentPage.value > totalPages.value) {
      table.currentPage.value = totalPages.value;
    }

    deleteDialog.isOpen.value = false;
    success("Berhasil", "Vessel berhasil dihapus.");
  } catch (e: any) {
    error("Gagal menghapus", e.response?.data?.message ?? "Terjadi kesalahan saat menghapus data.");
  } finally {
    deleteDialog.isLoading.value = false;
    deleteDialog.targetId.value = null;
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Vessel" description="Kelola data armada kapal">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <div>
        <DataList v-model:search="table.searchQuery.value" v-model:per-page="table.perPage.value"
          :loading="table.loading.value" :empty="paginatedRows.length === 0" :colspan="7" :show-footer="true"
          :show-toolbar="true" :total="totalRecords" :current-page="table.currentPage.value" :total-pages="totalPages"
          :active-filter-count="activeFilterCount" search-placeholder="Cari vessel..."
          loading-text="Memuat data vessel..." empty-description="Belum ada vessel untuk ditampilkan."
          @page-change="goToPage">
          <template #filters="{ close }">
            <div class="space-y-4 p-1">
              <div>
                <div class="px-3 pt-1 pb-2 text-overline">Transporter</div>

                <TomSelect :model-value="filters.transporterId.value"
                  @update:model-value="(val) => { setFilterTransporter(val as string); close() }"
                  :options="{ placeholder: 'Cari transporter...', dropdownParent: 'body' }" class="w-full !box">
                  <option value="">Semua Transporter</option>
                  <option v-for="t in transporterOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
                </TomSelect>
              </div>

              <div class="pt-3 border-slate-100 border-t">
                <Button type="button" variant="outline-secondary" class="w-full" :disabled="activeFilterCount === 0"
                  @click="resetFilters">
                  Clear Filter
                </Button>
              </div>
            </div>
          </template>

          <template #head>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>Nama Kapal</Table.Th>
            <Table.Th>Transporter</Table.Th>
            <Table.Th>Kapasitas Maks</Table.Th>
            <Table.Th>Tipe</Table.Th>
            <Table.Th class="text-center">Status</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="(item, idx) in paginatedRows" :key="item.id" class="hover:bg-slate-50 transition">
              <Table.Td class="num-sm text-center">{{ (table.currentPage.value - 1) * table.perPage.value + idx + 1
                }}.</Table.Td>
              <Table.Td>{{ item.name }}</Table.Td>
              <Table.Td>{{ item.transporter?.company_name ?? "-" }}</Table.Td>
              <Table.Td class="num-sm">{{ item.max_capacity != null ? formatNumber(item.max_capacity) : "-" }}
              </Table.Td>
              <Table.Td>{{ item.type ?? "-" }}</Table.Td>
              <Table.Td class="text-center">
                <Badge :variant="item.is_active ? 'soft-success' : 'soft-secondary'">
                  {{ item.is_active ? "Active" : "Inactive" }}
                </Badge>
              </Table.Td>
              <Table.Td class="text-center">
                <div class="inline-flex justify-center items-center gap-2">
                  <Button variant="soft-warning" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Edit"
                    @click.prevent="openEdit(item)">
                    <Lucide icon="Edit" class="w-4 h-4" />
                  </Button>
                  <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                    @click="confirmDelete(item.id)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </DataList>
      </div>

      <VesselFormModal :open="formModal.isOpen.value" :mode="formModal.mode.value" :item="formModal.selectedItem.value"
        @close="formModal.isOpen.value = false" @success="handleFormSuccess" />

      <DeleteRecordDialog :open="deleteDialog.isOpen.value" title="Hapus Vessel" :loading="deleteDialog.isLoading.value"
        @close="deleteDialog.isOpen.value = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
