<!-- pages/Penawaran/Verifikasi/Index.vue -->
<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { debounce } from 'lodash'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import {
  getVerifikasiConfig,
  getDisposisiLabel,
  disposisiBadgeClass,
  formatStatusLabel,
  statusBadgeClass,
  getDisposisiTanggal,
  type VerifikasiRole,
  type VerifikasiBrand,
} from './config'
import { formatDate } from '@/utils/format'

type PenawaranItem = any
// type PenawaranItem = {
//   id_penawaran: number
//   nomor_penawaran?: string
//   masa_berlaku?: string
//   sampai_dengan?: string
//   status?: string
//   disposisi_penawaran?: string | number
//   bm_tanggal?: string | null
//   om_tanggal?: string | null
//   customer?: { nama_perusahaan?: string }
//   cabang?: { nama_cabang?: string }
// }

const route = useRoute()
const { error } = useNotification()

const role = route.meta.role as VerifikasiRole
const brand = route.meta.brand as VerifikasiBrand
const config = getVerifikasiConfig(role, brand)

const api = createResourceApi(config.endpoint)

const penawarans = ref<PenawaranItem[]>([])
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const loading = ref(false)

async function fetchData(page = 1) {
  loading.value = true

  try {
    const { data } = await api.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
    })

    penawarans.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? penawarans.value.length
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data penawaran.')
  } finally {
    loading.value = false
  }
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

onMounted(() => fetchData())

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="config.title" :description="config.description" />

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="penawarans.length === 0" :colspan="8" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nomor atau customer..."
        loading-text="Memuat data penawaran..."
        empty-description="Belum ada penawaran yang sesuai dengan filter pencarian." @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12 text-center">No</Table.Th>
          <Table.Th>Nomor Penawaran</Table.Th>
          <Table.Th>Customer</Table.Th>
          <Table.Th>Cabang</Table.Th>
          <Table.Th class="text-center">Masa Berlaku</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Disposisi</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(pen, idx) in penawarans" :key="pen.id_penawaran" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ pen.nomor_penawaran || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="font-strong">{{ pen.customer?.nama_perusahaan || '-' }}</div>
              <div class="text-slate-500">{{ pen.nama || '-' }} - {{ pen.jabatan || '' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="text-slate-600">{{ pen.cabang?.nama_cabang || '-' }}</div>
            </Table.Td>

            <Table.Td>
              <div class="whitespace-nowrap text-slate-600">{{ formatDate(pen.masa_berlaku) }} s/d
                {{ formatDate(pen.sampai_dengan) }}</div>
            </Table.Td>

            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-3 py-1"
                :class="statusBadgeClass(pen.status)">
                {{ formatStatusLabel(pen.status) }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <div class="flex flex-col items-center gap-1">
                <span class="font-label inline-flex items-center rounded-full px-3 py-1"
                  :class="disposisiBadgeClass(pen.disposisi_penawaran)">
                  {{ getDisposisiLabel(pen.disposisi_penawaran) }}
                </span>

                <span v-if="getDisposisiTanggal(pen)" class="text-[11px] italic text-slate-400">
                  {{ getDisposisiTanggal(pen) }}
                </span>
              </div>
            </Table.Td>

            <Table.Td class="text-center">
              <RouterLink :to="{ name: config.detailRouteName, params: { id: pen.id_penawaran } }"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100 hover:text-blue-700"
                title="Lihat Detail">
                <Lucide icon="Eye" class="h-5 w-5" />
              </RouterLink>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>
