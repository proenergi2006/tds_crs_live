<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-lg font-medium">
          {{ isEdit ? 'Edit Penawaran' : 'Tambah Penawaran' }}
        </h2>
        <Button variant="outline-secondary" class="ml-auto" @click="goBack">
          Batal
        </Button>
      </div>
  
      <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="submitForm" class="space-y-4">
          <!-- Baris 1: Customer & Cabang -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Customer</label>
              <FormSelect v-model="form.id_customer" class="w-full">
                <option value="" disabled>Pilih Customer…</option>
                <option
                  v-for="c in customers"
                  :key="c.id_customer"
                  :value="c.id_customer"
                >
                  {{ c.nama_perusahaan }}
                </option>
              </FormSelect>
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Cabang</label>
              <FormSelect v-model="form.id_cabang" class="w-full">
                <option value="" disabled>Pilih Cabang…</option>
                <option
                  v-for="c in cabangs"
                  :key="c.id_cabang"
                  :value="c.id_cabang"
                >
                  {{ c.nama_cabang }}
                </option>
              </FormSelect>
            </div>
          </div>
  
          <!-- Baris 2: Masa Berlaku & Sampai Dengan -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Nomor Penawaran</label>
              <input
                v-model="form.nomor_penawaran"
                type="text"
                class="w-full border rounded p-2 bg-gray-100"
                disabled
              />
              <p class="text-xs text-gray-500">
                Nomor dibuat otomatis oleh backend.
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Masa Berlaku</label>
              <input
                v-model="form.masa_berlaku"
                type="date"
                class="w-full border rounded p-2"
                
              />
              
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Sampai Dengan</label>
              <input
                v-model="form.sampai_dengan"
                type="date"
                class="w-full border rounded p-2"
                
              />
            </div>
          </div>


          <div class="mb-4">
              <label class="block text-sm font-medium mb-1">Metode</label>
              <FormSelect v-model="form.metode" class="w-full">
                <option value="" disabled>Pilih…</option>
                <option value="trucking">Trucking</option>
                <option value="third party">Third Party</option>

              </FormSelect>
            </div>

          <div class="mb-4">
  <label class="block text-sm font-medium mb-1">Ukuran (Volume Dasar)</label>
  <input
    v-model="form.ukuran_dasar"
    type="text"
    inputmode="numeric"
    class="w-full border rounded p-2 text-right"
    placeholder="5000"
  />
  <p class="text-xs text-gray-500 text-right">Satuan dalam Liter / Kg / m³ sesuai konteks.</p>
  <input type="hidden" v-model="form.jenis_penawaran" />
</div>

       
  <!-- Rincian Item -->
<div>
  <h3 class="text-sm font-medium mb-2">Rincian Item</h3>
  <table class="min-w-full divide-y divide-slate-200 mb-4">
    <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
      <tr>
        <th class="px-4 py-2 text-left">Produk</th>
        <th class="px-4 py-2 text-right">Qty</th>
        <th class="px-4 py-2 text-right">Harga Tebus</th>
        <th class="px-4 py-2 text-right">Jumlah Harga</th>
        <th class="px-4 py-2 text-center">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50">
        <td class="px-4 py-2">
          <FormSelect v-model="item.id_produk" class="w-full" @change="checkHarga(item)">
            <option value="" disabled>Pilih Produk…</option>
            <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
              {{ p.nama_produk }} — {{ p.jenis?.nama || '-' }} /
              {{ p.ukuran?.nama_ukuran || '-' }} {{ p.ukuran?.satuan?.nama_satuan || '' }}
            </option>
          </FormSelect>
        </td>
        <td class="px-4 py-2 text-right">
          <input v-model="item.volume_order" @input="formatNumeric(item, 'volume_order', $event)"
                 type="text" inputmode="numeric"
                 class="w-full border rounded p-2 text-right" placeholder="0" />
        </td>
        <td class="px-4 py-2 text-right">
            <input
  :value="formatCurrencyInput(item.harga_tebus)"
  @input="onHargaTebusInput($event, item)"
  type="text"
  inputmode="numeric"
  class="w-full border rounded p-2 text-right"
  placeholder="0"
  readonly
/>
        </td>
        <td class="px-4 py-2 text-right">
          {{ formatCurrency(lineTotal(item)) }}
        </td>
        <td class="px-4 py-2 text-center">
          <button type="button" class="text-red-500 hover:underline" @click="removeItem(idx)">
            Hapus
          </button>
        </td>
      </tr>
    </tbody>
   
  </table>

  <div class="flex justify-end mt-4 text-sm font-semibold flex-col items-end space-y-1">
    <div class="bg-gray-100 rounded px-4 py-2 w-fit">
      Total Harga Tebus: {{ formatCurrency(grandTotalHargaTebus) }}
    </div>
  </div>
  <button type="button" class="px-3 py-1 bg-green-600 text-white rounded text-sm" @click="addItem">
    + Tambah Item
  </button>
