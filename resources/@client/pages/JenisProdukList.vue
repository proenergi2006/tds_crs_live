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
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'

const jenisProduks = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const loading = ref(false)

// create
const createModal = ref(false)
const createForm = reactive({
  nama: '',
  deskripsi: '',
  is_active: true
})
const createLoading = ref(false)
const createFieldErr = reactive({ nama: '' })

// edit
const editModal = ref(false)
const editForm = reactive({
  id_jenis: 0,
  nama: '',
  deskripsi: '',
  is_active: true
})
const editLoading = ref(false)
const editFieldErr = reactive({ nama: '' })

// delete
const deleteModal = ref(false)
const deleteId = ref<number | null>(null)
const deleteButtonRef = ref<HTMLButtonElement | null>(null)

onMounted(() => {
  fetchJenisProduks()
})

async function fetchJenisProduks(page = 1) {
  loading.value = true
  try {
    const res = await axios.get('/api/jenis-produks', {
      params: { page, per_page: perPage.value, search: searchQuery.value || undefined }
    })
    jenisProduks.value = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value = res.data.last_page
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  } finally {
    loading.value = false
  }
}

watch(searchQuery, debounce(() => fetchJenisProduks(1), 300))
watch(perPage, () => fetchJenisProduks(1))

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchJenisProduks(page)
}

function openCreate() {
  Object.assign(createForm, { nama: '', deskripsi: '', is_active: true })
  Object.assign(createFieldErr, { nama: '' })
  createModal.value = true
}

