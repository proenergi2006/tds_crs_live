<script setup lang="ts">
import { provide } from 'vue';

import Notification from '@/components/Base/Notification';
import Lucide from '@/components/Base/Lucide';

import {
  notificationRef,
  notificationPayload,
} from './notificationStore';

provide(
  'bind[appNotification]',
  (el: any) => {
    notificationRef.value = el;
  },
);
</script>

<template>
  <Notification refKey="appNotification" :options="{
    duration: 3000,
  }" class="flex">
    <Lucide :icon="notificationPayload.type === 'success'
      ? 'CheckCircle'
      : notificationPayload.type === 'error'
        ? 'XCircle'
        : notificationPayload.type === 'warning'
          ? 'AlertTriangle'
          : 'Info'
      " :class="{
        'text-success': notificationPayload.type === 'success',
        'text-danger': notificationPayload.type === 'error',
        'text-warning': notificationPayload.type === 'warning',
        'text-primary': notificationPayload.type === 'info',
      }" />

    <div class="ml-4 mr-4">
      <div class="font-medium">
        {{ notificationPayload.title }}
      </div>

      <div v-if="notificationPayload.message" class="mt-1 text-slate-500">
        {{ notificationPayload.message }}
      </div>

      <div v-if="notificationPayload.action" class="mt-1.5 flex font-medium">
        <button
          type="button"
          data-dismiss="notification"
          :data-notification-action="notificationPayload.action.id"
          class="text-primary dark:text-slate-400"
          :class="notificationPayload.action.variant === 'secondary' ? 'text-slate-500' : ''"
        >
          {{ notificationPayload.action.label }}
        </button>
      </div>
    </div>
  </Notification>
</template>
