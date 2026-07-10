<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { debounce } from 'lodash'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

/* Section: label & badge untuk disposisi_result (kolom lama, tetap dipakai
   sebagai indikator status tampilan karena belum ada endpoint ringkasan
   status approval per-row di list ini — lihat gap timeline di Detail page) */
const DISPOSISI_LABEL: Record<number, string> = {
  0: 'Draft / Belum Diforward',
  1: 'Menunggu Admin Finance',
  2: 'Diproses Admin Finance',
  3: 'Ditolak BM',
  4: 'Disetujui BM',
}

const DISPOSISI_BADGE: Record<number, string> = {
  0: 'bg-slate-100 text-slate-700',
  1: 'bg-amber-100 text-amber-700',
  2: 'bg-blue-100 text-blue-700',
  3: 'bg-rose-100 text-rose-700',
  4: 'bg-emerald-100 text-emerald-700',
}

const { error } = useNotification()
const api = createResourceApi('/customer-verifications')

const rows = ref<any[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const needUpdateCount = ref(0)
const loading = ref(false)

async function fetchData(page = 1) {
  loading.value = true
  try {
    const { data } = await api.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    rows.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? rows.value.length
    needUpdateCount.value = data.need_update_count ?? 0
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data verifikasi customer.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

function disposisiLabel(v: any) {
  return DISPOSISI_LABEL[Number(v) || 0] ?? '-'
}

function disposisiBadgeClass(v: any) {
  return DISPOSISI_BADGE[Number(v) || 0] ?? 'bg-slate-100 text-slate-700'
}

onMounted(() => fetchData())

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader title="Daftar Verifikasi Customer" description="Seluruh permintaan verifikasi data customer, dari link yang dibagikan sampai status approval terakhir.">
        <template #action>
          <RouterLink :to="{ name: 'link-customers' }"
            class="inline-flex items-center gap-2 rounded-lg bg-white/15 px-4 py-2 font-strong !text-white transition hover:bg-white/25">
            <Lucide icon="Link2" class="h-4 w-4" />
            Link Customers
            <span class="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ needUpdateCount }}</span>
          </RouterLink>
        </template>
      </PageHeader>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading" :empty="rows.length === 0"
        :colspan="6" :show-footer="true" :show-toolbar="true" :total="totalRecords" :current-page="currentPage"
        :total-pages="totalPages" search-placeholder="Cari nama, kode, atau email customer..."
        loading-text="Memuat data verifikasi..." empty-description="Belum ada data verifikasi customer."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Kode Link</Table.Th>
          <Table.Th>Kode Customer</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(row, idx) in rows" :key="row.id_verification" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <RouterLink v-if="row.token_verification" :to="{ name: 'verify-customer', params: { token: row.token_verification } }"
                target="_blank" class="font-strong !text-primary underline">
                LC{{ row.id_verification }}
              </RouterLink>
              <span v-else class="font-strong">LC{{ row.id_verification }}</span>
            </Table.Td>

            <Table.Td>
              <div class="text-slate-700">{{ row.customer?.kode_pelanggan || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ row.customer?.nama_perusahaan || '-' }}</div>
              <div class="text-slate-500">{{ row.customer?.email || '-' }}</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-3 py-1" :class="disposisiBadgeClass(row.disposisi_result)">
                {{ disposisiLabel(row.disposisi_result) }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="inline-flex items-center justify-center gap-1">
                <RouterLink :to="{ name: 'review-customer-detail', params: { id: row.id_verification } }"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                  title="Lihat Detail">
                  <Lucide icon="Eye" class="h-4 w-4" />
                </RouterLink>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
