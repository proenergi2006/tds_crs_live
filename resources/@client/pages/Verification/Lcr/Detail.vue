<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import Button from '@/components/Base/Button'
import { Tab } from '@/components/Base/Headless'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import Badge from '@/components/SystemDesign/Data/Badge.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import { createResourceApi } from '@/utils/resourceApi'
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
const confirmedWilOa = ref<number | string>('')
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
  return found?.name ?? `#${id}`
})
const confirmedWilOaName = computed<string>(() => {
  if (!confirmedWilOa.value) return '-'
  const found = wilayahAngkuts.value.find((w: any) => String(w.id) === String(confirmedWilOa.value))
  return found?.name ?? `#${confirmedWilOa.value}`
})
const decisionSubmitDisabled = computed<boolean>(
  () => submitting.value
    || (decisionMode.value === 'reject' && !note.value.trim())
    || (decisionMode.value === 'approve' && !confirmedWilOa.value),
)

const PHOTO_CATEGORIES = [
  { field: 'road_condition_photos', label: 'Foto Kondisi Jalan Menuju Lokasi' },
  { field: 'site_layout_photos', label: 'Foto Layout Site/Pabrik' },
  { field: 'unloading_layout_photos', label: 'Foto Layout Area Unloading' },
  { field: 'storage_facility_photos', label: 'Foto Fasilitas Penyimpanan' },
  { field: 'measurement_evidence_photos', label: 'Foto Alat Ukur' },
  { field: 'vessel_layout_photos', label: 'Foto Layout Vessel/Jetty' },
  { field: 'company_office_photos', label: 'Foto Kantor & Gerbang Perusahaan' },
  { field: 'additional_photos', label: 'Foto Tambahan' },
] as const

const photoCategoriesWithFiles = computed(() =>
  PHOTO_CATEGORIES
    .map(c => ({ ...c, files: detail.value?.photos?.[c.field] ?? [] }))
    .filter(c => c.files.length > 0),
)

const TAB_ITEMS = [
  { label: 'Overview' },
  { label: 'Logistic Info' },
  { label: 'Vessel Info' },
  { label: 'Evidence' },
]

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
    confirmedWilOa.value = data.id_wil_oa ?? ''
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
    const { data } = await createResourceApi('/transport-areas').getAll({ as_list: true })
    wilayahAngkuts.value = data.data
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
      id_wil_oa: decisionMode.value === 'approve' ? confirmedWilOa.value : null,
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

