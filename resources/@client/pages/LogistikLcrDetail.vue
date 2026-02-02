<template>
    <div class="p-6 intro-y">
      <!-- Header -->
      <div class="flex items-center">
        <h2 class="text-lg font-medium">Detail LCR (Logistik)</h2>
        <RouterLink :to="{ name: 'logistik-lcrs' }" class="ml-auto">
          <Button variant="outline-secondary">Kembali</Button>
        </RouterLink>
      </div>
  
      <!-- 1. General Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">1. General Information</div>
        <div class="p-5">
          <div class="overflow-x-auto">
            <table class="min-w-full border">
              <tbody>
                <tr>
                  <td class="bg-slate-100 px-3 py-2 w-56">Nama Perusahaan</td>
                  <td class="px-3 py-2">{{ row.customer?.nama_perusahaan || ('#' + row.id_customer) }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Kode Pelanggan</td>
                  <td class="px-3 py-2">{{ row.customer?.kode || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Alamat Site</td>
                  <td class="px-3 py-2 whitespace-pre-wrap">{{ row.alamat_survey || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Telepon</td>
                  <td class="px-3 py-2">{{ row.telp_survey || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Wilayah OA</td>
                  <td class="px-3 py-2">{{ row.wilayah_angkut?.destinasi || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Fax</td>
                  <td class="px-3 py-2">{{ row.fax_survey || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Website</td>
                  <td class="px-3 py-2">{{ row.website || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Tanggal Survey</td>
                  <td class="px-3 py-2">{{ formatDate(row.tgl_survey) }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Jenis Usaha</td>
                  <td class="px-3 py-2">{{ row.jenis_usaha || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Toleransi</td>
                  <td class="px-3 py-2">{{ row.toleransi || '-' }}</td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Status Logistik</td>
                  <td class="px-3 py-2">
                    <span class="px-2 py-1 rounded text-xs font-semibold" :class="statusBadgeClassByFlag(row.flag_disposisi)">
                      {{ statusLabelByFlag(row.flag_disposisi) }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="bg-slate-100 px-3 py-2">Ringkasan Logistik</td>
                  <td class="px-3 py-2 whitespace-pre-wrap">{{ row.logistik_summary || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
  
          <!-- Tabel Surveyor / PIC / Kompetitor -->
          <div class="mt-4 border rounded overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-100">
                <tr>
                  <th class="px-3 py-2 w-10">#</th>
                  <th class="px-3 py-2">Surveyor</th>
                  <th class="px-3 py-2">Penanggungjawab / Penerima di lapangan</th>
                  <th class="px-3 py-2">Jabatan</th>
                  <th class="px-3 py-2">No. HP</th>
                  <th class="px-3 py-2">Kompetitor</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(idx) in maxRowCount" :key="idx" class="border-b">
                  <td class="px-3 py-2">{{ idx }}</td>
                  <td class="px-3 py-2">{{ (row.nama_surveyor?.[idx-1]) || '-' }}</td>
                  <td class="px-3 py-2">{{ (row.picustomer?.[idx-1]?.nama) || '-' }}</td>
                  <td class="px-3 py-2">{{ (row.picustomer?.[idx-1]?.posisi) || '-' }}</td>
                  <td class="px-3 py-2">{{ (row.picustomer?.[idx-1]?.telepon) || '-' }}</td>
                  <td class="px-3 py-2">{{ (row.kompetitor?.[idx-1]) || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
  
          <!-- Hasil Produksi & Jam Operasional -->
          <div class="mt-4 border rounded overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-green-100">
                <tr>
                  <th class="px-3 py-2">Hasil Produksi</th>
                  <th class="px-3 py-2">Total Produksi / bln</th>
                  <th class="px-3 py-2">Senin - Jumat</th>
                  <th class="px-3 py-2">Sabtu</th>
                  <th class="px-3 py-2">Minggu</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="px-3 py-2">{{ (row.hasilsurv?.[0]) || '-' }}</td>
                  <td class="px-3 py-2">{{ (row.produkvol?.[0]?.volbul) || '-' }}</td>
                  <td class="px-3 py-2">{{ row.jam_operasional?.[0]?.senin_jumat || '-' }}</td>
                  <td class="px-3 py-2">{{ row.jam_operasional?.[0]?.sabtu || '-' }}</td>
                  <td class="px-3 py-2">{{ row.jam_operasional?.[0]?.minggu || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
  
      <!-- 2. Location Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">2. Location Information</div>
        <div class="p-5 grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-8">
            <div class="text-slate-500 mb-2">A. Peta</div>
            <div class="border rounded overflow-hidden h-96 bg-slate-50">
              <iframe v-if="mapUrl" :src="mapUrl" class="w-full h-full" loading="lazy"></iframe>
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                Koordinat tidak tersedia
              </div>
            </div>
          </div>
          <div class="col-span-12 md:col-span-4">
            <div class="text-slate-500 mb-2">Rute</div>
            <div class="border rounded p-3 min-h-[6rem] whitespace-pre-wrap">{{ row.rute_lokasi || '-' }}</div>
            <div class="text-slate-500 mt-4 mb-2">Catatan</div>
            <div class="border rounded p-3 min-h-[6rem] whitespace-pre-wrap">{{ row.note_lokasi || '-' }}</div>
          </div>
  
          <div class="col-span-12">
            <div class="grid grid-cols-12 gap-2">
              <div class="col-span-12 md:col-span-6">
                <div class="text-slate-500 text-xs">Link Google Maps</div>
                <div class="font-medium break-all">{{ row.link_google_maps || '-' }}</div>
              </div>
              <div class="col-span-12 md:col-span-3">
                <div class="text-slate-500 text-xs">Koordinat: Latitude</div>
                <div class="font-medium">{{ row.latitude_lokasi ?? '-' }}</div>
              </div>
              <div class="col-span-12 md:col-span-3">
                <div class="text-slate-500 text-xs">Longitude</div>
                <div class="font-medium">{{ row.longitude_lokasi ?? '-' }}</div>
              </div>
              <div class="col-span-12 md:col-span-3">
                <div class="text-slate-500 text-xs">Jarak dari Depot</div>
                <div class="font-medium">{{ row.jarak_depot || '-' }}</div>
              </div>
              <div class="col-span-12 md:col-span-3">
                <div class="text-slate-500 text-xs">Max Kapasitas Truk</div>
                <div class="font-medium">{{ row.max_truk || '-' }}</div>
              </div>
              <div class="col-span-12 md:col-span-3">
                <div class="text-slate-500 text-xs">Min. Vol. Pengiriman</div>
                <div class="font-medium">{{ row.min_vol_kirim || '-' }}</div>
              </div>
            </div>
          </div>
  
          <div class="col-span-12">
            <div class="text-slate-500 text-xs">Penjelasan Proses Bongkaran</div>
            <div class="font-medium whitespace-pre-wrap">{{ row.penjelasan_bongkar || '-' }}</div>
          </div>
        </div>
      </div>
  
      <!-- 3. Unloading Information -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">3. Unloading Information</div>
        <div class="p-5 space-y-8">
          <!-- Tangki -->
          <div>
            <div class="font-semibold mb-2">Tangki</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Tipe</th>
                    <th class="px-3 py-2">Kapasitas</th>
                    <th class="px-3 py-2">Jumlah</th>
                    <th class="px-3 py-2">Produk</th>
                    <th class="px-3 py-2">Inlet Pipa</th>
                    <th class="px-3 py-2">Ukuran</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(t, i) in (row.tangki || [])" :key="'t'+i" class="border-b">
                    <td class="px-3 py-2">{{ t.tipe || '-' }}</td>
                    <td class="px-3 py-2">{{ t.kapasitas || '-' }}</td>
                    <td class="px-3 py-2">{{ t.jumlah || '-' }}</td>
                    <td class="px-3 py-2">{{ t.produk || '-' }}</td>
                    <td class="px-3 py-2">{{ t.inlet || '-' }}</td>
                    <td class="px-3 py-2">{{ t.ukuran || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.tangki?.length)">
                    <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Pendukung</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Pompa</th>
                    <th class="px-3 py-2">Laju Alir</th>
                    <th class="px-3 py-2">P. Selang</th>
                    <th class="px-3 py-2">Valve</th>
                    <th class="px-3 py-2">Grounding</th>
                    <th class="px-3 py-2">Sinyal HP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(p, i) in (row.pendukung || [])" :key="'p'+i" class="border-b">
                    <td class="px-3 py-2">{{ p.pompa || '-' }}</td>
                    <td class="px-3 py-2">{{ p.aliran || '-' }}</td>
                    <td class="px-3 py-2">{{ p.selang || '-' }}</td>
                    <td class="px-3 py-2">{{ p.valve || '-' }}</td>
                    <td class="px-3 py-2">{{ p.ground || '-' }}</td>
                    <td class="px-3 py-2">{{ p.sinyal || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.pendukung?.length)">
                    <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Quantity (Tangki)</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Alat Ukur</th>
                    <th class="px-3 py-2">Merk</th>
                    <th class="px-3 py-2">Tera</th>
                    <th class="px-3 py-2">Masa Berlaku</th>
                    <th class="px-3 py-2">Flowmeter tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q, i) in (row.quantity_tangki || [])" :key="'qt'+i" class="border-b">
                    <td class="px-3 py-2">{{ q.alat || '-' }}</td>
                    <td class="px-3 py-2">{{ q.merk || '-' }}</td>
                    <td class="px-3 py-2">{{ q.tera || '-' }}</td>
                    <td class="px-3 py-2">{{ q.masa || '-' }}</td>
                    <td class="px-3 py-2">{{ q.flowmeter || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.quantity_tangki?.length)">
                    <td colspan="5" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Quality (Tangki)</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Min. Spec.</th>
                    <th class="px-3 py-2">Uji Lab</th>
                    <th class="px-3 py-2">COQ tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q, i) in (row.quality_tangki || [])" :key="'qlt'+i" class="border-b">
                    <td class="px-3 py-2">{{ q.spec || '-' }}</td>
                    <td class="px-3 py-2">{{ q.lab || '-' }}</td>
                    <td class="px-3 py-2">{{ q.coq || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.quality_tangki?.length)">
                    <td colspan="3" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4">
              <div class="text-slate-500 text-xs">Catatan</div>
              <div class="font-medium whitespace-pre-wrap">{{ row.catatan_tangki || '-' }}</div>
            </div>
          </div>
  
          <!-- Kapal -->
          <div>
            <div class="font-semibold mb-2">Kapal</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Tipe</th>
                    <th class="px-3 py-2">Kapasitas</th>
                    <th class="px-3 py-2">Jumlah</th>
                    <th class="px-3 py-2">Inlet Pipa</th>
                    <th class="px-3 py-2">Ukuran</th>
                    <th class="px-3 py-2">Metode</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(k, i) in (row.kapal || [])" :key="'kapal'+i" class="border-b">
                    <td class="px-3 py-2">{{ k.tipe || '-' }}</td>
                    <td class="px-3 py-2">{{ k.kapasitas || '-' }}</td>
                    <td class="px-3 py-2">{{ k.jumlah || '-' }}</td>
                    <td class="px-3 py-2">{{ k.inlet || '-' }}</td>
                    <td class="px-3 py-2">{{ k.ukuran || '-' }}</td>
                    <td class="px-3 py-2">{{ k.metode || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.kapal?.length)">
                    <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Jetty</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Max LOA</th>
                    <th class="px-3 py-2">Min PBL</th>
                    <th class="px-3 py-2">Draft (LWS)</th>
                    <th class="px-3 py-2">Kekuatan (DWT)</th>
                    <th class="px-3 py-2">Izin</th>
                    <th class="px-3 py-2">Persyaratan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(j, i) in (row.jetty || [])" :key="'jetty'+i" class="border-b">
                    <td class="px-3 py-2">{{ j.loa || '-' }}</td>
                    <td class="px-3 py-2">{{ j.pbl || '-' }}</td>
                    <td class="px-3 py-2">{{ j.lws || '-' }}</td>
                    <td class="px-3 py-2">{{ j.kekuatan || '-' }}</td>
                    <td class="px-3 py-2">{{ j.izin || '-' }}</td>
                    <td class="px-3 py-2">{{ j.syarat || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.jetty?.length)">
                    <td colspan="6" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Quantity (Kapal)</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Alat Ukur</th>
                    <th class="px-3 py-2">Merk</th>
                    <th class="px-3 py-2">Tera</th>
                    <th class="px-3 py-2">Masa Berlaku</th>
                    <th class="px-3 py-2">Flowmeter tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q, i) in (row.quantity_kapal || [])" :key="'qkapal'+i" class="border-b">
                    <td class="px-3 py-2">{{ q.alat || '-' }}</td>
                    <td class="px-3 py-2">{{ q.merk || '-' }}</td>
                    <td class="px-3 py-2">{{ q.tera || '-' }}</td>
                    <td class="px-3 py-2">{{ q.masa || '-' }}</td>
                    <td class="px-3 py-2">{{ q.flowmeter || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.quantity_kapal?.length)">
                    <td colspan="5" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4 font-semibold mb-2">Quality (Kapal)</div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                  <tr>
                    <th class="px-3 py-2">Min. Spec.</th>
                    <th class="px-3 py-2">Uji Lab</th>
                    <th class="px-3 py-2">COQ Tiap Pengiriman</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(q, i) in (row.quality_kapal || [])" :key="'qkualkapal'+i" class="border-b">
                    <td class="px-3 py-2">{{ q.spec || '-' }}</td>
                    <td class="px-3 py-2">{{ q.lab || '-' }}</td>
                    <td class="px-3 py-2">{{ q.coq || '-' }}</td>
                  </tr>
                  <tr v-if="!(row.quality_kapal?.length)">
                    <td colspan="3" class="px-3 py-2 text-center text-slate-500">-</td>
                  </tr>
                </tbody>
              </table>
            </div>
  
            <div class="mt-4">
              <div class="text-slate-500 text-xs">Catatan</div>
              <div class="font-medium whitespace-pre-wrap">{{ row.catatan_kapal || '-' }}</div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- 4. Informasi Gambar -->
      <div class="box mt-6">
        <div class="border-b px-5 py-4 bg-primary/10 font-semibold">4. Informasi Gambar</div>
  
        <div class="p-5 space-y-6">
          <div class="grid grid-cols-12 gap-5">
            <div
              v-for="(sec, idx) in imageSections"
              :key="sec.key"
              class="col-span-12 md:col-span-6"
            >
              <div class="text-sm font-semibold mb-2">
                {{ idx + 1 }}. {{ sec.label }}
              </div>
  
              <div class="border rounded p-3 min-h-[140px]">
                <template v-if="Array.isArray(row?.[sec.key]) && row[sec.key].length">
                  <div class="flex flex-wrap items-start gap-6">
                    <div
                      v-for="(it,i) in row[sec.key]"
                      :key="i"
                      class="flex flex-col items-center"
                    >
                      <img
                        :src="imgUrl(it)"
                        alt=""
                        class="w-28 h-28 object-cover rounded border cursor-zoom-in hover:opacity-90 transition"
                        @click="openViewer(sec.key, i)"
                      />
                      <div class="text-sm mt-1 text-center max-w-[8rem] break-words">
                        {{ it.caption || '-' }}
                      </div>
                    </div>
                  </div>
                </template>
                <div v-else class="text-slate-400">Tidak ada gambar.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- ===== Lightbox / Viewer ===== -->
      <div v-if="viewerOpen" class="fixed inset-0 z-[100]">
        <div class="absolute inset-0 bg-black/70" @click="closeViewer"></div>
  
        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="relative bg-white rounded shadow-xl max-w-[92vw] max-h-[92vh] w-fit">
            <!-- Close -->
            <button
              class="absolute -top-10 right-0 text-white text-2xl hover:opacity-80"
              @click="closeViewer"
              aria-label="Tutup"
              title="Tutup (Esc)"
            >×</button>
  
            <!-- Prev -->
            <button
              class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-full text-white text-3xl px-2"
              @click="prev"
              :disabled="viewerIndex<=0"
              :class="{'opacity-40 cursor-not-allowed': viewerIndex<=0}"
              aria-label="Sebelumnya"
              title="Sebelumnya (←)"
            >‹</button>
  
            <!-- Next -->
            <button
              class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-full text-white text-3xl px-2"
              @click="next"
              :disabled="viewerIndex>=viewerList.length-1"
              :class="{'opacity-40 cursor-not-allowed': viewerIndex>=viewerList.length-1}"
              aria-label="Berikutnya"
              title="Berikutnya (→)"
            >›</button>
  
            <div class="p-4">
              <div class="text-xs text-slate-600 mb-2">
                {{ viewerTitle }}
                <span v-if="viewerList.length" class="ml-1">• {{ viewerIndex+1 }} / {{ viewerList.length }}</span>
              </div>
  
              <img
                :src="imgUrl(currentImage)"
                alt=""
                class="block max-w-[86vw] max-h-[70vh] object-contain rounded"
              />
  
              <div class="text-center text-slate-800 mt-3">
                {{ currentImage?.caption || '-' }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ===== /Lightbox ===== -->
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue'
  import axios from 'axios'
  import { useRoute, RouterLink } from 'vue-router'
  import Button from '@/components/Base/Button'
  
  const route = useRoute()
  const id = Number(route.params.id)
  const row = ref<any>({})
  
  // Urutan & label section gambar
  const imageSections = [
    { key: 'kondisi_jalan',      label: 'Kondisi Jalan Menuju Lokasi' },
    { key: 'kantor_perusahaan',  label: 'Pintu Gerbang & Kantor Perusahaan' },
    { key: 'fasilitas_storage',  label: 'Fasilitas Penyimpanan' },
    { key: 'inlet_pipa',         label: 'Inlet Pipa' },
    { key: 'alat_ukur_gambar',   label: 'Alat Ukur' },
    { key: 'media_datar',        label: 'Media Datar' },
    { key: 'keterangan_lain',    label: 'Keterangan Penunjang Lain' },
    { key: 'layout_lokasi',      label: 'Peta / Layout Pabrik / Site' },
    { key: 'layout_bongkar',     label: 'Rute Pembongkaran' },
  ]
  
  // helper buat url gambar
  function imgUrl(it: any): string {
    if (!it) return ''
    // object {url, path}
    if (typeof it === 'object') {
      if (it.url && typeof it.url === 'string') return it.url
      if (it.path && typeof it.path === 'string') {
        return /^https?:\/\//i.test(it.path)
          ? it.path
          : `/storage/${it.path.replace(/^\/+/, '')}`
      }
      return ''
    }
    // string langsung
    if (typeof it === 'string') {
      if (/^https?:\/\//i.test(it)) return it
      return it.startsWith('/storage/')
        ? it
        : `/storage/${it.replace(/^\/+/, '')}`
    }
    return ''
  }
  
  // jumlah baris untuk tabel surveyor/pic/kompetitor
  const maxRowCount = computed(() => {
    const a = row.value?.nama_surveyor?.length || 0
    const b = row.value?.picustomer?.length || 0
    const c = row.value?.kompetitor?.length || 0
    return Math.max(1, a, b, c)
  })
  
  const mapUrl = computed(() => {
    const lat = parseFloat(row.value?.latitude_lokasi)
    const lon = parseFloat(row.value?.longitude_lokasi)
    if (!Number.isFinite(lat) || !Number.isFinite(lon)) return ''
    const zoom = 14
    const q = encodeURIComponent(`${lat},${lon}`)
    return `https://maps.google.com/maps?q=${q}&z=${zoom}&output=embed`
  })
  
  function statusLabelByFlag(v?: number | null) {
    if (v === 2) return 'Disetujui Logistik'
    if (v === 3) return 'Ditolak Logistik'
    return 'Menunggu Verifikasi'
  }
  function statusBadgeClassByFlag(v?: number | null) {
    if (v === 2) return 'bg-green-100 text-green-700'
    if (v === 3) return 'bg-red-100 text-red-700'
    return 'bg-amber-100 text-amber-700'
  }
  function formatDate(d?: string | null) {
    if (!d) return '-'
    const dt = new Date(d)
    return Number.isNaN(dt.getTime()) ? '-' : dt.toLocaleDateString()
  }
  
  onMounted(async () => {
    try {
      const res = await axios.get(`/api/logistik/customer-lcrs/${id}`)
      row.value = (res.data && typeof res.data === 'object' && 'data' in res.data)
        ? res.data.data
        : res.data
      console.log('LCR detail payload →', row.value)
    } catch (e:any) {
      console.error(e)
    }
  })
  
  /* ====== Lightbox / Viewer ====== */
  const viewerOpen  = ref(false)
  const viewerList  = ref<any[]>([])
  const viewerIndex = ref(0)
  const viewerTitle = ref('')
  
  const currentImage = computed(() => viewerList.value[viewerIndex.value] || null)
  
  function openViewer(sectionKey: string, index: number) {
    const list = (row.value?.[sectionKey] ?? []) as any[]
    viewerList.value  = list
    viewerIndex.value = index
    viewerTitle.value = imageSections.find(s => s.key === sectionKey)?.label || ''
    viewerOpen.value  = true
  }
  function closeViewer() { viewerOpen.value = false }
  function next() { if (viewerIndex.value < viewerList.value.length - 1) viewerIndex.value++ }
  function prev() { if (viewerIndex.value > 0) viewerIndex.value-- }
  
  function onKeydown(e: KeyboardEvent) {
    if (!viewerOpen.value) return
    if (e.key === 'Escape') closeViewer()
    if (e.key === 'ArrowRight') next()
    if (e.key === 'ArrowLeft')  prev()
  }
  watch(viewerOpen, (v) => {
    if (v) window.addEventListener('keydown', onKeydown)
    else   window.removeEventListener('keydown', onKeydown)
  })
  onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
  </script>
  