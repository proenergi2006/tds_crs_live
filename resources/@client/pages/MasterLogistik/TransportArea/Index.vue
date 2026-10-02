<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { debounce } from "lodash";

import Button from "@/components/Base/Button";
import Table from "@/components/Base/Table";
import Lucide from "@/components/Base/Lucide";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DeleteRecordDialog from "@/components/SystemDesign/Dialog/DeleteRecordDialog.vue";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import TransportAreaFormModal from "./Form.vue";
import type { TransportArea, TransportAreaDependents } from "./types";
import type { DeleteConflict, FormMode } from "../types";

const transportAreaApi = createResourceApi("/transport-areas");
const { success, error } = useNotification();

const table = {
  allRows: ref<TransportArea[]>([]),
  searchQuery: ref(""),
  perPage: ref(10),
  currentPage: ref(1),
  loading: ref(false),
};

const formModal = {
  isOpen: ref(false),
  mode: ref<FormMode>("create"),
  selectedItem: ref<TransportArea | null>(null),
};

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
  targetId: ref<number | null>(null),
  conflict: ref<DeleteConflict<TransportAreaDependents> | null>(null),
};

const filteredRows = computed(() => {
  const query = table.searchQuery.value.trim().toLowerCase();
  if (!query) return table.allRows.value;

  return table.allRows.value.filter((item) =>
    [
      item.name,
      item.province?.name,
      item.regency?.name,
      item.provinsi?.nama_provinsi,
      item.kabupaten?.nama_kabupaten,
    ].some((value) => String(value ?? "").toLowerCase().includes(query)),
  );
});

const totalRecords = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / table.perPage.value)));

const paginatedRows = computed(() => {
  const start = (table.currentPage.value - 1) * table.perPage.value;
  return filteredRows.value.slice(start, start + table.perPage.value);
});

watch(table.searchQuery, debounce(resetToFirstPage, 300));
watch(table.perPage, resetToFirstPage);

onMounted(() => {
  fetchData();
});

async function fetchData() {
  table.loading.value = true;

  try {
    const { data } = await transportAreaApi.getAll({ as_list: true });
    table.allRows.value = data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data transport area");
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

function openCreate() {
  formModal.mode.value = "create";
  formModal.selectedItem.value = null;
  formModal.isOpen.value = true;
}

function openEdit(item: TransportArea) {
  formModal.mode.value = "edit";
  formModal.selectedItem.value = item;
  formModal.isOpen.value = true;
}

function handleFormSuccess(data: TransportArea, mode: FormMode) {
  syncRow(data, mode);
  formModal.isOpen.value = false;
}

function syncRow(data: TransportArea, mode: FormMode) {
  if (mode === "create") {
    table.allRows.value.unshift(data);
    return;
  }

  const index = table.allRows.value.findIndex((item) => item.id === data.id);
  if (index !== -1) table.allRows.value[index] = data;
}

function confirmDelete(id: number) {
  deleteDialog.targetId.value = id;
  deleteDialog.conflict.value = null;
  deleteDialog.isOpen.value = true;
}

async function submitDelete() {
  if (!deleteDialog.targetId.value) return;

  deleteDialog.isLoading.value = true;

  try {
    await transportAreaApi.destroy(deleteDialog.targetId.value);

    table.allRows.value = table.allRows.value.filter((item) => item.id !== deleteDialog.targetId.value);

    if (table.currentPage.value > totalPages.value) {
      table.currentPage.value = totalPages.value;
    }

    deleteDialog.isOpen.value = false;
    success("Berhasil", "Transport Area berhasil dihapus.");
  } catch (e: any) {
    if (e.response?.status === 409) {
      deleteDialog.conflict.value = {
        message: e.response.data.message,
        dependents: e.response.data.dependents ?? null,
      };
    } else {
      deleteDialog.isOpen.value = false;
      error("Gagal menghapus", e.response?.data?.message ?? "Terjadi kesalahan saat menghapus data.");
    }
  } finally {
    deleteDialog.isLoading.value = false;
  }
}

function closeDeleteDialog() {
  deleteDialog.isOpen.value = false;
  deleteDialog.conflict.value = null;
  deleteDialog.targetId.value = null;
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="Transport Area" description="Kelola data area tujuan angkutan">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Data Baru
          </Button>
        </template>
      </PageHeader>

      <div>
        <DataList v-model:search="table.searchQuery.value" v-model:per-page="table.perPage.value"
          :loading="table.loading.value" :empty="paginatedRows.length === 0" :colspan="6" :show-footer="true"
          :show-toolbar="true" :total="totalRecords" :current-page="table.currentPage.value" :total-pages="totalPages"
          search-placeholder="Cari transport area..." loading-text="Memuat data transport area..."
          empty-description="Belum ada transport area untuk ditampilkan." @page-change="goToPage">
          <template #head>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>Nama Area</Table.Th>
            <Table.Th>Provinsi</Table.Th>
            <Table.Th>Kabupaten/Kota</Table.Th>
            <Table.Th class="text-center">Status</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="(item, idx) in paginatedRows" :key="item.id" class="hover:bg-slate-50 transition">
              <Table.Td class="num-sm text-center">{{ (table.currentPage.value - 1) * table.perPage.value + idx + 1
                }}.</Table.Td>
              <Table.Td>{{ item.name }}</Table.Td>
              <Table.Td>{{ item.province?.name || item.provinsi?.nama_provinsi || "-" }}</Table.Td>
              <Table.Td>{{ item.regency?.name || item.kabupaten?.nama_kabupaten || "-" }}</Table.Td>
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

      <TransportAreaFormModal :open="formModal.isOpen.value" :mode="formModal.mode.value"
        :item="formModal.selectedItem.value" @close="formModal.isOpen.value = false" @success="handleFormSuccess" />

      <DeleteRecordDialog :open="deleteDialog.isOpen.value"
        :title="deleteDialog.conflict.value ? 'Transport Area Tidak Dapat Dihapus' : 'Hapus Transport Area'"
        :loading="deleteDialog.isLoading.value" :show-confirm="!deleteDialog.conflict.value"
        :cancel-text="deleteDialog.conflict.value ? 'Tutup' : 'Batal'" @close="closeDeleteDialog"
        @confirm="submitDelete">
        <template v-if="deleteDialog.conflict.value">
          <p class="mt-2 text-body">{{ deleteDialog.conflict.value.message }}</p>
          <ul v-if="deleteDialog.conflict.value.dependents" class="mt-2 pl-5 text-body list-disc">
            <li>Transport Tariff: {{ deleteDialog.conflict.value.dependents.transport_tariffs }}</li>
            <li>LCR Customer: {{ deleteDialog.conflict.value.dependents.customer_lcr }}</li>
          </ul>
          <p class="mt-2 text-body">Hapus atau pindahkan data tersebut terlebih dahulu.</p>
        </template>
      </DeleteRecordDialog>
    </div>
  </div>
</template>
