<!-- src/pages/UkuranList.vue -->
<template>
  <div class="p-6 intro-y">
    <!-- Header & Add -->
    <div class="flex items-center mb-4">
      <h2 class="text-lg font-medium">Master Ukuran</h2>
      <Button
        variant="primary"
        class="ml-auto inline-flex items-center gap-2"
        @click="openCreate"
      >
        <Lucide icon="Plus" class="w-4 h-4" aria-hidden="true" />
        <span>Tambah Ukuran</span>
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mb-4 intro-y sm:flex-nowrap">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
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

    <!-- Table -->
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <Table class="min-w-full divide-y divide-slate-200">
        <Table.Thead>
          <Table.Tr>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>Nama Ukuran</Table.Th>
            <Table.Th>Satuan</Table.Th>
            <Table.Th class="text-center">Actions</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr
            v-for="(u, idx) in ukurans"
            :key="u.id_ukuran"
            class="hover:bg-slate-100 transition-colors"
          >
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ u.nama_ukuran }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ u.satuan?.nama_satuan || '-' }}
            </Table.Td>
            <Table.Td class="px-4 py-3 text-center whitespace-nowrap flex justify-center">
              <a @click.prevent="openEdit(u)" class="text-blue-600 hover:text-blue-800 mx-2">
                <Lucide icon="Edit" class="w-5 h-5" />
              </a>
              <a @click.prevent="confirmDelete(u.id_ukuran)" class="text-red-600 hover:text-red-800 mx-2">
                <Lucide icon="Trash2" class="w-5 h-5" />
              </a>
            </Table.Td>
          </Table.Tr>

          <Table.Tr v-if="!loading && ukurans.length === 0">
            <Table.Td colspan="4" class="text-center py-8 text-slate-500">
              Tidak ada data.
            </Table.Td>
          </Table.Tr>
        </Table.Tbody>
      </Table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-4 intro-y">
      <Pagination>
        <Pagination.Link :disabled="currentPage===1" @click="fetchUkuran(currentPage-1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page===currentPage"
          @click="fetchUkuran(page)"
        >
          {{ page }}
        </Pagination.Link>
        <Pagination.Link :disabled="currentPage===totalPages" @click="fetchUkuran(currentPage+1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>

    <!-- Create Modal -->
    <Dialog v-model:open="createModal">
      <Dialog.Panel class="w-96 p-6">
        <h3 class="text-lg font-medium mb-4">Tambah Ukuran</h3>
        <p v-if="createError" class="text-red-500 mb-2">{{ createError }}</p>

        <div class="space-y-3">
          <!-- Nama Ukuran (required) -->
          <div>
            <FormLabel htmlFor="create-nama">Nama Ukuran <span class="text-danger">*</span></FormLabel>
            <FormInput
              id="create-nama"
              v-model="createForm.nama_ukuran"
              placeholder="Nama Ukuran"
              :class="createFieldErr.nama_ukuran ? 'border-rose-500' : ''"
              required
            />
            <small v-if="createFieldErr.nama_ukuran" class="text-rose-600">{{ createFieldErr.nama_ukuran }}</small>
          </div>

          <!-- Satuan (required) -->
          <div>
            <FormLabel htmlFor="create-satuan">Satuan <span class="text-danger">*</span></FormLabel>
            <FormSelect
              id="create-satuan"
              v-model="createForm.id_satuan"
              :class="createFieldErr.id_satuan ? 'border-rose-500' : ''"
              required
            >
              <option disabled value="">-- Pilih Satuan --</option>
              <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">
                {{ s.nama_satuan }}
              </option>
            </FormSelect>
            <small v-if="createFieldErr.id_satuan" class="text-rose-600">{{ createFieldErr.id_satuan }}</small>
          </div>

          <!-- Created By -->
          <div>
            <FormLabel htmlFor="create-by">Created By</FormLabel>
            <FormInput
              id="create-by"
              v-model="createForm.created_by"
              placeholder="Created By"
              readonly
              class="bg-gray-100 cursor-not-allowed"
            />
          </div>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
          <Button
            variant="outline-secondary"
            @click="createModal = false"
            class="inline-flex items-center gap-2"
          >
            <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
            <span>Cancel</span>
          </Button>
          <Button
            variant="primary"
            :loading="createLoading"
            @click="submitCreate"
            class="inline-flex items-center gap-2"
          >
            <Lucide v-if="!createLoading" icon="PlusCircle" class="w-4 h-4" aria-hidden="true" />
            <span>Create</span>
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog v-model:open="editModal">
      <Dialog.Panel class="w-96 p-6">
        <h3 class="text-lg font-medium mb-4">Edit Ukuran</h3>
        <p v-if="editError" class="text-red-500 mb-2">{{ editError }}</p>

        <div class="space-y-3">
          <!-- Nama Ukuran (required) -->
          <div>
            <FormLabel htmlFor="edit-nama">Nama Ukuran <span class="text-danger">*</span></FormLabel>
            <FormInput
              id="edit-nama"
              v-model="editForm.nama_ukuran"
              placeholder="Nama Ukuran"
              :class="editFieldErr.nama_ukuran ? 'border-rose-500' : ''"
              required
            />
            <small v-if="editFieldErr.nama_ukuran" class="text-rose-600">{{ editFieldErr.nama_ukuran }}</small>
          </div>

          <!-- Satuan (required) -->
          <div>
            <FormLabel htmlFor="edit-satuan">Satuan <span class="text-danger">*</span></FormLabel>
            <FormSelect
              id="edit-satuan"
              v-model="editForm.id_satuan"
              :class="editFieldErr.id_satuan ? 'border-rose-500' : ''"
              required
            >
              <option disabled value="">-- Pilih Satuan --</option>
              <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">
                {{ s.nama_satuan }}
              </option>
            </FormSelect>
            <small v-if="editFieldErr.id_satuan" class="text-rose-600">{{ editFieldErr.id_satuan }}</small>
          </div>

          <!-- Updated By -->
          <div>
            <FormLabel htmlFor="edit-by">Updated By</FormLabel>
            <FormInput
              id="edit-by"
              v-model="editForm.lastupdate_by"
              placeholder="Updated By"
              readonly
              class="bg-gray-100 cursor-not-allowed"
            />
          </div>
        </div>

        <div class="flex justify-end space-x-2 mt-4">
          <Button
            variant="outline-secondary"
            @click="editModal = false"
            class="inline-flex items-center gap-2"
          >
            <Lucide icon="X" class="w-4 h-4" aria-hidden="true" />
            <span>Cancel</span>
          </Button>
          <Button
            variant="primary"
            :loading="editLoading"
            @click="submitEdit"
            class="inline-flex items-center gap-2"
          >
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
          <div class="mt-5 text-3xl">Apakah Anda Yakin ?</div>
          <div class="mt-2 text-slate-500">Data Tidak Bisa Di kembalikan </div>
        </div>
        <div class="px-5 pb-8 text-center">
          <Button variant="outline-secondary" @click="deleteModal = false" class="w-24 mr-1">
            Cancel
          </Button>
          <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">
            Delete
          </Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'

