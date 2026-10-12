<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import type { Placement } from "@floating-ui/vue";
import Lucide from "@/components/Base/Lucide";
import Tippy from "@/components/Base/Tippy";
import { useFloatingPanel } from "@/composables/useFloatingPanel";
import { useApprovalBadgeStore } from "@/stores/approvalBadge";
import type { VisibleNavSection } from "@/stores/menu";
import {
  buildRailEntries,
  getMenuBadge,
  isNodeCurrent,
  itemContainsCurrent,
  navItemKey,
  type RailEntry,
} from "./side-menu";

const props = defineProps<{
  sections: VisibleNavSection[];
}>();

const route = useRoute();
const router = useRouter();
const badgeStore = useApprovalBadgeStore();

const FLYOUT_CLOSE_DELAY_MS = 150;
const FLYOUT_PLACEMENT: Placement = "right-start";

const openFlyoutKey = ref<string | null>(null);
const openedByHover = ref(false);
const activeTriggerEl = ref<HTMLElement | null>(null);
const flyoutPanelEl = ref<HTMLElement | null>(null);
let closeTimer: ReturnType<typeof setTimeout> | null = null;
let focusPending = false;

const routeName = computed(() => String(route.name ?? ""));
const railEntries = computed(() => buildRailEntries(props.sections));
const openEntry = computed<RailEntry | null>(
  () => railEntries.value.find((entry) => entryKey(entry) === openFlyoutKey.value) ?? null,
);

const { floatingStyles, isPositioned } = useFloatingPanel(
  activeTriggerEl,
  flyoutPanelEl,
  computed(() => FLYOUT_PLACEMENT),
);

const entryKey = (entry: RailEntry) => navItemKey(entry.sectionKey, entry.item);

const startsNewSection = (index: number) =>
  index > 0 && railEntries.value[index - 1].sectionKey !== railEntries.value[index].sectionKey;

const isEntryActive = (entry: RailEntry) =>
  isNodeCurrent(entry.item, routeName.value) || itemContainsCurrent(entry.item, routeName.value);

const entryBadge = (entry: RailEntry) => getMenuBadge(entry.item.badgeKey, badgeStore);

const flyoutId = (itemKey: string) => `side-nav-flyout-${itemKey.replace(/[^a-zA-Z0-9]/g, "-")}`;

const resolveHref = (pageName: string) => {
  try {
    return router.resolve({ name: pageName }).fullPath;
  } catch {
    return "";
  }
};

const cancelFlyoutClose = () => {
  if (closeTimer) clearTimeout(closeTimer);
  closeTimer = null;
};

const openFlyout = (itemKey: string, triggerEl: HTMLElement) => {
  cancelFlyoutClose();
  activeTriggerEl.value = triggerEl;
  openFlyoutKey.value = itemKey;
  openedByHover.value = false;
};

const closeFlyout = (options: { restoreFocus?: boolean } = {}) => {
  cancelFlyoutClose();
  focusPending = false;

  const trigger = activeTriggerEl.value;

  openFlyoutKey.value = null;
  openedByHover.value = false;

  if (options.restoreFocus) trigger?.focus();
};

const scheduleFlyoutClose = () => {
  cancelFlyoutClose();
  closeTimer = setTimeout(() => closeFlyout(), FLYOUT_CLOSE_DELAY_MS);
};

const focusFirstFlyoutLink = () => {
  if (!focusPending || !flyoutPanelEl.value || !isPositioned.value) return;

  focusPending = false;
  flyoutPanelEl.value.querySelector<HTMLElement>("a[href]")?.focus();
};

const toggleFlyout = (itemKey: string, event: MouseEvent) => {
  if (openFlyoutKey.value === itemKey && openedByHover.value) {
    cancelFlyoutClose();
    openedByHover.value = false;
    return;
  }

  if (openFlyoutKey.value === itemKey) {
    closeFlyout();
    return;
  }

  openFlyout(itemKey, event.currentTarget as HTMLElement);

  if (event.detail === 0) {
    focusPending = true;
    nextTick(focusFirstFlyoutLink);
  }
};

const onTriggerPointerEnter = (itemKey: string, event: PointerEvent) => {
  if (event.pointerType !== "mouse") return;

  if (event.movementX === 0 && event.movementY === 0) return;

  if (openFlyoutKey.value === itemKey) {
    cancelFlyoutClose();
    return;
  }

  openFlyout(itemKey, event.currentTarget as HTMLElement);
  openedByHover.value = true;
};

const onTriggerPointerLeave = (event: PointerEvent) => {
  if (event.pointerType === "mouse" && openedByHover.value) scheduleFlyoutClose();
};

const onFlyoutPointerLeave = (event: PointerEvent) => {
  if (event.pointerType === "mouse" && openedByHover.value) scheduleFlyoutClose();
};

const onDocumentPointerDown = (event: PointerEvent) => {
  if (!openFlyoutKey.value) return;

  const target = event.target as Node;

  if (!flyoutPanelEl.value?.contains(target) && !activeTriggerEl.value?.contains(target)) {
    closeFlyout();
  }
};

const onDocumentKeydown = (event: KeyboardEvent) => {
  if (event.key === "Escape" && openFlyoutKey.value) closeFlyout({ restoreFocus: true });
};

const onFlyoutFocusOut = (event: FocusEvent) => {
  const next = event.relatedTarget as Node | null;

  if (next && !flyoutPanelEl.value?.contains(next) && !activeTriggerEl.value?.contains(next)) {
    closeFlyout();
  }
};

