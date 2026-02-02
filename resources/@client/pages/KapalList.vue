<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">Data Kapal</h2>
        <RouterLink :to="{ name: 'kapals-create' }" class="ml-auto">
          <Button variant="primary">Tambah Kapal</Button>
        </RouterLink>
      </div>
  
      <div class="flex mb-4">
        <FormInput v-model="searchQuery" placeholder="Cari nama kapal…" class="w-56 pr-10 !box">
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
        <FormSelect v-model="perPage" class="w-20 ml-2 !box">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
  
      <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2">No</th>
              <th class="px-4 py-2">Nama Kapal</th>
              <th class="px-4 py-2">Kapasitas</th>
              <th class="px-4 py-2">Transportir</th>
              <th class="px-4 py-2">Status</th>
              <th class="px-4 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(item, idx) in items" :key="item.id" class="hover:bg-slate-100">
              <td class="px-4 py-2 text-center">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-2 text-center">{{ item.nama_kapal }}</td>
              <td class="px-4 py-2 text-center">{{ item.kapasitas_max }} m³</td>
              <td class="px-4 py-2 text-center">{{ item.transportir?.nama_perusahaan || '-' }}</td>
              <td class="px-4 py-2 text-center">
                <span :class="item.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ item.is_active ? 'Aktif' : 'Non Aktif' }}
                </span>
              </td>
              <td class="px-4 py-2 flex justify-center space-x-2">
                <RouterLink :to="{ name: 'kapals-edit', params: { id: item.id } }" class="text-blue-600 hover:text-blue-800">
                  <Lucide icon="Edit" class="w-4 h-4" />
                </RouterLink>
                <button @click="confirmDelete(item.id)" class="text-red-600 hover:text-red-800">
                  <Lucide icon="Trash2" class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <Pagination class="mt-4">
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
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import { debounce } from 'lodash'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { RouterLink } from 'vue-router'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Pagination from '@/components/Base/Pagination'
  import Lucide from '@/components/Base/Lucide'
  
  const items = ref<any[]>([])
  const searchQuery = ref('')
  const perPage = ref(10)
  const currentPage = ref(1)
  const totalPages = ref(1)
  
  async function fetchData(page = 1) {
    try {
      const { data } = await axios.get('/api/master-kapals', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      items.value = data.data
      currentPage.value = data.current_page
      totalPages.value = data.last_page
    } catch {
      Swal.fire('Error', 'Gagal memuat data kapal', 'error')
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus kapal ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async result => {
      if (result.isConfirmed) {
        await axios.delete(`/api/master-kapals/${id}`)
        fetchData(currentPage.value)
        Swal.fire('Terhapus', 'Data berhasil dihapus', 'success')
      }
    })
  }
  
  onMounted(() => fetchData())
  watch(searchQuery, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  </script>
  