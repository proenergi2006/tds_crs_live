import { notificationRef, notificationPayload } from "./notificationStore";

export function useNotification() {
  function show(
    type: "success" | "error" | "warning" | "info",
    title: string,
    message?: string,
  ) {
    notificationPayload.value = {
      type,
      title,
      message: message ?? "",
    };

    notificationRef.value?.showToast();
  }

  return {
    success(title: string, message?: string) {
      show("success", title, message);
    },

    error(title: string, message?: string) {
      show("error", title, message);
    },

    warning(title: string, message?: string) {
      show("warning", title, message);
    },

    info(title: string, message?: string) {
      show("info", title, message);
    },
  };
}
