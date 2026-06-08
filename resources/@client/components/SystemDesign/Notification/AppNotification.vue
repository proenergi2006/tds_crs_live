<script setup lang="ts">
import { computed, provide } from 'vue';

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

const notificationOptions = computed(() => ({
  duration: notificationPayload.value.sticky ? -1 : 3000,
  close: !notificationPayload.value.actions.length,
}));
</script>

<template>
  <Notification refKey="appNotification" :options="notificationOptions" class="relative flex">
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

    <div class="ml-4" :class="notificationPayload.actions.length ? 'sm:mr-40' : 'mr-4'">
      <div class="font-medium">
        {{ notificationPayload.title }}
      </div>

      <div v-if="notificationPayload.message" class="mt-1 text-slate-500">
        {{ notificationPayload.message }}
      </div>
    </div>

    <div
      v-if="notificationPayload.actions.length"
      class="absolute bottom-0 right-0 top-0 flex flex-col border-l border-slate-200/60"
    >
      <button
        v-for="(action, index) in notificationPayload.actions"
        :key="action.id"
        type="button"
        data-dismiss="notification"
        :data-notification-action="action.id"
        class="flex flex-1 items-center justify-center px-6 text-sm font-medium"
        :class="[
          index < notificationPayload.actions.length - 1
            ? 'border-b border-slate-200/60'
            : '',
          action.variant === 'primary'
            ? 'text-primary'
            : 'text-slate-500',
        ]"
      >
        {{ action.label }}
      </button>
    </div>
  </Notification>
</template>
