<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi'
import { formatDate, formatDateTime, formatPercentRate, toDateKey, todayDateKey } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'
import { FORBIDDEN_ACTION_MESSAGE, actionErrorMessage, redirectToErrorPage } from '@/utils/http-error-route'
import { canManageQuotationBrand } from '@/utils/quotation-brand'
import type { ApiResponse, QuotationTransport } from '@/types'
import QuotationDetailsEditModal from './components/QuotationDetailsEditModal.vue'
import QuotationPdfDialog from './components/QuotationPdfDialog.vue'
import QuotationTransportSummary from './components/QuotationTransportSummary.vue'
import { QUOTATION_STATUS } from './status'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const auth = useAuthStore()
const quotationApi = createResourceApi('/quotations')

const QUOTATION_API_BASE = '/api/quotations'

const quotation = ref<any>({})
const loading = ref(true)
const ajukanLoading = ref(false)
const ajukanDialogOpen = ref(false)
const detailsEditOpen = ref(false)
const previewLangDialogOpen = ref(false)
const previewLoading = ref(false)
const reopenDialog = {
  isOpen: ref(false),
  catatan: ref(''),
  isLoading: ref(false),
}

const isProenergi = computed<boolean>(() => quotation.value.brand === 'proenergi')

const id = computed(() => Number(route.params.id))

const canManage = computed<boolean>(() => quotation.value.can_manage === true)

const canEditDetails = computed<boolean>(() => quotation.value.can_edit_details === true)

const canReopen = computed<boolean>(() => quotation.value.can_reopen === true)

const isExpired = computed<boolean>(() => {
  const to = toDateKey(quotation.value.sampai_dengan)
  return !!to && to < todayDateKey()
})

const canClone = computed<boolean>(() =>
  quotation.value.brand
    ? canManageQuotationBrand(auth.can, quotation.value.brand) &&
      (quotation.value.status === QUOTATION_STATUS.Rejected ||
        (quotation.value.status === QUOTATION_STATUS.Approved && isExpired.value))
    : false,
)

const items = computed<any[]>(() => quotation.value.items || [])

const dash = (v: any) => (v === null || v === undefined || v === '' ? '-' : v)

const paymentFields = computed(() => {
  const p = quotation.value
  return [
    { label: 'Tipe Pembayaran', value: p.tipe_pembayaran },
    ...(isProenergi.value ? [{ label: 'Acuan Pembayaran', value: p.acuan_pembayaran }] : []),
    { label: 'Metode Pemesanan', value: p.order_method },
    {
      label: 'Down Payment',
      value: p.dp_persen ? `${p.dp_persen}%${p.dp_keterangan ? ' — ' + p.dp_keterangan : ''}` : null,
    },
    {
      label: 'Repayment',
      value: p.repayment_persen ? `${p.repayment_persen}% / ${p.repayment_hari || 0} hari` : null,
    },
    { label: 'Toleransi Penyusutan', value: `${p.toleransi_penyusutan ?? 0}%` },
    { label: 'Abrasi', value: p.abrasi },
    { label: 'Refund', value: formatCurrency(p.refund), tone: 'red' },
    { label: 'Other Cost', value: formatCurrency(p.other_cost) },
  ]
})

const totalQuantity = computed<number>(() =>
  items.value.reduce((sum: number, it: any) => sum + (Number(it.quantity) || 0), 0)
)

const transports = computed<QuotationTransport[]>(() => quotation.value.transports ?? [])

const approvalAttempts = computed<{ label: string | null; steps: StepItem[] }[]>(() =>
  (quotation.value.approval_attempts ?? []).map((attempt: any) => ({
    label: attempt.label,
    steps: (attempt.steps ?? []).map((s: any) => ({
      title: s.title,
      description: s.description,
      status: s.status,
      statusText: s.status_text,
      timestamp: s.timestamp ? formatDateTime(s.timestamp) : undefined,
    })),
  }))
)

watch(
  () => route.fullPath,
  () => {
    if (route.params.id === undefined) return
    if (Number.isInteger(id.value) && id.value > 0) fetchQuotation()
    else redirectToErrorPage(router, 404)
  },
  { immediate: true },
)

