<script setup lang="ts">
import { ref, computed } from 'vue'
import Lucide from "@/components/Base/Lucide";
import Breadcrumb from "@/components/Base/Breadcrumb";
import AccountMenu from "@/components/SystemDesign/AccountMenu.vue";
import { type Menu as MenuItem } from "@/stores/menu";
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

// brand proenergi dari backend (UserAuthResource.brand), bukan replikasi bucket role manual
const isAgenRole = computed(() => {
  return auth.user?.brand === 'proenergi'
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
</script>

<template>
  <div
    class="relative z-[51] hidden md:flex h-[73px] items-center border-b border-slate-200 bg-slate-100 dark:bg-darkmode-700 md:px-6">
    <button type="button"
      class="mr-3 hidden h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-200/70 hover:text-slate-700 xl:flex"
      :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="emit('toggle-sidebar-collapse')">
      <Lucide :icon="isSidebarCollapsed ? 'PanelRightOpen' : 'PanelLeftClose'" class="h-5 w-5" />
    </button>

    <Breadcrumb class="hidden mr-auto -intro-x sm:flex">
      <Breadcrumb.Link v-for="(item, index) in breadcrumbs" :key="`${item.title}-${index}`" :to="item.to"
        :active="item.active" :disabled="item.disabled">
        {{ item.title }}
      </Breadcrumb.Link>
    </Breadcrumb>

    <div class="ml-auto flex items-center gap-3">
      <div class="flex items-center gap-2 intro-x">
      </div>

      <!-- Divider Vertikal -->
      <div class="hidden h-8 w-px bg-slate-200 dark:bg-darkmode-400 sm:block" />

      <AccountMenu />
    </div>
  </div>
</template>
