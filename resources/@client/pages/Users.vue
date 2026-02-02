<template>
  <h2 class="mt-10 text-lg font-medium intro-y">Users</h2>
  <div class="grid grid-cols-12 gap-6 mt-5">
    <!-- Toolbar -->
    <div class="col-span-12 flex flex-wrap items-center intro-y sm:flex-nowrap">
      <Button variant="primary" @click="openCreate">Add New User</Button>
      <div class="mx-auto text-slate-500">Page {{ currentPage }} of {{ totalPages }}</div>
      <FormInput v-model="searchQuery" placeholder="Search…" class="w-56 ml-auto pr-10 !box">
        <template #icon><Lucide icon="Search" /></template>
      </FormInput>
      <FormSelect v-model="perPage" class="w-20 ml-2 !box">
        <option :value="5">5</option>
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
      </FormSelect>
    </div>

    <!-- Data List -->
    <div class="col-span-12 overflow-auto intro-y lg:overflow-visible">
      <Table v-if="!loading" class="border-spacing-y-[10px] border-separate -mt-2">
        <Table.Thead>
          <Table.Tr>
            <Table.Th>No</Table.Th>
            <Table.Th>Name</Table.Th>
            <Table.Th>Email</Table.Th>
            <Table.Th>Cabang</Table.Th>
            <Table.Th>Role</Table.Th>
            <Table.Th class="text-center">Status</Table.Th>
            <Table.Th class="text-center">Actions</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr v-for="(user, idx) in users" :key="user.id" class="intro-x">
            <Table.Td>{{ (currentPage - 1) * perPage + idx + 1 }}</Table.Td>
            <Table.Td>{{ user.name }}</Table.Td>
            <Table.Td>{{ user.email }}</Table.Td>
            <Table.Td>{{ user.cabang?.nama_cabang || '-' }}</Table.Td>
            <Table.Td>{{ user.role?.role_name || '-' }}</Table.Td>
            <Table.Td>
              <span :class="user.is_active ? 'text-success' : 'text-danger'">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td>
              <a href="#" @click.prevent="openEdit(user)" class="text-primary">Edit</a> |
              <a href="#" @click.prevent="confirmDelete(user.id)" class="text-danger">Delete</a>
            </Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
      <div v-else class="text-center">Loading...</div>
    </div>

    <!-- Pagination -->
    <div class="col-span-12 flex justify-center intro-y mt-5">
      <Pagination>
        <Pagination.Link :disabled="currentPage===1" @click="fetchUsers(currentPage-1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link v-for="page in totalPages" :key="page" :active="page===currentPage" @click="fetchUsers(page)">
          {{ page }}
        </Pagination.Link>
        <Pagination.Link :disabled="currentPage===totalPages" @click="fetchUsers(currentPage+1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Dialog v-model:open="createModal">
    <Dialog.Panel class="w-96 p-6">
      <h3 class="text-lg font-medium mb-4">{{ isEdit ? 'Edit' : 'Add' }} User</h3>
      <p v-if="formError" class="text-red-500 mb-2">{{ formError }}</p>
      <FormInput v-model="form.name" placeholder="Name" class="mb-3" />
      <FormInput v-model="form.email" placeholder="Email" class="mb-3" />
      <FormInput v-model="form.password" type="password" placeholder="Password" class="mb-3" v-if="!isEdit" />
      <FormSelect v-model="form.id_cabang" class="mb-3">
        <option disabled value="">— Select Cabang —</option>
        <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">{{ c.nama_cabang }}</option>
      </FormSelect>
      <FormSelect v-model="form.id_role" class="mb-3">
        <option disabled value="">— Select Role —</option>
        <option v-for="r in rolesList" :key="r.id_role" :value="r.id_role">{{ r.role_name }}</option>
      </FormSelect>
      <label class="flex items-center mb-4">
        <FormCheck.Input v-model="form.is_active" type="checkbox" class="mr-2" /> Active
      </label>
      <div class="flex justify-end space-x-2">
        <Button variant="outline-secondary" @click="cancelModal">Cancel</Button>
        <Button variant="primary" :loading="formLoading" @click="isEdit ? submitEdit() : submitCreate()">
          {{ isEdit ? 'Save' : 'Create' }}
        </Button>
      </div>
    </Dialog.Panel>
  </Dialog>

  <!-- Delete Modal -->
  <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
    <Dialog.Panel>
      <div class="p-5 text-center">
        <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
        <div class="mt-5 text-3xl">Are you sure?</div>
        <div class="mt-2 text-slate-500">This cannot be undone.</div>
      </div>
      <div class="px-5 pb-8 text-center">
        <Button variant="outline-secondary" @click="deleteModal=false" class="w-24 mr-1">Cancel</Button>
        <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">Delete</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
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
const error = ref<string|null>(null)

const currentPage = ref(1)
const perPage = ref(10)
const totalPages = ref(1)
const searchQuery = ref('')

const form = reactive({
  id: 0,
  name: '',
  email: '',
  password: '',
  id_role: null,
  id_cabang: null,
  is_active: true,
})
const isEdit = ref(false)
const formError = ref<string|null>(null)
const formLoading = ref(false)

const createModal = ref(false)
const deleteModal = ref(false)
const deleteButtonRef = ref<HTMLButtonElement|null>(null)
let userToDelete: number|null = null

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const { data } = await axios.get('/api/users', {
      params: { page, per_page: perPage.value, search: searchQuery.value || undefined }
    })
    users.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
  } catch (e:any) {
    error.value = e.response?.data?.message || 'Failed to load users'
  } finally {
    loading.value = false
  }
}

async function fetchRolesList() {
  try {
    const { data } = await axios.get('/api/roles')
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

function openCreate() {
  isEdit.value = false
  formError.value = null
  Object.assign(form, {
    id: 0, name: '', email: '', password: '',
    id_role: null, id_cabang: null, is_active: true
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
    password: '',
    id_role: user.id_role,
    id_cabang: user.id_cabang,
    is_active: user.is_active,
  })
  createModal.value = true
}

async function submitCreate() {
  if (!form.name || !form.email || !form.password || !form.id_role || !form.id_cabang) {
    return Swal.fire({ icon: 'error', title: 'Field required', toast: true, timer: 2000 })
  }
  formLoading.value = true
  try {
    await axios.post('/api/users', form)
    createModal.value = false
    fetchUsers(currentPage.value)
    Swal.fire({ icon: 'success', title: 'User created', toast: true, timer: 1500 })
  } catch (e:any) {
    formError.value = e.response?.data?.message
  } finally {
    formLoading.value = false
  }
}

async function submitEdit() {
  if (!form.name || !form.email || !form.id_role || !form.id_cabang) {
    return Swal.fire({ icon: 'error', title: 'Field required', toast: true, timer: 2000 })
  }
  formLoading.value = true
  try {
    await axios.put(`/api/users/${form.id}`, form)
    createModal.value = false
    fetchUsers(currentPage.value)
    Swal.fire({ icon: 'success', title: 'User updated', toast: true, timer: 1500 })
  } catch (e:any) {
    formError.value = e.response?.data?.message
  } finally {
    formLoading.value = false
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
    Swal.fire({ icon: 'success', title: 'User deleted', toast: true, timer: 1500 })
  } catch {}
  userToDelete = null
}

function cancelModal() {
  createModal.value = false
}
</script>
