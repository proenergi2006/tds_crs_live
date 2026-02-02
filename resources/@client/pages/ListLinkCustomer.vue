<!-- src/views/LinkCustomers/ListLinkCustomer.vue -->
<template>
    <div class="p-6">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">List Link Customer</h2>
  
        <div class="ml-auto flex items-center gap-2">
          <span class="text-slate-500">Show</span>
          <FormSelect v-model="perPage" class="w-24 !box">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </FormSelect>
          <span class="text-slate-500">Data</span>
        </div>
      </div>
  
      <div class="flex items-center mb-3">
        <FormInput v-model="search" placeholder="Keywords..." class="w-72 !box pr-10">
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
      </div>
  
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-left w-16">No</th>
              <th class="px-4 py-2 text-left">Customer</th>
              <th class="px-4 py-2 text-left">Cabang Invoice</th>
              <th class="px-4 py-2 text-left">Alamat</th>
              <th class="px-4 py-2 text-left">Email</th>
              <th class="px-4 py-2 text-left">Telp/ Fax</th>
              <th class="px-4 py-2 text-center w-32">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(c, i) in rows" :key="c.id_customer" class="border-b hover:bg-slate-50">
              <td class="px-4 py-3">{{ (currentPage-1)*perPage + i + 1 }}</td>
              <td class="px-4 py-3">
                <div class="text-slate-400">-------</div>
                <div class="font-medium">{{ c.nama_perusahaan || '-' }}</div>
              </td>
              <td class="px-4 py-3">{{ c.cabang?.nama_cabang || '-' }}</td>
              <td class="px-4 py-3 whitespace-pre-wrap">{{ c.alamat_perusahaan || '-' }}</td>
              <td class="px-4 py-3">{{ c.email || '-' }}</td>
              <td class="px-4 py-3">
                <div>Telp : {{ c.telepon || '-' }}</div>
                <div>Fax  : {{ c.fax || '-' }}</div>
              </td>
              <td class="px-4 py-3 text-center">
                <Button
                  :disabled="busyId === c.id_customer"
                  @click="getLink(c)"
                  variant="primary"
                  size="sm"
                >
                  {{ busyId === c.id_customer ? '...' : 'Get Link' }}
                </Button>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <div class="flex justify-center mt-4">
        <Pagination>
          <Pagination.Link :disabled="currentPage===1" @click="fetchData(currentPage-1)">
            <Lucide icon="ChevronLeft"/>
          </Pagination.Link>
          <Pagination.Link
            v-for="p in totalPages"
            :key="p"
            :active="p===currentPage"
            @click="fetchData(p)"
          >{{ p }}</Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchData(currentPage+1)">
            <Lucide icon="ChevronRight"/>
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
  
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import Pagination from '@/components/Base/Pagination'
  import Lucide from '@/components/Base/Lucide'
  import { useRouter } from 'vue-router'
const router = useRouter()
  
  const rows = ref<any[]>([])
  const search = ref('')
  const perPage = ref(25)
  const currentPage = ref(1)
  const totalPages = ref(1)
  const busyId = ref<number | null>(null)
  
  async function fetchData(page = 1) {
    const { data } = await axios.get('/api/link-customers', {
      params: { page, per_page: perPage.value, search: search.value || undefined }
    })
    rows.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
  }
  
  async function getLink(row: any) {
  try {
    busyId.value = row.id_customer
    const { data } = await axios.post(`/api/link-customers/${row.id_customer}/generate`)
    const link = data.link as string

    const result = await Swal.fire({
      title: data.already_exists ? 'Token sudah ada' : 'Token dibuat',
      html: `
        <div class="text-left">
          <div class="text-slate-500 text-xs mb-1">Token</div>
          <div class="font-mono break-all mb-3"><b>${data.verification.token_verification}</b></div>
         
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Simpan & Kembali',
      cancelButtonText: 'Tutup',
    })

    if (result.isConfirmed) {
      try { await navigator.clipboard.writeText(link) } catch {}
      await Swal.fire({ icon:'success', title:'Token disalin', timer:1000, showConfirmButton:false })
      // kembali ke halaman sebelumnya (Customer Verification List)
      router.back() // atau: router.push({ name: 'customer-verifications' })
    }
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal membuat link', 'error')
  } finally {
    busyId.value = null
  }
}
  
  onMounted(() => fetchData())
  watch(search, debounce(() => fetchData(1), 300))
  watch(perPage, () => fetchData(1))
  </script>
  
  <style scoped>
  /* opsional styling kecil */
  </style>
  