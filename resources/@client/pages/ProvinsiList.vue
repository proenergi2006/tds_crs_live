<template>
    <div class="p-6 intro-y">
      <!-- Header & Add New -->
      <div class="flex items-center">
  <h2 class="text-lg font-medium">Master Provinsi</h2>
  <RouterLink :to="{ name: 'provinsi-create' }" class="ml-auto">
    <Button variant="primary" class="inline-flex items-center gap-2">
      <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
      <span>Tambah Provinsi</span>
    </Button>
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
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama Provinsi</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Created</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Updated</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="(p, idx) in provinsis"
              :key="p.id_provinsi"
              class="hover:bg-slate-100 transition-colors"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ p.nama_provinsi }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ formatDateTime(p.created_time) }}<br/>
                <span class="text-sm text-slate-500">by {{ p.created_by || '-' }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ formatDateTime(p.lastupdate_time) }}<br/>
                <span class="text-sm text-slate-500">by {{ p.lastupdate_by || '-' }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
                <RouterLink
                  :to="{ name: 'provinsi-edit', params: { id: p.id_provinsi } }"
                  class="text-blue-600 hover:text-blue-800 mx-2"
                >
                  <Lucide icon="Edit" class="w-5 h-5"/>
                </RouterLink>
                <span class="text-slate-300">|</span>
                <button
                  @click="confirmDelete(p.id_provinsi)"
                  class="text-red-600 hover:text-red-800 mx-2"
                >
                  <Lucide icon="Trash2" class="w-5 h-5"/>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <!-- Pagination -->
      <div class="flex justify-center mt-5 intro-y">
        <Pagination>
          <Pagination.Link
            :disabled="currentPage===1"
            @click="fetchProvinsis(currentPage-1)"
          >
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchProvinsis(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link
            :disabled="currentPage===totalPages"
            @click="fetchProvinsis(currentPage+1)"
          >
            <Lucide icon="ChevronRight" />
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
  const provinsis   = ref<any[]>([])
  const loading     = ref(false)
  const error       = ref<string|null>(null)
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage     = ref(10)
  const totalPages  = ref(1)
  
  async function fetchProvinsis(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const res = await axios.get('/api/provinsis', {
        params: { page, per_page: perPage.value, search: searchQuery.value || undefined }
      })
      provinsis.value   = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value  = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data'
    } finally {
      loading.value = false
    }
  }
  
  async function confirmDelete(id: number) {
  const result = await Swal.fire({
    title: 'Yakin ingin menghapus?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus'
  });
  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/provinsis/${id}`);
    // kalau berhasil lanjut buang dari list
    provinsis.value = provinsis.value.filter(p => p.id_provinsi !== id);
    Swal.fire({
      icon: 'success',
      title: 'Provinsi berhasil dihapus',
      toast: true,
      position: 'top-end',
      timer: 1500
    });
  } catch (e: any) {
    // cek jika kode HTTP 409 (conflict) dari controller
    if (e.response?.status === 409) {
      Swal.fire('Error', e.response.data.message, 'error');
    } else {
      Swal.fire('Error', 'Gagal menghapus provinsi', 'error');
    }
  }
}

  
  function formatDateTime(dt: string|null) {
    if (!dt) return '-'
    return new Date(dt).toLocaleString('id-ID', {
      day: '2-digit', month: 'long', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  }
  
  onMounted(() => fetchProvinsis())
  watch(searchQuery, debounce(() => fetchProvinsis(1), 300))
  watch(perPage, () => fetchProvinsis(1))
  </script>
  