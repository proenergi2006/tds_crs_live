<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'
import { formatDate } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'

const router = useRouter()
const { success, error } = useNotification()
const vendorPoApi = createResourceApi('/vendor-pos')

const vendorPos = ref<any[]>([])
const vendors = ref<any[]>([])
const terminals = ref<any[]>([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, total: 0 })

const searchQuery = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')
const filterTerminal = ref('')
const filterVendor = ref('')
const perPage = ref(10)

const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<{ id: number; label: string } | null>(null)

const activeFilterCount = computed(() =>
  [
    filterDateFrom.value,
    filterDateTo.value,
    filterTerminal.value,
    filterVendor.value
  ].filter(Boolean).length,
)

onMounted(async () => {
  await Promise.all([fetchVendors(), fetchTerminals()])
  fetchData(1)
})

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

async function fetchData(page = 1) {
  loading.value = true
  try {
    const { data } = await vendorPoApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      tanggal_dari: filterDateFrom.value || undefined,
      tanggal_sampai: filterDateTo.value || undefined,
      id_terminal: filterTerminal.value || undefined,
      id_vendor: filterVendor.value || undefined,
    })
    vendorPos.value = data.data || []
    meta.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      total: data.total || 0,
    }
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

async function fetchVendors() {
  try {
    const { data } = await axios.get('/api/vendors', { params: { per_page: 200 } })
    vendors.value = data.data || data || []
  } catch {
    vendors.value = []
  }
}

async function fetchTerminals() {
  try {
    const { data } = await axios.get('/api/terminals', { params: { per_page: 200 } })
    terminals.value = data.data || data || []
  } catch {
    terminals.value = []
  }
}

function goToPage(page: number) {
  if (page < 1 || page > meta.value.last_page) return
  fetchData(page)
}

function resetFilter() {
  filterDateFrom.value = ''
  filterDateTo.value = ''
  filterTerminal.value = ''
  filterVendor.value = ''
  fetchData(1)
}

function goCreate() {
  router.push({ name: 'vendor-pos-create' })
}

function goDetail(id: number) {
  router.push({ name: 'vendor-pos-detail', params: { id } })
}

function goEdit(id: number) {
  router.push({ name: 'vendor-pos-edit', params: { id } })
}

function goReceive(id: number) {
  router.push({ name: 'vendor-pos-receive', params: { id } })
}

async function previewPdf(id: number) {
  const previewTab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (previewTab) {
      previewTab.location.href = url
    } else {
      window.open(url, '_blank')
    }
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    previewTab?.close()
    error('Gagal', 'Gagal membuka preview PDF')
  }
}

