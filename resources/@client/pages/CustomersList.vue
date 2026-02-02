<template>
    <div class="p-6 intro-y">
      <!-- Header & Add New -->
      <!-- Header & Add New -->
<div class="flex items-center">
  <h2 class="text-lg font-medium">Master Customers</h2>
  <RouterLink :to="{ name: 'customers-create' }" class="ml-auto">
    <Button variant="primary" class="inline-flex items-center gap-2">
      <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
      <span>Tambah Customer</span>
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
  
      <!-- Data List -->
      <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama Customer</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Alamat Customer</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Cabang Invoice</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">LCR</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Jumlah</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="(c, idx) in customers"
              :key="c.id_customer"
              class="hover:bg-slate-100 transition-colors"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ c.nama_perusahaan }}
                <br>
                <span class="text-sm text-slate-500">{{ c.user?.name || '-' }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ c.alamat_perusahaan }}
                <br>
                <span class="text-sm text-slate-500">Telp : {{ c.telepon || '-' }} Fax : {{ c.fax || '-' }}</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ c.cabang?.nama_cabang || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ c.status_customer === 1 ? 'Prospect' : (c.status_customer === 2 ? 'Tetap' : '-') }}
              </td>
              <td>
                <td class="px-4 py-3 whitespace-nowrap">
  <Lucide v-if="c.has_lcr" icon="CheckCircle" class="w-5 h-5 text-green-600" />
  <Lucide v-else icon="XCircle" class="w-5 h-5 text-slate-400" />
</td>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-center">
 Penawaran :  {{ c.jumlah_penawaran ?? 0 }}
</td>
              
              <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
                <!-- <RouterLink
                  :to="{ name: 'customers-edit', params: { id: c.id_customer } }"
                  class="text-blue-600 hover:text-blue-800 mx-2"
                >
                  <Lucide icon="Edit" class="w-5 h-5"/>
                </RouterLink>
                <span class="text-slate-300">|</span> -->
                <button
                  @click="confirmDelete(c.id_customer)"
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
          <Pagination.Link :disabled="currentPage===1" @click="fetchCustomers(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchCustomers(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchCustomers(currentPage+1)">
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
  
  const router       = useRouter()
  const customers    = ref<any[]>([])
  const loading      = ref(false)
  const error        = ref<string|null>(null)
  const searchQuery  = ref('')
  const currentPage  = ref(1)
  const perPage      = ref(10)
  const totalPages   = ref(1)
  
  async function fetchCustomers(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const res = await axios.get('/api/customers', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      customers.value   = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value  = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Failed to load'
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Apakah Anda Yakin?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus'
    }).then(async res => {
      if (res.isConfirmed) {
        await axios.delete(`/api/customers/${id}`)
        customers.value = customers.value.filter(c => c.id_customer !== id)
        Swal.fire({ icon:'success', title:'Deleted', toast:true, position:'top-end', timer:1500 })
      }
    })
  }
  
  onMounted(() => fetchCustomers())
  
  watch(searchQuery, debounce(() => fetchCustomers(1), 300))
  watch(perPage, () => fetchCustomers(1))
  </script>
  