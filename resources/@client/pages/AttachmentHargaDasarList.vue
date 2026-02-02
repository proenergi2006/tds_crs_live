<template>
    <div class="p-6 intro-y">
      <!-- Header & Add New -->
      <div class="flex items-center">
  <h2 class="text-lg font-medium">Master Attachment Harga Dasar</h2>
  <RouterLink :to="{ name: 'attachment-harga-dasar-create' }" class="ml-auto">
    <Button variant="primary" class="inline-flex items-center gap-2">
      <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
      <span>Tambah Data</span>
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
          <!-- Header -->
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">No</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Periode Awal</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Periode Akhir</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Catatan</th>
              <th class="px-4 py-2 text-xs font-medium text-left uppercase">Lampiran</th>
              <th class="px-4 py-2 text-xs font-medium text-center uppercase">Actions</th>
            </tr>
          </thead>
          <!-- Body -->
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="(item, idx) in attachments"
              :key="item.id_attachment_harga_dasar"
              class="hover:bg-slate-100 transition-colors"
            >
              <td class="px-4 py-3 whitespace-nowrap">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ formatDate(item.periode_awal) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ formatDate(item.periode_akhir) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ item.catatan || '-' }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <template v-if="item.lampiran">
    <a
      :href="`/storage/${item.lampiran}`"
      target="_blank"
      class="text-primary hover:underline"
    >
      {{ item.lampiran }}
    </a>
  </template>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-center flex justify-center items-center">
                <RouterLink
                  :to="{ name: 'attachment-harga-dasar-edit', params: { id: item.id_attachment_harga_dasar } }"
                  class="text-blue-600 hover:text-blue-800 mx-2"
                >
                  <Lucide icon="Edit" class="w-5 h-5"/>
                </RouterLink>
                <span class="text-slate-300">|</span>
                <button
                  @click="confirmDelete(item.id_attachment_harga_dasar)"
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
            @click="fetchAttachments(currentPage-1)"
          >
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchAttachments(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link
            :disabled="currentPage===totalPages"
            @click="fetchAttachments(currentPage+1)"
          >
            <Lucide icon="ChevronRight" />
          </Pagination.Link>
        </Pagination>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, watch, onMounted } from 'vue'
  import axios from 'axios'
  import { debounce } from 'lodash'
  import Swal from 'sweetalert2'
  import { useRouter, RouterLink } from 'vue-router'
  
  import Button from '@/components/Base/Button'
  import Pagination from '@/components/Base/Pagination'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  const attachments = ref<any[]>([])
  const searchQuery = ref('')
  const perPage      = ref(10)
  const currentPage = ref(1)
  const totalPages  = ref(1)
  const loading     = ref(false)
  const error       = ref<string|null>(null)
  
  const router = useRouter()
  
  async function fetchAttachments(page = 1) {
    loading.value = true
    error.value   = null
    try {
      const res = await axios.get('/api/attachment-harga-dasar', {
        params: {
          page,
          per_page: perPage.value,
          search: searchQuery.value || undefined
        }
      })
      attachments.value = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value  = res.data.last_page
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Gagal memuat data'
    } finally {
      loading.value = false
    }
  }
  
  function confirmDelete(id: number) {
    Swal.fire({
      title: 'Hapus item ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus'
    }).then(async res => {
      if (res.isConfirmed) {
        await axios.delete(`/api/attachment-harga-dasar/${id}`)
        attachments.value = attachments.value.filter(i => i.id_attachment_harga_dasar !== id)
        Swal.fire({ icon:'success', title:'Terhapus', toast:true, position:'top-end', timer:1500 })
      }
    })
  }
  
  function formatDate(d: string) {
    return new Date(d).toLocaleDateString('id-ID', {
      day:   '2-digit',
      month: 'long',
      year:  'numeric',
    })
  }
  
  watch(searchQuery, debounce(() => fetchAttachments(1), 300))
  watch(perPage, () => fetchAttachments(1))
  
  onMounted(() => fetchAttachments())
  </script>
  