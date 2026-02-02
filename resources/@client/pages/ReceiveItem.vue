<template>
  <div class="p-6 bg-slate-100 min-h-screen">
    <!-- Header & PO Info -->
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-semibold text-slate-700">Receive Item</h1>

  <!-- tombol hanya muncul kalau masih ada selisih -->
  <Button v-if="canAddReceive" variant="primary" @click="openModal">
    <Lucide icon="Plus" class="w-4 h-4 mr-1" /> Add Receive
  </Button>
</div>

      <!-- PO Info Card -->
      <div class="bg-white shadow-lg rounded-lg p-6 grid grid-cols-2 gap-4">
        <div>
          <h2 class="text-lg font-medium mb-2">PO Info</h2>
          <p>
            <span class="font-semibold">Nomor PO:</span>
            {{ po.nomor_po || '-' }}
          </p>
          <p>
            <span class="font-semibold">Tanggal:</span>
            {{ formatDate(po.tanggal_inven) }}
          </p>
          <p>
            <span class="font-semibold">Vendor:</span>
            {{ po.vendor?.nama_vendor || '-' }}
          </p>
          <p>
            <span class="font-semibold">Terminal:</span>
            {{ po.terminal?.nama_terminal || '-' }}
          </p>
        </div>
        <div>
          <h2 class="text-lg font-medium mb-2">Terms & Totals</h2>
          <p>
            <span class="font-semibold">Terms:</span>
            {{ po.terms || '-' }} ({{ po.terms_day || '-' }} hari)
          </p>
          <p>
            <span class="font-semibold">Subtotal:</span>
            {{ formatCurrency(po.subtotal) }}
          </p>
          <p>
            <span class="font-semibold">PPN 11%:</span>
            {{ formatCurrency(po.ppn11) }}
          </p>
          <p>
            <span class="font-semibold">Total:</span>
            {{ formatCurrency(po.total_order) }}
          </p>
        </div>
      </div>

      <!-- Produk Table (Detail PO) -->
      <div class="bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">Rincian Produk</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
              <tr>
                <th class="px-4 py-2 text-left">Produk</th>
                <th class="px-4 py-2 text-right">Volume PO</th>
                <th class="px-4 py-2 text-right">Harga Tebus</th>
                <th class="px-4 py-2 text-right">Jumlah Harga</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr
                v-for="item in po.produks"
                :key="item.id_po_produk"
                class="hover:bg-slate-100"
              >
                <td class="px-4 py-2">{{ item.produk?.nama_produk || '-' }}</td>
                <td class="px-4 py-2 text-right">
                  {{ formatNumber(item.volume_po) }}
                </td>
                <td class="px-4 py-2 text-right">
                  {{ formatCurrency(item.harga_tebus) }}
                </td>
                <td class="px-4 py-2 text-right">
                  {{ formatCurrency(item.jumlah_harga) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Receive History (jika ada) -->
      <div v-if="receives.length" class="bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-lg font-medium mb-4">History Receive</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-2 text-left">Tanggal</th>
                <th class="px-4 py-2 text-right">Produk</th>
                <th class="px-4 py-2 text-right">Vol. BL</th>
                <th class="px-4 py-2 text-right">Vol. Terima</th>
                <th class="px-4 py-2 text-right">Selisih</th>
                <th class="px-4 py-2 text-left">PIC</th>
                <th class="px-4 py-2 text-center">File</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <template v-for="r in receives" :key="r.id">
                <!-- Baris header receive -->
          
                <!-- Detail produk per receive -->
                <tr
                  v-for="d in r.details"
                  :key="d.id"
                  class="hover:bg-slate-50"
                >
                <td class="px-4 py-2">
              <!-- pakai created_at dari receive_item_produks -->
              {{ formatDate(d.created_at) }}
            </td>
                  <td class="px-4 py-2 text-right">
                    {{ d.produk?.nama_produk || '-' }}
                  </td>
                  <td class="px-4 py-2 text-right">
                    {{ formatNumber(d.volume_bl) }}
                  </td>
                  <td class="px-4 py-2 text-right">
                    {{ formatNumber(d.volume_terima) }}
                  </td>
                  <td class="px-4 py-2 text-right">
                    {{ formatNumber(d.selisih) }}
                  </td>
                  <td class="px-4 py-2">  <span class="font-medium">{{ r.nama_pic }}</span></td>
                  <td class="px-4 py-2 text-center">
                    <a
                      v-if="r.file_url"
                      :href="r.file_url"
                      target="_blank"
                      class="text-blue-600 hover:underline"
                    >
                      Download
                    </a>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Receive -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 relative">
        <h3 class="text-lg font-semibold mb-4">
          Add Receive for PO {{ po.nomor_po }}
        </h3>
        <form @submit.prevent="submitReceive" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Tanggal Terima</label>
              <input
                v-model="form.received_at"
                type="date"
                class="w-full border rounded p-2"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Nama PIC</label>
              <input
                v-model="form.nama_pic"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Nama PIC"
                required
              />
            </div>
          </div>

          <!-- Input per Produk -->
          <div
            v-for="item in po.produks"
            :key="item.id_po_produk"
            class="border rounded p-4 space-y-2"
          >
            <p class="font-semibold">
              {{ item.produk?.nama_produk || '-' }}
              <span class="text-xs text-gray-500">
                (Vol PO: {{ formatNumber(item.volume_po) }})
              </span>
            </p>
            <div class="grid grid-cols-3 gap-4">
              <!-- Harga Tebus (readonly) -->
              <div>
                <label class="block text-sm mb-1">Harga Tebus</label>
                <input
                  :value="formatNumber(item.harga_tebus)"
                  readonly
                  class="w-full border rounded p-2 text-right bg-gray-100"
                />
              </div>
              <!-- Volume BL -->
              <div>
                <label class="block text-sm mb-1">Volume BL</label>
                <input
                  v-model="form.details[item.id_po_produk].volume_bl"
                  @input="onNumberInput(item.id_po_produk, 'volume_bl', $event)"
                  type="text"
                  inputmode="numeric"
                  class="w-full border rounded p-2 text-right"
                  placeholder="0"
                  required
                />
              </div>
              <!-- Volume Terima -->
              <div>
                <label class="block text-sm mb-1">Volume Terima</label>
                <input
                  v-model="form.details[item.id_po_produk].volume_terima"
                  @input="onNumberInput(item.id_po_produk, 'volume_terima', $event)"
                  type="text"
                  inputmode="numeric"
                  class="w-full border rounded p-2 text-right"
                  placeholder="0"
                  required
                />
              </div>
            </div>
            <div class="mt-2">
  <label class="block text-sm font-medium mb-1">Selisih</label>
  <input
    :value="formatSignedSelisih(item)"
    readonly
    class="w-full border rounded p-2 text-right bg-gray-50"
  />
  <!-- Hidden input agar selisih terkirim ke backend -->
  <input
    type="hidden"
    :name="`details[${item.id_po_produk}][selisih]`"
    :value="computeSelisih(item)"
  />
</div>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Upload File</label>
            <input
              ref="fileInput"
              @change="onFileChange"
              type="file"
              accept=".pdf,.jpg,.png"
              class="w-full"
            />
          </div>

          <!-- Progress Bar -->
          <div v-if="uploadProgress > 0" class="w-full bg-slate-200 rounded overflow-hidden">
            <div
              class="h-2 bg-blue-600 transition-width duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>

          <div class="flex justify-end space-x-2 mt-6">
            <button
              type="button"
              class="px-4 py-2 rounded border"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded bg-blue-600 text-white"
              :disabled="uploadProgress > 0 && uploadProgress < 100"
            >
              {{ uploadProgress === 100 ? '✓ Uploaded' : 'Save' }}
            </button>
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
import { useRoute } from 'vue-router'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

const route = useRoute()
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
  details: {} as Record<number, DetailForm>
})

