<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRoute, useRouter } from 'vue-router';
import Lucide from '@/components/Base/Lucide';
import Button from '@/components/Base/Button';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const userRoleId = computed(() => auth.user?.id_role);

const route = useRoute();
const router = useRouter();
const id = Number(route.params.id);

const po = ref<any>({});
const ceoSummary = ref('');
const cfoSummary = ref('');

// ===== T&C helper =====
const termsList = computed<string[]>(() => {
  const raw = po.value?.terms_condition;
  if (!raw) return [];
  if (Array.isArray(raw)) {
    return raw.filter(Boolean);
  }
  if (typeof raw === 'string') {
    // coba parse JSON array kalau kebetulan disimpan begitu
    try {
      const j = JSON.parse(raw);
      if (Array.isArray(j)) return j.filter(Boolean);
    } catch {}
    // fallback: pecah per baris
    return raw.replace(/\r\n/g, '\n')
              .split('\n')
              .map(s => s.trim())
              .filter(Boolean);
  }
  return [];
});
const previewPdfUrl = computed(() => `/api/vendor-pos/${id}/preview`);

async function fetchPo() {
  try {
    const { data } = await axios.get(`/api/vendor-pos/${id}`);
    po.value = data;
    ceoSummary.value = data.ceo_summary || '';
    cfoSummary.value = data.cfo_summary || '';
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal memuat PO', 'error');
  }
}

function goBack() {
  router.push({ name: 'po-verification-list' });
}

async function submit(action: 'approve' | 'reject') {
  try {
    const summaryToSend =
      userRoleId.value === 2 ? ceoSummary.value :
      userRoleId.value === 3 ? cfoSummary.value : '';

    await axios.post(
      `/api/po-verification/${id}`,
      { action, summary: summaryToSend },
      { headers: { Authorization: `Bearer ${localStorage.getItem('access_token')}` } }
    );

    Swal.fire('Sukses', 'PO berhasil diverifikasi', 'success');
    goBack();
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data.errors) {
      const msgs = Object.values(e.response.data.errors).flat().join('<br/>');
      Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msgs });
    } else {
      Swal.fire('Error', e.response?.data?.message || 'Gagal verifikasi', 'error');
    }
  }
}

function formatDate(d: string) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
    : '-';
}

function formatCurrency(v: number|string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v;
  return !isNaN(n) ? `Rp. ${n.toLocaleString('id-ID')}` : '-';
}

onMounted(fetchPo);
</script>

