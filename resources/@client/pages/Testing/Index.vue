<script setup lang="ts">
import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import Table from '@/components/Base/Table';
import Stepper, { type StepItem } from '@/components/SystemDesign/Stepper/Stepper.vue';

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

function buildSteps(count: number, activeIndex: number): StepItem[] {
  return Array.from({ length: count }, (_, i): StepItem => ({
    title: `Step ${i + 1}`,
    status: i < activeIndex ? 'completed' : i === activeIndex ? 'active' : 'pending',
  }));
}

const stepperDemos: { label: string; steps: StepItem[] }[] = [
  {
    label: '2 Step — Konfirmasi Sederhana',
    steps: [
      { title: 'Isi Data', description: 'Lengkapi form pengajuan.', status: 'completed' },
      { title: 'Konfirmasi', description: 'Tinjau dan kirim.', status: 'active' },
    ],
  },
  {
    label: '3 Step — Approval Penawaran',
    steps: [
      { title: 'Draft', description: 'Penawaran dibuat.', status: 'completed', timestamp: '20 Jun 2026' },
      { title: 'Waiting BM', description: 'Menunggu verifikasi Branch Manager.', status: 'active' },
      { title: 'Approved BM', description: 'Diteruskan ke Operations Manager.', status: 'pending' },
    ],
  },
  {
    label: '5 Step — Onboarding Customer',
    steps: [
      { title: 'Corporate Details', status: 'completed' },
      { title: 'Documentation', status: 'completed' },
      { title: 'Payment Info', status: 'active' },
      { title: 'Supply Scheme', status: 'pending' },
      { title: 'Summary', status: 'pending' },
    ],
  },
  {
    label: '10 Step — Uji Skalabilitas Connector',
    steps: buildSteps(10, 6),
  },
];

const approvalSteps = [
  { title: 'Draft', description: 'Penawaran dibuat dan masih dapat diubah.', state: 'completed', timestamp: '20 Jun 2026, 09:12' },
  { title: 'Waiting BM', description: 'Menunggu verifikasi dari Branch Manager.', state: 'active', timestamp: '' },
  { title: 'Approved BM', description: 'Disetujui Branch Manager, diteruskan ke Operations Manager.', state: 'pending', timestamp: '' },
  { title: 'Approved OM', description: 'Disetujui Operations Manager. Penawaran final.', state: 'pending', timestamp: '' },
];

const typeScale = [
  { cls: 'text-screen-title', role: 'Judul halaman / hero', sample: 'Detail Penawaran' },
  { cls: 'text-section-title', role: 'Judul kartu / section', sample: 'Informasi Umum' },
  { cls: 'text-overline', role: 'Judul sub-grup dalam kartu', sample: 'Ketentuan Transaksi' },
  { cls: 'text-overline', role: 'Overline di atas judul', sample: 'Modul Penawaran' },
  { cls: 'text-form-label', role: 'Label field / kolom', sample: 'Tipe Pembayaran' },
  { cls: 'text-body-lg', role: 'Paragraf pendukung judul', sample: 'Informasi lengkap penawaran beserta rincian harga.' },
  { cls: 'text-body', role: 'Paragraf / teks tabel default', sample: 'Penawaran ini berlaku selama periode yang tercantum.' },
  { cls: 'text-body-strong', role: 'Nilai yang ditekankan', sample: 'PT Batu Makmur Sentosa' },
  { cls: 'text-caption', role: 'Metadata samar', sample: 'Terakhir diperbarui 23 Jun 2026' },
];

const numericScale = [
  { cls: 'num-micro', role: 'Tabel padat / hitungan inline', sample: '1.250' },
  { cls: 'num-sm', role: 'Angka / nominal default', sample: 'Rp. 9.800.000' },
  { cls: 'num-md', role: 'Nilai yang ditekankan (kartu)', sample: 'Rp. 105.394.500' },
  { cls: 'num-lg', role: 'Angka statistik / KPI', sample: '1.284' },
  { cls: 'num-display', role: 'Metrik hero', sample: '98,6%' },
];

