<script setup lang="ts">
import "@/assets/css/themes/rubick/side-nav.css";
import { useRoute, useRouter } from "vue-router";
import defaultLogoUrl from "@/assets/images/logo.png";
import agenLogoUrl from "@/assets/images/logo-proenergi.png";
import Tippy from "@/components/Base/Tippy";
import Lucide from "@/components/Base/Lucide";
import TopBar from "@/components/Themes/Rubick/TopBar";
import MobileMenu from "@/components/MobileMenu";
import { useMenuStore } from "@/stores/menu";
import { useAuthStore } from "@/stores/auth";
import { useApprovalBadgeStore } from "@/stores/approvalBadge";
import {
  type ProvideForceActiveMenu,
  forceActiveMenu,
  type Route,
  type FormattedMenu,
  nestedMenu,
  linkTo,
  enter,
  leave,
} from "./side-menu";
import { watch, reactive, ref, computed, onMounted, onUnmounted, provide } from "vue";

const route: Route = useRoute();
const router = useRouter();
let formattedMenu = reactive<Array<FormattedMenu | "divider">>([]);
const setFormattedMenu = (
  computedFormattedMenu: Array<FormattedMenu | "divider">
) => {
  Object.assign(formattedMenu, computedFormattedMenu);
};
const menuStore = useMenuStore();
const authStore = useAuthStore();
const menu = computed(() => nestedMenu(menuStore.menu("side-menu"), route));
const windowWidth = ref(window.innerWidth);

// Manual user preference — persisted to localStorage
const userCollapsedPref = ref(
  localStorage.getItem("rubick-sidebar-collapsed") === "true"
);

// Auto-collapse below xl (< 1280px); at xl+ use the user's stored preference
const isSidebarCollapsed = computed(
  () => windowWidth.value < 1280 || userCollapsedPref.value
);

const user = computed(() => authStore.user);

const badgeStore = useApprovalBadgeStore();

// "Verifikasi" itu sentinel khusus buat grand total, bukan masuk breakdown biasa
const getMenuBadge = (badgeKey?: string): number => {
  if (!badgeKey) return 0;
  if (badgeKey === 'Verifikasi') return badgeStore.total;
  return badgeStore.breakdown[badgeKey] ?? 0;
};

// brand proenergi dari backend (UserAuthResource.brand), bukan replikasi bucket role manual
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

