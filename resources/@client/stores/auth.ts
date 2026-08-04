// resources/@client/stores/auth.ts
import { defineStore } from 'pinia'
import axios from 'axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as {
      id: string
      name: string
      email: string
      id_role: number
      two_factor_secret: string | null
      permissions: string[]
      impersonation: { admin: { id: number; name: string } | null; expires_at: string } | null
    } | null,

    // isLoggingOut dipindah dari module-level let ke state Pinia supaya reaktif
    // (getter isForceLoggingOut sebelumnya membaca closure, bukan state).
    isLoggingOut: false,
    sessionExpiredDialog: {
      open: false,
      message: 'Session expired. Please login again.',
    } as { open: boolean; message: string },
  }),

  getters: {
    // Cek apakah user memiliki permission tertentu.
    // Return false (bukan error) jika user null atau permissions belum ter-load.
    // Admin sudah mendapat semua 23 permission dari backend (via $appends accessor),
    // sehingga getter ini cukup array.includes() tanpa perlu bypass khusus di FE.
    can: (state) => (permission: string): boolean =>
      state.user?.permissions?.includes(permission) ?? false,

    // Expose isLoggingOut flag agar router guard bisa cek apakah forceLogout()
    // sedang/baru saja menangani sebuah 401, sebelum guard lain melakukan
    // clear+redirect-nya sendiri (cegah double-redirect).
    isForceLoggingOut: (state) => state.isLoggingOut,

    isImpersonating: (state): boolean => state.user?.impersonation != null,

    impersonationAdmin: (state): { id: number; name: string } | null =>
      state.user?.impersonation?.admin ?? null,
  },

  actions: {
    async fetchUser() {
      try {
        const { data } = await axios.get('/api/user')
        this.user = data
      } catch (err: any) {
        this.user = null

        // Token expired/invalid → paksa logout (clear token + redirect).
        // Error lain (network hiccup, 500, dll) cukup null-kan user tanpa logout.
        if (err?.response?.status === 401) {
          this.forceLogout(err?.response?.data?.reason)
        }
      }
    },

    setToken(token: string) {
      localStorage.setItem('access_token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },

    clear() {
      this.user = null
      localStorage.removeItem('access_token')
      delete axios.defaults.headers.common['Authorization']
    },

    // forceLogout jadi deklaratif (set state) alih-alih imperatif (Swal) — dialog
    // session-expired sekarang dirender oleh host terpisah (App.vue) yang membaca state ini.
    forceLogout(reason?: string) {
      // Cegah dobel trigger (mis. dari axios interceptor & fetchUser() untuk
      // 401 yang sama) — jangan tampilkan dialog dua kali.
      if (this.isLoggingOut) return
      this.isLoggingOut = true
      this.sessionExpiredDialog = {
        open: true,
        message: 'Session expired. Please login again.',
      }
    },

    confirmSessionExpired() {
      this.sessionExpiredDialog.open = false
      this.clear()
      router.push({ name: 'login' })
      this.isLoggingOut = false
    },
  }
})
