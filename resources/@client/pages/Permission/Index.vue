<template>
  <div class="p-6">
    <PageHeader title="Permission"
      description="Lihat daftar master permission dan kelola assignment permission per role.">
      <template #action>
        <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
          <Lucide icon="Plus" class="w-4 h-4" />
          Tambah Permission
        </Button>
      </template>
    </PageHeader>

    <Tab.Group class="mt-4">
      <Tab.List variant="link-tabs">
        <Tab>
          <Tab.Button>Master Permission</Tab.Button>
        </Tab>
        <Tab>
          <Tab.Button>Permission Matrix</Tab.Button>
        </Tab>
      </Tab.List>

      <Tab.Panels class="mt-4">
        <Tab.Panel>
          <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="isLoading"
            :empty="paginatedPermissions.length === 0" :colspan="4" :show-footer="true" :show-toolbar="true"
            :total="totalRecords" :current-page="currentPage" :total-pages="totalPages"
            search-placeholder="Cari permission..." loading-text="Memuat data permission..."
            empty-description="Belum ada data permission." @page-change="goToPage">
            <template #head>
              <Table.Th>Module</Table.Th>
              <Table.Th>Name</Table.Th>
              <Table.Th>Description</Table.Th>
              <Table.Th class="text-center">Aksi</Table.Th>
            </template>

            <template #body>
              <Table.Tr v-for="perm in paginatedPermissions" :key="perm.id" class="hover:bg-slate-50 transition">
                <Table.Td>
                  <span class="inline-flex bg-primary/10 px-3 py-1 rounded-full text-form-label text-primary">
                    {{ perm.module }}
                  </span>
                </Table.Td>
                <Table.Td class="font-medium text-slate-800">
                  <div class="flex items-center gap-2">
                    <span>{{ perm.name }}</span>
                    <span class="inline-flex bg-slate-100 px-2 py-0.5 rounded-full text-caption text-slate-600"
                      :title="`Digunakan oleh ${perm.roles_count ?? 0} role`">
                      {{ perm.roles_count ?? 0 }} role
                    </span>
                  </div>
                </Table.Td>
                <Table.Td class="text-slate-600">
                  {{ perm.description || '-' }}
                </Table.Td>
                <Table.Td class="text-center">
                  <div class="inline-flex justify-center items-center gap-2">
                    <Button variant="soft-warning" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Edit"
                      @click="openEdit(perm)">
                      <Lucide icon="Edit" class="w-4 h-4" />
                    </Button>
                    <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                      @click="confirmDelete(perm)">
                      <Lucide icon="Trash2" class="w-4 h-4" />
                    </Button>
                  </div>
                </Table.Td>
              </Table.Tr>
            </template>
          </DataList>
        </Tab.Panel>

        <Tab.Panel>
          <PermissionMatrix />
        </Tab.Panel>
      </Tab.Panels>
    </Tab.Group>

    <PermissionFormModal :open="formModal" :item="selectedPermission" :module-options="moduleOptions"
      @close="formModal = false" @success="handleFormSuccess" />

    <DeleteRecordDialog :open="deleteModal" title="Hapus Permission?" :description="deleteDescription"
      :loading="deleteLoading" @close="deleteModal = false" @confirm="submitDelete" />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import { debounce } from 'lodash'
import { Tab } from '@/components/Base/Headless'
import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PermissionMatrix from './Matrix.vue'
import PermissionFormModal from './Form.vue'
import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const permissionApi = createResourceApi('/permissions')
const { success, error: notifyError } = useNotification()

interface Permission {
  id: number
  name: string
  guard_name?: string
  module: string
  description: string
  roles_count?: number
}

interface PermissionGroup {
  module: string
  permissions: Permission[]
}

const isLoading = ref(true)
const permissionGroups = ref<PermissionGroup[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

const formModal = ref(false)
const selectedPermission = ref<Permission | null>(null)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<Permission | null>(null)

const allPermissions = computed<Permission[]>(() =>
  permissionGroups.value.flatMap(group => group.permissions),
)

const moduleOptions = computed<string[]>(() =>
  Array.from(new Set(permissionGroups.value.map(group => group.module))).sort(),
)

const deleteDescription = computed(() => {
  const count = deleteTarget.value?.roles_count ?? 0

  return count > 0
    ? `Permission "${deleteTarget.value?.name}" digunakan oleh ${count} role.`
    : `Permission "${deleteTarget.value?.name}" belum digunakan role manapun.`
})

const filteredPermissions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allPermissions.value

  return allPermissions.value.filter(perm => {
    return [perm.module, perm.name, perm.description].some(value =>
      String(value || '').toLowerCase().includes(query),
    )
  })
})

const totalRecords = computed(() => filteredPermissions.value.length)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalRecords.value / perPage.value)),
)

const paginatedPermissions = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredPermissions.value.slice(start, start + perPage.value)
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

function resetToFirstPage() {
  currentPage.value = 1
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

async function loadPermissions() {
  isLoading.value = true
  try {
    const { data } = await permissionApi.getAll()
    permissionGroups.value = data.data as PermissionGroup[]
    currentPage.value = 1
  } catch {
    permissionGroups.value = []
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  selectedPermission.value = null
  formModal.value = true
}

function openEdit(perm: Permission) {
  selectedPermission.value = perm
  formModal.value = true
}

function handleFormSuccess() {
  formModal.value = false
  loadPermissions()
}

function confirmDelete(perm: Permission) {
  deleteTarget.value = perm
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await permissionApi.destroy(deleteTarget.value.id)

    deleteModal.value = false
    success('Berhasil', 'Permission berhasil dihapus')
    loadPermissions()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus permission.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

onMounted(loadPermissions)
</script>
