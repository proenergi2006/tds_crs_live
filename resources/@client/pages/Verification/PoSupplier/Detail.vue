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
import { createResourceApi } from '@/utils/resourceApi.js'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const vendorPoApi = createResourceApi('/vendor-pos')

const id = Number(route.params.id)
const po = ref<any>({})
const loading = ref(true)
const approveLoading = ref(false)
const rejectLoading = ref(false)
const approveDialogOpen = ref(false)
const rejectDialogOpen = ref(false)

const produks = computed<any[]>(() => po.value.produks || [])

const approvalSteps = computed<StepItem[]>(() => {
  const d: number = po.value.disposisi_po ?? -1

  function s(completedWhen: boolean, activeWhen: boolean): StepItem['status'] {
    if (completedWhen) return 'completed'
    if (activeWhen) return 'active'
    return 'pending'
  }

  return [
    {
      title: 'Drafting',
      status: s(d >= 1, d === 0),
    },
    {
      title: 'Verifikasi CFO',
      status: s(d >= 2, d === 1),
    },
    {
      title: 'Verifikasi CEO',
      status: s(d === 4, d === 2),
    },
    {
      title: 'Disetujui',
      status: s(d === 4, false),
    },
  ]
})

onMounted(fetchPo)

async function fetchPo() {
  loading.value = true
  try {
    const { data } = await vendorPoApi.getById(id)
    po.value = data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat data PO')
  } finally {
    loading.value = false
  }
}

async function handleApprove() {
  approveLoading.value = true
  try {
    await axios.post(`/api/po-verification/${id}`, { action: 'approve' })
    success('Berhasil', 'PO berhasil disetujui')
    router.push({ name: 'po-verification-list' })
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal menyetujui PO')
  } finally {
    approveLoading.value = false
  }
}

async function handleReject() {
  rejectLoading.value = true
  try {
    await axios.post(`/api/po-verification/${id}`, { action: 'reject' })
    success('Berhasil', 'PO berhasil ditolak')
    router.push({ name: 'po-verification-list' })
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal menolak PO')
  } finally {
    rejectLoading.value = false
  }
}

async function preview() {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    error('Gagal', 'Gagal membuka preview PDF')
  }
}

function goBack() {
  router.push({ name: 'po-verification-list' })
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}

function formatNumber(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return !isNaN(n) ? n.toLocaleString('id-ID') : '-'
}

</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-slate-800">Detail Verifikasi PO</h2>
          <p class="mt-1 text-sm text-slate-500">
            Informasi lengkap Purchase Order <code>{{ po.nomor_po }}</code>
          </p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <!-- 2-COLUMN LAYOUT -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- KIRI: Konten utama -->
        <div class="space-y-6 xl:col-span-2">

          <!-- Informasi PO -->
          <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Nomor PO</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.nomor_po || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal PO</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ formatDate(po.tanggal_inven) }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Vendor</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.vendor?.nama_vendor || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Terminal</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.terminal?.nama_terminal || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kode Tax</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.kd_tax || '-' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Terms</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">
                  {{ po.terms || '-' }}
                  <span class="text-slate-400">&nbsp;·&nbsp;{{ po.terms_day || 0 }} hari</span>
                </div>
              </div>
            </div>
          </CardSection>

          <!-- Rincian Produk -->
          <CardSection title="Rincian Produk" description="Daftar item produk pada purchase order" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <DataList :loading="loading" :empty="produks.length === 0" :colspan="4" :show-footer="false">
              <template #head>
                <Table.Th>Produk</Table.Th>
                <Table.Th class="text-right">Volume PO</Table.Th>
                <Table.Th class="text-right">Harga Tebus</Table.Th>
                <Table.Th class="text-right">Jumlah Harga</Table.Th>
              </template>
              <template #body>
                <Table.Tr v-for="item in produks" :key="item.id_po_produk" class="transition hover:bg-slate-50">
                  <Table.Td>
                    <div class="font-medium text-slate-800">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="mt-0.5 text-xs text-slate-400">
                      {{ item.produk?.jenis?.nama || '-' }}
                      <span class="mx-1">·</span>
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.volume_po) }}</Table.Td>
                  <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.harga_tebus) }}
                  </Table.Td>
                  <Table.Td class="text-right font-semibold text-slate-800">{{ formatNumber(item.jumlah_harga) }}
                  </Table.Td>
                </Table.Tr>

                <Table.Tr>
                  <Table.Td :colspan="3" class="py-2.5 pr-6 text-right text-sm text-slate-500">Subtotal</Table.Td>
                  <Table.Td class="py-2.5 text-right text-sm font-medium text-slate-700">{{ formatNumber(po.subtotal) }}
                  </Table.Td>
                </Table.Tr>
                <Table.Tr>
                  <Table.Td :colspan="3" class="py-2.5 pr-6 text-right text-sm text-slate-500">PPN 11%</Table.Td>
                  <Table.Td class="py-2.5 text-right text-sm font-medium text-slate-700">{{ formatNumber(po.ppn11) }}
                  </Table.Td>
                </Table.Tr>
                <Table.Tr class="bg-emerald-50">
                  <Table.Td :colspan="3" class="py-3.5 pr-6 text-right text-sm font-semibold text-slate-800">Total Order
                  </Table.Td>
                  <Table.Td class="py-3.5 text-right text-base font-bold text-emerald-700">{{
                    formatNumber(po.total_order) }}</Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </CardSection>

          <!-- Catatan & Terms -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <CardSection title="Catatan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <div
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line min-h-[5rem]">
                {{ po.keterangan || '-' }}
              </div>
            </CardSection>

            <CardSection title="Terms & Condition" icon="ScrollText" icon-class="bg-blue-100 text-blue-600">
              <div
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line min-h-[5rem]">
                {{ po.terms_condition || '-' }}
              </div>
            </CardSection>
          </div>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-20 space-y-4">
            <CardSection title="Status Approval" description="Tahapan persetujuan PO" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <div class="space-y-5">
                <Stepper :steps="approvalSteps" direction="vertical" />

                <p class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                  Tinjau seluruh data PO sebelum memberikan keputusan verifikasi.
                </p>

                <div class="flex flex-col gap-2">
                  <Button variant="outline-primary" class="w-full inline-flex items-center justify-center gap-2"
                    @click="preview">
                    <Lucide icon="Printer" class="h-4 w-4" />
                    Preview PDF
                  </Button>
                  <Button variant="danger" class="inline-flex items-center justify-center gap-2 w-full"
                    @click="rejectDialogOpen = true">
                    <Lucide icon="X" class="h-4 w-4" />
                    Tolak
                  </Button>
                  <Button variant="success" class="inline-flex items-center justify-center gap-2 w-full"
                    @click="approveDialogOpen = true">
                    <Lucide icon="Check" class="h-4 w-4" />
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

  <ConfirmDialog :open="approveDialogOpen" title="Setujui PO?"
    description="PO akan disetujui dan diteruskan ke tahap berikutnya. Pastikan seluruh data sudah benar."
    confirm-text="Ya, Setujui" icon="CheckCircle" icon-class="bg-success/10 text-success" variant="success"
    :loading="approveLoading" @close="approveDialogOpen = false" @confirm="handleApprove" />

  <ConfirmDialog :open="rejectDialogOpen" title="Tolak PO?"
    description="PO akan ditolak dan dikembalikan ke status draft. Tindakan ini tidak dapat dibatalkan."
    confirm-text="Ya, Tolak" icon="XCircle" icon-class="bg-danger/10 text-danger" variant="danger"
    :loading="rejectLoading" @close="rejectDialogOpen = false" @confirm="handleReject" />
</template>
