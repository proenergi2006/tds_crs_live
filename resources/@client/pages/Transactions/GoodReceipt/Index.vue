<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { debounce } from "lodash";

import Button from "@/components/Base/Button";
import { FormSelect } from "@/components/Base/Form";
import Lucide from "@/components/Base/Lucide";
import Table from "@/components/Base/Table";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DateField from "@/components/SystemDesign/Form/DateField.vue";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";

const { error } = useNotification();

const rows = ref<any[]>([]);
const vendors = ref<any[]>([]);
const terminals = ref<any[]>([]);

const searchQuery = ref("");
const filterDateFrom = ref("");
const filterDateTo = ref("");
const filterTerminal = ref("");
const filterVendor = ref("");

const perPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalRows = ref(0);
const loading = ref(false);

const activeFilterCount = computed(() =>
  [
    filterDateFrom.value,
    filterDateTo.value,
    filterTerminal.value,
    filterVendor.value,
  ].filter(Boolean).length,
);

onMounted(async () => {
  await Promise.all([fetchVendors(), fetchTerminals()]);
  fetchData();
});

watch(
  [searchQuery, filterDateFrom, filterDateTo, filterTerminal, filterVendor],
  debounce(() => fetchData(1), 300),
);

watch(perPage, () => fetchData(1));

async function fetchVendors() {
  try {
    const res = await axios.get("/api/vendors", { params: { per_page: 200 } });
    vendors.value = res.data.data || res.data || [];
  } catch {
    vendors.value = [];
  }
}

async function fetchTerminals() {
  try {
    const res = await axios.get("/api/terminals", { params: { per_page: 200 } });
    terminals.value = res.data.data || res.data || [];
  } catch {
    terminals.value = [];
  }
}

async function fetchData(page = 1) {
  loading.value = true;

  try {
    const res = await axios.get("/api/good-receipts", {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        tanggal_dari: filterDateFrom.value || undefined,
        tanggal_sampai: filterDateTo.value || undefined,
        id_terminal: filterTerminal.value || undefined,
        id_vendor: filterVendor.value || undefined,
      },
    });

    rows.value = res.data.data || [];
    currentPage.value = res.data.current_page || 1;
    totalPages.value = res.data.last_page || 1;
    totalRows.value = res.data.total || 0;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message || "Gagal memuat data");
  } finally {
    loading.value = false;
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return;
  fetchData(page);
}

function resetFilter() {
  filterDateFrom.value = "";
  filterDateTo.value = "";
  filterTerminal.value = "";
  filterVendor.value = "";
  fetchData(1);
}

function formatDate(value?: string | null) {
  if (!value) return "-";
  const date = new Date(value);
  if (isNaN(date.getTime())) return value;
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader
        title="Penerimaan Barang (GR)"
        description="Daftar penerimaan barang dari vendor berdasarkan Purchase Order."
      >
        <template #action>
          <Button variant="outline-secondary" class="inline-flex items-center gap-2">
            <Lucide icon="Download" class="h-4 w-4" />
            Ekspor
          </Button>
          <Button variant="white" class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            Buat GR
          </Button>
        </template>
      </PageHeader>

      <DataList
        v-model:search="searchQuery"
        v-model:per-page="perPage"
        :loading="loading"
        :empty="rows.length === 0"
        :colspan="10"
        :show-footer="true"
        :show-toolbar="true"
        :total="totalRows"
        :current-page="currentPage"
        :total-pages="totalPages"
        :active-filter-count="activeFilterCount"
        search-placeholder="Cari no GR atau PO..."
        loading-text="Memuat data GR..."
        empty-description="Belum ada data penerimaan barang."
        @page-change="goToPage"
      >
        <template #filters>
          <div class="space-y-4 p-1">
            <div>
              <div class="px-3 pb-2 pt-1 text-xs font-semibold uppercase text-slate-500">Tanggal Dari</div>
              <DateField v-model="filterDateFrom" placeholder="Pilih tanggal awal" />
            </div>

            <div>
              <div class="px-3 pb-2 text-xs font-semibold uppercase text-slate-500">Tanggal Sampai</div>
              <DateField v-model="filterDateTo" placeholder="Pilih tanggal akhir" />
            </div>

            <div>
              <div class="px-3 pb-2 text-xs font-semibold uppercase text-slate-500">Terminal</div>
              <FormSelect v-model="filterTerminal">
                <option value="">Semua Terminal</option>
                <option v-for="terminal in terminals" :key="terminal.id_terminal" :value="terminal.id_terminal">
                  {{ terminal.nama_terminal }}
                </option>
              </FormSelect>
            </div>

            <div>
              <div class="px-3 pb-2 text-xs font-semibold uppercase text-slate-500">Vendor</div>
              <FormSelect v-model="filterVendor">
                <option value="">Semua Vendor</option>
                <option v-for="vendor in vendors" :key="vendor.id_vendor" :value="vendor.id_vendor">
                  {{ vendor.nama_vendor }}
                </option>
              </FormSelect>
            </div>

            <div class="border-t border-slate-100 pt-3">
              <Button
                type="button"
                variant="outline-secondary"
                class="w-full"
                :disabled="activeFilterCount === 0"
                @click="resetFilter"
              >
                Clear Filter
              </Button>
            </div>
          </div>
        </template>

        <template #head>
          <Table.Th>No GR</Table.Th>
          <Table.Th>No PO</Table.Th>
          <Table.Th>Vendor</Table.Th>
          <Table.Th>Material</Table.Th>
          <Table.Th class="text-right">Qty Dok</Table.Th>
          <Table.Th class="text-right">Qty Aktual</Table.Th>
          <Table.Th class="text-right">Selisih</Table.Th>
          <Table.Th>Terminal</Table.Th>
          <Table.Th>Tgl Terima</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
        </template>

        <template #body>
          <!-- data rows will be added when API is ready -->
        </template>
      </DataList>
    </div>
  </div>
</template>
