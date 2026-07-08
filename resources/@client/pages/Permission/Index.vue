<template>
  <div class="p-6">
    <PageHeader
      title="Permission"
      description="Lihat daftar master permission dan kelola assignment permission per role."
    />

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
        <!-- Master Permission (read-only, flat list, edit module/description only) -->
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
              <Table.Tr v-for="perm in paginatedPermissions" :key="perm.id" class="transition hover:bg-slate-50">
                <Table.Td>
                  <span class="font-label inline-flex rounded-full bg-primary/10 px-3 py-1 text-primary">
                    {{ perm.module }}
                  </span>
                </Table.Td>
                <Table.Td class="font-medium text-slate-800">
                  {{ perm.name }}
                </Table.Td>
                <Table.Td class="text-slate-600">
                  {{ perm.description || '-' }}
                </Table.Td>
                <Table.Td class="text-center">
                  <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Edit"
                    @click="openEdit(perm)">
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </Button>
                </Table.Td>
              </Table.Tr>
            </template>
          </DataList>
        </Tab.Panel>

        <!-- Permission Matrix -->
        <Tab.Panel>
          <PermissionMatrix />
        </Tab.Panel>
      </Tab.Panels>
    </Tab.Group>

    <!-- Edit Modal (module + description only) -->
    <PermissionFormModal :open="formModal" :item="selectedPermission" @close="formModal = false"
      @success="handleFormSuccess" />
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
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PermissionMatrix from './Matrix.vue'
import PermissionFormModal from './Form.vue'
import { createResourceApi } from '@/utils/resourceApi.js'

const permissionApi = createResourceApi('/permissions')

/* Section: Types */
interface Permission {
  id: number
  name: string
  guard_name?: string
  module: string
  description: string
}

interface PermissionGroup {
  module: string
  permissions: Permission[]
}

/* Section: State */
const isLoading = ref(true)
const permissionGroups = ref<PermissionGroup[]>([])

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

/* Section: Form state */
const formModal = ref(false)
const selectedPermission = ref<Permission | null>(null)

/* Section: Computed — flatten grouped-by-module response into a single list */
const allPermissions = computed<Permission[]>(() =>
  permissionGroups.value.flatMap(group => group.permissions),
)

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

/* Section: Data fetching */
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

/* Section: Form actions */
function openEdit(perm: Permission) {
  selectedPermission.value = perm
  formModal.value = true
}

function handleFormSuccess() {
  // Module may have changed, which moves the permission between groups —
  // simplest correct approach is to re-fetch the grouped list from the
  // backend rather than patch the grouping client-side.
  formModal.value = false
  loadPermissions()
}

/* Section: Lifecycle */
onMounted(loadPermissions)
</script>
