<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import PageHeader from '@/components/SystemDesign/Page/PageHeader.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import PenawaranPdfDialog from '@/components/SystemDesign/Dialog/PenawaranPdfDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatDate, formatDateTime } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()

type Brand = 'tds' | 'proenergi'

const BRAND_CONFIG = {
  tds: {
    resourceEndpoint: '/penawarans',
    apiBase: '/api/penawarans',
    listRoute: 'penawarans-list',
    editRoute: 'penawarans-edit',
    title: 'Detail Penawaran',
    description: 'Informasi lengkap penawaran dan status verifikasi.',
  },
  proenergi: {
    resourceEndpoint: '/penawarans-proenergi',
    apiBase: '/api/penawarans-proenergi',
    listRoute: 'penawarans-list-proenergi',
    editRoute: 'penawarans-edit-proenergi',
    title: 'Detail Penawaran Proenergi',
    description: 'Informasi lengkap dan status persetujuan penawaran Proenergi',
  },
}
// computed, bukan const: route TDS/Proenergi berbagi komponen ini tanpa remount
const brand = computed<Brand>(() => (route.meta.brand as Brand) === 'proenergi' ? 'proenergi' : 'tds')
const isProenergi = computed(() => brand.value === 'proenergi')
const cfg = computed(() => BRAND_CONFIG[brand.value])
const penawaranApi = computed(() => createResourceApi(cfg.value.resourceEndpoint))
const id = computed(() => Number(route.params.id))
const penawaran = ref<any>({})
const loading = ref(true)
const ajukanLoading = ref(false)
const ajukanDialogOpen = ref(false)

/* Hardcode false — wiring ke role asli di luar scope, keputusan terpisah */
const canSeeHarga = ref(false)

const items = computed<any[]>(() => penawaran.value.items || [])

const dash = (v: any) => (v === null || v === undefined || v === '' ? '-' : v)