async function submitCreate() {
  // validasi sederhana
  Object.assign(createFieldErr, { nama: '' })
  if (!createForm.nama.trim()) {
    createFieldErr.nama = 'Nama wajib diisi'
    return
  }

  createLoading.value = true
  try {
    const { data } = await axios.post('/api/jenis-produks', createForm)
    jenisProduks.value.unshift(data)
    createModal.value = false
    Swal.fire({ icon: 'success', title: 'Data berhasil dibuat', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal membuat data', 'error')
  } finally {
    createLoading.value = false
  }
}

function openEdit(p: any) {
  Object.assign(editForm, {
    id_jenis: p.id_jenis,
    nama: p.nama,
    deskripsi: p.deskripsi,
    is_active: p.is_active
  })
  Object.assign(editFieldErr, { nama: '' })
  editModal.value = true
}

async function submitEdit() {
  Object.assign(editFieldErr, { nama: '' })
  if (!editForm.nama.trim()) {
    editFieldErr.nama = 'Nama wajib diisi'
    return
  }

  editLoading.value = true
  try {
    const { data } = await axios.put(`/api/jenis-produks/${editForm.id_jenis}`, editForm)
    const idx = jenisProduks.value.findIndex(r => r.id_jenis === data.id_jenis)
    if (idx !== -1) jenisProduks.value[idx] = data
    editModal.value = false
    Swal.fire({ icon: 'success', title: 'Data berhasil diperbarui', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memperbarui data', 'error')
  } finally {
    editLoading.value = false
  }
}

function confirmDelete(id: number) {
  deleteId.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteId.value) return
  try {
    await axios.delete(`/api/jenis-produks/${deleteId.value}`)
    jenisProduks.value = jenisProduks.value.filter(j => j.id_jenis !== deleteId.value)
    deleteModal.value = false
    deleteId.value = null
    Swal.fire({ icon: 'success', title: 'Data berhasil dihapus', toast: true, position: 'top-end', timer: 2000 })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal menghapus data', 'error')
  }
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <PageHeader title="Master Jenis Produk" description="Kelola jenis produk dan statusnya">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Jenis
          </Button>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="0" :total-pages="totalPages" search-placeholder="Cari jenis produk..."
        @page-change="goToPage" />

      <DataList :loading="loading" :empty="jenisProduks.length === 0" :colspan="5" loading-text="Memuat jenis produk..."
        empty-description="Belum ada data jenis produk.">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama</Table.Th>
          <Table.Th>Deskripsi</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Actions</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(p, idx) in jenisProduks" :key="p.id_jenis" class="hover:bg-slate-100 transition-colors">
            <Table.Td>{{ (currentPage - 1) * perPage + idx + 1 }}</Table.Td>
            <Table.Td>{{ p.nama }}</Table.Td>
            <Table.Td>{{ p.deskripsi || '-' }}</Table.Td>
            <Table.Td class="text-center">
              <span :class="p.is_active ? 'text-success' : 'text-danger'">
                <Lucide icon="CheckSquare" class="w-4 h-4 inline-block mr-1" />
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center flex justify-center">
              <a @click.prevent="openEdit(p)"
                class="text-blue-600 hover:text-blue-800 mx-2 inline-flex items-center gap-1">
                <Lucide icon="Edit" class="w-5 h-5" />
                <span class="sr-only">Edit</span>
              </a>
              <a @click.prevent="confirmDelete(p.id_jenis)"
                class="text-red-600 hover:text-red-800 mx-2 inline-flex items-center gap-1">
                <Lucide icon="Trash2" class="w-5 h-5" />
                <span class="sr-only">Delete</span>
              </a>
            </Table.Td>
          </Table.Tr>

          <Table.Tr v-if="!loading && jenisProduks.length === 0">
            <Table.Td colspan="5" class="text-center py-8 text-slate-500">Tidak ada data.</Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Create Modal -->
      <Dialog v-model:open="createModal">
        <Dialog.Panel class="w-96 p-6">
          <h3 class="text-lg font-medium mb-4">Add New Jenis Produk</h3>

          <div class="space-y-3">
            <div>
              <FormLabel htmlFor="create-nama">Nama <span class="text-danger">*</span></FormLabel>
              <FormInput id="create-nama" v-model="createForm.nama" placeholder="Nama"
                :class="createFieldErr.nama ? 'border-rose-500' : ''" required />
              <small v-if="createFieldErr.nama" class="text-rose-600">{{ createFieldErr.nama }}</small>
            </div>

            <div>
              <FormLabel htmlFor="create-deskripsi">Deskripsi</FormLabel>
              <FormInput id="create-deskripsi" v-model="createForm.deskripsi" placeholder="Deskripsi (opsional)" />
            </div>

            <div>
              <FormLabel htmlFor="create-status">Status</FormLabel>
              <FormSelect id="create-status" v-model="createForm.is_active">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </FormSelect>
            </div>
          </div>

          <div class="flex justify-end space-x-2 mt-4">
            <Button variant="outline-secondary" @click="createModal = false" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" />
              <span>Cancel</span>
            </Button>
            <Button variant="primary" :loading="createLoading" @click="submitCreate"
              class="inline-flex items-center gap-2">
              <Lucide v-if="!createLoading" icon="PlusCircle" class="w-4 h-4" />
              <span>Create</span>
            </Button>
          </div>
        </Dialog.Panel>
      </Dialog>

      <!-- Edit Modal -->
      <Dialog v-model:open="editModal">
        <Dialog.Panel class="w-96 p-6">
          <h3 class="text-lg font-medium mb-4">Edit Jenis Produk</h3>

          <div class="space-y-3">
            <div>
              <FormLabel htmlFor="edit-nama">Nama <span class="text-danger">*</span></FormLabel>
              <FormInput id="edit-nama" v-model="editForm.nama" placeholder="Nama"
                :class="editFieldErr.nama ? 'border-rose-500' : ''" required />
              <small v-if="editFieldErr.nama" class="text-rose-600">{{ editFieldErr.nama }}</small>
            </div>

            <div>
              <FormLabel htmlFor="edit-deskripsi">Deskripsi</FormLabel>
              <FormInput id="edit-deskripsi" v-model="editForm.deskripsi" placeholder="Deskripsi (opsional)" />
            </div>

            <div>
              <FormLabel htmlFor="edit-status">Status</FormLabel>
              <FormSelect id="edit-status" v-model="editForm.is_active">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </FormSelect>
            </div>
          </div>

          <div class="flex justify-end space-x-2 mt-4">
            <Button variant="outline-secondary" @click="editModal = false" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" />
              <span>Cancel</span>
            </Button>
            <Button variant="primary" :loading="editLoading" @click="submitEdit" class="inline-flex items-center gap-2">
              <Lucide v-if="!editLoading" icon="Save" class="w-4 h-4" />
              <span>Save</span>
            </Button>
          </div>
        </Dialog.Panel>
      </Dialog>

      <!-- Delete Confirmation Modal -->
      <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
        <Dialog.Panel class="p-5 text-center">
          <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
          <div class="mt-5 text-3xl">Apakah Anda Yakin ?</div>
          <div class="mt-2 text-slate-500">Data tidak bisa dikembalikan.</div>
          <div class="px-5 pb-8 text-center mt-4">
            <Button variant="outline-secondary" class="w-28 mr-2 inline-flex items-center justify-center gap-2"
              @click="deleteModal = false">
              <Lucide icon="X" class="w-4 h-4" />
              <span>Batal</span>
            </Button>
            <Button variant="danger" ref="deleteButtonRef" class="w-28 inline-flex items-center justify-center gap-2"
              @click="submitDelete">
              <Lucide icon="Trash2" class="w-4 h-4" />
              <span>Hapus</span>
            </Button>
          </div>
        </Dialog.Panel>
      </Dialog>
    </div>
  </div>
</template>
