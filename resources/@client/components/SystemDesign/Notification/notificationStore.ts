import { ref } from "vue";
import type { NotificationElement } from "@/components/Base/Notification/Notification.vue";

export const notificationRef = ref<NotificationElement | null>(null);

export const notificationPayload = ref({
  type: "success",
  title: "",
  message: "",
});
