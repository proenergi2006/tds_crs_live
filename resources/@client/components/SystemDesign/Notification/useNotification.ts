import { nextTick } from "vue";
import { notificationRef, notificationPayload } from "./notificationStore";

type NotificationOptions = {
  action?: {
    label: string;
    variant?: "primary" | "secondary";
    onClick: () => void;
  };
};

const actionHandlers = new Map<string, () => void>();
let actionListenerRegistered = false;
let notificationActionId = 0;
let notificationRenderId = 0;

function ensureActionListener() {
  if (actionListenerRegistered) return;

  window.addEventListener("app-notification-action", (event) => {
    const id = (event as CustomEvent<string>).detail;
    actionHandlers.get(id)?.();
  });

  actionListenerRegistered = true;
}

function renderNotification() {
  const renderId = ++notificationRenderId;

  nextTick(() => {
    if (renderId !== notificationRenderId) return;

    notificationRef.value?.showToast();
  });
}

export function useNotification() {
  function show(
    type: "success" | "error" | "warning" | "info",
    title: string,
    message?: string,
    options: NotificationOptions = {},
  ) {
    actionHandlers.clear();

    const action = options.action
      ? {
          id: `notification-action-${++notificationActionId}`,
          label: options.action.label,
          variant: options.action.variant ?? "secondary",
        }
      : null;

    if (options.action && action) {
      ensureActionListener();
      actionHandlers.set(action.id, options.action.onClick);
    }

    notificationPayload.value = {
      type,
      title,
      message: message ?? "",
      action,
    };

    renderNotification();
  }

  return {
    success(title: string, message?: string, options?: NotificationOptions) {
      show("success", title, message, options);
    },

    error(title: string, message?: string, options?: NotificationOptions) {
      show("error", title, message, options);
    },

    warning(title: string, message?: string, options?: NotificationOptions) {
      show("warning", title, message, options);
    },

    info(title: string, message?: string, options?: NotificationOptions) {
      show("info", title, message, options);
    },
  };
}
