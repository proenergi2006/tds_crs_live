<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Pagination from '@/components/Base/Pagination'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'

// current user untuk created_by / lastupdate_by
const currentUserName = ref('')

// data list & paging
const satuans = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const loading = ref(false)

// create modal state
const createModal = ref(false)
const createForm = reactive({
  nama_satuan: '',
  deskripsi: '',
  is_active: true,
  created_by: ''
})
const createLoading = ref(false)
const createError = ref<string | null>(null)

// edit modal state
const editModal = ref(false)
const editForm = reactive({
  id_satuan: 0,
  nama_satuan: '',
  deskripsi: '',
  is_active: true,
  lastupdate_by: ''
})
const editLoading = ref(false)
const editError = ref<string | null>(null)

// delete modal state
const deleteModal = ref(false)
const deleteButtonRef = ref<HTMLButtonElement | null>(null)
let satuanToDelete: number | null = null

// fetch current user & initial data
onMounted(async () => {
  fetchSatuans()
  axios.get('/api/user')
    .then(r => currentUserName.value = r.data.name)
    .catch(() => { });
})

// fetch paginated satuans
async function fetchSatuans(page = 1) {
  loading.value = true
  try {
    const res = await axios.get('/api/satuans', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined
      }
    })
    satuans.value = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value = res.data.last_page
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  } finally {
    loading.value = false
  }
}

// watchers untuk search & perPage
watch(searchQuery, debounce(() => fetchSatuans(1), 300))
watch(perPage, () => fetchSatuans(1))

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchSatuans(page)
}

// open create modal
function openCreate() {
  createForm.nama_satuan = ''
  createForm.deskripsi = ''
  createForm.is_active = true
  createForm.created_by = currentUserName.value
  createError.value = null
  createModal.value = true
}

