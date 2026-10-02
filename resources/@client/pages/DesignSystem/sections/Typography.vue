<script setup lang="ts">
import ShowcaseSection from "../components/ShowcaseSection.vue";

interface TextStyle {
  class: string;
  sample: string;
  family: string;
  size: string;
  weight: number;
  lineHeight: number;
  tracking?: string;
  transform?: string;
  usage: string;
  emphasis?: boolean;
}

const textStyles: TextStyle[] = [
  { class: "text-screen-title", sample: "JUDUL HALAMAN UTAMA APLIKASI", family: "Ubuntu", size: "32px", weight: 700, lineHeight: 1.2, usage: "Judul halaman utama" },
  { class: "text-section-title", sample: "JUDUL SEKSI ATAU MODAL UTAMA", family: "Ubuntu", size: "20px", weight: 600, lineHeight: 1.25, usage: "Judul seksi / modal" },
  { class: "text-card-title", sample: "JUDUL KARTU ATAU SUB-GRUP", family: "Ubuntu", size: "16px", weight: 600, lineHeight: 1.3, usage: "Judul kartu / sub-grup" },
  { class: "text-overline", sample: "OVERLINE KATEGORI KONTEKSTUAL", family: "Ubuntu", size: "11px", weight: 600, lineHeight: 1.4, tracking: "0.12em", transform: "uppercase", usage: "Overline kontekstual / judul sub-grup di dalam card" },
  { class: "text-form-label", sample: "LABEL INPUT FORM / TABEL HEADER", family: "Ubuntu", size: "13px", weight: 500, lineHeight: 1.4, tracking: "0.04em", transform: "uppercase", usage: "Label input form / header tabel" },
  { class: "text-body-lg", sample: "Paragraf pengantar atau teks sambutan yang sedikit lebih besar dari isi standar.", family: "Lexend", size: "16px", weight: 400, lineHeight: 1.6, usage: "Paragraf pengantar di bawah judul" },
  { class: "text-body", sample: "Teks isi standar untuk paragraf, deskripsi, dan sel sel di dalam tabel aplikasi.", family: "Lexend", size: "14px", weight: 400, lineHeight: 1.5, usage: "Paragraf default / isi sel tabel" },
  { class: "text-body-strong", sample: "Teks isi yang ditebalkan untuk penekanan inline di dalam paragraf.", family: "Lexend", size: "14px", weight: 600, lineHeight: 1.5, usage: "Penekanan inline (bukan heading tersendiri)", emphasis: true },
  { class: "text-caption", sample: "Helper text, pesan error pada form, atau metadata tanggal dan waktu.", family: "Lexend", size: "12px", weight: 400, lineHeight: 1.4, usage: "Helper text / pesan error / metadata" },
];

interface NumericStyle {
  class: string;
  family: string;
  size: string;
  weight: number;
  lineHeight: number;
  tracking: string;
  usage: string;
}

const numericStyles: NumericStyle[] = [
  { class: "num-micro", family: "Lexend", size: "11px", weight: 400, lineHeight: 1.3, tracking: "0.02em", usage: "Tabel padat, hitungan inline" },
  { class: "num-sm", family: "Lexend", size: "13px", weight: 500, lineHeight: 1.3, tracking: "0.01em", usage: "Angka / nominal default" },
  { class: "num-md", family: "Lexend", size: "16px", weight: 600, lineHeight: 1.3, tracking: "0", usage: "Nilai ditekankan (highlight card)" },
  { class: "num-lg", family: "Lexend", size: "24px", weight: 700, lineHeight: 1.2, tracking: "-0.01em", usage: "Statistik / KPI" },
  { class: "num-display", family: "Lexend", size: "36px", weight: 700, lineHeight: 1.1, tracking: "-0.02em", usage: "Metrik hero" },
];

function meta(s: TextStyle | NumericStyle): string {
  const parts = [s.family, s.size, String(s.weight), `line-height ${s.lineHeight}`];
  if (s.tracking) parts.push(`tracking ${s.tracking}`);
  return parts.join(" · ");
}
</script>

<template>
  <ShowcaseSection id="typography" title="Typography"
    description="14 gaya typography — 9 gaya teks + 5 gaya numerik, semuanya Lexend (numerik pakai tabular-nums). Nama class semantik, bukan berbasis ukuran — pilih berdasarkan peran teksnya, bukan besar-kecilnya.">
    <div>
      <p class="mb-3 text-overline">Text Styles</p>
      <div class="space-y-3.5">
        <div v-for="s in textStyles" :key="s.class"
          class="items-start gap-x-4 gap-y-1 grid grid-cols-1 lg:grid-cols-[150px_1fr_260px] pb-3.5 last:pb-0 border-slate-100 last:border-0 border-b">
          <div class="flex items-center gap-1.5">
            <code class="num-micro !text-slate-500">.{{ s.class }}</code>
            <span v-if="s.emphasis" class="text-caption !text-slate-400">(emphasis)</span>
          </div>

          <p :class="s.class" class="!m-0">{{ s.sample }}</p>

          <div>
            <p class="text-caption !text-slate-400">{{ meta(s) }}{{ s.transform ? ` · ${s.transform}` : "" }}</p>
            <p class="mt-0.5 text-caption !text-slate-500">{{ s.usage }}</p>
          </div>
        </div>
      </div>
    </div>

    <div>
      <p class="mb-3 text-overline">Numeric Styles</p>
      <p class="mb-3 text-caption !text-slate-400">Semua gaya numerik pakai Lexend dengan
        font-feature-settings "tnum" 1 (tabular-nums) — lebar tiap digit sama, jadi angka di baris berbeda tetap sejajar
        per kolom.</p>

      <div class="gap-x-6 gap-y-5 grid grid-cols-2 sm:grid-cols-5">
        <div v-for="n in numericStyles" :key="n.class">
          <p :class="n.class" class="!m-0">1.234</p>
          <p :class="n.class" class="!m-0">12.345</p>
          <code class="block mt-1.5 num-micro !text-slate-500">.{{ n.class }}</code>
          <p class="mt-0.5 text-caption !text-slate-400">{{ meta(n) }}</p>
          <p class="mt-0.5 text-caption !text-slate-500">{{ n.usage }}</p>
        </div>
      </div>
    </div>
  </ShowcaseSection>
</template>