// dropdown Satuan
const satuans = ref<any[]>([])

// current user
const currentUserName = ref('')

// fetch current user & satuans dropdown
async function initDropdowns() {
  try {
    const { data: u } = await axios.get('/api/user')
    currentUserName.value = u.name
  } catch {}
  try {
    const { data } = await axios.get('/api/satuans', { params:{ per_page:100 } })
    satuans.value = data.data || data
  } catch {}
}

// paging & data
const ukurans     = ref<any[]>([])
const searchQuery = ref('')
const perPage     = ref(10)
const currentPage = ref(1)
const totalPages  = ref(1)
const loading     = ref(false)
const error       = ref<string|null>(null)

// modals & forms
const createModal   = ref(false)
const createForm    = reactive({
  nama_ukuran:'', id_satuan:'', created_by:''
})
const createLoading = ref(false)
const createError   = ref<string|null>(null)

const editModal   = ref(false)
const editForm    = reactive({
  id_ukuran:0, nama_ukuran:'', id_satuan:'', lastupdate_by:''
})
const editLoading = ref(false)
const editError   = ref<string|null>(null)

const deleteModal     = ref(false)
const deleteButtonRef = ref<HTMLButtonElement|null>(null)
let ukuranToDelete: number|null = null

// error per-field (frontend)
const createFieldErr = reactive({ nama_ukuran: '', id_satuan: '' })
const editFieldErr   = reactive({ nama_ukuran: '', id_satuan: '' })

