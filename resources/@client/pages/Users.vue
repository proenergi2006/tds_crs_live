<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">User Management</h2>
        <p class="mt-1 text-sm text-slate-500">
          Kelola akun pengguna, role, cabang, status aktif, dan reset password.
        </p>
      </div>

      <Button variant="primary" @click="openCreate" class="inline-flex items-center gap-2">
        <Lucide icon="Plus" class="h-4 w-4" />
        <span>Add New User</span>
      </Button>
    </div>

    <!-- Summary -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
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

    <!-- Toolbar -->
    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
      <div class="text-sm text-slate-500">
        Page <span class="font-semibold text-slate-700">{{ currentPage }}</span>
        of
        <span class="font-semibold text-slate-700">{{ totalPages }}</span>
      </div>

      <div class="flex flex-1 flex-col gap-3 md:flex-row md:justify-end">
        <FormInput v-model="searchQuery" placeholder="Search name, email, role..." class="w-full md:w-72">
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>

        <FormSelect v-model="perPage" class="w-full md:w-24">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </FormSelect>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div v-if="loading" class="flex items-center justify-center py-16 text-slate-500">
        <Lucide icon="LoaderCircle" class="mr-2 h-5 w-5 animate-spin" />
        Loading users...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">User</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No Telepon</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cabang</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
              <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
              <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr v-for="(user, idx) in users" :key="user.id" class="transition hover:bg-slate-50">
              <td class="px-5 py-4 text-sm text-slate-600">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">
                    {{ getInitials(user.name) }}
                  </div>
                  <div>
                    <div class="font-medium text-slate-800">{{ user.name }}</div>
                    <div class="text-sm text-slate-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ user.no_telepon || '-' }}
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ user.cabang?.nama_cabang || '-' }}
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ user.role?.role_name || '-' }}
              </td>

              <td class="px-5 py-4 text-center">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                  :class="user.is_active
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-rose-100 text-rose-700'"
                >
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-2">
                  <button
                    type="button"
                    class="rounded-lg p-2 text-primary transition hover:bg-primary/10"
                    title="Edit"
                    @click="openEdit(user)"
                  >
                    <Lucide icon="Pencil" class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    class="rounded-lg p-2 text-warning transition hover:bg-warning/10"
                    title="Reset Password"
                    @click="openResetPassword(user)"
                  >
                    <Lucide icon="KeyRound" class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    class="rounded-lg p-2 text-danger transition hover:bg-danger/10"
                    title="Delete"
                    @click="confirmDelete(user.id)"
                  >
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!users.length">
              <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                No users found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <Pagination>
        <Pagination.Link :disabled="currentPage === 1" @click="fetchUsers(currentPage - 1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>

        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page === currentPage"
          @click="fetchUsers(page)"
        >
          {{ page }}
        </Pagination.Link>

        <Pagination.Link :disabled="currentPage === totalPages" @click="fetchUsers(currentPage + 1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
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
          <FormInput v-model="form.name" placeholder="Name" />
          <FormInput v-model="form.email" placeholder="Email" />
          <FormInput v-model="form.no_telepon" placeholder="No Telepon" />
          <FormInput
            v-if="!isEdit"
            v-model="form.password"
            type="password"
            placeholder="Password"
          />

          <FormSelect v-model="form.id_cabang">
            <option disabled value="">— Select Cabang —</option>
            <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
              {{ c.nama_cabang }}
            </option>
          </FormSelect>

          <FormSelect v-model="form.id_role">
            <option disabled value="">— Select Role —</option>
            <option v-for="r in rolesList" :key="r.id_role" :value="r.id_role">
              {{ r.role_name }}
            </option>
          </FormSelect>

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
        <FormInput
          v-model="resetForm.password"
          type="text"
          placeholder="Masukkan password baru"
        />

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

  <!-- Delete Modal -->
  <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
    <Dialog.Panel class="max-w-md">
      <div class="p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          <Lucide icon="Trash2" class="h-8 w-8" />
        </div>
        <div class="mt-5 text-2xl font-semibold text-slate-800">Delete User?</div>
        <div class="mt-2 text-slate-500">
          Data user yang dihapus tidak bisa dikembalikan.
        </div>
      </div>
      <div class="px-6 pb-6 text-center">
        <Button variant="outline-secondary" @click="deleteModal=false" class="mr-2 w-24">Cancel</Button>
        <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">Delete</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'
import Table from '@/components/Base/Table'
import Button from '@/components/Base/Button'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect, FormCheck } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'

