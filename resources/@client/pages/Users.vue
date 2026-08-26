<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, email, requiredIf } from '@vuelidate/validators'
import { debounce } from 'lodash'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormCheck } from '@/components/Base/Form'
import TomSelect from '@/components/Base/TomSelect'
import { Dialog } from '@/components/Base/Headless'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'

/* Section: Types */
interface Role {
  id: number
  name: string
}

interface Cabang {
  id_cabang: number
  nama_cabang: string
}

interface User {
  id: number
  name: string
  email: string
  no_telepon: string | null
  is_active: boolean
  id_cabang: number | null
  primary_role?: Role
  roles?: Role[]
  cabang?: Cabang
}

/* Section: API instances */
const userApi = createResourceApi('/users')
const roleApi = createResourceApi('/roles')
const cabangApi = createResourceApi('/cabangs')
const { success, error } = useNotification()
const router = useRouter()
const auth = useAuthStore()

/* State: data & pagination */
const allUsers = ref<User[]>([])
const rolesList = ref<Role[]>([])
const cabangList = ref<Cabang[]>([])
const loading = ref(false)

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

/* State: create/edit form */
const form = reactive({
  id: 0,
  name: '',
  email: '',
  no_telepon: '',
  password: '',
  role_ids: [] as string[],
  primary_role_id: null as string | null,
  id_cabang: null as number | null,
  is_active: true,
})
const isEdit = ref(false)
const formError = ref<string | null>(null)
const formLoading = ref(false)
const createModal = ref(false)

const rules = {
  name: {
    required: helpers.withMessage('Nama wajib diisi', required),
  },
  email: {
    required: helpers.withMessage('Email wajib diisi', required),
    email: helpers.withMessage('Format email tidak valid', email),
  },
  password: {
    required: helpers.withMessage('Password wajib diisi', requiredIf(() => !isEdit.value)),
  },
  role_ids: {
    required: helpers.withMessage('Minimal satu role wajib dipilih', (v: string[]) => v.length > 0),
  },
  id_cabang: {
    required: helpers.withMessage('Cabang wajib dipilih', required),
  },
}

const v$ = useVuelidate(rules, form)

/* State: reset password */
const resetModal = ref(false)
const resetLoading = ref(false)
const resetForm = reactive({
  id: 0,
  name: '',
  password: '',
})

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const userToDelete = ref<number | null>(null)

/* State: impersonate */
const impersonateModal = ref(false)
const impersonateTarget = ref<User | null>(null)
const impersonateLoading = ref(false)

/* Section: Computed */
const filteredUsers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allUsers.value

  return allUsers.value.filter(item => {
    return [
      item.name,
      item.email,
      item.no_telepon,
      item.primary_role?.name,
      item.cabang?.nama_cabang,
    ].some(value => String(value || '').toLowerCase().includes(query))
  })
})

const totalRecords = computed(() => filteredUsers.value.length)

const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / perPage.value)))

const users = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredUsers.value.slice(start, start + perPage.value)
})

const totalUsers = computed(() => allUsers.value.length)
const activeUsers = computed(() => allUsers.value.filter(u => !!u.is_active).length)
const inactiveUsers = computed(() => allUsers.value.filter(u => !u.is_active).length)

const canImpersonate = computed((): boolean => auth.can('admin.users.impersonate'))

const primaryRoleOptions = computed(() => rolesList.value.filter(r => form.role_ids.includes(String(r.id))))

