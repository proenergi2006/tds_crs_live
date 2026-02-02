<template>
  <div class="p-6 intro-y">
    <div class="flex items-center">
  <h2 class="text-lg font-medium">Master Kabupaten</h2>
  <RouterLink :to="{ name: 'kabupatens-create' }" class="ml-auto">
    <Button variant="primary" class="inline-flex items-center gap-2">
      <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
      <span>Tambah Kabupaten</span>
    </Button>
  </RouterLink>
</div>

    <!-- Toolbar: Search / PerPage / Filter Provinsi -->
    <div class="flex flex-wrap items-center mt-5 intro-y sm:flex-nowrap space-x-2">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
      <!-- === Filter Provinsi === -->
      <FormSelect v-model="provinsiFilter" class="w-56 !box">
        <option value="">— Semua Provinsi —</option>
        <option
          v-for="p in provinsis"
          :key="p.id_provinsi"
          :value="p.id_provinsi"
        >
          {{ p.nama_provinsi }}
        </option>
      </FormSelect>
      <FormInput
        v-model="searchQuery"
        placeholder="Search…"
        class="w-56 !box"
      >
        <template #icon><Lucide icon="Search" /></template>
      </FormInput>
      <FormSelect v-model="perPage" class="w-20 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
      </FormSelect>
      
    </div>

    <!-- Tabel Kabupaten -->
    <div class="overflow-x-auto bg-white shadow rounded-lg mt-6">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Provinsi</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Kabupaten</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Created By</th>
            <th class="px-4 py-2 text-xs font-medium text-left uppercase">Created Time</th>
            <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(k, idx) in kabupatens"
            :key="k.id_kabupaten"
            class="hover:bg-slate-100 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
              {{ k.provinsi?.nama_provinsi || '-' }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap">{{ k.nama_kabupaten }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ k.created_by }}</td>
            <td class="px-4 py-3 whitespace-nowrap">
              {{ formatDate(k.created_time) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center">
              <RouterLink
                :to="{ name: 'kabupatens-edit', params: { id: k.id_kabupaten } }"
                class="text-blue-600 hover:text-blue-800 mx-2"
              >
                <Lucide icon="Edit" class="w-5 h-5"/>
              </RouterLink>
              <span class="text-slate-300">|</span>
              <button
                @click="confirmDelete(k.id_kabupaten)"
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
        <Pagination.Link :disabled="currentPage===1" @click="fetch(currentPage-1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page===currentPage"
          @click="fetch(page)"
        >
          {{ page }}
        </Pagination.Link>
        <Pagination.Link :disabled="currentPage===totalPages" @click="fetch(currentPage+1)">
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

const router        = useRouter()
const kabupatens    = ref<any[]>([])
const provinsis     = ref<any[]>([])
const searchQuery   = ref('')
const perPage       = ref(10)
const currentPage   = ref(1)
const totalPages    = ref(1)
const provinsiFilter = ref<number|string>('')
const loading       = ref(false)
const error         = ref<string|null>(null)

// ambil daftar provinsi untuk filter
async function fetchProvinsis() {
  try {
    const res = await axios.get('/api/provinsis', { params: { per_page: 100 }})
    provinsis.value = res.data.data || res.data
  } catch {}
}

// fungsi fetch dengan filter provinsi
async function fetch(page = 1) {
  loading.value = true
  error.value   = null
  try {
    const res = await axios.get('/api/kabupatens', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
        id_provinsi: provinsiFilter.value || undefined
      }
    })
    kabupatens.value  = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value  = res.data.last_page
  } catch (e:any) {
    error.value = e.response?.data?.message || 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

// on mount
onMounted(async () => {
  await fetchProvinsis()
  fetch()
})

// reaktif: search, perPage, filter provinsi
watch(searchQuery, debounce(() => fetch(1), 300))
watch(perPage, () => fetch(1))
watch(provinsiFilter, () => fetch(1))

function confirmDelete(id:number) {
  Swal.fire({
    title: 'Hapus kabupaten ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya'
  }).then(async res => {
    if (res.isConfirmed) {
      await axios.delete(`/api/kabupatens/${id}`)
      kabupatens.value = kabupatens.value.filter(k => k.id_kabupaten !== id)
      Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
    }
  })
}

function formatDate(d:string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' })
    : '-'
}
</script>
