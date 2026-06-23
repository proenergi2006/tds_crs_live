<script setup lang="ts">
/* Section: static demo data — content copied from Verification/Penawaran/Detail,
   stripped of all frontend logic. Purpose is to showcase the global typography
   classes (font-display, font-header, font-body, ...). */

import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import Table from '@/components/Base/Table';

const hargaProdukFields = [
  { label: 'Periode Awal', value: '01 Januari 2026' },
  { label: 'Periode Akhir', value: '31 Maret 2026' },
  { label: 'Harga COGS', value: 'Rp. 8.500.000' },
  { label: 'Harga Price List', value: 'Rp. 10.250.000' },
  { label: 'Harga BM', value: 'Rp. 9.100.000' },
  { label: 'Harga OM', value: 'Rp. 9.600.000' },
];

const infoGroups = [
  {
    label: 'Identitas',
    fields: [
      { label: 'Customer', value: 'PT Batu Makmur Sentosa' },
      { label: 'Cabang', value: 'Jakarta' },
    ],
  },
  {
    label: 'Ketentuan Transaksi',
    fields: [
      { label: 'Metode', value: 'Franco' },
      { label: 'Ketentuan Order', value: 'Per Tonase' },
      { label: 'Masa Berlaku', value: '01 Januari 2026 - 31 Maret 2026', span: 2 },
      { label: 'Tipe Pembayaran', value: 'Tempo 30 Hari' },
      { label: 'Status', value: 'Menunggu Branch Manager' },
      { label: 'Lokasi Kirim', value: 'Gudang Cakung, Jakarta Timur' },
    ],
  },
];

const hargaFields = [
  { label: 'Harga Penawaran', value: 'Rp. 9.800.000' },
  { label: 'Refund / Volume', value: 'Rp. 150.000' },
  { label: 'Other Cost / Volume', value: 'Rp. 75.000' },
  { label: 'Diskon', value: '-Rp. 500.000', tone: 'red' },
  { label: 'OAT / Volume', value: 'Rp. 120.000' },
  { label: 'Toleransi Penyusutan', value: '2%' },
  { label: 'Subtotal', value: 'Rp. 94.750.000' },
  { label: 'Total OAT', value: 'Rp. 1.200.000' },
  { label: 'PPN 11%', value: 'Rp. 10.444.500' },
  { label: 'Grand Total', value: 'Rp. 105.394.500', tone: 'green' },
];

const items = [
  { name: 'Batu Split 1-2', jenis: 'Agregat', ukuran: '1-2 cm / Ton', persen: '60', volume: '6.000' },
  { name: 'Abu Batu', jenis: 'Agregat', ukuran: '0-5 mm / Ton', persen: '25', volume: '2.500' },
  { name: 'Batu Screening', jenis: 'Agregat', ukuran: '5-10 mm / Ton', persen: '15', volume: '1.500' },
];

const approvalSteps = [
  { title: 'Draft', description: 'Penawaran dibuat dan masih dapat diubah.', state: 'completed', timestamp: '20 Jun 2026, 09:12' },
  { title: 'Waiting BM', description: 'Menunggu verifikasi dari Branch Manager.', state: 'active', timestamp: '' },
  { title: 'Approved BM', description: 'Disetujui Branch Manager, diteruskan ke Operations Manager.', state: 'pending', timestamp: '' },
  { title: 'Approved OM', description: 'Disetujui Operations Manager. Penawaran final.', state: 'pending', timestamp: '' },
];

