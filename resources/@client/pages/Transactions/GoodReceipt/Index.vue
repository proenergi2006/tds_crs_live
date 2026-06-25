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
const filterStatus = ref("");

const perPage = ref(10);
const currentPage = ref(1);
const totalPages = ref(1);
const totalRows = ref(0);
const loading = ref(true);
const pendingPos = ref<any[]>([]);
const loadingPending = ref(true);

const activeFilterCount = computed(() =>
  [
    filterDateFrom.value,
    filterDateTo.value,
    filterTerminal.value,
    filterVendor.value,
    filterStatus.value,
  ].filter(Boolean).length,
);

onMounted(async () => {
  await Promise.all([fetchVendors(), fetchTerminals()]);
  fetchData();
  fetchPendingGr();
});

watch(
  [searchQuery, filterDateFrom, filterDateTo, filterTerminal, filterVendor, filterStatus],
  debounce(() => fetchData(1), 300),
);

watch(perPage, () => fetchData(1));

async function fetchPendingGr() {
  loadingPending.value = true;
  try {
    const res = await axios.get('/api/good-receipts/pending');
    pendingPos.value = res.data;
  } catch {
    pendingPos.value = [];
  } finally {
    loadingPending.value = false;
  }
}

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
        status: filterStatus.value || undefined,
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
  filterStatus.value = "";
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

function formatNumber(v: number | null | undefined) {
  if (v == null) return '—'
  return v.toLocaleString('id-ID')
}

