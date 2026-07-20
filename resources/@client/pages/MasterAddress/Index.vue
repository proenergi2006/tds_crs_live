<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { debounce } from 'lodash'

import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'

import { createResourceApi } from '@/utils/resourceApi.js'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

interface RegionRow {
  id: number | string
  name: string
  postal_code?: string | null
  [key: string]: any
}

type RegionLevel = 'province' | 'regency' | 'district' | 'village'

const { error } = useNotification()

const summaryStats = [
  { label: 'Provinsi', value: 38, icon: 'Flag' as const },
  { label: 'Kabupaten/Kota', value: 514, icon: 'Building2' as const },
  { label: 'Kecamatan', value: 7285, icon: 'MapPin' as const },
  { label: 'Desa/Kelurahan', value: 83762, icon: 'Home' as const },
]

/* State: drill-down selection */
const selectedProvince = ref<RegionRow | null>(null)
const selectedRegency = ref<RegionRow | null>(null)
const selectedDistrict = ref<RegionRow | null>(null)

/* State: data list (CSR per level) */
const allRows = ref<RegionRow[]>([])
const searchQuery = ref('')
const perPage = ref(50)
const currentPage = ref(1)
const loading = ref(false)

const currentLevel = computed<RegionLevel>(() => {
  if (selectedDistrict.value) return 'village'
  if (selectedRegency.value) return 'district'
  if (selectedProvince.value) return 'regency'
  return 'province'
})

const levelLabels: Record<RegionLevel, { title: string; empty: string; search: string }> = {
  province: {
    title: 'Provinsi',
    empty: 'Belum ada data provinsi untuk ditampilkan.',
    search: 'Cari provinsi...',
  },
  regency: {
    title: 'Kabupaten/Kota',
    empty: 'Provinsi ini belum memiliki data kabupaten/kota.',
    search: 'Cari kabupaten/kota...',
  },
  district: {
    title: 'Kecamatan',
    empty: 'Kabupaten/kota ini belum memiliki data kecamatan.',
    search: 'Cari kecamatan...',
  },
  village: {
    title: 'Desa/Kelurahan',
    empty: 'Kecamatan ini belum memiliki data desa/kelurahan.',
    search: 'Cari desa/kelurahan...',
  },
}

const breadcrumbs = computed(() => {
  const crumbs: { label: string; onClick?: () => void }[] = [
    { label: 'Provinsi', onClick: selectedProvince.value ? goToRoot : undefined },
  ]

  if (selectedProvince.value) {
    crumbs.push({
      label: selectedProvince.value.name,
      onClick: selectedRegency.value ? goToProvince : undefined,
    })
  }

  if (selectedRegency.value) {
    crumbs.push({
      label: selectedRegency.value.name,
      onClick: selectedDistrict.value ? goToRegency : undefined,
    })
  }

  if (selectedDistrict.value) {
    crumbs.push({ label: selectedDistrict.value.name })
  }

  return crumbs
})

const filteredRows = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return allRows.value

  return allRows.value.filter((row) =>
    String(row.name || '').toLowerCase().includes(query),
  )
})

const totalRecords = computed(() => filteredRows.value.length)

const totalPages = computed(() => Math.max(1, Math.ceil(totalRecords.value / perPage.value)))

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredRows.value.slice(start, start + perPage.value)
})

onMounted(() => {
  fetchCurrentLevel()
})

watch(searchQuery, debounce(resetToFirstPage, 300))
watch(perPage, resetToFirstPage)

async function fetchCurrentLevel() {
  loading.value = true

  try {
    const api = createResourceApi(buildEndpoint())
    const { data } = await api.getAll()

    allRows.value = Array.isArray(data?.data) ? data.data : []
    currentPage.value = 1
  } catch (e: any) {
    allRows.value = []
    error('Gagal memuat data', e.response?.data?.message ?? 'Terjadi kesalahan saat memuat data wilayah.')
  } finally {
    loading.value = false
  }
}

function buildEndpoint(): string {
  switch (currentLevel.value) {
    case 'regency':
      return `/provinces/${selectedProvince.value!.id}/regencies`
    case 'district':
      return `/regencies/${selectedRegency.value!.id}/districts`
    case 'village':
      return `/districts/${selectedDistrict.value!.id}/villages`
    default:
      return '/provinces'
  }
}

