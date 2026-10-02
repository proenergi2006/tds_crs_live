<script setup lang="ts">
import Lucide, { type Icon } from "@/components/Base/Lucide/Lucide.vue";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const sample: Icon[] = [
  "Plus", "Check", "Edit", "Trash2", "Search", "SlidersHorizontal", "ChevronDown",
  "ChevronLeft", "ChevronRight", "X", "AlertTriangle", "AlertCircle", "CheckCircle",
  "XCircle", "Info", "Save", "Loader2", "CalendarDays", "Upload", "FileText",
  "Inbox", "CheckCheck", "Settings", "Download",
];
</script>

<template>
  <ShowcaseSection id="icons" title="Icons"
    description="lucide-vue-next, dipakai lewat komponen <Lucide icon=&quot;Name&quot; />. Berikut sampel ikon yang dipakai luas di aplikasi (bukan daftar lengkap).">
    <div class="gap-4 grid grid-cols-3 sm:grid-cols-6 md:grid-cols-8">
      <div v-for="name in sample" :key="name"
        class="flex flex-col items-center gap-2 p-3 border border-slate-100 rounded-lg">
        <Lucide :icon="name" class="w-5 h-5 text-slate-600" />
        <code class="num-micro !text-slate-500 text-center">{{ name }}</code>
      </div>
    </div>
  </ShowcaseSection>
</template>
