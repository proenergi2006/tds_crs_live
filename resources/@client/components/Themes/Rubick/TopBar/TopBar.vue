<script setup lang="ts">
import { ref, computed } from 'vue'
import Lucide from "@/components/Base/Lucide";
import Breadcrumb from "@/components/Base/Breadcrumb";
import { Menu } from "@/components/Base/Headless";
import { type Menu as MenuItem } from "@/stores/menu";
import defaultLogoUrl from "@/assets/images/logo-tds-1.png";
import agenLogoUrl from "@/assets/images/logo-proenergi.png";
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRoute, useRouter } from 'vue-router'
import { useMenuStore } from '@/stores/menu'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

withDefaults(defineProps<{
  isSidebarCollapsed?: boolean
}>(), {
  isSidebarCollapsed: false,
})

const emit = defineEmits<{
  (event: 'toggle-sidebar-collapse'): void
}>()

const searchDropdown = ref(false);
const showSearchDropdown = () => {
  searchDropdown.value = true;
};
const hideSearchDropdown = () => {
  searchDropdown.value = false;
};

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const menuStore = useMenuStore()
const notification = useNotification()

const userName = computed(() => auth.user?.name || 'Guest')
const userEmail = computed(() => auth.user?.email || '-')

const agenRoles = [13, 14, 15, 16]

const isAgenRole = computed(() => {
  return agenRoles.includes(Number(auth.user?.id_role))
})

const currentLogo = computed(() => {
  return isAgenRole.value ? agenLogoUrl : defaultLogoUrl
})

const brandName = computed(() => {
  return isAgenRole.value ? 'Agen Tri Daya Selaras' : 'Tri Daya Selaras'
})

type BreadcrumbItem = {
  title: string;
  to: string | { name: string };
  active?: boolean;
  disabled?: boolean;
}

function isMenuActiveForRoute(item: MenuItem, routeName: string) {
  return item.pageName === routeName || !!item.activePageNames?.includes(routeName)
}

function findMenuChain(menu: Array<MenuItem | 'divider'>, routeName: string): MenuItem[] {
  for (const item of menu) {
    if (item === 'divider') continue

    if (isMenuActiveForRoute(item, routeName)) {
      return [item]
    }

    if (item.subMenu) {
      const childChain = findMenuChain(item.subMenu, routeName)

      if (childChain.length > 0) {
        return [item, ...childChain]
      }
    }
  }

  return []
}

function resolveMenuTo(item: MenuItem) {
  if (item.pageName && router.hasRoute(item.pageName)) {
    return { name: item.pageName }
  }

  return route.fullPath
}

function getInternalRouteTitle() {
  if (typeof route.meta.breadcrumbTitle === 'string') {
    return route.meta.breadcrumbTitle
  }

  if (typeof route.meta.title === 'string') {
    return route.meta.title
  }

  const segments = route.path.replace(/\/+$/, '').split('/').filter(Boolean)
  const lastSegment = segments[segments.length - 1]

  if (lastSegment === 'create') return 'Tambah'
  if (lastSegment === 'edit') return 'Edit'

  return ''
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
  const routeName = String(route.name || '')
  const chain = findMenuChain(menuStore.menu('side-menu'), routeName)
  const items: BreadcrumbItem[] = []

  chain.forEach(item => {
    items.push({
      title: item.title,
      to: resolveMenuTo(item),
    })
  })

  const routeMatchesMenu = chain.some(item => item.pageName === routeName)
  const internalRouteTitle = getInternalRouteTitle()

  if (!routeMatchesMenu && internalRouteTitle) {
    items.push({
      title: internalRouteTitle,
      to: route.fullPath,
    })
  }

  if (items.length === 0) {
    items.push({
      title: brandName.value,
      to: route.fullPath,
    })
  }

  return items.map((item, index) => ({
    ...item,
    active: index === items.length - 1,
  }))
})

async function onLogout() {
  try {
    const { data } = await axios.post('/api/logout');
    notification.success(data.message)
  } catch (e) {
    console.error('Logout error', e);
  } finally {
    // Delay redirect so nextTick can fire showToast() before AppNotification unmounts.
    // Toastify clones the toast node into document.body (survives Layout unmount),
    // but only if showToast() runs before templateRef is nulled by unmount.
    setTimeout(() => {
      localStorage.removeItem('access_token');
      delete axios.defaults.headers.common['Authorization'];
      router.push({ name: 'login' });
    }, 500)
  }
}
</script>

