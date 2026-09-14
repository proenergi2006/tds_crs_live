<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { debounce } from 'lodash'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDateTime } from '@/utils/format'

type ArBucketKey =
  | 'outstanding_current'
  | 'overdue_1_30'
  | 'overdue_31_60'
  | 'overdue_61_90'
  | 'overdue_90_plus'

type ArAgingRow = {
  id_customer: number
  customer_code: string | null
  company_name: string | null
  outstanding_current: number
  overdue_1_30: number
  overdue_31_60: number
  overdue_61_90: number
  overdue_90_plus: number
  total_ar: number
  updated_by: { id: number; name: string } | null
  updated_at: string | null
}

const { success, error: notifyError } = useNotification()

const AR_BUCKETS: { key: ArBucketKey; label: string }[] = [
  { key: 'outstanding_current', label: 'Belum Jatuh Tempo' },
  { key: 'overdue_1_30', label: '1–30 Hari' },
  { key: 'overdue_31_60', label: '31–60 Hari' },
  { key: 'overdue_61_90', label: '61–90 Hari' },
  { key: 'overdue_90_plus', label: '> 90 Hari' },
]

const rows = ref<ArAgingRow[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)
const editModal = {
  open: ref(false),
  submitting: ref(false),
  error: ref<string | null>(null),
  row: ref<ArAgingRow | null>(null),
  form: ref<Record<ArBucketKey, number>>({
    outstanding_current: 0,
    overdue_1_30: 0,
    overdue_31_60: 0,
    overdue_61_90: 0,
    overdue_90_plus: 0,
  }),
}

const editTotal = computed<number>(() =>
  AR_BUCKETS.reduce((sum, b) => sum + Number(editModal.form.value[b.key] || 0), 0),
)

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))

onMounted(() => fetchData(1))

async function fetchData(page = 1): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get('/api/ar-agings', {
      params: {
        page,
        per_page: perPage.value,
        search: searchQuery.value || undefined,
      },
    })
    rows.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? rows.value.length
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data AR aging.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function openEditModal(row: ArAgingRow): void {
  editModal.row.value = row
  editModal.form.value = {
    outstanding_current: Number(row.outstanding_current ?? 0),
    overdue_1_30: Number(row.overdue_1_30 ?? 0),
    overdue_31_60: Number(row.overdue_31_60 ?? 0),
    overdue_61_90: Number(row.overdue_61_90 ?? 0),
    overdue_90_plus: Number(row.overdue_90_plus ?? 0),
  }
  editModal.error.value = null
  editModal.open.value = true
}

function closeEditModal(): void {
  editModal.open.value = false
  editModal.row.value = null
}

async function submitEditModal(): Promise<void> {
  if (!editModal.row.value) return
  editModal.submitting.value = true
  try {
    const payload: Record<ArBucketKey, number> = {
      outstanding_current: Number(editModal.form.value.outstanding_current) || 0,
      overdue_1_30: Number(editModal.form.value.overdue_1_30) || 0,
      overdue_31_60: Number(editModal.form.value.overdue_31_60) || 0,
      overdue_61_90: Number(editModal.form.value.overdue_61_90) || 0,
      overdue_90_plus: Number(editModal.form.value.overdue_90_plus) || 0,
    }
    const { data } = await axios.put(
      `/api/customers/${editModal.row.value.id_customer}/ar-aging`,
      payload,
    )
    success('Berhasil', 'AR aging tersimpan.')
    rows.value = rows.value.map(r => (r.id_customer === data.id_customer ? data : r))
    closeEditModal()
  } catch (e: any) {
    const status = e.response?.status
    if (status === 422) {
      editModal.error.value =
        Object.values(e.response?.data?.errors ?? {}).flat().join('\n') ||
        (e.response?.data?.message ?? 'Data yang dikirim tidak valid.')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal menyimpan AR aging.')
    }
  } finally {
    editModal.submitting.value = false
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="AR Aging Customer"
        description="Saldo piutang per customer. Dipakai sebagai exposure di gerbang kredit PO Customer." />

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="rows.length === 0" :colspan="11" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages"
        search-placeholder="Cari nama atau kode customer..." loading-text="Memuat data AR aging..."
        empty-description="Belum ada data customer." @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Kode Customer</Table.Th>
          <Table.Th>Nama Perusahaan</Table.Th>
          <Table.Th class="text-right">Belum Jatuh Tempo</Table.Th>
          <Table.Th class="text-right">1–30</Table.Th>
          <Table.Th class="text-right">31–60</Table.Th>
          <Table.Th class="text-right">61–90</Table.Th>
          <Table.Th class="text-right">&gt; 90</Table.Th>
          <Table.Th class="text-right">Total AR</Table.Th>
          <Table.Th>Terakhir Diubah</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in rows" :key="row.id_customer" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td class="whitespace-nowrap">
              {{ row.customer_code || '-' }}
            </Table.Td>

            <Table.Td class="font-strong">
              {{ row.company_name || '-' }}
            </Table.Td>

            <Table.Td class="text-right whitespace-nowrap">{{ formatCurrency(row.outstanding_current) }}</Table.Td>
            <Table.Td class="text-right whitespace-nowrap">{{ formatCurrency(row.overdue_1_30) }}</Table.Td>
            <Table.Td class="text-right whitespace-nowrap">{{ formatCurrency(row.overdue_31_60) }}</Table.Td>
            <Table.Td class="text-right whitespace-nowrap">{{ formatCurrency(row.overdue_61_90) }}</Table.Td>
            <Table.Td class="text-right whitespace-nowrap">{{ formatCurrency(row.overdue_90_plus) }}</Table.Td>

            <Table.Td class="font-strong text-right whitespace-nowrap">
              {{ formatCurrency(row.total_ar) }}
            </Table.Td>

            <Table.Td>
              <div class="font-body">{{ row.updated_by?.name ?? '-' }}</div>
              <div class="font-caption text-slate-500">{{ formatDateTime(row.updated_at) ?? '-' }}</div>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-1">
                <Button variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Edit"
                  @click="openEditModal(row)">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <FormModal :open="editModal.open.value"
        :title="`Edit AR Aging — ${editModal.row.value?.company_name ?? ''}`"
        :loading="editModal.submitting.value" :error="editModal.error.value" size="lg" submit-text="Simpan"
        @close="closeEditModal" @submit="submitEditModal">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <CurrencyField v-for="bucket in AR_BUCKETS" :key="bucket.key"
            v-model="editModal.form.value[bucket.key]" :label="bucket.label"
            :disabled="editModal.submitting.value" />
        </div>

        <div class="mt-4 flex items-center justify-end gap-3 border-t border-slate-200 pt-3">
          <span class="font-label">Total AR</span>
          <span class="font-strong">{{ formatCurrency(editTotal) }}</span>
        </div>
      </FormModal>
    </div>
  </div>
</template>
