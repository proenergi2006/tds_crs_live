<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import RoleFormModal from './Form.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const roleApi = createResourceApi('/roles')
const { success, error } = useNotification()

/* State: data & pagination */
const allRoles = ref<any[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const loading = ref(false)

/* State: form */
const formModal = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedRole = ref<any | null>(null)

/* State: deactivate (soft-delete) */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

onMounted(() => {
  fetchData()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

const filteredRoles = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allRoles.value

  return allRoles.value.filter(item => {
    return [
      item.role_name,
      item.role_desc,
      item.is_active ? 'active' : 'inactive',
    ].some(value => String(value || '').toLowerCase().includes(query))
  })
})

const totalRecords = computed(() => filteredRoles.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const roles = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredRoles.value.slice(start, start + perPage.value)
})

/* Data */
async function fetchData() {
  loading.value = true

  try {
    const { data } = await roleApi.getAll({
      as_list: true,
    })

    allRoles.value = Array.isArray(data) ? data : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data role')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

/* Form */
function openCreate() {
  formMode.value = 'create'
  selectedRole.value = null
  formModal.value = true
}

function openEdit(target: any) {
  formMode.value = 'edit'
  selectedRole.value = target
  formModal.value = true
}

function handleFormSuccess(data: any, mode: 'create' | 'edit') {
  syncRole(data, mode)
  formModal.value = false
}

function syncRole(data: any, mode: 'create' | 'edit') {
  if (mode === 'create') {
    allRoles.value.unshift(data)
    return
  }

  const index = allRoles.value.findIndex(
    item => item.id_role === data.id_role,
  )

  if (index !== -1) {
    allRoles.value[index] = data
  }
}

/* Deactivate (soft-delete: is_active = false, row is kept) */
function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await roleApi.destroy(deleteTarget.value)

    const index = allRoles.value.findIndex(
      item => item.id_role === deleteTarget.value,
    )

    if (index !== -1) {
      allRoles.value[index].is_active = false
    }

    deleteModal.value = false
    success('Berhasil', 'Role berhasil dinonaktifkan.')
  } catch (e: any) {
    error(
      'Gagal menonaktifkan',
      e.response?.data?.message ?? 'Terjadi kesalahan saat menonaktifkan role.',
    )
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- Page Header -->
      <PageHeader title="Role Management" description="Kelola data role dan akses pengguna dalam sistem.">
        <template #action>
          <div class="flex items-center gap-2">
            <RouterLink :to="{ name: 'permission-overview' }">
              <Button variant="white" class="inline-flex items-center gap-2">
                <Lucide icon="ShieldCheck" class="h-4 w-4" />
                Kelola Permission
              </Button>
            </RouterLink>
            <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
              <Lucide icon="Plus" class="h-4 w-4" />
              Tambah Role
            </Button>
          </div>
        </template>
      </PageHeader>

      <!-- Data Table List -->
      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="roles.length === 0" :colspan="5" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari role..."
        loading-text="Memuat data role..." empty-description="Belum ada role untuk ditampilkan."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Role</Table.Th>
          <Table.Th>Deskripsi</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in roles" :key="item.id_role" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              {{ item.role_name }}
            </Table.Td>
            <Table.Td class="text-slate-600">
              {{ item.role_desc || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1"
                :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" @click.prevent="openEdit(item)"
                  title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="confirmDelete(item.id_role)" title="Nonaktifkan">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create/Edit Modal -->
      <RoleFormModal :open="formModal" :mode="formMode" :item="selectedRole" @close="formModal = false"
        @success="handleFormSuccess" />

      <!-- Deactivate Confirmation Modal (soft-delete: is_active = false, row is kept) -->
      <DeleteRecordDialog :open="deleteModal" title="Nonaktifkan Role?"
        description="Role ini akan dinonaktifkan (bukan dihapus permanen) dan tidak dapat digunakan untuk login."
        confirm-text="Nonaktifkan" :loading="deleteLoading" @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