</div>

            
          

  
          <!-- Baris 4: Tipe Pembayaran, Order Method (input), Toleransi Penyusutan -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Tipe Pembayaran</label>
              <FormSelect v-model="form.tipe_pembayaran" class="w-full">
                <option value="" disabled>Pilih…</option>
                <option value="CBD">Cash Before Delivery</option>
                <option value="CBD">Terms Of Payment</option>
                <option value="COD">Cash On Delivery</option>
             
              </FormSelect>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Order Method</label>
              <input
                v-model="form.order_method"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Order Method…"
                
              />
            </div>
           
          </div>

          <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Biaya Kirim</label>
            <input v-model="form.biaya_kirim" @input="formatNumeric(form, 'biaya_kirim', $event)"
                   type="text" inputmode="numeric" class="w-full border rounded p-2 text-right" placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Diskon</label>
            <input v-model="form.diskon" @input="formatNumeric(form, 'diskon', $event)"
       type="text" inputmode="numeric" class="w-full border rounded p-2 text-right"
       placeholder="0 (%)" />
          </div>
        </div>
  
          <!-- Baris 5: Lokasi Pengiriman, Metode, Refund -->
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Lokasi Pengiriman</label>
              <input
                v-model="form.lokasi_pengiriman"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Lokasi"
              />
            </div>
           


           
          </div>
  
          <!-- Baris 6: Other Cost, Perhitungan, Keterangan -->
          <div class="grid grid-cols-1 gap-4">
           
           
            <div>
              <label class="block text-sm font-medium mb-1">Keterangan</label>
              <input
                v-model="form.keterangan"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Keterangan…"
              />
            </div>
          </div>
  
          <!-- Baris 7: Catatan & Syarat/Ketentuan -->
          <div>
            <label class="block text-sm font-medium mb-1">Catatan</label>
            <textarea
              v-model="form.catatan"
              rows="2"
              class="w-full border rounded p-2"
              placeholder="Catatan tambahan…"
            ></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Syarat & Ketentuan</label>
            <textarea
              v-model="form.syarat_ketentuan"
              rows="3"
              class="w-full border rounded p-2"
              placeholder="Syarat dan ketentuan…"
            ></textarea>
          </div>
  
          <!-- Ringkasan Harga di Bawah Tabel -->
          <!-- Ringkasan Harga -->
          <div class="bg-slate-50 p-4 rounded-lg text-sm">
  <div class="flex justify-between mb-1">
    <span>Subtotal (setelah diskon + biaya kirim):</span>
    <span class="font-medium">{{ formatCurrency(subtotalSetelahDiskon) }}</span>
  </div>
  <div v-if="totalOAT > 0" class="flex justify-between mb-1">
    <span>Total OAT:</span>
    <span class="font-medium">{{ formatCurrency(totalOAT) }}</span>
  </div>
  <div class="flex justify-between mb-1">
    <span>PPN 11%:</span>
    <span class="font-medium">{{ formatCurrency(ppn11) }}</span>
  </div>
  <div class="flex justify-between">
    <span>Total Keseluruhan:</span>
    <span class="font-semibold">{{ formatCurrency(grandTotalWithOAT) }}</span>
  </div>
