<script setup lang="ts">
import { watch, ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import "@/assets/css/themes/rubick/side-nav.css";
import "@/assets/css/themes/custom-1/side-nav.css";
import defaultLogoUrl from "@/assets/images/logo.png";
import agenLogoUrl from "@/assets/images/logo-proenergi.png";
import TopBar from "@/components/Themes/Rubick/TopBar";
import MobileMenu from "@/components/MobileMenu";
import { useMenuStore } from "@/stores/menu";
import { useAuthStore } from "@/stores/auth";
import { useApprovalBadgeStore } from "@/stores/approvalBadge";
import SideNavExpanded from "./SideNavExpanded.vue";
import SideNavRail from "./SideNavRail.vue";
import { resolveActivePath, toggleKey, unionKeys } from "./side-menu";

const route = useRoute();
const menuStore = useMenuStore();
const authStore = useAuthStore();
const badgeStore = useApprovalBadgeStore();
const windowWidth = ref(window.innerWidth);

const userCollapsedPref = ref(
  localStorage.getItem("rubick-sidebar-collapsed") === "true"
);

const openItems = ref<Set<string>>(new Set());
const isSettled = ref(false);

const isSidebarCollapsed = computed(
  () => windowWidth.value < 1280 || userCollapsedPref.value
);

const sections = computed(() => menuStore.sections);

const routeName = computed(() => String(route.name ?? ""));

const user = computed(() => authStore.user);

const isAgenRole = computed(() => {
  return user.value?.brand === 'proenergi';
});

const appName = computed(() => {
  return isAgenRole.value
    ? "Agen TDS"
    : "Tri Daya Selaras";
});

const currentLogo = computed(() => {
  return isAgenRole.value ? agenLogoUrl : defaultLogoUrl;
});

const contentScrollRef = ref<HTMLElement | null>(null);
let scrollTimer: ReturnType<typeof setTimeout> | null = null;

const onContentScroll = () => {
  contentScrollRef.value?.classList.add("is-scrolling");
  if (scrollTimer) clearTimeout(scrollTimer);
  scrollTimer = setTimeout(() => {
    contentScrollRef.value?.classList.remove("is-scrolling");
  }, 1000);
};

const toggleSidebarCollapse = () => {
  userCollapsedPref.value = !userCollapsedPref.value;
  localStorage.setItem(
    "rubick-sidebar-collapsed",
    String(userCollapsedPref.value)
  );
};

const expandActivePath = () => {
  const path = resolveActivePath(sections.value, routeName.value);

  if (!path) return;

  openItems.value = unionKeys(openItems.value, [path.itemKey]);
};

const onToggleItem = (itemKey: string) => {
  openItems.value = toggleKey(openItems.value, itemKey);
};

watch([routeName, sections], expandActivePath, { immediate: true });

watch([isSidebarCollapsed, () => route.fullPath], () => {
  isSettled.value = true;
});

onMounted(() => {
  badgeStore.fetch();

  window.addEventListener("resize", () => {
    windowWidth.value = window.innerWidth;
  });

  contentScrollRef.value?.addEventListener("scroll", onContentScroll);
});

onUnmounted(() => {
  contentScrollRef.value?.removeEventListener("scroll", onContentScroll);
  if (scrollTimer) clearTimeout(scrollTimer);
});
</script>

<template>
  <div :class="[
    'rubick custom-1 p-2 h-screen overflow-hidden',
    'before:content-[\'\'] before:bg-gradient-to-b before:from-theme-1 before:to-theme-2 dark:before:from-darkmode-800 dark:before:to-darkmode-800 before:fixed before:inset-0 before:z-[-1]',
  ]">
    <MobileMenu />
    <div class="flex pt-[4.7rem] md:pt-0 h-full">
      <nav :class="[
        'side-nav hidden w-[80px] md:flex flex-col xl:w-[230px] h-full',
        isSidebarCollapsed ? 'side-nav--collapsed' : '',
        isSettled ? 'side-nav--settled' : '',
      ]">
        <div class="side-nav__brand intro-x" :class="isSidebarCollapsed ? 'pl-7' : 'px-5'">
          <RouterLink :to="{ name: 'dashboard-overview-1' }" class="flex items-center gap-3 min-w-0">
            <div class="side-nav__brand-tile shrink-0">
              <img alt="Application Logo" class="w-8 h-8 object-contain" :src="currentLogo" />
            </div>
            <Transition name="side-nav-fade">
              <div v-if="!isSidebarCollapsed" class="side-nav__brand-text hidden xl:flex flex-col min-w-0 leading-tight">
                <span class="font-semibold truncate" :class="isAgenRole
                  ? 'bg-gradient-to-r from-yellow-300 via-orange-400 to-red-500 bg-clip-text text-transparent'
                  : 'text-white'
                  ">
                  {{ appName }}
                </span>
                <span class="truncate side-nav__brand-subtitle">Crushed Stone</span>
              </div>
            </Transition>
          </RouterLink>
        </div>
        <div class="side-nav__brand-divider" :class="isSidebarCollapsed && 'side-nav__brand-divider--collapsed'">
        </div>
        <div class="side-nav__body flex-1 w-full overflow-y-auto pt-6 pb-16">
          <Transition name="side-nav-fade">
            <SideNavExpanded
              v-if="!isSidebarCollapsed"
              :sections="sections"
              :open-items="openItems"
              @toggle-item="onToggleItem"
            />
            <SideNavRail v-else :sections="sections" />
          </Transition>
        </div>
      </nav>
      <div
        class="before:block flex flex-col flex-1 bg-slate-100 dark:bg-darkmode-700 rounded-xl before:w-full min-w-0 max-w-full md:max-w-auto before:h-px overflow-hidden before:content-['']">
        <div class="z-[51]">
          <TopBar :is-sidebar-collapsed="isSidebarCollapsed" @toggle-sidebar-collapse="toggleSidebarCollapse" />
        </div>
        <div ref="contentScrollRef" class="flex-1 overflow-y-auto content-area-scroll">
          <RouterView />
        </div>
      </div>
    </div>
  </div>
</template>