/* Section: Pembayaran & Lainnya */
const paymentFields = computed(() => {
  const p = penawaran.value
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

/* Section: Perhitungan Harga Dasar — formula diport dari Form.vue */
const dppHargaDasar = computed(() =>
  (Number(penawaran.value.harga_dasar) || 0) + (Number(penawaran.value.oat) || 0)
)
const ppnHargaDasar = computed(() => Math.round(dppHargaDasar.value * 0.11))
const grandTotalHargaDasar = computed(() => dppHargaDasar.value + ppnHargaDasar.value)

/* Section: Rincian Item — footer totals, gated canSeeHarga */
const subtotal = computed(() =>
  items.value.reduce((sum: number, it: any) => sum + (Number(it.jumlah_harga) || 0), 0)
)
const totalDiskon = computed(() =>
  Math.min(Math.max(Number(penawaran.value.discount) || 0, 0), subtotal.value)
)
const grandTotalHargaTebusSetelahDiskon = computed(() => subtotal.value - totalDiskon.value)

/* Section: Rincian Item — Volume/Persen totals footer, hanya tampil saat items > 2 */
const totalVolume = computed(() =>
  items.value.reduce((sum: number, it: any) => sum + (Number(it.volume_order) || 0), 0)
)
const totalPersen = computed(() =>
  items.value.reduce((sum: number, it: any) => sum + (Number(it.persen) || 0), 0)
)

/* Section: Ongkos Angkut — mirror kondisi tampil Form.vue:982,1019 */
const ongkosList = computed<any[]>(() => penawaran.value.ongkos || [])
const showOngkosKapal = computed(() => penawaran.value.metode === 'CIF' || penawaran.value.metode === 'DAP')
const showOngkosTruck = computed(() => penawaran.value.metode === 'DAP' || penawaran.value.metode === 'FOT')
const ongkosKapal = computed(() => ongkosList.value.filter((o: any) => o.jenis === 'KAPAL'))
const ongkosTruck = computed(() => ongkosList.value.filter((o: any) => o.jenis === 'TRUCK'))

// province/regency (BPS baru) dipakai kalau ada, fallback ke provinsi/kabupaten lama buat record yang belum termigrasi
function wilayahLabel(w: any) {
  if (!w) return null
  const parts = [
    w.province?.name || w.provinsi?.nama_provinsi,
    w.regency?.name || w.kabupaten?.nama_kabupaten,
    w.destinasi,
  ].filter(Boolean)
  return parts.length ? parts.join(' - ') : null
}

// backend (PenawaranApprovalStepsBuilder) yang nentuin title/status/label, frontend cuma format timestamp-nya
const approvalAttempts = computed<{ label: string | null; steps: StepItem[] }[]>(() =>
  (penawaran.value.approval_attempts ?? []).map((attempt: any) => ({
    label: attempt.label,
    steps: (attempt.steps ?? []).map((s: any) => ({
      title: s.title,
      description: s.description,
      status: s.status,
      timestamp: s.timestamp ? formatDateTime(s.timestamp) : undefined,
    })),
  }))
)

watch(() => route.fullPath, fetchPenawaran, { immediate: true })

async function fetchPenawaran() {
  loading.value = true
  try {
    const { data } = await penawaranApi.value.getById(id.value)
    penawaran.value = data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat detail penawaran')
  } finally {
    loading.value = false
  }
}

async function ajukanPenawaran() {
  ajukanLoading.value = true
  try {
    const { data } = await axios.patch(`${cfg.value.apiBase}/${id.value}/ajukan`)
    ajukanDialogOpen.value = false
    success('Berhasil Diajukan', data.message || 'Penawaran berhasil diajukan ke Branch Manager.')
    await fetchPenawaran()
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal mengajukan penawaran')
  } finally {
    ajukanLoading.value = false
  }
}

const previewLangDialogOpen = ref(false)
const previewLoading = ref(false)

async function preview(payload: { lang: 'id' | 'en'; priceFormat: 'dpp' | 'detail' }) {
  previewLoading.value = true
  const previewTab = openPdfLoadingTab()
  try {
    const response = await axios.get(`${cfg.value.apiBase}/${id.value}/preview`, {
      params: { lang: payload.lang, price_format: payload.priceFormat },
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
    error('Gagal', 'Gagal membuka preview PDF')
  } finally {
    previewLoading.value = false
  }
}

function goBack() {
  router.push({ name: cfg.value.listRoute })
}

function openEdit() {
  router.push({ name: cfg.value.editRoute, params: { id: id.value } })
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
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">{{ cfg.title }}</h2>
          <p class="font-lead mt-1">{{ cfg.description }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="['draft', 'rejected_bm', 'rejected_om'].includes(penawaran.status)" variant="soft-pending"
            @click="openEdit">
            <Lucide icon="Edit" class="mr-2 h-4 w-4" />
            Edit
          </Button>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </div>
      </div>

      <!-- 2-COLUMN LAYOUT -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- KIRI: Konten utama -->
        <div class="space-y-6 xl:col-span-2">

          <!-- Section 1: Informasi Penawaran -->
          <CardSection title="Informasi Penawaran" description="Identitas dokumen dan kontak tujuan" icon="FileText">
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12 md:col-span-5">
                <div class="space-y-3">
                  <div>
                    <div class="font-label">Nomor Penawaran</div>
                    <div
                      class="font-strong mt-1 whitespace-pre-line text-danger border border-danger/20 rounded px-2 py-1 inline-block bg-danger/5 text-xs">
                      {{ dash(penawaran.nomor_penawaran) }}
                    </div>
                  </div>

                  <div>
                    <div class="font-label">Masa Berlaku</div>
                    <div class="font-strong mt-1 whitespace-pre-line">
                      {{
                        penawaran.masa_berlaku
                          ? `${formatDate(penawaran.masa_berlaku)} – ${formatDate(penawaran.sampai_dengan)}`
                          : '-'
                      }}
                    </div>
                  </div>
                  <div>
                    <div class="font-label">Customer</div>
                    <div class="font-strong mt-1 whitespace-pre-line">
                      {{ dash(penawaran.customer?.company_name) }}
                    </div>
                  </div>

                  <div>
                    <div class="font-label">Cabang</div>
                    <div class="font-strong mt-1 whitespace-pre-line">
                      {{ dash(penawaran.cabang?.nama_cabang) }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-span-12 md:col-span-7">
                <div class="font-label mx-2 mb-1">Kontak Tujuan</div>
                <div>
                  <div class="rounded-xl border border-slate-200 px-4 py-3">
                    <div class="grid grid-cols-12 gap-4">
                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Kepada (Perusahaan / Dept.)</div>
                        <div class="font-strong mt-1">
                          {{ dash(penawaran.kepada) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Nama (UP.)</div>
                        <div class="font-strong mt-1">
                          {{ dash(penawaran.nama) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Jabatan</div>
                        <div class="font-strong mt-1">
                          {{ dash(penawaran.jabatan) }}
                        </div>
                      </div>

                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Telepon</div>
                        <div class="font-strong mt-1">
                          {{ dash(penawaran.telepon) }}
                        </div>
                      </div>

                      <div class="col-span-12">
                        <div class="font-label">Alamat</div>
                        <div class="font-strong mt-1">
                          {{ dash(penawaran.alamat) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </CardSection>

          <!-- Section 2: Detail Pengiriman & Daftar Produk (merged) -->
          <CardSection title="Detail Pengiriman & Daftar Produk"
            description="Instrumen pengiriman, tujuan kirim dan daftar produk penawaran" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <div class="grid grid-cols-12 gap-4">
              <div class="col-span-12 md:col-span-6">
                <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                  <div>
                    <div class="font-label">Tipe Pengiriman</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.type_pengiriman) }}</div>
                  </div>
                  <div>
                    <div class="font-label">Metode</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.metode) }}</div>
                  </div>

                  <!-- Ongkos Kapal (conditional, ported from old standalone Ongkos Angkut section) -->
                  <div v-if="showOngkosKapal" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="font-label mb-2">Ongkos Kapal</div>
                    <div v-if="ongkosKapal.length === 0" class="font-caption text-slate-500">
                      Belum ada data ongkos kapal.
                    </div>
                    <div v-for="oa in ongkosKapal" :key="oa.id"
                      class="rounded-xl border border-slate-200 px-4 py-3 mb-2 last:mb-0 bg-white">
                      <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-4">
                          <div class="font-label">Transportir</div>
                          <div class="font-strong mt-1">{{ dash(oa.transportir?.nama_perusahaan) }}</div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                          <div class="font-label">Wilayah Angkut</div>
                          <div class="font-strong mt-1">{{ dash(wilayahLabel(oa.wilayah)) }}</div>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                          <div class="font-label">Volume</div>
                          <div class="font-strong mt-1">{{ dash(oa.volume?.volume) }}</div>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                          <div class="font-label">Ongkos</div>
                          <div class="font-strong mt-1">{{ formatCurrency(oa.ongkos) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Ongkos Truck (conditional, ported from old standalone Ongkos Angkut section) -->
                  <div v-if="showOngkosTruck" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="font-label mb-2">Ongkos Truck</div>
                    <div v-if="ongkosTruck.length === 0" class="font-caption text-slate-500">
                      Belum ada data ongkos truck.
                    </div>
                    <div v-for="oa in ongkosTruck" :key="oa.id"
                      class="rounded-xl border border-slate-200 px-4 py-3 mb-2 last:mb-0 bg-white">
                      <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-4">
                          <div class="font-label">Transportir</div>
                          <div class="font-strong mt-1">{{ dash(oa.transportir?.nama_perusahaan) }}</div>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                          <div class="font-label">Wilayah Angkut</div>
                          <div class="font-strong mt-1">{{ dash(wilayahLabel(oa.wilayah)) }}</div>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                          <div class="font-label">Volume</div>
                          <div class="font-strong mt-1">{{ dash(oa.volume?.volume) }}</div>
                        </div>
                        <div class="col-span-6 md:col-span-2">
                          <div class="font-label">Ongkos</div>
                          <div class="font-strong mt-1">{{ formatCurrency(oa.ongkos) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                  <div>
                    <div class="font-label">Lokasi Pengiriman</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.lokasi_pengiriman) }}</div>
                  </div>
                  <div>
                    <div class="font-label">Titik Serah Terima & T&C Bongkar</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.keterangan) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <Table bordered sm class="font-body mt-4">
              <Table.Thead class="bg-slate-50">
                <Table.Th>Produk</Table.Th>
                <Table.Th class="w-28 text-right">Persen</Table.Th>
                <Table.Th class="w-40 text-right">Volume</Table.Th>
              </Table.Thead>
              <Table.Tbody class="bg-white">
                <Table.Tr v-for="item in items" :key="item.id_penawaran_item">
                  <Table.Td>
                    <div class="font-strong">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="font-caption mt-0.5">
                      {{ item.produk?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="font-num text-lg text-right">
                    {{ formatNumber(item.persen) }}%
                  </Table.Td>
                  <Table.Td class="font-num text-lg text-right">{{ formatNumber(item.volume_order) }}</Table.Td>
                </Table.Tr>
              </Table.Tbody>

              <Table.Tbody v-if="items.length > 2" class="border-t border-slate-200 bg-slate-50">
                <Table.Tr>
                  <Table.Td class="py-2.5 pr-6 text-right font-header">Total</Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatNumber(totalPersen) }}%</Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatNumber(totalVolume) }}</Table.Td>
                  <Table.Td v-if="canSeeHarga" colspan="2"></Table.Td>
                </Table.Tr>
              </Table.Tbody>

              <Table.Tbody v-if="canSeeHarga" class="border-t border-slate-200 bg-slate-50">
                <Table.Tr>
                  <Table.Td :colspan="items.length > 2 ? 4 : 3" class="py-2.5 pr-6 text-right font-header">
                    Subtotal Harga Tebus
                  </Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatCurrency(subtotal) }}</Table.Td>
                </Table.Tr>
                <Table.Tr v-if="totalDiskon > 0" class="bg-yellow-50">
                  <Table.Td :colspan="items.length > 2 ? 4 : 3"
                    class="py-2.5 pr-6 text-right font-header !text-yellow-700">
                    Diskon
                  </Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right !text-yellow-800">
                    - {{ formatCurrency(totalDiskon) }}
                  </Table.Td>
                </Table.Tr>
                <Table.Tr class="bg-emerald-50">
                  <Table.Td :colspan="items.length > 2 ? 4 : 3"
                    class="py-2.5 pr-6 text-right font-header !text-emerald-700">
                    Setelah Diskon
                  </Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right !text-emerald-800">
                    {{ formatCurrency(grandTotalHargaTebusSetelahDiskon) }}
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </CardSection>

          <div class="flex flex-col gap-6 xl:flex-row">
            <div class="flex-1">
              <!-- Section 3: Pembayaran & Lainnya (now standalone, no longer flex-paired) -->
              <CardSection title="Pembayaran & Lainnya" description="Ketentuan pembayaran dan info lainnya"
                icon="Wallet" icon-class="bg-amber-100 text-amber-600">
                <div class="grid grid-cols-12 gap-4">
                  <div v-for="field in paymentFields" :key="field.label" class="col-span-12 md:col-span-6">
                    <div class="font-label">{{ field.label }}</div>
                    <div class="font-strong mt-1 whitespace-pre-line"
                      :class="field.tone === 'red' ? 'text-danger' : ''">
                      {{ dash(field.value) }}
                    </div>
                  </div>
                </div>
              </CardSection>
            </div>

            <div class="flex-none">
              <!-- Section 4: Perhitungan Harga Dasar -->
              <CardSection title="Perhitungan Harga Dasar" description="Komponen harga dasar dan estimasi PPN"
                icon="Calculator" icon-class="bg-emerald-100 text-emerald-600">
                <dl class="flex flex-col gap-4 px-4">
                  <div class="flex-row gap-4 flex items-center justify-between">
                    <div class="grow bg-slate-100 p-4 rounded-lg text-right">
                      <dt class="font-label">Harga Dasar</dt>
                      <dd class="font-num-lg text-lg mt-1 !text-slate-800">{{ formatCurrency(penawaran.harga_dasar) }}
                      </dd>
                    </div>
                    <div class="grow bg-slate-100 p-4 rounded-lg text-right">
                      <dt class="font-label">OAT per Volume</dt>
                      <div class="font-num-lg text-lg mt-1 !text-slate-800">{{ formatCurrency(penawaran.oat) }}</div>
                    </div>
                  </div>
                  <div class="grow bg-slate-100 p-4 rounded-lg">
                    <div class="flex items-start justify-between">
                      <dt class="font-label">Subtotal (DPP)</dt>
                      <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(dppHargaDasar) }}</dd>
                    </div>
                  </div>
                  <div class="grow bg-slate-100 p-4 rounded-lg">
                    <div class="flex items-start justify-between">
                      <dt class="font-label">PPN 11%</dt>
                      <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(ppnHargaDasar) }}</dd>
                    </div>
                  </div>
                  <div class="grow bg-slate-100 p-4 rounded-lg">
                    <div class="flex items-start justify-between">
                      <dt class="font-label">TOTAL</dt>
                      <dd class="font-num-lg text-xl mt-1 !text-emerald-700">{{ formatCurrency(grandTotalHargaDasar)
                        }}</dd>
                    </div>
                  </div>
                </dl>
              </CardSection>
            </div>
          </div>

          <!-- Catatan & Syarat -->
          <CardSection title="Catatan & Syarat" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
            <div class="flex flex-row gap-4">
              <div class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Catatan</div>
                <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan || '-' }}</p>
              </div>
              <div class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Syarat & Ketentuan</div>
                <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.syarat_ketentuan || '-' }}</p>
              </div>
            </div>
          </CardSection>
        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-6 space-y-4">

            <!-- Status & Aksi -->
            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <div class="space-y-6">
                  <div v-for="(attempt, idx) in approvalAttempts" :key="idx" class="space-y-3">
                    <span v-if="attempt.label"
                      class="font-label inline-flex w-fit items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-500">
                      {{ attempt.label }}
                    </span>
                    <Stepper :steps="attempt.steps" direction="vertical" />
                  </div>
                </div>

                <p v-if="penawaran.status === 'draft'"
                  class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 font-caption">
                  Pastikan seluruh data penawaran sudah benar sebelum diajukan ke Branch Manager.
                </p>

                <div class="flex flex-col gap-2">
                  <Button v-if="penawaran.status === 'approved_om'" variant="outline-primary"
                    class="inline-flex w-full items-center justify-center gap-2" @click="previewLangDialogOpen = true">
                    <Lucide icon="Printer" class="h-4 w-4" />
                    Preview PDF
                  </Button>
                  <Button v-if="penawaran.status === 'draft'" variant="primary"
                    class="inline-flex w-full items-center justify-center gap-2" @click="ajukanDialogOpen = true">
                    <Lucide icon="Send" class="h-4 w-4" />
                    Ajukan ke Branch Manager
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
    description="Setelah diajukan, penawaran akan dikirim ke Branch Manager untuk verifikasi. Pastikan seluruh data sudah benar."
    confirm-text="Ya, Ajukan" icon="Send" icon-class="bg-primary/10 text-primary" variant="primary"
    :loading="ajukanLoading" @close="ajukanDialogOpen = false" @confirm="ajukanPenawaran" />

  <PenawaranPdfDialog :open="previewLangDialogOpen" :loading="previewLoading" @close="previewLangDialogOpen = false"
    @submit="preview" />
</template>
