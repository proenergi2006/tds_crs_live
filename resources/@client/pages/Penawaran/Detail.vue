<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatDate, formatDateTime } from '@/utils/format'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()

/* Brand: detail ini dipakai untuk TDS dan Proenergi. Brand dibaca dari route.meta. */
type Brand = 'tds' | 'proenergi'
const brand: Brand = (route.meta.brand as Brand) === 'proenergi' ? 'proenergi' : 'tds'
const isProenergi = brand === 'proenergi'

const BRAND_CONFIG = {
  tds: {
    resourceEndpoint: '/penawarans',
    apiBase: '/api/penawarans',
    listRoute: 'penawarans-list',
    title: 'Detail Penawaran',
  },
  proenergi: {
    resourceEndpoint: '/penawarans-proenergi',
    apiBase: '/api/penawarans-proenergi',
    listRoute: 'penawarans-list-proenergi',
    title: 'Detail Penawaran Proenergi',
  },
}
const cfg = BRAND_CONFIG[brand]

const penawaranApi = createResourceApi(cfg.resourceEndpoint)

const id = Number(route.params.id)
const penawaran = ref<any>({})
const loading = ref(true)
const ajukanLoading = ref(false)
const ajukanDialogOpen = ref(false)

/* Hardcode false — wiring ke role asli di luar scope, keputusan terpisah */
const canSeeHarga = ref(false)

const items = computed<any[]>(() => penawaran.value.items || [])

const dash = (v: any) => (v === null || v === undefined || v === '' ? '-' : v)

/* Section: Informasi Penawaran */
const infoPenawaranFields = computed(() => {
  const p = penawaran.value
  return [
    { label: 'Customer', value: p.customer?.nama_perusahaan },
    { label: 'Cabang', value: p.cabang?.nama_cabang },
    { label: 'Nomor Penawaran', value: p.nomor_penawaran },
    { label: 'Kepada', value: p.kepada },
    { label: 'Nama (UP.)', value: p.nama },
    { label: 'Jabatan', value: p.jabatan },
    { label: 'Telepon', value: p.telepon },
    { label: 'Alamat', value: p.alamat, span: 2 },
  ]
})

/* Section: Detail Pengiriman */
const deliveryFields = computed(() => {
  const p = penawaran.value
  return [
    { label: 'Type Pengiriman', value: p.type_pengiriman },
    { label: 'Metode Pengiriman', value: p.metode },
    { label: 'Lokasi Pengiriman', value: p.lokasi_pengiriman, span: 2 },
    { label: 'Titik Serah Terima & T&C Bongkar', value: p.keterangan, span: 2 },
  ]
})