/* Section: type scale legend — name + sample for every typography class. */
const typeScale = [
  { cls: 'font-display', role: 'Judul halaman / hero', sample: 'Detail Penawaran' },
  { cls: 'font-header', role: 'Judul kartu / section', sample: 'Informasi Umum' },
  { cls: 'font-section', role: 'Judul sub-grup dalam kartu', sample: 'Ketentuan Transaksi' },
  { cls: 'font-eyebrow', role: 'Overline di atas judul', sample: 'Modul Penawaran' },
  { cls: 'font-label', role: 'Label field / kolom', sample: 'Tipe Pembayaran' },
  { cls: 'font-lead', role: 'Paragraf pendukung judul', sample: 'Informasi lengkap penawaran beserta rincian harga.' },
  { cls: 'font-body', role: 'Paragraf / teks tabel default', sample: 'Penawaran ini berlaku selama periode yang tercantum.' },
  { cls: 'font-strong', role: 'Nilai yang ditekankan', sample: 'PT Batu Makmur Sentosa' },
  { cls: 'font-caption', role: 'Metadata samar', sample: 'Terakhir diperbarui 23 Jun 2026' },
];

/* Section: numeric scale — Oswald + tabular figures, kecil sampai display. */
const numericScale = [
  { cls: 'font-num-sm', role: 'Tabel padat / hitungan inline', sample: '1.250' },
  { cls: 'font-num', role: 'Angka / nominal default', sample: 'Rp. 9.800.000' },
  { cls: 'font-num-lg', role: 'Nilai yang ditekankan (kartu)', sample: 'Rp. 105.394.500' },
  { cls: 'font-num-display', role: 'Angka statistik / KPI', sample: '1.284' },
  { cls: 'font-num-display-lg', role: 'Metrik hero', sample: '98,6%' },
];

/* Section: KPI stat cards — konteks nyata pemakaian angka display. */
const stats = [
  { label: 'Total Penawaran', value: '1.284', delta: '+12% bln ini', icon: 'FileText', tone: 'text-indigo-600 bg-indigo-100' },
  { label: 'Nilai Disetujui', value: 'Rp. 4,2 M', delta: '+8% bln ini', icon: 'Wallet', tone: 'text-emerald-600 bg-emerald-100' },
  { label: 'Rasio Approval', value: '98,6%', delta: 'stabil', icon: 'ShieldCheck', tone: 'text-blue-600 bg-blue-100' },
];
</script>

