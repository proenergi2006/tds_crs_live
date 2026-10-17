<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'
import { actionErrorMessage, redirectToErrorPage } from '@/utils/http-error-route'
import type { ApiResponse, PoCustomerDetail } from '@/types'
import PoCustomerItemsTable from './components/PoCustomerItemsTable.vue'
import { poCustomerStatusBadgeClass } from './status'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()
const poCustomerApi = createResourceApi('/customer-pos')

const idPoc = Number(route.params.id)

const poCustomer = ref<PoCustomerDetail | null>(null)
const loading = ref(true)
const processing = ref(false)

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
}

const canEditPo = computed<boolean>(() => !!poCustomer.value?.can_update && !poCustomer.value.is_locked)

const canProcessSc = computed<boolean>(
  () => poCustomer.value?.status_key === 'awaiting_process' && !!poCustomer.value.can_update,
)

const takenItemCount = computed<number>(
  () => poCustomer.value?.items.filter(item => Number(item.volume) > 0).length ?? 0,
)

const showGatePanel = computed<boolean>(
  () => canProcessSc.value || poCustomer.value?.status_key === 'blocked',
)

const gatePanelDescription = computed<string>(() =>
  poCustomer.value?.status_key === 'blocked'
    ? 'PO ini di-block oleh gerbang kredit. Tim Finance dapat melakukan Unblock, atau Anda dapat mengedit PO (mis. volume/termin) lalu Proses SC ulang.'
    : 'Jalankan gerbang kredit untuk memeriksa headroom customer terhadap nilai order sebelum Sales Confirmation dibuat.',
)

const statusSteps = computed<StepItem[]>(() => {
  const po = poCustomer.value
  if (!po) return []

  const sc = po.sales_confirmation
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
  })

  return steps
})

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
  if (!Number.isInteger(idPoc) || idPoc <= 0) {
    poCustomer.value = null
    loading.value = false
    redirectToErrorPage(router, 404)
    return
  }

  loading.value = true
  try {
    const { data }: { data: ApiResponse<PoCustomerDetail> } = await poCustomerApi.getById(idPoc)
    poCustomer.value = data.data
  } catch (e: any) {
    poCustomer.value = null
    if (redirectToErrorPage(router, e.response?.status)) return
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat PO Customer.')
  } finally {
    loading.value = false
  }
}

async function processSalesConfirmation(): Promise<void> {
  processing.value = true
  try {
    const { data } = await axios.post<ApiResponse<unknown>>(`/api/po-customers/${idPoc}/process-sc`)
    success('Berhasil', data.message || 'Proses SC selesai.')
    await load()
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'Status PO Customer sudah berubah.')
      await load()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal memproses gerbang Sales Confirmation.'))
    }
  } finally {
    processing.value = false
  }
}

function goBackToIndex(): void {
  router.push({ name: 'po-customers-index' })
}

function goToEdit(): void {
  router.push({ name: 'po-customers-edit', params: { id: idPoc } })
}

function openDeleteDialog(): void {
  deleteDialog.isOpen.value = true
}

