<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'

import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import {
  getVerifikasiDetailConfig,
  formatStatusLabel,
  type VerifikasiRole,
  type VerifikasiBrand,
} from './config'
import { formatDateTime } from '@/utils/format'

interface PenawaranItemType {
  id_penawaran_item: number
  volume_order: number
  persen?: number
  produk?: {
    nama_produk?: string
    jenis?: { nama?: string }
    ukuran?: { nama_ukuran?: string; satuan?: { nama_satuan?: string } }
  }
}

interface Penawaran {
  id_penawaran?: number
  nomor_penawaran?: string
  status?: string
  disposisi_penawaran?: string | number
  metode?: string
  order_method?: string
  masa_berlaku?: string
  sampai_dengan?: string
  tipe_pembayaran?: string
  lokasi_pengiriman?: string
  syarat_ketentuan?: string
  harga_dasar?: number
  refund?: number
  other_cost?: number
  discount?: number
  oat?: number
  toleransi_penyusutan?: number
  keterangan?: string
  catatan?: string
  catatan_verifikasi?: string
  catatan_om?: string
  customer?: { nama_perusahaan?: string }
  cabang?: { nama_cabang?: string }
  produk_harga?: {
    periode_awal?: string
    periode_akhir?: string
    harga_price_list?: number
    harga_price_list_pe?: number
    harga_cogs?: number
    harga_bm?: number
    harga_om?: number
  }
  items?: PenawaranItemType[]
  created_at?: string
  bm_tanggal?: string | null
  om_tanggal?: string | null
}

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const role = route.meta.role as VerifikasiRole
const brand = route.meta.brand as VerifikasiBrand
const config = getVerifikasiDetailConfig(role, brand)

const id = Number(route.params.id)
const penawaran = ref<Penawaran>({})
const loading = ref(false)
const notFound = ref(false)

async function fetchPenawaran() {
  loading.value = true
  notFound.value = false

  try {
    const { data } = await axios.get(`/api${config.fetchEndpoint}/${id}`)
    penawaran.value = data
  } catch (e: any) {
    notFound.value = true
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat data penawaran.')
  } finally {
    loading.value = false
  }
}

const items = computed(() => penawaran.value.items ?? [])

function calcJumlah(item: PenawaranItemType) {
  const vol = Number(item.volume_order) || 0
  const hargaDasar = Number(penawaran.value.harga_dasar) || 0
  const refund = Number(penawaran.value.refund) || 0
  const otherCost = Number(penawaran.value.other_cost) || 0
  return vol * hargaDasar - vol * refund - vol * otherCost
}

const totalVolume = computed(() => items.value.reduce((s, it) => s + (Number(it.volume_order) || 0), 0))
const subtotal = computed(() => items.value.reduce((s, it) => s + calcJumlah(it), 0))
const totalDiskon = computed(() => Number(penawaran.value.discount) || 0)
const oatPerVol = computed(() => Number(penawaran.value.oat) || 0)
const totalOAT = computed(() => oatPerVol.value * totalVolume.value)
const dpp = computed(() => subtotal.value - totalDiskon.value + totalOAT.value)
const ppn = computed(() => Math.round(dpp.value * 0.11))
const grandTotal = computed(() => dpp.value + ppn.value)


const cogsPerVol = computed(() => Number(penawaran.value.produk_harga?.harga_cogs) || 0)
const hargaPriceList = computed(() => penawaran.value.produk_harga?.[config.hargaPriceListField])

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}

const hargaProdukFields = computed(() => {
  const fields = [
    { label: 'Periode Awal', value: formatDate(penawaran.value.produk_harga?.periode_awal) },
    { label: 'Periode Akhir', value: formatDate(penawaran.value.produk_harga?.periode_akhir) },
  ]

  if (config.showCogsRow) {
    fields.push({ label: 'Harga COGS', value: formatCurrency(penawaran.value.produk_harga?.harga_cogs) })
  }

  fields.push(
    { label: 'Harga Price List', value: formatCurrency(hargaPriceList.value) },
    { label: 'Harga BM', value: formatCurrency(penawaran.value.produk_harga?.harga_bm) },
    { label: 'Harga OM', value: formatCurrency(penawaran.value.produk_harga?.harga_om) },
  )

  return fields
})

const infoGroups = computed(() => [
  {
    label: 'Identitas',
    fields: [
      { label: 'Customer', value: penawaran.value.customer?.nama_perusahaan },
      { label: 'Cabang', value: penawaran.value.cabang?.nama_cabang },
    ],
  },
  {
    label: 'Ketentuan Transaksi',
    fields: [
      { label: 'Metode', value: penawaran.value.metode },
      { label: 'Ketentuan Order', value: penawaran.value.order_method },
      {
        label: 'Masa Berlaku',
        value: `${formatDate(penawaran.value.masa_berlaku)} - ${formatDate(penawaran.value.sampai_dengan)}`,
        span: 2,
      },
      { label: 'Tipe Pembayaran', value: penawaran.value.tipe_pembayaran },
      { label: 'Status', value: formatStatusLabel(penawaran.value.status) },
      { label: 'Lokasi Kirim', value: penawaran.value.lokasi_pengiriman },
    ],
  },
])

