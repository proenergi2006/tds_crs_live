<script setup lang="ts">
interface FontCandidate {
  name: string
  family: string
  googleParam: string
  weights: string
}

const fonts: FontCandidate[] = [
  { name: 'Open Sans', family: "'Open Sans', sans-serif", googleParam: 'Open+Sans:wght@400;600;700', weights: '400/600/700' },
  { name: 'Inter', family: "'Inter', sans-serif", googleParam: 'Inter:wght@400;600;700', weights: '400/600/700' },
  { name: 'Lato', family: "'Lato', sans-serif", googleParam: 'Lato:wght@400;700', weights: '400/700' },
  { name: 'Roboto', family: "'Roboto', sans-serif", googleParam: 'Roboto:wght@400;500;700', weights: '400/500/700' },
  { name: 'Nunito Sans', family: "'Nunito Sans', sans-serif", googleParam: 'Nunito+Sans:wght@400;600;700', weights: '400/600/700' },
  { name: 'Source Sans 3', family: "'Source Sans 3', sans-serif", googleParam: 'Source+Sans+3:wght@400;600;700', weights: '400/600/700' },
]

const fontUrl = 'https://fonts.googleapis.com/css2?' +
  fonts.map((f) => `family=${f.googleParam}`).join('&') +
  '&display=swap'
</script>

<template>
  <link rel="stylesheet" :href="fontUrl">

  <div class="mx-auto p-6 max-w-4xl">
    <h1 class="mb-1 font-bold text-2xl">Font Preview — Credit Approval Form</h1>
    <p class="mb-6 text-slate-500 text-sm">
      Sample layout meniru dokumen Credit Application PDF, dirender pakai tiap font Google di bawah untuk
      dibandingkan. Ini halaman dummy, bukan bagian dari fitur aplikasi.
    </p>

    <div v-for="font in fonts" :key="font.name" class="mb-8 border border-slate-200 rounded-lg overflow-hidden">
      <div class="flex justify-between items-center bg-slate-50 px-4 py-2 border-slate-200 border-b">
        <span class="font-semibold text-slate-700 text-sm">{{ font.name }}</span>
        <span class="text-slate-400 text-xs">weights: {{ font.weights }}</span>
      </div>

      <div class="p-4" :style="{ fontFamily: font.family }">
        <div class="mb-3 pb-1 border-slate-800 border-b-2">
          <span class="font-bold text-[15px] uppercase tracking-wide">Credit Approval Form</span>
        </div>

        <div class="mb-2 bg-slate-100 px-2 py-1 border-slate-800 border-l-4 font-bold text-[11px] uppercase">
          1. Customer Information
        </div>
        <table class="mb-3 w-full text-[11px]">
          <tr>
            <td class="py-0.5 pr-2 w-32 font-bold text-slate-600">Company Name:</td>
            <td class="py-0.5 font-bold">PT ESSAR CONSTRUCTIONS INDONESIA</td>
          </tr>
          <tr>
            <td class="py-0.5 pr-2 font-bold text-slate-600">Address:</td>
            <td class="py-0.5">Jalan Garuda Gang Belibis RT 008 RW 000, Sambaliung, Kab. Berau, Kalimantan Timur</td>
          </tr>
        </table>

        <div class="mb-2 bg-slate-100 px-2 py-1 border-slate-800 border-l-4 font-bold text-[11px] uppercase">
          3. Credit Limit &amp; TOP
        </div>
        <table class="border border-slate-300 w-full text-[10px] text-center border-collapse">
          <tr class="bg-slate-50 font-bold">
            <td class="border border-slate-300 px-2 py-1">Product</td>
            <td class="border border-slate-300 px-2 py-1">Credit Limit Request</td>
            <td class="border border-slate-300 px-2 py-1">Credit Limit Approval</td>
            <td class="border border-slate-300 px-2 py-1">TOP Request</td>
            <td class="border border-slate-300 px-2 py-1">TOP Approval</td>
          </tr>
          <tr>
            <td class="border border-slate-300 px-2 py-1">Crushed Stone</td>
            <td class="border border-slate-300 px-2 py-1">Rp 500.000.000</td>
            <td class="border border-slate-300 px-2 py-1">Rp 400.000.000</td>
            <td class="border border-slate-300 px-2 py-1">45 days</td>
            <td class="border border-slate-300 px-2 py-1">30 days</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>
