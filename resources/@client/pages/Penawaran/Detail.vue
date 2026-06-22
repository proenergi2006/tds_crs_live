<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatDate, formatDateTime } from '@/utils/format'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const penawaranApi = createResourceApi('/penawarans')

const id = Number(route.params.id)
const penawaran = ref<any>({})
const loading = ref(true)
const ajukanLoading = ref(false)
const ajukanDialogOpen = ref(false)

const items = computed<any[]>(() => penawaran.value.items || [])

const dash = (v: any) => (v === null || v === undefined || v === '' ? '-' : v)

/* Informasi umum dikelompokkan agar bisa dirender sebagai definition-list datar */
const infoGroups = computed(() => {
  const p = penawaran.value
  return [
    {
      label: 'Identitas & Pengiriman',
      fields: [
        { label: 'Customer', value: p.customer?.nama_perusahaan },
        { label: 'Cabang', value: p.cabang?.nama_cabang },
        { label: 'Nomor Penawaran', value: p.nomor_penawaran },
        { label: 'Type Pengiriman', value: p.type_pengiriman },
        { label: 'Metode Pengiriman', value: p.metode },
        {
          label: 'Masa Berlaku',
          value: p.masa_berlaku ? `${formatDate(p.masa_berlaku)} – ${formatDate(p.sampai_dengan)}` : null,
        },
        { label: 'Lokasi Pengiriman', value: p.lokasi_pengiriman },
      ],
    },
    {
      label: 'Kontak Tujuan',
      fields: [
        { label: 'Kepada', value: p.kepada },
        { label: 'Nama (UP.)', value: p.nama },
        { label: 'Jabatan', value: p.jabatan },
        { label: 'Telepon', value: p.telepon },
        { label: 'Alamat', value: p.alamat, span: 2 },
      ],
    },
    {
      label: 'Pembayaran & Ketentuan',
      fields: [
        { label: 'Tipe Pembayaran', value: p.tipe_pembayaran },
        { label: 'Ketentuan Order', value: p.order_method },
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
      ],
    },
  ]
})

/* Komponen harga sebagai daftar datar */
const hargaFields = computed(() => {
  const p = penawaran.value
  return [
    { label: 'Harga Dasar', value: formatCurrency(p.harga_dasar) },
    { label: 'Other Cost', value: formatCurrency(p.other_cost) },
    { label: 'OAT per Volume', value: `${formatCurrency(p.oat)} / volume` },
    { label: 'Diskon', value: `- ${formatCurrency(p.discount)}`, tone: 'red' },
    { label: 'Refund', value: `- ${formatCurrency(p.refund)}`, tone: 'red' },
  ]
})

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
    const { data } = await axios.patch(`/api/penawarans/${id}/ajukan`)
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
    const response = await axios.get(`/api/penawarans/${id}/preview`, {
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
  router.push({ name: 'penawarans-list' })
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
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-slate-800">Detail Penawaran</h2>
          <p class="mt-1 text-sm text-slate-500">
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

          <!-- Informasi Umum -->
          <CardSection title="Informasi Umum" description="Data utama penawaran" icon="FileText">
            <div class="space-y-6">
              <div v-for="(group, gi) in infoGroups" :key="group.label"
                :class="gi > 0 ? 'border-t border-slate-100 pt-6' : ''">
                <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ group.label }}</h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                  <div v-for="f in group.fields" :key="f.label"
                    :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ f.label }}</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm font-semibold text-slate-800">{{ dash(f.value) }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </CardSection>

          <!-- Rincian Harga -->
          <CardSection title="Rincian Harga" description="Komponen harga penawaran" icon="Wallet"
            icon-class="bg-emerald-100 text-emerald-600">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="f in hargaFields" :key="f.label">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ f.label }}</dt>
                <dd class="mt-1 text-sm font-semibold" :class="f.tone === 'red' ? 'text-red-600' : 'text-slate-800'">
                  {{ f.value }}
                </dd>
              </div>
            </dl>
          </CardSection>

          <!-- Rincian Item -->
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
                    <div class="font-medium text-slate-800">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="mt-0.5 text-xs text-slate-400">
                      {{ item.produk?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.persen) }}%</Table.Td>
                  <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.volume_order) }}
                  </Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </CardSection>

          <!-- Catatan & Keterangan -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <CardSection title="Catatan & Keterangan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <dl class="space-y-3 text-sm">
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Keterangan</dt>
                  <dd class="mt-1 font-medium text-slate-700">{{ penawaran.keterangan || '-' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Catatan</dt>
                  <dd class="mt-1 font-medium text-slate-700">{{ penawaran.catatan || '-' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Syarat &amp; Ketentuan</dt>
                  <dd class="mt-1 whitespace-pre-line font-medium text-slate-700">{{ penawaran.syarat_ketentuan || '-'
                    }}</dd>
                </div>
              </dl>
            </CardSection>

            <CardSection title="Catatan Verifikasi" icon="MessageSquare" icon-class="bg-blue-100 text-blue-600">
              <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Catatan Verifikasi BM</div>
                  <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ penawaran.catatan_verifikasi || '-' }}
                  </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Catatan Verifikasi OM</div>
                  <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ penawaran.catatan_om || '-' }}</p>
                </div>
              </div>
            </CardSection>
          </div>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-20 space-y-4">
            <CardSection title="Status Penawaran" description="Tahapan persetujuan penawaran" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5 px-2">
                <Stepper :steps="approvalSteps" direction="vertical" />

                <p v-if="penawaran.status === 'draft'"
                  class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500">
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
