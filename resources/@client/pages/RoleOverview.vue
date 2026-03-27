<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">Role Management</h2>
        <p class="mt-1 text-sm text-slate-500">
          Kelola data role, deskripsi role, dan status aktif.
        </p>
      </div>

      <Button variant="primary" @click="openCreate" class="inline-flex items-center gap-2">
        <Lucide icon="Plus" class="h-4 w-4" />
        <span>Add New Role</span>
      </Button>
    </div>

    <!-- Summary -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Total Roles</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ totalRoles }}</h3>
          </div>
          <div class="rounded-full bg-primary/10 p-3 text-primary">
            <Lucide icon="Shield" class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Active Roles</p>
            <h3 class="mt-1 text-2xl font-bold text-emerald-600">{{ activeRoles }}</h3>
          </div>
          <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
            <Lucide icon="BadgeCheck" class="h-5 w-5" />
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-500">Inactive Roles</p>
            <h3 class="mt-1 text-2xl font-bold text-rose-600">{{ inactiveRoles }}</h3>
          </div>
          <div class="rounded-full bg-rose-100 p-3 text-rose-600">
            <Lucide icon="ShieldOff" class="h-5 w-5" />
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
        <FormInput
          v-model="searchQuery"
          placeholder="Search role..."
          class="w-full md:w-72"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>

        <FormSelect v-model="perPage" class="w-full md:w-24">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div v-if="loading" class="flex items-center justify-center py-16 text-slate-500">
        <Lucide icon="LoaderCircle" class="mr-2 h-5 w-5 animate-spin" />
        Loading roles...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">No</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Role Name</th>
              <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
              <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
              <th class="px-5 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(role, idx) in roles"
              :key="role.id_role"
              class="transition hover:bg-slate-50"
            >
              <td class="px-5 py-4 text-sm text-slate-600">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Lucide icon="ShieldCheck" class="h-5 w-5" />
                  </div>
                  <div class="font-medium text-slate-800">
                    {{ role.role_name }}
                  </div>
                </div>
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ role.role_desc || '-' }}
              </td>

              <td class="px-5 py-4 text-center">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                  :class="role.is_active
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-rose-100 text-rose-700'"
                >
                  {{ role.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-2">
                  <button
                    type="button"
                    class="rounded-lg p-2 text-primary transition hover:bg-primary/10"
                    title="Edit"
                    @click="openEdit(role)"
                  >
                    <Lucide icon="Pencil" class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    class="rounded-lg p-2 text-danger transition hover:bg-danger/10"
                    title="Delete"
                    @click="confirmDelete(role.id_role)"
                  >
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!roles.length">
              <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                No roles found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
      <Pagination>
        <Pagination.Link :disabled="currentPage === 1" @click="fetchRoles(currentPage - 1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>

        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page === currentPage"
          @click="fetchRoles(page)"
        >
          {{ page }}
        </Pagination.Link>

        <Pagination.Link :disabled="currentPage === totalPages" @click="fetchRoles(currentPage + 1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>
  </div>

  <!-- Create Modal -->
  <Dialog v-model:open="createModal">
    <Dialog.Panel class="w-full max-w-lg overflow-hidden p-0">
      <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
        <h3 class="text-lg font-semibold text-slate-800">Add New Role</h3>
        <p class="mt-1 text-sm text-slate-500">Lengkapi data role baru.</p>
      </div>

      <div class="p-6">
        <p v-if="createError" class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600">
          {{ createError }}
        </p>

        <div class="space-y-4">
          <FormInput v-model="createForm.role_name" placeholder="Role Name" />
          <FormInput v-model="createForm.role_desc" placeholder="Description" />

          <FormSelect v-model="createForm.is_active">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </FormSelect>

          <FormInput
            v-model="createForm.created_by"
            placeholder="Created By"
            readonly
            class="bg-slate-100"
          />
        </div>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
        <Button variant="outline-secondary" @click="createModal = false">Cancel</Button>
        <Button variant="primary" :loading="createLoading" @click="submitCreate">Create</Button>
      </div>
    </Dialog.Panel>
  </Dialog>

  <!-- Edit Modal -->
  <Dialog v-model:open="editModal">
    <Dialog.Panel class="w-full max-w-lg overflow-hidden p-0">
      <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
        <h3 class="text-lg font-semibold text-slate-800">Edit Role</h3>
        <p class="mt-1 text-sm text-slate-500">Perbarui data role.</p>
      </div>

      <div class="p-6">
        <p v-if="editError" class="mb-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600">
          {{ editError }}
        </p>

        <div class="space-y-4">
          <FormInput v-model="editForm.role_name" placeholder="Role Name" />
          <FormInput v-model="editForm.role_desc" placeholder="Description" />

          <FormSelect v-model="editForm.is_active">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </FormSelect>

          <FormInput
            v-model="editForm.lastupdate_by"
            readonly
            placeholder="Updated By"
            class="bg-slate-100"
          />
        </div>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-6 py-4">
        <Button variant="outline-secondary" @click="editModal = false">Cancel</Button>
        <Button variant="primary" :loading="editLoading" @click="submitEdit">Save Changes</Button>
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
        <div class="mt-5 text-2xl font-semibold text-slate-800">Delete Role?</div>
        <div class="mt-2 text-slate-500">
          Data role yang dihapus tidak bisa dikembalikan.
        </div>
      </div>
      <div class="px-6 pb-6 text-center">
        <Button variant="outline-secondary" @click="deleteModal = false" class="mr-2 w-24">Cancel</Button>
        <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">Delete</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from "vue"
import axios from "axios"
import { debounce } from "lodash"
import Swal from "sweetalert2"

import Button from "@/components/Base/Button"
import Pagination from "@/components/Base/Pagination"
import { FormInput, FormSelect } from "@/components/Base/Form"
import Lucide from "@/components/Base/Lucide"
import { Dialog } from "@/components/Base/Headless"

const currentUserName = ref("")

const roles = ref<any[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const searchQuery = ref("")
const currentPage = ref(1)
const perPage = ref(10)
const totalPages = ref(1)
const totalRoles = ref(0)

const createModal = ref(false)
const createForm = reactive({
  role_name: "",
  role_desc: "",
  is_active: true,
  created_by: "",
})
const createLoading = ref(false)
const createError = ref<string | null>(null)

const editModal = ref(false)
const editForm = reactive({
  id_role: 0,
  role_name: "",
  role_desc: "",
  is_active: true,
  lastupdate_by: "",
})
const editLoading = ref(false)
const editError = ref<string | null>(null)

const deleteModal = ref(false)
const deleteButtonRef = ref<HTMLButtonElement | null>(null)
let roleToDelete: number | null = null

const activeRoles = computed(() => roles.value.filter((r: any) => !!r.is_active).length)
const inactiveRoles = computed(() => roles.value.filter((r: any) => !r.is_active).length)

onMounted(async () => {
  try {
    const { data } = await axios.get("/api/user")
    currentUserName.value = data.name
  } catch {}

  fetchRoles()
})

async function fetchRoles(page = 1) {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get("/api/roles", {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
      },
    })

    roles.value = data.data
    currentPage.value = data.current_page
    totalPages.value = data.last_page
    totalRoles.value = data.total || 0
  } catch (e: any) {
    error.value = e.response?.data?.message || "Gagal memuat data roles"
  } finally {
    loading.value = false
  }
}

watch(searchQuery, debounce(() => fetchRoles(1), 300))
watch(perPage, () => fetchRoles(1))

function openCreate() {
  createError.value = null
  Object.assign(createForm, {
    role_name: "",
    role_desc: "",
    is_active: true,
    created_by: currentUserName.value,
  })
  createModal.value = true
}

async function submitCreate() {
  createError.value = null

  if (!createForm.role_name.trim() || !createForm.role_desc.trim()) {
    return Swal.fire({
      icon: "error",
      title: "Error",
      text: "Name and Description cannot be empty",
      toast: true,
      position: "top-end",
      timer: 2000,
      showConfirmButton: false,
    })
  }

  createLoading.value = true
  try {
    const { data } = await axios.post("/api/roles", createForm)
    roles.value.unshift(data)
    createModal.value = false

    Swal.fire({
      icon: "success",
      title: "Role berhasil dibuat",
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    })

    fetchRoles(currentPage.value)
  } catch (e: any) {
    createError.value = e.response?.data?.message || "Gagal membuat role"
  } finally {
    createLoading.value = false
  }
}

function openEdit(role: any) {
  editError.value = null
  Object.assign(editForm, {
    id_role: role.id_role,
    role_name: role.role_name,
    role_desc: role.role_desc || "",
    is_active: role.is_active,
    lastupdate_by: currentUserName.value,
  })
  editModal.value = true
}

async function submitEdit() {
  editError.value = null

  if (!editForm.role_name.trim() || !editForm.role_desc.trim()) {
    return Swal.fire({
      icon: "error",
      title: "Error",
      text: "Name and Description cannot be empty",
      toast: true,
      position: "top-end",
      timer: 2000,
      showConfirmButton: false,
    })
  }

  editLoading.value = true
  try {
    const { data } = await axios.put(`/api/roles/${editForm.id_role}`, {
      role_name: editForm.role_name,
      role_desc: editForm.role_desc,
      is_active: editForm.is_active,
      lastupdate_by: editForm.lastupdate_by,
    })

    const idx = roles.value.findIndex((r) => r.id_role === data.id_role)
    if (idx !== -1) roles.value[idx] = data

    editModal.value = false

    Swal.fire({
      icon: "success",
      title: "Role berhasil diperbarui",
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    })

    fetchRoles(currentPage.value)
  } catch (e: any) {
    editError.value = e.response?.data?.message || "Gagal mengubah role"
  } finally {
    editLoading.value = false
  }
}

function confirmDelete(id: number) {
  roleToDelete = id
  deleteModal.value = true
}

async function submitDelete() {
  if (roleToDelete === null) return

  try {
    await axios.delete(`/api/roles/${roleToDelete}`)
    roles.value = roles.value.filter((r) => r.id_role !== roleToDelete)
    deleteModal.value = false

    Swal.fire({
      icon: "success",
      title: "Role berhasil dihapus",
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    })

    fetchRoles(currentPage.value)
  } catch (e: any) {
    Swal.fire("Error", e.response?.data?.message || "Gagal menghapus role", "error")
  } finally {
    roleToDelete = null
  }
}
</script>