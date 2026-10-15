import axios from 'axios'
import type { Router } from 'vue-router'

export const FORBIDDEN_ACTION_MESSAGE = 'Anda tidak berwenang melakukan aksi ini.'

function safeDecodeURI(path: string): string {
  try {
    return decodeURI(path)
  } catch {
    return path
  }
}

// pathMatch wajib diisi, kalau kosong catch-all ter-resolve ke "/"
export function redirectToErrorPage(router: Router, status: number | undefined): boolean {
  if (status === 403) {
    router.replace({ name: 'forbidden' })
    return true
  }

  if (status === 404) {
    router.replace({
      name: 'not-found',
      params: { pathMatch: safeDecodeURI(router.currentRoute.value.path).substring(1).split('/') },
    })
    return true
  }

  return false
}

export function actionErrorMessage(error: unknown, fallback: string): string {
  if (!axios.isAxiosError(error)) return fallback
  if (error.response?.status === 403) return FORBIDDEN_ACTION_MESSAGE
  return error.response?.data?.message ?? fallback
}
