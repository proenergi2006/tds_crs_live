<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'

import { createResourceApi } from '@/utils/resourceApi'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

type ApprovalTemplateRow = {
  id_template: number
  code: string
  name: string
  is_active: boolean
  steps_count: number
}

const approvalTemplateApi = createResourceApi('/approval-templates')
const { success, error } = useNotification()
const auth = useAuthStore()
const router = useRouter()

/* State: data & pagination */
const allTemplates = ref<ApprovalTemplateRow[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const loading = ref(false)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

const canManage = computed(() => auth.can('approval-template.manage'))

onMounted(() => {
  fetchData()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

const filteredTemplates = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allTemplates.value

  return allTemplates.value.filter(item => {
    return [item.code, item.name].some(value =>
      String(value || '').toLowerCase().includes(query),
    )
  })
})

const totalRecords = computed(() => filteredTemplates.value.length)

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(totalRecords.value / perPage.value))
})

const templates = computed(() => {
  const start = (currentPage.value - 1) * perPage.value

  return filteredTemplates.value.slice(start, start + perPage.value)
})

/* Data */
async function fetchData() {
  loading.value = true

  try {
    const { data } = await approvalTemplateApi.getAll({ as_list: true })

    allTemplates.value = Array.isArray(data) ? data : []
    currentPage.value = 1
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data approval template')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function resetToFirstPage() {
  currentPage.value = 1
}

/* Form */
function openCreate() {
  router.push({ name: 'approval-templates-create' })
}

function openEdit(target: ApprovalTemplateRow) {
  router.push({ name: 'approval-templates-edit', params: { id: target.id_template } })
}

/* Delete */
function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return

  deleteLoading.value = true

  try {
    await approvalTemplateApi.destroy(deleteTarget.value)

    allTemplates.value = allTemplates.value.filter(
      item => item.id_template !== deleteTarget.value,
    )

    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }

    deleteModal.value = false
    success('Berhasil', 'Approval template berhasil dihapus.')
  } catch (e: any) {
    // Backend mengembalikan 409 dengan pesan jelas kalau template masih
    // punya riwayat document_approvals -- tampilkan apa adanya, bukan pesan generik.
    error(
      'Gagal menghapus',
      e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.',
    )
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- Page Header -->
      <PageHeader title="Approval Template"
        description="Kelola urutan step & role approval yang dipakai alur verifikasi customer.">
        <template #action>
          <Button v-if="canManage" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Template
          </Button>
        </template>
      </PageHeader>

      <!-- Data Table List -->
      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="templates.length === 0" :colspan="5" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari kode / nama template..."
        loading-text="Memuat data approval template..." empty-description="Belum ada approval template untuk ditampilkan."
        @page-change="goToPage">
        <template #head>
          <Table.Th>Kode</Table.Th>
          <Table.Th>Nama Template</Table.Th>
          <Table.Th class="text-center">Jumlah Step</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="item in templates" :key="item.id_template" class="transition hover:bg-slate-50">
            <Table.Td class="font-strong">
              {{ item.code }}
            </Table.Td>
            <Table.Td>
              {{ item.name }}
            </Table.Td>
            <Table.Td class="font-num text-center">
              {{ item.steps_count }}
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex rounded-full px-3 py-1"
                :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="openEdit(item)" title="Edit">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button v-if="canManage" variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  @click="confirmDelete(item.id_template)" title="Hapus">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <!-- Delete Confirmation Modal -->
      <DeleteRecordDialog :open="deleteModal" title="Hapus Approval Template"
        description="Template yang masih punya riwayat siklus persetujuan tidak akan bisa dihapus."
        :loading="deleteLoading" @close="deleteModal = false" @confirm="submitDelete" />
    </div>
  </div>
</template>
