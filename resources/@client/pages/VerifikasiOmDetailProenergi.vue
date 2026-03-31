<template>
  <div class="p-6 intro-y">
    <div v-if="loading" class="flex min-h-[420px] items-center justify-center">
      <div class="rounded-2xl border border-slate-200 bg-white px-8 py-6 shadow-sm">
        <div class="flex items-center gap-3 text-slate-600">
          <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-blue-600"></span>
          <span class="text-base font-medium">Memuat data penawaran...</span>
        </div>
      </div>
    </div>

    <div v-else-if="!penawaran.id_penawaran" class="flex min-h-[420px] items-center justify-center">
      <div class="rounded-2xl border border-slate-200 bg-white px-8 py-8 text-center shadow-sm">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
          <span class="text-2xl">!</span>
        </div>
        <h3 class="text-lg font-semibold text-slate-700">Data penawaran tidak ditemukan</h3>
        <p class="mt-1 text-sm text-slate-500">Silakan kembali ke halaman sebelumnya.</p>
        <div class="mt-5">
          <Button variant="outline-secondary" @click="goBack">Kembali</Button>
        </div>
      </div>
    </div>

    <div v-else>
      <!-- Header -->
      <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Button variant="outline-secondary" @click="goBack">← Kembali</Button>
          <h1 class="mt-3 text-2xl font-bold text-slate-800">
            Verifikasi Penawaran Proenergi
          </h1>
          <p class="mt-1 text-sm text-slate-500">
            {{ penawaran.nomor_penawaran }}
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">Status</div>
            <div class="mt-2 text-base font-bold text-blue-700">
              {{ formatStatus(penawaran.status) }}
            </div>
          </div>

          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-500">Disposisi</div>
            <div class="mt-2 text-base font-bold text-emerald-700">
              {{ formatDisposisi(penawaran.disposisi_penawaran) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Ringkasan Nominal -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Harga Dasar</div>
          <div class="mt-3 text-3xl font-bold tracking-tight text-slate-800">
            {{ formatCurrency(penawaran.harga_dasar) }}
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total OAT</div>
          <div class="mt-3 text-3xl font-bold tracking-tight text-amber-600">
            {{ formatCurrency(totalOAT) }}
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">PPN 11%</div>
          <div class="mt-3 text-3xl font-bold tracking-tight text-sky-600">
            {{ formatCurrency(ppn) }}
          </div>
        </div>

        <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-green-600">Grand Total</div>
          <div class="mt-3 text-3xl font-bold tracking-tight text-green-700">
            {{ formatCurrency(grandTotal) }}
          </div>
        </div>
      </div>

      <!-- Informasi Harga Produk -->
      <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-slate-800">Informasi Harga Produk</h3>
            <p class="mt-1 text-sm text-slate-500">Referensi harga produk yang dipakai pada penawaran ini.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          <InfoCard label="Periode Awal" :value="formatDate(penawaran.produk_harga?.periode_awal)" />
          <InfoCard label="Periode Akhir" :value="formatDate(penawaran.produk_harga?.periode_akhir)" />
          <InfoCard label="Harga COGS" :value="formatCurrency(penawaran.produk_harga?.harga_cogs)" big />
          <InfoCard label="Harga Price List" :value="formatCurrency(penawaran.produk_harga?.harga_price_list_pe)" big />
          <InfoCard label="Harga BM" :value="formatCurrency(penawaran.produk_harga?.harga_bm)" big />
          <InfoCard label="Harga OM" :value="formatCurrency(penawaran.produk_harga?.harga_om)" big />
        </div>
      </div>

      <!-- Informasi Umum -->
      <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Informasi Umum Penawaran</h3>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
          <div class="space-y-3">
            <DetailRow label="Customer" :value="penawaran.customer?.nama_perusahaan || '-'" />
            <DetailRow label="Cabang" :value="penawaran.cabang?.nama_cabang || '-'" />
            <DetailRow label="Metode" :value="penawaran.metode || '-'" />
            <DetailRow label="Ketentuan Order" :value="penawaran.order_method || '-'" />
            <DetailRow
              label="Masa Berlaku"
              :value="`${formatDate(penawaran.masa_berlaku)} - ${formatDate(penawaran.sampai_dengan)}`"
            />
            <DetailRow label="Tipe Pembayaran" :value="penawaran.tipe_pembayaran || '-'" />
            <DetailRow label="Lokasi Kirim" :value="penawaran.lokasi_pengiriman || '-'" />
            <DetailRow label="Syarat & Ketentuan" :value="penawaran.syarat_ketentuan || '-'" />
          </div>

          <div class="space-y-3">
            <DetailRow label="Refund / Volume" :value="formatCurrency(penawaran.refund)" bigValue />
            <DetailRow label="Other Cost / Volume" :value="formatCurrency(penawaran.other_cost)" bigValue />
            <DetailRow label="Diskon" :value="formatCurrency(penawaran.discount)" bigValue />
            <DetailRow label="OAT / Volume" :value="formatCurrency(penawaran.oat)" bigValue />
            <DetailRow label="Toleransi Penyusutan" :value="`${penawaran.toleransi_penyusutan || 0}%`" />
            <DetailRow label="Keterangan" :value="penawaran.keterangan || '-'" />
            <DetailRow label="Catatan" :value="penawaran.catatan || '-'" />
          </div>
        </div>
      </div>

      <!-- Analisa Margin -->
      <!-- <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
          <h3 class="text-lg font-bold text-slate-800">Analisa Margin</h3>
          <p class="mt-1 text-sm text-slate-500">Margin dihitung tanpa PPN.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          <InfoCard label="Net Sell / vol" :value="formatCurrency(netSellPerVol)" big />
          <InfoCard label="COGS / vol" :value="formatCurrency(cogsPerVol)" big />
          <InfoCard label="OAT / vol" :value="formatCurrency(oatPerVol)" big />
          <InfoCard
            label="Gross Margin / vol"
            :value="`${formatCurrency(grossMarginPerVol)} (${grossMarginPct.toFixed(2)}%)`"
            big
          />
          <InfoCard
            label="Net Margin / vol"
            :value="`${formatCurrency(netMarginPerVol)} (${netMarginPct.toFixed(2)}%)`"
            big
          />
          <InfoCard label="Gross Profit (total)" :value="formatCurrency(grossProfit)" big />
          <InfoCard label="Net Profit (total)" :value="formatCurrency(netProfit)" big />
        </div>
      </div> -->

      <!-- Rincian Item -->
      <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h3 class="text-lg font-bold text-slate-800">Rincian Item</h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Produk</th>
                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Volume</th>
                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Harga Dasar</th>
                <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Jumlah</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="item in penawaran.items"
                :key="item.id_penawaran_item"
                class="border-t border-slate-100 transition hover:bg-slate-50"
              >
                <td class="px-5 py-4">
                  <div class="font-semibold text-slate-800">
                    {{ item.produk?.nama_produk || '-' }}
                  </div>
                  <div class="mt-1 text-sm text-slate-500">
                    {{ item.produk?.jenis?.nama || '-' }} /
                    {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                    {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                  </div>
                </td>

                <td class="px-5 py-4 text-right text-lg font-semibold text-slate-700">
                  {{ formatNumber(item.volume_order) }}
                </td>

                <td class="px-5 py-4 text-right text-xl font-bold text-slate-800">
                  {{ formatCurrency(penawaran.harga_dasar) }}
                </td>

                <td class="px-5 py-4 text-right text-xl font-bold text-emerald-700">
                  {{ formatCurrency(calcJumlah(item)) }}
                </td>
              </tr>
            </tbody>

            <tfoot class="bg-slate-50">
              <tr>
                <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-slate-600">Subtotal</td>
                <td class="px-5 py-3 text-right text-xl font-bold text-slate-800">{{ formatCurrency(subtotal) }}</td>
              </tr>

              <tr>
                <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-slate-600">Diskon</td>
                <td class="px-5 py-3 text-right text-xl font-bold text-red-600">-{{ formatCurrency(totalDiskon) }}</td>
              </tr>

              <tr>
                <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-slate-600">Total OAT</td>
                <td class="px-5 py-3 text-right text-xl font-bold text-amber-600">{{ formatCurrency(totalOAT) }}</td>
              </tr>

              <tr>
                <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-slate-600">PPN 11%</td>
                <td class="px-5 py-3 text-right text-xl font-bold text-sky-600">{{ formatCurrency(ppn) }}</td>
              </tr>

              <tr class="bg-green-50">
                <td colspan="3" class="px-5 py-4 text-right text-base font-bold text-green-700">Grand Total</td>
                <td class="px-5 py-4 text-right text-2xl font-extrabold text-green-700">{{ formatCurrency(grandTotal) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Catatan -->
      <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-3 text-lg font-bold text-slate-800">Catatan Verifikasi BM</h3>
          <div class="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
            {{ penawaran.catatan_verifikasi || '-' }}
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-3 text-lg font-bold text-slate-800">Catatan Verifikasi OM</h3>
          <div class="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
            {{ penawaran.catatan_om || '-' }}
          </div>
        </div>
      </div>

      <!-- Tombol -->
      <div class="flex flex-wrap gap-3">
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
import { ref, onMounted, computed, defineComponent, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import axios from 'axios'
import Button from '@/components/Base/Button'

const route = useRoute()
const router = useRouter()
const penawaran = ref<any>({})
const id = Number(route.params.id)
const loading = ref(false)

const InfoCard = defineComponent({
  name: 'InfoCard',
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
    big: { type: Boolean, default: false },
  },
  setup(props) {
    return () =>
      h('div', { class: 'rounded-2xl border border-slate-200 bg-slate-50 p-4' }, [
        h('div', { class: 'text-xs font-semibold uppercase tracking-wide text-slate-400' }, props.label),
        h(
          'div',
          {
            class: props.big
              ? 'mt-2 text-2xl font-bold tracking-tight text-slate-800'
              : 'mt-2 text-base font-semibold text-slate-700',
          },
          props.value
        ),
      ])
  },
})

const DetailRow = defineComponent({
  name: 'DetailRow',
  props: {
    label: { type: String, required: true },
    value: { type: String, required: true },
    bigValue: { type: Boolean, default: false },
  },
  setup(props) {
    return () =>
      h('div', { class: 'grid grid-cols-3 gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3' }, [
        h('div', { class: 'text-sm font-medium text-slate-500' }, props.label),
        h(
          'div',
          {
            class: props.bigValue
              ? 'col-span-2 text-xl font-bold tracking-tight text-slate-800'
              : 'col-span-2 text-sm font-semibold text-slate-700',
          },
          `: ${props.value}`
        ),
      ])
  },
})

async function fetchPenawaran() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/penawarans-proenergi/${id}`)
    penawaran.value = data
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data penawaran', 'error')
  } finally {
    loading.value = false
  }
}

function calcJumlah(item: any) {
  const vol = Number(item.volume_order) || 0
  const hargaDasar = Number(penawaran.value.harga_dasar) || 0
  const refund = Number(penawaran.value.refund) || 0
  const otherCost = Number(penawaran.value.other_cost) || 0

  return vol * hargaDasar - vol * refund - vol * otherCost
}

const totalVolume = computed(() =>
  Array.isArray(penawaran.value.items)
    ? penawaran.value.items.reduce((s: number, it: any) => s + (Number(it.volume_order) || 0), 0)
    : 0
)

const subtotal = computed(() =>
  Array.isArray(penawaran.value.items)
    ? penawaran.value.items.reduce((sum: number, it: any) => sum + calcJumlah(it), 0)
    : 0
)

const totalDiskon = computed(() => Number(penawaran.value.discount) || 0)
const oatPerVol = computed(() => Number(penawaran.value.oat) || 0)
const totalOAT = computed(() => oatPerVol.value * totalVolume.value)
const dpp = computed(() => subtotal.value - totalDiskon.value + totalOAT.value)
const ppn = computed(() => dpp.value * 0.11)
const grandTotal = computed(() => dpp.value + ppn.value)

const netSellPerVol = computed(() => {
  const jual = Number(penawaran.value.harga_dasar) || 0
  const refund = Number(penawaran.value.refund) || 0
  const other = Number(penawaran.value.other_cost) || 0
  return jual - refund - other
})

const cogsPerVol = computed(() => Number(penawaran.value.produk_harga?.harga_cogs) || 0)
const grossMarginPerVol = computed(() => netSellPerVol.value - cogsPerVol.value)
const netMarginPerVol = computed(() => netSellPerVol.value - cogsPerVol.value - oatPerVol.value)

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

const grossProfit = computed(() => grossMarginPerVol.value * totalVolume.value)
const netProfit = computed(() => netMarginPerVol.value * totalVolume.value)

async function verifikasi() {
  const result = await Swal.fire({
    title: 'Verifikasi Penawaran OM?',
    input: 'textarea',
    inputLabel: 'Catatan Verifikasi',
    inputPlaceholder: 'Masukkan catatan jika ada...',
    showCancelButton: true,
    confirmButtonText: 'Ya, Disetujui',
    cancelButtonText: 'Batal',
    preConfirm: val => val || 'Tanpa catatan',
  })

  if (!result.isConfirmed) return

  await axios.patch(`/api/penawarans-proenergi/${id}/verifikasi-om`, { catatan: result.value })
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
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  await axios.patch(`/api/penawarans-proenergi/${id}/tolak-om`, { catatan: result.value })
  Swal.fire('Ditolak', 'Penawaran ditolak', 'success')
  fetchPenawaran()
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

function formatCurrency(v: number | string = 0) {
  const n = Number(v) || 0
  return `Rp. ${n.toLocaleString('id-ID')}`
}

function formatNumber(v: number | string = 0) {
  const n = Number(v) || 0
  return n.toLocaleString('id-ID')
}

function formatStatus(status: string) {
  if (!status) return '-'

  switch (status) {
    case 'draft':
      return 'Draft'
    case 'waiting_branch_manager':
      return 'Menunggu BM'
    case 'approved_bm':
      return 'Approved BM'
    case 'approved_om':
      return 'Approved OM'
    case 'rejected_bm':
      return 'Ditolak BM'
    case 'rejected_om':
      return 'Ditolak OM'
    default:
      return status
    }
}

function formatDisposisi(disposisi: string | number) {
  switch (String(disposisi)) {
    case '1':
      return 'Draft'
    case '2':
      return 'Menunggu BM'
    case '3':
      return 'Menunggu OM'
    case '4':
      return 'Disetujui OM'
    case '5':
      return 'Ditolak BM'
    case '6':
      return 'Ditolak OM'
    default:
      return '-'
  }
}

function goBack() {
  router.back()
}

onMounted(fetchPenawaran)
</script>