async function submitDelete(): Promise<void> {
  deleteDialog.isLoading.value = true
  try {
    const { data } = await axios.delete<ApiResponse<null>>(`/api/customer-pos/${idPoc}`)
    deleteDialog.isOpen.value = false
    success('Berhasil', data.message || 'PO Customer berhasil dihapus.')
    router.push({ name: 'po-customers-index' })
  } catch (e: any) {
    if (e.response?.status === 409) {
      notifyError('Gagal', e.response?.data?.message ?? 'PO Customer sudah terkunci.')
      deleteDialog.isOpen.value = false
      await load()
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal menghapus PO Customer.'))
    }
  } finally {
    deleteDialog.isLoading.value = false
  }
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-screen-title">{{ poCustomer?.nomor_poc || 'Detail PO Customer' }}</h2>
            <span v-if="poCustomer" class="px-2.5 py-1 rounded-full text-caption"
              :class="poCustomerStatusBadgeClass(poCustomer.status_key)">{{ poCustomer.status_label }}</span>
          </div>
          <p v-if="poCustomer" class="mt-1 text-body-lg">
            {{ poCustomer.customer?.company_name || '-' }} · Penawaran {{ poCustomer.quotation?.nomor_penawaran || '-' }}
          </p>
          <p v-else class="mt-1 text-body-lg">Informasi lengkap PO Customer dan status Sales Confirmation-nya.</p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline-secondary" @click="goBackToIndex">
            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
            Kembali
          </Button>
          <Button v-if="canEditPo" variant="outline-secondary" @click="goToEdit">
            <Lucide icon="Pencil" class="mr-2 h-4 w-4" />
            Edit PO
          </Button>
          <Button v-if="canEditPo" variant="outline-danger" @click="openDeleteDialog">
            <Lucide icon="Trash2" class="mr-2 h-4 w-4" />
            Hapus
          </Button>
        </div>
      </div>

      <div v-if="loading" class="flex min-h-[320px] items-center justify-center gap-3 text-slate-500">
        <Lucide icon="Loader2" class="h-6 w-6 animate-spin" />
        <span class="text-body">Memuat data PO Customer...</span>
      </div>

      <div v-else-if="!poCustomer" class="flex min-h-[320px] flex-col items-center justify-center gap-2 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
          <Lucide icon="AlertTriangle" class="h-7 w-7 text-rose-500" />
        </div>
        <h3 class="text-section-title">PO Customer tidak ditemukan</h3>
        <p class="text-body">Silakan kembali ke halaman sebelumnya.</p>
      </div>

      <template v-else>
        <div class="gap-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="text-overline">Total Nilai PO</div>
              <div class="mt-1 num-lg">{{ formatCurrency(poCustomer.totals.grand_total) }}</div>
              <div class="text-caption">DPP {{ formatCurrency(poCustomer.totals.dpp) }}</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
              <Lucide icon="Banknote" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="text-overline">Total Volume</div>
              <div class="mt-1 num-lg">{{ formatNumber(poCustomer.totals.volume) }} m³</div>
              <div class="text-caption">{{ takenItemCount }} dari {{ poCustomer.items.length }} item diambil</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
              <Lucide icon="Boxes" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="text-overline">Term of Payment</div>
              <div class="mt-1 num-lg">{{ paymentDisplay }}</div>
              <div class="text-body text-xs">{{ poCustomer.tipe_bayar === 'CREDIT' ? 'Sejak Invoice Diterbitkan' : 'Dibayar di muka / saat kirim' }}</div>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
              <Lucide icon="Clock" class="h-4 w-4" />
            </div>
          </div>

          <div class="flex items-start justify-between gap-3 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <div>
              <div class="text-overline">Target Pengiriman</div>
              <div class="mt-1 num-lg">{{ formatDate(poCustomer.supply_date) }}</div>
              <div class="text-body text-xs">PO Date: {{ formatDate(poCustomer.tanggal_poc) }}</div>
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
                      <div class="text-form-label">Nomor PO Customer</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ poCustomer.nomor_poc || '-' }}</div>
                    </div>

                    <div class="gap-4 grid grid-cols-2">
                      <div>
                        <div class="text-form-label">Tanggal PO</div>
                        <div class="mt-1 text-body-strong">{{ formatDate(poCustomer.tanggal_poc) }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Supply Date</div>
                        <div class="mt-1 text-body-strong">{{ formatDate(poCustomer.supply_date) }}</div>
                      </div>
                    </div>

                    <div>
                      <div class="text-form-label">Term of Payment</div>
                      <div class="mt-1 text-body-strong">{{ paymentDisplay }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-span-12 md:col-span-5">
                  <div class="mx-2 mb-1 text-form-label">Customer & Penawaran</div>
                  <div class="px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="gap-4 grid grid-cols-12">
                      <div class="col-span-12">
                        <div class="text-form-label">Nama Perusahaan</div>
                        <div class="mt-1 text-body-strong">{{ poCustomer.customer?.company_name || '-' }}</div>
                      </div>
                      <div class="gap-4 grid grid-cols-2 col-span-12">
                        <div>
                          <div class="text-form-label">Kode Customer</div>
                          <div class="mt-1 text-body-strong">{{ poCustomer.customer?.customer_code || '-' }}</div>
                        </div>
                        <div>
                          <div class="text-form-label">Nomor Penawaran</div>
                          <div class="mt-1 text-body-strong">{{ poCustomer.quotation?.nomor_penawaran || '-' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardSection>

            <CardSection title="Item PO" description="Volume dan harga per item yang diambil customer"
              icon="Layers" icon-class="bg-indigo-100 text-indigo-600">
              <PoCustomerItemsTable :items="poCustomer.items" :totals="poCustomer.totals" />
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
                    <div class="text-body-strong">{{ poCustomer.lampiran_poc_ori }}</div>
                    <div class="text-caption text-slate-500">Diunggah {{ formatDate(poCustomer.created_time) }}</div>
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
                  <p class="text-body text-slate-600">
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

    <DeleteRecordDialog :open="deleteDialog.isOpen.value" :title="`Hapus PO ${poCustomer?.nomor_poc ?? ''}`"
      description="PO Customer beserta itemnya dan lampirannya akan dihapus." :loading="deleteDialog.isLoading.value"
      @close="deleteDialog.isOpen.value = false" @confirm="submitDelete" />
  </div>
</template>
