import type { AxiosInstance } from 'axios'
import { useAuthStore } from '@/stores/auth'

export function installAuthInterceptor(instance: AxiosInstance) {
  instance.interceptors.response.use(
    (res) => res,
    (err) => {
      if (err.response?.status === 401) {
        // token expired / session expired — abaikan kalau yang gagal adalah
        // request login itu sendiri (401 di sana berarti kredensial salah,
        // bukan token expired), jangan trigger forceLogout untuk kasus itu.
        const requestUrl: string = err.config?.url || ''
        const isLoginRequest = requestUrl.includes('/login')

        if (!isLoginRequest) {
          // useAuthStore() dipanggil di dalam callback error (bukan top-level module)
          // supaya Pinia sudah pasti ter-install duluan saat instance ini dipasang
          // (main.ts App mount), baik untuk axios global maupun resourceApi.
          const auth = useAuthStore()
          auth.forceLogout(err.response?.data?.reason)
        }
      }
      return Promise.reject(err)
    },
  )
}
