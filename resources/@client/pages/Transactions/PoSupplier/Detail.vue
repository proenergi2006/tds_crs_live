<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DataList from '@/components/SystemDesign/Data/DataList.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi.js'

const router = useRouter()
const route = useRoute()
const { success, error } = useNotification()
const vendorPoApi = createResourceApi('/vendor-pos')

const id = Number(route.params.id)
const po = ref<any>({})
const loading = ref(true)
const approving = ref(false)

const produks = computed<any[]>(() => po.value.produks || [])

onMounted(fetchPo)

async function fetchPo() {
  loading.value = true
  try {
    const { data } = await vendorPoApi.getById(id)
    po.value = data
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal memuat data PO')
  } finally {
    loading.value = false
  }
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

  approving.value = true
  try {
    const { data } = await axios.patch(`/api/vendor-pos/${id}/approve`)
    po.value = data
    success('Berhasil', 'PO berhasil dikirim untuk persetujuan')
    router.push({ name: 'vendor-pos-list' })
  } catch (e: any) {
    error('Gagal', e.response?.data?.message || 'Gagal mengirim persetujuan')
  } finally {
    approving.value = false
  }
}

async function preview() {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 10000)
  } catch {
    error('Gagal', 'Gagal membuka preview PDF')
  }
}

function goBack() {
  router.push({ name: 'vendor-pos-list' })
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-'
}

function formatNumber(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return !isNaN(n) ? n.toLocaleString('id-ID') : '-'
}

function statusLabel(disposisi: number) {
  const map: Record<number, string> = {
    0: 'Draft',
    1: 'Menunggu Verifikasi CFO',
    2: 'Menunggu Verifikasi CEO',
    4: 'Sudah Diverifikasi CEO',
  }
  return map[disposisi] ?? '-'
}

