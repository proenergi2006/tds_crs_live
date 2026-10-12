<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import Lucide from "@/components/Base/Lucide";
import { useApprovalBadgeStore } from "@/stores/approvalBadge";
import type { NavItem } from "@/stores/menu";
import {
  enter,
  leave,
  getMenuBadge,
  isNodeCurrent,
  itemContainsCurrent,
  navItemKey,
} from "./side-menu";

const props = defineProps<{
  item: NavItem;
  sectionKey: string;
  isOpen: boolean;
}>();

const emit = defineEmits<{
  (e: "toggle-item", itemKey: string): void;
}>();

const route = useRoute();
const badgeStore = useApprovalBadgeStore();

const routeName = computed(() => String(route.name ?? ""));
const itemKey = computed(() => navItemKey(props.sectionKey, props.item));
const childListId = computed(() => `side-nav-children-${itemKey.value.replace(/[^a-zA-Z0-9]/g, "-")}`);
const hasChildren = computed(() => !!props.item.children?.length);
const isCurrent = computed(() => isNodeCurrent(props.item, routeName.value));
const containsCurrent = computed(() => itemContainsCurrent(props.item, routeName.value));
const itemBadge = computed(() => getMenuBadge(props.item.badgeKey, badgeStore));

const levelOneClass = computed(() => [
  "side-menu",
  (isCurrent.value || containsCurrent.value) && "side-menu--active",
]);

const emitToggle = () => {
  emit("toggle-item", itemKey.value);
};
</script>

<template>
  <li class="side-nav__item">
    <RouterLink
      v-if="item.pageName && !hasChildren"
      :to="{ name: item.pageName }"
      :class="levelOneClass"
    >
      <div class="side-menu__icon">
        <Lucide :icon="item.icon" />
      </div>
      <div class="side-menu__title">
        <span class="side-nav__label">{{ item.title }}</span>
        <span v-if="itemBadge > 0" class="side-nav__badge">{{ itemBadge }}</span>
      </div>
    </RouterLink>

    <div v-else-if="item.pageName" :class="[levelOneClass, 'side-nav__row']">
      <RouterLink :to="{ name: item.pageName }" class="side-nav__row-link">
        <div class="side-menu__icon">
          <Lucide :icon="item.icon" />
        </div>
        <div class="side-menu__title">
          <span class="side-nav__label">{{ item.title }}</span>
          <span v-if="itemBadge > 0" class="side-nav__badge">{{ itemBadge }}</span>
        </div>
      </RouterLink>
      <button
        type="button"
        class="side-nav__toggle"
        :aria-expanded="isOpen"
        :aria-controls="childListId"
        :aria-label="`Tampilkan sub-menu ${item.title}`"
        @click="emitToggle"
      >
        <Lucide icon="ChevronDown" :class="{ 'rotate-180': isOpen }" />
      </button>
    </div>

    <button
      v-else
      type="button"
      :class="[levelOneClass, 'side-nav__button']"
      :aria-expanded="isOpen"
      :aria-controls="childListId"
      @click="emitToggle"
    >
      <div class="side-menu__icon">
        <Lucide :icon="item.icon" />
      </div>
      <div class="side-menu__title">
        <span class="side-nav__label">{{ item.title }}</span>
        <span v-if="itemBadge > 0" class="side-nav__badge">{{ itemBadge }}</span>
        <div class="side-menu__sub-icon" :class="{ 'rotate-180': isOpen }">
          <Lucide icon="ChevronDown" />
        </div>
      </div>
    </button>

    <Transition v-if="hasChildren" @enter="enter" @leave="leave">
      <ul v-if="isOpen" :id="childListId" class="side-nav__children">
        <li v-for="child in item.children" :key="child.pageName">
          <RouterLink
            :to="{ name: child.pageName }"
            :class="['side-menu', isNodeCurrent(child, routeName) && 'side-menu--active']"
          >
            <div class="side-menu__icon">
              <Lucide :icon="child.icon" />
            </div>
            <div class="side-menu__title">
              <span class="side-nav__label">{{ child.title }}</span>
              <span
                v-if="getMenuBadge(child.badgeKey, badgeStore) > 0"
                class="side-nav__badge"
              >
                {{ getMenuBadge(child.badgeKey, badgeStore) }}
              </span>
            </div>
          </RouterLink>
        </li>
      </ul>
    </Transition>
  </li>
</template>
