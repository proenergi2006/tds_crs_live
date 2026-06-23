import { ref } from "vue";

export type NotificationAction = {
  id: string;
  label: string;
  variant?: "primary" | "secondary";
};

/** Diisi oleh AppNotification.vue saat mount; memicu tampilnya satu toast baru. */
export const notificationRef = ref<{ showToast: () => void } | null>(null);

export const notificationPayload = ref({
  type: "success",
  title: "",
  message: "",
  action: null as NotificationAction | null,
  items: [] as string[],
  sticky: false,
});
