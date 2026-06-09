<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { debounce } from "lodash";
import Swal from "sweetalert2";
import { RouterLink, useRouter } from "vue-router";

import Button from "@/components/Base/Button";
import { FormSelect } from "@/components/Base/Form";
import Lucide from "@/components/Base/Lucide";
import Table from "@/components/Base/Table";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DateField from "@/components/SystemDesign/Form/DateField.vue";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import PageToolbar from "@/components/SystemDesign/Page/PageToolbar.vue";

const router = useRouter();

const vendorPos = ref<any[]>([]);
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

function formatDate(value: string | null | undefined) {
  if (!value) return "-";

  const date = new Date(value);
  if (isNaN(date.getTime())) return value;

  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}

function statusLabel(disposisi: number) {
  return disposisi === 0
    ? "Draft"
    : disposisi === 1
      ? "Menunggu Verifikasi CFO"
      : disposisi === 2
        ? "Menunggu Verifikasi CEO"
        : disposisi === 4
          ? "Verified"
          : "-";
}

function statusBadgeClass(disposisi: number) {
  return disposisi === 0
    ? "bg-yellow-100 text-yellow-700"
    : disposisi === 1
      ? "bg-orange-100 text-orange-700"
      : disposisi === 2
        ? "bg-red-100 text-red-700"
        : disposisi === 4
          ? "bg-green-100 text-green-700"
          : "bg-slate-100 text-slate-600";
}

async function fetchVendors() {
  try {
    const res = await axios.get("/api/vendors", {
      params: { per_page: 200 },
    });

    vendors.value = res.data.data || res.data || [];
  } catch {
    vendors.value = [];
  }
}

async function fetchTerminals() {
  try {
    const res = await axios.get("/api/terminals", {
      params: { per_page: 200 },
    });

    terminals.value = res.data.data || res.data || [];
  } catch {
    terminals.value = [];
  }
}

