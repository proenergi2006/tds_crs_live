<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { debounce } from "lodash";

import Button from "@/components/Base/Button";
import Table from "@/components/Base/Table";
import Lucide from "@/components/Base/Lucide";
import TomSelect from "@/components/Base/TomSelect";
import { FormSelect } from "@/components/Base/Form";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DeleteRecordDialog from "@/components/SystemDesign/Dialog/DeleteRecordDialog.vue";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";
import { formatCurrency, formatNumber } from "@/utils/format";

import TransportTariffFormModal from "./Form.vue";
import type { TransportTariff, TransportType } from "./types";
import type { FormMode } from "../types";

const transportTariffApi = createResourceApi("/transport-tariffs");
const { success, error } = useNotification();

const table = {
  allRows: ref<TransportTariff[]>([]),
  searchQuery: ref(""),
  perPage: ref(10),
  currentPage: ref(1),
  loading: ref(false),
};

const filters = {
  transportType: ref<"" | TransportType>(""),
  transporterId: ref(""),
  transportAreaId: ref(""),
  isActive: ref<"" | "active" | "inactive">(""),
};

const formModal = {
  isOpen: ref(false),
  mode: ref<FormMode>("create"),
  selectedItem: ref<TransportTariff | null>(null),
};

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
  targetId: ref<number | null>(null),
};

const transporterOptions = computed(() => {
  const options = new Map<string, string>();

  table.allRows.value.forEach((item) => {
    if (item.transporter) options.set(String(item.transporter.id), item.transporter.company_name);
  });

  return Array.from(options.entries()).map(([id, name]) => ({ id, name }));
});

const transportAreaOptions = computed(() => {
  const options = new Map<string, string>();

  table.allRows.value.forEach((item) => {
    if (item.transport_area) options.set(String(item.transport_area.id), item.transport_area.name);
  });

  return Array.from(options.entries()).map(([id, name]) => ({ id, name }));
});

const activeFilterCount = computed(() => {
  return [filters.transportType.value, filters.transporterId.value, filters.transportAreaId.value, filters.isActive.value].filter(
    Boolean,
  ).length;
});

const filteredRows = computed(() => {
  const query = table.searchQuery.value.trim().toLowerCase();

  return table.allRows.value.filter((item) => {
    const matchesType = !filters.transportType.value || item.transport_type?.value === filters.transportType.value;
    const matchesTransporter =
      !filters.transporterId.value || String(item.transporter?.id ?? "") === filters.transporterId.value;
    const matchesArea =
      !filters.transportAreaId.value || String(item.transport_area?.id ?? "") === filters.transportAreaId.value;
    const matchesStatus =
      !filters.isActive.value || (filters.isActive.value === "active" ? item.is_active : !item.is_active);
    const matchesSearch =
      !query ||
      [item.transporter?.company_name, item.transport_area?.name, item.volume?.volume].some((value) =>
        String(value ?? "").toLowerCase().includes(query),
      );

    return matchesType && matchesTransporter && matchesArea && matchesStatus && matchesSearch;
  });
});

const totalRecords = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / table.perPage.value)));

const paginatedRows = computed(() => {
  const start = (table.currentPage.value - 1) * table.perPage.value;
  return filteredRows.value.slice(start, start + table.perPage.value);
});

watch(
  [table.searchQuery, filters.transportType, filters.transporterId, filters.transportAreaId, filters.isActive],
  debounce(resetToFirstPage, 300),
);
watch(table.perPage, resetToFirstPage);

onMounted(() => {
  fetchData();
});

