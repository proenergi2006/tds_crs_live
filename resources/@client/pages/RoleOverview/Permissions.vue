<template>
  <div class="p-6">
    <PageHeader title="Permission Matrix"
      description="Kelola assignment permission per role. Centang untuk memberikan akses, kosongkan untuk mencabut.">
      <template #action>
        <RouterLink :to="{ name: 'role-overview' }">
          <Button variant="outline-secondary" class="inline-flex items-center gap-2">
            <Lucide icon="ArrowLeft" class="h-4 w-4" />
            Kembali ke Role
          </Button>
        </RouterLink>
      </template>
    </PageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-24 text-slate-500">
      <Lucide icon="Loader" class="mr-2 h-5 w-5 animate-spin" />
      Memuat permission matrix...
    </div>

    <template v-else>
      <!-- Matrix Table -->
      <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full min-w-max divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th
                  class="min-w-[260px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Permission
                </th>
                <th v-for="role in roles" :key="role.id_role"
                  class="min-w-[110px] px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                  <span class="block max-w-[100px] break-words leading-tight">{{ role.role_name }}</span>
                </th>
              </tr>
            </thead>

            <tbody>
              <template v-for="group in permissionGroups" :key="group.module">
                <!-- Module header row -->
                <tr class="border-t border-slate-200 bg-slate-100">
                  <td :colspan="roles.length + 1" class="px-5 py-2">
                    <span class="text-xs font-bold uppercase tracking-widest text-slate-600">
                      {{ group.module }}
                    </span>
                  </td>
                </tr>

                <!-- Permission rows -->
                <tr v-for="perm in group.permissions" :key="perm.id"
                  class="border-t border-slate-100 transition hover:bg-slate-50">
                  <td class="min-w-[260px] px-5 py-3">
                    <div class="text-sm font-medium text-slate-800">{{ perm.name }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ perm.description }}</div>
                  </td>
                  <td v-for="role in roles" :key="role.id_role" class="px-3 py-3 text-center">
                    <input type="checkbox" class="h-4 w-4 cursor-pointer rounded border-slate-300"
                      :checked="matrix[perm.id]?.[role.id_role] ?? false"
                      @change="toggle(perm.id, role.id_role, ($event.target as HTMLInputElement).checked)" />
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Sticky save bar -->
      <div
        class="sticky bottom-0 -mx-6 -mb-6 flex items-center justify-between border-t border-slate-200 bg-white px-6 py-4"
        style="box-shadow: 0 -2px 8px rgba(0,0,0,0.06)">
        <span class="text-sm text-slate-500">
          <template v-if="isDirty">
            <span class="font-semibold text-amber-600">{{ changedRoleCount }} role</span>
            memiliki perubahan yang belum disimpan.
          </template>
          <template v-else>
            Tidak ada perubahan.
          </template>
        </span>

        <Button variant="primary" :disabled="!isDirty || isSaving" class="inline-flex items-center gap-2"
          @click="saveChanges">
          <Lucide v-if="isSaving" icon="Loader" class="h-4 w-4 animate-spin" />
          Save Changes
        </Button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

/* Section: Types */
interface Permission {
  id: number
  name: string
  module: string
  description: string
}

interface PermissionGroup {
  module: string
  permissions: Permission[]
}

interface RoleItem {
  id_role: number
  role_name: string
  is_active: boolean
}

/* Section: State */
const { success, error } = useNotification()

const isLoading = ref(true)
const isSaving = ref(false)

const permissionGroups = ref<PermissionGroup[]>([])
const roles = ref<RoleItem[]>([])

// matrix[permId][roleId] = boolean
const matrix = ref<Record<number, Record<number, boolean>>>({})
const originalMatrix = ref<Record<number, Record<number, boolean>>>({})

/* Section: Computed */
const allPermIds = computed(() =>
  permissionGroups.value.flatMap(g => g.permissions.map(p => p.id))
)

const isDirty = computed(() =>
  JSON.stringify(matrix.value) !== JSON.stringify(originalMatrix.value)
)

const changedRoleCount = computed(() =>
  roles.value.filter(role => {
    const roleId = role.id_role
    return allPermIds.value.some(
      permId =>
        (matrix.value[permId]?.[roleId] ?? false) !==
        (originalMatrix.value[permId]?.[roleId] ?? false),
    )
  }).length
)

/* Section: Helpers */
function toggle(permId: number, roleId: number, checked: boolean) {
  if (!matrix.value[permId]) matrix.value[permId] = {}
  matrix.value[permId][roleId] = checked
}

function getPermissionIdsForRole(
  roleId: number,
  src: Record<number, Record<number, boolean>>,
): number[] {
  return allPermIds.value.filter(permId => src[permId]?.[roleId] === true)
}

function snapshotMatrix(): Record<number, Record<number, boolean>> {
  return JSON.parse(JSON.stringify(matrix.value))
}

/* Section: Data fetching */
async function loadData() {
  isLoading.value = true
  try {
    const [permRes, roleRes] = await Promise.all([
      axios.get('/api/permissions'),
      axios.get('/api/roles', { params: { per_page: 200 } }),
    ])

    permissionGroups.value = permRes.data.data as PermissionGroup[]
    roles.value = (roleRes.data.data as RoleItem[]).filter(r => r.id_role !== 1)

    // Inisialisasi matrix dengan false untuk setiap pasangan (permId, roleId)
    permissionGroups.value.forEach(group => {
      group.permissions.forEach(perm => {
        matrix.value[perm.id] = {}
        roles.value.forEach(role => {
          matrix.value[perm.id][role.id_role] = false
        })
      })
    })

    // Fetch permissions tiap role secara paralel
    await Promise.all(
      roles.value.map(async role => {
        const { data } = await axios.get(`/api/roles/${role.id_role}/permissions`)
          ; (data.data as number[]).forEach(permId => {
            if (matrix.value[permId]) {
              matrix.value[permId][role.id_role] = true
            }
          })
      }),
    )

    originalMatrix.value = snapshotMatrix()
  } catch {
    error('Gagal memuat permission matrix')
  } finally {
    isLoading.value = false
  }
}

/* Section: Save */
async function saveChanges() {
  if (!isDirty.value || isSaving.value) return

  const changedRoles = roles.value.filter(role => {
    const roleId = role.id_role
    return allPermIds.value.some(
      permId =>
        (matrix.value[permId]?.[roleId] ?? false) !==
        (originalMatrix.value[permId]?.[roleId] ?? false),
    )
  })

  isSaving.value = true
  try {
    await Promise.all(
      changedRoles.map(role =>
        axios.put(`/api/roles/${role.id_role}/permissions`, {
          permission_ids: getPermissionIdsForRole(role.id_role, matrix.value),
        }),
      ),
    )

    originalMatrix.value = snapshotMatrix()
    success('Permissions berhasil disimpan')
  } catch {
    error('Gagal menyimpan permissions')
  } finally {
    isSaving.value = false
  }
}

/* Section: Lifecycle */
onMounted(loadData)
</script>
