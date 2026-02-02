<template>
  <div class="p-6 intro-y">
    <div v-if="!penawaran.id_penawaran" class="text-center py-10">
      <span class="animate-spin text-lg">⏳ Memuat data penawaran...</span>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="flex items-center mb-4 justify-between">
        <div>
          <Button variant="outline-secondary" @click="goBack">← Kembali</Button>
          <h1 class="text-2xl font-semibold mt-2">
            Verifikasi Penawaran OM {{ penawaran.nomor_penawaran }}
          </h1>
        </div>
      </div>

      <!-- Informasi Harga Produk -->
      <div class="bg-white p-6 rounded shadow text-sm mb-6 border border-slate-200">
        <h3 class="text-lg font-semibold mb-3 text-slate-700">Informasi Harga Produk</h3>
        <div class="grid grid-cols-2 gap-y-2 gap-x-6">
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Periode Awal</span>
            <span class="col-span-2">: {{ formatDate(penawaran.produk_harga?.periode_awal) || '-' }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Periode Akhir</span>
            <span class="col-span-2">: {{ formatDate(penawaran.produk_harga?.periode_akhir) || '-' }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Harga COGS</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.produk_harga?.harga_cogs) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Harga Price List</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.produk_harga?.harga_price_list) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Harga BM</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.produk_harga?.harga_bm) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Harga OM</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.produk_harga?.harga_om) }}</span>
          </div>
        </div>
      </div>

      <!-- Informasi Umum -->
      <div class="bg-white p-6 rounded shadow grid grid-cols-2 gap-6 text-sm mb-6">
        <div class="space-y-2">
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Customer</span>
            <span class="col-span-2">: {{ penawaran.customer?.nama_perusahaan || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Cabang</span>
            <span class="col-span-2">: {{ penawaran.cabang?.nama_cabang || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Metode</span>
            <span class="col-span-2">: {{ penawaran.metode || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Ketentuan Order</span>
            <span class="col-span-2">: {{ penawaran.order_method || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Masa Berlaku</span>
            <span class="col-span-2">
              : {{ formatDate(penawaran.masa_berlaku) }} - {{ formatDate(penawaran.sampai_dengan) }}
            </span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Tipe Pembayaran</span>
            <span class="col-span-2">: {{ penawaran.tipe_pembayaran || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Status</span>
            <span class="col-span-2">: <span class="capitalize text-blue-600">{{ penawaran.status }}</span></span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Lokasi Kirim</span>
            <span class="col-span-2">: {{ penawaran.lokasi_pengiriman || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Syarat & Ketentuan</span>
            <span class="col-span-2">: {{ penawaran.syarat_ketentuan || '-' }}</span>
          </div>
        </div>

        <div class="space-y-2">
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Harga Penawaran</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.harga_dasar) }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Refund</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.refund) }} / volume</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Other Cost</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.other_cost) }} / volume</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Diskon</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.discount) }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">OAT</span>
            <span class="col-span-2">: {{ formatCurrency(penawaran.oat) }} / volume</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Toleransi Penyusutan</span>
            <span class="col-span-2">: {{ penawaran.toleransi_penyusutan }}%</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Keterangan</span>
            <span class="col-span-2">: {{ penawaran.keterangan || '-' }}</span>
          </div>
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Catatan</span>
            <span class="col-span-2">: {{ penawaran.catatan || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- ✅ Analisa Margin -->
      <div class="bg-white p-6 rounded shadow text-sm mb-6 border border-slate-200">
        <h3 class="text-lg font-semibold mb-3 text-slate-700">Analisa Margin</h3>

        <div class="grid grid-cols-2 gap-y-2 gap-x-6">
          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Net Sell / vol</span>
            <span class="col-span-2">: {{ formatCurrency(netSellPerVol) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">COGS / vol</span>
            <span class="col-span-2">: {{ formatCurrency(cogsPerVol) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">OAT / vol</span>
            <span class="col-span-2">: {{ formatCurrency(oatPerVol) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Gross Margin / vol</span>
            <span class="col-span-2">
              : <b>{{ formatCurrency(grossMarginPerVol) }}</b>
              ({{ grossMarginPct.toFixed(2) }}%)
            </span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Net Margin / vol</span>
            <span class="col-span-2">
              : <b>{{ formatCurrency(netMarginPerVol) }}</b>
              ({{ netMarginPct.toFixed(2) }}%)
            </span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Gross Profit (total)</span>
            <span class="col-span-2">: {{ formatCurrency(grossProfit) }}</span>
          </div>

          <div class="grid grid-cols-3">
            <span class="font-medium text-gray-600">Net Profit (total)</span>
            <span class="col-span-2">: {{ formatCurrency(netProfit) }}</span>
          </div>
        </div>

        <p class="text-xs text-slate-500 mt-3">
          *Margin dihitung tanpa PPN. (PPN bukan revenue perusahaan)
        </p>
      </div>

      <!-- Rincian Item -->
      <div class="bg-white p-6 rounded shadow mb-6">
        <h3 class="font-semibold mb-2">Rincian Item</h3>

        <table class="min-w-full text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="text-left px-4 py-2">Produk</th>
              <th class="text-right px-4 py-2">Volume</th>
              <th class="text-right px-4 py-2">Harga Dasar</th>
              <th class="text-right px-4 py-2">Jumlah</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="item in penawaran.items"
              :key="item.id_penawaran_item"
              class="hover:bg-slate-50"
            >
              <td class="px-4 py-2">
                {{ item.produk?.nama_produk || '-' }} -
                {{ item.produk?.jenis?.nama || '-' }} /
                {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
              </td>

              <td class="px-4 py-2 text-right">
                {{ formatNumber(item.volume_order) }}
              </td>

              <td class="px-4 py-2 text-right">
                {{ formatCurrency(penawaran.harga_dasar) }}
              </td>

              <td class="px-4 py-2 text-right font-medium">
                {{ formatCurrency(calcJumlah(item)) }}
              </td>
            </tr>
          </tbody>

          <tfoot class="bg-slate-50">
            <tr>
              <td colspan="3" class="text-right font-semibold px-4 py-2">Subtotal</td>
              <td class="text-right font-semibold px-4 py-2">{{ formatCurrency(subtotal) }}</td>
            </tr>

            <tr>
              <td colspan="3" class="text-right px-4 py-2">Diskon</td>
              <td class="text-right px-4 py-2 text-red-500">-{{ formatCurrency(totalDiskon) }}</td>
            </tr>

            <tr>
              <td colspan="3" class="text-right px-4 py-2">Total OAT</td>
              <td class="text-right px-4 py-2">{{ formatCurrency(totalOAT) }}</td>
            </tr>

            <!-- ✅ PPN dihitung dari DPP (Subtotal - Diskon + OAT) -->
            <tr>
              <td colspan="3" class="text-right px-4 py-2">PPN 11%</td>
              <td class="text-right px-4 py-2">{{ formatCurrency(ppn) }}</td>
            </tr>

            <tr class="bg-green-50 font-bold text-green-700">
              <td colspan="3" class="text-right px-4 py-2">Grand Total</td>
              <td class="text-right px-4 py-2">{{ formatCurrency(grandTotal) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Catatan -->
      <div class="bg-white p-6 rounded shadow mb-6 text-sm">
        <p><strong>Catatan Verifikasi BM:</strong> {{ penawaran.catatan_verifikasi || '-' }}</p>
        <p><strong>Catatan Verifikasi OM:</strong> {{ penawaran.catatan_om || '-' }}</p>
      </div>

      <!-- Tombol Aksi -->
      <div class="flex gap-2 mt-6">
        <Button variant="primary" v-if="penawaran.status === 'approved_bm'" @click="verifikasi">
          Setujui
        </Button>
        <Button variant="danger" v-if="penawaran.status === 'approved_bm'" @click="tolak">
          Tolak
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import axios from 'axios'
import Button from '@/components/Base/Button'

const route = useRoute()
const router = useRouter()
const penawaran = ref<any>({})
const id = Number(route.params.id)

// ====== Ambil data ======
async function fetchPenawaran() {
  const { data } = await axios.get(`/api/penawarans/${id}`)
  penawaran.value = data
}

// ====== Hitung jumlah per item (volume × harga - refund - other) ======
function calcJumlah(item: any) {
  const vol = Number(item.volume_order) || 0
  const hargaDasar = Number(penawaran.value.harga_dasar) || 0
  const refund = Number(penawaran.value.refund) || 0
  const otherCost = Number(penawaran.value.other_cost) || 0

  return (vol * hargaDasar) - (vol * refund) - (vol * otherCost)
}

// ====== Total volume ======
const totalVolume = computed(() =>
  Array.isArray(penawaran.value.items)
    ? penawaran.value.items.reduce((s: number, it: any) => s + (Number(it.volume_order) || 0), 0)
    : 0
)

// ====== Subtotal ======
const subtotal = computed(() =>
  Array.isArray(penawaran.value.items)
    ? penawaran.value.items.reduce((sum: number, it: any) => sum + calcJumlah(it), 0)
    : 0
)

// ====== Diskon ======
const totalDiskon = computed(() => Number(penawaran.value.discount) || 0)

// ====== OAT ======
const oatPerVol = computed(() => Number(penawaran.value.oat) || 0)
const totalOAT = computed(() => oatPerVol.value * totalVolume.value)

// ====== DPP & PPN ======
// ✅ DPP = (Subtotal - Diskon) + OAT
const dpp = computed(() => (subtotal.value - totalDiskon.value) + totalOAT.value)
const ppn = computed(() => dpp.value * 0.11)

// ✅ Grand Total = DPP + PPN
const grandTotal = computed(() => dpp.value + ppn.value)

// =======================================================
// ✅ ANALISA MARGIN (tanpa PPN)
// =======================================================

// Net Sell / vol = harga_dasar - refund - other_cost
const netSellPerVol = computed(() => {
  const jual   = Number(penawaran.value.harga_dasar) || 0
  const refund = Number(penawaran.value.refund) || 0
  const other  = Number(penawaran.value.other_cost) || 0
  return jual - refund - other
})

// COGS / vol
const cogsPerVol = computed(() => Number(penawaran.value.produk_harga?.harga_cogs) || 0)

// Gross Margin / vol (tanpa OAT)
const grossMarginPerVol = computed(() => netSellPerVol.value - cogsPerVol.value)

// Net Margin / vol (setelah OAT)
const netMarginPerVol = computed(() => netSellPerVol.value - cogsPerVol.value - oatPerVol.value)

// Margin %
const grossMarginPct = computed(() => {
  const sell = netSellPerVol.value
  if (sell <= 0) return 0
  return (grossMarginPerVol.value / sell) * 100
})

const netMarginPct = computed(() => {
  const sell = netSellPerVol.value
  if (sell <= 0) return 0
  return (netMarginPerVol.value / sell) * 100
})

// Profit total
const grossProfit = computed(() => grossMarginPerVol.value * totalVolume.value)
const netProfit = computed(() => netMarginPerVol.value * totalVolume.value)

// ====== Aksi OM ======
async function verifikasi() {
  const result = await Swal.fire({
    title: 'Verifikasi Penawaran OM?',
    input: 'textarea',
    inputLabel: 'Catatan Verifikasi',
    inputPlaceholder: 'Masukkan catatan jika ada...',
    showCancelButton: true,
    confirmButtonText: 'Ya, Disetujui',
    cancelButtonText: 'Batal',
    preConfirm: (val) => val || 'Tanpa catatan'
  })
  if (!result.isConfirmed) return

  await axios.patch(`/api/penawarans/${id}/verifikasi-om`, { catatan: result.value })
  Swal.fire('Berhasil', 'Penawaran disetujui', 'success')
  fetchPenawaran()
}

async function tolak() {
  const result = await Swal.fire({
    title: 'Tolak Penawaran OM?',
    input: 'textarea',
    inputLabel: 'Catatan Penolakan',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tolak',
    cancelButtonText: 'Batal'
  })
  if (!result.isConfirmed) return

  await axios.patch(`/api/penawarans/${id}/tolak-om`, { catatan: result.value })
  Swal.fire('Ditolak', 'Penawaran ditolak', 'success')
  fetchPenawaran()
}

// ====== Helper ======
function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}
function formatCurrency(v: number | string = 0) {
  const n = Number(v) || 0
  return `Rp. ${n.toLocaleString('id-ID')}`
}
function formatNumber(v: number | string = 0) {
  const n = Number(v) || 0
  return n.toLocaleString('id-ID')
}
function goBack() {
  router.back()
}

onMounted(fetchPenawaran)
</script>