function formatSigned(v: number) {
  if (v === 0) return '0'
  return (v > 0 ? '+' : '') + v.toLocaleString('id-ID')
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Penerimaan Barang (GR)"
        description="Daftar penerimaan barang dari vendor berdasarkan Purchase Order.">
        <!-- <template #action>
          <Button variant="white" class="inline-flex items-center gap-2">
            <Lucide icon="Download" class="h-4 w-4" />
            Ekspor
          </Button>
        </template> -->
      </PageHeader>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <!-- Kolom kiri: konten utama -->
        <div class="col-span-8">
          <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
            :empty="rows.length === 0" :colspan="9" :show-footer="true" :show-toolbar="true" :total="totalRows"
            :current-page="currentPage" :total-pages="totalPages" :active-filter-count="activeFilterCount"
            search-placeholder="Cari no GR atau PO..." loading-text="Memuat data GR..."
            empty-description="Belum ada data penerimaan barang." @page-change="goToPage">
            <template #filters>
              <div class="space-y-4 p-1">
                <div>
                  <div class="font-section px-3 pb-2 pt-1">Tanggal Dari</div>
                  <DateField v-model="filterDateFrom" placeholder="Pilih tanggal awal" />
                </div>

                <div>
                  <div class="font-section px-3 pb-2">Tanggal Sampai</div>
                  <DateField v-model="filterDateTo" placeholder="Pilih tanggal akhir" />
                </div>

                <div>
                  <div class="font-section px-3 pb-2">Terminal</div>
                  <FormSelect v-model="filterTerminal">
                    <option value="">Semua Terminal</option>
                    <option v-for="terminal in terminals" :key="terminal.id_terminal" :value="terminal.id_terminal">
                      {{ terminal.nama_terminal }}
                    </option>
                  </FormSelect>
                </div>

                <div>
                  <div class="font-section px-3 pb-2">Vendor</div>
                  <FormSelect v-model="filterVendor">
                    <option value="">Semua Vendor</option>
                    <option v-for="vendor in vendors" :key="vendor.id_vendor" :value="vendor.id_vendor">
                      {{ vendor.nama_vendor }}
                    </option>
                  </FormSelect>
                </div>

                <div>
                  <div class="font-section px-3 pb-2">Status</div>
                  <FormSelect v-model="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="selesai">Selesai</option>
                  </FormSelect>
                </div>

                <div class="border-t border-slate-100 pt-3">
                  <Button type="button" variant="outline-secondary" class="w-full" :disabled="activeFilterCount === 0"
                    @click="resetFilter">
                    Clear Filter
                  </Button>
                </div>
              </div>
            </template>

            <template #head>
              <Table.Th class="w-32">No GR</Table.Th>
              <Table.Th class="w-40">No PO</Table.Th>
              <Table.Th>Vendor</Table.Th>
              <Table.Th class="text-right">Qty Dok</Table.Th>
              <Table.Th class="text-right">Qty Aktual</Table.Th>
              <Table.Th class="text-right">Selisih</Table.Th>
              <Table.Th>Terminal</Table.Th>
              <Table.Th class="w-32">Tgl Terima</Table.Th>
            </template>

            <template #body>
              <Table.Tr v-for="row in rows" :key="row.id" class="transition hover:bg-slate-50 cursor-pointer"
                @click="$router.push({ name: 'vendor-pos-receive', params: { id: row.id_po } })">
                <Table.Td>
                  <span class="font-num !text-primary">{{ row.no_gr }}</span>
                </Table.Td>
                <Table.Td>
                  <span class="font-body">{{ row.no_po }}</span>
                </Table.Td>
                <Table.Td>
                  <span class="font-body">{{ row.vendor }}</span>
                </Table.Td>
                <Table.Td class="text-right font-body">
                  {{ formatNumber(row.qty_dok) }}
                </Table.Td>
                <Table.Td class="text-right font-strong">
                  {{ row.qty_aktual != null ? formatNumber(row.qty_aktual) : '—' }}
                </Table.Td>
                <Table.Td class="font-num text-right" :class="row.selisih == null ? 'text-slate-400'
                  : row.selisih === 0 ? 'text-emerald-600'
                    : row.selisih > 0 ? 'text-blue-600'
                      : 'text-red-500'">
                  {{ row.selisih != null ? formatSigned(row.selisih) : '—' }}
                </Table.Td>
                <Table.Td>
                  <span class="font-body">{{ row.terminal }}</span>
                </Table.Td>
                <Table.Td>
                  <span class="font-body">{{ formatDate(row.tgl_terima) }}</span>
                </Table.Td>
              </Table.Tr>
            </template>
          </DataList>
        </div>

        <!-- Kolom kanan: sidebar pending -->
        <div class="col-span-4 xl:pt-0">
          <div class="sticky top-4">
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">

              <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <div class="flex items-center gap-2 font-strong">
                  <Lucide icon="AlertCircle" class="h-4 w-4 text-amber-500" />
                  Belum Terealisasi
                </div>
                <span class="rounded-full bg-amber-50 font-label px-2 py-0.5 !text-amber-700">
                  {{ pendingPos.length }}
                </span>
              </div>

              <div v-if="loadingPending" class="px-4 py-6 font-body text-center">
                Memuat...
              </div>

              <div v-else-if="pendingPos.length === 0" class="px-4 py-8 text-center">
                <Lucide icon="CheckCircle" class="mx-auto h-8 w-8 text-emerald-400" />
                <p class="mt-2 font-body">Semua PO sudah terealisasi</p>
              </div>

              <div v-else class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto">
                <div v-for="po in pendingPos" :key="po.id_po"
                  class="cursor-pointer px-4 py-3 transition hover:bg-slate-50"
                  @click="$router.push({ name: 'vendor-pos-receive', params: { id: po.id_po } })">
                  <p class="font-num !text-primary">{{ po.nomor_po }}</p>
                  <p class="mt-0.5 font-caption">{{ po.vendor }}</p>
                  <div class="mt-2 flex items-center justify-between">
                    <span class="rounded-full font-label px-2 py-0.5" :class="po.status === 'belum'
                      ? 'bg-amber-50 text-amber-700'
                      : 'bg-blue-50 text-blue-700'">
                      {{ po.status === 'belum' ? 'Belum ada GR' : `Parsial ${po.persen}%` }}
                    </span>
                    <span class="font-caption">{{ formatNumber(po.volume_po) }} ton</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
