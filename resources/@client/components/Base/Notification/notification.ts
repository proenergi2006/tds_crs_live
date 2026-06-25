import Toastify from "toastify-js";
import {
  type NotificationElement,
  type NotificationProps,
} from "./Notification.vue";

const toastifyClass = "_" + Math.random().toString(36).substr(2, 9);

const bindNotificationButtons = (el: NotificationElement) => {
  el
    .querySelectorAll("[data-notification-action]")
    .forEach(function (button) {
      const actionButton = button as HTMLElement;

      if (actionButton.dataset.notificationActionBound) return;

      actionButton.dataset.notificationActionBound = "true";
      actionButton.addEventListener("click", function () {
        const action = actionButton.getAttribute("data-notification-action");

        if (action) {
          window.dispatchEvent(
            new CustomEvent("app-notification-action", {
              detail: action,
            }),
          );
        }
      });
    });

  el
    .querySelectorAll("[data-dismiss='notification']")
    .forEach(function (button) {
      const dismissButton = button as HTMLElement;

      if (dismissButton.dataset.notificationDismissBound) return;

      dismissButton.dataset.notificationDismissBound = "true";
      dismissButton.addEventListener("click", function () {
        el.toastify.hideToast();
      });
    });
};

const init = (el: NotificationElement, props: NotificationProps) => {
  el.showToast = () => {
    const clonedEl = el.cloneNode(true) as NotificationElement;
    clonedEl.classList.remove("hidden");
    clonedEl.classList.add(toastifyClass);
    clonedEl.toastify = Toastify({
      duration: -1,
      newWindow: true,
      close: true,
      gravity: "top",
      position: "right",
      stopOnFocus: true,
      ...props.options,
      node: clonedEl,
    });
    clonedEl.toastify.showToast();
    bindNotificationButtons(clonedEl);

    el.hideToast = () => {
      document.querySelectorAll(`.${toastifyClass}`).forEach(function (el) {
        const toastifyEl = el as NotificationElement;
        toastifyEl.toastify.hideToast();
      });
    };
  };
};

const reInit = (el: NotificationElement) => {
  const wrapperEl = document.querySelectorAll(`.${toastifyClass}`)[0];
  if (wrapperEl) {
    const toastifyEl = wrapperEl as NotificationElement;
    toastifyEl.innerHTML = el.innerHTML;
    bindNotificationButtons(toastifyEl);
  }
};

export { init, reInit };