function statusBadgeClass(disposisi: number) {
  const map: Record<number, string> = {
    0: 'bg-yellow-100 text-yellow-700',
    1: 'bg-orange-100 text-orange-700',
    2: 'bg-red-100 text-red-700',
    4: 'bg-green-100 text-green-700',
  }
  return map[disposisi] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <div class="grid grid-cols-12 gap-6 p-4">
    <div class="col-span-12 intro-x flex flex-col gap-6">

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-slate-800">Detail PO Supplier</h2>
          <p class="mt-1 text-sm text-slate-500">
            Informasi lengkap Purchase Order <code>{{ po.nomor_po }}</code>
          </p>
        </div>
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <!-- INFO UTAMA + STATUS -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
          <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
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
                  {{ po.terms || '-' }}
                  <span class="text-slate-500">({{ po.terms_day || 0 }} hari)</span>
                </div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kode Tax</div>
                <div class="mt-1 text-sm font-semibold text-slate-800">{{ po.kd_tax || '-' }}</div>
              </div>
            </div>
          </CardSection>
        </div>

        <CardSection title="Status Approval" description="Tahapan persetujuan PO" icon="ShieldCheck"
          icon-class="bg-success/10 text-success">
          <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 p-4">
              <div class="mb-3 text-xs uppercase tracking-wide text-slate-500">Flow Approval</div>
              <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                  <div class="h-3 w-3 rounded-full" :class="po.disposisi_po >= 1 ? 'bg-warning' : 'bg-slate-300'" />
                  <span class="text-slate-700">Menunggu / proses CFO</span>
                </div>
                <div class="flex items-center gap-3">
                  <div class="h-3 w-3 rounded-full" :class="po.disposisi_po >= 2 ? 'bg-danger' : 'bg-slate-300'" />
                  <span class="text-slate-700">Menunggu / proses CEO</span>
                </div>
                <div class="flex items-center gap-3">
                  <div class="h-3 w-3 rounded-full" :class="po.disposisi_po === 4 ? 'bg-success' : 'bg-slate-300'" />
                  <span class="text-slate-700">Approved final</span>
                </div>
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
              Pastikan seluruh data PO sudah benar sebelum dikirim untuk persetujuan.
            </div>

            <div class="grid grid-cols-12">
              <div class="col-span-4">
                <Button variant="outline-primary" @click="preview">
                  <Lucide icon="Printer" class="mr-2 h-4 w-4" />
                  Preview PDF
                </Button>
              </div>
              <div class="col-span-8">
                <Button v-if="po.disposisi_po === 0" variant="primary" class="w-full" :loading="approving"
                  @click="approve">
                  <Lucide v-if="!approving" icon="Send" class="mr-2 h-4 w-4" />
                  Kirim Persetujuan
                </Button>
              </div>
            </div>
          </div>
        </CardSection>
      </div>

      <!-- RINCIAN PRODUK -->
      <CardSection title="Rincian Produk" description="Daftar item produk pada purchase order" icon="Boxes"
        icon-class="bg-indigo-100 text-indigo-600">
        <DataList :loading="loading" :empty="produks.length === 0" :colspan="4" :show-footer="false">
          <template #head>
            <Table.Th>Produk</Table.Th>
            <Table.Th class="text-right">Volume PO</Table.Th>
            <Table.Th class="text-right">Harga Tebus</Table.Th>
            <Table.Th class="text-right">Jumlah Harga</Table.Th>
          </template>
          <template #body>
            <Table.Tr v-for="item in produks" :key="item.id_po_produk" class="transition hover:bg-slate-50">
              <Table.Td>
                <div class="font-medium text-slate-800">{{ item.produk?.nama_produk || '-' }}</div>
                <div class="mt-1 text-sm text-slate-500">
                  Jenis: {{ item.produk?.jenis?.nama || '-' }}
                  <span class="mx-1">•</span>
                  {{ item.produk?.ukuran?.nama_ukuran || '-' }}
                  {{ item.produk?.ukuran?.satuan?.nama_satuan || '-' }}
                </div>
              </Table.Td>
              <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.volume_po) }}</Table.Td>
              <Table.Td class="text-right font-medium text-slate-700">{{ formatNumber(item.harga_tebus) }}</Table.Td>
              <Table.Td class="text-right font-semibold text-slate-800">{{ formatNumber(item.jumlah_harga) }}</Table.Td>
            </Table.Tr>

            <!-- POS-style totals -->
            <Table.Tr class="border-t border-slate-200 bg-slate-50">
              <Table.Td :colspan="3" class="py-2.5 pr-6 text-right text-sm text-slate-500">Subtotal</Table.Td>
              <Table.Td class="py-2.5 text-right text-sm font-medium text-slate-700">{{ formatNumber(po.subtotal) }}
              </Table.Td>
            </Table.Tr>
            <Table.Tr class="bg-slate-50">
              <Table.Td :colspan="3" class="py-2.5 pr-6 text-right text-sm text-slate-500">PPN 11%</Table.Td>
              <Table.Td class="py-2.5 text-right text-sm font-medium text-slate-700">{{ formatNumber(po.ppn11) }}
              </Table.Td>
            </Table.Tr>
            <Table.Tr class="border-t-2 border-slate-300 bg-emerald-50">
              <Table.Td :colspan="3" class="py-3.5 pr-6 text-right text-sm font-semibold text-slate-800">Total Order
              </Table.Td>
              <Table.Td class="py-3.5 text-right text-base font-bold text-emerald-700">{{ formatNumber(po.total_order)
              }}</Table.Td>
            </Table.Tr>
          </template>
        </DataList>
      </CardSection>

      <!-- CATATAN & TERMS -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <CardSection title="Catatan" icon="StickyNote" icon-class="bg-amber-100 text-amber-600">
          <div
            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line">
            {{ po.keterangan || '-' }}
          </div>
        </CardSection>

        <CardSection title="Terms & Condition" icon="ScrollText" icon-class="bg-blue-100 text-blue-600">
          <div
            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 whitespace-pre-line">
            {{ po.terms_condition || '-' }}
          </div>
        </CardSection>
      </div>

    </div>
  </div>
</template>