<template>
  <div class="p-6 intro-y">
    <div v-if="!po.id_po" class="text-center py-10">
      <svg class="animate-spin h-8 w-8 mx-auto" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
      </svg>
      <p class="mt-2 text-gray-500">Memuat data PO...</p>
    </div>

    <div v-else>
      <!-- Status -->
      <div class="flex items-center mb-4">
        <template v-if="po.cfo_result === 1">
          <Lucide icon="CheckCircle" class="text-green-500 w-5 h-5 mr-2" />
          <span class="text-green-500 font-medium">Verified</span>
        </template>
        <template v-else-if="po.cfo_result === 2">
          <Lucide icon="XCircle" class="text-red-500 w-5 h-5 mr-2" />
          <span class="text-red-500 font-medium">Rejected</span>
        </template>
        <template v-else>
          <span class="text-gray-500">Pending Verification</span>
        </template>
      </div>

      <div class="flex items-center mb-4">
        <Button variant="outline-secondary" @click="goBack">Kembali</Button>
        <h2 class="text-lg font-medium ml-4">Verifikasi PO {{ po.nomor_po }}</h2>
      </div>

      <!-- PO Header -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div><strong>Nomor PO:</strong> {{ po.nomor_po }}</div>
          <div><strong>Tanggal:</strong> {{ formatDate(po.tanggal_inven) }}</div>
          <div><strong>Vendor:</strong> {{ po.vendor?.nama_vendor || '-' }}</div>
          <div><strong>Terminal:</strong> {{ po.terminal?.nama_terminal || '-' }}</div>
          <div><strong>Terms:</strong> {{ po.terms }} ({{ po.terms_day }} hari)</div>
          <div><strong>Keterangan:</strong> {{ po.keterangan || '-' }}</div>
        </div>
      </div>

      <!-- Produk -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium mb-4">Rincian Produk</h3>
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
              <tr v-for="item in po.produks" :key="item.id_po_produk" class="border-t hover:bg-slate-50">
                <td class="px-4 py-2">
                  {{ item.produk?.nama_produk || '-' }} - Jenis :
                  {{ item.produk?.jenis?.nama || '-' }}
                  ({{ item.produk?.ukuran?.nama_ukuran || '-' }}
                  {{ item.produk?.ukuran?.satuan?.nama_satuan || '-' }})
                </td>
                <td class="px-4 py-2 text-right">{{item.volume_po }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.harga_tebus) }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.jumlah_harga) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Total -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div><strong>Subtotal:</strong> {{ formatCurrency(po.subtotal) }}</div>
          <div><strong>PPN 11%:</strong> {{ formatCurrency(po.ppn11) }}</div>
          <div><strong>Total Order:</strong> {{ formatCurrency(po.total_order) }}</div>
        </div>
      </div>

      <!-- =============== T&C (Syarat & Ketentuan) =============== -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">Syarat &amp; Ketentuan</h3>
        
        </div>

        <!-- jika ada HTML full -->
        <div v-if="po.terms_condition"
             class="text-sm leading-relaxed space-y-2"
             v-html="po.terms_condition">
        </div>

        <!-- fallback: list dari teks per baris -->
        <ol v-else-if="termsList.length" class="list-decimal pl-6 space-y-2 text-sm">
          <li v-for="(t, i) in termsList" :key="i">{{ t }}</li>
        </ol>

        <div v-else class="text-slate-500 text-sm">
          Tidak ada syarat &amp; ketentuan.
        </div>
      </div>
      <!-- ======================================================== -->

      <!-- CFO Input -->
      <div
        v-if="userRoleId === 3 && po.disposisi_po === 1"
        class="bg-white shadow rounded-lg p-6 mb-6"
      >
        <h3 class="text-lg font-medium mb-4">Ringkasan Verifikasi CFO</h3>
        <textarea
          v-model="cfoSummary"
          rows="4"
          class="w-full border rounded-md p-2 mb-4"
          placeholder="Masukkan ringkasan verifikasi CFO..."
        ></textarea>
        <div class="flex space-x-4">
          <Button variant="success" @click="submit('approve')">Approve</Button>
          <Button variant="danger"  @click="submit('reject')">Reject</Button>
        </div>
      </div>

      <!-- CEO Input -->
      <div
        v-if="userRoleId === 2 && po.disposisi_po === 2 && po.cfo_result === 1"
        class="bg-white shadow rounded-lg p-6 mb-6"
      >
        <h3 class="text-lg font-medium mb-4">Ringkasan Verifikasi CEO</h3>
        <textarea
          v-model="ceoSummary"
          rows="4"
          class="w-full border rounded-md p-2 mb-4"
          placeholder="Masukkan ringkasan verifikasi CEO..."
        ></textarea>
        <div class="flex space-x-4">
          <Button variant="success" @click="submit('approve')">Approve</Button>
          <Button variant="danger"  @click="submit('reject')">Reject</Button>
        </div>
      </div>

      <!-- CFO Read-only -->
      <div v-if="po.cfo_summary" class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium mb-4">Ringkasan Verifikasi CFO</h3>
        <textarea
          readonly
          :value="po.cfo_summary"
          rows="3"
          class="w-full bg-gray-100 border rounded-md p-2 mb-4"
        ></textarea>
      </div>

      <!-- CEO Read-only -->
      <div v-if="po.ceo_summary" class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium mb-4">Ringkasan Verifikasi CEO</h3>
        <textarea
          readonly
          :value="po.ceo_summary"
          rows="3"
          class="w-full bg-gray-100 border rounded-md p-2 mb-4"
        ></textarea>
      </div>
    </div>
  </div>
</template>
