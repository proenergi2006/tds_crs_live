<template>
  <div class="min-h-screen bg-slate-100 p-6">
    <div class="mx-auto max-w-6xl">
      <!-- HERO HEADER -->
      <div
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-theme-1 via-theme-2 to-slate-700 px-6 py-6 text-white shadow-xl"
      >
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-10 left-10 h-32 w-32 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <div class="mb-2 inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm">
              <span>Detail Purchase Order Vendor</span>
            </div>
            <h1 class="text-2xl font-bold tracking-wide">
              {{ po.nomor_po || 'Nomor PO' }}
            </h1>
            <p class="mt-1 text-sm text-white/80">
              Informasi lengkap Purchase Order, rincian produk, nilai transaksi, dan status approval.
            </p>
          </div>

          <div class="flex flex-col items-start gap-2 lg:items-end">
            <div
              class="inline-flex rounded-full px-4 py-2 text-sm font-semibold"
              :class="statusBadgeClass(po.disposisi_po)"
            >
              {{ statusLabel(po.disposisi_po) }}
            </div>
            <div class="text-sm text-white/80">
              Tanggal PO: {{ formatDate(po.tanggal_inven) }}
            </div>
          </div>
        </div>
      </div>

      <!-- INFO GRID -->
      <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <!-- INFORMASI UTAMA -->
        <div class="xl:col-span-2">
          <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Lucide icon="FileText" class="h-5 w-5" />
              </div>
              <div>
                <h2 class="text-lg font-semibold text-slate-800">Informasi PO</h2>
                <p class="text-sm text-slate-500">Data utama purchase order vendor</p>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Nomor PO</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.nomor_po || '-' }}</div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal Inven</div>
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
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Terms</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">
                  {{ po.terms || '-' }} <span class="text-slate-500">({{ po.terms_day || 0 }} hari)</span>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kode Tax</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.kd_tax || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div>
          <div class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center gap-3">
              <div class="flex h-11 w-11 items-center justify-center rounded-full bg-success/10 text-success">
                <Lucide icon="ShieldCheck" class="h-5 w-5" />
              </div>
              <div>
                <h2 class="text-lg font-semibold text-slate-800">Status Approval</h2>
                <p class="text-sm text-slate-500">Tahapan persetujuan PO</p>
              </div>
            </div>

            <div class="space-y-4">
              <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-xs uppercase tracking-wide text-slate-500">Status Saat Ini</div>
                <div class="mt-2">
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                    :class="statusBadgeClass(po.disposisi_po)"
                  >
                    {{ statusLabel(po.disposisi_po) }}
                  </span>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 p-4">
                <div class="mb-2 text-xs uppercase tracking-wide text-slate-500">Flow Approval</div>

                <div class="space-y-3 text-sm">
                  <div class="flex items-center gap-3">
                    <div
                      class="h-3 w-3 rounded-full"
                      :class="po.disposisi_po >= 1 ? 'bg-warning' : 'bg-slate-300'"
                    ></div>
                    <span class="text-slate-700">Menunggu / proses CFO</span>
                  </div>

                  <div class="flex items-center gap-3">
                    <div
                      class="h-3 w-3 rounded-full"
                      :class="po.disposisi_po >= 2 ? 'bg-danger' : 'bg-slate-300'"
                    ></div>
                    <span class="text-slate-700">Menunggu / proses CEO</span>
                  </div>

                  <div class="flex items-center gap-3">
                    <div
                      class="h-3 w-3 rounded-full"
                      :class="po.disposisi_po === 4 ? 'bg-success' : 'bg-slate-300'"
                    ></div>
                    <span class="text-slate-700">Approved final</span>
                  </div>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Pastikan seluruh data PO sudah benar sebelum dikirim untuk persetujuan.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RINCIAN PRODUK -->
      <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
            <Lucide icon="Boxes" class="h-5 w-5" />
          </div>
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Rincian Produk</h2>
            <p class="text-sm text-slate-500">Daftar item produk pada purchase order</p>
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Produk</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Volume PO</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Harga Tebus</th>
                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Jumlah Harga</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200">
              <tr
                v-for="item in po.produks"
                :key="item.id_po_produk"
                class="transition-colors hover:bg-slate-50"
              >
                <td class="px-4 py-4">
                  <div class="font-medium text-slate-800">
                    {{ item.produk?.nama_produk || '-' }}
                  </div>
                  <div class="mt-1 text-sm text-slate-500">
                    Jenis: {{ item.produk?.jenis?.nama || '-' }}
                    <span class="mx-1">•</span>
                    {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                    {{ item.produk?.ukuran?.satuan?.nama_satuan || '-' }}
                  </div>
                </td>
                <td class="px-4 py-4 text-right font-medium text-slate-700">
                  {{ formatNumber(item.volume_po) }}
                </td>
                <td class="px-4 py-4 text-right font-medium text-slate-700">
                  {{ formatNumber(item.harga_tebus) }}
                </td>
                <td class="px-4 py-4 text-right font-semibold text-slate-800">
                  {{ formatNumber(item.jumlah_harga) }}
                </td>
              </tr>

              <tr v-if="!po.produks || !po.produks.length">
                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                  Tidak ada rincian produk
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TOTAL CARD -->
      <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="text-sm text-slate-500">Subtotal</div>
          <div class="mt-2 text-2xl font-bold text-slate-800">{{ formatNumber(po.subtotal) }}</div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="text-sm text-slate-500">PPN 11%</div>
          <div class="mt-2 text-2xl font-bold text-slate-800">{{ formatNumber(po.ppn11) }}</div>
        </div>

        <div class="rounded-2xl bg-gradient-to-r from-success/90 to-emerald-600 p-6 text-white shadow-sm">
          <div class="text-sm text-white/80">Total Order</div>
          <div class="mt-2 text-2xl font-bold">{{ formatNumber(po.total_order) }}</div>
        </div>
      </div>

      <!-- CATATAN & TERMS -->
      <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
              <Lucide icon="StickyNote" class="h-5 w-5" />
            </div>
            <h3 class="text-lg font-semibold text-slate-800">Catatan</h3>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line">
            {{ po.keterangan || '-' }}
          </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
              <Lucide icon="ScrollText" class="h-5 w-5" />
            </div>
            <h3 class="text-lg font-semibold text-slate-800">Terms & Condition</h3>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line">
            {{ po.terms_condition || '-' }}
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="mt-6 flex flex-wrap justify-end gap-3">
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>

        <Button variant="outline-primary" @click="preview">
          <Lucide icon="Printer" class="mr-2 h-4 w-4" />
          Preview PDF
        </Button>

        <Button
          v-if="po.disposisi_po === 0"
          variant="primary"
          :loading="loading"
          @click="approve"
        >
          <Lucide v-if="!loading" icon="Send" class="mr-2 h-4 w-4" />
          Kirim Persetujuan
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter, useRoute } from 'vue-router'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

