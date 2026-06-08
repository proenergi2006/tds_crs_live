import { ref } from "vue";
import type { NotificationElement } from "@/components/Base/Notification/Notification.vue";

export type NotificationAction = {
  id: string;
  label: string;
  variant?: "primary" | "secondary";
};

export const notificationRef = ref<NotificationElement | null>(null);

export const notificationPayload = ref({
  type: "success",
  title: "",
  message: "",
  sticky: false,
  actions: [] as NotificationAction[],
});
