<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import {
  getVerifikasiDetailConfig,
  type VerifikasiRole,
} from './config'
import { QUOTATION_STATUS, priceTierBadgeClass, priceTierLabel } from '@/pages/Quotation/status'
import { formatDate, formatDateTime } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'
import type { QuotationBrand, QuotationTransport } from '@/types'
import QuotationPdfDialog from '@/pages/Quotation/components/QuotationPdfDialog.vue'
import QuotationTransportSummary from '@/pages/Quotation/components/QuotationTransportSummary.vue'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const role = computed(() => route.meta.role as VerifikasiRole)
const brand = computed(() => route.meta.brand as QuotationBrand)
const config = computed(() => getVerifikasiDetailConfig(role.value, brand.value))

const id = computed(() => Number(route.params.id))
const quotation = ref<any>({})
const loading = ref(false)
const notFound = ref(false)

async function fetchQuotation() {
  loading.value = true
  notFound.value = false

  try {
    const { data } = await axios.get(`/api${config.value.fetchEndpoint}/${id.value}`)
    quotation.value = data
  } catch (e: any) {
    notFound.value = true
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data penawaran.')
  } finally {
    loading.value = false
  }
}

const items = computed(() => quotation.value.items ?? [])
const isMultiProduct = computed<boolean>(() => items.value.length > 1)

const isProenergi = computed(() => brand.value === 'proenergi')

const totalQuantity = computed<number>(() =>
  items.value.reduce((s: number, it: any) => s + (Number(it.quantity) || 0), 0),
)
const marginPercent = computed<number | null>(() => {
  const dpp = Number(quotation.value.dpp) || 0
  const marginTotal = quotation.value.margin_total
  return dpp > 0 && marginTotal != null ? (Number(marginTotal) / dpp) * 100 : null
})

const cogsBasisNote = computed<string>(() => {
  if (isMultiProduct.value) return ''
  const basis = items.value[0]?.cogs_basis
  if (!basis) return ''
  return `Harga ${basis === 'loco' ? 'Loco' : 'Franco'}`
})

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}

const transports = computed<QuotationTransport[]>(() => quotation.value.transports ?? [])

const approvalAttempts = computed<{ label: string | null; steps: StepItem[] }[]>(() =>
  (quotation.value.approval_attempts ?? []).map((attempt: any) => ({
    label: attempt.label,
    steps: (attempt.steps ?? []).map((s: any) => ({
      title: s.title,
      description: s.description,
      status: s.status,
      timestamp: s.timestamp ? formatDateTime(s.timestamp) : undefined,
    })),
  }))
)

const verifikasiDialogOpen = ref(false)
const verifikasiCatatan = ref('')
const verifikasiLoading = ref(false)

const tolakDialogOpen = ref(false)
const tolakCatatan = ref('')
const tolakLoading = ref(false)

async function verifikasi() {
  verifikasiLoading.value = true
  try {
    await axios.patch(`/api${config.value.verifyEndpoint(id.value)}`, {
      catatan: verifikasiCatatan.value || 'Tanpa catatan',
    })
    verifikasiDialogOpen.value = false
    verifikasiCatatan.value = ''
    success('Berhasil', 'Penawaran disetujui.')
    fetchQuotation()
  } catch (e: any) {
    verifikasiDialogOpen.value = false
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memverifikasi penawaran.')
    if ([403, 409].includes(e.response?.status)) fetchQuotation()
  } finally {
    verifikasiLoading.value = false
  }
}

async function tolak() {
  if (!tolakCatatan.value.trim()) {
    notifyError('Gagal', 'Catatan penolakan wajib diisi.')
    return
  }

  tolakLoading.value = true
  try {
    await axios.patch(`/api${config.value.rejectEndpoint(id.value)}`, { catatan: tolakCatatan.value })
    tolakDialogOpen.value = false
    tolakCatatan.value = ''
    success('Ditolak', 'Penawaran ditolak.')
    fetchQuotation()
  } catch (e: any) {
    tolakDialogOpen.value = false
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menolak penawaran.')
    if ([403, 409].includes(e.response?.status)) fetchQuotation()
  } finally {
    tolakLoading.value = false
  }
}

function formatCurrency(v?: number | string | null) {
  return `Rp. ${(Number(v) || 0).toLocaleString('id-ID')}`
}
function formatNumber(v?: number | string | null) {
  return (Number(v) || 0).toLocaleString('id-ID')
}
const previewLangDialogOpen = ref(false)
const previewLoading = ref(false)