<template>
  <div
    class="relative z-[51] flex h-[67px] items-center border-b border-slate-200 bg-slate-100 dark:bg-darkmode-700 md:px-6">
    <button type="button"
      class="mr-3 hidden h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-200/70 hover:text-slate-700 xl:flex"
      :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="emit('toggle-sidebar-collapse')">
      <Lucide :icon="isSidebarCollapsed ? 'PanelRightOpen' : 'PanelLeftClose'" class="h-5 w-5" />
    </button>

    <!-- BEGIN: Breadcrumb -->
    <Breadcrumb class="hidden mr-auto -intro-x sm:flex">
      <Breadcrumb.Link v-for="(item, index) in breadcrumbs" :key="`${item.title}-${index}`" :to="item.to"
        :active="item.active" :disabled="item.disabled">
        {{ item.title }}
      </Breadcrumb.Link>
    </Breadcrumb>
    <!-- END: Breadcrumb -->

    <div class="ml-auto flex items-center gap-3">
      <div class="flex items-center gap-2 intro-x">
        <!-- BEGIN: Search -->
        <!-- <div class="relative hidden sm:block">
          <input type="text" placeholder="Search..."
            class="w-48 rounded-full border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm text-slate-600 placeholder-slate-400 transition focus:w-64 focus:border-primary focus:outline-none focus:ring-0 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 dark:placeholder-slate-500" />
          <Lucide icon="Search" class="absolute inset-y-0 left-3 my-auto h-4 w-4 text-slate-400" />
        </div> -->
        <!-- END: Search -->

        <!-- BEGIN: Notification -->
        <!-- <Menu>
          <Menu.Button
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-200/70 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-darkmode-400">
            <Lucide icon="Bell" class="h-5 w-5" />
            <span class="absolute right-1.5 top-1.5 flex h-2 w-2 items-center justify-center rounded-full bg-danger">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-danger opacity-75" />
            </span>
          </Menu.Button>

          <Menu.Items class="mt-px w-72">
            <Menu.Header class="font-normal flex items-center justify-between">
              <span class="font-medium">Notifications</span>
              <span class="text-xs">3 unread</span>
            </Menu.Header>

            <Menu.Divider />

            <Menu.Item as="button" class="hover:bg-dark/5 flex flex-col items-start gap-0.5 py-3 w-full">
              <div class="flex w-full items-start gap-2">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-dark/5">
                  <Lucide icon="ShoppingCart" class="h-3.5 w-3.5" />
                </div>
                <div class="flex-1 text-left">
                  <div class="text-sm font-medium leading-snug">PO-2024-0042 disetujui</div>
                  <div class="mt-0.5 text-xs">Purchase Order · 2 menit lalu</div>
                </div>
                <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-danger" />
              </div>
            </Menu.Item>

            <Menu.Item as="button" class="hover:bg-dark/5 flex flex-col items-start gap-0.5 py-3">
              <div class="flex w-full items-start gap-2">
                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-dark/5">
                  <Lucide icon="ClipboardList" class="h-3.5 w-3.5" />
                </div>
                <div class="flex-1 text-left">
                  <div class="text-sm font-medium leading-snug">PR-2024-0018 menunggu approval</div>
                  <div class="mt-0.5 text-xs">Purchase Request · 3 jam lalu</div>
                </div>
              </div>
            </Menu.Item>

            <Menu.Divider />

            <Menu.Item as="button" class="hover:bg-dark/5 w-full justify-center text-center text-xs py-2">
              Lihat semua notifikasi
            </Menu.Item>
          </Menu.Items>
        </Menu> -->
        <!-- END: Notification -->
      </div>

      <!-- Divider Vertikal -->
      <div class="hidden h-8 w-px bg-slate-200 dark:bg-darkmode-400 sm:block" />

      <!-- BEGIN: Account Menu -->
      <Menu>
        <Menu.Button class="flex items-center gap-3 intro-x">
          <!-- User info: hidden on mobile -->
          <div class="hidden text-right sm:block">
            <div class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-tight">
              {{ userName }}
            </div>
            <div class="text-xs text-slate-400 dark:text-slate-500 leading-tight mt-0.5">
              {{ userEmail }}
            </div>
          </div>

          <!-- Avatar -->
          <div
            class="flex items-center justify-center overflow-hidden rounded-full shadow-lg zoom-in h-10 w-10 shrink-0">
            <img alt="Application Logo" class="h-full w-full object-contain" :src="currentLogo" />
          </div>
        </Menu.Button>

        <Menu.Items class="w-56 mt-px text-white bg-primary">
          <Menu.Header class="font-normal">
            <div class="font-medium">{{ userName }}</div>
            <div class="text-xs text-white/70 mt-0.5 dark:text-slate-500">
              {{ userEmail }}
            </div>
          </Menu.Header>

          <Menu.Divider class="bg-white/[0.08]" />

          <Menu.Item as="button" class="hover:bg-white/5" @click="router.push({ name: 'profile-overview-1' })">
            <Lucide icon="User" class="w-4 h-4 mr-2" />
            Profile
          </Menu.Item>

          <Menu.Item v-if="auth.can('admin.users.manage')" as="button" class="hover:bg-white/5"
            @click="() => router.push({ name: 'users' })">
            <Lucide icon="Edit" class="w-4 h-4 mr-2" />
            Add Account
          </Menu.Item>

          <Menu.Item class="hover:bg-white/5">
            <Lucide icon="HelpCircle" class="w-4 h-4 mr-2" />
            Help
          </Menu.Item>

          <Menu.Divider class="bg-white/[0.08]" />

          <Menu.Item as="button" @click="onLogout"
            class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2">
            <Lucide icon="ToggleRight" class="w-4 h-4 mr-2" />
            Logout
          </Menu.Item>
        </Menu.Items>
      </Menu>
    </div>
  </div>
</template>
