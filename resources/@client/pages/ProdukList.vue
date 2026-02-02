<template>
  <div class="p-6 intro-y">
    <h2 class="text-lg font-medium">Master Produk</h2>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
      <RouterLink :to="{ name: 'produks-create' }">
    <Button variant="primary" class="inline-flex items-center gap-2">
      <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
      <span>Tambah Produk</span>
    </Button>
  </RouterLink>
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
      <FormInput
        v-model="searchQuery"
        placeholder="Search…"
        class="w-56 ml-auto pr-10 !box"
      >
        <template #icon><Lucide icon="Search" /></template>
      </FormInput>
      <FormSelect v-model="perPage" class="w-20 ml-2 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
      </FormSelect>
    </div>

    <!-- Data List -->
    <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
      <table class="min-w-full divide-y divide-slate-200">
        <!-- Header -->
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama Produk</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Merk Dagang</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Ukuran</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Jenis</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Status</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
          </tr>
        </thead>
        <!-- Body -->
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(p, idx) in produks"
            :key="p.id_produk"
            class="hover:bg-slate-100 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ p.nama_produk }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ p.merk_dagang || '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ p.ukuran?.nama_ukuran || '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ p.jenis?.nama || '-' }}</td>
            <td class="px-4 py-3 whitespace-nowrap text-center">
              <span :class="p.is_active ? 'text-green-600' : 'text-red-600'">
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
              <RouterLink
                :to="{ name: 'produks-edit', params: { id: p.id_produk } }"
                class="text-blue-600 hover:text-blue-800 mx-2"
              >
                <Lucide icon="Edit" class="w-5 h-5" />
              </RouterLink>
              <span class="text-slate-300">|</span>
              <button
                @click="confirmDelete(p.id_produk)"
                class="text-red-600 hover:text-red-800 mx-2"
              >
                <Lucide icon="Trash2" class="w-5 h-5" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-5 intro-y col-span-12">
      <Pagination>
        <Pagination.Link :disabled="currentPage===1" @click="fetchProduks(currentPage-1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page===currentPage"
          @click="fetchProduks(page)"
        >
          {{ page }}
        </Pagination.Link>
        <Pagination.Link :disabled="currentPage===totalPages" @click="fetchProduks(currentPage+1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

import Button from '@/components/Base/Button';
import Pagination from '@/components/Base/Pagination';
import { FormInput, FormSelect } from '@/components/Base/Form';
import Lucide from '@/components/Base/Lucide';

const router = useRouter();

const produks = ref<any[]>([]);
const loading = ref(false);
const error = ref<string|null>(null);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const totalPages = ref(1);

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
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal memuat data produk';
  } finally {
    loading.value = false;
  }
}

watch(searchQuery, debounce(() => fetchProduks(1), 300));
watch(perPage, () => fetchProduks(1));

onMounted(() => {
  fetchProduks();
});

let produkToDelete: number|null = null;
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
      produks.value = produks.value.filter(p => p.id_produk !== produkToDelete);
      Swal.fire({ icon:'success', title:'Produk dihapus', toast:true, position:'top-end', timer:1500 });
    }
    produkToDelete = null;
  });
}
</script>
