<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import { FormInput, FormLabel } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

// Types
interface DetailForm {
  volume_bl: string
  volume_terima: string
}

// Route / composables
const route = useRoute()
const router = useRouter()
const { success, error } = useNotification()

// State
const id = Number(route.params.id)
const po = ref<any>({})
const receives = ref<any[]>([])
const loading = ref(true)
const showModal = ref(false)
const submitLoading = ref(false)
const uploadProgress = ref(0)

const form = reactive({
  received_at: '',
  nama_pic: '',
  file: null as File | null,
  details: {} as Record<number, DetailForm>,
})

// Computed
const poProducts = computed<any[]>(() => Array.isArray(po.value.produks) ? po.value.produks : [])

const latestReceive = computed(() => {
  if (!receives.value.length) return null
  return [...receives.value].sort((a: any, b: any) =>
    new Date(b.received_at || b.created_at || 0).getTime() -
    new Date(a.received_at || a.created_at || 0).getTime()
  )[0]
})

const canAddReceive = computed(() => {
  const last = latestReceive.value
  if (!last || !Array.isArray(last.details)) return true
  return last.details.some((d: any) => Number(d.selisih ?? 0) !== 0)
})

// Lifecycle
onMounted(fetchData)

// Fetch
async function fetchData() {
  loading.value = true
  try {
    const [{ data: poData }, { data: recData }] = await Promise.all([
      axios.get(`/api/vendor-pos/${id}`),
      axios.get(`/api/vendor-pos/${id}/receives`),
    ])
    po.value = poData
    receives.value = recData

    poData.produks.forEach((item: any) => {
      form.details[item.id_po_produk] = { volume_bl: '', volume_terima: '' }
    })
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat data')
  } finally {
    loading.value = false
  }
}

// Action handlers
function goBack() {
  router.push({ name: 'vendor-pos-list' })
}

