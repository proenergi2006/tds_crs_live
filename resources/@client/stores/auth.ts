import { defineStore } from 'pinia'
import axios from 'axios'
import router from '@/router'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as {
      id: string
      name: string
      email: string
      permissions: string[]
      roles: { id: number; name: string }[]
      primary_role: { id: number; name: string } | null
      brand: 'tds' | 'proenergi'
      impersonation: { admin: { id: number; name: string } | null; expires_at: string } | null
    } | null,

    // dipindah dari module-level let ke state Pinia biar reaktif
    isLoggingOut: false,
    sessionExpiredDialog: {
      open: false,
      message: 'Session expired. Please login again.',
    } as { open: boolean; message: string },
  }),

  getters: {
    // admin bypass semua permission check -- cermin Gate::before backend (roles, bukan permissions[] yang bisa kosong)
    can: (state) => (permission: string): boolean =>
      state.user?.roles?.some(r => r.name === 'Administrator')
        ? true
        : state.user?.permissions?.includes(permission) ?? false,

    hasRole: (state) => (roleId: number): boolean =>
      state.user?.roles?.some(r => r.id === roleId) ?? false,

    // dipakai router guard buat cek forceLogout udah/lagi jalan, cegah double-redirect
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

        // token expired/invalid doang yang force logout, error lain (network/500) cukup null-kan user
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

    // deklaratif (set state) — dialog session-expired dirender App.vue yang baca state ini
    forceLogout(reason?: string) {
      // cegah dobel trigger dari axios interceptor & fetchUser() buat 401 yang sama
      if (this.isLoggingOut) return
      this.isLoggingOut = true
      this.sessionExpiredDialog = {
        open: true,
        message: reason === 'session_expired'
          ? 'Session expired. Please login again.'
          : 'Your session is invalid. Please login again.',
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
