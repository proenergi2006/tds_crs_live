<script setup lang="ts">
import { useRouter } from 'vue-router'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import type { Icon } from '@/components/Base/Lucide/Lucide.vue'

defineProps<{
  variant: '403' | '404'
}>()

const router = useRouter()

const ERROR_STATE_CONTENT: Record<'403' | '404', { icon: Icon; title: string; description: string }> = {
  '403': {
    icon: 'ShieldAlert',
    title: 'Akses ditolak',
    description: 'Anda tidak memiliki izin untuk membuka halaman atau data ini.',
  },
  '404': {
    icon: 'SearchX',
    title: 'Halaman tidak ditemukan',
    description: 'Alamat salah, atau data yang Anda cari sudah tidak ada.',
  },
}

function goBack(): void {
  const hasInAppHistory = Boolean(window.history.state?.back)

  if (hasInAppHistory) router.back()
  else goToDashboard()
}

function goToDashboard(): void {
  router.push({ name: 'dashboard-overview-1' })
}
</script>

<template>
  <div class="page-content-wrapper">
    <div class="flex min-h-[420px] flex-col items-center justify-center gap-2 text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
        <Lucide :icon="ERROR_STATE_CONTENT[variant].icon" class="h-7 w-7 text-rose-500" />
      </div>
      <p class="num-display text-slate-700">{{ variant }}</p>
      <h3 class="text-section-title">{{ ERROR_STATE_CONTENT[variant].title }}</h3>
      <p class="text-body max-w-md">{{ ERROR_STATE_CONTENT[variant].description }}</p>

      <div class="mt-4 flex items-center gap-2">
        <Button variant="outline-secondary" @click="goBack">
          <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
          Kembali
        </Button>
        <Button variant="primary" @click="goToDashboard">
          <Lucide icon="Home" class="mr-2 h-4 w-4" />
          Ke Dashboard
        </Button>
      </div>
    </div>
  </div>
</template>
