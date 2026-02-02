<template>
  <div class="p-6 w-full">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-700">Form PO Customer</h2>
      <RouterLink
        to="/penawarans"
        class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded transition"
      >
        ← Kembali
      </RouterLink>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-white p-6 rounded shadow-md">
      <!-- Kiri -->
      <div class="space-y-4">
        <!-- Customer -->
        <div>
          <label class="block text-sm text-gray-600 mb-1">Nama Customer</label>
          <select v-model="form.customer_id" class="form-input" disabled>
            <option :value="form.customer_id">{{ form.customer_nama }}</option>
          </select>
        </div>

        <!-- TOP -->
        <div>
          <label class="block text-sm text-gray-600 mb-1">TOP / Termin Pembayaran *</label>
          <input type="text" v-model="form.top_poc" class="form-input" />
        </div>

        <!-- Nomor Penawaran -->
        <div>
          <label class="block text-sm text-gray-600 mb-1">Nomor Penawaran</label>
          <select v-model="form.id_penawaran" class="form-input" disabled>
            <option :value="form.id_penawaran">{{ form.nomor_penawaran }}</option>
          </select>
        </div>

        <!-- TABEL KETERANGAN PENAWARAN -->
        <div v-if="penawaranLoaded" class="mt-2">
          <table class="min-w-full border border-gray-300 text-sm">
            <thead>
              <tr class="bg-gray-100">
                <th colspan="2" class="text-center py-2 font-bold text-gray-700">KETERANGAN</th>
              </tr>
            </thead>

            <tbody>
              <tr class="border-t">
                <td class="px-4 py-2 border-r w-1/3">Masa berlaku harga</td>
                <td class="px-4 py-2">
                  {{ formatDate(form.masa_berlaku) }} – {{ formatDate(form.sampai_dengan) }}
                </td>
              </tr>

              <tr class="border-t">
                <td class="px-4 py-2 border-r">Cabang</td>
                <td class="px-4 py-2">{{ form.cabang_nama }}</td>
              </tr>

              <!-- Produk per item -->
              <tr
                v-for="(it, i) in items"
                :key="it.id_penawaran_item || i"
                class="border-t align-top"
              >
                <td class="px-4 py-2 border-r">Produk {{ i + 1 }}</td>
                <td class="px-4 py-2">
                  <div class="font-medium">
                    {{ it.produk?.nama_produk || '-' }}
                    <span v-if="it.produk?.jenis?.nama"> · {{ it.produk?.jenis?.nama }}</span>
                  </div>
                  <div>
                    Ukuran:
                    <span class="font-medium">
                      {{ it.produk?.ukuran?.nama_ukuran || '-' }}
                    </span>
                    <span class="text-gray-500">
                      (Satuan: {{ it.produk?.ukuran?.satuan?.nama_satuan || '-' }})
                    </span>
                  </div>
                  <div>
                    Volume:
                    <span class="font-medium">{{ formatNumber(toNumber(it.volume_order)) }}</span>
                  </div>
                  <div>
                    Harga / m³:
                    <span class="font-medium">{{ formatCurrency(toNumber(it.harga_tebus)) }}</span>
                  </div>
                </td>
              </tr>

              <tr class="border-t">
                <td class="px-4 py-2 border-r">Ongkos Angkut</td>
                <td class="px-4 py-2">{{ formatCurrency(form.oat) }}</td>
              </tr>

              <tr class="border-t">
                <td class="px-4 py-2 border-r">Total Volume</td>
                <td class="px-4 py-2">{{ formatNumber(form.volume) }}</td>
              </tr>

              <tr class="border-t">
                <td class="px-4 py-2 border-r">Harga / m³</td>
                <td class="px-4 py-2">
                  <template v-if="allSamePrices">
                    {{ formatCurrency(form.harga_unit) }}
                  </template>
                  <template v-else>
                    <span class="italic text-gray-500">Bervariasi</span>
                  </template>
                </td>
              </tr>

              <tr class="border-t">
                <td class="px-4 py-2 border-r">Total Harga (Σ volume × harga)</td>
                <td class="px-4 py-2">{{ formatCurrency(form.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ringkasan di bawah tabel -->
        <div class="grid grid-cols-1 gap-4">
          <div>
           <!-- JADI: Total Harga (jumlah harga semua produk) -->
<label class="block text-sm text-gray-600 mb-1">Total Harga</label>
<input
  type="text"
  :value="formatCurrency(totalHargaProduk)"
  readonly
  class="form-input"
/>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Jumlah Volume (Total) *</label>
            <input type="text" :value="formatNumber(form.volume)" readonly class="form-input" />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Total Order</label>
            <input type="text" :value="formatCurrency(form.total)" readonly class="form-input" />
          </div>
        </div>
      </div>

      <!-- Kanan -->
      <div class="space-y-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1">Nomor PO *</label>
          <input type="text" v-model="form.nomor_po" class="form-input" />
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Tanggal PO *</label>
          <input type="date" v-model="form.tanggal_po" class="form-input" />
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Tanggal Pengiriman *</label>
          <input type="date" v-model="form.tanggal_kirim" class="form-input" />
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">Lampiran</label>
          <input type="file" @change="handleFile" class="form-input" />
        </div>

        <div class="pt-6">
          <button
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
            @click="submit"
          >
            💾 Simpan PO
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const penawaranLoaded = ref(false)
const route = useRoute()
const id_penawaran = route.query.id_penawaran

const items = ref<any[]>([])

const form = ref<any>({
  // header penawaran
  customer_id: '',
  customer_nama: '',
  id_penawaran: id_penawaran || '',
  nomor_penawaran: '',
  cabang_nama: '',
  masa_berlaku: '',
  sampai_dengan: '',
  oat: 0,

  // aggregations
  harga_unit: 0, // unit price (from first item if all same)
  volume: 0,     // sum of volumes
  total: 0,      // Σ volume * harga

  // submit fields
  top_poc: '',
  nomor_po: '',
  tanggal_po: '',
  tanggal_kirim: '',
  lampiran: null,
  produk_poc: '', // send 1st product id to backend (column required)
})

const totalHargaProduk = computed(() =>
  items.value.reduce((sum, it) => sum + toNumber(it.harga_tebus), 0)
)

/* ---------- helpers ---------- */

// convert string "2.100,00" / "2,100.00" / 2100 → number
function toNumber(val: any): number {
  if (val === null || val === undefined) return 0
  if (typeof val === 'number') return val

  const s = String(val).trim()
  if (!s) return 0

  // If both separators exist, assume European "dot thousands, comma decimal"
  if (s.includes('.') && s.includes(',')) {
    const cleaned = s.replace(/\./g, '').replace(',', '.')
    const n = Number(cleaned)
    return isNaN(n) ? 0 : n
  }

  // If only comma exists, treat as decimal separator
  if (s.includes(',') && !s.includes('.')) {
    const cleaned = s.replace(',', '.')
    const n = Number(cleaned)
    return isNaN(n) ? 0 : n
  }

  const n = Number(s.replace(/[^\d.-]/g, ''))
  return isNaN(n) ? 0 : n
}

function formatNumber(n: number) {
  return (Number(n) || 0).toLocaleString('id-ID')
}

function formatCurrency(value: number | string) {
  const num = typeof value === 'string' ? toNumber(value) : value
  return isNaN(num as number)
    ? 'Rp. 0'
    : `Rp. ${(num as number).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}`
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

/* ---------- derived ---------- */

const allSamePrices = computed(() => {
  if (!items.value.length) return true
  const base = toNumber(items.value[0]?.harga_tebus)
  return items.value.every(it => toNumber(it.harga_tebus) === base)
})

function calcTotals() {
  const totalVol = items.value.reduce((sum, it) => sum + toNumber(it.volume_order), 0)

  const firstPrice = items.value[0] ? toNumber(items.value[0].harga_tebus) : 0

  const totalOrder = items.value.reduce(
    (sum, it) => sum + toNumber(it.volume_order) * toNumber(it.harga_tebus),
    0
  )

  form.value.volume = Math.round(totalVol) // liters as integer
  form.value.harga_unit = allSamePrices.value ? firstPrice : 0
  form.value.total = totalOrder
}

/* ---------- io ---------- */

function handleFile(e: any) {
  form.value.lampiran = e.target.files[0]
}

async function fetchPenawaran() {
  if (!id_penawaran) return
  try {
    const { data } = await axios.get(`/api/penawarans/${id_penawaran}`)

    // header
    form.value.customer_id = data.id_customer
    form.value.customer_nama = data.customer?.nama_perusahaan || '-'
    form.value.id_penawaran = data.id_penawaran
    form.value.nomor_penawaran = data.nomor_penawaran || '-'
    form.value.cabang_nama = data.cabang?.nama_cabang || '-'
    form.value.masa_berlaku = data.masa_berlaku
    form.value.sampai_dengan = data.sampai_dengan
    form.value.oat = toNumber(data.oat)

    // items
    items.value = Array.isArray(data.items) ? data.items : []

    // default for produk_poc (first product id, to satisfy NOT NULL)
    form.value.produk_poc = items.value[0]?.produk?.id_produk || ''

    // totals
    calcTotals()

    penawaranLoaded.value = true
  } catch (err) {
    alert('Gagal memuat data penawaran')
  }
}

async function submit() {
  // volume integer
  const cleanVolume = Math.round(Number(form.value.volume) || 0)

  const payload = new FormData()
  payload.append('id_customer', String(form.value.customer_id))
  payload.append('id_penawaran', String(form.value.id_penawaran))
  payload.append('nomor_poc', form.value.nomor_po)
  payload.append('top_poc', form.value.top_poc)
  payload.append('tanggal_poc', form.value.tanggal_po)
  payload.append('supply_date', form.value.tanggal_kirim)

  // KIRIM TOTAL HARGA (bukan unit), dan JANGAN duplikat field
  payload.append('harga_poc', String(totalHargaProduk.value))

  payload.append('volume_poc', String(cleanVolume))

  // ⬇️ WAJIB: kirim produk_poc agar tidak NULL di DB
  payload.append('produk_poc', String(form.value.produk_poc || ''))

  if (form.value.lampiran) {
    payload.append('lampiran_poc', form.value.lampiran)
  }

  // Konfirmasi yang sesuai (tampilkan Total Harga)
  const html =
    `PO akan disimpan:<br>` +
    `• Volume: <b>${formatNumber(cleanVolume)} m³</b><br>` +
    `• Total Harga Produk: <b>${formatCurrency(totalHargaProduk.value)}</b><br>` +
    `• Total Order (Σ volume × harga): <b>${formatCurrency(form.value.total)}</b>`

  const result = await Swal.fire({
    title: 'Apakah Anda yakin?',
    html,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, simpan',
    cancelButtonText: 'Batal',
  })
  if (!result.isConfirmed) return

  try {
    await axios.post('/api/customer-pos', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    await Swal.fire({
      icon: 'success',
      title: 'PO berhasil disimpan!',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
    })
  } catch (err: any) {
    if (err.response?.status === 422) {
      const errs = err.response?.data?.errors || {}
      const msg = Object.values(errs).flat().join('<br>')
      Swal.fire({ icon: 'error', title: 'Validasi gagal', html: msg })
    } else {
      // bantu debug
      console.error('Save failed:', err.response?.data || err)
      Swal.fire({ icon: 'error', title: 'Terjadi kesalahan saat menyimpan PO' })
    }
  }
}


onMounted(fetchPenawaran)
</script>

<style scoped>
.form-input {
  @apply border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>
