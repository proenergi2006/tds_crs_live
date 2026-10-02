<script setup lang="ts">
import Button from "@/components/Base/Button";
import Lucide from "@/components/Base/Lucide";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const colors = ["primary", "secondary", "success", "warning", "danger", "dark", "white", "info"] as const;
const treatments = ["solid", "outline", "soft"] as const;

function variantFor(treatment: (typeof treatments)[number], color: (typeof colors)[number]) {
  const value = treatment === "solid" ? color : `${treatment}-${color}`;
  return value as InstanceType<typeof Button>["$props"]["variant"];
}
</script>

<template>
  <ShowcaseSection id="buttons" title="Button"
    description="Base/Button/Button.vue — 24 variant (8 warna semantik x 3 treatment).">
    <div v-for="treatment in treatments" :key="treatment">
      <h3 class="mb-3 text-overline capitalize">{{ treatment }}</h3>
      <div class="flex flex-wrap gap-2">
        <Button v-for="c in colors" :key="c" :variant="variantFor(treatment, c)">
          {{ c }}
        </Button>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">States</h3>
      <div class="flex flex-wrap items-center gap-3">
        <Button variant="primary">Default</Button>
        <Button variant="primary" disabled>Disabled</Button>
        <Button variant="primary" size="sm">Size sm</Button>
        <Button variant="primary" size="lg">Size lg</Button>
        <Button variant="primary" rounded>Rounded</Button>
        <Button variant="primary" elevated>Elevated</Button>
        <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-9 !h-9" title="Icon only">
          <Lucide icon="Trash2" class="w-4 h-4" />
        </Button>
      </div>
      <p class="mt-2 text-caption">Focus state (ring) aktif otomatis lewat keyboard tab — tidak bisa didemokan statis di
        sini. Tidak ada prop `loading` bawaan di Button; pola loading (spin icon) diimplementasikan manual per
        pemakaian, lihat contoh di FormModal.</p>
    </div>
  </ShowcaseSection>
</template>