const router = useRouter()
const route = useRoute()
const id = Number(route.params.id)

const po = ref<any>({})
const loading = ref(false)

async function fetchPo() {
  try {
    const { data } = await axios.get(`/api/vendor-pos/${id}`)
    po.value = data
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error')
  }
}

function goBack() {
  router.push({ name: 'vendor-pos-list' })
}

async function approve() {
  const res = await Swal.fire({
    title: 'Yakin kirim untuk persetujuan?',
    text: 'PO akan diteruskan ke proses approval.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, kirim',
    cancelButtonText: 'Batal',
  })

  if (!res.isConfirmed) return

  loading.value = true
  try {
    const { data } = await axios.patch(`/api/vendor-pos/${id}/approve`)
    po.value = data

    await Swal.fire({
      icon: 'success',
      title: 'PO berhasil dikirim untuk persetujuan',
      toast: true,
      position: 'top-end',
      timer: 1500,
      showConfirmButton: false,
    })

    router.push({ name: 'vendor-pos-list' })
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal mengirim', 'error')
  } finally {
    loading.value = false
  }
}

async function preview() {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, {
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    Swal.fire('Error', 'Gagal membuka PDF', 'error')
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
  return !isNaN(n) ? n.toLocaleString('id-ID') : '-'
}

function statusLabel(disposisi: number) {
  return disposisi === 0
    ? 'Draft'
    : disposisi === 1
    ? 'Menunggu Verifikasi CFO'
    : disposisi === 2
    ? 'Menunggu Verifikasi CEO'
    : disposisi === 4
    ? 'Sudah Diverifikasi CEO'
    : '-'
}

function statusBadgeClass(disposisi: number) {
  return disposisi === 0
    ? 'bg-yellow-100 text-yellow-700'
    : disposisi === 1
    ? 'bg-orange-100 text-orange-700'
    : disposisi === 2
    ? 'bg-red-100 text-red-700'
    : disposisi === 4
    ? 'bg-green-100 text-green-700'
    : 'bg-slate-100 text-slate-600'
}

onMounted(fetchPo)
</script>