const hargaFields = computed(() => [
  { label: 'Harga Penawaran', value: formatCurrency(penawaran.value.harga_dasar) },
  { label: 'Refund / Volume', value: formatCurrency(penawaran.value.refund) },
  { label: 'Other Cost / Volume', value: formatCurrency(penawaran.value.other_cost) },
  { label: 'Diskon', value: `-${formatCurrency(totalDiskon.value)}`, tone: 'red' },
  { label: 'OAT / Volume', value: formatCurrency(oatPerVol.value) },
  { label: 'Toleransi Penyusutan', value: `${penawaran.value.toleransi_penyusutan || 0}%` },
  { label: 'Subtotal', value: formatCurrency(subtotal.value) },
  { label: 'Total OAT', value: formatCurrency(totalOAT.value) },
  { label: 'PPN 11%', value: formatCurrency(ppn.value) },
  { label: 'Grand Total', value: formatCurrency(grandTotal.value), tone: 'green' },
])

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


async function verifikasi() {
  const result = await Swal.fire({
    title: `Verifikasi Penawaran ${role.toUpperCase()}?`,
    input: 'textarea',
    inputLabel: 'Catatan Verifikasi',
    inputPlaceholder: 'Masukkan catatan jika ada...',
    showCancelButton: true,
    confirmButtonText: 'Ya, Disetujui',
    cancelButtonText: 'Batal',
    preConfirm: (val) => val || 'Tanpa catatan',
  })
  if (!result.isConfirmed) return

  try {
    await axios.patch(`/api${config.verifyEndpoint(id)}`, { catatan: result.value })
    success('Berhasil', 'Penawaran disetujui.')
    fetchPenawaran()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memverifikasi penawaran.')
  }
}

async function tolak() {
  const result = await Swal.fire({
    title: `Tolak Penawaran ${role.toUpperCase()}?`,
    input: 'textarea',
    inputLabel: 'Catatan Penolakan',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tolak',
    cancelButtonText: 'Batal',
  })
  if (!result.isConfirmed) return

  try {
    await axios.patch(`/api${config.rejectEndpoint(id)}`, { catatan: result.value })
    success('Ditolak', 'Penawaran ditolak.')
    fetchPenawaran()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menolak penawaran.')
  }
}

function formatDate(d?: string | null) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-'
}
function formatCurrency(v?: number | string | null) {
  return `Rp. ${(Number(v) || 0).toLocaleString('id-ID')}`
}
function formatNumber(v?: number | string | null) {
  return (Number(v) || 0).toLocaleString('id-ID')
}
function goBack() {
  router.back()
}

onMounted(fetchPenawaran)
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">{{ config.title }}</h2>
          <p class="font-lead mt-1">
            Informasi lengkap penawaran <code>{{ penawaran.nomor_penawaran || '-' }}</code>
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

          <CardSection title="Informasi Harga Produk" description="Referensi harga master yang dipakai penawaran ini"
            icon="Tag" icon-class="bg-indigo-100 text-indigo-600">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in hargaProdukFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </CardSection>

          <CardSection title="Informasi Umum" description="Data utama penawaran" icon="FileText">
            <div class="space-y-6">
              <div v-for="(group, gi) in infoGroups" :key="group.label"
                :class="gi > 0 ? 'border-t border-slate-100 pt-6' : ''">
                <h3 class="mb-4 font-section">{{ group.label }}</h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                  <div v-for="f in group.fields" :key="f.label" :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                    <dt class="font-label">{{ f.label }}</dt>
                    <dd class="font-strong mt-1 whitespace-pre-line">{{ dash(f.value) }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </CardSection>

          <CardSection title="Rincian Harga" description="Komponen harga penawaran" icon="Wallet"
            icon-class="bg-emerald-100 text-emerald-600">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in hargaFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-num mt-1"
                  :class="f.tone === 'red' ? 'text-red-600' : f.tone === 'green' ? 'text-emerald-600' : 'text-slate-800'">
                  {{ f.value }}
                </dd>
              </div>
            </dl>
          </CardSection>

          <CardSection title="Rincian Item" description="Daftar produk pada penawaran" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <DataList :loading="loading" :empty="items.length === 0" :colspan="3" :show-footer="false">
              <template #head>
                <Table.Th>Produk</Table.Th>
                <Table.Th class="w-28 text-right">Persen</Table.Th>
                <Table.Th class="w-40 text-right">Volume</Table.Th>
              </template>
              <template #body>
                <Table.Tr v-for="item in items" :key="item.id_penawaran_item" class="transition hover:bg-slate-50">
                  <Table.Td>
                    <div class="font-strong">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="font-caption mt-0.5">
                      {{ item.produk?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="font-num text-right">{{ formatNumber(item.persen) }}%</Table.Td>
                  <Table.Td class="font-num text-right">{{ formatNumber(item.volume_order) }}
                  </Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </CardSection>

          <CardSection title="Catatan & Keterangan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Keterangan</div>
                <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.keterangan || '-' }}</p>
              </div>
              <div class="space-y-3 font-body">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">Catatan</div>
                  <p v-if="penawaran.catatan" class="font-body mt-1 whitespace-pre-line">{{
                    penawaran.catatan }}</p>
                  <p v-else="penawaran.catatan" class="mt-1 font-body whitespace-pre-line !text-slate-300">-</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">Syarat & Ketentuan</div>
                  <p v-if="penawaran.syarat_ketentuan" class="font-body mt-1 whitespace-pre-line">{{
                    penawaran.syarat_ketentuan }}</p>
                  <p v-else="penawaran.catatan" class="mt-1 font-body whitespace-pre-line !text-slate-300">-</p>
                </div>
              </div>
            </div>
          </CardSection>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-20 space-y-4">
            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <Stepper :steps="approvalSteps" direction="vertical" />
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
                  <Button variant="danger" class="inline-flex w-full items-center justify-center gap-2" @click="tolak">
                    <Lucide icon="X" class="h-4 w-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2"
                    @click="verifikasi">
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
</template>
