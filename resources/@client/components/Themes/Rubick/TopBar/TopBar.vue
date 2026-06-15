<script setup lang="ts">
import { ref, computed } from 'vue'
import Lucide from "@/components/Base/Lucide";
import Breadcrumb from "@/components/Base/Breadcrumb";
import { Menu } from "@/components/Base/Headless";
import { type Menu as MenuItem } from "@/stores/menu";
import defaultLogoUrl from "@/assets/images/logo-tds-1.png";
import agenLogoUrl from "@/assets/images/logo-proenergi.png";
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'
import { useRoute, useRouter } from 'vue-router'
import { useMenuStore } from '@/stores/menu'

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
    await Swal.fire({
      icon: 'success',
      title: data.message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
  } catch (e) {
    console.error('Logout error', e);
  } finally {
    localStorage.removeItem('access_token');
    delete axios.defaults.headers.common['Authorization'];
    router.push({ name: 'login' });
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

    <!-- BEGIN: Search -->
    <div class="relative mr-3 intro-x sm:mr-6">
      <div class="relative hidden sm:block"></div>
    </div>
    <!-- END: Search -->

    <!-- BEGIN: Account Menu -->
    <Menu>
      <Menu.Button class="flex items-center justify-center overflow-hidden rounded-full shadow-lg zoom-in intro-x">
        <img alt="Application Logo" class="h-10 w-auto max-w-[42px] object-contain" :src="currentLogo" />
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

        <Menu.Item v-if="auth.user?.id_role === 1" as="button" class="hover:bg-white/5"
          @click="() => router.push({ name: 'users' })">
          <Lucide icon="Edit" class="w-4 h-4 mr-2" />
          Add Account
        </Menu.Item>

        <Menu.Item class="hover:bg-white/5">
          <Lucide icon="HelpCircle" class="w-4 h-4 mr-2" />
          Help
        </Menu.Item>

        <Menu.Divider class="bg-white/[0.08]" />

        <Menu.Item as="button" @click="onLogout" class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2">
          <Lucide icon="ToggleRight" class="w-4 h-4 mr-2" />
          Logout
        </Menu.Item>
      </Menu.Items>
    </Menu>
  </div>
</template>