</div>
  
          <!-- Aksi Simpan -->
          <div class="flex justify-end space-x-2 mt-6">
            <button
              type="button"
              class="px-4 py-2 rounded border"
              @click="goBack"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded bg-blue-600 text-white"
              :disabled="loading"
            >
              {{ loading ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useRoute, useRouter } from 'vue-router';
import Button from '@/components/Base/Button';
import { FormSelect } from '@/components/Base/Form';

const route = useRoute();
const router = useRouter();
const idParam = route.params.id;
const isEdit = Boolean(idParam);

const loading = ref(false);
const customers = ref<any[]>([]);
const cabangs = ref<any[]>([]);
const produks = ref<any[]>([]);

interface ItemLine {
  id_produk: number | '';
  volume_order: string;
  harga_tebus: string;
  harga_price_list?: number;
}

const form = reactive({
  id_customer: '' as number | '',
  id_cabang: '' as number | '',
  nomor_penawaran: '' as string,
  masa_berlaku: '' as string,
  sampai_dengan: '' as string,
  items: [] as ItemLine[],
  tipe_pembayaran: '' as string,
  order_method: '' as string,
  lokasi_pengiriman: '' as string,
  metode: '' as string,
  refund: '' as string,
  other_cost: '' as string,
  perhitungan: '' as string,
  keterangan: '' as string,
  catatan: '' as string,
  syarat_ketentuan: '' as string,
  pengiriman_via: 'truck+kapal',
  oat: '' as string,
  ukuran_dasar: '0',
  biaya_kirim: '' as string,
  diskon: '' as string,
  jenis_penawaran: '2', 
});

onMounted(() => {
  fetchSelects();
  if (!isEdit) {
    form.items.push({ id_produk: '', volume_order: '', harga_tebus: '' });
  } else {
    fetchPenawaran();
  }
});

async function fetchSelects() {
  try {
    const [cData, caData, pData] = await Promise.all([
      axios.get('/api/customers'),
      axios.get('/api/cabangs'),
      axios.get('/api/produks?with=ukuran')
    ]);
    customers.value = cData.data.data || cData.data;
    cabangs.value = caData.data.data || caData.data;
    produks.value = pData.data.data || pData.data;
  } catch {
    Swal.fire('Error', 'Gagal memuat data master', 'error');
  }
}

async function checkHarga(item: ItemLine) {
  if (!item.id_produk || !form.masa_berlaku) return;

  try {
    const { data } = await axios.get('/api/produk-hargas/check', {
      params: {
        produk_id: item.id_produk,
        tanggal: form.masa_berlaku,
      },
    });

    if (data.found) {
      item.harga_price_list = data.harga_price_list;
      item.harga_tebus = Math.round(data.harga_price_list).toString();

      // Misalnya Anda mau hitung volume_order = ukuran_dasar
      const dasar = parseInt(form.ukuran_dasar.replace(/\./g, ''), 10) || 0;
      item.volume_order = dasar.toLocaleString('id-ID');

      Swal.fire({
        toast: true,
        icon: 'info',
        position: 'top-end',
        title: 'Harga ditemukan',
        timer: 1500,
        showConfirmButton: false,
      });
    } else {
      item.harga_price_list = 0;
      item.harga_tebus = '';
      Swal.fire({
        icon: 'warning',
        title: 'Harga tidak tersedia',
        text: 'Harga belum diinput untuk tanggal tersebut.',
      });
    }
  } catch {
    Swal.fire('Error', 'Gagal cek harga produk', 'error');
  }
}

async function fetchPenawaran() {
  try {
    const { data } = await axios.get(`/api/penawarans/${idParam}`);
    Object.assign(form, {
      id_customer: data.id_customer,
      id_cabang: data.id_cabang,
      nomor_penawaran: data.nomor_penawaran,
      masa_berlaku: data.masa_berlaku,
      sampai_dengan: data.sampai_dengan,
      tipe_pembayaran: data.tipe_pembayaran || '',
      order_method: data.order_method || '',
      lokasi_pengiriman: data.lokasi_pengiriman || '',
      metode: data.metode || '',
      refund: data.refund?.toString() || '',
      other_cost: data.other_cost?.toString() || '',
      perhitungan: data.perhitungan || '',
      keterangan: data.keterangan || '',
      catatan: data.catatan || '',
      syarat_ketentuan: data.syarat_ketentuan || '',
      pengiriman_via: data.pengiriman_via || 'truck+kapal',
      oat: data.oat?.toString() || '',
    });
    form.items = data.items.map((it: any) => ({
      id_produk: it.id_produk,
      volume_order: it.volume_order?.toLocaleString('id-ID') || '',
      harga_tebus: it.harga_tebus?.toLocaleString('id-ID') || '',
    }));
  } catch {
    Swal.fire('Error', 'Gagal memuat data penawaran', 'error');
  }
}

function addItem() {
  form.items.push({ id_produk: '', volume_order: '', harga_tebus: '' });
}

function removeItem(idx: number) {
  form.items.splice(idx, 1);
}

function formatNumeric(obj: any, field: keyof ItemLine | keyof typeof form, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '');
  const num = parseInt(raw, 10);
  const formatted = isNaN(num) ? '' : num.toLocaleString('id-ID');
  obj[field] = formatted;
}
function formatCurrencyInput(value: string | number): string {
  const num = typeof value === 'number'
    ? value
    : parseInt(value.replace(/\./g, ''), 10);
  return isNaN(num) ? '' : num.toLocaleString('id-ID');
}
function onHargaTebusInput(event: Event, item: ItemLine) {
  const raw = (event.target as HTMLInputElement).value.replace(/[^\d]/g, '');
  // Simpan ke model sebagai string angka polos (tanpa titik)
  item.harga_tebus = raw;
}