/* Section: Lifecycle & watch */
onMounted(() => {
  fetchData()
  fetchRolesList()
  fetchCabangList()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

// role utama harus salah satu dari role_ids yang dipilih -- reset otomatis kalau yang lama sudah tidak dicentang
watch(() => form.role_ids, (roleIds) => {
  if (form.primary_role_id && roleIds.includes(form.primary_role_id)) return
  form.primary_role_id = roleIds[0] ?? null
})

/* Section: Data fetching */
async function fetchData() {
  loading.value = true
  try {
    const { data } = await userApi.getAll({ as_list: true })
    allUsers.value = Array.isArray(data) ? data : []
  } catch {
    allUsers.value = []
  } finally {
    loading.value = false
  }
}

async function fetchRolesList() {
  try {
    const { data } = await roleApi.getAll({ per_page: 1000 })
    rolesList.value = data.data
  } catch {}
}

async function fetchCabangList() {
  try {
    const { data } = await cabangApi.getAll({ as_list: true })
    cabangList.value = Array.isArray(data) ? data : []
  } catch {}
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

/* Section: Action handlers - create/edit */
function getFieldError(field: keyof typeof rules) {
  return v$.value[field]?.$errors[0]?.$message?.toString() || ''
}

function openCreate() {
  isEdit.value = false
  formError.value = null
  Object.assign(form, {
    id: 0,
    name: '',
    email: '',
    no_telepon: '',
    password: '',
    role_ids: [],
    primary_role_id: null,
    id_cabang: null,
    is_active: true,
  })
  v$.value.$reset()
  createModal.value = true
}

function openEdit(user: User) {
  isEdit.value = true
  formError.value = null
  Object.assign(form, {
    id: user.id,
    name: user.name,
    email: user.email,
    no_telepon: user.no_telepon || '',
    password: '',
    role_ids: (user.roles ?? []).map(r => String(r.id)),
    primary_role_id: user.primary_role ? String(user.primary_role.id) : null,
    id_cabang: user.id_cabang,
    is_active: user.is_active,
  })
  v$.value.$reset()
  createModal.value = true
}

function buildRolePayload() {
  return {
    role_ids: form.role_ids.map(Number),
    primary_role_id: form.primary_role_id ? Number(form.primary_role_id) : null,
  }
}

async function submitCreate() {
  const isValid = await v$.value.$validate()
  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  formLoading.value = true
  try {
    await userApi.store({ ...form, ...buildRolePayload() })
    await fetchData()
    createModal.value = false
    success('Berhasil', 'User berhasil dibuat')
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Failed to create user'
  } finally {
    formLoading.value = false
  }
}

async function submitEdit() {
  const isValid = await v$.value.$validate()
  if (!isValid) {
    error('Gagal', 'Periksa kembali data yang wajib diisi')
    return
  }

  formLoading.value = true
  try {
    await userApi.update(form.id, { ...form, ...buildRolePayload() })
    await fetchData()
    createModal.value = false
    success('Berhasil', 'User berhasil diperbarui')
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Failed to update user'
  } finally {
    formLoading.value = false
  }
}

function cancelModal() {
  createModal.value = false
}

/* Section: Action handlers - reset password */
function openResetPassword(user: User) {
  resetForm.id = user.id
  resetForm.name = user.name
  resetForm.password = ''
  resetModal.value = true
}

function generatePassword() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$'
  let result = ''
  for (let i = 0; i < 10; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  resetForm.password = result
}

async function submitResetPassword() {
  if (!resetForm.password) {
    return error('Gagal', 'Password baru wajib diisi')
  }

  resetLoading.value = true
  try {
    // Endpoint custom di luar CRUD standar users, tidak ditangani createResourceApi.
    await axios.put(`/api/users/${resetForm.id}/reset-password`, {
      password: resetForm.password,
    })

    resetModal.value = false
    success('Password berhasil direset', `Password baru: ${resetForm.password}`)
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Failed to reset password')
  } finally {
    resetLoading.value = false
  }
}

/* Section: Action handlers - delete */
function confirmDelete(id: number) {
  userToDelete.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!userToDelete.value) return
  deleteLoading.value = true
  try {
    await userApi.destroy(userToDelete.value)
    await fetchData()
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }
    deleteModal.value = false
    success('Berhasil', 'User berhasil dihapus')
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Failed to delete user')
  } finally {
    deleteLoading.value = false
    userToDelete.value = null
  }
}

/* Section: Action handlers - impersonate */
function openImpersonateConfirm(user: User) {
  impersonateTarget.value = user
  impersonateModal.value = true
}

function cancelImpersonate() {
  impersonateModal.value = false
  impersonateTarget.value = null
}

async function submitImpersonate() {
  if (!impersonateTarget.value) return

  impersonateLoading.value = true
  try {
    const { data } = await axios.post(`/api/users/${impersonateTarget.value.id}/impersonate`)

    auth.setToken(data.access_token)
    impersonateModal.value = false

    window.location.href = router.resolve({ name: 'dashboard-overview-1' }).href
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal impersonate user')
  } finally {
    impersonateLoading.value = false
  }
}

/* Section: Helpers */
function getInitials(name: string) {
  if (!name) return 'U'
  return name
    .split(' ')
    .slice(0, 2)
    .map(part => part.charAt(0).toUpperCase())
    .join('')
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="User Management"
        description="Kelola akun pengguna, role, cabang, status aktif, dan reset password.">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Add New User
          </Button>
        </template>
      </PageHeader>

      <!-- Summary -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Total Users</p>
              <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ totalUsers }}</h3>
            </div>
            <div class="rounded-full bg-primary/10 p-3 text-primary">
              <Lucide icon="Users" class="h-5 w-5" />
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Active Users</p>
              <h3 class="mt-1 text-2xl font-bold text-emerald-600">{{ activeUsers }}</h3>
            </div>
            <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
              <Lucide icon="BadgeCheck" class="h-5 w-5" />
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-500">Inactive Users</p>
              <h3 class="mt-1 text-2xl font-bold text-rose-600">{{ inactiveUsers }}</h3>
            </div>
            <div class="rounded-full bg-rose-100 p-3 text-rose-600">
              <Lucide icon="UserX" class="h-5 w-5" />
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table List -->
      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="users.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Search name, email, role..."
        loading-text="Loading users..." empty-description="No users found." @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>User</Table.Th>
          <Table.Th>No Telepon</Table.Th>
          <Table.Th>Cabang</Table.Th>
          <Table.Th>Role</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Actions</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(user, idx) in users" :key="user.id" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>

            <Table.Td>
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <div class="font-medium text-slate-800">{{ user.name }}</div>
                  <div class="text-sm text-slate-500">{{ user.email }}</div>
                </div>
              </div>
            </Table.Td>

            <Table.Td>{{ user.no_telepon || '-' }}</Table.Td>
            <Table.Td>{{ user.cabang?.nama_cabang || '-' }}</Table.Td>
            <Table.Td>{{ user.primary_role?.name || '-' }}</Table.Td>

            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1"
                :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" @click="openEdit(user)"
                  title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button variant="soft-warning" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="openResetPassword(user)" title="Reset Password">
                  <Lucide icon="KeyRound" class="h-4 w-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="confirmDelete(user.id)" title="Delete">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
                <Button v-if="canImpersonate" variant="soft-info" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  :disabled="user.primary_role?.id === 1 || !user.is_active || Number(user.id) === Number(auth.user?.id)"
                  @click="openImpersonateConfirm(user)" title="Impersonate">
                  <Lucide icon="LogIn" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>

    <!-- Create/Edit Modal -->
    <Dialog v-model:open="createModal">
      <Dialog.Panel class="w-full max-w-lg p-0 overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-800">
            {{ isEdit ? 'Edit User' : 'Add New User' }}
          </h3>
          <p class="mt-1 text-sm text-slate-500">
            Lengkapi data user di bawah ini.
          </p>
        </div>

        <div class="p-6">
          <p v-if="formError" class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600">
            {{ formError }}
          </p>

          <div class="space-y-4">
            <div>
              <FormInput v-model="form.name" placeholder="Name" :class="getFieldError('name') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('name')" class="font-caption !text-rose-600">{{ getFieldError('name') }}</small>
            </div>

            <div>
              <FormInput v-model="form.email" placeholder="Email" :class="getFieldError('email') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('email')" class="font-caption !text-rose-600">{{ getFieldError('email') }}</small>
            </div>

            <FormInput v-model="form.no_telepon" placeholder="No Telepon" />

            <div v-if="!isEdit">
              <FormInput v-model="form.password" type="password" placeholder="Password"
                :class="getFieldError('password') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('password')" class="font-caption !text-rose-600">{{ getFieldError('password') }}</small>
            </div>

            <div>
              <FormSelect v-model="form.id_cabang" :class="getFieldError('id_cabang') ? 'border-rose-500' : ''">
                <option disabled value="">— Select Cabang —</option>
                <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
                  {{ c.nama_cabang }}
                </option>
              </FormSelect>
              <small v-if="getFieldError('id_cabang')" class="font-caption !text-rose-600">{{ getFieldError('id_cabang') }}</small>
            </div>

            <div>
              <label class="font-label mb-1 block text-slate-700">Roles</label>
              <TomSelect v-model="form.role_ids" multiple class="w-full"
                :options="{ placeholder: 'Pilih satu atau lebih role...', dropdownParent: 'body', onDelete: () => true }"
                :class="getFieldError('role_ids') ? 'border-rose-500' : ''">
                <option v-for="r in rolesList" :key="r.id" :value="String(r.id)">
                  {{ r.name }}
                </option>
              </TomSelect>
              <small v-if="getFieldError('role_ids')" class="font-caption !text-rose-600">{{ getFieldError('role_ids') }}</small>
            </div>

            <div v-if="form.role_ids.length > 1">
              <label class="font-label mb-1 block text-slate-700">Role Utama</label>
              <FormSelect v-model="form.primary_role_id">
                <option v-for="r in primaryRoleOptions" :key="r.id" :value="String(r.id)">
                  {{ r.name }}
                </option>
              </FormSelect>
              <small class="font-caption block text-slate-400">Menentukan brand & tab dashboard default untuk user ini.</small>
            </div>

            <label class="flex items-center rounded-lg border border-slate-200 px-3 py-3">
              <FormCheck.Input v-model="form.is_active" type="checkbox" class="mr-3" />
              <span class="text-sm text-slate-700">Active User</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
          <Button variant="outline-secondary" @click="cancelModal">Cancel</Button>
          <Button variant="primary" :loading="formLoading" @click="isEdit ? submitEdit() : submitCreate()">
            {{ isEdit ? 'Save Changes' : 'Create User' }}
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <!-- Reset Password Modal -->
    <Dialog v-model:open="resetModal">
      <Dialog.Panel class="w-full max-w-md p-0 overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-800">Reset Password</h3>
          <p class="mt-1 text-sm text-slate-500">
            Reset password untuk user:
            <span class="font-medium text-slate-700">{{ resetForm.name }}</span>
          </p>
        </div>

        <div class="p-6 space-y-4">
          <FormInput v-model="resetForm.password" type="text" placeholder="Masukkan password baru" />

          <div class="flex gap-2">
            <Button variant="outline-secondary" class="w-full" @click="generatePassword">
              Generate Password
            </Button>
          </div>

          <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">
            Password baru akan langsung menggantikan password lama user.
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
          <Button variant="outline-secondary" @click="resetModal = false">Cancel</Button>
          <Button variant="primary" :loading="resetLoading" @click="submitResetPassword">
            Reset Password
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <!-- Impersonate Confirm Modal -->
    <Dialog v-model:open="impersonateModal">
      <Dialog.Panel class="w-full max-w-md p-0 overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-800">Impersonate User</h3>
        </div>

        <div class="p-6">
          <p class="text-sm text-slate-600">
            Anda akan masuk sebagai
            <span class="font-medium text-slate-800">{{ impersonateTarget?.name }}</span>.
            Sesi ini berlaku 2 jam dan bisa diakhiri kapan saja lewat tombol Kembali ke Admin.
          </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
          <Button variant="outline-secondary" @click="cancelImpersonate">Cancel</Button>
          <Button variant="primary" :loading="impersonateLoading" @click="submitImpersonate">
            Impersonate
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <DeleteRecordDialog :open="deleteModal" title="Hapus User"
      description="Data user yang dihapus tidak bisa dikembalikan." :loading="deleteLoading"
      @close="deleteModal = false" @confirm="submitDelete" />
  </div>
</template>