const stats = [
  { label: 'Total Penawaran', value: '1.284', delta: '+12% bln ini', icon: 'FileText', tone: 'text-indigo-600 bg-indigo-100' },
  { label: 'Nilai Disetujui', value: 'Rp. 4,2 M', delta: '+8% bln ini', icon: 'Wallet', tone: 'text-emerald-600 bg-emerald-100' },
  { label: 'Rasio Approval', value: '98,6%', delta: 'stabil', icon: 'ShieldCheck', tone: 'text-blue-600 bg-blue-100' },
];
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex flex-col gap-6 intro-x">

      <section class="bg-white shadow-sm p-6 rounded-lg">
        <h2 class="text-section-title">Skala Typography</h2>
        <p class="mt-1 text-body">
          Setiap baris memakai class-nya sendiri. Nama class ditandai di kanan.
        </p>

        <div class="mt-5 divide-y divide-slate-100">
          <div v-for="t in typeScale" :key="t.cls"
            class="flex sm:flex-row flex-col sm:justify-between sm:items-center gap-1 sm:gap-6 py-3">
            <div :class="t.cls">{{ t.sample }}</div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="hidden sm:inline text-caption">{{ t.role }}</span>
              <code class="cls-tag">.{{ t.cls }}</code>
            </div>
          </div>
        </div>
      </section>

      <div class="gap-4 grid grid-cols-1 sm:grid-cols-3">
        <div v-for="s in stats" :key="s.label" class="bg-white shadow-sm p-5 rounded-lg">
          <div class="flex justify-between items-center">
            <span class="text-form-label">{{ s.label }}</span>
            <div class="flex justify-center items-center rounded-full w-9 h-9" :class="s.tone">
              <Lucide :icon="(s.icon as any)" class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-3 num-lg">{{ s.value }}</div>
          <div class="mt-1 text-caption">{{ s.delta }}</div>
        </div>
      </div>

      <section class="bg-white shadow-sm p-6 rounded-lg">
        <h2 class="text-section-title">Skala Angka</h2>
        <p class="mt-1 text-body">
          Khusus figur: Lexend dengan <em>tabular figures</em> agar rata di kolom.
        </p>

        <div class="mt-5 divide-y divide-slate-100">
          <div v-for="n in numericScale" :key="n.cls"
            class="flex sm:flex-row flex-col sm:justify-between sm:items-center gap-1 sm:gap-6 py-3">
            <div :class="n.cls">{{ n.sample }}</div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="hidden sm:inline text-caption">{{ n.role }}</span>
              <code class="cls-tag">.{{ n.cls }}</code>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white shadow-sm p-6 rounded-lg">
        <h2 class="text-section-title">Stepper Component</h2>
        <p class="mt-1 text-body">
          <code class="cls-tag">SystemDesign/Stepper</code> — arsitektur connector-based, horizontal & vertical
          memakai layout flex yang sama, otomatis menyesuaikan jumlah step.
        </p>

        <div class="flex flex-col gap-8 mt-6">
          <div v-for="demo in stepperDemos" :key="demo.label" class="p-5 border border-slate-200 rounded-xl">
            <h3 class="mb-4 text-overline">{{ demo.label }} <span class="text-caption">({{ demo.steps.length }}
                step)</span></h3>

            <div class="gap-8 grid grid-cols-1 lg:grid-cols-2">
              <div>
                <p class="mb-3 text-form-label">Horizontal</p>
                <div class="bg-slate-50 p-4 rounded-lg overflow-x-auto">
                  <div class="min-w-[560px]">
                    <Stepper :steps="demo.steps" direction="horizontal" size="sm" show-label />
                  </div>
                </div>
              </div>
              <div>
                <p class="mb-3 text-form-label">Vertical</p>
                <div class="bg-slate-50 p-4 rounded-lg">
                  <Stepper :steps="demo.steps" direction="vertical" size="sm" show-label />
                </div>
              </div>
            </div>
          </div>

          <div class="p-5 border border-slate-200 rounded-xl">
            <h3 class="mb-4 text-overline">Variasi Ukuran <span class="text-caption">(sm / md / lg)</span></h3>
            <div class="gap-6 grid grid-cols-1 lg:grid-cols-3">
              <div v-for="s in (['sm', 'md', 'lg'] as const)" :key="s">
                <p class="mb-3 text-form-label">size="{{ s }}"</p>
                <div class="bg-slate-50 p-4 rounded-lg">
                  <Stepper :steps="stepperDemos[1].steps" direction="vertical" :size="s" show-status-badge />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="flex lg:flex-row flex-col lg:justify-between lg:items-start gap-4">
        <div>
          <p class="text-overline">Modul Penawaran <code class="cls-tag">.text-overline</code></p>
          <h1 class="mt-1 text-screen-title">Detail Penawaran <code class="cls-tag">.text-screen-title</code></h1>
          <p class="mt-1 text-body-lg">
            Informasi lengkap penawaran <code>PNW/2026/06/0012</code> <code class="cls-tag">.text-body-lg</code>
          </p>
        </div>
        <Button variant="outline-secondary">
          <Lucide icon="ArrowLeft" class="mr-2 w-4 h-4" />
          Kembali
        </Button>
      </div>

      <div class="gap-6 grid grid-cols-1 xl:grid-cols-3">

        <div class="space-y-6 xl:col-span-2">

          <section class="bg-white shadow-sm rounded-lg">
            <div class="flex items-center gap-3 p-6">
              <div
                class="flex justify-center items-center bg-indigo-100 rounded-full w-11 h-11 text-indigo-600 shrink-0">
                <Lucide icon="Tag" class="w-5 h-5" />
              </div>
              <div class="flex items-center gap-2">
                <h2 class="text-section-title">Informasi Harga Produk</h2>
                <code class="cls-tag">.text-section-title</code>
              </div>
            </div>
            <hr class="mb-4" />
            <dl class="gap-x-8 gap-y-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 px-6 pb-6">
              <div v-for="(f, i) in hargaProdukFields" :key="f.label">
                <dt class="text-form-label">
                  {{ f.label }}
                  <code v-if="i === 0" class="cls-tag">.text-form-label</code>
                </dt>
                <dd class="mt-1 text-body-strong">
                  {{ f.value }}
                  <code v-if="i === 0" class="cls-tag">.text-body-strong</code>
                </dd>
              </div>
            </dl>
          </section>

          <section class="bg-white shadow-sm rounded-lg">
            <div class="flex items-center gap-3 p-6">
              <div class="flex justify-center items-center bg-primary/10 rounded-full w-11 h-11 text-primary shrink-0">
                <Lucide icon="FileText" class="w-5 h-5" />
              </div>
              <h2 class="text-section-title">Informasi Umum</h2>
            </div>
            <hr class="mb-4" />
            <div class="space-y-6 px-6 pb-6">
              <div v-for="(group, gi) in infoGroups" :key="group.label"
                :class="gi > 0 ? 'border-t border-slate-100 pt-6' : ''">
                <h3 class="flex items-center gap-2 mb-4 text-overline">
                  {{ group.label }}
                  <code v-if="gi === 0" class="cls-tag">.text-overline</code>
                </h3>
                <dl class="gap-x-8 gap-y-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                  <div v-for="f in group.fields" :key="f.label" :class="(f as any).span === 2 ? 'sm:col-span-2' : ''">
                    <dt class="text-form-label">{{ f.label }}</dt>
                    <dd class="mt-1 text-body-strong whitespace-pre-line">{{ f.value }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </section>

          <section class="bg-white shadow-sm rounded-lg">
            <div class="flex items-center gap-3 p-6">
              <div
                class="flex justify-center items-center bg-emerald-100 rounded-full w-11 h-11 text-emerald-600 shrink-0">
                <Lucide icon="Wallet" class="w-5 h-5" />
              </div>
              <h2 class="text-section-title">Rincian Harga</h2>
            </div>
            <hr class="mb-4" />
            <dl class="gap-x-8 gap-y-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 px-6 pb-6">
              <div v-for="(f, i) in hargaFields" :key="f.label">
                <dt class="text-form-label">{{ f.label }}</dt>
                <dd class="mt-1 num-sm"
                  :class="f.tone === 'red' ? '!text-red-600' : f.tone === 'green' ? '!text-emerald-600' : ''">
                  {{ f.value }}
                  <code v-if="i === 0" class="cls-tag">.num-sm</code>
                </dd>
              </div>
            </dl>
          </section>

          <section class="bg-white shadow-sm rounded-lg">
            <div class="flex items-center gap-3 p-6">
              <div
                class="flex justify-center items-center bg-indigo-100 rounded-full w-11 h-11 text-indigo-600 shrink-0">
                <Lucide icon="Boxes" class="w-5 h-5" />
              </div>
              <h2 class="text-section-title">Rincian Item</h2>
            </div>
            <hr class="mb-4" />
            <div class="px-6 pb-6">
              <Table>
                <Table.Thead>
                  <Table.Tr>
                    <Table.Th class="text-form-label">Produk</Table.Th>
                    <Table.Th class="w-28 text-form-label text-right">Persen</Table.Th>
                    <Table.Th class="w-40 text-form-label text-right">Volume</Table.Th>
                  </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                  <Table.Tr v-for="item in items" :key="item.name" class="hover:bg-slate-50 transition">
                    <Table.Td>
                      <div class="text-body-strong">{{ item.name }}</div>
                      <div class="mt-0.5 text-caption">
                        {{ item.jenis }} <span class="mx-1">·</span> {{ item.ukuran }}
                      </div>
                    </Table.Td>
                    <Table.Td class="text-right num-micro">{{ item.persen }}%</Table.Td>
                    <Table.Td class="text-right num-sm">{{ item.volume }}</Table.Td>
                  </Table.Tr>
                </Table.Tbody>
              </Table>
            </div>
          </section>

          <section class="bg-white shadow-sm rounded-lg">
            <div class="flex items-center gap-3 p-6">
              <div class="flex justify-center items-center bg-amber-100 rounded-full w-11 h-11 text-amber-600 shrink-0">
                <Lucide icon="StickyNote" class="w-5 h-5" />
              </div>
              <h2 class="text-section-title">Catatan & Keterangan</h2>
            </div>
            <hr class="mb-4" />
            <div class="gap-6 grid grid-cols-1 lg:grid-cols-2 px-6 pb-6">
              <div class="bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                <div class="text-overline">Keterangan</div>
                <p class="mt-1 text-body whitespace-pre-line">
                  Penawaran ini berlaku selama periode yang tercantum dan dapat ditinjau ulang jika
                  terjadi perubahan harga dasar produk.
                  <code class="cls-tag">.text-body</code>
                </p>
              </div>
              <div class="bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                <div class="text-overline">Syarat & Ketentuan</div>
                <p class="mt-1 text-body whitespace-pre-line">
                  Harga belum termasuk biaya bongkar di lokasi tujuan. Toleransi penyusutan mengikuti
                  ketentuan yang disepakati kedua belah pihak.
                </p>
              </div>
            </div>
          </section>

        </div>

        <div class="xl:col-span-1">
          <div class="top-6 sticky space-y-4">

            <section class="bg-white shadow-sm rounded-lg">
              <div class="flex items-center gap-3 p-6">
                <div
                  class="flex justify-center items-center bg-success/10 rounded-full w-11 h-11 text-success shrink-0">
                  <Lucide icon="ShieldCheck" class="w-5 h-5" />
                </div>
                <h2 class="text-section-title">Status Penawaran</h2>
              </div>
              <hr class="mb-4" />
              <ol class="space-y-5 px-6 pb-6">
                <li v-for="step in approvalSteps" :key="step.title" class="flex gap-3">
                  <div class="flex justify-center items-center mt-0.5 rounded-full w-6 h-6 shrink-0" :class="step.state === 'completed' ? 'bg-emerald-100 text-emerald-600'
                    : step.state === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-400'">
                    <Lucide :icon="step.state === 'completed' ? 'Check' : 'Circle'" class="w-3.5 h-3.5" />
                  </div>
                  <div>
                    <div class="text-overline !tracking-wide">{{ step.title }}</div>
                    <p class="mt-0.5 text-body">{{ step.description }}</p>
                    <p v-if="step.timestamp" class="mt-0.5 text-caption">{{ step.timestamp }}</p>
                  </div>
                </li>
              </ol>
            </section>

            <section class="bg-white shadow-sm rounded-lg">
              <div class="flex items-center gap-3 p-6">
                <div class="flex justify-center items-center bg-blue-100 rounded-full w-11 h-11 text-blue-600 shrink-0">
                  <Lucide icon="MessageSquare" class="w-5 h-5" />
                </div>
                <h2 class="text-section-title">Catatan Verifikasi</h2>
              </div>
              <hr class="mb-4" />
              <div class="space-y-3 px-6 pb-6">
                <div class="bg-slate-50 px-4 py-3 border border-slate-200 rounded-xl">
                  <div class="text-overline">Catatan Verifikasi BM</div>
                  <p class="mt-1 text-body whitespace-pre-line">Harga sudah sesuai price list periode berjalan.</p>
                </div>
                <div class="flex flex-row gap-2">
                  <Button variant="danger" class="inline-flex justify-center items-center gap-2 w-full">
                    <Lucide icon="X" class="w-4 h-4" />
                    Tolak
                  </Button>
                  <Button variant="primary" class="inline-flex justify-center items-center gap-2 w-full">
                    <Lucide icon="Check" class="w-4 h-4" />
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