// submit create
async function submitCreate() {
  if (!createForm.nama_satuan.trim()) {
    return Swal.fire('Error', 'Nama Satuan tidak boleh kosong', 'error')
  }
  createLoading.value = true
  try {
    const { data } = await axios.post('/api/satuans', createForm)
    satuans.value.unshift(data)
    createModal.value = false
    Swal.fire({ icon: 'success', title: 'Satuan berhasil dibuat', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    createError.value = e.response?.data?.message || 'Gagal membuat satuan'
  } finally {
    createLoading.value = false
  }
}

// open edit modal
function openEdit(s: any) {
  editForm.id_satuan = s.id_satuan
  editForm.nama_satuan = s.nama_satuan
  editForm.deskripsi = s.deskripsi
  editForm.is_active = s.is_active
  editForm.lastupdate_by = currentUserName.value
  editError.value = null
  editModal.value = true
}

// submit edit
async function submitEdit() {
  if (!editForm.nama_satuan.trim()) {
    return Swal.fire('Error', 'Nama Satuan tidak boleh kosong', 'error')
  }
  editLoading.value = true
  try {
    const { data } = await axios.put(
      `/api/satuans/${editForm.id_satuan}`,
      {
        nama_satuan: editForm.nama_satuan,
        deskripsi: editForm.deskripsi,
        is_active: editForm.is_active,
        lastupdate_by: editForm.lastupdate_by
      }
    )
    const idx = satuans.value.findIndex(r => r.id_satuan === data.id_satuan)
    if (idx !== -1) satuans.value[idx] = data
    editModal.value = false
    Swal.fire({ icon: 'success', title: 'Satuan berhasil diperbarui', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    editError.value = e.response?.data?.message || 'Gagal memperbarui satuan'
  } finally {
    editLoading.value = false
  }
}

// confirm delete
function confirmDelete(id: number) {
  satuanToDelete = id
  deleteModal.value = true
}

// submit delete
async function submitDelete() {
  if (satuanToDelete === null) return
  deleteModal.value = false
  try {
    await axios.delete(`/api/satuans/${satuanToDelete}`)
    satuans.value = satuans.value.filter(s => s.id_satuan !== satuanToDelete)
    Swal.fire({ icon: 'success', title: 'Satuan berhasil dihapus', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal menghapus satuan', 'error')
  } finally {
    satuanToDelete = null
  }
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <PageHeader title="Master Satuan" description="Kelola data satuan produk">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Satuan
          </Button>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari satuan..."
        @page-change="goToPage" />

      <DataList :loading="loading" :empty="satuans.length === 0" :colspan="5" loading-text="Memuat data satuan..."
        empty-description="Belum ada satuan untuk ditampilkan.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Satuan</Table.Th>
          <Table.Th>Deskripsi</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Actions</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(s, idx) in satuans" :key="s.id_satuan" class="hover:bg-slate-100 transition-colors">
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ s.nama_satuan }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ s.deskripsi || '-' }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                :class="s.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ s.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <button type="button" @click.prevent="openEdit(s)"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100">
                <Lucide icon="Edit" class="h-4 w-4" />
              </button>
              <button type="button" @click="confirmDelete(s.id_satuan)"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-100 ml-2">
                <Lucide icon="Trash2" class="h-4 w-4" />
              </button>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <Dialog v-model:open="createModal">
        <Dialog.Panel class="w-96 p-6">
          <h3 class="text-lg font-medium mb-4">Add New Satuan</h3>
          <p v-if="createError" class="text-red-500 mb-2">{{ createError }}</p>

          <div class="space-y-3">
            <div>
              <FormLabel htmlFor="create-nama">Nama Satuan</FormLabel>
              <FormInput id="create-nama" v-model="createForm.nama_satuan" placeholder="Nama Satuan" />
            </div>

            <div>
              <FormLabel htmlFor="create-desc">Deskripsi</FormLabel>
              <FormInput id="create-desc" v-model="createForm.deskripsi" placeholder="Deskripsi" />
            </div>

            <div class="flex items-start gap-3">
              <div class="flex-1">
                <FormLabel htmlFor="create-status">Status</FormLabel>
                <FormSelect id="create-status" v-model="createForm.is_active">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </FormSelect>
              </div>

              <div class="flex-1">
                <FormLabel htmlFor="create-by">Created By</FormLabel>
                <FormInput id="create-by" v-model="createForm.created_by" placeholder="Created By" readonly
                  class="bg-gray-100 cursor-not-allowed" />
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-2 mt-5">
            <Button variant="outline-secondary" @click="createModal = false" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Cancel</span>
            </Button>

            <Button variant="primary" :loading="createLoading" @click="submitCreate"
              class="inline-flex items-center gap-2">
              <Lucide v-if="!createLoading" icon="PlusCircle" class="w-4 h-4" aria-hidden="true" />
              <span>Create</span>
            </Button>
          </div>
        </Dialog.Panel>
      </Dialog>

      <!-- Edit Modal -->
      <Dialog v-model:open="editModal">
        <Dialog.Panel class="w-96 p-6">
          <h3 class="text-lg font-medium mb-4">Edit Satuan</h3>
          <p v-if="editError" class="text-red-500 mb-2">{{ editError }}</p>

          <div class="space-y-3">
            <div>
              <FormLabel htmlFor="edit-nama">Nama Satuan</FormLabel>
              <FormInput id="edit-nama" v-model="editForm.nama_satuan" placeholder="Nama Satuan" />
            </div>

            <div>
              <FormLabel htmlFor="edit-desc">Deskripsi</FormLabel>
              <FormInput id="edit-desc" v-model="editForm.deskripsi" placeholder="Deskripsi" />
            </div>

            <div class="flex items-start gap-3">
              <div class="flex-1">
                <FormLabel htmlFor="edit-status">Status</FormLabel>
                <FormSelect id="edit-status" v-model="editForm.is_active">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </FormSelect>
              </div>

              <div class="flex-1">
                <FormLabel htmlFor="edit-by">Updated By</FormLabel>
                <FormInput id="edit-by" v-model="editForm.lastupdate_by" placeholder="Updated By" readonly
                  class="bg-gray-100 cursor-not-allowed" />
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-2 mt-5">
            <Button variant="outline-secondary" @click="editModal = false" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Cancel</span>
            </Button>

            <Button variant="primary" :loading="editLoading" @click="submitEdit" class="inline-flex items-center gap-2">
              <Lucide v-if="!editLoading" icon="Save" class="w-4 h-4" aria-hidden="true" />
              <span>Save</span>
            </Button>
          </div>
        </Dialog.Panel>
      </Dialog>

      <!-- Delete Confirmation Modal -->
      <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
        <Dialog.Panel>
          <div class="p-5 text-center">
            <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
            <div class="mt-5 text-3xl">Apakah Anda Yakin?</div>
            <div class="mt-2 text-slate-500">Data Tidak Bisa Dikembalikan</div>
          </div>
          <div class="px-5 pb-8 text-center">
            <Button variant="outline-secondary" class="w-24 mr-1 inline-flex items-center justify-center gap-2"
              @click="deleteModal = false">
              <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
              <span>Cancel</span>
            </Button>

            <Button variant="danger" ref="deleteButtonRef" class="w-24 inline-flex items-center justify-center gap-2"
              @click="submitDelete">
              <Lucide icon="Trash2" class="w-4 h-4" aria-hidden="true" />
              <span>Delete</span>
            </Button>
          </div>

        </Dialog.Panel>
      </Dialog>
    </div>
  </div>
</template>
