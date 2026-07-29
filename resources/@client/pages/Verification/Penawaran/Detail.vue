<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import PenawaranPdfDialog from '@/components/SystemDesign/Dialog/PenawaranPdfDialog.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import {
  getVerifikasiDetailConfig,
  type VerifikasiRole,
  type VerifikasiBrand,
} from './config'
import { formatDate, formatDateTime } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

// computed, bukan const: route bm/om/reguler/proenergi berbagi komponen ini tanpa remount
const role = computed(() => route.meta.role as VerifikasiRole)
const brand = computed(() => route.meta.brand as VerifikasiBrand)
const config = computed(() => getVerifikasiDetailConfig(role.value, brand.value))

const id = computed(() => Number(route.params.id))
const penawaran = ref<any>({})
const loading = ref(false)
const notFound = ref(false)

async function fetchPenawaran() {
  loading.value = true
  notFound.value = false

  try {
    const { data } = await axios.get(`/api${config.value.fetchEndpoint}/${id.value}`)
    penawaran.value = data
  } catch (e: any) {
    notFound.value = true
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data penawaran.')
  } finally {
    loading.value = false
  }
}

const items = computed(() => penawaran.value.items ?? [])

const isProenergi = computed(() => brand.value === 'proenergi')

const totalVolume = computed(() => items.value.reduce((s, it) => s + (Number(it.volume_order) || 0), 0))
const dppHargaDasar = computed(() =>
  (Number(penawaran.value.harga_dasar) || 0) + (Number(penawaran.value.oat) || 0)
)
const subTotalFinal = computed(() => dppHargaDasar.value * totalVolume.value || 0)
const ppnFinal = computed(() => (Number(penawaran.value.ppn_harga_dasar) || 0) * totalVolume.value || 0)
const totalFinal = computed(() => subTotalFinal.value + ppnFinal.value || 0)

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}

const ongkosList = computed(() => penawaran.value.ongkos ?? [])
const showOngkosKapal = computed(() => penawaran.value.metode === 'CIF' || penawaran.value.metode === 'DAP')
const showOngkosTruck = computed(() => penawaran.value.metode === 'DAP' || penawaran.value.metode === 'FOT')
const ongkosKapal = computed(() => ongkosList.value.filter((o: any) => o.jenis === 'KAPAL'))
const ongkosTruck = computed(() => ongkosList.value.filter((o: any) => o.jenis === 'TRUCK'))

// province/regency (BPS baru) dipakai kalau tersedia, fallback ke
// provinsi/kabupaten lama untuk record yang belum termigrasi ATAU selama
// PenawaranController belum eager-load ongkos.wilayah.province/regency
// (laravel-nusa-address-full-migration Task 8 — lihat laporan Apollo).
function wilayahLabel(w: any) {
  if (!w) return null
  const parts = [
    w.province?.name || w.provinsi?.nama_provinsi,
    w.regency?.name || w.kabupaten?.nama_kabupaten,
    w.destinasi,
  ].filter(Boolean)
  return parts.length ? parts.join(' - ') : null
}

const approvalSteps = computed<StepItem[]>(() => {
  const s = penawaran.value.status
  const step = s === 'approved_om' ? 4 : s === 'approved_bm' ? 3 : s === 'waiting_branch_manager' ? 2 : 1

  function st(completedWhen: boolean, activeWhen: boolean): StepItem['status'] {
    if (completedWhen) return 'completed'
    if (activeWhen) return 'active'
    return 'pending'
  }

  return [
    {
      title: 'Draft',
      description: 'Penawaran dibuat dan masih dapat diubah.',
      status: st(step > 1, step === 1),
      timestamp: formatDateTime(penawaran.value.created_at),
    },
    {
      title: 'Waiting BM',
      description: 'Menunggu verifikasi dari Branch Manager.',
      status: st(step > 2, step === 2),
    },
    {
      title: 'Approved BM',
      description: 'Disetujui Branch Manager, diteruskan ke Operations Manager.',
      status: st(step > 3, step === 3),
      timestamp: formatDateTime(penawaran.value.bm_tanggal),
    },
    {
      title: 'Approved OM',
      description: 'Disetujui Operations Manager. Penawaran final.',
      status: st(step === 4, false),
    },
  ]
})


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
    fetchPenawaran()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memverifikasi penawaran.')
  } finally {
    verifikasiLoading.value = false
  }
}

