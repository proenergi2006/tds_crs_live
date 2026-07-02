<script setup lang="ts">
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { Menu } from '@/components/Base/Headless'
import defaultLogoUrl from '@/assets/images/logo-tds-1.png'
import agenLogoUrl from '@/assets/images/logo-proenergi.png'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useAccount } from '@/composables/useAccount'

withDefaults(defineProps<{
  showUserInfo?: boolean
}>(), {
  showUserInfo: true,
})

const router = useRouter()
const auth = useAuthStore()

const { userName, userEmail, onLogout } = useAccount()

const agenRoles = [13, 14, 15, 16]

const isAgenRole = computed(() => {
  return agenRoles.includes(Number(auth.user?.id_role))
})

const currentLogo = computed(() => {
  return isAgenRole.value ? agenLogoUrl : defaultLogoUrl
})
</script>

<template>
  <Menu>
    <Menu.Button class="flex items-center gap-3 intro-x">
      <!-- User info: hidden on mobile -->
      <div v-if="showUserInfo" class="text-right block">
        <div class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-tight">
          {{ userName }}
        </div>
        <div class="text-xs text-slate-400 dark:text-slate-500 leading-tight mt-0.5">
          {{ userEmail }}
        </div>
      </div>

      <!-- Avatar -->
      <div class="flex items-center justify-center overflow-hidden rounded-full shadow-lg zoom-in h-10 w-10 shrink-0">
        <img alt="Application Logo" class="h-full w-full object-contain" :src="currentLogo" />
      </div>
    </Menu.Button>

    <Menu.Items class="w-56 mt-px text-white bg-primary" placement="bottom-end" transition>
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

      <Menu.Item as="button" @click="onLogout" class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2">
        <Lucide icon="ToggleRight" class="w-4 h-4 mr-2" />
        Logout
      </Menu.Item>
    </Menu.Items>
  </Menu>
</template>
