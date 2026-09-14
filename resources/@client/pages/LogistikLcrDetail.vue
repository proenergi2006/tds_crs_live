<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import { formatDate, formatDateTime, formatNumber } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idLcr = Number(route.params.id)

const loading = ref(true)
const bootstrapError = ref<string | null>(null)
const detail = ref<any>(null)
const timeline = ref<any[]>([])
const timelineError = ref(false)
const wilayahAngkuts = ref<any[]>([])
const decisionMode = ref<'approve' | 'reject'>('approve')
const note = ref('')
const submitting = ref(false)
const confirmOpen = ref(false)
const confirmResetOpen = ref(false)

const pageTitle = computed<string>(() => {
  const nama = detail.value?.customer?.nama_perusahaan
  return nama ? `Review LCR — ${nama} · ${detail.value?.site_name ?? '-'}` : 'Review LCR'
})
const approvalStatus = computed<string | null>(() => detail.value?.approval?.status ?? null)
const isInProgress = computed<boolean>(() => approvalStatus.value === 'in_progress')
const isDecided = computed<boolean>(() => approvalStatus.value === 'approved' || approvalStatus.value === 'rejected')
const supportsVessel = computed<boolean>(() => detail.value?.supports_vessel_delivery === true)
const mapUrl = computed<string>(() => {
  const lat = parseFloat(detail.value?.latitude_lokasi)
  const lng = parseFloat(detail.value?.longitude_lokasi)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return ''
  return `https://maps.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}&z=14&output=embed`
})
const wilayahAngkutName = computed<string>(() => {
  const id = detail.value?.id_wil_oa
  if (id === null || id === undefined || id === '') return '-'
  const found = wilayahAngkuts.value.find((w: any) => String(w.id) === String(id))
  return found?.destinasi ?? `#${id}`
})
const decisionSubmitDisabled = computed<boolean>(
  () => submitting.value || (decisionMode.value === 'reject' && !note.value.trim()),
)

onMounted(async () => {
  await fetchDetail()
  if (detail.value) {
    await Promise.all([fetchTimeline(), fetchWilayahAngkuts()])
  }
})

