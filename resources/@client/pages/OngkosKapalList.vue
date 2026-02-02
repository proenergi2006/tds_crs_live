<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">Ongkos Kapal</h2>
        <RouterLink :to="{ name: 'ongkos-kapal-create' }" class="ml-auto">
          <Button variant="primary">Tambah Ongkos Kapal</Button>
        </RouterLink>
      </div>
  
      <div class="flex flex-wrap items-center mb-4">
        <FormInput v-model="searchQuery" placeholder="Cari transportir…" class="w-56 pr-10 !box">
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
              <th class="px-4 py-2">Transportir</th>
              <th class="px-4 py-2">Wilayah Angkut</th>
              <th class="px-4 py-2">Total Volume</th>
              <th class="px-4 py-2">Status</th>
              <th class="px-4 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(item, idx) in items" :key="item.id" class="hover:bg-slate-100">
              <td class="px-4 py-2">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-2">{{ item.transportir?.nama_perusahaan || '-' }}</td>
              <td class="px-4 py-2">{{ item.angkut_wilayah?.destinasi || '-' }}</td>
              <td class="px-4 py-2">{{ item.ongkos_details?.length || 0 }}</td>
              <td class="px-4 py-2">
                <span :class="item.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
              </td>
              <td class="px-4 py-2 text-center flex justify-center space-x-2">
                <RouterLink :to="{ name: 'ongkos-kapal-edit', params: { id: item.id } }" class="text-blue-600 hover:text-blue-800">
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
  
      <div class="flex justify-center mt-5">
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
      const { data } = await axios.get('/api/ongkos-kapal', {
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
      Swal.fire('Error', 'Gagal memuat data ongkos kapal', 'error')
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus data ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async result => {
      if (result.isConfirmed) {
        try {
          await axios.delete(`/api/ongkos-kapal/${id}`)
          fetchData(currentPage.value)
          Swal.fire({ icon: 'success', title: 'Terhapus', toast: true, position: 'top-end', timer: 1500 })
        } catch {
          Swal.fire('Error', 'Gagal menghapus data', 'error')
        }
      }
    })
  }
  
  onMounted(() => fetchData())
  watch(searchQuery, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  </script>
  