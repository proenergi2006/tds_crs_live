<script setup lang="ts">
import { computed, ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Table from '@/components/Base/Table'
import Lucide from '@/components/Base/Lucide'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi.js'
import { copyToClipboard } from '@/utils/clipboard'
import ExtendableButton from '@/components/SystemDesign/Button/ExtendableButton.vue'

type VerificationTab = 'all' | 'verified' | 'unverified'

const customerApi = createResourceApi('/customers')
const { success, error } = useNotification()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

/* Brand switching — TDS vs Proenergi (pola sama seperti Penawaran/Form.vue) */
type Brand = 'tds' | 'proenergi'
const brand: Brand = (route.meta.brand as Brand) === 'proenergi' ? 'proenergi' : 'tds'
const isProenergi = brand === 'proenergi'

/* State: data & pagination */
const customers = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)
const totalRecords = ref(0)
const totalPages = ref(1)

/* State: delete */
const deleteModal = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref<number | null>(null)

/* State: verification tab */
const activeTab = ref<VerificationTab>('all')
const tabOptions: { value: VerificationTab; label: string }[] = [
  { value: 'all', label: 'All Data' },
  { value: 'verified', label: 'Verified' },
  { value: 'unverified', label: 'Unverified' },
]
const tabCounts = ref<Partial<Record<VerificationTab, number>>>({})

/* State: link generate/regenerate, and result dialog */
const linkBusyId = ref<number | null>(null)
const linkResultOpen = ref(false)
const linkResult = reactive({ token: '', link: '', alreadyExists: false })

/* Computed: permission (customer.manage + ownership, lihat Task 1(a)).
   Proenergi di luar scope restrukturisasi permission ini (lihat CLAUDE.md) — tombol
   tetap tampil apa adanya untuk brand tersebut, tidak digate ulang di sini. */
const canManageCustomer = computed(() => isProenergi ? true : auth.can('customer.manage'))
const canViewAnyCustomer = computed(() => isProenergi ? true : auth.can('customer.viewAny'))

function canManageRow(item: any) {
  if (isProenergi) return true
  return (
    canManageCustomer.value &&
    (canViewAnyCustomer.value || Number(item.id_user) === Number(auth.user?.id))
  )
}

/* Computed: summary cards (Proenergi only) */
const totalProspect = computed(() => customers.value.filter(c => c.status_customer === 1).length)
const totalTetap = computed(() => customers.value.filter(c => c.status_customer === 2).length)
const totalPenawaran = computed(() =>
  customers.value.reduce((sum, c) => sum + Number(c.jumlah_penawaran ?? 0), 0)
)

const linkResultTitle = computed(() => (linkResult.alreadyExists ? 'Token Sudah Ada' : 'Token Dibuat'))
const linkResultDescription = computed(() =>
  linkResult.alreadyExists
    ? 'Link verifikasi untuk customer ini masih aktif dan belum kedaluwarsa.'
    : 'Link verifikasi baru berhasil dibuat untuk customer ini.'
)

onMounted(() => fetchData())

watch(searchQuery, debounce(() => fetchData(1), 300))
watch(perPage, () => fetchData(1))
watch(activeTab, () => fetchData(1))

async function fetchData(page = currentPage.value) {
  loading.value = true
  try {
    const { data } = await customerApi.getAll({
      page,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      tab: activeTab.value,
    })
    customers.value = data.data ?? []
    currentPage.value = data.current_page ?? 1
    totalPages.value = data.last_page ?? 1
    totalRecords.value = data.total ?? 0
    tabCounts.value = data.tab_counts ?? tabCounts.value
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal memuat data customer')
  } finally {
    loading.value = false
  }
}

function selectTab(tab: VerificationTab) {
  if (activeTab.value === tab) return
  activeTab.value = tab
}

function tabLabel(opt: { value: VerificationTab; label: string }) {
  const count = tabCounts.value[opt.value]
  return typeof count === 'number' ? `${opt.label} (${count})` : opt.label
}

function goToPage(page: number) {
  fetchData(page)
}

function openCreate() {
  router.push({ name: isProenergi ? 'customers-create-proenergi' : 'customers-create' })
}

function openEdit(id: number) {
  router.push({ name: isProenergi ? 'customers-edit-proenergi' : 'customers-edit', params: { id } })
}

function openCreatePenawaran(id: number) {
  const routeName = isProenergi ? 'penawarans-create-proenergi' : 'penawarans-create'
  router.push({ name: routeName, query: { customer_id: id } })
}

function openReview(idCustomer: number) {
  router.push({ name: 'customer-detail', params: { id: idCustomer } })
}

async function generateLink(item: any) {
  try {
    linkBusyId.value = item.id_customer
    const { data } = await axios.post(`/api/link-customers/${item.id_customer}/generate`)

    linkResult.token = data.verification?.token_verification ?? '-'
    linkResult.link = data.link
    linkResult.alreadyExists = !!data.already_exists
    linkResultOpen.value = true

    fetchData(currentPage.value)
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal membuat link verifikasi.')
  } finally {
    linkBusyId.value = null
  }
}

