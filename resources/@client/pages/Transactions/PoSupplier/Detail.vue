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
import { createResourceApi } from '@/utils/resourceApi.js'
import { formatCurrency, formatDate, formatDateTime } from '@/utils/format'
import { openPdfLoadingTab } from '@/utils/pdfPreviewTab'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const vendorPoApi = createResourceApi('/vendor-pos')

const id = Number(route.params.id)
const po = ref<any>({})
const loading = ref(true)
const approving = ref(false)
const approveDialogOpen = ref(false)

const produks = computed<any[]>(() => po.value.produks || [])
const isWaitingOrApproved = computed(() => {
  const key = po.value.status_po?.key
  return key === 'WaitingCeo' || key === 'Approved'
})
const isEditableState = computed(() => !isWaitingOrApproved.value)
const isApprovalDisabled = isWaitingOrApproved

const approvalSteps = computed<StepItem[]>(() => {
  const key = po.value.status_po?.key ?? 'Draft'

  function s(completedWhen: boolean, activeWhen: boolean): StepItem['status'] {
    if (completedWhen) return 'completed'
    if (activeWhen) return 'active'
    return 'pending'
  }

  const waitingOrApproved = key === 'WaitingCeo' || key === 'Approved'

  return [
    {
      title: 'Drafting',
      description: 'Inisiasi dokumen PO kepada vendor',
      status: s(waitingOrApproved, !waitingOrApproved),
    },
    {
      title: 'PO Diajukan',
      description: 'PO diteruskan ke proses verifikasi CEO',
      status: s(waitingOrApproved, false),
      timestamp: formatDateTime(po.value.cfo_tgl),
    },
    {
      title: 'Verifikasi CEO',
      description: 'Menunggu keputusan persetujuan CEO',
      status: s(key === 'Approved', key === 'WaitingCeo'),
      timestamp: formatDateTime(po.value.ceo_tgl),
    },
    {
      title: 'Disetujui',
      description: 'Dokumen PO disetujui untuk lanjut ke flow berikutnya',
      status: s(key === 'Approved', false),
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

async function approve() {
  approving.value = true
  try {
    const { data } = await axios.patch(`/api/vendor-pos/${id}/approve`)
    po.value = data
    success('Berhasil', 'PO berhasil dikirim untuk persetujuan')
    approveDialogOpen.value = false
    router.push({ name: 'vendor-pos-list' })
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal mengirim persetujuan')
  } finally {
    approving.value = false
  }
}

async function preview() {
  const previewTab = openPdfLoadingTab()
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (previewTab) {
      previewTab.location.href = url
    } else {
      window.open(url, '_blank')
    }
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    previewTab?.close()
    error('Gagal', 'Gagal membuka preview PDF')
  }
}

function goBack() {
  router.push({ name: 'vendor-pos-list' })
}

function goToEdit() {
  router.push({ name: 'vendor-pos-edit', params: { id } });
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
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="font-display">Detail PO Supplier</h2>
          <p class="font-lead mt-1">
            Informasi lengkap Purchase Order.
          </p>
        </div>
        <div>
          <Button variant="outline-secondary" @click="goBack">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
          <Button v-if="isEditableState" class="ml-2" variant="soft-pending" @click="goToEdit">
            <Lucide icon="Edit" class="mr-2 h-4 w-4" />
            Edit
          </Button>
        </div>
      </div>

      <!-- 2-COLUMN LAYOUT (mirip FormPage sidebar) -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- KIRI: Konten utama -->
        <div class="space-y-6 xl:col-span-2">

          <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                <div>
                  <div class="font-label">Nomor PO</div>
                  <div class="font-strong mt-1">{{ po.nomor_po || '-' }}</div>
                </div>
                <div>
                  <div class="font-label">Tanggal PO</div>
                  <div class="font-strong mt-1">{{ formatDate(po.tanggal_inven) }}</div>
                </div>
                <div>
                  <div class="font-label">Terms</div>
                  <div class="font-strong mt-1">
                    {{ po.terms || '-' }}
                    <span class="text-slate-400">&nbsp;·&nbsp;{{ po.terms_day || 0 }} hari</span>
                  </div>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                <div>
                  <div class="font-label">Vendor</div>
                  <div class="font-strong mt-1">{{ po.vendor?.nama_vendor || '-' }}</div>
                </div>
                <div>
                  <div class="font-label">Terminal</div>
                  <div class="font-strong mt-1">{{ po.terminal?.nama_terminal || '-' }}</div>
                </div>
              </div>
            </div>
          </CardSection>

          <CardSection title="Rincian Produk" description="Daftar item produk pada purchase order" icon="Boxes"
            icon-class="bg-indigo-100 text-indigo-600">
            <div class="overflow-x-auto">
              <Table bordered sm class="font-body">
                <Table.Thead class="bg-slate-50">
                  <Table.Th class="font-label">Produk</Table.Th>
                  <Table.Th class="font-label text-right">Volume PO</Table.Th>
                  <Table.Th class="font-label text-right">Harga Tebus</Table.Th>
                  <Table.Th class="font-label text-right">Jumlah Harga</Table.Th>
                  <Table.Th class="font-label text-center">Kode Tax</Table.Th>
                  <Table.Th class="font-label text-right">Tax Amount</Table.Th>
                </Table.Thead>

                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="item in produks" :key="item.id_po_produk">
                    <Table.Td>
                      <div class="font-strong">{{ item.produk?.nama_produk || '-' }}</div>
                      <div class="font-caption mt-0.5">
                        {{ item.produk?.jenis?.nama || '-' }}
                        <span class="mx-1">·</span>
                        {{ item.produk?.ukuran?.nama_ukuran || '-' }} {{ item.produk?.ukuran?.satuan?.nama_satuan || ''
                        }}
                      </div>
                    </Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatNumber(item.volume_po) }}</Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatCurrency(item.harga_tebus) }}</Table.Td>
                    <Table.Td class="font-num text-lg text-right">{{ formatCurrency(item.jumlah_harga) }}</Table.Td>
                    <Table.Td class="text-center font-strong">{{ item.kd_tax ?? '-' }}</Table.Td>
                    <Table.Td class="font-num text-lg text-right">
                      {{ item.tax_amount ? formatCurrency(item.tax_amount) : '-' }}
                    </Table.Td>
                  </Table.Tr>

                  <Table.Tr>
                    <Table.Td :colspan="3" class="py-2.5 pr-6 text-right font-header">Subtotal</Table.Td>
                    <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatCurrency(po.subtotal) }}
                    </Table.Td>
                    <Table.Td :colspan="2"></Table.Td>
                  </Table.Tr>
                  <Table.Tr>
                    <Table.Td :colspan="3" class="py-2.5 pr-6 text-right font-header">Total Tax</Table.Td>
                    <Table.Td class="py-2.5 font-num-lg text-xl text-right">{{ formatCurrency(po.ppn11) }}
                    </Table.Td>
                    <Table.Td :colspan="2"></Table.Td>
                  </Table.Tr>
                  <Table.Tr>
                    <Table.Td :colspan="3" class="py-3.5 pr-6 font-header text-right">Total Order</Table.Td>
                    <Table.Td class="py-3.5 font-num-lg text-xl text-right !text-emerald-700">
                      {{ formatCurrency(po.total_order) }}
                    </Table.Td>
                    <Table.Td :colspan="2"></Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </table>
            </div>
          </CardSection>

          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <CardSection title="Catatan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
              <div
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-body whitespace-pre-line min-h-[5rem]">
                {{ po.keterangan || '-' }}
              </div>
            </CardSection>

            <CardSection title="Terms & Condition" icon="ScrollText" icon-class="bg-blue-100 text-blue-600">
              <div
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-body whitespace-pre-line min-h-[5rem]">
                {{ po.terms_condition || '-' }}
              </div>
            </CardSection>
          </div>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-6 space-y-4">
            <CardSection title="Status Approval" description="Tahapan persetujuan PO" icon="ShieldCheck"
              icon-class="bg-success/10 text-success">
              <Stepper :steps="approvalSteps" direction="vertical" />
            </CardSection>

            <div class="bg-white p-6 rounded-lg">
              <div
                class="mb-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-body whitespace-pre-line min-h-[5rem]">
                <div class="font-label mb-3">APPROVAL NOTES</div>
                <div class="space-y-3">
                  <div v-if="po.cfo_result === 2" class="rounded-xl border p-3 space-y-1"
                    :class="'border-danger/30 bg-danger/5'">
                    <div class="flex items-center justify-between">
                      <span class="font-strong">CFO</span>
                      <span class="text-xs font-label px-2 py-0.5 rounded-full"
                        :class="po.cfo_result === 2 ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'">
                        {{ po.cfo_result === 2 ? 'Ditolak' : 'Disetujui' }}
                      </span>
                    </div>
                    <div class="flex items-start justify-between">
                      <div class="font-body text-xs text-slate-600">{{ po.cfo_summary ?? '-' }}</div>
                      <div class="font-caption text-slate-400 min-w-36 text-right">{{ formatDate(po.cfo_tgl) }}</div>
                    </div>
                  </div>

                  <div v-if="po.ceo_result !== null && po.ceo_result !== undefined"
                    class="rounded-xl border p-3 space-y-1"
                    :class="po.ceo_result === 2 ? 'border-danger/30 bg-danger/5' : 'border-success/30 bg-success/5'">
                    <div class="flex items-center justify-between">
                      <span class="font-strong">CEO</span>
                      <span class="text-xs font-label px-2 py-0.5 rounded-full"
                        :class="po.ceo_result === 2 ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'">
                        {{ po.ceo_result === 2 ? 'Ditolak' : 'Disetujui' }}
                      </span>
                    </div>
                    <div class="flex items-start justify-between">
                      <div class="font-body text-xs text-slate-600">{{ po.ceo_summary ?? '-' }}</div>
                      <div class="font-caption text-slate-400 min-w-36 text-right">{{ formatDate(po.ceo_tgl) }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-col gap-2">
                <Button variant="outline-primary" class="w-full inline-flex items-center justify-center gap-2"
                  @click="preview">
                  <Lucide icon="Printer" class="h-4 w-4" />
                  Preview PDF
                </Button>
                <Button :disabled="isApprovalDisabled" variant="primary"
                  class="w-full inline-flex items-center justify-center gap-2" @click="approveDialogOpen = true">
                  <Lucide icon="Send" class="h-4 w-4" />
                  Kirim Persetujuan
                </Button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <ConfirmDialog :open="approveDialogOpen" title="Kirim untuk Persetujuan?"
    description="PO akan diteruskan ke proses approval. Pastikan seluruh data sudah benar." confirm-text="Ya, kirim"
    icon="Send" icon-class="bg-primary/10 text-primary" variant="primary" :loading="approving"
    @close="approveDialogOpen = false" @confirm="approve" />
</template>
