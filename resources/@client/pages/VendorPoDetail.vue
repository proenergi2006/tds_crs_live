<template>
  <div class="p-6 bg-slate-100 min-h-screen">
    <div class="max-w-3xl mx-auto">
      <!-- KARTU HEADER PO -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-semibold">Detail Vendor PO</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div><span class="font-medium">Nomor PO:</span> {{ po.nomor_po }}</div>
          <div><span class="font-medium">Tanggal Inven:</span> {{ formatDate(po.tanggal_inven) }}</div>
          <div><span class="font-medium">Vendor:</span> {{ po.vendor?.nama_vendor }}</div>
          <div><span class="font-medium">Terminal:</span> {{ po.terminal?.nama_terminal }}</div>
          <div><span class="font-medium">Terms:</span> {{ po.terms }} ({{ po.terms_day }} hari)</div>
          <div><span class="font-medium">Kode Tax:</span> {{ po.kd_tax }}</div>
        </div>
        <!-- Status PO -->
        <div class="mt-4 text-sm">
          <span class="font-medium">Status PO:</span>
          <span
            :class="{
              'text-yellow-600': po.disposisi_po === 0,
              'text-orange-600': po.disposisi_po === 1,
              'text-red-600': po.disposisi_po === 2,
              'text-green-600': po.disposisi_po === 4
            }"
          >
          {{
      po.disposisi_po === 0
        ? 'Draft'
        : po.disposisi_po === 1
        ? 'Menunggu Verifikasi CFO'
        : po.disposisi_po === 2
        ? 'Menunggu Verifikasi CEO'
        : po.disposisi_po === 4
        ? 'Sudah Diverifikasi CEO'
        : '-'
    }}
          </span>
        </div>
      </div>

      <!-- TABEL RINCIAN PRODUK -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium mb-2">Rincian Produk</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border">
            <thead class="bg-slate-50 text-xs text-slate-600 uppercase">
              <tr>
                <th class="px-4 py-2 text-left">Produk</th>
                <th class="px-4 py-2 text-right">Volume PO</th>
                <th class="px-4 py-2 text-right">Harga Tebus</th>
                <th class="px-4 py-2 text-right">Jumlah Harga</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in po.produks"
                :key="item.id_po_produk"
                class="border-t hover:bg-slate-50"
              >
              <td class="px-4 py-2 whitespace-nowrap">
  {{ item.produk?.nama_produk || '-' }} - Jenis :
  {{ item.produk?.jenis?.nama || '-' }} 
  ({{ item.produk?.ukuran?.nama_ukuran || '-' }} 
  {{ item.produk?.ukuran?.satuan?.nama_satuan || '-' }})
</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(item.volume_po) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(item.harga_tebus) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(item.jumlah_harga) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TOTAL -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div><span class="font-medium">Subtotal:</span> {{ formatNumber(po.subtotal) }}</div>
          <div><span class="font-medium">PPN 11%:</span> {{ formatNumber(po.ppn11) }}</div>
          <div><span class="font-medium">Total Order:</span> {{ formatNumber(po.total_order) }}</div>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg p-6 mb-6">
  <!-- Catatan -->
  <div class="mb-4">
    <h3 class="text-md font-semibold mb-2">Catatan</h3>
    <div class="border rounded px-4 py-2 text-sm text-slate-700 whitespace-pre-line">
      {{ po.keterangan || '-' }}
    </div>
  </div>

  <!-- Terms & Condition -->
  <div v-if="po.terms_condition">
    <h3 class="text-md font-semibold mb-2">Terms & Condition</h3>
    <div class="border rounded px-4 py-2 text-sm text-slate-700 whitespace-pre-line">
      {{ po.terms_condition }}
    </div>
  </div>
</div>

      <!-- TOMBOL AKSI -->
      <div class="flex justify-end space-x-2">
        <Button variant="outline-secondary" @click="goBack">Kembali</Button>
        <Button variant="outline-primary" @click="preview">Preview PDF</Button>
        <Button
          v-if="po.disposisi_po === 0"
          variant="primary"
          :loading="loading"
          @click="approve"
        >
          Kirim Persetujuan
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRouter, useRoute } from 'vue-router';
import Button from '@/components/Base/Button';

const router = useRouter();
const route = useRoute();
const id = Number(route.params.id);
const po = ref<any>({});
const loading = ref(false);

async function fetchPo() {
  try {
    const { data } = await axios.get(`/api/vendor-pos/${id}`);
    po.value = data;
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error');
  }
}

function goBack() {
  router.push({ name: 'vendor-pos-list' });
}

async function approve() {
  const res = await Swal.fire({
    title: 'Yakin kirim untuk persetujuan?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, kirim',
  });
  if (!res.isConfirmed) return;

  loading.value = true;
  try {
    const { data } = await axios.patch(`/api/vendor-pos/${id}/approve`);
    po.value = data;
    await Swal.fire({
      icon: 'success',
      title: 'PO berhasil dikirim untuk persetujuan',
      toast: true,
      position: 'top-end',
      timer: 1500,
      showConfirmButton: false,
    });
    router.push({ name: 'vendor-pos-list' });
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal mengirim', 'error');
  } finally {
    loading.value = false;
  }
}

async function preview() {
  try {
    const response = await axios.get(`/vendor-pos/${id}/preview`, {
      responseType: 'blob'
    });
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url  = URL.createObjectURL(blob);
    window.open(url, '_blank');
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  } catch {
    Swal.fire('Error','Gagal membuka PDF','error');
  }
}

// format tanggal ke “DD MMMM YYYY”
function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      })
    : '-';
}

// format angka dengan ribuan
function formatNumber(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v;
  return !isNaN(n) ? n.toLocaleString('id-ID') : '-';
}

onMounted(fetchPo);
</script>