function lineTotal(item: ItemLine): number {
  const v = toNumber(item.volume_order);
  const h = toNumber(item.harga_tebus);
  return v * h;
}

function toNumber(str: string): number {
  // Hapus semua titik (anggap sebagai pemisah ribuan)
  // Ganti koma jadi titik (untuk desimal)
  const clean = (str || '0').replace(/\./g, '').replace(/,/g, '.');
  const num = parseFloat(clean);
  return isNaN(num) ? 0 : num;
}

const totalVolumePO = computed(() => {
  return form.items.reduce((sum, it) => sum + (parseInt(it.volume_order.replace(/\./g, ''), 10) || 0), 0);
});

const totalOAT = computed(() => {
  const oatPerVolume = parseInt((form.oat || '0').replace(/\./g, ''), 10) || 0;
  return oatPerVolume * totalVolumePO.value;
});

const grandTotalHargaTebus = computed(() => {
  return form.items.reduce((sum, it) => sum + lineTotal(it), 0);
});



const subtotalWithBiayaDiskon = computed(() => {
  const diskonVal = parseInt((form.diskon || '0').replace(/\./g, ''), 10) || 0;
  const biayaVal = parseInt((form.biaya_kirim || '0').replace(/\./g, ''), 10) || 0;
  let subtotal = grandTotalHargaTebus.value - diskonVal + biayaVal;
  return subtotal < 0 ? 0 : subtotal;
});

const subtotalSetelahDiskon = computed(() => {
  const diskonPersen = parseFloat((form.diskon || '0').replace(',', '.')) || 0;
  const potongan = Math.round(grandTotalHargaTebus.value * diskonPersen / 100);
  const biayaKirim = parseInt((form.biaya_kirim || '0').replace(/\./g, ''), 10) || 0;
  return (grandTotalHargaTebus.value - potongan + biayaKirim);
});

const ppn11 = computed(() => Math.round(subtotalSetelahDiskon.value * 0.11));

const grandTotalWithOAT = computed(() => {
  return subtotalSetelahDiskon.value + totalOAT.value + ppn11.value;
});

async function submitForm() {
  loading.value = true;
  try {
    const payloadItems = form.items.map((it) => ({
      id_produk: it.id_produk,
      volume_order: parseInt((it.volume_order || '').replace(/\./g, ''), 10) || 0,
      harga_tebus: parseInt((it.harga_tebus || '').replace(/\./g, ''), 10) || 0,
      jumlah_harga: lineTotal(it),
    }));

    const payload = {
      id_customer: form.id_customer,
      id_cabang: form.id_cabang,
      masa_berlaku: form.masa_berlaku,
      sampai_dengan: form.sampai_dengan,
      items: payloadItems,
      tipe_pembayaran: form.tipe_pembayaran,
      order_method: form.order_method,
      lokasi_pengiriman: form.lokasi_pengiriman,
      metode: form.metode,
      refund: parseFloat((form.refund || '0').replace(/\./g, '')) || 0,
      other_cost: parseFloat((form.other_cost || '0').replace(/\./g, '')) || 0,
      perhitungan: form.perhitungan,
      keterangan: form.keterangan,
      catatan: form.catatan,
      syarat_ketentuan: form.syarat_ketentuan,
   
      ppn11: ppn11.value,
      total_with_oat: grandTotalWithOAT.value,
      oat: parseInt((form.oat || '0').replace(/\./g, ''), 10) || 0,
      pengiriman_via: form.pengiriman_via,
      diskon: parseFloat((form.diskon || '0').replace(',', '.')) || 0,
biaya_kirim: parseInt((form.biaya_kirim || '0').replace(/\./g, ''), 10) || 0,
subtotal: subtotalSetelahDiskon.value,
jenis_penawaran: form.jenis_penawaran,
    };

    if (isEdit) {
      await axios.put(`/api/penawarans/${idParam}`, payload);
      Swal.fire({ icon: 'success', title: 'Penawaran diupdate', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false });
    } else {
      await axios.post('/api/penawarans', payload);
      Swal.fire({ icon: 'success', title: 'Penawaran dibuat', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false });
    }
    goBack();
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data.errors) {
      const msgs = Object.values(e.response.data.errors).flat().join('<br/>');
      Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msgs });
    } else {
      Swal.fire('Error', e.response?.data?.message || 'Gagal menyimpan', 'error');
    }
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({ name: 'penawarans-list' });
}

function formatCurrency(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v;
  return !isNaN(n) ? `Rp. ${n.toLocaleString('id-ID')}` : '-';
}
</script>
