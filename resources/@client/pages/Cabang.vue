<!-- src/views/Master/CabangList.vue -->
<template>
  <div class="p-6 intro-y">
    <!-- Header & Add -->
    <div class="flex items-center mb-4">
      <h2 class="text-lg font-medium">Master Cabang</h2>
      <Button variant="primary" class="ml-auto" @click="openCreate">
        <span class="inline-flex items-center">
          <Lucide icon="Plus" class="w-4 h-4 mr-2" />
          Tambah Cabang
        </span>
      </Button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center mb-4 intro-y sm:flex-nowrap">
      <div class="mx-auto text-slate-500">
        Page {{ currentPage }} of {{ totalPages }}
      </div>

      <!-- Search with suggestions -->
      <div ref="searchWrapRef" class="relative ml-auto w-56">
        <FormInput
          v-model="searchQuery"
          placeholder="Search…"
          class="pr-10 !box"
          @focus="onSearchFocus"
          @input="onSearchInput"
          @keydown="onSearchKeydown"
        >
          <template #icon><Lucide icon="Search" /></template>
        </FormInput>

        <!-- Suggestion dropdown -->
        <div
          v-if="suggestOpen && suggestList.length"
          class="absolute z-40 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg"
        >
          <ul class="max-h-64 overflow-auto py-1">
            <li
              v-for="(s, i) in suggestList"
              :key="s.id_cabang"
              @mousedown.prevent="chooseSuggest(s)"
              :class="[
                'px-3 py-2 cursor-pointer flex items-center justify-between',
                i === activeIndex ? 'bg-slate-100' : 'hover:bg-slate-50'
              ]"
            >
              <div class="truncate">
                <div class="font-medium truncate">{{ s.nama_cabang }}</div>
                <div class="text-xs text-slate-500 truncate" v-if="s.inisial_cabang || s.inisial_segel">
                  {{ s.inisial_cabang || '-' }} • {{ s.inisial_segel || '-' }}
                </div>
              </div>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700'"
              >
                {{ s.is_active ? 'Active' : 'Inactive' }}
              </span>
            </li>
          </ul>
        </div>
      </div>

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
            <Table.Th>Nama Cabang</Table.Th>
            <Table.Th class="text-center">Status</Table.Th>
            <Table.Th class="text-center">Actions</Table.Th>
          </Table.Tr>
        </Table.Thead>
        <Table.Tbody>
          <Table.Tr
            v-for="(c, idx) in cabangs"
            :key="c.id_cabang"
            class="hover:bg-slate-100 transition-colors"
          >
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>
            <Table.Td class="px-4 py-3 whitespace-nowrap">
              {{ c.nama_cabang }}
            </Table.Td>
            <Table.Td class="px-4 py-3 text-center whitespace-nowrap">
              <span :class="c.is_active ? 'text-success' : 'text-danger'">
                {{ c.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="px-4 py-3 text-center whitespace-nowrap flex justify-center">
              <a @click.prevent="openEdit(c)" class="text-blue-600 hover:text-blue-800 mx-2">
                <Lucide icon="Edit" class="w-5 h-5"/>
              </a>
              <a @click.prevent="confirmDelete(c.id_cabang)" class="text-red-600 hover:text-red-800 mx-2">
                <Lucide icon="Trash2" class="w-5 h-5"/>
              </a>
            </Table.Td>
          </Table.Tr>

          <Table.Tr v-if="!loading && cabangs.length === 0">
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
        <Pagination.Link :disabled="currentPage===1" @click="fetchCabangs(currentPage-1)">
          <Lucide icon="ChevronLeft" />
        </Pagination.Link>
        <Pagination.Link
          v-for="page in totalPages"
          :key="page"
          :active="page===currentPage"
          @click="fetchCabangs(page)"
        >
          {{ page }}
        </Pagination.Link>
        <Pagination.Link :disabled="currentPage===totalPages" @click="fetchCabangs(currentPage+1)">
          <Lucide icon="ChevronRight" />
        </Pagination.Link>
      </Pagination>
    </div>

    <!-- Create Modal -->
    <Dialog v-model:open="createModal">
  <Dialog.Panel class="w-96 p-6">
    <h3 class="text-lg font-medium mb-4">Tambah Cabang</h3>

    <p v-if="createError" class="text-red-500 mb-2">{{ createError }}</p>

    <!-- Nama Cabang -->
    <div class="mb-3">
      <FormLabel for="nama_cabang">Nama Cabang <span class="text-danger">*</span></FormLabel>
      <FormInput
        id="nama_cabang"
        v-model="createForm.nama_cabang"
        placeholder="Contoh: Cabang Jakarta"
      />
    </div>

    <!-- Inisial Cabang -->
    <div class="mb-3">
      <FormLabel for="inisial_cabang">Inisial Cabang</FormLabel>
      <FormInput
        id="inisial_cabang"
        v-model="createForm.inisial_cabang"
        placeholder="Mis. JKT"
      />
    </div>

    <!-- Inisial Segel -->
    <div class="mb-3">
      <FormLabel for="inisial_segel">Inisial Segel</FormLabel>
      <FormInput
        id="inisial_segel"
        v-model="createForm.inisial_segel"
        placeholder="Mis. SG-JKT"
      />
    </div>

    <!-- Catatan Cabang -->
    <div class="mb-4">
      <FormLabel for="catatan_cabang">Catatan Cabang</FormLabel>
      <FormInput
        id="catatan_cabang"
        v-model="createForm.catatan_cabang"
        placeholder="Catatan internal (opsional)"
      />
      <!-- kalau mau helper text -->
      <!-- <small class="text-slate-500">Maks 500 karakter.</small> -->
    </div>

    <!-- Status & Created By -->
    <div class="flex items-end gap-2 mb-4">
      <div class="flex-1">
        <FormLabel for="is_active">Status</FormLabel>
        <FormSelect id="is_active" v-model="createForm.is_active">
          <option :value="true">Active</option>
          <option :value="false">Inactive</option>
        </FormSelect>
      </div>
      <div class="flex-1">
        <FormLabel for="created_by">Created By</FormLabel>
        <FormInput
          id="created_by"
          v-model="createForm.created_by"
          placeholder="Creator"
          readonly
          class="bg-gray-100 cursor-not-allowed"
        />
      </div>
    </div>

    <div class="flex justify-end space-x-2">
      <Button variant="outline-secondary" @click="createModal = false">Batal</Button>
      <Button variant="primary" :loading="createLoading" @click="submitCreate">Simpan</Button>
    </div>
  </Dialog.Panel>
</Dialog>

   <!-- Edit Modal -->
<Dialog v-model:open="editModal">
  <Dialog.Panel class="w-96 p-6">
    <h3 class="text-lg font-medium mb-4">Edit Cabang</h3>

    <p v-if="editError" class="text-red-500 mb-2">{{ editError }}</p>

    <div class="space-y-3">
      <!-- Nama Cabang -->
      <div>
        <label class="block text-sm text-gray-600 mb-1">Nama Cabang</label>
        <FormInput
          v-model="editForm.nama_cabang"
          placeholder="Nama Cabang"
        />
      </div>

      <!-- Inisial Cabang -->
      <div>
        <label class="block text-sm text-gray-600 mb-1">Inisial Cabang</label>
        <FormInput
          v-model="editForm.inisial_cabang"
          placeholder="Inisial Cabang"
        />
      </div>

      <!-- Inisial Segel -->
      <div>
        <label class="block text-sm text-gray-600 mb-1">Inisial Segel</label>
        <FormInput
          v-model="editForm.inisial_segel"
          placeholder="Inisial Segel"
        />
      </div>

      <!-- Catatan Cabang -->
      <div>
        <label class="block text-sm text-gray-600 mb-1">Catatan Cabang</label>
        <FormInput
          v-model="editForm.catatan_cabang"
          placeholder="Catatan Cabang"
        />
      </div>

      <!-- Status + Updated By -->
      <div class="flex items-start gap-3">
        <div class="flex-1">
          <label class="block text-sm text-gray-600 mb-1">Status</label>
          <FormSelect v-model="editForm.is_active">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </FormSelect>
        </div>

        <div class="flex-1">
          <label class="block text-sm text-gray-600 mb-1">Updated By</label>
          <FormInput
            v-model="editForm.lastupdate_by"
            placeholder="Updated By"
            readonly
            class="bg-gray-100 cursor-not-allowed"
          />
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-2 mt-6">
      <Button variant="outline-secondary" @click="editModal = false">Batal</Button>
      <Button variant="primary" :loading="editLoading" @click="submitEdit">Ubah</Button>
    </div>
  </Dialog.Panel>
</Dialog>


    <!-- Delete Confirmation Modal -->
    <Dialog v-model:open="deleteModal" :initialFocus="deleteButtonRef">
      <Dialog.Panel class="p-5 text-center">
        <Lucide icon="XCircle" class="w-16 h-16 mx-auto text-danger" />
        <div class="mt-5 text-3xl">Apakah Anda Yakin ?</div>
        <div class="mt-2 text-slate-500">Data Yang Di Hapus Tidak Bisa Dikembalikan</div>
        <div class="px-5 pb-8 text-center">
          <Button variant="outline-secondary" class="w-24 mr-1" @click="deleteModal = false">Batal</Button>
          <Button variant="danger" ref="deleteButtonRef" class="w-24" @click="submitDelete">Hapus</Button>
        </div>
      </Dialog.Panel>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Pagination from '@/components/Base/Pagination'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import { debounce } from 'lodash'

// current user untuk created_by / lastupdate_by
const currentUserName = ref('')

// list & paging
const cabangs      = ref<any[]>([])
const searchQuery  = ref('')
const perPage      = ref(10)
const currentPage  = ref(1)
const totalPages   = ref(1)
const loading      = ref(false)

// suggestions
const suggestOpen   = ref(false)
const suggestList   = ref<any[]>([])
const activeIndex   = ref(-1)
const searchWrapRef = ref<HTMLElement|null>(null)

// create modal state
const createModal   = ref(false)
const createForm    = reactive({
  nama_cabang: '',
  inisial_cabang: '',
  inisial_segel: '',
  catatan_cabang: '',
  is_active:   true,
  created_by:  ''
})
const createLoading = ref(false)
const createError   = ref<string|null>(null)

// edit modal state
const editModal     = ref(false)
const editForm      = reactive({
  id_cabang:       0,
  nama_cabang:     '',
  inisial_cabang:  '',
  inisial_segel:   '',
  catatan_cabang:  '',
  is_active:       true,
  lastupdate_by:   ''
})
const editLoading   = ref(false)
const editError     = ref<string|null>(null)

// delete modal state
const deleteModal     = ref(false)
const deleteButtonRef = ref<HTMLButtonElement|null>(null)
let cabangToDelete: number|null = null

const router = useRouter()

// INIT
onMounted(async () => {
  try {
    const { data } = await axios.get('/api/user')
    currentUserName.value = data.name
  } catch {}
  fetchCabangs()
  document.addEventListener('mousedown', handleClickOutside)
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})

// FETCH LIST
async function fetchCabangs(page = 1) {
  loading.value = true
  try {
    const res = await axios.get('/api/cabangs', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined
      }
    })
    cabangs.value     = res.data.data
    currentPage.value = res.data.current_page
    totalPages.value  = res.data.last_page
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  } finally {
    loading.value = false
  }
}

