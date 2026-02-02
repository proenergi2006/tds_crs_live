<template>
    <div class="p-6 intro-y">
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Transportir</h2>
        <RouterLink :to="{ name: 'transportirs-create' }" class="ml-auto">
          <Button variant="primary">Tambah Transportir</Button>
        </RouterLink>
      </div>
  
      <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
        <div class="mx-auto text-slate-500">
          Halaman {{ currentPage }} dari {{ totalPages }}
        </div>
        <FormInput v-model="searchQuery" placeholder="Cari…" class="w-56 ml-auto pr-10 !box">
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
        <FormSelect v-model="perPage" class="w-20 ml-2 !box">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
  
      <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Perusahaan</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Singkatan</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Kepemilikan</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Telepon</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(t, idx) in transportirs" :key="t.id" class="hover:bg-slate-100 transition-colors">
              <td class="px-4 py-3">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-3">{{ t.nama_perusahaan }}</td>
              <td class="px-4 py-3">{{ t.singkatan }}</td>
              <td class="px-4 py-3">{{ t.kepemilikan }}</td>
              <td class="px-4 py-3">{{ t.telpon }}</td>
              <td class="px-4 py-3">
                <span :class="t.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ t.is_active ? 'Aktif' : 'Non Aktif' }}
                </span>
              </td>
              <td class="px-4 py-3 text-center flex justify-center items-center">
                <RouterLink :to="{ name: 'transportirs-edit', params: { id: t.id } }" class="text-blue-600 hover:text-blue-800 mx-2">
                  <Lucide icon="Edit" class="w-5 h-5" />
                </RouterLink>
                <span class="text-slate-300">|</span>
                <button @click="confirmDelete(t.id)" class="text-red-600 hover:text-red-800 mx-2">
                  <Lucide icon="Trash2" class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <div class="flex justify-center mt-5 intro-y">
        <Pagination>
          <Pagination.Link :disabled="currentPage === 1" @click="fetchData(currentPage - 1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link v-for="page in totalPages" :key="page" :active="page === currentPage" @click="fetchData(page)">
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage === totalPages" @click="fetchData(currentPage + 1)">
            <Lucide icon="ChevronRight" />
          </Pagination.Link>
        </Pagination>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { debounce } from 'lodash'
  import { useRouter, RouterLink } from 'vue-router'
  
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Pagination from '@/components/Base/Pagination'
  import Lucide from '@/components/Base/Lucide'
  
  const router = useRouter()
  const transportirs = ref<any[]>([])
  const searchQuery = ref('')
  const perPage = ref(10)
  const currentPage = ref(1)
  const totalPages = ref(1)
  const loading = ref(false)
  
  async function fetchData(page = 1) {
    loading.value = true
    try {
      const { data } = await axios.get('/api/transportirs', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      transportirs.value = data.data
      currentPage.value = data.current_page
      totalPages.value = data.last_page
    } catch {
      Swal.fire('Error', 'Gagal memuat data transportir', 'error')
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus transportir ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async result => {
      if (result.isConfirmed) {
        try {
          await axios.delete(`/api/transportirs/${id}`)
          transportirs.value = transportirs.value.filter(t => t.id !== id)
          Swal.fire({ icon: 'success', title: 'Terhapus', toast: true, position: 'top-end', timer: 1500 })
        } catch {
          Swal.fire('Error', 'Gagal menghapus transportir', 'error')
        }
      }
    })
  }
  
  onMounted(() => fetchData())
  
  watch(searchQuery, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  </script>
  