const navigateFromRail = (pageName: string) => {
  closeFlyout();
  router.push({ name: pageName });
};

const navigateFromFlyout = (pageName: string) => {
  router.push({ name: pageName });
  closeFlyout();
};

watch([isPositioned, flyoutPanelEl], focusFirstFlyoutLink, { flush: "post" });

onMounted(() => {
  document.addEventListener("pointerdown", onDocumentPointerDown, true);
  document.addEventListener("keydown", onDocumentKeydown);
});

onUnmounted(() => {
  document.removeEventListener("pointerdown", onDocumentPointerDown, true);
  document.removeEventListener("keydown", onDocumentKeydown);
  cancelFlyoutClose();
});
</script>

<template>
  <div class="side-nav__rail-wrap">
    <ul class="side-nav__rail">
      <template v-for="(entry, index) in railEntries" :key="entryKey(entry)">
        <li v-if="startsNewSection(index)" class="side-nav__divider my-2" aria-hidden="true"></li>
        <li>
          <Tippy
            v-if="!entry.item.children?.length"
            as="a"
            :content="`${entry.sectionTitle} › ${entry.item.title}`"
            :options="{ placement: 'right' }"
            :href="resolveHref(entry.item.pageName ?? '')"
            :class="['side-menu', isEntryActive(entry) && 'side-menu--active']"
            @click.prevent="navigateFromRail(entry.item.pageName ?? '')"
          >
            <div class="side-menu__icon">
              <Lucide :icon="entry.item.icon" />
            </div>
            <span v-if="entryBadge(entry) > 0" class="side-nav__badge side-nav__rail-badge">
              {{ entryBadge(entry) }}
            </span>
          </Tippy>

          <button
            v-else
            type="button"
            :class="[
              'side-menu side-nav__rail-trigger',
              isEntryActive(entry) && 'side-menu--active',
              openFlyoutKey === entryKey(entry) && 'side-nav__rail-trigger--open',
            ]"
            :aria-label="`${entry.sectionTitle} › ${entry.item.title}`"
            :aria-expanded="openFlyoutKey === entryKey(entry)"
            :aria-controls="flyoutId(entryKey(entry))"
            @click="toggleFlyout(entryKey(entry), $event)"
            @pointerenter="onTriggerPointerEnter(entryKey(entry), $event)"
            @pointerleave="onTriggerPointerLeave"
          >
            <div class="side-menu__icon">
              <Lucide :icon="entry.item.icon" />
            </div>
            <span v-if="entryBadge(entry) > 0" class="side-nav__badge side-nav__rail-badge">
              {{ entryBadge(entry) }}
            </span>
          </button>
        </li>
      </template>
    </ul>

    <Teleport to="body">
      <div
        v-if="openEntry"
        :id="flyoutId(entryKey(openEntry))"
        ref="flyoutPanelEl"
        class="side-nav-flyout z-[9999]"
        :style="[floatingStyles, !isPositioned ? { visibility: 'hidden' } : {}]"
        @pointerenter="cancelFlyoutClose"
        @pointerleave="onFlyoutPointerLeave"
        @focusout="onFlyoutFocusOut"
      >
        <div class="side-nav-flyout__context">{{ openEntry.sectionTitle }}</div>
        <a
          v-if="openEntry.item.pageName"
          :href="resolveHref(openEntry.item.pageName)"
          :class="['side-nav__collapsed-item', isEntryActive(openEntry) && 'side-nav__collapsed-item--active']"
          @click.prevent="navigateFromFlyout(openEntry.item.pageName)"
        >
          <Lucide :icon="openEntry.item.icon" />
          <span>{{ openEntry.item.title }}</span>
          <span
            v-if="entryBadge(openEntry) > 0"
            class="ml-auto min-w-[18px] rounded-full bg-emerald-800 px-1.5 text-center text-[10px] font-semibold leading-5 text-white"
          >
            {{ entryBadge(openEntry) }}
          </span>
        </a>
        <div v-else class="side-nav__collapsed-item side-nav__collapsed-item--static">
          <Lucide :icon="openEntry.item.icon" />
          <span>{{ openEntry.item.title }}</span>
          <span
            v-if="entryBadge(openEntry) > 0"
            class="ml-auto min-w-[18px] rounded-full bg-emerald-800 px-1.5 text-center text-[10px] font-semibold leading-5 text-white"
          >
            {{ entryBadge(openEntry) }}
          </span>
        </div>
        <div class="side-nav-flyout__children">
          <a
            v-for="child in openEntry.item.children"
            :key="child.pageName"
            :href="resolveHref(child.pageName)"
            :class="['side-nav__collapsed-item', isNodeCurrent(child, routeName) && 'side-nav__collapsed-item--active']"
            @click.prevent="navigateFromFlyout(child.pageName)"
          >
            <Lucide :icon="child.icon" />
            <span>{{ child.title }}</span>
            <span
              v-if="getMenuBadge(child.badgeKey, badgeStore) > 0"
              class="ml-auto min-w-[18px] rounded-full bg-emerald-800 px-1.5 text-center text-[10px] font-semibold leading-5 text-white"
            >
              {{ getMenuBadge(child.badgeKey, badgeStore) }}
            </span>
          </a>
        </div>
      </div>
    </Teleport>
  </div>
</template>
