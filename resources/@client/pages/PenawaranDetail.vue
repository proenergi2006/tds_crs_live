<template>
  <div class="p-6 intro-y">
    <!-- Loading -->
    <div v-if="!penawaran.id_penawaran" class="text-center py-16">
      <svg class="animate-spin h-8 w-8 mx-auto text-slate-400" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
      </svg>
      <p class="mt-3 text-slate-500">Memuat detail penawaran…</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex items-center">
        <Button variant="outline-secondary" @click="goBack" class="inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 -ml-0.5" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Kembali
        </Button>

        <div class="ml-4">
          <h1 class="text-2xl font-semibold leading-tight">
            Detail Penawaran <span class="text-slate-600">{{ penawaran.nomor_penawaran }}</span>
          </h1>
        </div>
      </div>

      <!-- Stepper -->
      <div class="bg-white shadow rounded-lg p-4">
        <div class="flex items-center justify-between text-xs">
          <div class="flex-1 flex items-center">
            <div :class="stepDotClass(1)" />
            <span class="ml-2 font-medium">Draft</span>
          </div>
          <div class="flex-1 h-0.5 mx-2" :class="stepBarClass(2)" />
          <div class="flex-1 flex items-center">
            <div :class="stepDotClass(2)" />
            <span class="ml-2 font-medium">Waiting BM</span>
          </div>
          <div class="flex-1 h-0.5 mx-2" :class="stepBarClass(3)" />
          <div class="flex-1 flex items-center">
            <div :class="stepDotClass(3)" />
            <span class="ml-2 font-medium">Approved BM</span>
          </div>
          <div class="flex-1 h-0.5 mx-2" :class="stepBarClass(4)" />
          <div class="flex-1 flex items-center">
            <div :class="stepDotClass(4)" />
            <span class="ml-2 font-medium">Approved OM</span>
          </div>
        </div>
      </div>

      <!-- Informasi Umum -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kiri -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium mb-4">Informasi Umum</h2>
          <div class="grid sm:grid-cols-2 gap-y-3 gap-x-6 text-sm">
            <div><p class="text-slate-500">Customer</p><p class="font-medium">{{ penawaran.customer?.nama_perusahaan || '-' }}</p></div>
            <div><p class="text-slate-500">Cabang</p><p class="font-medium">{{ penawaran.cabang?.nama_cabang || '-' }}</p></div>
            <div><p class="text-slate-500">Metode Pengiriman</p><p class="font-medium">{{ penawaran.metode || '-' }}</p></div>
            <div><p class="text-slate-500">Ketentuan Order</p><p class="font-medium">{{ penawaran.order_method || '-' }}</p></div>
            <div><p class="text-slate-500">Tipe Pembayaran</p><p class="font-medium">{{ penawaran.tipe_pembayaran || '-' }}</p></div>
            <div><p class="text-slate-500">Masa Berlaku</p><p class="font-medium">{{ formatDate(penawaran.masa_berlaku) }} - {{ formatDate(penawaran.sampai_dengan) }}</p></div>
            <div><p class="text-slate-500">Toleransi Penyusutan</p><p class="font-medium">{{ penawaran.toleransi_penyusutan ?? 0 }}%</p></div>

            <!-- Tambahan Harga -->
            <div><p class="text-slate-500">Harga Dasar</p><p class="font-medium">{{ formatCurrency(penawaran.harga_dasar) }}</p></div>
            <div><p class="text-slate-500">Other Cost</p><p class="font-medium">{{ formatCurrency(penawaran.other_cost) }}</p></div>
            <div><p class="text-slate-500">Diskon</p><p class="font-medium text-red-600">- {{ formatCurrency(penawaran.discount) }}</p></div>
            <div><p class="text-slate-500">Refund</p><p class="font-medium text-red-600">- {{ formatCurrency(penawaran.refund) }}</p></div>
            <div><p class="text-slate-500">OAT per Volume</p><p class="font-medium">{{ formatCurrency(penawaran.oat) }} / volume</p></div>

            <div class="sm:col-span-2"><p class="text-slate-500">Lokasi Pengiriman</p><p class="font-medium">{{ penawaran.lokasi_pengiriman || '-' }}</p></div>
          </div>
        </div>

        <!-- Kanan -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium mb-4">Catatan & Keterangan</h2>
          <dl class="space-y-3 text-sm">
            <div><dt class="text-slate-500">Keterangan</dt><dd class="font-medium">{{ penawaran.keterangan || '-' }}</dd></div>
            <div><dt class="text-slate-500">Catatan</dt><dd class="font-medium">{{ penawaran.catatan || '-' }}</dd></div>
            <div><dt class="text-slate-500">Syarat & Ketentuan</dt><dd class="font-medium whitespace-pre-line">{{ penawaran.syarat_ketentuan || '-' }}</dd></div>
          </dl>

          <div class="mt-6 grid sm:grid-cols-2 gap-3 text-sm">
            <div class="bg-slate-50 rounded-lg p-3">
              <p class="text-slate-500">Catatan Verifikasi BM</p>
              <p class="font-medium whitespace-pre-line">{{ penawaran.catatan_verifikasi || '-' }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
              <p class="text-slate-500">Catatan Verifikasi OM</p>
              <p class="font-medium whitespace-pre-line">{{ penawaran.catatan_om || '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Rincian Item -->
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium mb-3">Rincian Item</h2>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-slate-600 uppercase text-xs">
            <tr>
              <th class="text-left px-4 py-2">Produk</th>
              <th class="text-right px-4 py-2">Volume</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="item in penawaran.items" :key="item.id_penawaran_item">
              <td class="px-4 py-2">
                <div class="font-medium">{{ item.produk?.nama_produk || '-' }}</div>
                <div class="text-xs text-slate-500">
                  {{ item.produk?.jenis?.nama || '-' }} /
                  {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                  {{ item.produk?.ukuran?.satuan?.nama_satuan || '' }}
                </div>
              </td>
              <td class="px-4 py-2 text-right">{{ formatNumber(item.volume_order) }}</td>
            </tr>
            <tr v-if="!penawaran.items?.length">
              <td colspan="2" class="px-4 py-6 text-center text-slate-500 text-sm">Belum ada item.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tombol Aksi -->
      <div class="flex items-center justify-end gap-2">
        <Button variant="outline-primary" @click="preview">Preview PDF</Button>
        <Button v-if="penawaran.status === 'approved_om'" variant="outline-primary" @click="previewLang('id')">Cetak Indonesia</Button>
        <Button v-if="penawaran.status === 'approved_om'" variant="outline-primary" @click="previewLang('en')">Cetak English</Button>

        <!-- Tombol Ajukan -->
        <Button
          v-if="penawaran.status === 'draft'"
          variant="primary"
          class="ml-2"
          @click="ajukanPenawaran"
        >
          Ajukan ke Branch Manager
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import axios from 'axios'
import Button from '@/components/Base/Button'

const route = useRoute()
const router = useRouter()
const penawaran = ref<any>({})
const id = Number(route.params.id)

async function fetchPenawaran() {
  try {
    const { data } = await axios.get(`/api/penawarans/${id}`)
    penawaran.value = data
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat detail penawaran', 'error')
  }
}

function formatDate(d: string) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-'
}
function formatCurrency(v: number | string = 0) {
  const n = Number(v) || 0
  return `Rp. ${n.toLocaleString('id-ID')}`
}
function formatNumber(v: number | string = 0) {
  const n = Number(v) || 0
  return n.toLocaleString('id-ID')
}

function currentStep(): number {
  const s = penawaran.value?.status
  if (s === 'approved_om') return 4
  if (s === 'approved_bm') return 3
  if (s === 'waiting_branch_manager') return 2
  return 1
}
function stepDotClass(step: number) {
  const done = currentStep() >= step
  return ['w-3 h-3 rounded-full border', done ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-slate-300'].join(' ')
}
function stepBarClass(step: number) {
  const done = currentStep() >= step
  return done ? 'bg-indigo-200' : 'bg-slate-200'
}

async function preview() {
  try {
    const response = await axios.get(`/api/penawarans/${id}/preview`, { responseType: 'blob' })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const pdfUrl = URL.createObjectURL(blob)
    window.open(pdfUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(pdfUrl), 60000)
  } catch {
    Swal.fire('Error', 'Gagal membuka PDF', 'error')
  }
}

async function previewLang(lang: 'id' | 'en') {
  try {
    const response = await axios.get(`/api/penawarans/${id}/preview`, { params: { lang }, responseType: 'blob' })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const pdfUrl = URL.createObjectURL(blob)
    window.open(pdfUrl, '_blank')
    setTimeout(() => URL.revokeObjectURL(pdfUrl), 60000)
  } catch {
    Swal.fire('Error', 'Gagal membuka PDF', 'error')
  }
}

async function ajukanPenawaran() {
  Swal.fire({
    title: 'Ajukan Penawaran?',
    text: 'Setelah diajukan, penawaran akan dikirim ke Branch Manager untuk verifikasi.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Ajukan',
    cancelButtonText: 'Batal',
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const { data } = await axios.patch(`/api/penawarans/${id}/ajukan`)
        Swal.fire({
          icon: 'success',
          title: 'Berhasil Diajukan',
          text: data.message || 'Penawaran berhasil diajukan ke Branch Manager.',
          timer: 2000,
          showConfirmButton: false,
        })
        await fetchPenawaran()
      } catch (e: any) {
        Swal.fire('Error', e.response?.data?.message || 'Gagal mengajukan penawaran', 'error')
      }
    }
  })
}

function goBack() {
  router.push({ name: 'penawarans-list' })
}

onMounted(fetchPenawaran)
</script>
