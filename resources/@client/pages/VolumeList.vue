<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">Volume</h2>
        <RouterLink :to="{ name: 'volumes-create' }" class="ml-auto">
          <Button variant="primary">Tambah Volume</Button>
        </RouterLink>
      </div>
  
      <div class="flex flex-wrap items-center mb-4">
        <FormInput v-model="searchQuery" placeholder="Cari volume…" class="w-56 pr-10 !box">
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
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Volume</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Satuan</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(v, idx) in volumes" :key="v.id_volume" class="hover:bg-slate-100">
              <td class="px-4 py-2">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-2">{{ v.volume }}</td>
              <td class="px-4 py-2">{{ v.satuan?.nama_satuan || '-' }}</td>
              <td class="px-4 py-2">
                <span :class="v.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ v.is_active ? 'Aktif' : 'Non Aktif' }}
                </span>
              </td>
              <td class="px-4 py-2 text-center flex justify-center space-x-2">
                <RouterLink :to="{ name: 'volumes-edit', params: { id: v.id_volume } }" class="text-blue-600 hover:text-blue-800">
                  <Lucide icon="Edit" class="w-4 h-4" />
                </RouterLink>
                <button @click="confirmDelete(v.id_volume)" class="text-red-600 hover:text-red-800">
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
  import { debounce } from 'lodash'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { RouterLink } from 'vue-router'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Pagination from '@/components/Base/Pagination'
  import Lucide from '@/components/Base/Lucide'
  
  const volumes = ref<any[]>([])
  const searchQuery = ref('')
  const perPage = ref(10)
  const currentPage = ref(1)
  const totalPages = ref(1)
  const loading = ref(false)
  
  async function fetchData(page = 1) {
    loading.value = true
    try {
      const { data } = await axios.get('/api/volumes', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      volumes.value = data.data
      currentPage.value = data.current_page
      totalPages.value = data.last_page
    } catch {
      Swal.fire('Error', 'Gagal memuat data volume', 'error')
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus volume ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async result => {
      if (result.isConfirmed) {
        try {
          await axios.delete(`/api/volumes/${id}`)
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
  