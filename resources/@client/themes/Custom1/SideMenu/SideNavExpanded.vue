<script setup lang="ts">
import type { VisibleNavSection } from "@/stores/menu";
import SideNavSection from "./SideNavSection.vue";

defineProps<{
  sections: VisibleNavSection[];
  openItems: Set<string>;
}>();

const emit = defineEmits<{
  (e: "toggle-item", itemKey: string): void;
}>();
</script>

<template>
  <div class="side-nav__tree">
    <ul>
      <SideNavSection
        v-for="section in sections"
        :key="section.key"
        :section="section"
        :open-items="openItems"
        @toggle-item="emit('toggle-item', $event)"
      />
    </ul>
  </div>
</template>
