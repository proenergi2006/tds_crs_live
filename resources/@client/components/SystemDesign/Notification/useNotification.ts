import {
  notificationRef,
  notificationPayload,
  type NotificationAction,
} from "./notificationStore";
import { nextTick } from "vue";

type ActionNotification = {
  type?: "success" | "error" | "warning" | "info";
  title: string;
  message?: string;
  actions: Array<NotificationAction & {
    onClick?: () => void;
  }>;
};

type NotificationOptions = {
  withAction?: boolean;
  actions?: Array<NotificationAction & {
    onClick?: () => void;
  }>;
};

const actionHandlers = new Map<string, () => void>();
let actionListenerRegistered = false;
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
    if (options.withAction) {
      showWithActions({
        type,
        title,
        message,
        actions: options.actions ?? [],
      });
      return;
    }

    notificationRef.value?.hideToast();

    notificationPayload.value = {
      type,
      title,
      message: message ?? "",
      sticky: false,
      actions: [],
    };

    renderNotification();
  }

  function showWithActions(options: ActionNotification) {
    ensureActionListener();
    actionHandlers.clear();
    notificationRef.value?.hideToast();

    const actions = options.actions.map((action) => {
      if (action.onClick) {
        actionHandlers.set(action.id, action.onClick);
      }

      return {
        id: action.id,
        label: action.label,
        variant: action.variant ?? "secondary",
      };
    });

    notificationPayload.value = {
      type: options.type ?? "success",
      title: options.title,
      message: options.message ?? "",
      sticky: true,
      actions,
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

    action(options: ActionNotification) {
      showWithActions(options);
    },
  };
}