provide<ProvideForceActiveMenu>("forceActiveMenu", (pageName: string) => {
  forceActiveMenu(route, pageName);
  setFormattedMenu(menu.value);
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

const onCollapsedPanelItemClick = (
  event: MouseEvent,
  menuItem: FormattedMenu
) => {
  event.preventDefault();

  if (menuItem.subMenu) {
    return;
  }

  linkTo(menuItem, router);
  setFormattedMenu([...formattedMenu]);
};

watch(menu, () => {
  setFormattedMenu(menu.value);
});

watch(
  computed(() => route.path),
  () => {
    delete route.forceActiveMenu;
  }
);

onMounted(() => {
  setFormattedMenu(menu.value);

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
    'rubick px-2 py-2 sm:py-4 sm:px-4 h-screen overflow-hidden',
    'before:content-[\'\'] before:bg-gradient-to-b before:from-theme-1 before:to-theme-2 dark:before:from-darkmode-800 dark:before:to-darkmode-800 before:fixed before:inset-0 before:z-[-1]',
  ]">
    <MobileMenu />
    <div class="pt-[4.7rem] flex md:pt-0 h-full">
      <nav :class="[
        'side-nav hidden w-[80px] md:flex flex-col xl:w-[230px] h-full',
        isSidebarCollapsed ? 'side-nav--collapsed' : '',
      ]">
        <div class="side-nav__brand intro-x" :class="isSidebarCollapsed ? 'pl-7' : 'px-5'">
          <RouterLink :to="{ name: 'dashboard-overview-1' }" class="flex min-w-0 items-center gap-3">
            <div class="side-nav__brand-tile shrink-0">
              <img alt="Application Logo" class="w-8 h-8 object-contain" :src="currentLogo" />
            </div>
            <div class="hidden min-w-0 flex-col leading-tight xl:flex" :class="isSidebarCollapsed && 'xl:hidden'">
              <span class="font-semibold truncate" :class="isAgenRole
                ? 'bg-gradient-to-r from-yellow-300 via-orange-400 to-red-500 bg-clip-text text-transparent'
                : 'text-white'
                ">
                {{ appName }}
              </span>
              <span class="side-nav__brand-subtitle truncate">Crushed Stone</span>
            </div>
          </RouterLink>
        </div>
        <div class="side-nav__brand-divider" :class="isSidebarCollapsed && 'side-nav__brand-divider--collapsed'">
        </div>
        <div :class="['side-nav__body pt-6', !isSidebarCollapsed && 'flex-1 w-full overflow-y-auto pt-6 pb-16']">
          <ul>
            <template v-for="(menu, menuKey) in formattedMenu">
              <li v-if="menu == 'divider'" type="li" class="my-6 side-nav__divider" :key="'divider-' + menuKey"></li>
              <li v-else :key="menuKey" class="side-nav__item">
                <Tippy as="a" :content="menu.title" :options="{
                  placement: 'right',
                }" :disable="isSidebarCollapsed ? !!menu.subMenu : windowWidth > 1260" :href="menu.subMenu
                  ? '#'
                  : ((pageName: string | undefined) => {
                    try {
                      return router.resolve({
                        name: pageName,
                      }).fullPath;
                    } catch (err) {
                      return '';
                    }
                  })(menu.pageName)
                  " @click="(event: MouseEvent) => {
                    event.preventDefault();
                    linkTo(menu, router);
                    setFormattedMenu([...formattedMenu]);
                  }" :class="[
                    menu.active ? 'side-menu side-menu--active' : 'side-menu',
                  ]">
                  <div class="side-menu__icon">
                    <Lucide :icon="menu.icon" />
                  </div>
                  <div class="side-menu__title">
                    <span class="flex-1 min-w-0 truncate">{{ menu.title }}</span>
                    <span v-if="getMenuBadge(menu.badgeKey) > 0" :class="[
                      'shrink-0 mr-[1.25rem] text-[10px] font-semibold rounded-full px-1.5 leading-5 min-w-[18px] text-center',
                      menu.active ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-700',
                    ]">
                      {{ getMenuBadge(menu.badgeKey) }}
                    </span>
                    <div v-if="menu.subMenu && getMenuBadge(menu.badgeKey) === 0" :class="[
                      'side-menu__sub-icon ',
                      { 'transform rotate-180': menu.activeDropdown },
                    ]">
                      <Lucide icon="ChevronDown" />
                    </div>
                  </div>
                </Tippy>
                <div v-if="isSidebarCollapsed && menu.subMenu" class="side-nav__collapsed-panel">
                  <div class="side-nav__collapsed-title">{{ menu.title }}</div>
                  <template v-for="(subMenu, subMenuKey) in menu.subMenu" :key="subMenuKey">
                    <div v-if="subMenu.subMenu" :class="[
                      'side-nav__collapsed-group',
                      subMenu.active && 'side-nav__collapsed-item--active',
                    ]">
                      <div class="side-nav__collapsed-item">
                        <Lucide :icon="subMenu.icon" />
                        <span>{{ subMenu.title }}</span>
                        <Lucide icon="CornerRightDown" class="side-nav__collapsed-chevron" />
                      </div>
                      <div class="side-nav__collapsed-children">
                        <a v-for="(lastSubMenu, lastSubMenuKey) in subMenu.subMenu" :key="lastSubMenuKey" href="#"
                          :class="[
                            'side-nav__collapsed-item side-nav__collapsed-item--child',
                            lastSubMenu.active && 'side-nav__collapsed-item--active',
                          ]" @click="(event: MouseEvent) => onCollapsedPanelItemClick(event, lastSubMenu)">
                          <Lucide :icon="lastSubMenu.icon" />
                          <span>{{ lastSubMenu.title }}</span>
                        </a>
                      </div>
                    </div>
                    <a v-else href="#" :class="[
                      'side-nav__collapsed-item',
                      subMenu.active && 'side-nav__collapsed-item--active',
                    ]" @click="(event: MouseEvent) => onCollapsedPanelItemClick(event, subMenu)">
                      <Lucide :icon="subMenu.icon" />
                      <span>{{ subMenu.title }}</span>
                      <span v-if="getMenuBadge(subMenu.badgeKey) > 0"
                        class="ml-auto text-[10px] font-semibold bg-emerald-800 text-white rounded-full px-1.5 leading-5 min-w-[18px] text-center">
                        {{ getMenuBadge(subMenu.badgeKey) }}
                      </span>
                    </a>
                  </template>
                </div>
                <Transition v-if="!isSidebarCollapsed" @enter="enter" @leave="leave">
                  <ul v-if="menu.subMenu && menu.activeDropdown"
                    :class="{ 'side-menu__sub-open': menu.activeDropdown }">
                    <li v-for="(subMenu, subMenuKey) in menu.subMenu" :key="subMenuKey">
                      <Tippy as="a" :content="subMenu.title" :options="{
                        placement: 'right',
                      }" :disable="windowWidth > 1260" :href="subMenu.subMenu
                        ? '#'
                        : ((pageName: string | undefined) => {
                          try {
                            return router.resolve({
                              name: pageName,
                            }).fullPath;
                          } catch (err) {
                            return '';
                          }
                        })(subMenu.pageName)
                        " :class="[
                          subMenu.active
                            ? 'side-menu side-menu--active'
                            : 'side-menu',
                        ]" @click="(event: MouseEvent) => {
                          event.preventDefault();
                          linkTo(subMenu, router);
                          setFormattedMenu([...formattedMenu]);
                        }">
                        <div class="side-menu__icon side-menu__icon--tree">
                          <Lucide :icon="subMenu.active ? 'ChevronsRight' : 'CornerDownRight'" />
                        </div>
                        <div class="side-menu__title">
                          {{ subMenu.title }}
                          <span v-if="getMenuBadge(subMenu.badgeKey) > 0"
                            class="ml-auto mr-[10px] shrink-0 text-[10px] font-semibold bg-white text-emerald-700 rounded-full px-1.5 leading-5 min-w-[18px] text-center">
                            {{ getMenuBadge(subMenu.badgeKey) }}
                          </span>
                          <div v-if="subMenu.subMenu" :class="[
                            'side-menu__sub-icon',
                            { 'transform rotate-180': subMenu.activeDropdown },
                          ]">
                            <Lucide icon="ChevronDown" />
                          </div>
                        </div>
                      </Tippy>
                      <Transition @enter="enter" @leave="leave" v-if="subMenu.subMenu">
                        <ul v-if="subMenu.subMenu && subMenu.activeDropdown" :class="{
                          'side-menu__sub-open': subMenu.activeDropdown,
                        }">
                          <li v-for="(lastSubMenu, lastSubMenuKey) in subMenu.subMenu" :key="lastSubMenuKey">
                            <Tippy as="a" :content="lastSubMenu.title" :options="{
                              placement: 'right',
                            }" :disable="windowWidth > 1260" :href="lastSubMenu.subMenu
                              ? '#'
                              : ((pageName: string | undefined) => {
                                try {
                                  return router.resolve({
                                    name: pageName,
                                  }).fullPath;
                                } catch (err) {
                                  return '';
                                }
                              })(lastSubMenu.pageName)
                              " :class="[
                                lastSubMenu.active
                                  ? 'side-menu side-menu--active'
                                  : 'side-menu',
                              ]" @click="(event: MouseEvent) => {
                                event.preventDefault();
                                linkTo(lastSubMenu, router);
                                setFormattedMenu([...formattedMenu]);
                              }">
                              <div class="side-menu__icon side-menu__icon--tree">
                                <Lucide :icon="lastSubMenu.active ? 'ChevronsRight' : 'CornerDownRight'" />
                              </div>
                              <div class="side-menu__title">
                                {{ lastSubMenu.title }}
                              </div>
                            </Tippy>
                          </li>
                        </ul>
                      </Transition>
                    </li>
                  </ul>
                </Transition>
              </li>
            </template>
          </ul>
        </div>
      </nav>
      <div
        class="md:max-w-auto min-w-0 max-w-full flex-1 rounded-[30px] bg-slate-100 before:block before:h-px before:w-full before:content-[''] dark:bg-darkmode-700 flex flex-col overflow-hidden">
        <!-- TopBar di LUAR area scroll: tetap diam di atas, hanya konten yang scroll. -->
        <div class="z-[51]">
          <TopBar :is-sidebar-collapsed="isSidebarCollapsed" @toggle-sidebar-collapse="toggleSidebarCollapse" />
        </div>
        <div ref="contentScrollRef" class="content-area-scroll flex-1 overflow-y-auto">
          <RouterView />
        </div>
      </div>
    </div>
  </div>
</template>