const fileInput = ref<HTMLInputElement>()

async function fetchData() {
  try {
    const [{ data: poData }, { data: recData }] = await Promise.all([
      axios.get(`/api/vendor-pos/${id}`),
      axios.get(`/api/vendor-pos/${id}/receives`)
    ])
    po.value = poData
    receives.value = recData

    // Inisialisasi form.details
    poData.produks.forEach((item: any) => {
      form.details[item.id_po_produk] = {
        volume_bl: '',
        volume_terima: ''
      }
    })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  }
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
  e: Event
) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  form.details[idProduk][field] = isNaN(num) ? '' : num.toLocaleString('id-ID')
}


const latestReceive = computed(() => {
  if (!Array.isArray(receives.value) || receives.value.length === 0) return null
  // clone + sort ascending; ambil yang terakhir
  const sorted = [...receives.value].sort((a: any, b: any) => {
    const da = new Date(a.received_at || a.created_at || 0).getTime()
    const db = new Date(b.received_at || b.created_at || 0).getTime()
    return da - db
  })
  return sorted[sorted.length - 1]
})

const canAddReceive = computed(() => {
  const last = latestReceive.value
  if (!last || !Array.isArray(last.details)) return true        // belum ada receive → boleh tambah
  // kalau ada minimal satu detail yang selisih != 0 → masih boleh tambah
  return last.details.some((d: any) => Number(d.selisih ?? 0) !== 0)
})

// Hitung selisih: (Volume Terima − Volume PO)
function computeSelisih(item: any): number {
  const rawTerima = parseInt(
    String(form.details[item.id_po_produk].volume_terima).replace(/\./g, ''),
    10
  ) || 0
  const rawPo = item.volume_po || 0
  return rawTerima - rawPo
}

// Format selisih dengan tanda +/− dan ribuan
function formatSignedSelisih(item: any): string {
  const sel = computeSelisih(item)
  if (sel === 0) return '0'
  return `${sel > 0 ? '+' : ''}${Math.abs(sel).toLocaleString('id-ID')}`
}

// Saat menampilkan history receive, selisih per‐produk juga diperlukan
function formatSigned(value: number): string {
  if (value === 0) return '0'
  return `${value > 0 ? '+' : ''}${Math.abs(value).toLocaleString('id-ID')}`
}

async function submitReceive() {
  try {
    const payload = new FormData()
    payload.append('received_at', form.received_at)
    payload.append('nama_pic', form.nama_pic)

    // Untuk masing‐masing produk, append volume_bl, volume_terima, dan selisih
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
        onUploadProgress: (e) => {
          uploadProgress.value = Math.round((e.loaded / (e.total || 1)) * 100)
        }
      }
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
        year: 'numeric'
      })
    : '-'
}
function formatNumber(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return isNaN(n) ? '-' : n.toLocaleString('id-ID')
}
function formatCurrency(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return isNaN(n) ? '-' : `Rp. ${n.toLocaleString('id-ID')}`
}

onMounted(fetchData)
</script>