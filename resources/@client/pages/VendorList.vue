<template>
    <div class="p-6 intro-y">
      <!-- Header & Add -->
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">Vendor</h2>
        <RouterLink :to="{ name: 'vendors-create' }" class="ml-auto">
          <Button variant="primary">Add New Vendor</Button>
        </RouterLink>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center mb-4 intro-y sm:flex-nowrap">
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
      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <Table class="min-w-full divide-y divide-slate-200">
          <Table.Thead>
            <Table.Tr>
              <Table.Th class="w-12">No</Table.Th>
              <Table.Th>Nama Vendor</Table.Th>
              <Table.Th>Inisial</Table.Th>
              <Table.Th>Catatan</Table.Th>
              <Table.Th class="text-center">Status</Table.Th>
              <Table.Th class="text-center">Actions</Table.Th>
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            <Table.Tr
              v-for="(v, idx) in vendors"
              :key="v.id_vendor"
              class="hover:bg-slate-100 transition-colors"
            >
              <Table.Td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </Table.Td>
              <Table.Td class="px-4 py-3 whitespace-nowrap">{{ v.nama_vendor }}</Table.Td>
              <Table.Td class="px-4 py-3 whitespace-nowrap">{{ v.inisial }}</Table.Td>
              <Table.Td class="px-4 py-3 truncate">{{ v.catatan || '-' }}</Table.Td>
              <Table.Td class="px-4 py-3 text-center whitespace-nowrap">
                <span :class="v.is_active ? 'text-success' : 'text-danger'">
                  {{ v.is_active ? 'Active' : 'Inactive' }}
                </span>
              </Table.Td>
              <Table.Td class="px-4 py-3 text-center whitespace-nowrap flex justify-center">
                <RouterLink
                  :to="{ name: 'vendors-edit', params: { id: v.id_vendor } }"
                  class="text-blue-600 hover:text-blue-800 mx-2"
                >
                  <Lucide icon="Edit" class="w-5 h-5"/>
                </RouterLink>
                <button
                  @click="confirmDelete(v.id_vendor)"
                  class="text-red-600 hover:text-red-800 mx-2"
                >
                  <Lucide icon="Trash2" class="w-5 h-5"/>
                </button>
              </Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
  
      <!-- Pagination -->
      <div class="flex justify-center mt-4 intro-y">
        <Pagination>
          <Pagination.Link :disabled="currentPage===1" @click="fetchVendors(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchVendors(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchVendors(currentPage+1)">
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
  import Table from '@/components/Base/Table'
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  const router      = useRouter()
  const vendors     = ref<any[]>([])
  const searchQuery = ref('')
  const perPage     = ref(10)
  const currentPage = ref(1)
  const totalPages  = ref(1)
  const loading     = ref(false)
  const error       = ref<string|null>(null)
  
  async function fetchVendors(page = 1) {
    loading.value = true
    try {
      const res = await axios.get('/api/vendors', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      vendors.value    = res.data.data
      currentPage.value= res.data.current_page
      totalPages.value = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data'
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus vendor ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async res => {
      if (res.isConfirmed) {
        await axios.delete(`/api/vendors/${id}`)
        vendors.value = vendors.value.filter(v => v.id_vendor !== id)
        Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
      }
    })
  }
  
  onMounted(() => fetchVendors())
  watch(searchQuery, debounce(() => fetchVendors(1), 300))
  watch(perPage, () => fetchVendors(1))
  </script>
  