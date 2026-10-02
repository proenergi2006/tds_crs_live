<script setup lang="ts">
import ShowcaseSection from "../components/ShowcaseSection.vue";

const spacingSamples = ["p-1", "p-2", "p-3", "p-4", "p-6", "p-8"];
const radiusSamples = ["rounded-md", "rounded-lg", "rounded-xl", "rounded-full"];
const shadowSamples = ["shadow-sm", "shadow-md", "shadow-lg", "shadow-xl"];
</script>

<template>
  <ShowcaseSection id="spacing" title="Spacing, Radius & Shadow"
    description="Tidak ada custom scale di tailwind.config.js — spacing/radius/shadow pakai default Tailwind sepenuhnya. Satu primitif custom: class .box (shadow tipis + border slate-200 + radius 0.6rem), dipakai sebagai card wrapper di seluruh aplikasi (termasuk section ini sendiri).">
    <div>
      <h3 class="mb-3 text-overline">Spacing (default Tailwind, contoh yang sering dipakai)</h3>
      <div class="flex flex-wrap items-end gap-4">
        <div v-for="s in spacingSamples" :key="s" class="text-center">
          <div class="bg-primary/10 border border-primary/20" :class="s">
            <div class="bg-primary w-4 h-4"></div>
          </div>
          <code class="block mt-1 num-micro !text-slate-500">{{ s }}</code>
        </div>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Radius</h3>
      <div class="flex flex-wrap gap-4">
        <div v-for="r in radiusSamples" :key="r" class="text-center">
          <div class="bg-primary/10 border border-primary w-20 h-20" :class="r"></div>
          <code class="block mt-1 num-micro !text-slate-500">{{ r }}</code>
        </div>
        <div class="text-center">
          <div class="bg-white border border-slate-200 w-20 h-20"
            style="border-radius: 0.6rem; box-shadow: 0px 3px 5px #0000000b"></div>
          <code class="block mt-1 num-micro !text-slate-500">.box (0.6rem)</code>
        </div>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Shadow</h3>
      <div class="flex flex-wrap gap-6">
        <div v-for="s in shadowSamples" :key="s" class="text-center">
          <div class="bg-white border border-slate-100 rounded-lg w-20 h-20" :class="s"></div>
          <code class="block mt-1 num-micro !text-slate-500">{{ s }}</code>
        </div>
      </div>
    </div>
  </ShowcaseSection>
</template>