async function copyLinkResult() {
  const copied = await copyToClipboard(linkResult.link)
  if (copied) {
    linkResultOpen.value = false
    success('Link disalin', 'Link verifikasi berhasil disalin ke clipboard.')
  } else {
    error('Gagal menyalin', 'Link tidak berhasil disalin otomatis. Silakan salin manual dari kotak token di atas.')
  }
}

function closeLinkResult() {
  linkResultOpen.value = false
}

async function openCustomerLink(item: any) {
  try {
    linkBusyId.value = item.id_customer
    const { data } = await axios.post(`/api/link-customers/${item.id_customer}/generate`)
    window.open(data.link, '_blank')
  } catch (e: any) {
    error('Gagal', e.response?.data?.message ?? 'Gagal membuka link verifikasi.')
  } finally {
    linkBusyId.value = null
  }
}

function confirmDelete(id: number) {
  deleteTarget.value = id
  deleteModal.value = true
}

async function submitDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    await customerApi.destroy(deleteTarget.value)
    deleteModal.value = false
    success('Berhasil', 'Customer berhasil dihapus.')
    fetchData(currentPage.value)
  } catch (e: any) {
    error('Gagal menghapus', e.response?.data?.message ?? 'Terjadi kesalahan saat menghapus data.')
  } finally {
    deleteLoading.value = false
    deleteTarget.value = null
  }
}

function getStatusLabel(status?: number) {
  if (status === 1) return 'Prospect'
  if (status === 2) return 'Tetap'
  return '-'
}

function getStatusClass(status?: number) {
  if (status === 1) return 'bg-amber-100 text-amber-700'
  if (status === 2) return 'bg-emerald-100 text-emerald-700'
  return 'bg-slate-100 text-slate-500'
}

function getVerificationBadgeLabel(item: any) {
  switch (item.verification_badge) {
    case 'verified': return 'Verified'
    case 'belum_ada_link': return 'Belum Ada Link'
    case 'menunggu_customer': return 'Menunggu Customer'
    case 'link_kedaluwarsa': return 'Link Kedaluwarsa'
    case 'perlu_direview': return 'Perlu Direview'
    case 'proses_internal': return `Proses Internal (${item.latest_verification?.stage_label ?? '-'})`
    case 'ditolak': return 'Ditolak'
    default: return '-'
  }
}

