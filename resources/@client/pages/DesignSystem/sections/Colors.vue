<script setup lang="ts">
import ShowcaseSection from "../components/ShowcaseSection.vue";

const tokens: { name: string; class: string; usage: string }[] = [
  { name: "primary", class: "bg-primary", usage: "Aksi utama / brand" },
  { name: "secondary", class: "bg-secondary", usage: "Aksi netral / sekunder" },
  { name: "success", class: "bg-success", usage: "Berhasil, aktif, disetujui" },
  { name: "info", class: "bg-info", usage: "Informasi netral, detail" },
  { name: "warning", class: "bg-warning", usage: "Peringatan, perlu diedit" },
  { name: "pending", class: "bg-pending", usage: "Menunggu proses" },
  { name: "danger", class: "bg-danger", usage: "Bahaya, hapus, ditolak" },
  { name: "dark", class: "bg-dark", usage: "Aksen gelap / kontras tinggi" },
];

const fixed: { name: string; class: string; usage: string; border?: boolean }[] = [
  { name: "white", class: "bg-white", usage: "Permukaan terang di atas latar berwarna", border: true },
  { name: "light", class: "bg-light", usage: "Latar netral paling terang" },
];
</script>

<template>
  <ShowcaseSection id="colors" title="Colors"
    description="Token warna semantik aplikasi. Nilainya adalah CSS variable (--color-*) yang berubah mengikuti tema aktif (theme-1..4) — bukan hex tetap. white/light literal, tidak ikut tema.">
    <div>
      <h3 class="mb-3 text-overline">Token warna (mengikuti tema)</h3>
      <div class="gap-4 grid grid-cols-2 sm:grid-cols-4">
        <div v-for="t in tokens" :key="t.name" class="border border-slate-200 rounded-lg overflow-hidden">
          <div class="h-16" :class="t.class"></div>
          <div class="p-3">
            <div class="text-body-strong">{{ t.name }}</div>
            <code class="num-micro !text-slate-500">{{ t.class }}</code>
            <p class="mt-1 text-caption">{{ t.usage }}</p>
          </div>
        </div>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Warna literal (tidak ikut tema)</h3>
      <div class="gap-4 grid grid-cols-2 sm:grid-cols-4">
        <div v-for="t in fixed" :key="t.name" class="border border-slate-200 rounded-lg overflow-hidden">
          <div class="h-16" :class="[t.class, t.border ? 'border-b border-slate-200' : '']"></div>
          <div class="p-3">
            <div class="text-body-strong">{{ t.name }}</div>
            <code class="num-micro !text-slate-500">{{ t.class }}</code>
            <p class="mt-1 text-caption">{{ t.usage }}</p>
          </div>
        </div>
      </div>
    </div>
  </ShowcaseSection>
</template>