async function tolak() {
  tolakLoading.value = true
  try {
    await axios.patch(`/api${config.value.rejectEndpoint(id.value)}`, { catatan: tolakCatatan.value })
    tolakDialogOpen.value = false
    tolakCatatan.value = ''
    success('Ditolak', 'Penawaran ditolak.')
    fetchPenawaran()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menolak penawaran.')
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

async function preview(payload: { lang: 'id' | 'en'; priceFormat: 'dpp' | 'detail' }) {
  previewLoading.value = true
  try {
    const response = await axios.get(`/api${config.value.fetchEndpoint}/${id.value}/preview`, {
      params: { lang: payload.lang, price_format: payload.priceFormat },
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
    previewLangDialogOpen.value = false
  } catch {
    notifyError('Gagal', 'Gagal membuka preview PDF')
  } finally {
    previewLoading.value = false
  }
}

function goBack() {
  router.back()
}

watch(() => route.fullPath, fetchPenawaran, { immediate: true })
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">{{ config.title }}</h2>
          <p class="font-lead mt-1">
            Informasi lengkap penawaran dan status verifikasi.
          </p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data penawaran...</span>
      </div>

      <div v-else-if="notFound" class="flex min-h-[320px] flex-col items-center justify-center gap-2 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
          <Lucide icon="AlertTriangle" class="h-7 w-7 text-rose-500" />
        </div>
        <h3 class="font-header">Data penawaran tidak ditemukan</h3>
        <p class="font-body">Silakan kembali ke halaman sebelumnya.</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- KIRI -->
        <div class="space-y-6 xl:col-span-2">

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
                    <div class="font-strong mt-1">
                      {{ penawaran.masa_berlaku ? `${formatDate(penawaran.masa_berlaku)} –
                      ${formatDate(penawaran.sampai_dengan)}` : '-' }}
                    </div>
                  </div>
                  <div>
                    <div class="font-label">Customer</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.customer?.company_name) }}
                    </div>
                  </div>
                  <div>
                    <div class="font-label">Cabang</div>
                    <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.cabang?.nama_cabang) }}</div>
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
                        <div class="font-strong mt-1">{{ dash(penawaran.kepada) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Nama (UP.)</div>
                        <div class="font-strong mt-1">{{ dash(penawaran.nama) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Jabatan</div>
                        <div class="font-strong mt-1">{{ dash(penawaran.jabatan) }}</div>
                      </div>
                      <div class="col-span-12 md:col-span-6">
                        <div class="font-label">Telepon</div>
                        <div class="font-strong mt-1">{{ dash(penawaran.telepon) }}</div>
                      </div>
                      <div class="col-span-12">
                        <div class="font-label">Alamat</div>
                        <div class="font-strong mt-1">{{ dash(penawaran.alamat) }}</div>
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

            <div class="rounded-xl">
              <Table bordered sm class="font-body mt-4">
                <Table.Thead class="bg-slate-50">
                  <Table.Th>Produk</Table.Th>
                  <Table.Th class="w-28 text-right">Persen</Table.Th>
                  <Table.Th class="w-40 text-right">Volume</Table.Th>
                  <Table.Th class="w-40 text-right">Pricelist</Table.Th>
                </Table.Thead>
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="item in items" :key="item.id_penawaran_item">
                    <Table.Td>
                      <div class="font-strong">{{ item.produk?.nama_produk || '-' }}</div>
                      <div class="font-caption mt-0.5">
                        {{ item.produk?.jenis?.nama || '-' }}
                        <span class="mx-1">·</span>
                        {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || ''
                        }}
                      </div>
                    </Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatNumber(item.persen) }}%</Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatNumber(item.volume_order) }}</Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatCurrency(item.harga_tebus) }}</Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </CardSection>

          <CardSection title="Rincian Harga" description="Komponen harga penawaran" icon="Wallet"
            icon-class="bg-emerald-100 text-emerald-600">
            <div class="grid grid-cols-2 gap-6">
              <dl class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div class="bg-slate-100 p-4 rounded-lg text-right">
                    <dt class="font-label">Harga Dasar</dt>
                    <dd class="font-num-lg text-lg mt-1">{{ formatCurrency(penawaran.harga_dasar) }}
                    </dd>
                  </div>
                  <div class="bg-slate-100 p-4 rounded-lg text-right">
                    <dt class="font-label">OAT per Volume</dt>
                    <div class="font-num-lg text-lg mt-1">{{ formatCurrency(penawaran.oat) }}</div>
                  </div>
                </div>
                <div class="grow bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="font-label">Subtotal (DPP) Harga Dasar</dt>
                  <dd class="font-num-lg text-xl mt-1">{{ formatCurrency(dppHargaDasar) }}</dd>
                </div>
                <div class="grow bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="font-label">PPN (11%) Harga Dasar</dt>
                  <dd class="font-num-lg text-xl mt-1">{{ formatCurrency(penawaran.ppn_harga_dasar) }}
                  </dd>
                </div>
                <div class="grow bg-slate-100 p-4 rounded-lg text-right">
                  <dt class="font-label">Total Harga Dasar</dt>
                  <dd class="font-num-lg text-xl mt-1 text-success">{{
                    formatCurrency(penawaran.grand_total_harga_dasar)
                    }}</dd>
                </div>
              </dl>

              <div>
                <div class="font-display text-right">Grand Total</div>
                <div class="font-body text-right">Perhitungan harga penawaran
                  berdasarkan<br />harga dasar
                  dan
                  volume
                  penawaran</div>

                <div class="flex flex-col gap-4 mt-[17px]">
                  <div class="bg-green-50 p-4 rounded-lg text-right">
                    <div class="font-section">Subtotal (DPP) Penawaran Final</div>
                    <div class="font-num-lg text-xl mt-1">{{ formatCurrency(subTotalFinal) }}
                    </div>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg text-right">
                    <div class="font-section">PPN (11%) Penawaran Final</div>
                    <div class="font-num-lg text-xl mt-1">{{ formatCurrency(ppnFinal) }}
                    </div>
                  </div>
                  <div class="bg-green-50 p-4 rounded-lg text-right">
                    <div class="font-section">Total Penawaran Final</div>
                    <div class="font-num-lg text-xl mt-1 text-success">{{ formatCurrency(totalFinal) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardSection>

          <div class="flex flex-col gap-6 xl:flex-row">
            <div class="flex-1">

              <CardSection title="Pembayaran & Lainnya" description="Ketentuan pembayaran dan toleransi" icon="Wallet"
                icon-class="bg-amber-100 text-amber-600">
                <div class="grid grid-cols-12 gap-4">
                  <div class="col-span-12 md:col-span-6">
                    <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                      <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12" :class="isProenergi ? 'md:col-span-6' : ''">
                          <div class="font-label">Tipe Pembayaran</div>
                          <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.tipe_pembayaran) }}</div>
                        </div>
                      </div>

                      <!-- Panel CUSTOM -->
                      <div v-if="penawaran.tipe_pembayaran === 'CUSTOM'"
                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h4 class="font-section mb-3">Detail Pembayaran Custom</h4>
                        <div class="flex flex-col gap-4">
                          <div>
                            <div class="font-label">Down Payment (%)</div>
                            <div class="font-strong mt-1">{{ formatNumber(penawaran.dp_persen) }}%</div>
                          </div>

                          <div>
                            <div class="font-label">Repayment</div>
                            <div class="font-strong mt-1">{{ formatNumber(penawaran.repayment_persen) }}% after {{
                              formatNumber(penawaran.repayment_hari) }} days</div>
                          </div>
                        </div>
                      </div>

                      <div>
                        <div class="font-label">Metode Pemesanan</div>
                        <div class="font-strong mt-1 whitespace-pre-line">{{ dash(penawaran.order_method) }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-span-12 md:col-span-6">
                    <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                      <div class="flex flex-col gap-4">
                        <div>
                          <div class="font-label">Toleransi Penyusutan</div>
                          <div class="font-strong mt-1">{{ formatNumber(penawaran.toleransi_penyusutan) }}%</div>
                        </div>

                        <div>
                          <div class="font-label">Abrasi</div>
                          <div class="font-strong mt-1">{{ dash(penawaran.abrasi) }}</div>
                        </div>

                        <div>
                          <div class="font-label">Refund / Volume</div>
                          <div class="font-strong mt-1">{{ formatCurrency(penawaran.refund) }}</div>
                        </div>

                        <div>
                          <div class="font-label">Other Cost</div>
                          <div class="font-strong mt-1">{{ formatCurrency(penawaran.other_cost) }}</div>
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
                  <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="font-label">Catatan</div>
                    <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan || '-' }}</p>
                  </div>
                  <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="font-label">Syarat & Ketentuan</div>
                    <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.syarat_ketentuan || '-' }}</p>
                  </div>
                </div>
              </CardSection>
            </div>
          </div>


        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-6 space-y-4">
            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <Stepper :steps="approvalSteps" direction="vertical" />

                <Button variant="outline-primary" class="inline-flex w-full items-center justify-center gap-2"
                  @click="previewLangDialogOpen = true">
                  <Lucide icon="Printer" class="h-4 w-4" />
                  Preview PDF
                </Button>
              </div>
            </CardSection>

            <CardSection title="Catatan Verifikasi" icon="MessageSquare" icon-class="bg-blue-100 text-blue-600">
              <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">{{
                    config.catatanVerifikasiLabel }}
                  </div>
                  <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_verifikasi || '-' }}
                  </p>
                </div>
                <div v-if="config.showOmCatatan" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">Catatan Verifikasi OM</div>
                  <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_om || '-' }}</p>
                </div>

                <div v-if="penawaran.status === config.actionWaitingStatus" class="flex flex-row gap-2">
                  <Button variant="danger" class="inline-flex w-full items-center justify-center gap-2"
                    @click="tolakDialogOpen = true">
                    <Lucide icon="X" class="h-4 w-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2"
                    @click="verifikasiDialogOpen = true">
                    <Lucide icon="Check" class="h-4 w-4" />
                    Setujui
                  </Button>
                </div>

                <p v-else class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 font-caption">
                  Tidak ada aksi yang bisa dilakukan pada status penawaran saat ini.
                </p>
              </div>
            </CardSection>
          </div>
        </div>

      </div>
    </div>
  </div>

  <ConfirmDialog :open="verifikasiDialogOpen" :title="`Verifikasi Penawaran ${role.toUpperCase()}?`"
    description="Pastikan seluruh data penawaran sudah benar sebelum menyetujui." confirm-text="Ya, Disetujui"
    icon="Check" icon-class="bg-primary/10 text-primary" variant="primary" :loading="verifikasiLoading"
    @close="verifikasiDialogOpen = false; verifikasiCatatan = ''" @confirm="verifikasi">
    <FormLabel>Catatan Verifikasi</FormLabel>
    <FormTextarea v-model="verifikasiCatatan" placeholder="Masukkan catatan jika ada..." :rows="3" />
  </ConfirmDialog>

  <ConfirmDialog :open="tolakDialogOpen" :title="`Tolak Penawaran ${role.toUpperCase()}?`"
    description="Penawaran akan ditolak dan dikembalikan ke tahap sebelumnya." confirm-text="Ya, Tolak" icon="X"
    icon-class="bg-danger/10 text-danger" variant="danger" :loading="tolakLoading"
    @close="tolakDialogOpen = false; tolakCatatan = ''" @confirm="tolak">
    <FormLabel>Catatan Penolakan</FormLabel>
    <FormTextarea v-model="tolakCatatan" placeholder="Masukkan alasan penolakan..." :rows="3" />
  </ConfirmDialog>

  <PenawaranPdfDialog :open="previewLangDialogOpen" :loading="previewLoading" @close="previewLangDialogOpen = false"
    @submit="preview" />
</template>
