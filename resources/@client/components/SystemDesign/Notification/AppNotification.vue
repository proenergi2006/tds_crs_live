<script setup lang="ts">
import '@/assets/css/vendors/toastify.css';
import { onMounted, ref } from 'vue';
import Toastify from 'toastify-js';

import Lucide from '@/components/Base/Lucide';

import {
  notificationRef,
  notificationPayload,
} from './notificationStore';
import Button from '@/components/Base/Button';

/*
 | Tiap notifikasi dirender sebagai snapshot DOM independen: `showToast`
 | meng-clone node template (yang reaktif terhadap payload saat ini) lalu
 | menyerahkannya ke Toastify. Karena tiap toast adalah node terpisah, payload
 | yang berubah untuk notifikasi berikutnya TIDAK menyentuh toast yang sudah
 | tampil — termasuk sticky error. (Sengaja tidak memakai mekanisme reInit
 | bawaan Base Notification yang menimpa isi toast yang sedang aktif.)
 */
const templateRef = ref<HTMLElement | null>(null);

function bindButtons(node: HTMLElement, instance: ReturnType<typeof Toastify>) {
  node.querySelectorAll('[data-notification-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const action = btn.getAttribute('data-notification-action');
      if (action) {
        window.dispatchEvent(
          new CustomEvent('app-notification-action', { detail: action }),
        );
      }
    });
  });

  node.querySelectorAll("[data-dismiss='notification']").forEach((btn) => {
    btn.addEventListener('click', () => instance.hideToast());
  });
}

function showToast() {
  if (!templateRef.value) return;

  const node = templateRef.value.cloneNode(true) as HTMLElement;
  node.classList.remove('hidden');

  const instance = Toastify({
    node,
    duration: notificationPayload.value.sticky ? -1 : 3000,
    newWindow: true,
    close: false,
    gravity: 'top',
    position: 'right',
    stopOnFocus: true,
  });

  instance.showToast();
  bindButtons(node, instance);
}

onMounted(() => {
  notificationRef.value = { showToast };
});
</script>

<template>
  <div ref="templateRef"
    class="hidden flex py-5 pl-5 pr-14 bg-white border rounded-lg shadow-xl border-slate-200/60 dark:bg-darkmode-600 dark:text-slate-300 dark:border-darkmode-600">
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
      <div class="font-strong">
        {{ notificationPayload.title }}
      </div>

      <div v-if="notificationPayload.message" class="font-body mt-1 whitespace-pre-line break-words">
        {{ notificationPayload.message }}
      </div>

      <ul v-if="notificationPayload.items && notificationPayload.items.length"
        class="font-body mt-1.5 max-h-60 list-disc space-y-0.5 overflow-auto pl-4">
        <li v-for="(item, i) in notificationPayload.items" :key="i" class="break-words">
          {{ item }}
        </li>
      </ul>

      <div v-if="notificationPayload.sticky" class="mt-4">
        <Button type="button" size="sm" variant="outline-secondary" data-dismiss="notification">
          Tutup
        </Button>
      </div>

      <div v-if="notificationPayload.action" class="mt-1.5 flex">
        <button type="button" data-dismiss="notification" :data-notification-action="notificationPayload.action.id"
          class="font-strong !text-primary dark:!text-slate-400"
          :class="notificationPayload.action.variant === 'secondary' ? '!text-slate-500' : ''">
          {{ notificationPayload.action.label }}
        </button>
      </div>
    </div>
  </div>
</template>