async function fetchData() {
  table.loading.value = true;

  try {
    const { data } = await transportTariffApi.getAll({ as_list: true });
    table.allRows.value = data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data transport tariff");
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

function setFilterTransportArea(value: string) {
  filters.transportAreaId.value = value;
}

function resetFilters() {
  filters.transportType.value = "";
  filters.transporterId.value = "";
  filters.transportAreaId.value = "";
  filters.isActive.value = "";
}

function openCreate() {
  formModal.mode.value = "create";
  formModal.selectedItem.value = null;
  formModal.isOpen.value = true;
}

function openEdit(item: TransportTariff) {
  formModal.mode.value = "edit";
  formModal.selectedItem.value = item;
  formModal.isOpen.value = true;
}

function handleFormSuccess(data: TransportTariff, mode: FormMode) {
  syncRow(data, mode);
  formModal.isOpen.value = false;
}

function syncRow(data: TransportTariff, mode: FormMode) {
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
    await transportTariffApi.destroy(deleteDialog.targetId.value);

    table.allRows.value = table.allRows.value.filter((item) => item.id !== deleteDialog.targetId.value);

    if (table.currentPage.value > totalPages.value) {
      table.currentPage.value = totalPages.value;
    }

    deleteDialog.isOpen.value = false;
    success("Berhasil", "Transport Tariff berhasil dihapus.");
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
      <PageHeader title="Transport Tariff" description="Kelola data tarif angkutan kapal dan truck">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <div>
        <DataList v-model:search="table.searchQuery.value" v-model:per-page="table.perPage.value"
          :loading="table.loading.value" :empty="paginatedRows.length === 0" :colspan="8" :show-footer="true"
          :show-toolbar="true" :total="totalRecords" :current-page="table.currentPage.value" :total-pages="totalPages"
          :active-filter-count="activeFilterCount" search-placeholder="Cari transport tariff..."
          loading-text="Memuat data transport tariff..."
          empty-description="Belum ada transport tariff untuk ditampilkan." @page-change="goToPage">
          <template #filters="{ close }">
            <div class="space-y-4 p-1">
              <div>
                <div class="px-3 pt-1 pb-2 text-overline">Jenis Angkutan</div>
                <FormSelect v-model="filters.transportType.value">
                  <option value="">Semua</option>
                  <option value="VESSEL">Kapal</option>
                  <option value="TRUCK">Truck</option>
                </FormSelect>
              </div>

              <div>
                <div class="px-3 pt-1 pb-2 text-overline">Transporter</div>
                <TomSelect :model-value="filters.transporterId.value"
                  @update:model-value="(val) => { setFilterTransporter(val as string); close() }"
                  :options="{ placeholder: 'Cari transporter...', dropdownParent: 'body' }" class="w-full !box">
                  <option value="">Semua Transporter</option>
                  <option v-for="t in transporterOptions" :key="t.id" :value="t.id">{{ t.name }}</option>
                </TomSelect>
              </div>

              <div>
                <div class="px-3 pt-1 pb-2 text-overline">Transport Area</div>
                <TomSelect :model-value="filters.transportAreaId.value"
                  @update:model-value="(val) => { setFilterTransportArea(val as string); close() }"
                  :options="{ placeholder: 'Cari transport area...', dropdownParent: 'body' }" class="w-full !box">
                  <option value="">Semua Area</option>
                  <option v-for="w in transportAreaOptions" :key="w.id" :value="w.id">{{ w.name }}</option>
                </TomSelect>
              </div>

              <div>
                <div class="px-3 pt-1 pb-2 text-overline">Status</div>
                <FormSelect v-model="filters.isActive.value">
                  <option value="">Semua</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </FormSelect>
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
            <Table.Th>Transporter</Table.Th>
            <Table.Th>Jenis</Table.Th>
            <Table.Th>Transport Area</Table.Th>
            <Table.Th>Volume</Table.Th>
            <Table.Th class="text-right">Tarif</Table.Th>
            <Table.Th class="text-center">Status</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="(item, idx) in paginatedRows" :key="item.id" class="hover:bg-slate-50 transition">
              <Table.Td class="num-sm text-center">{{ (table.currentPage.value - 1) * table.perPage.value + idx + 1
                }}.</Table.Td>
              <Table.Td>{{ item.transporter?.company_name ?? "-" }}</Table.Td>
              <Table.Td>
                <Badge v-if="item.transport_type">{{ item.transport_type.label }}</Badge>
                <span v-else>-</span>
              </Table.Td>
              <Table.Td>{{ item.transport_area?.name ?? "-" }}</Table.Td>
              <Table.Td>{{ item.volume ? formatNumber(item.volume.volume) : "-" }}</Table.Td>
              <Table.Td class="num-sm text-right">{{ formatCurrency(item.rate) }}</Table.Td>
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

      <TransportTariffFormModal :open="formModal.isOpen.value" :mode="formModal.mode.value"
        :item="formModal.selectedItem.value" @close="formModal.isOpen.value = false" @success="handleFormSuccess" />

      <DeleteRecordDialog :open="deleteDialog.isOpen.value" title="Hapus Transport Tariff"
        :loading="deleteDialog.isLoading.value" @close="deleteDialog.isOpen.value = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
