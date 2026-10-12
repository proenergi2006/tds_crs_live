<template>
  <RouterView />
  <RouteLoading />
  <AppNotification />
  <ConfirmDialog
    :open="auth.sessionExpiredDialog.open"
    title="Logged out"
    :description="auth.sessionExpiredDialog.message"
    icon="AlertTriangle"
    icon-class="bg-orange-100 text-orange-500"
    variant="warning"
    confirm-text="OK"
    :show-cancel="false"
    :static-backdrop="true"
    @confirm="auth.confirmSessionExpired()"
  />
</template>


<script setup lang="ts">
import RouteLoading from '@/components/RouteLoading.vue'
import AppNotification from '@/components/SystemDesign/Notification/AppNotification.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>