const users = ref<any[]>([])
const rolesList = ref<any[]>([])
const cabangList = ref<any[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const currentPage = ref(1)
const perPage = ref(10)
const totalPages = ref(1)
const totalUsers = ref(0)
const searchQuery = ref('')

const form = reactive({
  id: 0,
  name: '',
  email: '',
  no_telepon: '',
  password: '',
  id_role: null as number | null,
  id_cabang: null as number | null,
  is_active: true,
})

const isEdit = ref(false)
const formError = ref<string | null>(null)
const formLoading = ref(false)

const createModal = ref(false)
const deleteModal = ref(false)
const deleteButtonRef = ref<HTMLButtonElement | null>(null)
let userToDelete: number | null = null

const resetModal = ref(false)
const resetLoading = ref(false)
const resetForm = reactive({
  id: 0,
  name: '',
  password: '',
})

const activeUsers = computed(() => users.value.filter((u: any) => !!u.is_active).length)
const inactiveUsers = computed(() => users.value.filter((u: any) => !u.is_active).length)

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/users', {
      params: { page, per_page: perPage.value, search: searchQuery.value || undefined }
    })

    users.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
    totalUsers.value = data.total || 0
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load users'
  } finally {
    loading.value = false
  }
}

async function fetchRolesList() {
  try {
    const { data } = await axios.get('/api/roles', {
      params: { per_page: 1000 }
    })
    rolesList.value = data.data
  } catch {}
}

async function fetchCabangList() {
  try {
    const { data } = await axios.get('/api/cabangs')
    cabangList.value = data.data
  } catch {}
}

onMounted(() => {
  fetchUsers()
  fetchRolesList()
  fetchCabangList()
})

watch(searchQuery, debounce(() => fetchUsers(1), 300))
watch(perPage, () => fetchUsers(1))

function getInitials(name: string) {
  if (!name) return 'U'
  return name
    .split(' ')
    .slice(0, 2)
    .map(part => part.charAt(0).toUpperCase())
    .join('')
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
    id_role: null,
    id_cabang: null,
    is_active: true,
  })
  createModal.value = true
}

function openEdit(user: any) {
  isEdit.value = true
  formError.value = null
  Object.assign(form, {
    id: user.id,
    name: user.name,
    email: user.email,
    no_telepon: user.no_telepon || '',
    password: '',
    id_role: user.id_role,
    id_cabang: user.id_cabang,
    is_active: user.is_active,
  })
  createModal.value = true
}

async function submitCreate() {
  if (!form.name || !form.email || !form.password || !form.id_role || !form.id_cabang) {
    return Swal.fire({ icon: 'error', title: 'Field required', toast: true, timer: 2000, showConfirmButton: false, position: 'top-end' })
  }

  formLoading.value = true
  try {
    await axios.post('/api/users', form)
    createModal.value = false
    fetchUsers(currentPage.value)
    Swal.fire({ icon: 'success', title: 'User created', toast: true, timer: 1500, showConfirmButton: false, position: 'top-end' })
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Failed to create user'
  } finally {
    formLoading.value = false
  }
}

async function submitEdit() {
  if (!form.name || !form.email || !form.id_role || !form.id_cabang) {
    return Swal.fire({ icon: 'error', title: 'Field required', toast: true, timer: 2000, showConfirmButton: false, position: 'top-end' })
  }

  formLoading.value = true
  try {
    await axios.put(`/api/users/${form.id}`, form)
    createModal.value = false
    fetchUsers(currentPage.value)
    Swal.fire({ icon: 'success', title: 'User updated', toast: true, timer: 1500, showConfirmButton: false, position: 'top-end' })
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Failed to update user'
  } finally {
    formLoading.value = false
  }
}

function openResetPassword(user: any) {
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
    return Swal.fire('Error', 'Password baru wajib diisi', 'error')
  }

  resetLoading.value = true
  try {
    await axios.put(`/api/users/${resetForm.id}/reset-password`, {
      password: resetForm.password,
    })

    resetModal.value = false

    Swal.fire({
      icon: 'success',
      title: 'Password berhasil direset',
      html: `Password baru: <b>${resetForm.password}</b>`,
    })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to reset password', 'error')
  } finally {
    resetLoading.value = false
  }
}

function confirmDelete(id: number) {
  userToDelete = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!userToDelete) return
  try {
    await axios.delete(`/api/users/${userToDelete}`)
    deleteModal.value = false
    fetchUsers(currentPage.value)
    Swal.fire({ icon: 'success', title: 'User deleted', toast: true, timer: 1500, showConfirmButton: false, position: 'top-end' })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Failed to delete user', 'error')
  }
  userToDelete = null
}

function cancelModal() {
  createModal.value = false
}
</script>