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

const userApi = createResourceApi('/users')
const roleApi = createResourceApi('/roles')
const cabangApi = createResourceApi('/cabangs')
const { success, error } = useNotification()
const router = useRouter()
const auth = useAuthStore()

const allUsers = ref<User[]>([])
const rolesList = ref<Role[]>([])
const cabangList = ref<Cabang[]>([])
const loading = ref(false)

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

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

const resetModal = ref(false)
const resetLoading = ref(false)
const resetForm = reactive({
  id: 0,
  name: '',
  password: '',
})

const deleteModal = ref(false)
const deleteLoading = ref(false)
const userToDelete = ref<number | null>(null)

const impersonateModal = ref(false)
const impersonateTarget = ref<User | null>(null)
const impersonateLoading = ref(false)

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

onMounted(() => {
  fetchData()
  fetchRolesList()
  fetchCabangList()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

watch(() => form.role_ids, (roleIds) => {
  if (form.primary_role_id && roleIds.includes(form.primary_role_id)) return
  form.primary_role_id = roleIds[0] ?? null
})

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
  } catch { }
}

async function fetchCabangList() {
  try {
    const { data } = await cabangApi.getAll({ as_list: true })
    cabangList.value = Array.isArray(data) ? data : []
  } catch { }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

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
    <div class="flex flex-col gap-4 intro-y">
      <PageHeader title="User Management"
        description="Kelola akun pengguna, role, cabang, status aktif, dan reset password.">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Add New User
          </Button>
        </template>
      </PageHeader>

      <div class="gap-4 grid grid-cols-1 md:grid-cols-3">
        <div class="bg-white shadow-sm p-5 border border-slate-200 rounded-2xl">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-slate-500 text-sm">Total Users</p>
              <h3 class="mt-1 font-bold text-slate-800 text-2xl">{{ totalUsers }}</h3>
            </div>
            <div class="bg-primary/10 p-3 rounded-full text-primary">
              <Lucide icon="Users" class="w-5 h-5" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm p-5 border border-slate-200 rounded-2xl">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-slate-500 text-sm">Active Users</p>
              <h3 class="mt-1 font-bold text-emerald-600 text-2xl">{{ activeUsers }}</h3>
            </div>
            <div class="bg-emerald-100 p-3 rounded-full text-emerald-600">
              <Lucide icon="BadgeCheck" class="w-5 h-5" />
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm p-5 border border-slate-200 rounded-2xl">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-slate-500 text-sm">Inactive Users</p>
              <h3 class="mt-1 font-bold text-rose-600 text-2xl">{{ inactiveUsers }}</h3>
            </div>
            <div class="bg-rose-100 p-3 rounded-full text-rose-600">
              <Lucide icon="UserX" class="w-5 h-5" />
            </div>
          </div>
        </div>
      </div>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading" :empty="users.length === 0"
        :colspan="7" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
        :total-pages="totalPages" search-placeholder="Search name, email, role..." loading-text="Loading users..."
        empty-description="No users found." @page-change="goToPage">
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
          <Table.Tr v-for="(user, idx) in users" :key="user.id" class="hover:bg-slate-50 transition">
            <Table.Td class="num-sm text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>

            <Table.Td>
              <div class="flex items-center gap-3">
                <div
                  class="flex justify-center items-center bg-primary/10 rounded-full w-10 h-10 font-semibold text-primary">
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <div class="font-medium text-slate-800">{{ user.name }}</div>
                  <div class="text-slate-500 text-sm">{{ user.email }}</div>
                </div>
              </div>
            </Table.Td>

            <Table.Td>{{ user.no_telepon || '-' }}</Table.Td>
            <Table.Td>{{ user.cabang?.nama_cabang || '-' }}</Table.Td>
            <Table.Td>{{ user.primary_role?.name || '-' }}</Table.Td>

            <Table.Td class="text-center">
              <span class="inline-flex px-3 py-1 rounded-full text-form-label"
                :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex justify-center items-center gap-2">
                <Button variant="soft-warning" rounded class="!shadow-none !p-0 !w-8 !h-8" @click="openEdit(user)"
                  title="Edit">
                  <Lucide icon="Edit" class="w-4 h-4" />
                </Button>
                <Button variant="soft-warning" rounded class="!shadow-none !p-0 !w-8 !h-8"
                  @click="openResetPassword(user)" title="Reset Password">
                  <Lucide icon="KeyRound" class="w-4 h-4" />
                </Button>
                <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8"
                  @click="confirmDelete(user.id)" title="Delete">
                  <Lucide icon="Trash2" class="w-4 h-4" />
                </Button>
                <Button v-if="canImpersonate" variant="soft-info" rounded class="!shadow-none !p-0 !w-8 !h-8"
                  :disabled="user.primary_role?.id === 1 || !user.is_active || Number(user.id) === Number(auth.user?.id)"
                  @click="openImpersonateConfirm(user)" title="Impersonate">
                  <Lucide icon="LogIn" class="w-4 h-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>

    <Dialog v-model:open="createModal">
      <Dialog.Panel class="p-0 w-full max-w-lg overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-slate-200 border-b">
          <h3 class="font-semibold text-slate-800 text-lg">
            {{ isEdit ? 'Edit User' : 'Add New User' }}
          </h3>
          <p class="mt-1 text-slate-500 text-sm">
            Lengkapi data user di bawah ini.
          </p>
        </div>

        <div class="p-6">
          <p v-if="formError" class="bg-rose-50 mb-4 px-3 py-2 rounded-lg text-rose-600 text-sm">
            {{ formError }}
          </p>

          <div class="space-y-4">
            <div>
              <FormInput v-model="form.name" placeholder="Name"
                :class="getFieldError('name') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('name')" class="text-caption !text-rose-600">{{ getFieldError('name')
                }}</small>
            </div>

            <div>
              <FormInput v-model="form.email" placeholder="Email"
                :class="getFieldError('email') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('email')" class="text-caption !text-rose-600">{{ getFieldError('email')
                }}</small>
            </div>

            <FormInput v-model="form.no_telepon" placeholder="No Telepon" />

            <div v-if="!isEdit">
              <FormInput v-model="form.password" type="password" placeholder="Password"
                :class="getFieldError('password') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('password')" class="text-caption !text-rose-600">{{ getFieldError('password')
                }}</small>
            </div>

            <div>
              <FormSelect v-model="form.id_cabang" :class="getFieldError('id_cabang') ? 'border-rose-500' : ''">
                <option disabled value="">— Select Cabang —</option>
                <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
                  {{ c.nama_cabang }}
                </option>
              </FormSelect>
              <small v-if="getFieldError('id_cabang')" class="text-caption !text-rose-600">{{ getFieldError('id_cabang')
                }}</small>
            </div>

            <div>
              <label class="block mb-1 text-form-label text-slate-700">Roles</label>
              <TomSelect v-model="form.role_ids" multiple class="w-full"
                :options="{ placeholder: 'Pilih satu atau lebih role...', dropdownParent: 'body', onDelete: () => true }"
                :class="getFieldError('role_ids') ? 'border-rose-500' : ''">
                <option v-for="r in rolesList" :key="r.id" :value="String(r.id)">
                  {{ r.name }}
                </option>
              </TomSelect>
              <small v-if="getFieldError('role_ids')" class="text-caption !text-rose-600">{{ getFieldError('role_ids')
                }}</small>
            </div>

            <div v-if="form.role_ids.length > 1">
              <label class="block mb-1 text-form-label text-slate-700">Role Utama</label>
              <FormSelect v-model="form.primary_role_id">
                <option v-for="r in primaryRoleOptions" :key="r.id" :value="String(r.id)">
                  {{ r.name }}
                </option>
              </FormSelect>
              <small class="block text-caption text-slate-400">Menentukan brand & tab dashboard default untuk user
                ini.</small>
            </div>

            <label class="flex items-center px-3 py-3 border border-slate-200 rounded-lg">
              <FormCheck.Input v-model="form.is_active" type="checkbox" class="mr-3" />
              <span class="text-slate-700 text-sm">Active User</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-2 bg-white px-6 py-4 border-slate-200 border-t">
          <Button variant="outline-secondary" @click="cancelModal">Cancel</Button>
          <Button variant="primary" :loading="formLoading" @click="isEdit ? submitEdit() : submitCreate()">
            {{ isEdit ? 'Save Changes' : 'Create User' }}
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <Dialog v-model:open="resetModal">
      <Dialog.Panel class="p-0 w-full max-w-md overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-slate-200 border-b">
          <h3 class="font-semibold text-slate-800 text-lg">Reset Password</h3>
          <p class="mt-1 text-slate-500 text-sm">
            Reset password untuk user:
            <span class="font-medium text-slate-700">{{ resetForm.name }}</span>
          </p>
        </div>

        <div class="space-y-4 p-6">
          <FormInput v-model="resetForm.password" type="text" placeholder="Masukkan password baru" />

          <div class="flex gap-2">
            <Button variant="outline-secondary" class="w-full" @click="generatePassword">
              Generate Password
            </Button>
          </div>

          <div class="bg-amber-50 px-3 py-2 rounded-lg text-amber-700 text-sm">
            Password baru akan langsung menggantikan password lama user.
          </div>
        </div>

        <div class="flex justify-end gap-2 bg-white px-6 py-4 border-slate-200 border-t">
          <Button variant="outline-secondary" @click="resetModal = false">Cancel</Button>
          <Button variant="primary" :loading="resetLoading" @click="submitResetPassword">
            Reset Password
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <Dialog v-model:open="impersonateModal">
      <Dialog.Panel class="p-0 w-full max-w-md overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-slate-200 border-b">
          <h3 class="font-semibold text-slate-800 text-lg">Impersonate User</h3>
        </div>

        <div class="p-6">
          <p class="text-slate-600 text-sm">
            Anda akan masuk sebagai
            <span class="font-medium text-slate-800">{{ impersonateTarget?.name }}</span>.
            Sesi ini berlaku 2 jam dan bisa diakhiri kapan saja lewat tombol Kembali ke Admin.
          </p>
        </div>

        <div class="flex justify-end gap-2 bg-white px-6 py-4 border-slate-200 border-t">
          <Button variant="outline-secondary" @click="cancelImpersonate">Cancel</Button>
          <Button variant="primary" :loading="impersonateLoading" @click="submitImpersonate">
            Impersonate
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <DeleteRecordDialog :open="deleteModal" title="Hapus User"
      description="Data user yang dihapus tidak bisa dikembalikan." :loading="deleteLoading"
      @close="deleteModal = false" @confirm="submitDelete" />
  </div>
</template>
