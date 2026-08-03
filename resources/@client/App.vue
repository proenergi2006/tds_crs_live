<template>
  <RouterView />
  <RouteLoading />
  <ImpersonationBanner />
</template>


<script setup lang="ts">
import RouteLoading from '@/components/RouteLoading.vue'
import ImpersonationBanner from '@/components/ImpersonationBanner.vue'
</script>
