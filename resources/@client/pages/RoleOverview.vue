<template>
    <h2 class="mt-10 text-lg font-medium intro-y">Roles</h2>
  
    <div class="grid grid-cols-12 gap-6 mt-5">
      <!-- Toolbar -->
      <div class="col-span-12 flex flex-wrap items-center intro-y sm:flex-nowrap">
        <Button variant="primary" @click="openCreate">Add New Role</Button>
        <div class="mx-auto text-slate-500">Page {{ currentPage }} of {{ totalPages }}</div>
        <FormInput
          v-model="searchQuery"
          placeholder="Search…"
          class="w-56 ml-auto pr-10 !box"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>
        <FormSelect v-model="perPage" class="w-20 ml-2 !box">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
        </FormSelect>
      </div>
  
      <!-- Data List -->
    <div class="col-span-12 overflow-auto intro-y lg:overflow-visible">
      <Table class="border-spacing-y-[10px] border-separate sm:mt-2">
        <Table.Thead>
          <Table.Tr>
            <Table.Th class="border-b-0 whitespace-nowrap">No</Table.Th>
            <Table.Th class="border-b-0 whitespace-nowrap">Role Name</Table.Th>
            <Table.Th class="border-b-0 whitespace-nowrap">Role Desc</Table.Th>
            <Table.Th class="text-center border-b-0 whitespace-nowrap">Status</Table.Th>
            <Table.Th class="text-center border-b-0 whitespace-nowrap">Actions</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr v-for="(role, idx) in roles" :key="role.id_role" class="intro-x">
            <Table.Td class="box w-10 rounded-l-none rounded-r-none border-x-0 shadow first:rounded-l-[0.6rem] first:border-l last:rounded-r-[0.6rem] last:border-r dark:bg-darkmode-600">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td class="box rounded-l-none rounded-r-none border-x-0 shadow dark:bg-darkmode-600">
              <span class="font-medium whitespace-nowrap">{{ role.role_name }}</span>
            </Table.Td>
            <Table.Td class="box rounded-l-none rounded-r-none border-x-0 shadow text-slate-500 dark:bg-darkmode-600">
              {{ role.role_desc || '-' }}
            </Table.Td>
            <Table.Td class="box w-40 rounded-l-none rounded-r-none border-x-0 shadow dark:bg-darkmode-600">
              <div class="flex items-center justify-center" :class="role.is_active ? 'text-success' : 'text-danger'">
                <Lucide icon="CheckSquare" class="w-4 h-4 mr-2" />
                {{ role.is_active ? 'Active' : 'Inactive' }}
              </div>
            </Table.Td>
            <Table.Td class="box w-56 rounded-l-none rounded-r-none border-x-0 shadow dark:bg-darkmode-600 before:absolute before:inset-y-0 before:left-0 before:my-auto before:block before:h-8 before:w-px before:bg-slate-200 before:dark:bg-darkmode-400">
              <div class="flex items-center justify-center">
                <a href="#" class="flex items-center mr-3 text-primary" @click.prevent="openEdit(role)">
                  <Lucide icon="Edit" class="w-4 h-4 mr-1" /> Edit
                </a>
                <a href="#" class="flex items-center text-danger" @click.prevent="confirmDelete(role.id_role)">
                  <Lucide icon="Trash2" class="w-4 h-4 mr-1" /> Delete
                </a>
              </div>
            </Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
    </div>

  
      <!-- Pagination Controls -->
      <div class="col-span-12 flex justify-center intro-y">
        <Pagination>
          <Pagination.Link :disabled="currentPage===1" @click="fetchRoles(currentPage-1)">
            <Lucide icon="ChevronLeft" />
          </Pagination.Link>
          <Pagination.Link
            v-for="page in totalPages"
            :key="page"
            :active="page===currentPage"
            @click="fetchRoles(page)"
          >
            {{ page }}
          </Pagination.Link>
          <Pagination.Link :disabled="currentPage===totalPages" @click="fetchRoles(currentPage+1)">
            <Lucide icon="ChevronRight" />
          </Pagination.Link>
        </Pagination>
      </div>
    </div>
  
    <!-- Create Modal -->
    <Dialog v-model:open="createModal">
      <Dialog.Panel class="w-96 p-6">
        <h3 class="text-lg font-medium mb-4">Add New Role</h3>
        <p v-if="createError" class="text-red-500 mb-2">{{ createError }}</p>
        <FormInput v-model="createForm.role_name" placeholder="Role Name" class="mb-3" />
        <FormInput v-model="createForm.role_desc" placeholder="Description" class="mb-3" />
        <div class="flex items-center mb-4">
          <FormSelect v-model="createForm.is_active" class="mr-2">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </FormSelect>
          <FormInput v-model="createForm.created_by" placeholder="Created By" readonly class="bg-gray-100 cursor-not-allowed" />
        </div>
        <div class="flex justify-end space-x-2">
          <Button variant="outline-secondary" @click="createModal=false">Cancel</Button>
          <Button variant="primary" :loading="createLoading" @click="submitCreate">Create</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  
    <!-- Edit Modal -->
    <Dialog v-model:open="editModal">
      <Dialog.Panel class="w-96 p-6">
        <h3 class="text-lg font-medium mb-4">Edit Role</h3>
        <p v-if="editError" class="text-red-500 mb-2">{{ editError }}</p>
        <FormInput v-model="editForm.role_name" placeholder="Role Name" class="mb-3" />
        <FormInput v-model="editForm.role_desc" placeholder="Description" class="mb-3" />
        <div class="flex items-center mb-4">
          <FormSelect v-model="editForm.is_active" class="mr-2">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </FormSelect>
          <FormInput v-model="editForm.lastupdate_by" readonly placeholder="Updated By" />
        </div>
        <div class="flex justify-end space-x-2">
          <Button variant="outline-secondary" @click="editModal=false">Cancel</Button>
          <Button variant="primary" :loading="editLoading" @click="submitEdit">Save</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  
    <!-- Delete Confirmation Modal -->
    <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
      <Dialog.Panel>
        <div class="p-5 text-center">
          <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
          <div class="mt-5 text-3xl">Are you sure?</div>
          <div class="mt-2 text-slate-500">This process cannot be undone.</div>
        </div>
        <div class="px-5 pb-8 text-center">
          <Button variant="outline-secondary" @click="deleteModal=false" class="w-24 mr-1">Cancel</Button>
          <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">Delete</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted, watch } from "vue";
  import axios from "axios";
  import { debounce } from "lodash";
  import Swal from "sweetalert2";
  
  import Button from "@/components/Base/Button";
  import Pagination from "@/components/Base/Pagination";
  import { FormInput, FormSelect } from "@/components/Base/Form";
  import Lucide from "@/components/Base/Lucide";
  import { Dialog, Menu } from "@/components/Base/Headless";
  import Table from "@/components/Base/Table";
  
  // current user name
  const currentUserName = ref("");
  
  const roles        = ref<any[]>([]);
  const loading      = ref(false);
  const error        = ref<string|null>(null);
  
  const searchQuery  = ref("");
  const currentPage  = ref(1);
  const perPage      = ref(10);
  const totalPages   = ref(1);
  
  const createModal   = ref(false);
  const createForm    = reactive({ role_name: "", role_desc: "", is_active: true, created_by: "" });
  const createLoading = ref(false);
  const createError   = ref<string|null>(null);
  
  const editModal     = ref(false);
  const editForm      = reactive({ id_role: 0, role_name: "", role_desc: "", is_active: true, lastupdate_by: "" });
  const editLoading   = ref(false);
  const editError     = ref<string|null>(null);
  
  const deleteModal     = ref(false);
  const deleteButtonRef = ref<HTMLButtonElement|null>(null);
  let roleToDelete: number|null = null;
  
  // fetch current user
  onMounted(async () => {
    try {
      const { data } = await axios.get('/api/user');
      currentUserName.value = data.name;
    } catch {}
    fetchRoles();
  });
  
  async function fetchRoles(page = 1) {
    loading.value = true;
    error.value   = null;
    try {
      const { data } = await axios.get("/api/roles", { params: { page, per_page: perPage.value, search: searchQuery.value || undefined }});
      roles.value       = data.data;
      currentPage.value = data.current_page;
      totalPages.value  = data.last_page;
    } catch (e: any) {
      error.value = e.response?.data?.message || "Gagal memuat data roles";
    } finally {
      loading.value = false;
    }
  }
  
  watch(searchQuery, debounce(() => fetchRoles(1), 300));
  watch(perPage, () => fetchRoles(1));
  
  function openCreate() {
    createError.value = null;
    Object.assign(createForm, { role_name: "", role_desc: "", is_active: true, created_by: currentUserName.value });
    createModal.value = true;
  }
  
  async function submitCreate() {
    createError.value = null;
    // validation
    if (!createForm.role_name.trim() || !createForm.role_desc.trim()) {
      return Swal.fire({ icon: 'error', title: 'Error', text: 'Name and Description cannot be empty', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
    }
    createLoading.value = true;
    try {
      const { data } = await axios.post("/api/roles", createForm);
      roles.value.unshift(data);
      createModal.value = false;
      Swal.fire({ icon: "success", title: "Role berhasil dibuat", toast: true, position: "top-end", showConfirmButton: false, timer: 2000, timerProgressBar: true });
    } catch (e: any) {
      createError.value = e.response?.data?.message || "Gagal membuat role";
    } finally {
      createLoading.value = false;
    }
  }
  
  function openEdit(role: any) {
    editError.value = null;
    Object.assign(editForm, { id_role: role.id_role, role_name: role.role_name, role_desc: role.role_desc || "", is_active: role.is_active, lastupdate_by: currentUserName.value });
    editModal.value = true;
  }
  
  async function submitEdit() {
    editError.value = null;
    if (!editForm.role_name.trim() || !editForm.role_desc.trim()) {
      return Swal.fire({ icon: 'error', title: 'Error', text: 'Name and Description cannot be empty', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
    }
    editLoading.value = true;
    try {
      const { data } = await axios.put(`/api/roles/${editForm.id_role}`, { role_name: editForm.role_name, role_desc: editForm.role_desc, is_active: editForm.is_active, lastupdate_by: editForm.lastupdate_by });
      const idx = roles.value.findIndex(r => r.id_role === data.id_role);
      if (idx !== -1) roles.value[idx] = data;
      editModal.value = false;
      Swal.fire({ icon: "success", title: "Role berhasil diperbarui", toast: true, position: "top-end", showConfirmButton: false, timer: 2000, timerProgressBar: true });
    } catch (e: any) {
      editError.value = e.response?.data?.message || "Gagal mengubah role";
    } finally {
      editLoading.value = false;
    }
  }
  
  function confirmDelete(id: number) { roleToDelete = id; deleteModal.value = true; }
  async function submitDelete() { if (roleToDelete === null) return; try { await axios.delete(`/api/roles/${roleToDelete}`); roles.value = roles.value.filter(r => r.id_role !== roleToDelete); deleteModal.value = false; Swal.fire({ icon: "success", title: "Role berhasil dihapus", toast: true, position: "top-end", showConfirmButton: false, timer: 2000, timerProgressBar: true }); } catch {} finally { roleToDelete = null; } }
  </script>
  