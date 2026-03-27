<template>
  <div class="p-6 intro-y">
    <!-- Header -->
    <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">
          Master Customers Proenergi
        </h2>
        <p class="mt-1 text-sm text-slate-500">
          Kelola data customer, status customer, cabang invoice, dan jumlah penawaran.
        </p>
      </div>

      <RouterLink :to="{ name: 'customers-create-proenergi' }" class="md:ml-auto">
        <Button variant="primary" class="inline-flex items-center gap-2">
          <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
          <span>Tambah Customer</span>
        </Button>
      </RouterLink>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 xl:grid-cols-4">
      <div class="p-5 bg-white border shadow-sm rounded-xl">
        <div class="text-sm text-slate-500">Total Customer</div>
        <div class="mt-2 text-2xl font-semibold text-slate-800">
          {{ totalCustomers }}
        </div>
      </div>

      <div class="p-5 bg-white border shadow-sm rounded-xl">
        <div class="text-sm text-slate-500">Prospect</div>
        <div class="mt-2 text-2xl font-semibold text-amber-600">
          {{ totalProspect }}
        </div>
      </div>

      <div class="p-5 bg-white border shadow-sm rounded-xl">
        <div class="text-sm text-slate-500">Customer Tetap</div>
        <div class="mt-2 text-2xl font-semibold text-emerald-600">
          {{ totalTetap }}
        </div>
      </div>

      <div class="p-5 bg-white border shadow-sm rounded-xl">
        <div class="text-sm text-slate-500">Total Penawaran</div>
        <div class="mt-2 text-2xl font-semibold text-primary">
          {{ totalPenawaran }}
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col gap-3 p-4 mb-6 bg-white border shadow-sm rounded-xl md:flex-row md:items-center">
      <div class="text-sm text-slate-500">
        Page <span class="font-medium text-slate-700">{{ currentPage }}</span>
        of
        <span class="font-medium text-slate-700">{{ totalPages }}</span>
      </div>

      <div class="flex flex-col gap-3 ml-auto sm:flex-row sm:items-center">
        <FormInput
          v-model="searchQuery"
          placeholder="Search customer..."
          class="w-full sm:w-72"
        >
          <template #icon>
            <Lucide icon="Search" class="w-4 h-4" />
          </template>
        </FormInput>

        <FormSelect v-model="perPage" class="w-full sm:w-24">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="px-4 py-3 mb-6 text-sm border rounded-lg bg-danger/10 border-danger/20 text-danger"
    >
      {{ error }}
    </div>

    <!-- Table -->
    <div class="overflow-hidden bg-white border shadow-sm rounded-xl">
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="border-b bg-slate-50">
            <tr>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">No</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">Nama Customer</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">Alamat Customer</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">Cabang Invoice</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">Status</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-left uppercase text-slate-600">LCR</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">Jumlah</th>
              <th class="px-5 py-4 text-xs font-semibold tracking-wide text-center uppercase text-slate-600">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <!-- Loading -->
            <tr v-if="loading">
              <td colspan="8" class="px-5 py-10 text-center text-slate-500">
                Loading data customer...
              </td>
            </tr>

            <!-- Empty -->
            <tr v-else-if="customers.length === 0">
              <td colspan="8" class="px-5 py-10 text-center text-slate-500">
                Data customer tidak ditemukan.
              </td>
            </tr>

            <!-- Data -->
            <tr
              v-for="(c, idx) in customers"
              v-else
              :key="c.id_customer"
              class="transition hover:bg-slate-50"
            >
              <td class="px-5 py-4 align-top whitespace-nowrap text-slate-700">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <td class="px-5 py-4 align-top">
                <div class="font-medium text-slate-800">
                  {{ c.nama_perusahaan || '-' }}
                </div>
                <div class="mt-1 text-sm text-slate-500">
                  {{ c.user?.name || '-' }}
                </div>
              </td>

              <td class="px-5 py-4 align-top">
                <div class="text-slate-700">
                  {{ c.alamat_perusahaan || '-' }}
                </div>
                <div class="mt-1 text-sm text-slate-500">
                  Telp: {{ c.telepon || '-' }} | Fax: {{ c.fax || '-' }}
                </div>
              </td>

              <td class="px-5 py-4 align-top whitespace-nowrap text-slate-700">
                {{ c.cabang?.nama_cabang || '-' }}
              </td>

              <td class="px-5 py-4 align-top whitespace-nowrap">
                <span
                  class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full"
                  :class="getStatusBadgeClass(c.status_customer)"
                >
                  {{ getStatusLabel(c.status_customer) }}
                </span>
              </td>

              <td class="px-5 py-4 align-top whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <Lucide
                    v-if="c.has_lcr"
                    icon="CheckCircle"
                    class="w-5 h-5 text-emerald-600"
                  />
                  <Lucide
                    v-else
                    icon="XCircle"
                    class="w-5 h-5 text-slate-400"
                  />
                  <span class="text-sm text-slate-600">
                    {{ c.has_lcr ? 'Available' : 'No LCR' }}
                  </span>
                </div>
              </td>

              <td class="px-5 py-4 text-center align-top whitespace-nowrap text-slate-700">
                Penawaran : {{ c.jumlah_penawaran ?? 0 }}
              </td>

              <td class="px-5 py-4 align-top whitespace-nowrap">
                <div class="flex items-center justify-center gap-2">
                  <!-- Aktifkan jika route edit sudah tersedia -->
                  <!--
                  <RouterLink
                    :to="{ name: 'customers-edit-proenergi', params: { id: c.id_customer } }"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-primary hover:bg-slate-50"
                    title="Edit"
                  >
                    <Lucide icon="Pencil" class="w-4 h-4" />
                  </RouterLink>
                  -->

                  <button
                    @click="confirmDelete(c.id_customer)"
                    class="inline-flex items-center justify-center w-9 h-9 text-red-600 border border-red-200 rounded-lg hover:bg-red-50"
                    title="Delete"
                  >
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-6 intro-y" v-if="totalPages > 1">
      <Pagination>
        <Pagination.Link
          :disabled="currentPage === 1"
          @click="fetchCustomers(currentPage - 1)"
        >
          <Lucide icon="ChevronLeft" class="w-4 h-4" />
        </Pagination.Link>

        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page === currentPage"
          @click="fetchCustomers(page)"
        >
          {{ page }}
        </Pagination.Link>

        <Pagination.Link
          :disabled="currentPage === totalPages"
          @click="fetchCustomers(currentPage + 1)"
        >
          <Lucide icon="ChevronRight" class="w-4 h-4" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import { RouterLink } from 'vue-router'

