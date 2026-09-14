<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { createResourceApi } from '@/utils/resourceApi'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { poCustomerStatusBadgeClass } from './status'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, error: notifyError } = useNotification()
const poCustomerApi = createResourceApi('/customer-pos')

const idPoc = Number(route.params.id)

const poCustomer = ref<any>(null)
const loading = ref(true)
const processing = ref(false)

const canProcessSc = computed<boolean>(
  () => poCustomer.value?.status_key === 'awaiting_process' && auth.can('penawaran.manage'),
)

const showGatePanel = computed<boolean>(
  () => canProcessSc.value || poCustomer.value?.status_key === 'blocked',
)

const gatePanelDescription = computed<string>(() =>
  poCustomer.value?.status_key === 'blocked'
    ? 'PO ini sedang diproses oleh tim Finance untuk kelayakan kredit. Anda akan diberi tahu begitu bisa lanjut ke Sales Confirmation.'
    : 'Jalankan gerbang kredit untuk memeriksa headroom customer terhadap nilai order sebelum Sales Confirmation dibuat.',
)

const statusSteps = computed<StepItem[]>(() => {
  const po = poCustomer.value ?? {}
  const sc = po.sales_confirmation ?? null
  const key = po.status_key

  const scStatus: StepItem['status'] =
    key === 'done' ? 'completed' : key === 'sc_in_progress' ? 'active' : 'pending'

  const processStatus: StepItem['status'] =
    key === 'awaiting_process' ? 'pending' : key === 'blocked' ? 'rejected' : 'completed'

  const steps: StepItem[] = [
    {
      title: 'PO Customer Dibuat',
      status: 'completed',
      description: po.created_by || undefined,
      timestamp: po.created_time ? formatDateTime(po.created_time) : undefined,
    },
    {
      title: 'Proses SC',
      status: processStatus,
      description:
        key === 'awaiting_process' || key === 'blocked' ? po.status_label || undefined : undefined,
    },
  ]

  steps.push({
    title: 'Sales Confirmation',
    status: scStatus,
    description: sc?.disposisi_label || po.status_label || undefined,
    timestamp: sc?.lastupdate_time ? formatDateTime(sc.lastupdate_time) : undefined,
  })

  return steps
})

const penawaranItems = computed<any[]>(() => poCustomer.value?.penawaran?.items ?? [])

const totalNilaiPo = computed<number>(
  () => Number(poCustomer.value?.harga_poc ?? 0) * Number(poCustomer.value?.volume_poc ?? 0),
)

const paymentDisplay = computed<string>(() => {
  const po = poCustomer.value
  if (!po?.tipe_bayar) return '-'
  if (po.tipe_bayar === 'CREDIT' && po.termin_hari) {
    return `${po.tipe_bayar_label} — ${po.termin_hari} Hari`
  }
  return po.tipe_bayar_label ?? po.tipe_bayar
})

const attachmentIsPdf = computed<boolean>(() =>
  String(poCustomer.value?.lampiran_poc_ori ?? '').toLowerCase().endsWith('.pdf'),
)

onMounted(load)

async function load(): Promise<void> {
  loading.value = true
  try {
    const { data } = await poCustomerApi.getById(idPoc)
    poCustomer.value = data
  } catch (e: any) {
    poCustomer.value = null
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat PO Customer.')
  } finally {
    loading.value = false
  }
}

async function processSalesConfirmation(): Promise<void> {
  processing.value = true
  try {
    const { data } = await axios.post(`/api/po-customers/${idPoc}/process-sc`)
    success('Berhasil', data.status_label ?? 'Proses SC selesai.')
    await load()
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Status PO Customer sudah berubah.')
      await load()
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal memproses gerbang Sales Confirmation.')
    }
  } finally {
    processing.value = false
  }
}

function goBackToIndex(): void {
  router.push({ name: 'po-customers-index' })
}