async function fetchData(page = 1) {
  loading.value = true;

  try {
    const res = await axios.get("/api/vendor-pos", {
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

    vendorPos.value = res.data.data || [];
    currentPage.value = res.data.current_page || 1;
    totalPages.value = res.data.last_page || 1;
    totalRows.value = res.data.total || 0;
  } catch (e: any) {
    Swal.fire(
      "Error",
      e.response?.data?.message || "Gagal memuat data",
      "error",
    );
  } finally {
    loading.value = false;
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return;
  fetchData(page);
}

function resetFilter() {
  searchQuery.value = "";
  filterDateFrom.value = "";
  filterDateTo.value = "";
  filterTerminal.value = "";
  filterVendor.value = "";
  fetchData(1);
}

async function preview(id: number) {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = URL.createObjectURL(blob);
    window.open(url, "_blank");
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  } catch {
    Swal.fire("Error", "Gagal membuka PDF", "error");
  }
}

function goReceiveItem(idPo: number) {
  router.push({
    name: "receive-item-list",
    params: { id: idPo },
  });
}

function confirmDelete(nomorPo: string, id: number) {
  Swal.fire({
    title: `Hapus PO ${nomorPo}?`,
    text: "Data yang dihapus tidak dapat dikembalikan.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, hapus",
    cancelButtonText: "Batal",
  }).then(async (res) => {
    if (!res.isConfirmed) return;

    try {
      await axios.delete(`/api/vendor-pos/${id}`);
      Swal.fire({
        icon: "success",
        title: `PO ${nomorPo} terhapus`,
        toast: true,
        position: "top-end",
        timer: 1500,
        showConfirmButton: false,
      });
      fetchData(currentPage.value);
    } catch (e: any) {
      Swal.fire(
        "Error",
        e.response?.data?.message || "Gagal menghapus PO",
        "error",
      );
    }
  });
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <div class="col-span-12 intro-y">
      <PageHeader title="PO Supplier"
        description="Kelola Purchase Order vendor, filter data, dan akses aksi dengan cepat.">
        <template #action>
          <!-- <RouterLink :to="{ name: 'vendor-pos-create' }"> -->
          <Button as="RouterLink" :to="{ name: 'vendor-pos-create' }" variant="white"
            class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah PO
          </Button>
          <!-- </RouterLink> -->
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :total-pages="totalPages" :active-filter-count="activeFilterCount" search-placeholder="Nomor PO / keterangan..."
        search-label="Cari PO" @page-change="goToPage">
        <template #filters>
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-slate-700">Filter PO</h3>
              <p class="text-sm text-slate-500">
                Filter berdasarkan tanggal, terminal, dan vendor.
              </p>
            </div>

            <Button variant="outline-secondary" class="inline-flex items-center gap-2" @click="resetFilter">
              <Lucide icon="RotateCcw" class="h-4 w-4" />
              Reset
            </Button>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
              <DateField v-model="filterDateFrom" label="Tanggal Dari" placeholder="Pilih tanggal awal" />
            </div>

            <div>
              <DateField v-model="filterDateTo" label="Tanggal Sampai" placeholder="Pilih tanggal akhir" />
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">Terminal</label>
              <FormSelect v-model="filterTerminal" class="!box">
                <option value="">Semua Terminal</option>
                <option v-for="terminal in terminals" :key="terminal.id_terminal" :value="terminal.id_terminal">
                  {{ terminal.nama_terminal }}
                </option>
              </FormSelect>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-600">Vendor</label>
              <FormSelect v-model="filterVendor" class="!box">
                <option value="">Semua Vendor</option>
                <option v-for="vendor in vendors" :key="vendor.id_vendor" :value="vendor.id_vendor">
                  {{ vendor.nama_vendor }}
                </option>
              </FormSelect>
            </div>
          </div>
        </template>
      </PageToolbar>

      <DataList :loading="loading" :empty="vendorPos.length === 0" :colspan="7" :show-footer="true" :total="totalRows"
        :current-page="currentPage" :per-page="perPage" loading-text="Memuat data PO supplier..."
        empty-description="Tidak ada data PO ditemukan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Tanggal PO</Table.Th>
          <Table.Th>Vendor</Table.Th>
          <Table.Th>Terminal</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(po, idx) in vendorPos" :key="po.id_po" class="transition hover:bg-slate-50">
            <Table.Td class="whitespace-nowrap text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              <div class="font-semibold text-slate-700">{{ po.nomor_po }}</div>
            </Table.Td>

            <Table.Td class="whitespace-nowrap text-slate-700">
              {{ formatDate(po.tanggal_inven) }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap text-slate-700">
              {{ po.vendor?.nama_vendor || "-" }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap text-slate-700">
              {{ po.terminal?.nama_terminal || "-" }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusBadgeClass(po.disposisi_po)">
                {{ statusLabel(po.disposisi_po) }}
              </span>
            </Table.Td>

            <Table.Td class="whitespace-nowrap text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <RouterLink :to="{ name: 'vendor-pos-detail', params: { id: po.id_po } }" title="Detail">
                  <Button variant="soft-dark" rounded class="!h-8 !w-8 !p-0 !shadow-none">
                    <Lucide icon="Eye" class="h-4 w-4" />
                  </Button>
                </RouterLink>

                <RouterLink :to="{ name: 'vendor-pos-edit', params: { id: po.id_po } }" title="Edit">
                  <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none">
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </Button>
                </RouterLink>

                <Button v-if="po.disposisi_po === 0" variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Delete" @click="confirmDelete(po.nomor_po, po.id_po)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>

                <Button v-if="po.disposisi_po === 4" variant="soft-primary" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Receive Item" @click="goReceiveItem(po.id_po)">
                  <Lucide icon="Package" class="h-4 w-4" />
                </Button>

                <Button v-if="po.disposisi_po === 4" variant="soft-success" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Cetak" @click="preview(po.id_po)">
                  <Lucide icon="Printer" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