function getVerificationBadgeClass(badge?: string) {
  switch (badge) {
    case 'verified': return 'bg-emerald-100 text-emerald-700'
    case 'belum_ada_link': return 'bg-slate-100 text-slate-500'
    case 'menunggu_customer': return 'bg-amber-100 text-amber-700'
    case 'link_kedaluwarsa': return 'bg-red-100 text-red-700'
    case 'perlu_direview': return 'bg-sky-100 text-sky-700'
    case 'proses_internal': return 'bg-indigo-100 text-indigo-700'
    case 'ditolak': return 'bg-red-100 text-red-700'
    default: return 'bg-slate-100 text-slate-500'
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <PageHeader :title="isProenergi ? 'Master Customers Proenergi' : 'Master Customers'"
        :description="isProenergi ? 'Kelola data customer Proenergi yang kamu tangani' : 'Kelola data customer yang kamu tangani'">
        <template #action>
          <Button v-if="canManageCustomer" variant="white" class="inline-flex items-center gap-2" @click="openCreate">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Customer
          </Button>
        </template>
      </PageHeader>

      <!-- Summary Cards (Proenergi only) -->
      <div v-if="isProenergi" class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="box p-4">
          <div class="font-label">Total Customer</div>
          <div class="font-num-display mt-1">{{ totalRecords }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Prospect</div>
          <div class="font-num-display mt-1 !text-amber-600">{{ totalProspect }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Customer Tetap</div>
          <div class="font-num-display mt-1 !text-emerald-600">{{ totalTetap }}</div>
        </div>
        <div class="box p-4">
          <div class="font-label">Total Penawaran</div>
          <div class="font-num-display mt-1 !text-primary">{{ totalPenawaran }}</div>
        </div>
      </div>

      <!-- Verification tab selector -->
      <div class="flex flex-wrap items-center gap-2">
        <Button v-for="opt in tabOptions" :key="opt.value"
          :variant="activeTab === opt.value ? 'primary' : 'outline-primary'" size="sm" @click="selectTab(opt.value)">
          {{ tabLabel(opt) }}
        </Button>
      </div>

      <DataList v-model:search="searchQuery" v-model:per-page="perPage" :loading="loading"
        :empty="customers.length === 0" :colspan="8" :show-footer="true" :show-toolbar="true" :total="totalRecords"
        :current-page="currentPage" :total-pages="totalPages" search-placeholder="Cari nama perusahaan atau email..."
        loading-text="Memuat data customer..." empty-description="Belum ada customer yang ditambahkan."
        @page-change="goToPage">
        <template #head>
          <Table.Th class="w-12">No</Table.Th>
          <Table.Th>Nama Customer</Table.Th>
          <Table.Th>Alamat</Table.Th>
          <Table.Th>Kontak</Table.Th>
          <Table.Th class="text-center">Status</Table.Th>
          <Table.Th class="text-center">LCR</Table.Th>
          <Table.Th class="text-center">Quotations</Table.Th>
          <Table.Th class="text-center w-[160px]">Aksi</Table.Th>
        </template>

        <template #body>
          <Table.Tr v-for="(item, idx) in customers" :key="item.id_customer" class="transition hover:bg-slate-50">
            <Table.Td class="font-num text-center">
              {{ (currentPage - 1) * perPage + idx + 1 }}.
            </Table.Td>
            <Table.Td>
              <div class="font-strong">{{ item.nama_perusahaan || '-' }}</div>
              <div class="font-caption mt-0.5">{{ isProenergi ? (item.user?.name || '-') : (item.email || '-') }}</div>
            </Table.Td>
            <Table.Td>
              <div class="font-body">{{ item.alamat_perusahaan || '-' }}</div>
              <div class="font-caption mt-0.5">
                {{ item.cabang?.nama_cabang || '-' }}
              </div>
            </Table.Td>
            <Table.Td>
              <div class="font-body">{{ item.telepon || '-' }}</div>
              <div class="font-caption mt-0.5">Fax: {{ item.fax || '-' }}</div>
            </Table.Td>
            <Table.Td class="text-center">
              <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                :class="getStatusClass(item.status_customer)">
                {{ getStatusLabel(item.status_customer) }}
              </span>
            </Table.Td>
            <Table.Td class="text-center">
              <Lucide v-if="item.has_lcr" icon="CheckCircle" class="mx-auto h-5 w-5 text-emerald-600" />
              <Lucide v-else icon="XCircle" class="mx-auto h-5 w-5 text-slate-300" />
            </Table.Td>
            <Table.Td class="font-num text-center">
              {{ item.jumlah_penawaran ?? 0 }}
            </Table.Td>
            <Table.Td class="text-center">
              <div v-if="activeTab === 'all'" class="inline-flex items-center justify-center gap-2">
                <Button variant="soft-primary" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Buat Quotation"
                  @click="openCreatePenawaran(item.id_customer)">
                  <Lucide icon="FilePlus" class="h-4 w-4" />
                </Button>
                <Button v-if="canManageRow(item)" variant="soft-pending" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Edit" @click="openEdit(item.id_customer)">
                  <Lucide icon="Edit" class="h-4 w-4" />
                </Button>
                <Button v-if="canManageRow(item)" variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none"
                  title="Hapus" @click="confirmDelete(item.id_customer)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>

              <div v-else-if="activeTab === 'unverified'" class="inline-flex items-center justify-center gap-2">
                <Button v-if="item.verification_badge === 'belum_ada_link'" variant="soft-secondary" rounded
                  class="!h-8 !w-8 !p-0 !shadow-none" title="Generate Link" :disabled="linkBusyId === item.id_customer"
                  @click="generateLink(item)">
                  <Lucide icon="Link" class="h-4 w-4" />
                </Button>
                <Button
                  v-else-if="item.verification_badge === 'link_kedaluwarsa' || item.verification_badge === 'ditolak'"
                  variant="soft-danger" rounded class="!h-8 !w-8 !p-0 !shadow-none" title="Regenerate Link"
                  :disabled="linkBusyId === item.id_customer" @click="generateLink(item)">
                  <Lucide icon="RefreshCw" class="h-4 w-4" />
                </Button>
                <Button v-else-if="item.verification_badge === 'menunggu_customer'" variant="soft-warning" rounded
                  class="!h-8 !w-8 !p-0 !shadow-none" title="Buka Link" :disabled="linkBusyId === item.id_customer"
                  @click="openCustomerLink(item)">
                  <Lucide icon="ExternalLink" class="h-4 w-4" />
                </Button>
                <ExtendableButton v-else-if="item.verification_badge === 'perlu_direview'" variant="soft-info" rounded
                  label="Verifikasi" @click="openReview(item.id_customer)">
                  <Lucide icon="ClipboardCheck" class="h-4 w-4" />
                </ExtendableButton>
              </div>
            </Table.Td>
          </Table.Tr>
        </template>
      </DataList>

      <DeleteRecordDialog :open="deleteModal" title="Hapus Customer" :loading="deleteLoading"
        @close="deleteModal = false" @confirm="submitDelete" />

      <ConfirmDialog :open="linkResultOpen" :title="linkResultTitle" :description="linkResultDescription"
        confirm-text="Salin Link" cancel-text="Tutup" icon="Link" icon-class="bg-primary/10 text-primary"
        variant="primary" @close="closeLinkResult" @confirm="copyLinkResult">
        <div class="font-caption mb-1">Token Verifikasi</div>
        <div class="font-mono break-all rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
          {{ linkResult.token }}
        </div>
      </ConfirmDialog>
    </div>
  </div>
</template>