<template>
  <div class="page-content-wrapper">
    <div class="intro-x flex flex-col gap-6">

      <!-- TYPE SCALE LEGEND -->
      <section class="rounded-lg bg-white p-6 shadow-sm">
        <h2 class="font-header">Skala Typography</h2>
        <p class="font-body mt-1">
          Setiap baris memakai class-nya sendiri. Nama class ditandai di kanan.
        </p>

        <div class="mt-5 divide-y divide-slate-100">
          <div v-for="t in typeScale" :key="t.cls"
            class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
            <div :class="t.cls">{{ t.sample }}</div>
            <div class="flex shrink-0 items-center gap-2">
              <span class="font-caption hidden sm:inline">{{ t.role }}</span>
              <code class="cls-tag">.{{ t.cls }}</code>
            </div>
          </div>
        </div>
      </section>

      <!-- KPI STAT CARDS — angka display dalam konteks -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div v-for="s in stats" :key="s.label" class="rounded-lg bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="font-label">{{ s.label }}</span>
            <div class="flex h-9 w-9 items-center justify-center rounded-full" :class="s.tone">
              <Lucide :icon="(s.icon as any)" class="h-4 w-4" />
            </div>
          </div>
          <div class="font-num-display mt-3">{{ s.value }}</div>
          <div class="font-caption mt-1">{{ s.delta }}</div>
        </div>
      </div>

      <!-- NUMERIC SCALE LEGEND -->
      <section class="rounded-lg bg-white p-6 shadow-sm">
        <h2 class="font-header">Skala Angka</h2>
        <p class="font-body mt-1">
          Khusus figur: Oswald dengan <em>tabular figures</em> agar rata di kolom.
        </p>

        <div class="mt-5 divide-y divide-slate-100">
          <div v-for="n in numericScale" :key="n.cls"
            class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
            <div :class="n.cls">{{ n.sample }}</div>
            <div class="flex shrink-0 items-center gap-2">
              <span class="font-caption hidden sm:inline">{{ n.role }}</span>
              <code class="cls-tag">.{{ n.cls }}</code>
            </div>
          </div>
        </div>
      </section>

      <!-- HEADER -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="font-eyebrow">Modul Penawaran <code class="cls-tag">.font-eyebrow</code></p>
          <h1 class="font-display mt-1">Detail Penawaran <code class="cls-tag">.font-display</code></h1>
          <p class="font-lead mt-1">
            Informasi lengkap penawaran <code>PNW/2026/06/0012</code> <code class="cls-tag">.font-lead</code>
          </p>
        </div>
        <Button variant="outline-secondary">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- KIRI -->
        <div class="space-y-6 xl:col-span-2">

          <!-- Informasi Harga Produk -->
          <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center gap-3 p-6">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <Lucide icon="Tag" class="h-5 w-5" />
              </div>
              <div class="flex items-center gap-2">
                <h2 class="font-header">Informasi Harga Produk</h2>
                <code class="cls-tag">.font-header</code>
              </div>
            </div>
            <hr class="mb-4" />
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-6 pb-6 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="(f, i) in hargaProdukFields" :key="f.label">
                <dt class="font-label">
                  {{ f.label }}
                  <code v-if="i === 0" class="cls-tag">.font-label</code>
                </dt>
                <dd class="font-strong mt-1">
                  {{ f.value }}
                  <code v-if="i === 0" class="cls-tag">.font-strong</code>
                </dd>
              </div>
            </dl>
          </section>

          <!-- Informasi Umum -->
          <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center gap-3 p-6">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Lucide icon="FileText" class="h-5 w-5" />
              </div>
              <h2 class="font-header">Informasi Umum</h2>
            </div>
            <hr class="mb-4" />
            <div class="space-y-6 px-6 pb-6">
              <div v-for="(group, gi) in infoGroups" :key="group.label"
                :class="gi > 0 ? 'border-t border-slate-100 pt-6' : ''">
                <h3 class="font-section mb-4 flex items-center gap-2">
                  {{ group.label }}
                  <code v-if="gi === 0" class="cls-tag">.font-section</code>
                </h3>
                <dl class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                  <div v-for="f in group.fields" :key="f.label" :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                    <dt class="font-label">{{ f.label }}</dt>
                    <dd class="font-strong mt-1 whitespace-pre-line">{{ f.value }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </section>

          <!-- Rincian Harga -->
          <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center gap-3 p-6">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                <Lucide icon="Wallet" class="h-5 w-5" />
              </div>
              <h2 class="font-header">Rincian Harga</h2>
            </div>
            <hr class="mb-4" />
            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-6 pb-6 sm:grid-cols-2 lg:grid-cols-3">
              <div v-for="(f, i) in hargaFields" :key="f.label">
                <dt class="font-label">{{ f.label }}</dt>
                <dd class="font-num mt-1"
                  :class="f.tone === 'red' ? '!text-red-600' : f.tone === 'green' ? '!text-emerald-600' : ''">
                  {{ f.value }}
                  <code v-if="i === 0" class="cls-tag">.font-num</code>
                </dd>
              </div>
            </dl>
          </section>

          <!-- Rincian Item -->
          <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center gap-3 p-6">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <Lucide icon="Boxes" class="h-5 w-5" />
              </div>
              <h2 class="font-header">Rincian Item</h2>
            </div>
            <hr class="mb-4" />
            <div class="px-6 pb-6">
              <Table>
                <Table.Thead>
                  <Table.Tr>
                    <Table.Th class="font-label">Produk</Table.Th>
                    <Table.Th class="font-label w-28 text-right">Persen</Table.Th>
                    <Table.Th class="font-label w-40 text-right">Volume</Table.Th>
                  </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                  <Table.Tr v-for="item in items" :key="item.name" class="transition hover:bg-slate-50">
                    <Table.Td>
                      <div class="font-strong">{{ item.name }}</div>
                      <div class="font-caption mt-0.5">
                        {{ item.jenis }} <span class="mx-1">·</span> {{ item.ukuran }}
                      </div>
                    </Table.Td>
                    <Table.Td class="font-num-sm text-right">{{ item.persen }}%</Table.Td>
                    <Table.Td class="font-num text-right">{{ item.volume }}</Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </section>

          <!-- Catatan & Keterangan -->
          <section class="rounded-lg bg-white shadow-sm">
            <div class="flex items-center gap-3 p-6">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <Lucide icon="StickyNote" class="h-5 w-5" />
              </div>
              <h2 class="font-header">Catatan &amp; Keterangan</h2>
            </div>
            <hr class="mb-4" />
            <div class="grid grid-cols-1 gap-6 px-6 pb-6 lg:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-section">Keterangan</div>
                <p class="font-body mt-1 whitespace-pre-line">
                  Penawaran ini berlaku selama periode yang tercantum dan dapat ditinjau ulang jika
                  terjadi perubahan harga dasar produk.
                  <code class="cls-tag">.font-body</code>
                </p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="font-section">Syarat &amp; Ketentuan</div>
                <p class="font-body mt-1 whitespace-pre-line">
                  Harga belum termasuk biaya bongkar di lokasi tujuan. Toleransi penyusutan mengikuti
                  ketentuan yang disepakati kedua belah pihak.
                </p>
              </div>
            </div>
          </section>

        </div>

        <!-- KANAN: Sticky sidebar -->
        <div class="xl:col-span-1">
          <div class="sticky top-20 space-y-4">

            <!-- Status Penawaran -->
            <section class="rounded-lg bg-white shadow-sm">
              <div class="flex items-center gap-3 p-6">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-success/10 text-success">
                  <Lucide icon="ShieldCheck" class="h-5 w-5" />
                </div>
                <h2 class="font-header">Status Penawaran</h2>
              </div>
              <hr class="mb-4" />
              <ol class="space-y-5 px-6 pb-6">
                <li v-for="step in approvalSteps" :key="step.title" class="flex gap-3">
                  <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                    :class="step.state === 'completed' ? 'bg-emerald-100 text-emerald-600'
                      : step.state === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-400'">
                    <Lucide :icon="step.state === 'completed' ? 'Check' : 'Circle'" class="h-3.5 w-3.5" />
                  </div>
                  <div>
                    <div class="font-section !tracking-wide">{{ step.title }}</div>
                    <p class="font-body mt-0.5">{{ step.description }}</p>
                    <p v-if="step.timestamp" class="font-caption mt-0.5">{{ step.timestamp }}</p>
                  </div>
                </li>
              </ol>
            </section>

            <!-- Catatan Verifikasi -->
            <section class="rounded-lg bg-white shadow-sm">
              <div class="flex items-center gap-3 p-6">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                  <Lucide icon="MessageSquare" class="h-5 w-5" />
                </div>
                <h2 class="font-header">Catatan Verifikasi</h2>
              </div>
              <hr class="mb-4" />
              <div class="space-y-3 px-6 pb-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <div class="font-section">Catatan Verifikasi BM</div>
                  <p class="font-body mt-1 whitespace-pre-line">Harga sudah sesuai price list periode berjalan.</p>
                </div>
                <div class="flex flex-row gap-2">
                  <Button variant="danger" class="inline-flex w-full items-center justify-center gap-2">
                    <Lucide icon="X" class="h-4 w-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex w-full items-center justify-center gap-2">
                    <Lucide icon="Check" class="h-4 w-4" />
                    Setujui
                  </Button>
                </div>
              </div>
            </section>

          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* Small monospace chip used to highlight which typography class an element uses. */
.cls-tag {
  display: inline-block;
  margin-left: 0.375rem;
  padding: 0.0625rem 0.375rem;
  border-radius: 0.25rem;
  border: 1px solid #e0e7ff;
  background: #eef2ff;
  color: #4f46e5;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.625rem;
  line-height: 1.4;
  letter-spacing: 0;
  vertical-align: middle;
  white-space: nowrap;
}
</style>
