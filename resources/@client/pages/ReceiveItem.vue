<template>
  <div class="p-4">
    <div class="mb-6 mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">
          Good Receipt
        </h2>

        <p class="mt-1 text-sm text-slate-500">
          Catat penerimaan produk dari vendor PO dan posting volume terima ke stok.
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
          <Lucide icon="ArrowLeft" class="h-4 w-4" />
          Kembali
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="space-y-6 xl:col-span-2">
        <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Nomor PO</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.nomor_po || '-' }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Tanggal PO</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">{{ formatDate(po.tanggal_inven) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Vendor</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.vendor?.nama_vendor || '-' }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Terminal</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.terminal?.nama_terminal || '-' }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Terms</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">
                {{ po.terms || '-' }} ({{ po.terms_day || '-' }} hari)
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="text-xs font-semibold uppercase text-slate-500">Subtotal</div>
              <div class="mt-1 text-sm font-semibold text-slate-800">{{ formatCurrency(po.subtotal) }}</div>
            </div>
          </div>
        </CardSection>

        <CardSection
          title="Rincian Produk PO"
          description="Produk yang akan diterima berdasarkan vendor PO"
          icon="Boxes"
          icon-class="bg-indigo-100 text-indigo-600"
        >
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Produk</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Volume PO</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Harga Tebus</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Jumlah Harga</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="item in poProducts" :key="item.id_po_produk" class="transition hover:bg-slate-50">
                  <td class="px-4 py-4">
                    <div class="font-medium text-slate-800">
                      {{ item.produk?.nama_produk || '-' }}
                    </div>
                    <div class="mt-1 text-sm text-slate-500">
                      {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                      {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                    </div>
                  </td>
                  <td class="px-4 py-4 text-right font-medium text-slate-700">
                    {{ formatNumber(item.volume_po) }}
                  </td>
                  <td class="px-4 py-4 text-right text-slate-700">
                    {{ formatCurrency(item.harga_tebus) }}
                  </td>
                  <td class="px-4 py-4 text-right font-semibold text-slate-800">
                    {{ formatCurrency(item.jumlah_harga) }}
                  </td>
                </tr>

                <tr v-if="poProducts.length === 0">
                  <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                    Tidak ada rincian produk untuk ditampilkan.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardSection>

      </div>

      <div class="xl:col-span-1">
        <div class="sticky top-4 space-y-6">
          <CardSection
            title="Ringkasan Receive"
            description="Kondisi penerimaan saat ini"
            icon="PackageCheck"
            icon-class="bg-emerald-100 text-emerald-600"
          >
            <div class="space-y-3">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm text-slate-500">Total Produk PO</div>
                <div class="mt-1 text-xl font-bold text-slate-800">
                  {{ poProducts.length }}
                </div>
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
                  <div
                    v-for="receive in receives"
                    :key="receive.id"
                    class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <div class="text-sm font-semibold text-slate-800">
                          {{ formatDate(receive.received_at || receive.created_at) }}
                        </div>
                        <div class="text-xs text-slate-500">
                          PIC: {{ receive.nama_pic || '-' }}
                        </div>
                      </div>

                      <a
                        v-if="receive.file_url"
                        :href="receive.file_url"
                        target="_blank"
                        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                      >
                        <Lucide icon="Download" class="h-3.5 w-3.5" />
                        File
                      </a>
                    </div>

                    <div class="mt-3 space-y-2">
                      <div
                        v-for="detail in receive.details"
                        :key="detail.id"
                        class="rounded-md bg-white px-3 py-2 text-xs"
                      >
                        <div class="font-medium text-slate-700">
                          {{ detail.produk?.nama_produk || '-' }}
                        </div>
                        <div class="mt-1 grid grid-cols-3 gap-2 text-slate-500">
                          <span>BL {{ formatNumber(detail.volume_bl) }}</span>
                          <span>Terima {{ formatNumber(detail.volume_terima) }}</span>
                          <span
                            class="text-right font-semibold"
                            :class="Number(detail.selisih || 0) === 0 ? 'text-emerald-600' : 'text-amber-600'"
                          >
                            {{ formatSigned(Number(detail.selisih || 0)) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="rounded-lg border border-dashed border-slate-300 px-4 py-8 text-center">
                  <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <Lucide icon="Inbox" class="h-5 w-5" />
                  </div>
                  <p class="mt-3 text-sm font-medium text-slate-700">Belum ada receive</p>
                  <p class="mt-1 text-xs text-slate-500">Receive pertama dapat dicatat dari tombol Add Receive.</p>
                </div>
              </div>

              <Button
                v-if="canAddReceive"
                variant="primary"
                class="mt-2 inline-flex w-full items-center justify-center gap-2"
                @click="openModal"
              >
                <Lucide icon="Plus" class="h-4 w-4" />
                Add Receive
              </Button>
            </div>
          </CardSection>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
          <div>
            <h3 class="text-lg font-semibold text-slate-800">
              Add Receive
            </h3>
            <p class="mt-1 text-sm text-slate-500">
              PO {{ po.nomor_po || '-' }}
            </p>
          </div>

          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50"
            @click="closeModal"
          >
            <Lucide icon="X" class="h-4 w-4" />
          </button>
        </div>

        <form class="max-h-[calc(90vh-82px)] overflow-y-auto px-6 py-5" @submit.prevent="submitReceive">
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
            <div
              v-for="item in poProducts"
              :key="item.id_po_produk"
              class="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
            >
              <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="font-semibold text-slate-800">
                    {{ item.produk?.nama_produk || '-' }}
                  </p>
                  <p class="text-sm text-slate-500">
                    Volume PO: {{ formatNumber(item.volume_po) }}
                  </p>
                </div>

                <div class="text-sm font-medium text-slate-600">
                  Harga Tebus: {{ formatCurrency(item.harga_tebus) }}
                </div>
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                  <FormLabel :for="`harga_tebus_${item.id_po_produk}`">Harga Tebus</FormLabel>
                  <FormInput
                    :id="`harga_tebus_${item.id_po_produk}`"
                    :value="formatNumber(item.harga_tebus)"
                    readonly
                    class="bg-slate-100 text-right"
                  />
                </div>

                <div>
                  <FormLabel :for="`volume_bl_${item.id_po_produk}`">Volume BL</FormLabel>
                  <FormInput
                    :id="`volume_bl_${item.id_po_produk}`"
                    v-model="form.details[item.id_po_produk].volume_bl"
                    type="text"
                    inputmode="numeric"
                    class="text-right"
                    placeholder="0"
                    required
                    @input="onNumberInput(item.id_po_produk, 'volume_bl', $event)"
                  />
                </div>

                <div>
                  <FormLabel :for="`volume_terima_${item.id_po_produk}`">Volume Terima</FormLabel>
                  <FormInput
                    :id="`volume_terima_${item.id_po_produk}`"
                    v-model="form.details[item.id_po_produk].volume_terima"
                    type="text"
                    inputmode="numeric"
                    class="text-right"
                    placeholder="0"
                    required
                    @input="onNumberInput(item.id_po_produk, 'volume_terima', $event)"
                  />
                </div>

                <div>
                  <FormLabel :for="`selisih_${item.id_po_produk}`">Selisih</FormLabel>
                  <FormInput
                    :id="`selisih_${item.id_po_produk}`"
                    :value="formatSignedSelisih(item)"
                    readonly
                    class="bg-slate-100 text-right font-semibold"
                  />
                  <input
                    type="hidden"
                    :name="`details[${item.id_po_produk}][selisih]`"
                    :value="computeSelisih(item)"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 rounded-xl border border-slate-200 p-4">
            <FormLabel for="receive_file">Upload File</FormLabel>
            <input
              id="receive_file"
              ref="fileInput"
              type="file"
              accept=".pdf,.jpg,.png"
              class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary/90"
              @change="onFileChange"
            />
            <p class="mt-2 text-xs text-slate-500">
              Format file: PDF, JPG, atau PNG. Maksimal mengikuti validasi server.
            </p>
          </div>

          <div v-if="uploadProgress > 0" class="mt-5 overflow-hidden rounded-full bg-slate-200">
            <div
              class="h-2 bg-primary transition-[width] duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>

          <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
            <Button type="button" variant="outline-secondary" @click="closeModal">
              Batal
            </Button>

            <Button type="submit" variant="primary" :disabled="uploadProgress > 0 && uploadProgress < 100">
              <Lucide icon="Save" class="mr-2 h-4 w-4" />
              {{ uploadProgress === 100 ? 'Uploaded' : 'Simpan Receive' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRoute, useRouter } from 'vue-router'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const po = ref<any>({})
const receives = ref<any[]>([])
const showModal = ref(false)
const uploadProgress = ref(0)

interface DetailForm {
  volume_bl: string
  volume_terima: string
}

const form = reactive({
  received_at: '',
  nama_pic: '',
  file: null as File | null,
  details: {} as Record<number, DetailForm>,
})

const fileInput = ref<HTMLInputElement>()

const poProducts = computed(() => {
  return Array.isArray(po.value.produks) ? po.value.produks : []
})

async function fetchData() {
  try {
    const [{ data: poData }, { data: recData }] = await Promise.all([
      axios.get(`/api/vendor-pos/${id}`),
      axios.get(`/api/vendor-pos/${id}/receives`),
    ])
    po.value = poData
    receives.value = recData

    poData.produks.forEach((item: any) => {
      form.details[item.id_po_produk] = {
        volume_bl: '',
        volume_terima: '',
      }
    })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  }
}

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
  if (fileInput.value) fileInput.value.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files
  form.file = files?.[0] ?? null
}

function onNumberInput(
  idProduk: number,
  field: keyof DetailForm,
  e: Event,
) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  form.details[idProduk][field] = isNaN(num) ? '' : num.toLocaleString('id-ID')
}

const latestReceive = computed(() => {
  if (!Array.isArray(receives.value) || receives.value.length === 0) return null

  const sorted = [...receives.value].sort((a: any, b: any) => {
    const da = new Date(a.received_at || a.created_at || 0).getTime()
    const db = new Date(b.received_at || b.created_at || 0).getTime()
    return da - db
  })
  return sorted[sorted.length - 1]
})

const canAddReceive = computed(() => {
  const last = latestReceive.value
  if (!last || !Array.isArray(last.details)) return true

  return last.details.some((d: any) => Number(d.selisih ?? 0) !== 0)
})

function computeSelisih(item: any): number {
  const rawTerima = parseInt(
    String(form.details[item.id_po_produk]?.volume_terima || '').replace(/\./g, ''),
    10,
  ) || 0
  const rawPo = item.volume_po || 0
  return rawTerima - rawPo
}

function formatSignedSelisih(item: any): string {
  const sel = computeSelisih(item)
  if (sel === 0) return '0'
  return `${sel > 0 ? '+' : ''}${Math.abs(sel).toLocaleString('id-ID')}`
}

function formatSigned(value: number): string {
  if (value === 0) return '0'
  return `${value > 0 ? '+' : ''}${Math.abs(value).toLocaleString('id-ID')}`
}

async function submitReceive() {
  try {
    const payload = new FormData()
    payload.append('received_at', form.received_at)
    payload.append('nama_pic', form.nama_pic)

    po.value.produks.forEach((item: any) => {
      const key = item.id_po_produk
      const d = form.details[key]
      const rawBl = d.volume_bl.replace(/\./g, '')
      const rawTerima = d.volume_terima.replace(/\./g, '')
      const rawPo = item.volume_po || 0
      const selisih = parseInt(rawTerima || '0', 10) - rawPo

      payload.append(`details[${key}][volume_bl]`, rawBl)
      payload.append(`details[${key}][volume_terima]`, rawTerima)
      payload.append(`details[${key}][selisih]`, String(selisih))
    })

    if (form.file) {
      payload.append('file', form.file)
    }

    await axios.post(
      `/api/vendor-pos/${id}/receives`,
      payload,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: e => {
          uploadProgress.value = Math.round((e.loaded / (e.total || 1)) * 100)
        },
      },
    )

    Swal.fire('Sukses', 'Data receive berhasil disimpan', 'success')
    closeModal()
    fetchData()
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal simpan', 'error')
  }
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    })
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

onMounted(fetchData)
</script>
