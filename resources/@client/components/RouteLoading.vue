<template>
    <teleport to="body">
      <transition name="fade">
        <div
          v-if="isLoading"
          class="fixed inset-0 z-[99999] bg-black/20 backdrop-blur-[2px] flex items-center justify-center"
          aria-live="polite"
          aria-busy="true"
        >
          <div class="bg-white rounded-full p-3 shadow-lg">
            <Lucide icon="Loader2" class="w-8 h-8 animate-spin text-slate-700" />
          </div>
        </div>
      </transition>
    </teleport>
  </template>
  
  <script setup lang="ts">
  import { computed } from 'vue'
  import { routeLoading } from '@/utils/routeLoading'
  import Lucide from '@/components/Base/Lucide'
  const isLoading = computed(() => routeLoading.value)
  </script>
  
  <style scoped>
  .fade-enter-active, .fade-leave-active { transition: opacity .15s ease }
  .fade-enter-from, .fade-leave-to { opacity: 0 }
  </style>
  