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
const penawaranApi = createResourceApi('/penawarans-proenergi')

const id = Number(route.params.id)
const penawaran = ref<any>({})
const loading = ref(true)
const ajukanLoading = ref(false)
const ajukanDialogOpen = ref(false)

const items = computed<any[]>(() => penawaran.value.items || [])

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
    const { data } = await axios.patch(`/api/penawarans-proenergi/${id}/ajukan`)
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
    const response = await axios.get(`/api/penawarans-proenergi/${id}/preview`, {
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
  router.push({ name: 'penawarans-list-proenergi' })
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
          <h2 class="font-display">Detail Penawaran Proenergi</h2>
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

          <!-- Informasi Umum -->
          <CardSection title="Informasi Umum" description="Data utama penawaran" icon="FileText">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Customer</div>
                <div class="font-strong mt-1">{{ penawaran.customer?.nama_perusahaan || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Cabang</div>
                <div class="font-strong mt-1">{{ penawaran.cabang?.nama_cabang || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Metode Pengiriman</div>
                <div class="font-strong mt-1">{{ penawaran.metode || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Ketentuan Order</div>
                <div class="font-strong mt-1">{{ penawaran.order_method || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Tipe Pembayaran</div>
                <div class="font-strong mt-1">{{ penawaran.tipe_pembayaran || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Toleransi Penyusutan</div>
                <div class="font-strong mt-1">{{ penawaran.toleransi_penyusutan ?? 0 }}%</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2 lg:col-span-3">
                <div class="font-label">Masa Berlaku</div>
                <div class="font-strong mt-1">
                  {{ formatDate(penawaran.masa_berlaku) }} &ndash; {{ formatDate(penawaran.sampai_dengan) }}
                </div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2 lg:col-span-3">
                <div class="font-label">Lokasi Pengiriman</div>
                <div class="font-strong mt-1">{{ penawaran.lokasi_pengiriman || '-' }}</div>
              </div>
            </div>
          </CardSection>

          <!-- Rincian Harga -->
          <CardSection title="Rincian Harga" description="Komponen harga penawaran" icon="Wallet"
            icon-class="bg-emerald-100 text-emerald-600">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Harga Dasar</div>
                <div class="font-strong mt-1">{{ formatCurrency(penawaran.harga_dasar) }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Other Cost</div>
                <div class="font-strong mt-1">{{ formatCurrency(penawaran.other_cost) }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">OAT per Volume</div>
                <div class="font-strong mt-1">{{ formatCurrency(penawaran.oat) }} / volume</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Diskon</div>
                <div class="font-strong mt-1 !text-red-600">- {{ formatCurrency(penawaran.discount) }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="font-label">Refund</div>
                <div class="font-strong mt-1 !text-red-600">- {{ formatCurrency(penawaran.refund) }}</div>
              </div>
            </div>
          </CardSection>

          <!-- Rincian Item -->
          <CardSection title="Rincian Item" description="Daftar produk pada penawaran" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <DataList :loading="loading" :empty="items.length === 0" :colspan="2" :show-footer="false">
              <template #head>
                <Table.Th>Produk</Table.Th>
                <Table.Th class="text-right">Volume</Table.Th>
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
                  <Table.Td class="font-num text-right">{{ formatNumber(item.volume_order) }}</Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </CardSection>

          <!-- Catatan & Keterangan -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <CardSection title="Catatan & Keterangan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <dl class="space-y-3 font-body">
                <div>
                  <dt class="font-label">Keterangan</dt>
                  <dd class="font-strong mt-1">{{ penawaran.keterangan || '-' }}</dd>
                </div>
                <div>
                  <dt class="font-label">Catatan</dt>
                  <dd class="font-strong mt-1">{{ penawaran.catatan || '-' }}</dd>
                </div>
                <div>
                  <dt class="font-label">Syarat &amp; Ketentuan</dt>
                  <dd class="font-strong mt-1 whitespace-pre-line">{{ penawaran.syarat_ketentuan || '-' }}</dd>
                </div>
              </dl>
            </CardSection>

            <CardSection title="Catatan Verifikasi" icon="MessageSquare" icon-class="bg-blue-100 text-blue-600">
              <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">Catatan Verifikasi BM</div>
                  <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_verifikasi || '-' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-label">Catatan Verifikasi OM</div>
                  <p class="font-body mt-1 whitespace-pre-line">{{ penawaran.catatan_om || '-' }}</p>
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
              <div class="space-y-5">
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