// fetch list Ukuran
async function fetchUkuran(page = 1) {
  loading.value = true
  try {
    const res = await axios.get('/api/ukurans', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined
      }
    })
    ukurans.value     = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value  = res.data.last_page
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data','error')
  } finally {
    loading.value = false
  }
}

// init onMounted
onMounted(async () => {
  await initDropdowns()
  fetchUkuran()
})

// watchers untuk search & perPage
watch(searchQuery, debounce(() => fetchUkuran(1), 300))
watch(perPage, () => fetchUkuran(1))

// Create
function openCreate() {
  Object.assign(createForm, {
    nama_ukuran:'', id_satuan:'', created_by:currentUserName.value
  })
  Object.assign(createFieldErr, { nama_ukuran:'', id_satuan: '' })
  createError.value = null
  createModal.value = true
}
async function submitCreate() {
  // reset error
  Object.assign(createFieldErr, { nama_ukuran:'', id_satuan:'' })

  // validasi sederhana
  let ok = true
  if (!createForm.nama_ukuran.trim()) {
    createFieldErr.nama_ukuran = 'Nama Ukuran wajib diisi'
    ok = false
  }
  if (!createForm.id_satuan) {
    createFieldErr.id_satuan = 'Satuan wajib dipilih'
    ok = false
  }
  if (!ok) return

  createLoading.value = true
  try {
    const { data } = await axios.post('/api/ukurans', createForm)
    ukurans.value.unshift(data)
    createModal.value = false
    Swal.fire({ icon:'success', title:'Ukuran berhasil dibuat', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    createError.value = e.response?.data?.message || 'Gagal membuat'
  } finally {
    createLoading.value = false
  }
}

// Edit
function openEdit(u:any) {
  Object.assign(editForm, {
    id_ukuran: u.id_ukuran,
    nama_ukuran: u.nama_ukuran,
    id_satuan: u.id_satuan,
    lastupdate_by: currentUserName.value
  })
  Object.assign(editFieldErr, { nama_ukuran:'', id_satuan: '' })
  editError.value = null
  editModal.value = true
}
async function submitEdit() {
  Object.assign(editFieldErr, { nama_ukuran:'', id_satuan:'' })
  let ok = true
  if (!editForm.nama_ukuran.trim()) {
    editFieldErr.nama_ukuran = 'Nama Ukuran wajib diisi'
    ok = false
  }
  if (!editForm.id_satuan) {
    editFieldErr.id_satuan = 'Satuan wajib dipilih'
    ok = false
  }
  if (!ok) return

  editLoading.value = true
  try {
    const { data } = await axios.put(
      `/api/ukurans/${editForm.id_ukuran}`,
      {
        nama_ukuran: editForm.nama_ukuran,
        id_satuan: editForm.id_satuan,
        lastupdate_by: editForm.lastupdate_by
      }
    )
    const idx = ukurans.value.findIndex(x => x.id_ukuran === data.id_ukuran)
    if (idx !== -1) ukurans.value[idx] = data
    editModal.value = false
    Swal.fire({ icon:'success', title:'Ukuran diperbarui', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    editError.value = e.response?.data?.message || 'Gagal update'
  } finally {
    editLoading.value = false
  }
}

// Delete
function confirmDelete(id:number) {
  ukuranToDelete = id
  deleteModal.value = true
}
async function submitDelete() {
  if (!ukuranToDelete) return
  deleteModal.value = false
  try {
    await axios.delete(`/api/ukurans/${ukuranToDelete}`)
    ukurans.value = ukurans.value.filter(u => u.id_ukuran !== ukuranToDelete)
    Swal.fire({ icon:'success', title:'Ukuran dihapus', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal hapus','error')
  } finally {
    ukuranToDelete = null
  }
}
</script>
