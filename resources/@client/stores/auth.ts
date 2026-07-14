// resources/@client/stores/auth.ts
import { defineStore } from 'pinia'
import axios from 'axios'
import router from '@/router'
import Swal from 'sweetalert2'

// Guard modul-level: cegah forceLogout() jalan dobel kalau dipicu bersamaan
// oleh interceptor axios global (main.ts) dan fetchUser() (dipanggil dari
// router guard) untuk 401 yang sama.
let isLoggingOut = false

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

    // Expose module-level isLoggingOut flag agar router guard bisa cek apakah
    // forceLogout() sedang/baru saja menangani sebuah 401, sebelum guard lain
    // melakukan clear+redirect-nya sendiri (cegah double-redirect).
    isForceLoggingOut: (): boolean => isLoggingOut,
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
          this.forceLogout()
        }
      }
    },

    clear() {
      this.user = null
      localStorage.removeItem('access_token')
      delete axios.defaults.headers.common['Authorization']
    },

    forceLogout(message = 'Session expired. Please login again.') {
      // Cegah dobel trigger (mis. dari axios interceptor & fetchUser() untuk
      // 401 yang sama) — jangan tampilkan toast/redirect dua kali.
      if (isLoggingOut) return
      isLoggingOut = true

      Swal.fire({
        icon: 'warning',
        title: 'Logged out',
        text: message,
        confirmButtonText: 'OK',
        allowOutsideClick: false,
      }).then(() => {
        this.clear()
        router.push({ name: 'login' })
        isLoggingOut = false
      })
    }
  }
})