async function fetchQuotation() {
  loading.value = true
  try {
    const { data }: { data: ApiResponse<any> } = await quotationApi.getById(id.value)
    quotation.value = data.data
  } catch (e: any) {
    if (redirectToErrorPage(router, e.response?.status)) return
    error('Gagal', e.response?.data?.message || 'Gagal memuat detail penawaran')
  } finally {
    loading.value = false
  }
}

async function submitQuotation() {
  ajukanLoading.value = true
  try {
    const { data } = await axios.patch<ApiResponse<null>>(`${QUOTATION_API_BASE}/${id.value}/submit`)
    ajukanDialogOpen.value = false
    success('Berhasil Diajukan', data.message || 'Penawaran berhasil diajukan untuk verifikasi.')
    await fetchQuotation()
  } catch (e: any) {
    error('Gagal', actionErrorMessage(e, 'Gagal mengajukan penawaran'))
  } finally {
    ajukanLoading.value = false
  }
}

async function submitReopen() {
  const catatan = reopenDialog.catatan.value.trim()
  if (!catatan) {
    error('Gagal', 'Alasan wajib diisi.')
    return
  }

  reopenDialog.isLoading.value = true
  try {
    const { data } = await axios.patch<ApiResponse<unknown>>(`${QUOTATION_API_BASE}/${id.value}/reopen`, { catatan })
    closeReopenDialog()
    success('Berhasil', data.message || 'Penawaran dikembalikan ke Draft.')
    await fetchQuotation()
  } catch (e: any) {
    closeReopenDialog()
    error(
      'Gagal',
      e.response?.data?.errors?.catatan?.[0] ?? actionErrorMessage(e, 'Gagal mengembalikan penawaran ke Draft.'),
    )
    if ([403, 409].includes(e.response?.status)) await fetchQuotation()
  } finally {
    reopenDialog.isLoading.value = false
  }
}

function closeReopenDialog() {
  reopenDialog.isOpen.value = false
  reopenDialog.catatan.value = ''
}