function statusBadgeVariant(status?: string | null): 'soft-pending' | 'soft-success' | 'soft-danger' | 'soft-dark' {
  if (status === 'in_progress' || status === 'pending') return 'soft-pending'
  if (status === 'approved') return 'soft-success'
  if (status === 'rejected') return 'soft-danger'
  return 'soft-dark'
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

function rawText(value: unknown): string {
  return value === null || value === undefined || String(value).trim() === '' ? '-' : String(value)
}
</script>

<template>
  <FormPage :title="pageTitle" size="full" layout="sidebar" surface="plain" :show-footer="false" :loading="loading"
    :error="bootstrapError">
    <template #action>
      <Button variant="outline-secondary" @click="goBack">
        <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
        Kembali
      </Button>
    </template>

    <div v-if="!loading && detail">
      <Tab.Group>
        <Tab.List variant="link-tabs" class="gap-1 border-slate-200 border-b">
          <Tab v-for="t in TAB_ITEMS" :key="t.label" :full-width="false" v-slot="{ selected }">
            <Tab.Button class="flex items-center gap-2 px-4 py-2.5 text-sm" :class="selected
              ? 'text-primary border-b-primary font-medium'
              : 'text-slate-500 border-b-transparent hover:text-slate-700 hover:border-b-slate-300'">
              <span>{{ t.label }}</span>
            </Tab.Button>
          </Tab>
        </Tab.List>

        <Tab.Panels class="mt-4">
          <Tab.Panel>
            <div class="space-y-6 p-6 box">
              <div class="gap-4 grid grid-cols-2">
                <div class="space-y-4">
                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Identitas Umum
                    </h3>
                    <div class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                      <div class="sm:col-span-2">
                        <div class="text-form-label">Wilayah Angkut</div>
                        <div class="mt-1 text-body-strong">{{ wilayahAngkutName }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Tanggal Survei</div>
                        <div class="mt-1 text-body-strong">{{ formatDate(detail.survey_date) }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Surveyor</div>
                        <div class="mt-1 text-body-strong">{{ detail.surveyor_names || '-' }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Kontak Site
                    </h3>
                    <div v-if="detail.contact" class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                      <div>
                        <div class="text-form-label">Nama</div>
                        <div class="mt-1 text-body-strong">{{ detail.contact.full_name || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Jabatan</div>
                        <div class="mt-1 text-body-strong">{{ detail.contact.position || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Mobile</div>
                        <div class="mt-1 text-body-strong">{{ detail.contact.phone || detail.contact.mobile || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Email</div>
                        <div class="mt-1 text-body-strong">{{ detail.contact.email || '-' }}</div>
                      </div>
                    </div>
                    <div v-else class="text-body text-slate-500">Belum ada kontak site.</div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Lokasi
                    </h3>
                    <div class="gap-4 grid">
                      <div>
                        <div class="text-form-label">Alamat</div>
                        <div class="mt-1 text-body-strong">
                          {{ [detail.address?.address_line, detail.address?.village?.name,
                          detail.address?.district?.name,
                          detail.address?.regency?.name, detail.address?.province?.name,
                          detail.address?.postal_code].filter(Boolean).join(', ') || '-' }}
                        </div>

                        <div class="gap-x-6 gap-y-3 grid grid-cols-2 mt-3">
                          <div>
                            <div class="text-form-label">Latitude</div>
                            <div class="mt-1 text-body-strong">{{ detail.latitude_lokasi ?? '-' }}</div>
                          </div>
                          <div>
                            <div class="text-form-label">Longitude</div>
                            <div class="mt-1 text-body-strong">{{ detail.longitude_lokasi ?? '-' }}</div>
                          </div>
                        </div>

                        <a v-if="detail.link_google_maps" :href="detail.link_google_maps" target="_blank" rel="noopener"
                          class="inline-flex items-center gap-2 mt-3 text-primary underline">
                          <Lucide icon="Map" class="w-4 h-4" />
                          Buka di Google Maps
                        </a>

                        <div v-if="mapUrl" class="border border-slate-200 rounded-lg overflow-hidden">
                          <iframe :src="mapUrl" class="w-full h-64 lg:h-full" loading="lazy"></iframe>
                        </div>
                        <div v-else
                          class="flex justify-center items-center bg-slate-50 border border-slate-300 border-dashed rounded-lg h-64 lg:h-full text-body text-slate-500">
                          Koordinat belum tersedia.
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="p-4 border border-slate-200 rounded-lg">
                    <div class="space-y-3">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Profil Bisnis & Operasional
                      </h3>
                      <div>
                        <div class="text-form-label">Jenis Usaha</div>
                        <div class="mt-1 text-body-strong">
                          {{ detail.site_business_type || '-'
                          }}<template v-if="detail.site_business_type_other"> &mdash; {{ detail.site_business_type_other
                          }}</template>
                        </div>
                      </div>
                      <div>
                        <div class="text-form-label">Lingkungan</div>
                        <div class="mt-1 text-body-strong">
                          {{ detail.site_environment_label || '-'
                          }}<template v-if="detail.site_environment_other"> &mdash; {{ detail.site_environment_other
                          }}</template>
                        </div>
                      </div>

                      <div>
                        <div class="text-form-label">Catatan Survei</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.survey_notes || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Catatan Lingkungan</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.site_environment_notes || '-' }}
                        </div>
                      </div>

                      <div>
                        <div class="text-form-label">Kompetitor</div>
                        <div class="mt-1 text-body-strong">{{ detail.competitors || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Jam Operasional</div>
                        <div class="mt-1 text-body-strong">{{ detail.operating_hours || '-' }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Kebutuhan Produk & Volume/Bulan
                    </h3>
                    <div v-if="Array.isArray(detail.product_volume) && detail.product_volume.length"
                      class="border border-slate-200 rounded-lg overflow-x-auto">
                      <Table bordered class="text-body">
                        <Table.Thead class="bg-slate-50 text-form-label">
                          <Table.Tr>
                            <Table.Th>Produk</Table.Th>
                            <Table.Th>Volume / Bulan</Table.Th>
                          </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody class="bg-white">
                          <Table.Tr v-for="(item, i) in detail.product_volume" :key="i">
                            <Table.Td class="text-body-strong">{{ item.produk || '-' }}</Table.Td>
                            <Table.Td class="text-body-strong">{{ item.volume_bulan || '-' }}</Table.Td>
                          </Table.Tr>
                        </Table.Tbody>
                      </Table>
                    </div>
                    <div v-else class="text-body">Belum ada data produk & volume.</div>
                  </div>
                </div>
              </div>

            </div>
          </Tab.Panel>

          <Tab.Panel>
            <div class="space-y-6 p-6 box">
              <div class="gap-4 grid grid-cols-2">
                <div class="space-y-4">
                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Kapasitas Transport
                    </h3>
                    <div class="gap-3 grid grid-cols-3">
                      <div class="bg-slate-50 p-3 border border-slate-100 rounded-lg">
                        <div class="text-caption text-slate-500">Kapasitas Truk</div>
                        <div class="num-md">{{ num(detail.max_truck_capacity_min) }}&ndash;{{
                          num(detail.max_truck_capacity_max) }}</div>
                      </div>
                      <div class="bg-slate-50 p-3 border border-slate-100 rounded-lg">
                        <div class="text-caption text-slate-500">Volume Kirim Minimum</div>
                        <div class="num-md">{{ num(detail.min_vol_kirim) }}</div>
                      </div>
                      <div class="bg-slate-50 p-3 border border-slate-100 rounded-lg">
                        <div class="text-caption text-slate-500">Jarak dari Depot</div>
                        <div class="num-md">{{ num(detail.distance_from_depot) }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Rute
                    </h3>
                    <div class="space-y-3">
                      <div>
                        <div class="text-form-label">Rute Lokasi</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.rute_lokasi || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Catatan Lokasi</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.note_lokasi || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Catatan Akses</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.access_notes || '-' }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Biaya Rute
                    </h3>
                    <div v-if="Array.isArray(detail.route_costs) && detail.route_costs.length"
                      class="border border-slate-200 rounded-lg overflow-x-auto">
                      <Table bordered class="text-body">
                        <Table.Thead class="bg-slate-50 text-form-label">
                          <Table.Tr>
                            <Table.Th>Jenis Biaya</Table.Th>
                            <Table.Th>Nominal</Table.Th>
                            <Table.Th>Catatan</Table.Th>
                          </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody class="bg-white">
                          <Table.Tr v-for="(item, i) in detail.route_costs" :key="i">
                            <Table.Td class="text-body-strong">{{ item.cost_type || '-' }}</Table.Td>
                            <Table.Td class="text-body-strong">{{ num(item.amount) }}</Table.Td>
                            <Table.Td class="text-body-strong">{{ item.notes || '-' }}</Table.Td>
                          </Table.Tr>
                        </Table.Tbody>
                      </Table>
                    </div>
                    <div v-else class="text-body">Belum ada data biaya rute.</div>
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Unloading
                    </h3>
                    <div class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                      <div>
                        <div class="text-form-label">Metode Unloading</div>
                        <div class="mt-1 text-body-strong">{{ detail.unloading_method || '-' }}</div>
                      </div>
                      <div>
                        <div class="text-form-label">Maks Truk / Hari</div>
                        <div class="mt-1 text-body-strong">{{ num(detail.max_trucks_per_day) }}</div>
                      </div>
                    </div>
                    <div class="mt-3">
                      <div class="text-form-label">Catatan Unloading</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.unloading_notes || '-' }}</div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Penyimpanan
                    </h3>
                    <div class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                      <div>
                        <div class="text-form-label">Jenis Penyimpanan</div>
                        <div class="mt-1 text-body-strong">
                          {{ detail.storage_type_label || '-'
                          }}<template v-if="detail.storage_type_other"> &mdash; {{ detail.storage_type_other
                          }}</template>
                        </div>
                      </div>
                      <div>
                        <div class="text-form-label">Kapasitas Penyimpanan</div>
                        <div class="mt-1 text-body-strong">{{ rawText(detail.storage_capacity) }}</div>
                      </div>
                    </div>
                    <div class="mt-3">
                      <div class="text-form-label">Catatan Penyimpanan</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.storage_notes || '-' }}</div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Quality Check
                    </h3>
                    <div class="mb-1 text-form-label">Metode</div>
                    <div
                      v-if="methodChips(detail.quality_checking_method_labels, detail.quality_checking_method_other).length"
                      class="flex flex-wrap gap-1.5">
                      <Badge
                        v-for="chip in methodChips(detail.quality_checking_method_labels, detail.quality_checking_method_other)"
                        :key="chip" variant="soft-dark">{{ chip }}</Badge>
                    </div>
                    <div v-else class="text-body-strong">-</div>
                    <div class="mt-3">
                      <div class="text-form-label">Catatan</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.quality_checking_notes || '-' }}
                      </div>
                    </div>
                  </div>

                  <div class="p-4 border border-slate-200 rounded-lg">
                    <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                      <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Quantity Check
                    </h3>
                    <div class="mb-1 text-form-label">Metode</div>
                    <div
                      v-if="methodChips(detail.quantity_checking_method_labels, detail.quantity_checking_method_other).length"
                      class="flex flex-wrap gap-1.5">
                      <Badge
                        v-for="chip in methodChips(detail.quantity_checking_method_labels, detail.quantity_checking_method_other)"
                        :key="chip" variant="soft-dark">{{ chip }}</Badge>
                    </div>
                    <div v-else class="text-body-strong">-</div>
                    <div class="mt-3">
                      <div class="text-form-label">Catatan</div>
                      <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.quantity_checking_notes || '-' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Tab.Panel>

          <Tab.Panel>
            <div class="p-6 box">
              <div v-if="!supportsVessel"
                class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
                <Lucide icon="Anchor" class="w-6 h-6 text-slate-400" />
                <div class="text-body-strong">Vessel Delivery</div>
                <div class="text-body text-slate-500">Tidak didukung di site ini.</div>
              </div>

              <div v-else class="space-y-4">
                <div class="gap-4 grid grid-cols-2">
                  <div class="space-y-4">
                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Vessel
                      </h3>
                      <div class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                        <div>
                          <div class="text-form-label">Jenis Vessel</div>
                          <div class="mt-1 text-body-strong">
                            {{ detail.vessel_type_label || '-'
                            }}<template v-if="detail.vessel_type_other"> &mdash; {{ detail.vessel_type_other
                            }}</template>
                          </div>
                        </div>
                        <div>
                          <div class="text-form-label">Kapasitas Kargo</div>
                          <div class="mt-1 text-body-strong">{{ rawText(detail.vessel_cargo_capacity) }}</div>
                        </div>
                      </div>
                    </div>

                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Jetty
                      </h3>
                      <div class="gap-x-6 gap-y-3 grid grid-cols-1 sm:grid-cols-2">
                        <div>
                          <div class="text-form-label">Jenis Jetty</div>
                          <div class="mt-1 text-body-strong">{{ detail.jetty_type || '-' }}</div>
                        </div>
                        <div>
                          <div class="text-form-label">Maks LOA</div>
                          <div class="mt-1 text-body-strong">{{ num(detail.max_loa) }}</div>
                        </div>
                        <div>
                          <div class="text-form-label">Min PBL</div>
                          <div class="mt-1 text-body-strong">{{ num(detail.min_pbl) }}</div>
                        </div>
                        <div>
                          <div class="text-form-label">Draft (LWS)</div>
                          <div class="mt-1 text-body-strong">{{ num(detail.draft_lws) }}</div>
                        </div>
                        <div class="sm:col-span-2">
                          <div class="text-form-label">Kapasitas Jetty (DWT)</div>
                          <div class="mt-1 text-body-strong">{{ num(detail.jetty_capacity_dwt) }}</div>
                        </div>
                      </div>
                    </div>

                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Izin & Dokumen
                      </h3>
                      <div class="space-y-3">
                        <div>
                          <div class="text-form-label">Info Izin Jetty</div>
                          <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.jetty_permit_info || '-' }}
                          </div>
                        </div>
                        <div>
                          <div class="text-form-label">Persyaratan Dokumen</div>
                          <div class="mt-1 text-body-strong whitespace-pre-line">{{ detail.document_requirements || '-' }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Vessel Handling
                      </h3>
                      <div class="text-form-label">Metode Unloading Vessel</div>
                      <div class="mt-1 text-body-strong">
                        {{ detail.vessel_unloading_method_label || '-'
                        }}<template v-if="detail.vessel_unloading_method_other"> &mdash; {{
                          detail.vessel_unloading_method_other }}</template>
                      </div>
                    </div>

                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Vessel Quality Check
                      </h3>
                      <div class="mb-1 text-form-label">Metode</div>
                      <div
                        v-if="methodChips(detail.vessel_quality_checking_method_labels, detail.vessel_quality_checking_method_other).length"
                        class="flex flex-wrap gap-1.5">
                        <Badge
                          v-for="chip in methodChips(detail.vessel_quality_checking_method_labels, detail.vessel_quality_checking_method_other)"
                          :key="chip" variant="soft-dark">{{ chip }}</Badge>
                      </div>
                      <div v-else class="text-body-strong">-</div>
                      <div class="mt-3">
                        <div class="text-form-label">Catatan</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">
                          {{ detail.vessel_quality_checking_notes || '-' }}
                        </div>
                      </div>
                    </div>

                    <div class="p-4 border border-slate-200 rounded-lg">
                      <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                        <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />Vessel Quantity Check
                      </h3>
                      <div class="mb-1 text-form-label">Metode</div>
                      <div
                        v-if="methodChips(detail.vessel_quantity_checking_method_labels, detail.vessel_quantity_checking_method_other).length"
                        class="flex flex-wrap gap-1.5">
                        <Badge
                          v-for="chip in methodChips(detail.vessel_quantity_checking_method_labels, detail.vessel_quantity_checking_method_other)"
                          :key="chip" variant="soft-dark">{{ chip }}</Badge>
                      </div>
                      <div v-else class="text-body-strong">-</div>
                      <div class="mt-3">
                        <div class="text-form-label">Catatan</div>
                        <div class="mt-1 text-body-strong whitespace-pre-line">
                          {{ detail.vessel_quantity_checking_notes || '-' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="gap-4 grid grid-cols-2">
                </div>
              </div>
            </div>
          </Tab.Panel>

          <Tab.Panel>
            <div class="p-6 box">
              <div v-if="photoCategoriesWithFiles.length" class="gap-4 grid grid-cols-1 lg:grid-cols-2">
                <div v-for="category in photoCategoriesWithFiles" :key="category.field"
                  class="p-4 border border-slate-200 rounded-lg">
                  <h3 class="flex items-center gap-1.5 mb-3 pb-2 border-slate-200 border-b text-overline">
                    <Lucide icon="Info" class="w-3.5 h-3.5 shrink-0" />{{ category.label }}
                  </h3>
                  <div class="gap-3 grid grid-cols-2 sm:grid-cols-3">
                    <a v-for="photo in category.files" :key="photo.id" :href="photo.url ?? undefined" target="_blank"
                      rel="noopener" class="group block border border-slate-200 rounded-lg overflow-hidden">
                      <img v-if="photo.url" :src="photo.url" :alt="photo.file_name"
                        class="group-hover:opacity-90 w-full h-28 object-cover transition" />
                      <div v-else class="flex justify-center items-center bg-slate-50 w-full h-28">
                        <Lucide icon="FileWarning" class="w-6 h-6 text-slate-400" />
                      </div>
                      <div class="px-2 py-1.5">
                        <div class="text-caption text-slate-600 truncate">{{ photo.file_name }}</div>
                        <div v-if="photo.notes" class="text-caption text-slate-400 truncate">{{ photo.notes }}</div>
                      </div>
                    </a>
                  </div>
                </div>
              </div>
              <div v-else
                class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
                <Lucide icon="Inbox" class="w-6 h-6 text-slate-400" />
                <div class="text-body">Belum ada foto yang diunggah.</div>
              </div>
            </div>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>

    <template #sidebar>
      <CardSection v-if="detail" title="Status Approval" icon="ShieldCheck">
        <div class="space-y-4">
          <div>
            <div class="mb-1.5 text-overline text-slate-400">Status Saat Ini</div>
            <div class="flex items-center gap-2">
              <Badge :variant="statusBadgeVariant(detail.approval?.status)">
                {{ detail.approval?.status_label ?? 'Belum ada approval' }}
              </Badge>
              <span v-if="isInProgress" class="text-caption text-slate-500">
                Langkah {{ detail.approval.current_step_order }}
              </span>
            </div>
          </div>

          <div class="pt-3 border-slate-100 border-t">
            <div class="mb-2 text-overline text-slate-400">Riwayat Review</div>
            <div v-if="timelineError" class="text-body text-slate-500">Riwayat approval tidak tersedia.</div>
            <div v-else-if="!timeline.length" class="text-body text-slate-500">Belum ada riwayat approval.</div>
            <div v-else class="space-y-3">
              <div v-for="cycle in timeline" :key="cycle.id_approval"
                class="bg-slate-50 p-3 border border-slate-200 rounded-lg">
                <div class="space-y-3">
                  <div v-for="step in cycle.steps" :key="step.step_order"
                    class="pb-2 last:pb-0 border-slate-200 last:border-0 border-b">
                    <div class="flex justify-between items-center gap-2">
                      <span class="text-body-strong text-slate-600">{{ step.step_name || `Langkah ${step.step_order}`
                      }}</span>
                      <Badge :variant="statusBadgeVariant(step.status)">{{ stepStatusLabel(step.status) }}</Badge>
                    </div>
                    <div class="text-caption text-slate-500">
                      {{ step.actor_name ?? '-' }} · {{ formatDateTime(step.acted_at) ?? '-' }}
                    </div>
                    <div v-if="step.decision_note"
                      class="mt-1 pl-2 border-slate-300 border-l-2 text-body text-slate-500">
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
            <Button type="button" class="flex-1 justify-center items-center gap-2"
              :variant="decisionMode === 'approve' ? 'primary' : 'outline-secondary'" @click="decisionMode = 'approve'">
              Approve
            </Button>
            <Button type="button" class="flex-1 justify-center items-center gap-2"
              :variant="decisionMode === 'reject' ? 'danger' : 'outline-secondary'" @click="decisionMode = 'reject'">
              Reject
            </Button>
          </div>

          <div v-if="decisionMode === 'approve'">
            <FormLabel>
              Konfirmasi Wilayah Angkut
              <RequiredAsterisk />
            </FormLabel>
            <FormSelect v-model="confirmedWilOa" :disabled="submitting">
              <option value="">-- Pilih Wilayah --</option>
              <option v-for="w in wilayahAngkuts" :key="w.id" :value="w.id">{{ w.name }}</option>
            </FormSelect>
            <small class="block mt-1 text-caption text-slate-500">
              Diisi Marketing: <strong>{{ wilayahAngkutName }}</strong>. Ubah kalau dirasa kurang sesuai.
            </small>
          </div>

          <div>
            <FormLabel>{{ decisionMode === 'reject' ? 'Alasan Penolakan' : 'Catatan (opsional)' }}</FormLabel>
            <FormTextarea v-model="note" rows="3" :disabled="submitting" />
          </div>

          <Button class="inline-flex justify-center items-center gap-2 w-full"
            :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :disabled="decisionSubmitDisabled"
            @click="confirmOpen = true">
            <Lucide icon="Send" class="w-4 h-4" />
            Ajukan Keputusan
          </Button>
        </div>
      </CardSection>

      <CardSection v-if="isDecided" title="Buka Ulang Keputusan" icon="RotateCcw">
        <div class="space-y-3">
          <p class="text-body">Memulai siklus review baru; status kembali ke Menunggu.</p>
          <Button variant="outline-secondary" class="inline-flex justify-center items-center gap-2 w-full"
            @click="confirmResetOpen = true">
            <Lucide icon="RotateCcw" class="w-4 h-4" />
            Reset Keputusan
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>

  <ConfirmDialog :open="confirmOpen"
    :title="decisionMode === 'approve' ? 'Setujui LCR site ini?' : 'Tolak LCR site ini?'" :description="decisionMode === 'approve'
      ? `Site akan dinyatakan lolos verifikasi Logistik dengan wilayah angkut final: ${confirmedWilOaName}.`
      : 'Alasan penolakan akan tercatat di riwayat approval.'"
    :confirm-text="decisionMode === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak'"
    :icon="decisionMode === 'approve' ? 'Check' : 'X'"
    :icon-class="decisionMode === 'approve' ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
    :variant="decisionMode === 'approve' ? 'primary' : 'danger'" :loading="submitting" @close="confirmOpen = false"
    @confirm="submitDecision" />

  <ConfirmDialog :open="confirmResetOpen" title="Buka ulang review LCR ini?"
    description="Siklus approval baru akan dimulai." confirm-text="Ya, Buka Ulang" icon="RotateCcw"
    icon-class="bg-amber-100 text-amber-600" variant="warning" :loading="submitting" @close="confirmResetOpen = false"
    @confirm="resetDecision" />
</template>
