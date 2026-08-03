<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { useAuthStore } from '@/stores/auth'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const auth = useAuthStore()
const { error } = useNotification()

const leaveLoading = ref(false)
const leaveError = ref<string | null>(null)
const now = ref(Date.now())

function formatRemaining(expiresAt: string, nowMs: number): string {
  const diffMs = new Date(expiresAt).getTime() - nowMs
  if (diffMs <= 0) return 'Sudah berakhir'

  const totalMinutes = Math.floor(diffMs / 60000)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  return `${hours}j ${minutes}m`
}

const remainingLabel = computed((): string => {
  const expiresAt = auth.user?.impersonation?.expires_at
  return expiresAt ? formatRemaining(expiresAt, now.value) : ''
})

let intervalId: ReturnType<typeof setInterval> | undefined

onMounted(() => {
  intervalId = setInterval(() => { now.value = Date.now() }, 60000)
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})

async function leaveImpersonation() {
  leaveLoading.value = true
  leaveError.value = null
  try {
    const { data } = await axios.post('/api/impersonate/leave')

    auth.setToken(data.access_token)
    window.location.reload()
  } catch (e: any) {
    const message = e.response?.data?.message || 'Gagal kembali ke akun admin'
    leaveError.value = message
    error('Gagal', message)
  } finally {
    leaveLoading.value = false
  }
}
</script>

<template>
  <teleport to="body">
    <div v-if="auth.isImpersonating"
      class="fixed inset-x-0 top-0 z-[9998] flex flex-wrap items-center justify-between gap-3 bg-amber-500 px-4 py-2 text-sm text-white shadow-md">
      <div class="flex items-center gap-2">
        <Lucide icon="UserCog" class="h-4 w-4" />
        <span>
          Sedang impersonate <strong>{{ auth.user?.name }}</strong>
          sebagai admin: <strong>{{ auth.impersonationAdmin?.name }}</strong>
          — berakhir dalam {{ remainingLabel }}
        </span>
      </div>

      <div class="flex items-center gap-3">
        <span v-if="leaveError" class="text-rose-100">{{ leaveError }}</span>
        <Button variant="outline-secondary" class="!h-8 !border-white !text-white"
          :loading="leaveLoading" @click="leaveImpersonation">
          Kembali ke Admin
        </Button>
      </div>
    </div>
  </teleport>
</template>
