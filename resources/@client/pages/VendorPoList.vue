<template>
  <div class="p-6 intro-y">
    <div class="flex items-center">
      <h2 class="text-lg font-medium">Daftar Vendor PO</h2>
      <RouterLink :to="{ name: 'vendor-pos-create' }" class="ml-auto">
        <Button variant="primary">Tambah PO</Button>
      </RouterLink>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
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

    <!-- Table -->
    <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nomor PO</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Vendor</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Terminal</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Status</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(po, idx) in vendorPos"
            :key="po.id_po"
            class="hover:bg-slate-100 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">{{ po.nomor_po }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ po.vendor?.nama_vendor }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ po.terminal?.nama_terminal }}</td>
            <td class="px-4 py-3 text-center whitespace-nowrap">
              <span
                :class="{
                  'text-yellow-600': po.disposisi_po === 0,
                  'text-orange-600': po.disposisi_po === 1,
                  'text-red-600':    po.disposisi_po === 2,
                  'text-green-600':  po.disposisi_po === 4
                }"
              >
                {{
                  po.disposisi_po === 0 ? 'Draft'
                  : po.disposisi_po === 1 ? 'Menunggu Verifikasi CFO'
                  : po.disposisi_po === 2 ? 'Menunggu Verifikasi CEO'
                  : po.disposisi_po === 4 ? 'Verified'
                  : '-'
                }}
              </span>
            </td>
         
            <td class="px-4 py-3 whitespace-nowrap text-center">
    <div class="inline-flex items-center space-x-4">
      <!-- Cetak (ikon printer) -->
      <button
        v-if="po.disposisi_po === 4"
        @click="preview(po.id_po)"
        class="text-green-600 hover:text-green-800"
        title="Cetak"
      >
        <Lucide icon="Printer" class="w-5 h-5" />
      </button>

      <!-- Receive Item (ikon package/delivery) -->
      <button
        v-if="po.disposisi_po === 4"
        @click="goReceiveItem(po.id_po)"
        class="text-indigo-600 hover:text-indigo-800"
        title="Receive Item"
      >
        <Lucide icon="Package" class="w-5 h-5" />
      </button>

      <!-- Delete (ikon trash) -->
      <button
        v-if="po.disposisi_po === 0"
        @click="confirmDelete(po.nomor_po, po.id_po)"
        class="text-red-600 hover:text-red-800"
        title="Delete"
      >
        <Lucide icon="Trash2" class="w-5 h-5" />
      </button>

      <!-- Detail (ikon eye) -->
      <RouterLink
        :to="{ name: 'vendor-pos-detail', params: { id: po.id_po } }"
        class="text-blue-600 hover:text-blue-800"
        title="Detail"
      >
        <Lucide icon="Eye" class="w-5 h-5" />
      </RouterLink>

      <!-- Edit (ikon edit) -->
      <RouterLink
        :to="{ name: 'vendor-pos-edit', params: { id: po.id_po } }"
        class="text-yellow-600 hover:text-yellow-800"
        title="Edit"
      >
        <Lucide icon="Edit" class="w-5 h-5" />
      </RouterLink>
    </div>
  </td>



          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-5 intro-y">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchData(currentPage - 1)"
        >
          <Lucide icon="ChevronLeft"/>
        </Pagination.Link>
        <Pagination.Link
          v-for="p in totalPages"
          :key="p"
          :active="p === currentPage"
          @click="fetchData(p)"
        >
          {{ p }}
        </Pagination.Link>
        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchData(currentPage + 1)"
        >
          <Lucide icon="ChevronRight"/>
        </Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { useRouter, RouterLink } from 'vue-router'
import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

const router      = useRouter()
const vendorPos   = ref<any[]>([])
const searchQuery = ref('')
const perPage     = ref(10)
const currentPage = ref(1)
const totalPages  = ref(1)

/** Ambil data PO */
async function fetchData(page = 1) {
  try {
    const res = await axios.get('/api/vendor-pos', {
      params: { page, per_page: perPage.value, search: searchQuery.value || undefined }
    })
    vendorPos.value   = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value  = res.data.last_page
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data','error')
  }
}

/** Buka PDF di tab baru */
async function preview(id: number) {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, {
      responseType: 'blob'
    });
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url  = URL.createObjectURL(blob);
    window.open(url, '_blank');
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  } catch {
    Swal.fire('Error','Gagal membuka PDF','error');
  }
}

function goReceiveItem(idPo: number) {
  router.push({
    name: 'receive-item-list',    // ganti sesuai nama route-mu
    params: { id: idPo }
  })
}

function confirmDelete(nomorPo: string, id: number) {
  Swal.fire({
    title: `Hapus PO ${nomorPo}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus'
  }).then(async res => {
    if (!res.isConfirmed) return;
    await axios.delete(`/api/vendor-pos/${id}`);
    Swal.fire({
      icon: 'success',
      title: `PO ${nomorPo} terhapus`,
      toast: true,
      position: 'top-end',
      timer: 1500
    });
    fetchData(currentPage.value);
  });
}

onMounted(() => fetchData())
watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>