// SUGGESTIONS
const fetchSuggest = debounce(async () => {
  try {
    const { data } = await axios.get('/api/cabangs/suggest', {
      params: { q: searchQuery.value, limit: 8 }
    })
    suggestList.value = data
    suggestOpen.value = true
    activeIndex.value = -1
  } catch (e) {
    // optional: handle error
  }
}, 200)

function onSearchFocus() {
  fetchSuggest() // tampilkan default suggestion
}
function onSearchInput() {
  fetchSuggest()
}
function onSearchKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') {
    e.preventDefault()
    if (suggestOpen.value && activeIndex.value >= 0) {
      chooseSuggest(suggestList.value[activeIndex.value])
    } else {
      suggestOpen.value = false
      fetchCabangs(1)
    }
    return
  }
  if (!suggestOpen.value || suggestList.value.length === 0) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % suggestList.value.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeIndex.value = (activeIndex.value - 1 + suggestList.value.length) % suggestList.value.length
  } else if (e.key === 'Escape') {
    suggestOpen.value = false
  }
}
function chooseSuggest(item: any) {
  searchQuery.value = item.nama_cabang
  suggestOpen.value = false
  fetchCabangs(1)
}
function handleClickOutside(ev: MouseEvent) {
  if (!searchWrapRef.value) return
  if (!searchWrapRef.value.contains(ev.target as Node)) {
    suggestOpen.value = false
  }
}