function openModal() {
  form.received_at = new Date().toISOString().substr(0, 10)
  form.nama_pic = ''
  form.file = null
  uploadProgress.value = 0
  Object.values(form.details).forEach(d => {
    d.volume_bl = ''
    d.volume_terima = ''
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function onNumberInput(idProduk: number, field: keyof DetailForm, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  form.details[idProduk][field] = isNaN(num) ? '' : num.toLocaleString('id-ID')
}

async function submitReceive() {
  submitLoading.value = true
  uploadProgress.value = 0
  try {
    const payload = new FormData()
    payload.append('received_at', form.received_at)
    payload.append('nama_pic', form.nama_pic)

    po.value.produks.forEach((item: any) => {
      const key = item.id_po_produk
      const d = form.details[key]
      const rawBl = d.volume_bl.replace(/\./g, '')
      const rawTerima = d.volume_terima.replace(/\./g, '')
      const selisih = parseInt(rawTerima || '0', 10) - (item.volume_po || 0)
      payload.append(`details[${key}][volume_bl]`, rawBl)
      payload.append(`details[${key}][volume_terima]`, rawTerima)
      payload.append(`details[${key}][selisih]`, String(selisih))
    })

    if (form.file) payload.append('file', form.file)

    await axios.post(`/api/vendor-pos/${id}/receives`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: e => {
        uploadProgress.value = Math.round((e.loaded / (e.total || 1)) * 100)
      },
    })

    success('Berhasil', 'Data receive berhasil disimpan')
    closeModal()
    fetchData()
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal simpan')
  } finally {
    submitLoading.value = false
  }
}

// Helpers
function computeSelisih(item: any): number {
  const rawTerima = parseInt(
    String(form.details[item.id_po_produk]?.volume_terima || '').replace(/\./g, ''),
    10,
  ) || 0
  return rawTerima - (item.volume_po || 0)
}

function formatSignedSelisih(item: any): string {
  return formatSigned(computeSelisih(item))
}

function formatSigned(value: number): string {
  if (value === 0) return '0'
  return `${value > 0 ? '+' : ''}${Math.abs(value).toLocaleString('id-ID')}`
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}

function formatNumber(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return isNaN(n) ? '-' : n.toLocaleString('id-ID')
}

function formatCurrency(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return isNaN(n) ? '-' : `Rp ${n.toLocaleString('id-ID')}`
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-4">

      <!-- HEADER -->
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-slate-800">Good Receipt</h2>
          <p class="mt-1 text-sm text-slate-500">
            Catat penerimaan produk dari vendor PO <code>{{ po.nomor_po }}</code> dan posting volume terima ke stok.
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

          <!-- Rincian Produk PO -->
          <CardSection title="Rincian Produk PO" description="Produk yang akan diterima berdasarkan vendor PO"
            icon="Boxes" icon-class="bg-indigo-100 text-indigo-600">
            <DataList :loading="loading" :empty="poProducts.length === 0" :colspan="4" :show-footer="false">
              <template #head>
                <Table.Th>Produk</Table.Th>
                <Table.Th class="text-right">Volume PO</Table.Th>
                <Table.Th class="text-right">Harga Tebus</Table.Th>
                <Table.Th class="text-right">Jumlah Harga</Table.Th>
              </template>
              <template #body>
                <Table.Tr v-for="item in poProducts" :key="item.id_po_produk" class="transition hover:bg-slate-50">
                  <Table.Td>
                    <div class="font-medium text-slate-800">{{ item.produk?.nama_produk || '-' }}</div>
                    <div class="mt-0.5 text-xs text-slate-400">
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                      {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </Table.Td>
                  <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.volume_po) }}</Table.Td>
                  <Table.Td class="text-right text-slate-700">{{ formatCurrency(item.harga_tebus) }}</Table.Td>
                  <Table.Td class="text-right font-semibold text-slate-800">{{ formatCurrency(item.jumlah_harga) }}
                  </Table.Td>
                </Table.Tr>
              </template>
            </DataList>
          </CardSection>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-4 space-y-4">
            <CardSection title="Ringkasan Receive" description="Kondisi penerimaan saat ini" icon="PackageCheck"
              icon-class="bg-emerald-100 text-emerald-600">
              <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div class="text-sm text-slate-500">Total Produk PO</div>
                  <div class="mt-1 text-xl font-bold text-slate-800">{{ poProducts.length }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                  <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                      <div class="text-sm font-semibold text-slate-800">History Receive</div>
                      <div class="text-xs text-slate-500">Riwayat penerimaan produk</div>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                      {{ receives.length }}
                    </span>
                  </div>

                  <div v-if="receives.length" class="space-y-3">
                    <div v-for="receive in receives" :key="receive.id"
                      class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <div class="text-sm font-semibold text-slate-800">
                            {{ formatDate(receive.received_at || receive.created_at) }}
                          </div>
                          <div class="text-xs text-slate-500">PIC: {{ receive.nama_pic || '-' }}</div>
                        </div>
                        <a v-if="receive.file_url" :href="receive.file_url" target="_blank"
                          class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                          <Lucide icon="Download" class="h-3.5 w-3.5" />
                          File
                        </a>
                      </div>

                      <div class="mt-3 space-y-2">
                        <div v-for="detail in receive.details" :key="detail.id"
                          class="rounded-md bg-white px-3 py-2 text-xs">
                          <div class="font-medium text-slate-700">{{ detail.produk?.nama_produk || '-' }}</div>
                          <div class="mt-1 grid grid-cols-3 gap-2 text-slate-500">
                            <span>BL {{ formatNumber(detail.volume_bl) }}</span>
                            <span>Terima {{ formatNumber(detail.volume_terima) }}</span>
                            <span class="text-right font-semibold"
                              :class="Number(detail.selisih || 0) === 0 ? 'text-emerald-600' : 'text-amber-600'">
                              {{ formatSigned(Number(detail.selisih || 0)) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center">
                    <div
                      class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                      <Lucide icon="Inbox" class="h-5 w-5" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-700">Belum ada receive</p>
                    <p class="mt-1 text-xs text-slate-500">Receive pertama dapat dicatat dari tombol Add Receive.</p>
                  </div>
                </div>

                <Button v-if="canAddReceive" variant="primary"
                  class="mt-2 inline-flex w-full items-center justify-center gap-2" @click="openModal">
                  <Lucide icon="Plus" class="h-4 w-4" />
                  Add Receive
                </Button>
              </div>
            </CardSection>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Add Receive Modal -->
  <FormModal :open="showModal" title="Add Receive" :description="`PO ${po.nomor_po || '-'}`" size="xl"
    submit-text="Simpan Receive" submit-icon="Save" :loading="submitLoading" @close="closeModal"
    @submit="submitReceive">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <FormLabel for="received_at">Tanggal Terima</FormLabel>
        <FormInput id="received_at" v-model="form.received_at" type="date" required />
      </div>
      <div>
        <FormLabel for="nama_pic">Nama PIC</FormLabel>
        <FormInput id="nama_pic" v-model="form.nama_pic" placeholder="Nama PIC" required />
      </div>
    </div>

    <div class="mt-5 space-y-4">
      <div v-for="item in poProducts" :key="item.id_po_produk"
        class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-semibold text-slate-800">{{ item.produk?.nama_produk || '-' }}</p>
            <p class="text-sm text-slate-500">Volume PO: {{ formatNumber(item.volume_po) }}</p>
          </div>
          <div class="text-sm font-medium text-slate-600">Harga Tebus: {{ formatCurrency(item.harga_tebus) }}</div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
          <div>
            <FormLabel :for="`harga_tebus_${item.id_po_produk}`">Harga Tebus</FormLabel>
            <FormInput :id="`harga_tebus_${item.id_po_produk}`" :value="formatNumber(item.harga_tebus)" readonly
              class="bg-slate-100 text-right" />
          </div>
          <div>
            <FormLabel :for="`volume_bl_${item.id_po_produk}`">Volume BL</FormLabel>
            <FormInput :id="`volume_bl_${item.id_po_produk}`" v-model="form.details[item.id_po_produk].volume_bl"
              type="text" inputmode="numeric" class="text-right" placeholder="0" required
              @input="onNumberInput(item.id_po_produk, 'volume_bl', $event)" />
          </div>
          <div>
            <FormLabel :for="`volume_terima_${item.id_po_produk}`">Volume Terima</FormLabel>
            <FormInput :id="`volume_terima_${item.id_po_produk}`"
              v-model="form.details[item.id_po_produk].volume_terima" type="text" inputmode="numeric" class="text-right"
              placeholder="0" required @input="onNumberInput(item.id_po_produk, 'volume_terima', $event)" />
          </div>
          <div>
            <FormLabel :for="`selisih_${item.id_po_produk}`">Selisih</FormLabel>
            <FormInput :id="`selisih_${item.id_po_produk}`" :value="formatSignedSelisih(item)" readonly
              class="bg-slate-100 text-right font-semibold" />
          </div>
        </div>
      </div>
    </div>

    <div class="mt-5">
      <FileUploadField
        v-model="form.file"
        label="Upload File"
        hint="Format file: PDF, JPG, atau PNG. Maks 5MB."
        accept=".pdf,.jpg,.png"
        choose-text="Pilih File"
        :max-size-mb="5"
      />
    </div>

    <div v-if="uploadProgress > 0" class="mt-4 overflow-hidden rounded-full bg-slate-200">
      <div class="h-2 bg-primary transition-[width] duration-300" :style="{ width: uploadProgress + '%' }" />
    </div>
  </FormModal>
</template>