function poVolumeForItem(it: any): number {
  if (!poCustomer.value) return 0
  return Math.round((poCustomer.value.volume_poc ?? 0) * Number(it.persen ?? 0) / 100)
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h2 class="font-display">Detail PO Customer</h2>
            <span v-if="poCustomer" class="font-label inline-flex items-center rounded-full px-3 py-1"
              :class="poCustomerStatusBadgeClass(poCustomer.status_key)">
              {{ poCustomer.status_label }}
            </span>
          </div>
          <p class="mt-1 font-lead">Informasi lengkap PO Customer dan status Sales Confirmation-nya.</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline-secondary" @click="goBackToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
        </div>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="font-body">Memuat data PO Customer...</span>
      </div>

      <div v-else-if="!poCustomer" class="flex min-h-[320px] flex-col items-center justify-center gap-2 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
          <Lucide icon="AlertTriangle" class="h-7 w-7 text-rose-500" />
        </div>
        <h3 class="font-header">PO Customer tidak ditemukan</h3>
        <p class="font-body">Silakan kembali ke halaman sebelumnya.</p>
      </div>

      <template v-else>
        <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="font-section">Total Nilai PO</div>
              <div class="mt-1 font-num-display">{{ formatCurrency(totalNilaiPo) }}</div>
              <div class="font-body text-xs">{{ formatCurrency(poCustomer.harga_poc) }} / m³</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
              <Lucide icon="Banknote" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="font-section">Total Volume</div>
              <div class="mt-1 font-num-display">{{ formatNumber(poCustomer.volume_poc) }} m³</div>
              <div class="font-body text-xs">{{ penawaranItems.length }} Variasi Ukuran</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
              <Lucide icon="Boxes" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="font-section">Term of Payment</div>
              <div class="mt-1 font-num-display">{{ paymentDisplay }}</div>
              <div class="font-body text-xs">{{ poCustomer.tipe_bayar === 'CREDIT' ? 'Sejak Invoice Diterbitkan' : 'Dibayar di muka / saat kirim' }}</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
              <Lucide icon="Clock" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="font-section">Target Pengiriman</div>
              <div class="mt-1 font-num-display">{{ formatDate(poCustomer.supply_date) }}</div>
              <div class="font-body text-xs">PO Date: {{ formatDate(poCustomer.tanggal_poc) }}</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
              <Lucide icon="Calendar" class="h-4 w-4" />
            </div>
          </div>
        </div>

        <div class="gap-6 grid grid-cols-1 xl:grid-cols-3 mt-6">
          <div class="space-y-6 xl:col-span-2">
            <CardSection title="Informasi Utama PO & Customer" description="Detail identitas pemesanan dan sumber penawaran"
              icon="FileText" icon-class="bg-primary/10 text-primary">
              <div class="gap-4 grid grid-cols-12">
                <div class="col-span-12 md:col-span-7">
                  <div class="space-y-3">
                    <div>
                      <div class="font-label">Nomor PO Customer</div>
                      <div class="mt-1 font-strong whitespace-pre-line">{{ poCustomer.nomor_poc || '-' }}</div>
                    </div>

                    <div class="gap-4 grid grid-cols-2">
                      <div>
                        <div class="font-label">Tanggal PO</div>
                        <div class="mt-1 font-strong">{{ formatDate(poCustomer.tanggal_poc) }}</div>
                      </div>
                      <div>
                        <div class="font-label">Supply Date</div>
                        <div class="mt-1 font-strong">{{ formatDate(poCustomer.supply_date) }}</div>
                      </div>
                    </div>

                    <div>
                      <div class="font-label">Term of Payment</div>
                      <div class="mt-1 font-strong">{{ paymentDisplay }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-span-12 md:col-span-5">
                  <div class="mx-2 mb-1 font-label">Customer & Penawaran</div>
                  <div class="px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="gap-4 grid grid-cols-12">
                      <div class="col-span-12">
                        <div class="font-label">Nama Perusahaan</div>
                        <div class="mt-1 font-strong">{{ poCustomer.customer?.company_name || '-' }}</div>
                      </div>
                      <div class="gap-4 grid grid-cols-2 col-span-12">
                        <div>
                          <div class="font-label">Kode Customer</div>
                          <div class="mt-1 font-strong">{{ poCustomer.customer?.customer_code || '-' }}</div>
                        </div>
                        <div>
                          <div class="font-label">Nomor Penawaran</div>
                          <div class="mt-1 font-strong">{{ poCustomer.penawaran?.nomor_penawaran || '-' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardSection>

            <CardSection title="Rincian Produk & Item PO" description="Breakdown rasio, volume, dan harga per item"
              icon="Layers" icon-class="bg-indigo-100 text-indigo-600">
              <template v-if="poCustomer.produk_poc" #action>
                <span class="bg-slate-100 px-2.5 py-1 rounded-full font-caption text-slate-600">
                  ID Produk: {{ poCustomer.produk_poc }}
                </span>
              </template>

              <div class="overflow-x-auto">
                <Table bordered sm class="font-body">
                  <Table.Thead class="bg-slate-50">
                    <Table.Tr>
                      <Table.Th class="px-4 py-3 font-label text-left">Produk</Table.Th>
                      <Table.Th class="px-4 py-3 font-label text-left">Ukuran</Table.Th>
                      <Table.Th class="px-4 py-3 font-label text-right">Rasio</Table.Th>
                      <Table.Th class="px-4 py-3 font-label text-right">Volume</Table.Th>
                    </Table.Tr>
                  </Table.Thead>

                  <Table.Tbody class="bg-white">
                    <Table.Tr v-for="(it, i) in penawaranItems" :key="it.id_penawaran_item || i">
                      <Table.Td class="px-4 py-3">
                        <div class="font-strong">{{ it.produk?.nama_produk || '-' }}</div>
                        <div v-if="it.produk?.jenis?.nama" class="font-caption">{{ it.produk?.jenis?.nama }}</div>
                      </Table.Td>
                      <Table.Td class="px-4 py-3">{{ it.produk?.ukuran?.nama_ukuran || '-' }}</Table.Td>
                      <Table.Td class="px-4 py-3 font-num text-right">{{ Math.round(Number(it.persen ?? 0)) }}%</Table.Td>
                      <Table.Td class="px-4 py-3 font-num text-right">{{ formatNumber(poVolumeForItem(it)) }} m³</Table.Td>
                    </Table.Tr>

                    <Table.Tr v-if="!penawaranItems.length">
                      <Table.Td colspan="4" class="px-4 py-6 font-body text-center">Belum ada item penawaran.</Table.Td>
                    </Table.Tr>
                  </Table.Tbody>

                  <Table.Tbody class="bg-slate-50">
                    <Table.Tr>
                      <Table.Td colspan="3" class="px-4 py-2 font-strong text-right">Total Keseluruhan</Table.Td>
                      <Table.Td class="px-4 py-2 font-num text-right">{{ formatNumber(poCustomer.volume_poc) }} m³</Table.Td>
                    </Table.Tr>
                  </Table.Tbody>
                </Table>
              </div>

              <div class="mt-4 px-4 py-3 border border-slate-200 rounded-xl space-y-2">
                <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
                  <span class="font-label">Harga per m³</span>
                  <span class="font-strong text-right">{{ formatCurrency(poCustomer.harga_poc) }}</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="font-label">Total Harga</span>
                  <span class="font-num-lg text-right">{{ formatCurrency(totalNilaiPo) }}</span>
                </div>
              </div>
            </CardSection>

            <CardSection v-if="poCustomer.lampiran_poc" title="Lampiran Dokumen PO" icon="Paperclip"
              icon-class="bg-rose-100 text-rose-600">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4 py-3 border border-slate-200 rounded-xl">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="attachmentIsPdf ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-600'">
                    <Lucide icon="FileText" class="h-5 w-5" />
                  </div>
                  <div>
                    <div class="font-strong">{{ poCustomer.lampiran_poc_ori }}</div>
                    <div class="font-caption text-slate-500">Diunggah {{ formatDate(poCustomer.created_time) }}</div>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <Button as="a" variant="outline-secondary" :href="`/storage/${poCustomer.lampiran_poc}`" target="_blank">
                    <Lucide icon="Eye" class="mr-2 h-4 w-4" />
                    Pratinjau
                  </Button>
                  <Button as="a" variant="primary" :href="`/storage/${poCustomer.lampiran_poc}`"
                    :download="poCustomer.lampiran_poc_ori">
                    <Lucide icon="Download" class="mr-2 h-4 w-4" />
                    Unduh
                  </Button>
                </div>
              </div>
            </CardSection>
          </div>

          <div class="xl:col-span-1">
            <div class="top-6 sticky space-y-4">
              <CardSection title="Status PO Customer" description="Tahapan menuju Sales Confirmation" icon="ShieldCheck"
                icon-class="bg-success/10 text-success">
                <div class="space-y-5 px-2">
                  <Stepper :steps="statusSteps" direction="vertical" />
                </div>
              </CardSection>

              <CardSection v-if="showGatePanel" title="Proses Sales Confirmation" icon="ShieldAlert"
                icon-class="bg-amber-100 text-amber-600">
                <div class="space-y-4 px-2">
                  <p class="font-body text-slate-600">
                    {{ gatePanelDescription }}
                  </p>

                  <Button v-if="canProcessSc" variant="primary" :disabled="processing"
                    @click="processSalesConfirmation">
                    <Lucide icon="ShieldCheck" class="mr-2 h-4 w-4" />
                    Proses SC
                  </Button>
                </div>
              </CardSection>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
