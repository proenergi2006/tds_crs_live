<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/format'

import { poCustomerStatusBadgeClass } from './status'

const route = useRoute()
const router = useRouter()
const { error: notifyError } = useNotification()
const poCustomerApi = createResourceApi('/customer-pos')

const idPoc = Number(route.params.id)

const poCustomer = ref<any>(null)
const loading = ref(true)

const statusSteps = computed<StepItem[]>(() => {
  const po = poCustomer.value ?? {}
  const sc = po.sales_confirmation ?? null

  const scStatus: StepItem['status'] =
    po.status_key === 'done' ? 'completed' : po.status_key === 'sc_in_progress' ? 'active' : 'pending'

  return [
    {
      title: 'PO Customer Dibuat',
      status: 'completed',
      description: po.created_by || undefined,
      timestamp: po.created_time ? formatDateTime(po.created_time) : undefined,
    },
    {
      title: 'Sales Confirmation',
      status: scStatus,
      description: sc?.disposisi_label || po.status_label || undefined,
      timestamp: sc?.lastupdate_time ? formatDateTime(sc.lastupdate_time) : undefined,
    },
  ]
})

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

function goBackToIndex(): void {
  router.push({ name: 'po-customers-index' })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-y flex flex-col gap-4">
      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <h2 class="font-display">Detail PO Customer</h2>
          <p class="mt-1 font-lead">Informasi PO Customer dan status Sales Confirmation-nya.</p>
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
        <div class="gap-6 grid grid-cols-1 xl:grid-cols-3">
          <div class="space-y-6 xl:col-span-2">
            <CardSection title="Informasi PO Customer" icon="FileText" icon-class="bg-primary/10 text-primary">
              <div class="gap-4 grid grid-cols-12">
                <div class="col-span-12 md:col-span-7">
                  <div class="space-y-3">
                    <div>
                      <div class="font-label">Nomor PO</div>
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
                      <div class="font-label">TOP</div>
                      <div class="mt-1 font-strong">{{ poCustomer.top_poc || '-' }}</div>
                    </div>

                    <div>
                      <div class="font-label">Volume</div>
                      <div class="mt-1 font-strong">{{ formatNumber(poCustomer.volume_poc) }} m³</div>
                    </div>

                    <div>
                      <div class="font-label">Harga</div>
                      <div class="mt-1 font-strong">{{ formatCurrency(poCustomer.harga_poc) }} /m³</div>
                    </div>

                    <div>
                      <div class="font-label">Produk (ID)</div>
                      <div class="mt-1 font-strong">{{ poCustomer.produk_poc ?? '-' }}</div>
                    </div>

                    <div v-if="poCustomer.lampiran_poc">
                      <div class="font-label">Lampiran</div>
                      <a :href="`/storage/${poCustomer.lampiran_poc}`" target="_blank"
                        class="font-strong mt-1 inline-flex items-center gap-1 text-primary">
                        <Lucide icon="Paperclip" class="h-4 w-4" />
                        {{ poCustomer.lampiran_poc_ori }}
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-span-12 md:col-span-5">
                  <div class="mx-2 mb-1 font-label">Customer & Penawaran</div>
                  <div class="px-4 py-3 border border-slate-200 rounded-xl">
                    <div class="gap-4 grid grid-cols-12">
                      <div class="col-span-12">
                        <div class="font-label">Kode Customer</div>
                        <div class="mt-1 font-strong">{{ poCustomer.customer?.customer_code || '-' }}</div>
                      </div>
                      <div class="col-span-12">
                        <div class="font-label">Nama Perusahaan</div>
                        <div class="mt-1 font-strong">{{ poCustomer.customer?.company_name || '-' }}</div>
                      </div>
                      <div class="col-span-12">
                        <div class="font-label">Nomor Penawaran</div>
                        <div class="mt-1 font-strong">{{ poCustomer.penawaran?.nomor_penawaran || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardSection>
          </div>

          <div class="xl:col-span-1">
            <div class="top-6 sticky space-y-4">
              <CardSection title="Status PO Customer" description="Tahapan menuju Sales Confirmation" icon="ShieldCheck"
                icon-class="bg-success/10 text-success">
                <div class="space-y-5 px-2">
                  <span class="font-label inline-flex items-center rounded-full px-4 py-1.5 text-base"
                    :class="poCustomerStatusBadgeClass(poCustomer.status_key)">
                    {{ poCustomer.status_label }}
                  </span>

                  <Stepper :steps="statusSteps" direction="vertical" />
                </div>
              </CardSection>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