async function fetchDetail(): Promise<void> {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/review/lcr-sites/${idLcr}`)
    detail.value = data
    bootstrapError.value = null
  } catch (e: any) {
    bootstrapError.value = e.response?.data?.message ?? 'Gagal memuat detail LCR.'
    detail.value = null
  } finally {
    loading.value = false
  }
}

async function fetchTimeline(): Promise<void> {
  try {
    const { data } = await axios.get(`/api/lcr-sites/${idLcr}/approval-timeline`)
    timeline.value = data.data ?? []
    timelineError.value = false
  } catch {
    timeline.value = []
    timelineError.value = true
  }
}

async function fetchWilayahAngkuts(): Promise<void> {
  try {
    const { data } = await axios.get('/api/wilayah-angkuts')
    wilayahAngkuts.value = data?.data ?? data ?? []
  } catch {
    wilayahAngkuts.value = []
  }
}

async function submitDecision(): Promise<void> {
  submitting.value = true
  try {
    await axios.patch(`/api/review/lcr-sites/${idLcr}/decision`, {
      decision: decisionMode.value,
      note: note.value.trim() || null,
    })
    success('Berhasil', decisionMode.value === 'approve' ? 'Keputusan approve tersimpan.' : 'Keputusan reject tersimpan.')
    router.push({ name: 'logistik-lcrs' })
  } catch (e: any) {
    const status = e.response?.status
    if (status === 409) {
      notifyError('Gagal', 'Siklus approval sudah tidak aktif.')
      router.push({ name: 'logistik-lcrs' })
    } else if (status === 422) {
      const errors = e.response?.data?.errors
      notifyError(
        'Gagal',
        errors
          ? ((Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.')
          : (e.response?.data?.message ?? 'Periksa kembali input Anda.'),
      )
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Gagal memproses keputusan.')
    }
  } finally {
    submitting.value = false
    confirmOpen.value = false
  }
}

async function resetDecision(): Promise<void> {
  submitting.value = true
  try {
    await axios.patch(`/api/review/lcr-sites/${idLcr}/reset-decision`)
    success('Berhasil', 'Review LCR dibuka ulang.')
    note.value = ''
    decisionMode.value = 'approve'
    await fetchDetail()
    await fetchTimeline()
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal membuka ulang review.')
  } finally {
    submitting.value = false
    confirmResetOpen.value = false
  }
}

function goBack(): void {
  router.back()
}

function approvalBadgeClass(status?: string | null): string {
  if (status === 'in_progress') return 'bg-amber-100 text-amber-700'
  if (status === 'approved') return 'bg-emerald-100 text-emerald-700'
  if (status === 'rejected') return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-700'
}

function stepBadgeClass(status?: string | null): string {
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  if (status === 'approved') return 'bg-emerald-100 text-emerald-700'
  if (status === 'rejected') return 'bg-rose-100 text-rose-700'
  return 'bg-slate-100 text-slate-700'
}

function stepStatusLabel(status?: string | null): string {
  if (status === 'pending') return 'Menunggu'
  if (status === 'approved') return 'Disetujui'
  if (status === 'rejected') return 'Ditolak'
  return status ?? '-'
}

function methodChips(labels?: string[] | null, other?: string | null): string[] {
  const chips = Array.isArray(labels) ? [...labels] : []
  if (other && String(other).trim() !== '') chips.push(other)
  return chips
}

function num(value: unknown): string {
  return value === null || value === undefined || value === '' ? '-' : formatNumber(value as number | string)
}
</script>

<template>
  <FormPage :title="pageTitle" size="full" layout="sidebar" surface="plain" :show-footer="false" :loading="loading"
    :error="bootstrapError">
    <template #action>
      <Button variant="outline-secondary" @click="goBack">
        <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
        Kembali
      </Button>
    </template>

    <div v-if="!loading && detail" class="space-y-6">
      <CardSection title="Identitas & Info Umum" icon="MapPin">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Nama Site</span>
            <span class="font-strong text-right">{{ detail.site_name || '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Tanggal Survei</span>
            <span class="font-strong text-right">{{ formatDate(detail.survey_date) }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Surveyor</span>
            <span class="font-strong text-right">{{ detail.surveyor_names || '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Jenis Usaha Site</span>
            <span class="font-strong text-right">
              {{ detail.site_business_type || '-'
              }}<template v-if="detail.site_business_type_other"> &mdash; {{ detail.site_business_type_other }}</template>
            </span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Lingkungan Site</span>
            <span class="font-strong text-right">
              {{ detail.site_environment_label || '-'
              }}<template v-if="detail.site_environment_other"> &mdash; {{ detail.site_environment_other }}</template>
            </span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Kompetitor</span>
            <span class="font-strong text-right">{{ detail.competitors || '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Jam Operasional</span>
            <span class="font-strong text-right">{{ detail.operating_hours || '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Wilayah Angkut</span>
            <span class="font-strong text-right">{{ wilayahAngkutName }}</span>
          </div>
        </dl>

        <div class="mt-3 space-y-3">
          <div>
            <div class="font-label">Catatan Lingkungan Site</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.site_environment_notes || '-' }}</div>
          </div>
          <div>
            <div class="font-label">Catatan Survei</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.survey_notes || '-' }}</div>
          </div>
          <div>
            <div class="font-label mb-1">Volume Produk</div>
            <div v-if="Array.isArray(detail.product_volume) && detail.product_volume.length" class="overflow-x-auto">
              <Table bordered sm class="font-body">
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="(item, i) in detail.product_volume" :key="i">
                    <Table.Td v-for="[k, v] in Object.entries(item ?? {})" :key="k">
                      <span class="font-label block text-slate-500">{{ k }}</span>
                      <span class="font-strong">{{ v ?? '-' }}</span>
                    </Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
            <div v-else class="font-body">-</div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Akses & Rute" icon="Navigation">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Kapasitas Truk Maks (Min)</span>
            <span class="font-strong text-right">{{ num(detail.max_truck_capacity_min) }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Kapasitas Truk Maks (Maks)</span>
            <span class="font-strong text-right">{{ num(detail.max_truck_capacity_max) }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Jarak dari Depot</span>
            <span class="font-strong text-right">{{ num(detail.distance_from_depot) }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Volume Kirim Minimum</span>
            <span class="font-strong text-right">{{ num(detail.min_vol_kirim) }}</span>
          </div>
        </dl>

        <div class="mt-3 space-y-3">
          <div>
            <div class="font-label">Rute Lokasi</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.rute_lokasi || '-' }}</div>
          </div>
          <div>
            <div class="font-label">Catatan Lokasi</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.note_lokasi || '-' }}</div>
          </div>
          <div>
            <div class="font-label">Catatan Akses</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.access_notes || '-' }}</div>
          </div>
          <div>
            <div class="font-label mb-1">Biaya Rute</div>
            <div v-if="Array.isArray(detail.route_costs) && detail.route_costs.length" class="overflow-x-auto">
              <Table bordered sm class="font-body">
                <Table.Tbody class="bg-white">
                  <Table.Tr v-for="(item, i) in detail.route_costs" :key="i">
                    <Table.Td v-for="[k, v] in Object.entries(item ?? {})" :key="k">
                      <span class="font-label block text-slate-500">{{ k }}</span>
                      <span class="font-strong">{{ v ?? '-' }}</span>
                    </Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
            <div v-else class="font-body">-</div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Layout & Unloading Truk" icon="Truck">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Metode Unloading</span>
            <span class="font-strong text-right">{{ detail.unloading_method || '-' }}</span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Maks Truk / Hari</span>
            <span class="font-strong text-right">{{ num(detail.max_trucks_per_day) }}</span>
          </div>
        </dl>

        <div class="mt-3">
          <div class="font-label">Catatan Unloading</div>
          <div class="font-body mt-1 whitespace-pre-line">{{ detail.unloading_notes || '-' }}</div>
        </div>
      </CardSection>

      <CardSection title="Penyimpanan" icon="Package">
        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Jenis Penyimpanan</span>
            <span class="font-strong text-right">
              {{ detail.storage_type_label || '-'
              }}<template v-if="detail.storage_type_other"> &mdash; {{ detail.storage_type_other }}</template>
            </span>
          </div>
          <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">Kapasitas Penyimpanan</span>
            <span class="font-strong text-right">{{ num(detail.storage_capacity) }}</span>
          </div>
        </dl>

        <div class="mt-3">
          <div class="font-label">Catatan Penyimpanan</div>
          <div class="font-body mt-1 whitespace-pre-line">{{ detail.storage_notes || '-' }}</div>
        </div>
      </CardSection>

      <CardSection title="Verifikasi Quality &amp; Quantity" icon="ClipboardCheck">
        <div class="space-y-4">
          <div>
            <div class="font-label mb-1">Metode Pengecekan Quality</div>
            <div
              v-if="methodChips(detail.quality_checking_method_labels, detail.quality_checking_method_other).length"
              class="flex flex-wrap gap-1.5">
              <span
                v-for="chip in methodChips(detail.quality_checking_method_labels, detail.quality_checking_method_other)"
                :key="chip"
                class="font-label inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-slate-700">{{ chip }}</span>
            </div>
            <div v-else class="font-body">-</div>
          </div>
          <div>
            <div class="font-label">Catatan Quality</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.quality_checking_notes || '-' }}</div>
          </div>
          <div>
            <div class="font-label mb-1">Metode Pengecekan Quantity</div>
            <div
              v-if="methodChips(detail.quantity_checking_method_labels, detail.quantity_checking_method_other).length"
              class="flex flex-wrap gap-1.5">
              <span
                v-for="chip in methodChips(detail.quantity_checking_method_labels, detail.quantity_checking_method_other)"
                :key="chip"
                class="font-label inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-slate-700">{{ chip }}</span>
            </div>
            <div v-else class="font-body">-</div>
          </div>
          <div>
            <div class="font-label">Catatan Quantity</div>
            <div class="font-body mt-1 whitespace-pre-line">{{ detail.quantity_checking_notes || '-' }}</div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Vessel / Jetty" icon="Anchor">
        <div v-if="!supportsVessel" class="font-body text-slate-500">Site tidak mendukung pengiriman via vessel.</div>

        <template v-else>
          <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Jenis Vessel</span>
              <span class="font-strong text-right">
                {{ detail.vessel_type_label || '-'
                }}<template v-if="detail.vessel_type_other"> &mdash; {{ detail.vessel_type_other }}</template>
              </span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Kapasitas Kargo Vessel</span>
              <span class="font-strong text-right">{{ num(detail.vessel_cargo_capacity) }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Metode Unloading Vessel</span>
              <span class="font-strong text-right">
                {{ detail.vessel_unloading_method_label || '-'
                }}<template v-if="detail.vessel_unloading_method_other"> &mdash; {{ detail.vessel_unloading_method_other }}</template>
              </span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Jenis Jetty</span>
              <span class="font-strong text-right">{{ detail.jetty_type || '-' }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Maks LOA</span>
              <span class="font-strong text-right">{{ num(detail.max_loa) }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Min PBL</span>
              <span class="font-strong text-right">{{ num(detail.min_pbl) }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Draft (LWS)</span>
              <span class="font-strong text-right">{{ num(detail.draft_lws) }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Kapasitas Jetty (DWT)</span>
              <span class="font-strong text-right">{{ num(detail.jetty_capacity_dwt) }}</span>
            </div>
          </dl>

          <div class="mt-3 space-y-4">
            <div>
              <div class="font-label mb-1">Metode Quantity Vessel</div>
              <div
                v-if="methodChips(detail.vessel_quantity_checking_method_labels, detail.vessel_quantity_checking_method_other).length"
                class="flex flex-wrap gap-1.5">
                <span
                  v-for="chip in methodChips(detail.vessel_quantity_checking_method_labels, detail.vessel_quantity_checking_method_other)"
                  :key="chip"
                  class="font-label inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-slate-700">{{ chip }}</span>
              </div>
              <div v-else class="font-body">-</div>
              <div class="font-body mt-1 whitespace-pre-line text-slate-600">
                {{ detail.vessel_quantity_checking_notes || '-' }}
              </div>
            </div>
            <div>
              <div class="font-label mb-1">Metode Quality Vessel</div>
              <div
                v-if="methodChips(detail.vessel_quality_checking_method_labels, detail.vessel_quality_checking_method_other).length"
                class="flex flex-wrap gap-1.5">
                <span
                  v-for="chip in methodChips(detail.vessel_quality_checking_method_labels, detail.vessel_quality_checking_method_other)"
                  :key="chip"
                  class="font-label inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-slate-700">{{ chip }}</span>
              </div>
              <div v-else class="font-body">-</div>
              <div class="font-body mt-1 whitespace-pre-line text-slate-600">
                {{ detail.vessel_quality_checking_notes || '-' }}
              </div>
            </div>
            <div>
              <div class="font-label">Info Izin Jetty</div>
              <div class="font-body mt-1 whitespace-pre-line">{{ detail.jetty_permit_info || '-' }}</div>
            </div>
            <div>
              <div class="font-label">Persyaratan Dokumen</div>
              <div class="font-body mt-1 whitespace-pre-line">{{ detail.document_requirements || '-' }}</div>
            </div>
          </div>
        </template>
      </CardSection>

      <CardSection title="Lokasi &amp; Koordinat" icon="Map">
        <div class="space-y-3">
          <div>
            <div class="font-label">Alamat</div>
            <div class="font-body mt-1">
              {{ [detail.address?.address_line, detail.address?.village?.name, detail.address?.district?.name,
                detail.address?.regency?.name, detail.address?.province?.name,
                detail.address?.postal_code].filter(Boolean).join(', ') || '-' }}
            </div>
          </div>

          <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Latitude</span>
              <span class="font-strong text-right">{{ detail.latitude_lokasi ?? '-' }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Longitude</span>
              <span class="font-strong text-right">{{ detail.longitude_lokasi ?? '-' }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">Link Google Maps</span>
              <span class="font-strong text-right">
                <a v-if="detail.link_google_maps" :href="detail.link_google_maps" target="_blank" rel="noopener"
                  class="text-primary underline">Buka Peta</a>
                <template v-else>-</template>
              </span>
            </div>
          </dl>

          <div v-if="mapUrl" class="overflow-hidden rounded-lg border border-slate-200">
            <iframe :src="mapUrl" class="h-72 w-full" loading="lazy"></iframe>
          </div>
        </div>
      </CardSection>

      <CardSection title="Kontak Site" icon="Users">
        <div v-if="Array.isArray(detail.contacts) && detail.contacts.length" class="overflow-x-auto">
          <Table bordered sm class="font-body">
            <Table.Thead class="bg-slate-50">
              <Table.Th class="font-label">Nama</Table.Th>
              <Table.Th class="font-label">Jabatan</Table.Th>
              <Table.Th class="font-label">Telepon</Table.Th>
              <Table.Th class="font-label">Mobile</Table.Th>
              <Table.Th class="font-label">Email</Table.Th>
            </Table.Thead>
            <Table.Tbody class="bg-white">
              <Table.Tr v-for="c in detail.contacts" :key="c.id_contact ?? c.email ?? c.full_name">
                <Table.Td class="font-strong">{{ c.full_name || '-' }}</Table.Td>
                <Table.Td>{{ c.position || '-' }}</Table.Td>
                <Table.Td>{{ c.phone || '-' }}</Table.Td>
                <Table.Td>{{ c.mobile || '-' }}</Table.Td>
                <Table.Td>{{ c.email || '-' }}</Table.Td>
              </Table.Tr>
            </Table.Tbody>
          </Table>
        </div>
        <div v-else
          class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
          <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
          <div class="font-body">Belum ada kontak site.</div>
        </div>
      </CardSection>
    </div>

    <template #sidebar>
      <CardSection v-if="detail" title="Status Approval" icon="ShieldCheck">
        <div class="space-y-4">
          <div class="flex items-center gap-2">
            <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
              :class="approvalBadgeClass(detail.approval?.status)">
              {{ detail.approval?.status_label ?? 'Belum ada approval' }}
            </span>
            <span v-if="isInProgress" class="font-caption text-slate-500">
              Langkah {{ detail.approval.current_step_order }}
            </span>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <div v-if="timelineError" class="font-body text-slate-500">Riwayat approval tidak tersedia.</div>
            <div v-else-if="!timeline.length" class="font-body text-slate-500">Belum ada riwayat approval.</div>
            <div v-else class="space-y-4">
              <div v-for="cycle in timeline" :key="cycle.id_approval" class="rounded-lg border border-slate-200 p-3">
                <div class="space-y-3">
                  <div v-for="step in cycle.steps" :key="step.step_order"
                    class="border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                    <div class="flex items-center justify-between gap-2">
                      <span class="font-strong">{{ step.step_name || `Langkah ${step.step_order}` }}</span>
                      <span class="font-label inline-flex items-center rounded-full px-2.5 py-0.5"
                        :class="stepBadgeClass(step.status)">{{ stepStatusLabel(step.status) }}</span>
                    </div>
                    <div class="font-caption text-slate-500">
                      {{ step.actor_name ?? '-' }} · {{ formatDateTime(step.acted_at) ?? '-' }}
                    </div>
                    <div v-if="step.decision_note"
                      class="font-body mt-1 border-l-2 border-slate-200 pl-2 text-slate-600">
                      {{ step.decision_note }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </CardSection>

      <CardSection v-if="isInProgress" title="Keputusan" icon="Gavel">
        <div class="space-y-3">
          <div class="flex gap-2">
            <Button type="button" class="flex-1 items-center justify-center gap-2"
              :variant="decisionMode === 'approve' ? 'primary' : 'outline-secondary'"
              @click="decisionMode = 'approve'">
              Approve
            </Button>
            <Button type="button" class="flex-1 items-center justify-center gap-2"
              :variant="decisionMode === 'reject' ? 'danger' : 'outline-secondary'" @click="decisionMode = 'reject'">
              Reject
            </Button>
          </div>

          <div>
            <FormLabel>{{ decisionMode === 'reject' ? 'Alasan Penolakan' : 'Catatan (opsional)' }}</FormLabel>
            <FormTextarea v-model="note" rows="3" :disabled="submitting" />
          </div>

          <Button class="inline-flex w-full items-center justify-center gap-2"
            :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :disabled="decisionSubmitDisabled"
            @click="confirmOpen = true">
            <Lucide icon="Send" class="h-4 w-4" />
            Ajukan Keputusan
          </Button>
        </div>
      </CardSection>

      <CardSection v-if="isDecided" title="Buka Ulang Keputusan" icon="RotateCcw">
        <div class="space-y-3">
          <p class="font-body">Memulai siklus review baru; status kembali ke Menunggu.</p>
          <Button variant="outline-secondary" class="inline-flex w-full items-center justify-center gap-2"
            @click="confirmResetOpen = true">
            <Lucide icon="RotateCcw" class="h-4 w-4" />
            Reset Keputusan
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <ConfirmDialog :open="confirmOpen"
    :title="decisionMode === 'approve' ? 'Setujui LCR site ini?' : 'Tolak LCR site ini?'"
    :description="decisionMode === 'approve'
      ? 'Site akan dinyatakan lolos verifikasi Logistik.'
      : 'Alasan penolakan akan tercatat di riwayat approval.'"
    :confirm-text="decisionMode === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
    :icon="decisionMode === 'approve' ? 'Check' : 'X'"
    :icon-class="decisionMode === 'approve' ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
    :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :loading="submitting" @close="confirmOpen = false"
    @confirm="submitDecision" />

  <ConfirmDialog :open="confirmResetOpen" title="Buka ulang review LCR ini?"
    description="Siklus approval baru akan dimulai." confirm-text="Ya, Buka Ulang" icon="RotateCcw"
    icon-class="bg-amber-100 text-amber-600" variant="warning" :loading="submitting"
    @close="confirmResetOpen = false" @confirm="resetDecision" />
</template>
