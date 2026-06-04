<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import Lucide from '@/components/Base/Lucide';
import Table from '@/components/Base/Table';
import { FormSelect } from '@/components/Base/Form';

import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue';
import PageToolbar from '@/components/SystemDesign/Page/PageToolbar.vue';
import DataList from '@/components/SystemDesign/Data/DataList.vue';
import Button from '@/components/Base/Button/Button.vue';

type Vendor = {
  id: number;
  code: string;
  name: string;
  city: string;
  phone: string;
  email: string;
  status: 'active' | 'inactive';
};

const loading = ref(false);
const searchQuery = ref('');
const perPage = ref(5);
const currentPage = ref(1);

const selectedStatus = ref('');
const selectedCity = ref('');

const activeFilterCount = computed(() => {
  let count = 0;

  if (selectedStatus.value) count++;
  if (selectedCity.value) count++;

  return count;
});

const vendors = ref<Vendor[]>([
  {
    id: 1,
    code: 'VND-001',
    name: 'PT Batu Makmur Sentosa',
    city: 'Jakarta',
    phone: '021-7788-1200',
    email: 'admin@batumakmur.co.id',
    status: 'active',
  },
  {
    id: 2,
    code: 'VND-002',
    name: 'CV Quarry Nusantara',
    city: 'Bandung',
    phone: '022-8899-2211',
    email: 'sales@quarrynusantara.co.id',
    status: 'active',
  },
  {
    id: 3,
    code: 'VND-003',
    name: 'PT Andesit Jaya Abadi',
    city: 'Surabaya',
    phone: '031-6677-3300',
    email: 'contact@andesitjaya.co.id',
    status: 'inactive',
  },
  {
    id: 4,
    code: 'VND-004',
    name: 'PT Split Stone Indonesia',
    city: 'Jakarta',
    phone: '021-5544-9911',
    email: 'info@splitstone.co.id',
    status: 'active',
  },
  {
    id: 5,
    code: 'VND-005',
    name: 'CV Mineral Alam Raya',
    city: 'Bandung',
    phone: '022-3322-1100',
    email: 'hello@mineralalam.co.id',
    status: 'inactive',
  },
  {
    id: 6,
    code: 'VND-006',
    name: 'PT Agregat Prima',
    city: 'Surabaya',
    phone: '031-4422-8811',
    email: 'office@agregatprima.co.id',
    status: 'active',
  },
]);

const filteredVendors = computed(() => {
  return vendors.value.filter((vendor) => {
    const search = searchQuery.value.toLowerCase();

    const matchSearch =
      vendor.name.toLowerCase().includes(search) ||
      vendor.code.toLowerCase().includes(search) ||
      vendor.email.toLowerCase().includes(search);

    const matchStatus =
      !selectedStatus.value || vendor.status === selectedStatus.value;

    const matchCity =
      !selectedCity.value || vendor.city === selectedCity.value;

    return matchSearch && matchStatus && matchCity;
  });
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredVendors.value.length / perPage.value));
});

const paginatedVendors = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;

  return filteredVendors.value.slice(start, end);
});

function goToPage(page: number) {
  if (page < 1 || page > totalPages.value) return;

  currentPage.value = page;
}

watch([searchQuery, perPage, selectedStatus, selectedCity], () => {
  currentPage.value = 1;
});
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 mt-4 intro-y">
      <PageHeader title="Daftar Vendor" description="Kelola data vendor yang digunakan dalam proses procurement.">
        <template #action>
          <Button variant="primary" class="inline-flex items-center gap-2">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Vendor
          </Button>
        </template>
      </PageHeader>

      <PageToolbar v-model:search="searchQuery" v-model:per-page="perPage" :current-page="currentPage"
        :active-filter-count="activeFilterCount" :total-pages="totalPages" search-placeholder="Cari nama vendor..."
        @page-change="goToPage">
        <template #filters>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">
                Status
              </label>
              <FormSelect v-model="selectedStatus" class="!box">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
              </FormSelect>
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">
                Kota
              </label>
              <FormSelect v-model="selectedCity" class="!box">
                <option value="">Semua Kota</option>
                <option value="Jakarta">Jakarta</option>
                <option value="Bandung">Bandung</option>
                <option value="Surabaya">Surabaya</option>
              </FormSelect>
            </div>
          </div>
        </template>
      </PageToolbar>

      <DataList :loading="loading" :empty="paginatedVendors.length === 0" :colspan="7"
        loading-text="Memuat data vendor..." empty-description="Belum ada vendor yang sesuai dengan pencarian.">
        <template #head>
          <Table.Th class="w-16 text-center">No</Table.Th>
          <Table.Th>Nama Vendor</Table.Th>
          <Table.Th>Kota</Table.Th>
          <Table.Th>Kontak</Table.Th>
          <Table.Th>Email</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(vendor, index) in paginatedVendors" :key="vendor.id" class="transition hover:bg-slate-50">
            <Table.Td class="text-center font-medium text-slate-700">
              {{ (currentPage - 1) * perPage + index + 1 }}
            </Table.Td>

            <Table.Td>
              <div class="font-semibold text-slate-800">
                {{ vendor.name }}
              </div>
              <div class="text-xs text-slate-400">
                {{ vendor.code }}
              </div>
            </Table.Td>

            <Table.Td class="text-slate-600">
              {{ vendor.city }}
            </Table.Td>

            <Table.Td class="text-slate-600">
              {{ vendor.phone }}
            </Table.Td>

            <Table.Td class="text-slate-600">
              {{ vendor.email }}
            </Table.Td>

            <Table.Td class="text-center">
              <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="vendor.status === 'active'
                ? 'bg-emerald-100 text-emerald-700'
                : 'bg-slate-100 text-slate-600'">
                {{ vendor.status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
              </span>
            </Table.Td>

            <Table.Td class="text-center">
              <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition hover:bg-blue-100">
                <Lucide icon="Eye" class="h-4 w-4" />
              </button>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>
    </div>
  </div>
</template>