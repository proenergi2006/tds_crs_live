<template>
    <div class="p-6 intro-y">
      <!-- Header & Add New -->
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Terminal</h2>
        <RouterLink :to="{ name: 'terminals-create' }" class="ml-auto">
          <Button variant="primary">Tambah Terminal</Button>
        </RouterLink>
      </div>
  
      <!-- Toolbar -->
      <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap">
        <div class="mx-auto text-slate-500">
          Halaman {{ currentPage }} dari {{ totalPages }}
        </div>
        <FormInput
          v-model="searchQuery"
          placeholder="Cari…"
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
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Cabang</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Kategori</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Inisial</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Lokasi</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Telepon</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="(t, idx) in terminals"
              :key="t.id_terminal"
              class="hover:bg-slate-100 transition-colors"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.nama_terminal }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.cabang?.nama_cabang || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.kategori_terminal }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.inisial || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.lokasi || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ t.telp_terminal || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
                <RouterLink
                  :to="{ name: 'terminals-edit', params: { id: t.id_terminal } }"
                  class="text-blue-600 hover:text-blue-800 mx-2"
                >
                  <Lucide icon="Edit" class="w-5 h-5"/>
                </RouterLink>
                <span class="text-slate-300">|</span>
                <button
                  @click="confirmDelete(t.id_terminal)"
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
          <Pagination.Link :disabled="currentPage===1" @click="fetchTerminals(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchTerminals(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchTerminals(currentPage+1)">
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
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Pagination from '@/components/Base/Pagination'
  import Lucide from '@/components/Base/Lucide'
  
  const router        = useRouter()
  const terminals     = ref<any[]>([])
  const searchQuery   = ref('')
  const perPage       = ref(10)
  const currentPage   = ref(1)
  const totalPages    = ref(1)
  const loading       = ref(false)
  const error         = ref<string|null>(null)
  
  async function fetchTerminals(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const res = await axios.get('/api/terminals', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      terminals.value   = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value  = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data'
    } finally {
      loading.value = false
    }
  }
  
  onMounted(() => fetchTerminals())
  
  watch(searchQuery, debounce(() => fetchTerminals(1), 300))
  watch(perPage, () => fetchTerminals(1))
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus terminal ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async res => {
      if (res.isConfirmed) {
        await axios.delete(`/api/terminals/${id}`)
        terminals.value = terminals.value.filter(t => t.id_terminal !== id)
        Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
      }
    })
  }
  </script>
  