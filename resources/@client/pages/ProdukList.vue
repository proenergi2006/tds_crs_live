<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';

import Button from '@/components/Base/Button';
import Table from '@/components/Base/Table';
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue';
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue';
import DataList from '@/components/SystemDesign/Data/DataList.vue';
import Lucide from '@/components/Base/Lucide';

const produks = ref<any[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);
const totalRecords = ref(1);

async function fetchProduks(page = 1) {
  loading.value = true;
  error.value = null;
  try {
    const res = await axios.get('/api/produks', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
      },
    });
    produks.value = res.data.data;
    currentPage.value = res.data.current_page;
    totalPages.value = res.data.last_page;
    totalRecords.value = res.data.total;
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal memuat data produk';
  } finally {
    loading.value = false;
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return;
  fetchProduks(page);
}

watch(searchQuery, debounce(() => fetchProduks(1), 300));
watch(perPage, () => fetchProduks(1));

onMounted(() => {
  fetchProduks();
});

let produkToDelete: number | null = null;
function confirmDelete(id: number) {
  produkToDelete = id;
  Swal.fire({
    title: 'Yakin ingin menghapus?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: '🗑️ Hapus',
    cancelButtonText: '↩️ Batal',
  }).then(async (res) => {
    if (res.isConfirmed && produkToDelete) {
      await axios.delete(`/api/produks/${produkToDelete}`);
      produks.value = produks.value.filter((p) => p.id_produk !== produkToDelete);
      Swal.fire({ icon: 'success', title: 'Produk dihapus', toast: true, position: 'top-end', timer: 1500 });
    }
    produkToDelete = null;
  });
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <PageHeader title="Master Produk" description="Kelola daftar produk dan atributnya.">
        <template #action>
          <RouterLink :to="{ name: 'produks-create' }">
            <Button variant="primary" class="inline-flex items-center gap-2">
              <Lucide icon="Plus" class="h-4 w-4" />
              Tambah Produk
            </Button>
          </RouterLink>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari produk..."
        @page-change="goToPage" />

      <DataList :loading="loading" :empty="produks.length === 0" :colspan="7" :show-footer="true" :total="totalRecords"
        :current-page="currentPage" :per-page="perPage" loading-text="Memuat data produk..."
        empty-description="Belum ada produk untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-16 text-center">No</Table.Th>
          <Table.Th>Nama Produk</Table.Th>
          <Table.Th>Merk Dagang</Table.Th>
          <Table.Th>Ukuran</Table.Th>
          <Table.Th>Jenis</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(p, idx) in produks" :key="p.id_produk" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td>
              <div class="font-semibold text-slate-800">{{ p.nama_produk }}</div>
            </Table.Td>
            <Table.Td class="text-slate-600">{{ p.merk_dagang || '-' }}</Table.Td>
            <Table.Td class="text-slate-600">{{ p.ukuran?.nama_ukuran || '-' }}</Table.Td>
            <Table.Td class="text-slate-600">{{ p.jenis?.nama || '-' }}</Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center gap-2">
                <RouterLink :to="{ name: 'produks-edit', params: { id: p.id_produk } }"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-orange-200 bg-orange-50 text-orange-600 transition hover:bg-orange-100">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </RouterLink>
                <button type="button" @click="confirmDelete(p.id_produk)"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