async function preview(payload: { lang: 'id' | 'en' }) {
  previewLoading.value = true
  const previewTab = openPdfLoadingTab()
  try {
    const response = await axios.get(`${QUOTATION_API_BASE}/${id.value}/preview`, {
      params: { lang: payload.lang },
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (previewTab) {
      previewTab.location.href = url
    } else {
      window.open(url, '_blank')
    }
    setTimeout(() => URL.revokeObjectURL(url), 60000)
    previewLangDialogOpen.value = false
  } catch (e: any) {
    previewTab?.close()
    error('Gagal', e.response?.status === 403 ? FORBIDDEN_ACTION_MESSAGE : 'Gagal membuka preview PDF')
  } finally {
    previewLoading.value = false
  }
}

async function onDetailsSaved() {
  detailsEditOpen.value = false
  await fetchQuotation()
}

function goBack() {
  router.push({ name: 'quotations.index' })
}

function openEdit() {
  router.push({ name: 'quotations.edit', params: { id: id.value } })
}

function openClone() {
  router.push({ name: 'quotations.create', query: { clone_from: String(id.value) } })
}

function formatCurrency(v: number | string = 0) {
  const n = Number(v) || 0
  return `Rp. ${n.toLocaleString('id-ID')}`
}

function formatNumber(v: number | string = 0) {
  const n = Number(v) || 0
  return n.toLocaleString('id-ID')
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-x">

      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <h2 class="text-screen-title">Detail Penawaran</h2>
          <p class="mt-1 text-body-lg">Informasi lengkap penawaran dan status verifikasi.</p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="quotation.status === QUOTATION_STATUS.Draft && canManage" variant="soft-warning" @click="openEdit">
            <Lucide icon="Edit" class="mr-2 w-4 h-4" />
            Edit
          </Button>
          <Button v-if="canEditDetails" variant="soft-dark" @click="detailsEditOpen = true">
            <Lucide icon="Edit3" class="mr-2 w-4 h-4" />
            Edit Info
          </Button>
          <Button v-if="canClone" variant="soft-dark" @click="openClone">
            <Lucide icon="Copy" class="mr-2 w-4 h-4" />
            Buat Penawaran Baru dari Ini
          </Button>
          <Button v-if="canReopen" variant="outline-danger" @click="reopenDialog.isOpen.value = true">
            <Lucide icon="Undo2" class="mr-2 w-4 h-4" />
            Kembalikan ke Draft
          </Button>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
            Kembali
          </Button>
        </div>
      </div>

      <div class="gap-6 grid grid-cols-1 xl:grid-cols-3">

        <div class="space-y-6 xl:col-span-2">

          <CardSection title="Informasi Penawaran" description="Identitas dokumen dan kontak tujuan" icon="FileText">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12 md:col-span-5">
                <div class="space-y-3">
                  <div>
                    <div class="text-form-label">Nomor Penawaran</div>
                    <div
                      class="inline-block bg-danger/5 mt-1 px-2 py-1 border border-danger/20 rounded text-body-strong text-danger text-xs whitespace-pre-line">
                      {{ dash(quotation.nomor_penawaran) }}
                    </div>
                  </div>

                  <div>
                    <div class="text-form-label">Masa Berlaku</div>
                    <div class="mt-1 text-body whitespace-pre-line">
                      {{
                        quotation.masa_berlaku
                          ? `${formatDate(quotation.masa_berlaku)} – ${formatDate(quotation.sampai_dengan)}`
                          : '-'
                      }}
                    </div>
                  </div>
                  <div>
                    <div class="text-form-label">Customer</div>
                    <div class="mt-1 text-body whitespace-pre-line">
                      {{ dash(quotation.customer?.company_name) }}
                    </div>
                  </div>

                  <div>
                    <div class="text-form-label">Cabang</div>
                    <div class="mt-1 text-body whitespace-pre-line">
                      {{ dash(quotation.branch?.nama_cabang) }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-span-12 md:col-span-7">
                <div class="mx-2 mb-1 text-form-label">Kontak Tujuan</div>
                <div>
                  <div class="px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="gap-4 grid grid-cols-12">
                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Kepada (Perusahaan / Dept.)</div>
                        <div class="mt-1 text-body">
                          {{ dash(quotation.customer?.company_name) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Nama (UP.)</div>
                        <div class="mt-1 text-body">
                          {{ dash(quotation.customer_contact?.full_name) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Jabatan</div>
                        <div class="mt-1 text-body">
                          {{ dash(quotation.customer_contact?.position) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Telepon</div>
                        <div class="mt-1 text-body">
                          {{ dash(quotation.customer_contact?.mobile) }}
                        </div>
                      </div>

                      <div class="col-span-12">
                        <div class="text-form-label">Alamat</div>
                        <div class="mt-1 text-body">
                          {{ dash(quotation.customer?.head_office_address?.address_line) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </CardSection>

          <CardSection title="Detail Pengiriman & Daftar Produk"
            description="Instrumen pengiriman, tujuan kirim dan daftar produk penawaran" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12 md:col-span-6">
                <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
                  <div>
                    <div class="text-form-label">Tipe Pengiriman</div>
                    <div class="mt-1 text-body whitespace-pre-line">{{ dash(quotation.type_pengiriman) }}</div>
                  </div>
                  <div>
                    <div class="text-form-label">Metode</div>
                    <div class="mt-1 text-body whitespace-pre-line">{{ dash(quotation.metode) }}</div>
                  </div>
                </div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
                  <div>
                    <div class="text-form-label">Lokasi Pengiriman</div>
                    <div class="mt-1 text-body whitespace-pre-line">{{ dash(quotation.lokasi_pengiriman) }}</div>
                  </div>
                  <div>
                    <div class="text-form-label">Titik Serah Terima & T&C Bongkar</div>
                    <div class="mt-1 text-body whitespace-pre-line">{{ dash(quotation.keterangan) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <QuotationTransportSummary v-if="quotation.transport_basis !== 'franco'" :basis="quotation.transport_basis ?? null" :mode="quotation.transport_mode ?? null"
              :transports="transports" />

            <Table bordered sm class="mt-4 text-body">
              <Table.Thead class="bg-slate-50">
                <Table.Th>Produk</Table.Th>
                <Table.Th class="w-40">Source</Table.Th>
                <Table.Th class="w-28 text-right">Quantity</Table.Th>
                <Table.Th class="w-36 text-right">Material/m³</Table.Th>
                <Table.Th class="w-36 text-right">Transport/m³</Table.Th>
                <Table.Th class="w-36 text-right">Final/m³</Table.Th>
                <Table.Th class="w-40 text-right">Subtotal</Table.Th>
              </Table.Thead>
              <Table.Tbody class="bg-white">
                <Table.Tr v-for="item in items" :key="item.id">
                  <Table.Td>
                    <div class="text-body-strong">{{ item.product?.nama_produk || '-' }}</div>
                    <div class="mt-0.5 text-caption">
                      {{ item.product?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.product?.ukuran?.nama_ukuran || '-' }} {{ item.product?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="text-body">{{ item.source_branch?.nama_cabang ?? '—' }}</Table.Td>
                  <Table.Td class="num-sm text-lg text-right">{{ formatNumber(item.quantity) }}</Table.Td>
                  <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.unit_price) }}</Table.Td>
                  <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.transport_price) }}</Table.Td>
                  <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.final_price) }}</Table.Td>
                  <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.final_total) }}</Table.Td>
                </Table.Tr>
              </Table.Tbody>

              <Table.Tbody v-if="items.length > 1" class="bg-slate-50 border-slate-200 border-t">
                <Table.Tr>
                  <Table.Td colspan="2" class="py-2.5 pr-6 text-section-title text-right">Total</Table.Td>
                  <Table.Td class="py-2.5 num-md text-xl text-right">{{ formatNumber(totalQuantity) }}</Table.Td>
                  <Table.Td colspan="3"></Table.Td>
                  <Table.Td class="py-2.5 num-md text-xl text-right">{{ formatCurrency(quotation.dpp) }}</Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </CardSection>

          <div class="gap-6 grid xl:grid-cols-2">
            <div>
              <CardSection title="Pembayaran & Lainnya" description="Ketentuan pembayaran dan info lainnya"
                icon="Wallet" icon-class="bg-amber-100 text-amber-600" class="h-full">
                <div class="gap-4 grid grid-cols-12">
                  <div v-for="field in paymentFields" :key="field.label" class="col-span-12 md:col-span-6">
                    <div class="text-form-label">{{ field.label }}</div>
                    <div class="mt-1 text-body whitespace-pre-line"
                      :class="field.tone === 'red' ? 'text-danger' : ''">
                      {{ dash(field.value) }}
                    </div>
                  </div>
                </div>
              </CardSection>
            </div>

            <div>
              <CardSection title="Ringkasan Harga" description="DPP, PPN, dan total penawaran"
                icon="Calculator" icon-class="bg-emerald-100 text-emerald-600" class="h-full">
                <dl class="flex flex-col gap-4 px-4">
                  <div class="bg-slate-100 p-4 rounded-lg grow">
                    <div class="flex justify-between items-start">
                      <dt class="text-form-label">DPP</dt>
                      <dd class="mt-1 num-md !text-slate-800 text-xl">{{ formatCurrency(quotation.dpp) }}</dd>
                    </div>
                  </div>
                  <div class="bg-slate-100 p-4 rounded-lg grow">
                    <div class="flex justify-between items-start">
                      <dt class="text-form-label">PPN ({{ formatPercentRate(quotation.ppn_rate) }})</dt>
                      <dd class="mt-1 num-md !text-slate-800 text-xl">{{ formatCurrency(quotation.ppn) }}</dd>
                    </div>
                  </div>
                  <div class="bg-slate-100 p-4 rounded-lg grow">
                    <div class="flex justify-between items-start">
                      <dt class="text-form-label">TOTAL</dt>
                      <dd class="mt-1 num-md !text-emerald-700 text-xl">{{ formatCurrency(quotation.total) }}</dd>
                    </div>
                  </div>
                </dl>
              </CardSection>
            </div>
          </div>

          <div class="gap-6 grid grid-cols-1">
            <CardSection title="Catatan & Syarat" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <div class="flex flex-col gap-4">
                <div class="flex-1 bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                  <div class="text-form-label">Catatan</div>
                  <p class="mt-1 text-body whitespace-pre-line">{{ quotation.catatan || '-' }}</p>
                </div>
                <div class="flex-1 bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                  <div class="text-form-label">Syarat & Ketentuan</div>
                  <p class="mt-1 text-body whitespace-pre-line">{{ quotation.syarat_ketentuan || '-' }}</p>
                </div>
              </div>
            </CardSection>
          </div>
        </div>

        <div class="xl:col-span-1">
          <div class="top-6 sticky space-y-4">

            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <div class="space-y-6">
                  <div v-for="(attempt, idx) in approvalAttempts" :key="idx" class="space-y-3">
                    <span v-if="attempt.label"
                      class="inline-flex items-center bg-slate-50 px-3 py-1 border border-slate-200 rounded-full w-fit text-form-label text-slate-500">
                      {{ attempt.label }}
                    </span>
                    <Stepper :steps="attempt.steps" direction="vertical" />
                  </div>
                </div>

                <p v-if="quotation.status === QUOTATION_STATUS.Draft"
                  class="bg-slate-50 px-4 py-3 border border-slate-100 rounded-xl text-caption">
                  Pastikan seluruh data penawaran sudah benar sebelum diajukan untuk verifikasi.
                </p>

                <div class="flex flex-col gap-2">
                  <Button variant="outline-primary" class="inline-flex justify-center items-center gap-2 w-full"
                    @click="previewLangDialogOpen = true">
                    <Lucide icon="Printer" class="w-4 h-4" />
                    Preview PDF
                  </Button>
                  <Button v-if="quotation.status === QUOTATION_STATUS.Draft && canManage" variant="primary"
                    class="inline-flex justify-center items-center gap-2 w-full" @click="ajukanDialogOpen = true">
                    <Lucide icon="Send" class="w-4 h-4" />
                    Ajukan Verifikasi
                  </Button>
                </div>
              </div>
            </CardSection>
          </div>
        </div>

      </div>
    </div>
  </div>

  <ConfirmDialog :open="ajukanDialogOpen" title="Ajukan Penawaran?"
    description="Setelah diajukan, penawaran dikirim untuk diverifikasi. Pastikan seluruh data sudah benar."
    confirm-text="Ya, Ajukan" icon="Send" icon-class="bg-primary/10 text-primary" variant="primary"
    :loading="ajukanLoading" @close="ajukanDialogOpen = false" @confirm="submitQuotation" />

  <ConfirmDialog :open="reopenDialog.isOpen.value" title="Kembalikan ke Draft?"
    description="Penawaran kembali ke Draft dengan nomor yang sama. Riwayat verifikasi ditutup dan penawaran perlu diajukan ulang."
    confirm-text="Ya, kembalikan" icon="Undo2" icon-class="bg-warning/10 text-warning" variant="warning"
    :loading="reopenDialog.isLoading.value" :confirm-disabled="!reopenDialog.catatan.value.trim()"
    @close="closeReopenDialog" @confirm="submitReopen">
    <FormLabel>Alasan
      <RequiredAsterisk />
    </FormLabel>
    <FormTextarea v-model="reopenDialog.catatan.value" placeholder="Masukkan alasan pengembalian..." :rows="3"
      :maxlength="1000" />
  </ConfirmDialog>

  <QuotationDetailsEditModal :open="detailsEditOpen" :quotation="quotation"
    @close="detailsEditOpen = false" @saved="onDetailsSaved" />

  <QuotationPdfDialog :open="previewLangDialogOpen" :loading="previewLoading" @close="previewLangDialogOpen = false"
    @submit="preview" />
</template>