function resetToFirstPage() {
  currentPage.value = 1
}

function resetListState() {
  searchQuery.value = ''
  currentPage.value = 1
}

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

function selectProvince(row: RegionRow) {
  selectedProvince.value = row
  selectedRegency.value = null
  selectedDistrict.value = null
  resetListState()
  fetchCurrentLevel()
}

function selectRegency(row: RegionRow) {
  selectedRegency.value = row
  selectedDistrict.value = null
  resetListState()
  fetchCurrentLevel()
}

function selectDistrict(row: RegionRow) {
  selectedDistrict.value = row
  resetListState()
  fetchCurrentLevel()
}

function goToRoot() {
  selectedProvince.value = null
  selectedRegency.value = null
  selectedDistrict.value = null
  resetListState()
  fetchCurrentLevel()
}

function goToProvince() {
  selectedRegency.value = null
  selectedDistrict.value = null
  resetListState()
  fetchCurrentLevel()
}

function goToRegency() {
  selectedDistrict.value = null
  resetListState()
  fetchCurrentLevel()
}

function handleRowClick(row: RegionRow) {
  if (currentLevel.value === 'province') return selectProvince(row)
  if (currentLevel.value === 'regency') return selectRegency(row)
  if (currentLevel.value === 'district') return selectDistrict(row)
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <!-- Page Header -->
      <PageHeader title="Master Address"
        description="Jelajahi data referensi provinsi, kabupaten/kota, kecamatan, desa/kelurahan." />

      <!-- Summary Stats -->
      <CardSection title="Ringkasan Data"
        description="Jumlah baris per level, per data hasil migrasi (bukan hitungan real-time)." icon="BarChart3">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <div v-for="stat in summaryStats" :key="stat.label"
            class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
              <Lucide :icon="stat.icon" class="h-5 w-5" />
            </div>
            <div>
              <div class="font-num text-lg font-semibold text-slate-800">
                {{ stat.value.toLocaleString('id-ID') }}
              </div>
              <div class="font-body text-slate-500">
                {{ stat.label }}
              </div>
            </div>
          </div>
        </div>
      </CardSection>

      <!-- Drill-down Browser -->
      <CardSection title="Jelajahi Wilayah" description="Klik baris untuk melihat data di bawahnya." icon="Globe">
        <div class="mb-4 flex flex-wrap items-center gap-1 font-body">
          <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
            <button v-if="crumb.onClick" type="button" class="text-primary hover:underline" @click="crumb.onClick">
              {{ crumb.label }}
            </button>
            <span v-else class="font-semibold text-slate-700">{{ crumb.label }}</span>

            <Lucide v-if="idx < breadcrumbs.length - 1" icon="ChevronRight" class="h-3.5 w-3.5 text-slate-400" />
          </template>
        </div>

        <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
          :empty="paginatedRows.length === 0" :colspan="3" :show-footer="true" :show-toolbar="true"
          :total="totalRecords" :current-page="currentPage" :total-pages="totalPages"
          :search-placeholder="levelLabels[currentLevel].search" loading-text="Memuat data wilayah..."
          :empty-description="levelLabels[currentLevel].empty" @page-change="goToPage">
          <template #head>
            <Table.Th class="w-12">No</Table.Th>
            <Table.Th>{{ levelLabels[currentLevel].title }}</Table.Th>
            <Table.Th v-if="currentLevel === 'village'">Kode Pos</Table.Th>
            <Table.Th v-else class="w-12" />
          </template>

          <template #body>
            <Table.Tr v-for="(item, idx) in paginatedRows" :key="item.id"
              :class="currentLevel !== 'village' ? 'cursor-pointer transition hover:bg-slate-50' : ''"
              @click="handleRowClick(item)">
              <Table.Td class="font-num text-center">
                {{ (currentPage - 1) * perPage + idx + 1 }}.
              </Table.Td>
              <Table.Td>
                {{ item.name }}
              </Table.Td>
              <Table.Td v-if="currentLevel === 'village'" class="text-slate-600">
                {{ item.postal_code || '-' }}
              </Table.Td>
              <Table.Td v-else class="text-center">
                <Lucide icon="ChevronRight" class="inline h-4 w-4 text-slate-400" />
              </Table.Td>
            </Table.Tr>
          </template>
        </DataList>
      </CardSection>
    </div>
  </div>
</template>