import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

type Customer = {
  id_customer: number
  nama_perusahaan?: string
  alamat_perusahaan?: string
  telepon?: string
  fax?: string
  status_customer?: number
  has_lcr?: boolean
  jumlah_penawaran?: number
  user?: {
    name?: string
  }
  cabang?: {
    nama_cabang?: string
  }
}

const customers = ref<Customer[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const totalPages = ref(1)
const totalRecords = ref(0)

async function fetchCustomers(page = 1) {
  loading.value = true
  error.value = null

  try {
    const res = await axios.get('/api/customers', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
      },
    })

    customers.value = res.data.data ?? []
    currentPage.value = res.data.current_page ?? 1
    totalPages.value = res.data.last_page ?? 1
    totalRecords.value = res.data.total ?? customers.value.length
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load customers'
    customers.value = []
  } finally {
    loading.value = false
  }
}

function getStatusLabel(status?: number) {
  if (status === 1) return 'Prospect'
  if (status === 2) return 'Tetap'
  return '-'
}

function getStatusBadgeClass(status?: number) {
  if (status === 1) return 'bg-amber-100 text-amber-700'
  if (status === 2) return 'bg-emerald-100 text-emerald-700'
  return 'bg-slate-100 text-slate-500'
}

const totalCustomers = computed(() => totalRecords.value)

const totalProspect = computed(() =>
  customers.value.filter((c) => c.status_customer === 1).length
)

const totalTetap = computed(() =>
  customers.value.filter((c) => c.status_customer === 2).length
)

const totalPenawaran = computed(() =>
  customers.value.reduce((sum, c) => sum + Number(c.jumlah_penawaran ?? 0), 0)
)

async function confirmDelete(id: number) {
  const result = await Swal.fire({
    title: 'Apakah Anda yakin?',
    text: 'Data customer yang dihapus tidak dapat dikembalikan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    reverseButtons: true,
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/api/customers/${id}`)

    Swal.fire({
      icon: 'success',
      title: 'Deleted',
      text: 'Customer berhasil dihapus.',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 1500,
    })

    fetchCustomers(currentPage.value)
  } catch (e: any) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: e.response?.data?.message || 'Gagal menghapus customer.',
    })
  }
}

onMounted(() => {
  fetchCustomers()
})

watch(
  searchQuery,
  debounce(() => {
    fetchCustomers(1)
  }, 300)
)

watch(perPage, () => {
  fetchCustomers(1)
})
</script>