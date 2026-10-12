<script setup lang="ts">
import { computed } from "vue";
import { useApprovalBadgeStore } from "@/stores/approvalBadge";
import type { VisibleNavSection } from "@/stores/menu";
import SideNavItem from "./SideNavItem.vue";
import { getMenuBadge, navItemKey } from "./side-menu";

const props = defineProps<{
  section: VisibleNavSection;
  openItems: Set<string>;
}>();

const emit = defineEmits<{
  (e: "toggle-item", itemKey: string): void;
}>();

const badgeStore = useApprovalBadgeStore();

const badge = computed(() => getMenuBadge(props.section.badgeKey, badgeStore));

const isItemOpen = (itemKey: string) => props.openItems.has(itemKey);

const emitToggleItem = (itemKey: string) => {
  emit("toggle-item", itemKey);
};
</script>

<template>
  <li role="presentation" class="side-nav__heading">
    <span class="side-nav__label">{{ section.title }}</span>
    <span v-if="badge > 0" class="side-nav__badge">{{ badge }}</span>
  </li>
  <SideNavItem
    v-for="item in section.items"
    :key="navItemKey(section.key, item)"
    :item="item"
    :section-key="section.key"
    :is-open="isItemOpen(navItemKey(section.key, item))"
    @toggle-item="emitToggleItem"
  />
</template>
