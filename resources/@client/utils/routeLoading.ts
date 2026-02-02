import { ref } from 'vue'

export const routeLoading = ref(false)

let holdTimer: ReturnType<typeof setTimeout> | null = null
let visibleSince = 0

/**
 * Tampilkan overlay segera.
 */
export function startRouteLoading() {
  if (holdTimer) {
    clearTimeout(holdTimer)
    holdTimer = null
  }
  if (!routeLoading.value) {
    routeLoading.value = true
    visibleSince = Date.now()
  }
}

/**
 * Sembunyikan overlay, tetapi tahan minimal `minVisibleMs` milidetik
 * agar mata sempat melihat (anti “kedip tak terlihat”).
 */
export function stopRouteLoading(minVisibleMs = 250) {
  const elapsed = Date.now() - visibleSince
  const wait = Math.max(0, minVisibleMs - elapsed)

  if (holdTimer) clearTimeout(holdTimer)
  holdTimer = setTimeout(() => {
    routeLoading.value = false
    holdTimer = null
  }, wait)
}
