// resources/@client/stores/auth.ts
import { defineStore } from 'pinia'
import axios from 'axios'
import router from '@/router'
import Swal from 'sweetalert2'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as {
      id: string
      name: string
      email: string
      id_role: number
      two_factor_secret: string | null
      permissions: string[]
    } | null,
  }),

  getters: {
    // Cek apakah user memiliki permission tertentu.
    // Return false (bukan error) jika user null atau permissions belum ter-load.
    // Admin sudah mendapat semua 23 permission dari backend (via $appends accessor),
    // sehingga getter ini cukup array.includes() tanpa perlu bypass khusus di FE.
    can: (state) => (permission: string): boolean =>
      state.user?.permissions?.includes(permission) ?? false,
  },

  actions: {
    async fetchUser() {
      try {
        const { data } = await axios.get('/api/user')
        this.user = data
      } catch {
        this.user = null
      }
    },

    clear() {
      this.user = null
      localStorage.removeItem('access_token')
      delete axios.defaults.headers.common['Authorization']
    },

    forceLogout(message = 'Session expired. Please login again.') {
      Swal.fire({
        icon: 'warning',
        title: 'Logged out',
        text: message,
        confirmButtonText: 'OK',
        allowOutsideClick: false,
      }).then(() => {
        this.clear()
        router.push({ name: 'login' })
      })
    }
  }
})
