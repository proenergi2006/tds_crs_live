import type { AxiosInstance } from 'axios'
import { useAuthStore } from '@/stores/auth'

export function installAuthInterceptor(instance: AxiosInstance) {
  instance.interceptors.response.use(
    (res) => res,
    (err) => {
      if (err.response?.status === 401) {
        // abaikan 401 dari request /login itu sendiri (itu kredensial salah, bukan token expired)
        const requestUrl: string = err.config?.url || ''
        const isLoginRequest = requestUrl.includes('/login')

        if (!isLoginRequest) {
          // dipanggil di dalam callback biar Pinia udah pasti ke-install duluan saat instance ini dipasang
          const auth = useAuthStore()
          auth.forceLogout(err.response?.data?.reason)
        }
      }
      return Promise.reject(err)
    },
  )
}