/* Section: Pembayaran & Lainnya */
const paymentFields = computed(() => {
  const p = penawaran.value
  return [
    { label: 'Tipe Pembayaran', value: p.tipe_pembayaran },
    ...(isProenergi ? [{ label: 'Acuan Pembayaran', value: p.acuan_pembayaran }] : []),
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

onMounted(fetchPenawaran)

async function fetchPenawaran() {
  loading.value = true
  try {
    const { data } = await penawaranApi.getById(id)
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
    const { data } = await axios.patch(`${cfg.apiBase}/${id}/ajukan`)
    ajukanDialogOpen.value = false
    success('Berhasil Diajukan', data.message || 'Penawaran berhasil diajukan ke Branch Manager.')
    await fetchPenawaran()
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal mengajukan penawaran')
  } finally {
    ajukanLoading.value = false
  }
}

async function preview(lang?: 'id' | 'en') {
  try {
    const response = await axios.get(`${cfg.apiBase}/${id}/preview`, {
      params: lang ? { lang } : {},
      responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch {
    error('Gagal', 'Gagal membuka preview PDF')
  }
}

function goBack() {
  router.push({ name: cfg.listRoute })
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
          <p class="font-lead mt-1">
            Informasi lengkap penawaran <code>{{ penawaran.nomor_penawaran || '-' }}</code>
          </p>
        </div>
        <div>
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
          <CardSection title="Informasi Penawaran" description="Identitas dokumen dan kontak tujuan" icon="FileText"
            :collapsible="true">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in infoPenawaranFields" :key="f.label"
                :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1 whitespace-pre-line">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </CardSection>

          <!-- Section 2: Rincian Item -->
          <CardSection title="Rincian Item" description="Daftar produk dan periode harga penawaran" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600" :collapsible="true">

            <!-- Periode Harga -->
            <dl class="mb-5 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
              <div>
                <dt class="font-label">Periode Harga</dt>
                <dd class="font-strong mt-1">
                  {{
                    penawaran.masa_berlaku
                      ? `${formatDate(penawaran.masa_berlaku)} – ${formatDate(penawaran.sampai_dengan)}`
                      : '-'
                  }}
                </dd>
              </div>
            </dl>

            <Table bordered sm class="font-body">
              <Table.Thead class="bg-slate-50">
                <Table.Th>Produk</Table.Th>
                <Table.Th class="w-28 text-right">Persen</Table.Th>
                <Table.Th class="w-40 text-right">Volume</Table.Th>
                <Table.Th v-if="canSeeHarga" class="w-40 text-right">Harga Tebus</Table.Th>
                <Table.Th v-if="canSeeHarga" class="w-44 text-right">Jumlah Harga</Table.Th>
              </Table.Thead>
              <Table.Tbody class="bg-white">
                <Table.Tr v-for="item in items" :key="item.id_penawaran_item" class="transition hover:bg-slate-50">
                  <Table.Td>
                    <div class="font-strong">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="font-caption mt-0.5">
                      {{ item.produk?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="font-num-lg text-xl text-right">{{ formatNumber(item.persen) }}%</Table.Td>
                  <Table.Td class="font-num-lg text-xl text-right">{{ formatNumber(item.volume_order) }}</Table.Td>
                  <Table.Td v-if="canSeeHarga" class="font-num-lg text-xl text-right">
                    {{ formatCurrency(item.harga_tebus) }}
                  </Table.Td>
                  <Table.Td v-if="canSeeHarga" class="font-num-lg text-xl text-right">
                    {{ formatCurrency(item.jumlah_harga) }}
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>

              <Table.Tbody v-if="canSeeHarga" class="border-t border-slate-200 bg-slate-50">
                <Table.Tr>
                  <Table.Td :colspan="4" class="py-2.5 pr-6 text-right font-header">Subtotal Harga Tebus</Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatCurrency(subtotal) }}</Table.Td>
                </Table.Tr>
                <Table.Tr v-if="totalDiskon > 0" class="bg-yellow-50">
                  <Table.Td :colspan="4" class="py-2.5 pr-6 text-right font-header !text-yellow-700">Diskon</Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right !text-yellow-800">
                    - {{ formatCurrency(totalDiskon) }}
                  </Table.Td>
                </Table.Tr>
                <Table.Tr class="bg-emerald-50">
                  <Table.Td :colspan="4" class="py-2.5 pr-6 text-right font-header !text-emerald-700">Setelah Diskon
                  </Table.Td>
                  <Table.Td class="py-2.5 font-num-lg text-xl text-right !text-emerald-800">
                    {{ formatCurrency(grandTotalHargaTebusSetelahDiskon) }}
                  </Table.Td>
                </Table.Tr>
              </Table.Tbody>
            </Table>
          </CardSection>

          <!-- Section 3: Detail Pengiriman -->
          <CardSection title="Detail Pengiriman" description="Metode dan lokasi tujuan pengiriman" icon="Truck"
            icon-class="bg-violet-100 text-violet-600" :collapsible="true">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in deliveryFields" :key="f.label" :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-strong mt-1 whitespace-pre-line">{{ dash(f.value) }}</dd>
              </div>
            </dl>
          </CardSection>

          <!-- Section 4: Pembayaran & Lainnya -->
          <CardSection title="Pembayaran & Lainnya" description="Ketentuan pembayaran dan toleransi" icon="Wallet"
            icon-class="bg-amber-100 text-amber-600" :collapsible="true">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in paymentFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-num-lg text-xl mt-1"
                  :class="(f as any).tone === 'red' ? '!text-red-600' : '!text-slate-800'">
                  {{ dash(f.value) }}
                </dd>
              </div>
            </dl>
          </CardSection>

          <!-- Section 5: Perhitungan Harga Dasar -->
          <CardSection title="Perhitungan Harga Dasar" description="Komponen harga dasar dan estimasi PPN"
            icon="Calculator" icon-class="bg-emerald-100 text-emerald-600" :collapsible="true">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div>
                <dt class="font-label">Harga Dasar</dt>
                <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(penawaran.harga_dasar) }}</dd>
              </div>
              <div>
                <dt class="font-label">OAT per Volume</dt>
                <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(penawaran.oat) }} / volume</dd>
              </div>
              <div>
                <dt class="font-label">Subtotal (DPP)</dt>
                <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(dppHargaDasar) }}</dd>
              </div>
              <div>
                <dt class="font-label">PPN 11%</dt>
                <dd class="font-num-lg text-xl mt-1 !text-slate-800">{{ formatCurrency(ppnHargaDasar) }}</dd>
              </div>
              <div>
                <dt class="font-label">TOTAL</dt>
                <dd class="font-num-lg text-xl mt-1 text-xl !text-emerald-700">{{ formatCurrency(grandTotalHargaDasar)
                  }}</dd>
              </div>
            </dl>
          </CardSection>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-6 space-y-4">

            <!-- Status & Aksi -->
            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <Stepper :steps="approvalSteps" direction="vertical" />

                <p v-if="penawaran.status === 'draft'"
                  class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 font-caption">
                  Pastikan seluruh data penawaran sudah benar sebelum diajukan ke Branch Manager.
                </p>

                <div class="flex flex-col gap-2">
                  <Button variant="outline-primary" class="inline-flex w-full items-center justify-center gap-2"
                    @click="preview()">
                    <Lucide icon="Printer" class="h-4 w-4" />
                    Preview PDF
                  </Button>
                  <Button v-if="penawaran.status === 'approved_om'" variant="outline-primary"
                    class="inline-flex w-full items-center justify-center gap-2" @click="preview('id')">
                    <Lucide icon="FileText" class="h-4 w-4" />
                    Cetak Indonesia
                  </Button>
                  <Button v-if="penawaran.status === 'approved_om'" variant="outline-primary"
                    class="inline-flex w-full items-center justify-center gap-2" @click="preview('en')">
                    <Lucide icon="FileText" class="h-4 w-4" />
                    Cetak English
                  </Button>
                  <Button v-if="penawaran.status === 'draft'" variant="primary"
                    class="inline-flex w-full items-center justify-center gap-2" @click="ajukanDialogOpen = true">
                    <Lucide icon="Send" class="h-4 w-4" />
                    Ajukan ke Branch Manager
                  </Button>
                </div>
              </div>
            </CardSection>

            <!-- Catatan & Syarat -->
            <CardSection title="Catatan & Syarat" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <div class="space-y-4">
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

            <!-- Catatan Verifikasi BM / OM -->
            <section class="p-6 rounded-lg bg-white shadow-sm space-y-3">
              <div v-if="!penawaran.catatan_verifikasi && !penawaran.catatan_om"
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Catatan Verifikasi</div>
                <i class="font-body text-xs mt-1 whitespace-pre-line">Tidak ada catatan verifikasi untuk penawaran
                  ini.</i>
              </div>
              <div v-if="penawaran.catatan_verifikasi" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Catatan Verifikasi BM</div>
                <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_verifikasi || '-' }}</p>
              </div>
              <div v-if="penawaran.catatan_om" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-label">Catatan Verifikasi OM</div>
                <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_om || '-' }}</p>
              </div>
            </section>

          </div>
        </div>

      </div>
    </div>
  </div>

  <ConfirmDialog :open="ajukanDialogOpen" title="Ajukan Penawaran?"
    description="Setelah diajukan, penawaran akan dikirim ke Branch Manager untuk verifikasi. Pastikan seluruh data sudah benar."
    confirm-text="Ya, Ajukan" icon="Send" icon-class="bg-primary/10 text-primary" variant="primary"
    :loading="ajukanLoading" @close="ajukanDialogOpen = false" @confirm="ajukanPenawaran" />
</template>