async function preview(payload: { lang: 'id' | 'en' }) {
  previewLoading.value = true
  const previewTab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/api${config.value.fetchEndpoint}/${id.value}/preview`, {
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
  } catch {
    previewTab?.close()
    notifyError('Gagal', 'Gagal membuka preview PDF')
  } finally {
    previewLoading.value = false
  }
}

function goBack() {
  router.back()
}

watch(() => route.fullPath, fetchQuotation, { immediate: true })
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-4 intro-x">

      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-screen-title">{{ config.title }}</h2>
            <span v-if="quotation.out_of_range === true"
              class="inline-flex items-center px-3 py-1 rounded-full text-form-label whitespace-nowrap"
              :class="priceTierBadgeClass('out_of_range')">
              {{ priceTierLabel('out_of_range') }}
            </span>
            <span v-else-if="quotation.price_tier"
              class="inline-flex items-center px-3 py-1 rounded-full text-form-label whitespace-nowrap"
              :class="priceTierBadgeClass(quotation.price_tier)">
              {{ priceTierLabel(quotation.price_tier) }}
            </span>
          </div>
          <p class="mt-1 text-body-lg">
            Informasi lengkap penawaran dan status verifikasi.
          </p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
          Kembali
        </Button>
      </div>

      <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[320px] text-slate-500">
        <Lucide icon="Loader" class="w-6 h-6 animate-spin" />
        <span class="text-body">Memuat data penawaran...</span>
      </div>

      <div v-else-if="notFound" class="flex flex-col justify-center items-center gap-2 min-h-[320px] text-center">
        <div class="flex justify-center items-center bg-rose-50 rounded-full w-14 h-14">
          <Lucide icon="AlertTriangle" class="w-7 h-7 text-rose-500" />
        </div>
        <h3 class="text-section-title">Data penawaran tidak ditemukan</h3>
        <p class="text-body">Silakan kembali ke halaman sebelumnya.</p>
      </div>

      <div v-else class="gap-6 grid grid-cols-1 xl:grid-cols-3">

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
                    <div class="mt-1 text-body-strong">
                      {{ quotation.masa_berlaku ? `${formatDate(quotation.masa_berlaku)} –
                      ${formatDate(quotation.sampai_dengan)}` : '-' }}
                    </div>
                  </div>
                  <div>
                    <div class="text-form-label">Customer</div>
                    <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.customer?.company_name) }}
                    </div>
                  </div>
                  <div>
                    <div class="text-form-label">Cabang</div>
                    <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.branch?.nama_cabang) }}</div>
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
                        <div class="mt-1 text-body-strong">{{ dash(quotation.customer?.company_name) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Nama (UP.)</div>
                        <div class="mt-1 text-body-strong">{{ dash(quotation.customer_contact?.full_name) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Jabatan</div>
                        <div class="mt-1 text-body-strong">{{ dash(quotation.customer_contact?.position) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="text-form-label">Telepon</div>
                        <div class="mt-1 text-body-strong">{{ dash(quotation.customer_contact?.mobile) }}</div>
                      </div>
                      <div class="col-span-12">
                        <div class="text-form-label">Alamat</div>
                        <div class="mt-1 text-body-strong">{{ dash(quotation.customer?.head_office_address?.address_line) }}
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
                  <div class="gap-4 grid grid-cols-2">
                    <div>
                      <div class="text-form-label">Tipe Pengiriman</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.type_pengiriman) }}</div>
                    </div>
                    <div>
                      <div class="text-form-label">Metode</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.metode) }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
                  <div>
                    <div class="text-form-label">Lokasi Pengiriman</div>
                    <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.lokasi_pengiriman) }}</div>
                  </div>
                  <div>
                    <div class="text-form-label">Titik Serah Terima & T&C Bongkar</div>
                    <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.keterangan) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <QuotationTransportSummary v-if="quotation.transport_basis !== 'franco'" :basis="quotation.transport_basis ?? null" :mode="quotation.transport_mode ?? null"
              :transports="transports" show-internals />

            <div class="rounded-xl">
              <Table bordered sm class="mt-4 text-body">
                <Table.Thead class="bg-slate-50">
                  <Table.Th>Produk</Table.Th>
                  <Table.Th class="w-40">Source</Table.Th>
                  <Table.Th class="w-28 text-right">Quantity</Table.Th>
                  <Table.Th class="w-36 text-right">Material/m³</Table.Th>
                  <Table.Th class="w-36 text-right">Transport/m³</Table.Th>
                  <Table.Th class="w-36 text-right">Final/m³</Table.Th>
                  <Table.Th class="w-40 text-right">Subtotal</Table.Th>
                  <Table.Th class="w-44">Batas Tier</Table.Th>
                  <Table.Th class="w-32 text-center">Tier</Table.Th>
                  <Table.Th class="w-36 text-right">Margin Material</Table.Th>
                  <Table.Th class="w-36 text-right">Margin Transport</Table.Th>
                </Table.Thead>
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="item in items" :key="item.id">
                    <Table.Td>
                      <div class="text-body-strong">{{ item.product?.nama_produk || '-' }}</div>
                      <div class="mt-0.5 text-caption">
                        {{ item.product?.jenis?.nama || '-' }}
                        <span class="mx-1">·</span>
                        {{ item.product?.ukuran?.nama_ukuran || '-' }} {{ item.product?.ukuran?.satuan?.nama_satuan || ''
                        }}
                      </div>
                    </Table.Td>
                    <Table.Td class="text-body">{{ item.source_branch?.nama_cabang ?? '—' }}</Table.Td>
                    <Table.Td class="num-sm text-lg text-right">{{ formatNumber(item.quantity) }}</Table.Td>
                    <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.unit_price) }}</Table.Td>
                    <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.transport_price) }}</Table.Td>
                    <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.final_price) }}</Table.Td>
                    <Table.Td class="num-sm text-lg text-right">{{ formatCurrency(item.final_total) }}</Table.Td>
                    <Table.Td class="text-caption">
                      <template v-if="item.tier_limits">
                        <div v-if="item.tier_limits.price_list != null">Price list {{ formatCurrency(item.tier_limits.price_list) }}</div>
                        <div>BM ≥ {{ formatCurrency(item.tier_limits.bm_price) }}</div>
                        <div>OM ≥ {{ formatCurrency(item.tier_limits.om_price) }}</div>
                        <div>CEO ≥ {{ formatCurrency(item.tier_limits.ceo_price) }}</div>
                      </template>
                      <span v-else>—</span>
                    </Table.Td>
                    <Table.Td class="text-center">
                      <span v-if="item.price_tier"
                        class="inline-flex items-center px-3 py-1 rounded-full text-form-label whitespace-nowrap"
                        :class="priceTierBadgeClass(item.price_tier)">
                        {{ priceTierLabel(item.price_tier) }}
                      </span>
                      <span v-else>-</span>
                    </Table.Td>
                    <Table.Td class="num-sm text-right">
                      <span v-if="item.margin_material != null">{{ formatCurrency(item.margin_material) }}</span>
                      <span v-else class="text-slate-400" title="Data COGS tidak tersedia">—</span>
                    </Table.Td>
                    <Table.Td class="num-sm text-right">
                      <span v-if="item.margin_transport != null">{{ formatCurrency(item.margin_transport) }}</span>
                      <span v-else class="text-slate-400" title="Tidak tercatat (data lama)">—</span>
                    </Table.Td>
                  </Table.Tr>
                </Table.Tbody>

                <Table.Tbody v-if="isMultiProduct" class="bg-slate-50 border-slate-200 border-t">
                  <Table.Tr>
                    <Table.Td colspan="2" class="py-2.5 pr-6 text-section-title text-right">Total</Table.Td>
                    <Table.Td class="py-2.5 num-md text-right">{{ formatNumber(totalQuantity) }}</Table.Td>
                    <Table.Td colspan="3"></Table.Td>
                    <Table.Td class="py-2.5 num-md text-right">{{ formatCurrency(quotation.dpp) }}</Table.Td>
                    <Table.Td colspan="4"></Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </CardSection>

          <CardSection title="Rincian Harga" description="Komponen harga penawaran" icon="Wallet"
            icon-class="bg-emerald-100 text-emerald-600">
            <dl class="gap-4 grid grid-cols-1 sm:grid-cols-2">
              <div class="bg-slate-100 p-4 rounded-lg text-right">
                <dt class="text-form-label">Subtotal Material</dt>
                <dd class="mt-1 num-md text-lg">{{ formatCurrency(quotation.subtotal_material) }}</dd>
              </div>
              <div class="bg-slate-100 p-4 rounded-lg text-right">
                <dt class="text-form-label">Total Transport</dt>
                <dd class="mt-1 num-md text-lg">{{ formatCurrency(quotation.transport_total) }}</dd>
              </div>
              <div class="bg-slate-100 p-4 rounded-lg text-right">
                <dt class="text-form-label">DPP</dt>
                <dd class="mt-1 num-md text-xl">{{ formatCurrency(quotation.dpp) }}</dd>
              </div>
              <div class="bg-slate-100 p-4 rounded-lg text-right">
                <dt class="text-form-label">PPN (11%)</dt>
                <dd class="mt-1 num-md text-xl">{{ formatCurrency(quotation.ppn) }}</dd>
              </div>
              <div class="bg-slate-100 p-4 rounded-lg text-right">
                <dt class="text-form-label">Total</dt>
                <dd class="mt-1 num-md text-success text-xl">{{ formatCurrency(quotation.total) }}</dd>
              </div>
            </dl>

            <div class="mt-6 pt-5 border-slate-100 border-t">
              <h4 class="mb-3 text-overline">Analisa Margin</h4>
              <dl class="gap-4 grid grid-cols-1">
                <div class="bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="text-form-label">Margin Material</dt>
                  <dd v-if="quotation.margin_material_total != null" class="mt-1 num-md text-lg">
                    {{ formatCurrency(quotation.margin_material_total) }}
                  </dd>
                  <dd v-else class="mt-1 text-body !text-amber-700">Data COGS material tidak lengkap</dd>
                </div>
                <div class="bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="text-form-label">Margin Transport</dt>
                  <dd v-if="quotation.margin_transport_total != null" class="mt-1 num-md text-lg">
                    {{ formatCurrency(quotation.margin_transport_total) }}
                  </dd>
                  <dd v-else class="mt-1 text-body text-slate-500">Tidak tercatat (data lama)</dd>
                </div>
                <div class="bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="text-form-label">Total Margin</dt>
                  <dd v-if="quotation.margin_total != null" class="mt-1 num-md text-lg">
                    {{ formatCurrency(quotation.margin_total) }}
                    <template v-if="marginPercent != null">({{ marginPercent.toFixed(2) }}% dari DPP)</template>
                  </dd>
                  <template v-else>
                    <dd class="mt-1 num-md text-lg">—</dd>
                    <dd class="mt-1 text-caption">Tidak dihitung karena ada komponen yang tidak tercatat</dd>
                  </template>
                </div>
              </dl>
              <div v-if="cogsBasisNote" class="mt-1 text-caption text-slate-500 italic">{{ cogsBasisNote }}</div>
            </div>
          </CardSection>

          <div class="flex xl:flex-row flex-col gap-6">
            <div class="flex-1">

              <CardSection title="Pembayaran & Lainnya" description="Ketentuan pembayaran dan toleransi" icon="Wallet"
                icon-class="bg-amber-100 text-amber-600">
                <div class="gap-4 grid grid-cols-12">
                  <div class="col-span-12 md:col-span-6">
                    <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
                      <div class="gap-4 grid grid-cols-12">
                        <div class="col-span-12" :class="isProenergi ? 'md:col-span-6' : ''">
                          <div class="text-form-label">Tipe Pembayaran</div>
                          <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.tipe_pembayaran) }}</div>
                        </div>
                      </div>

                      <div v-if="quotation.tipe_pembayaran === 'CUSTOM'"
                        class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
                        <h4 class="mb-3 text-overline">Detail Pembayaran Custom</h4>
                        <div class="flex flex-col gap-4">
                          <div>
                            <div class="text-form-label">Down Payment (%)</div>
                            <div class="mt-1 text-body-strong">{{ formatNumber(quotation.dp_persen) }}%</div>
                          </div>

                          <div>
                            <div class="text-form-label">Repayment</div>
                            <div class="mt-1 text-body-strong">{{ formatNumber(quotation.repayment_persen) }}% after {{
                              formatNumber(quotation.repayment_hari) }} days</div>
                          </div>
                        </div>
                      </div>

                      <div>
                        <div class="text-form-label">Metode Pemesanan</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ dash(quotation.order_method) }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
                      <div class="flex flex-col gap-4">
                        <div>
                          <div class="text-form-label">Toleransi Penyusutan</div>
                          <div class="mt-1 text-body-strong">{{ formatNumber(quotation.toleransi_penyusutan) }}%</div>
                        </div>

                        <div>
                          <div class="text-form-label">Abrasi</div>
                          <div class="mt-1 text-body-strong">{{ dash(quotation.abrasi) }}</div>
                        </div>

                        <div>
                          <div class="text-form-label">Refund / Volume</div>
                          <div class="mt-1 text-body-strong">{{ formatCurrency(quotation.refund) }}</div>
                        </div>

                        <div>
                          <div class="text-form-label">Other Cost</div>
                          <div class="mt-1 text-body-strong">{{ formatCurrency(quotation.other_cost) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </CardSection>
            </div>

            <div class="flex-1">
              <CardSection title="Catatan & Syarat" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
                <div class="flex flex-col gap-4">
                  <div class="bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="text-form-label">Catatan</div>
                    <p class="mt-1 text-body whitespace-pre-line">{{ quotation.catatan || '-' }}</p>
                  </div>
                  <div class="bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="text-form-label">Syarat & Ketentuan</div>
                    <p class="mt-1 text-body whitespace-pre-line">{{ quotation.syarat_ketentuan || '-' }}</p>
                  </div>
                </div>
              </CardSection>
            </div>
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

                <p v-if="!quotation.can_verify && quotation.status === QUOTATION_STATUS.WaitingVerification && quotation.verifier_role"
                  class="bg-slate-50 px-4 py-3 border border-slate-100 rounded-xl text-caption">
                  Menunggu verifikasi {{ quotation.verifier_role }}
                </p>

                <Button variant="outline-primary" class="inline-flex justify-center items-center gap-2 w-full"
                  @click="previewLangDialogOpen = true">
                  <Lucide icon="Printer" class="w-4 h-4" />
                  Preview PDF
                </Button>
              </div>
            </CardSection>

            <CardSection v-if="quotation.can_verify === true" title="Aksi Verifikasi"
              icon="CheckCircle" icon-class="bg-blue-100 text-blue-600">
              <div class="space-y-3">
                <div class="flex flex-row gap-2">
                  <Button variant="danger" class="inline-flex justify-center items-center gap-2 w-full"
                    @click="tolakDialogOpen = true">
                    <Lucide icon="X" class="w-4 h-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex justify-center items-center gap-2 w-full"
                    @click="verifikasiDialogOpen = true">
                    <Lucide icon="Check" class="w-4 h-4" />
                    Setujui
                  </Button>
                </div>
              </div>
            </CardSection>
          </div>
        </div>

      </div>
    </div>
  </div>

  <ConfirmDialog :open="verifikasiDialogOpen" title="Setujui Penawaran?"
    description="Pastikan seluruh data penawaran sudah benar sebelum menyetujui." confirm-text="Ya, Disetujui"
    icon="Check" icon-class="bg-primary/10 text-primary" variant="primary" :loading="verifikasiLoading"
    @close="verifikasiDialogOpen = false; verifikasiCatatan = ''" @confirm="verifikasi">
    <FormLabel>Catatan Verifikasi</FormLabel>
    <FormTextarea v-model="verifikasiCatatan" placeholder="Masukkan catatan jika ada..." :rows="3" />
  </ConfirmDialog>

  <ConfirmDialog :open="tolakDialogOpen" title="Tolak Penawaran?"
    description="Penawaran akan ditolak. Marketing dapat membuat penawaran baru dari salinan penawaran ini." confirm-text="Ya, Tolak" icon="X"
    icon-class="bg-danger/10 text-danger" variant="danger" :loading="tolakLoading"
    :confirm-disabled="!tolakCatatan.trim()" @close="tolakDialogOpen = false; tolakCatatan = ''" @confirm="tolak">
    <FormLabel>Catatan Penolakan
      <RequiredAsterisk />
    </FormLabel>
    <FormTextarea v-model="tolakCatatan" placeholder="Masukkan alasan penolakan..." :rows="3" />
  </ConfirmDialog>

  <QuotationPdfDialog :open="previewLangDialogOpen" :loading="previewLoading" @close="previewLangDialogOpen = false"
    @submit="preview" />
</template>
