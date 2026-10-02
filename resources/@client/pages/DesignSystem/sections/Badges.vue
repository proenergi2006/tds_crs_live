<script setup lang="ts">
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const variants = [
  "soft-primary", "soft-secondary", "soft-success", "soft-info",
  "soft-warning", "soft-pending", "soft-danger", "soft-dark", "dark",
] as const;

const examples: { label: string; variant: (typeof variants)[number] }[] = [
  { label: "Draft", variant: "soft-secondary" },
  { label: "Review", variant: "soft-pending" },
  { label: "Approved", variant: "soft-success" },
  { label: "Rejected", variant: "soft-danger" },
  { label: "Published", variant: "dark" },
];
</script>

<template>
  <ShowcaseSection id="badges" title="Badge"
    description="SystemDesign/Data/Badge.vue — 8 soft + 1 solid dark. Tanpa border/hover (murni statis, non-interaktif). Warna tidak punya makna domain baku — contoh di bawah cuma ilustrasi.">
    <div>
      <h3 class="mb-3 text-overline">Semua variant</h3>
      <div class="flex flex-wrap gap-2">
        <Badge v-for="v in variants" :key="v" :variant="v">{{ v }}</Badge>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Contoh pemakaian status</h3>
      <div class="flex flex-wrap gap-2">
        <Badge v-for="e in examples" :key="e.label" :variant="e.variant">{{ e.label }}</Badge>
      </div>
    </div>
  </ShowcaseSection>
</template>
