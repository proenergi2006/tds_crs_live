<template>
    <div class="p-6 intro-y">
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Personnel</h2>
        <RouterLink :to="{ name: 'personnels-create' }" class="ml-auto">
          <Button variant="primary">Tambah Personnel</Button>
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
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Transportir</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Nama Dokumen</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Masa Berlaku</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Status</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="(p, idx) in personnels" :key="p.id" class="hover:bg-slate-100 transition-colors">
              <td class="px-4 py-3">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-3">{{ p.nama }}</td>
              <td class="px-4 py-3">{{ p.transportir?.nama_perusahaan || '-' }}</td>
              <td class="px-4 py-3">{{ p.nama_dokumen || '-' }}</td>
              <td class="px-4 py-3">{{ formatDate(p.masa_berlaku) }}</td>
              <td class="px-4 py-3">
                <span :class="p.is_active ? 'text-green-600' : 'text-red-600'">
                  {{ p.is_active ? 'Aktif' : 'Non Aktif' }}
                </span>
              </td>
              <td class="px-4 py-3 text-center flex justify-center items-center">
                <RouterLink :to="{ name: 'personnels-edit', params: { id: p.id } }" class="text-blue-600 hover:text-blue-800 mx-2">
                  <Lucide icon="Edit" class="w-5 h-5" />
                </RouterLink>
                <span class="text-slate-300">|</span>
                <button @click="confirmDelete(p.id)" class="text-red-600 hover:text-red-800 mx-2">
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
  
  const personnels = ref<any[]>([])
  const searchQuery = ref('')
  const perPage = ref(10)
  const currentPage = ref(1)
  const totalPages = ref(1)
  const loading = ref(false)
  

  function formatDate(dateStr: string | null) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long', // Bisa diganti '2-digit' kalau mau angka
    year: 'numeric'
  })
}

  async function fetchData(page = 1) {
    loading.value = true
    try {
      const { data } = await axios.get('/api/personnels', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      personnels.value = data.data
      currentPage.value = data.current_page
      totalPages.value = data.last_page
    } catch {
      Swal.fire('Error', 'Gagal memuat data personnel', 'error')
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus personnel ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus'
    }).then(async result => {
      if (result.isConfirmed) {
        try {
          await axios.delete(`/api/personnels/${id}`)
          personnels.value = personnels.value.filter(p => p.id !== id)
          Swal.fire({ icon: 'success', title: 'Terhapus', toast: true, position: 'top-end', timer: 1500 })
        } catch {
          Swal.fire('Error', 'Gagal menghapus personnel', 'error')
        }
      }
    })
  }
  
  onMounted(() => fetchData())
  watch(searchQuery, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  </script>
  