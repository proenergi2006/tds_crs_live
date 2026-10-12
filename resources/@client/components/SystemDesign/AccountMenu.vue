<script setup lang="ts">
import { computed, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { Menu } from '@/components/Base/Headless'
import AccountSettingsModal from '@/components/SystemDesign/Dialog/AccountSettingsModal.vue'
import { useAccount } from '@/composables/useAccount'
import { useImpersonation } from '@/composables/useImpersonation'
import { useAuthStore } from '@/stores/auth'
import defaultLogoUrl from '@/assets/images/logo-tds-1.png'
import agenLogoUrl from '@/assets/images/logo-proenergi.png'

withDefaults(defineProps<{
  showUserInfo?: boolean
}>(), {
  showUserInfo: true,
})

const auth = useAuthStore()

const { userName, userEmail, onLogout } = useAccount()
const { isImpersonating, adminName, remainingLabel, leaveLoading, leaveImpersonation } = useImpersonation()

const isAgenRole = computed(() => {
  return auth.user?.brand === 'proenergi'
})

const currentLogo = computed(() => {
  return isAgenRole.value ? agenLogoUrl : defaultLogoUrl
})

const showSettingsModal = ref(false)
</script>

<template>
  <Menu>
    <Menu.Button
      class="flex items-center gap-3 intro-x"
      :title="isImpersonating ? 'Sedang impersonate' : undefined"
    >
      <div v-if="showUserInfo" class="text-right block">
        <div class="text-sm font-medium text-slate-700 dark:text-slate-200 leading-tight">
          {{ userName }}
        </div>
        <div class="text-xs text-slate-400 dark:text-slate-500 leading-tight mt-0.5">
          {{ userEmail }}
        </div>
      </div>

      <div class="relative shrink-0">
        <div
          class="flex items-center justify-center overflow-hidden rounded-full shadow-lg zoom-in h-10 w-10"
          :class="isImpersonating && 'ring-2 ring-amber-400 ring-offset-2'"
        >
          <img alt="Application Logo" class="h-full w-full object-contain" :src="currentLogo" />
        </div>
        <span
          v-if="isImpersonating"
          class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-white ring-2 ring-white"
        >
          <Lucide icon="UserCog" class="h-3 w-3" />
        </span>
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

      <template v-if="isImpersonating">
        <div class="mx-2 my-2 rounded-md bg-amber-400/20 px-3 py-2 text-amber-100">
          <div class="flex items-start gap-2 text-xs">
            <Lucide icon="UserCog" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>Impersonate oleh <strong class="text-white">{{ adminName }}</strong></span>
          </div>
          <div class="mt-1 text-xs text-amber-200/80">
            Berakhir dalam {{ remainingLabel }}
          </div>
          <Menu.Item
            as="button"
            type="button"
            :disabled="leaveLoading"
            @click="leaveImpersonation"
            class="mt-2 flex w-full items-center justify-center rounded-md bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <Lucide icon="Undo2" class="mr-2 h-4 w-4" />
            Kembali ke Admin
          </Menu.Item>
        </div>

        <Menu.Divider class="bg-white/[0.08]" />
      </template>

      <Menu.Item as="button" @click="showSettingsModal = true" class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2">
        <Lucide icon="Settings" class="w-4 h-4 mr-2" />
        Settings
      </Menu.Item>

      <Menu.Divider class="bg-white/[0.08]" />

      <Menu.Item as="button" @click="onLogout" class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2">
        <Lucide icon="ToggleRight" class="w-4 h-4 mr-2" />
        Logout
      </Menu.Item>
    </Menu.Items>
  </Menu>

  <AccountSettingsModal :open="showSettingsModal" @close="showSettingsModal = false" />
</template>