function confirmDelete(id: number, label: string) {
  deleteTarget.value = { id, label }
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await vendorPoApi.destroy(deleteTarget.value.id)
    vendorPos.value = vendorPos.value.filter(po => po.id_po !== deleteTarget.value!.id)
    meta.value.total = Math.max(0, meta.value.total - 1)
    deleteModal.value = false
    success('Berhasil', `PO ${deleteTarget.value.label} berhasil dihapus`)
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal menghapus PO')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

function isEditableState(key?: string): boolean {
  return key !== 'Approved'
}

function isUnreleaseState(key?: string): boolean {
  return key === 'Approved'
}

function isDestroyableState(key?: string): boolean {
  return key !== 'WaitingCeo' && key !== 'Approved'
}

function statusLabel(statusPo?: { key: string; label: string }) {
  return statusPo?.label ?? '-'
}

function statusBadgeClass(statusPo?: { key: string; label: string }) {
  const map: Record<string, string> = {
    Draft: 'bg-slate-100 text-slate-600',
    WaitingCeo: 'bg-blue-100 text-blue-700',
    Approved: 'bg-emerald-100 text-emerald-700',
    DitolakCfo: 'bg-red-100 text-red-700',
    DitolakCeo: 'bg-red-100 text-red-700',
  }
  return map[statusPo?.key ?? ''] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-y">

      <PageHeader title="Daftar PO Supplier"
        description="Kelola Purchase Order vendor, filter data, dan akses aksi dengan cepat.">
        <template #action>
          <Button variant="white" class="inline-flex items-center gap-2" @click="goCreate">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah PO
          </Button>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="vendorPos.length === 0" :colspan="7" :show-footer="true" :show-toolbar="true" :total="meta.total"
        :current-page="meta.current_page" :total-pages="meta.last_page" :active-filter-count="activeFilterCount"
        search-placeholder="Cari Nomor PO..." loading-text="Memuat data PO..."
        empty-description="Belum ada data PO untuk ditampilkan." @page-change="goToPage">
        <template #filters="{ close }">
          <div class="space-y-4 p-1">
            <div>
              <div class="px-3 pt-1 pb-2 text-overline">Tanggal Dari</div>
              <FormInput v-model="filterDateFrom" type="date" class="!box" />
            </div>
            <div>
              <div class="px-3 pb-2 text-overline">Tanggal Sampai</div>
              <FormInput v-model="filterDateTo" type="date" class="!box" />
            </div>
            <div>
              <div class="px-3 pb-2 text-overline">Terminal</div>
              <FormSelect v-model="filterTerminal" class="!box">
                <option value="">— Semua Terminal —</option>
                <option v-for="t in terminals" :key="t.id_terminal" :value="t.id_terminal">
                  {{ t.nama_terminal }}
                </option>
              </FormSelect>
            </div>
            <div>
              <div class="px-3 pb-2 text-overline">Vendor</div>
              <FormSelect v-model="filterVendor" class="!box">
                <option value="">— Semua Vendor —</option>
                <option v-for="v in vendors" :key="v.id_vendor" :value="v.id_vendor">
                  {{ v.nama_vendor }}
                </option>
              </FormSelect>
            </div>
            <div class="flex gap-2 pt-3 border-slate-100 border-t">
              <Button type="button" variant="primary" class="inline-flex flex-1 justify-center items-center gap-2"
                :disabled="loading" @click="() => { fetchData(1); close() }">
                <Lucide icon="Search" class="w-4 h-4" />
                Cari
              </Button>
              <Button type="button" variant="outline-secondary" class="flex-1" :disabled="activeFilterCount === 0"
                @click="() => { resetFilter(); close() }">
                Reset
              </Button>
            </div>
          </div>
        </template>

        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nomor PO</Table.Th>
          <Table.Th>Tanggal PO</Table.Th>
          <Table.Th>Vendor</Table.Th>
          <Table.Th>Terminal</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(po, idx) in vendorPos" :key="po.id_po" class="hover:bg-slate-50 transition">
            <Table.Td class="num-sm text-center">
              {{ (meta.current_page - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <div class="text-body-strong">{{ po.nomor_po }}</div>
            </Table.Td>
            <Table.Td class="text-slate-700 whitespace-nowrap">{{ formatDate(po.tanggal_inven) }}</Table.Td>
            <Table.Td class="text-slate-700">{{ po.vendor?.nama_vendor || '-' }}</Table.Td>
            <Table.Td class="text-slate-700">{{ po.terminal?.nama_terminal || '-' }}</Table.Td>
            <Table.Td class="text-center">
              <span class="inline-flex px-3 py-1 rounded-full text-form-label" :class="statusBadgeClass(po.status_po)">
                {{ statusLabel(po.status_po) }}
              </span>
            </Table.Td>
            <Table.Td class="w-[320px] text-center">
              <div class="inline-flex justify-center items-center gap-1">
                <ExtendableButton variant="soft-dark" rounded label="Detail" @click="goDetail(po.id_po)">
                  <Lucide icon="Eye" class="w-4 h-4" />
                </ExtendableButton>
                <ExtendableButton v-if="isEditableState(po.status_po?.key)" variant="soft-warning" rounded label="Edit"
                  @click="goEdit(po.id_po)">
                  <Lucide icon="Edit" class="w-4 h-4" />
                </ExtendableButton>
                <ExtendableButton v-if="isUnreleaseState(po.status_po?.key)" variant="soft-danger" rounded
                  label="Unrelease" @click="goEdit(po.id_po)">
                  <Lucide icon="Undo2" class="w-4 h-4" />
                </ExtendableButton>
                <ExtendableButton v-if="isDestroyableState(po.status_po?.key)" variant="soft-danger" rounded
                  label="Hapus" @click="confirmDelete(po.id_po, po.nomor_po)">
                  <Lucide icon="Trash2" class="w-4 h-4 shrink-0" />
                </ExtendableButton>
                <ExtendableButton v-if="po.status_po?.key === 'Approved'" variant="soft-success" rounded
                  label="Good Receipt" @click="goReceive(po.id_po)">
                  <Lucide icon="PackageCheck" class="w-4 h-4" />
                </ExtendableButton>
                <ExtendableButton v-if="po.status_po?.key === 'Approved'" variant="soft-secondary" rounded label="Cetak"
                  @click="previewPdf(po.id_po)">
                  <Lucide icon="Printer" class="w-4 h-4" />
                </ExtendableButton>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" :title="`Hapus PO ${deleteTarget?.label ?? ''}?`" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />

    </div>
  </div>
</template>