// SEARCH & PER-PAGE
// Jika ingin auto-filter saat mengetik, aktifkan watcher berikut.
// import { watch } from 'vue'
// watch(searchQuery, debounce(() => fetchCabangs(1), 300))
import { watch } from 'vue'
watch(perPage, () => fetchCabangs(1))

// CREATE
function openCreate() {
  createForm.nama_cabang   = ''
  createForm.inisial_cabang= ''
  createForm.inisial_segel = ''
  createForm.catatan_cabang= ''
  createForm.is_active     = true
  createForm.created_by    = currentUserName.value
  createError.value        = null
  createModal.value        = true
}
async function submitCreate() {
  if (!createForm.nama_cabang.trim()) {
    return Swal.fire('Error','Nama Cabang tidak boleh kosong','error')
  }
  createLoading.value = true
  try {
    const { data } = await axios.post('/api/cabangs', createForm)
    // Refresh lebih aman agar sinkron dengan pagination
    await fetchCabangs(1)
    createModal.value = false
    Swal.fire({ icon:'success', title:'Cabang berhasil dibuat', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    createError.value = e.response?.data?.message || 'Gagal membuat cabang'
  } finally {
    createLoading.value = false
  }
}

// EDIT
function openEdit(c: any) {
  editForm.id_cabang      = c.id_cabang
  editForm.nama_cabang    = c.nama_cabang
  editForm.inisial_cabang = c.inisial_cabang
  editForm.inisial_segel  = c.inisial_segel
  editForm.catatan_cabang = c.catatan_cabang
  editForm.is_active      = c.is_active
  editForm.lastupdate_by  = currentUserName.value
  editError.value         = null
  editModal.value         = true
}
async function submitEdit() {
  if (!editForm.nama_cabang.trim()) {
    return Swal.fire('Error','Nama Cabang tidak boleh kosong','error')
  }
  editLoading.value = true
  try {
    const { data } = await axios.put(`/api/cabangs/${editForm.id_cabang}`, {
      nama_cabang:    editForm.nama_cabang,
      inisial_cabang: editForm.inisial_cabang,
      inisial_segel:  editForm.inisial_segel,
      catatan_cabang: editForm.catatan_cabang,
      is_active:      editForm.is_active,
      lastupdate_by:  editForm.lastupdate_by
    })
    const idx = cabangs.value.findIndex(c => c.id_cabang === data.id_cabang)
    if (idx !== -1) cabangs.value[idx] = data
    editModal.value = false
    Swal.fire({ icon:'success', title:'Cabang berhasil diperbarui', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    editError.value = e.response?.data?.message || 'Gagal memperbarui cabang'
  } finally {
    editLoading.value = false
  }
}

// DELETE
function confirmDelete(id: number) {
  cabangToDelete = id
  deleteModal.value = true
}
async function submitDelete() {
  if (!cabangToDelete) return
  try {
    await axios.delete(`/api/cabangs/${cabangToDelete}`)
    cabangs.value = cabangs.value.filter(c => c.id_cabang !== cabangToDelete)
    deleteModal.value = false
    Swal.fire({ icon:'success', title:'Cabang berhasil dihapus', toast:true, position:'top-end', timer:2000 })
  } catch (e:any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal menghapus', 'error')
  }
}
</script>

<style scoped>
/* pastikan dropdown di atas elemen lain */
.z-40 { z-index: 40; }
</style>
