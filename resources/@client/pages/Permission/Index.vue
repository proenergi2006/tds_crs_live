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
        <!-- Master Permission (read-only) -->
        <Tab.Panel>
          <div v-if="isLoading" class="flex items-center justify-center py-24 text-slate-500">
            <Lucide icon="Loader" class="mr-2 h-5 w-5 animate-spin" />
            Memuat data permission...
          </div>

          <div v-else class="space-y-6">
            <CardSection
              v-for="group in permissionGroups"
              :key="group.module"
              :title="group.module"
              icon="Shield"
            >
              <div class="divide-y divide-slate-100">
                <div
                  v-for="perm in group.permissions"
                  :key="perm.id"
                  class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                >
                  <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-slate-800">{{ perm.name }}</div>
                    <div class="text-xs text-slate-500">{{ perm.description || '-' }}</div>
                  </div>

                  <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shrink-0 !shadow-none" title="Edit"
                    @click="openEdit(perm)">
                    <Lucide icon="Edit" class="h-4 w-4" />
                  </Button>
                </div>

                <div v-if="!group.permissions.length" class="py-3 text-sm text-slate-500">
                  Tidak ada permission pada module ini.
                </div>
              </div>
            </CardSection>

            <div
              v-if="!permissionGroups.length"
              class="rounded-lg bg-white p-6 text-center text-sm text-slate-500 shadow-sm"
            >
              Belum ada data permission.
            </div>
          </div>
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
import { ref, onMounted } from 'vue'
import { Tab } from '@/components/Base/Headless'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
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

/* Section: Form state */
const formModal = ref(false)
const selectedPermission = ref<Permission | null>(null)

/* Section: Data fetching */
async function loadPermissions() {
  isLoading.value = true
  try {
    const { data } = await permissionApi.getAll()
    permissionGroups.value = data.data as PermissionGroup[]
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
