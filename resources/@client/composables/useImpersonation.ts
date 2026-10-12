import { computed, onScopeDispose, ref } from 'vue'
import axios from 'axios'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'

function formatRemaining(expiresAt: string, nowMs: number): string {
  const diffMs = new Date(expiresAt).getTime() - nowMs
  if (diffMs <= 0) return 'Sudah berakhir'

  const totalMinutes = Math.floor(diffMs / 60000)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  return `${hours}j ${minutes}m`
}

export function useImpersonation() {
  const auth = useAuthStore()
  const { error } = useNotification()

  const leaveLoading = ref(false)
  const now = ref(Date.now())

  const isImpersonating = computed(() => auth.isImpersonating)
  const adminName = computed(() => auth.impersonationAdmin?.name ?? '')
  const remainingLabel = computed((): string => {
    const expiresAt = auth.user?.impersonation?.expires_at
    return expiresAt ? formatRemaining(expiresAt, now.value) : ''
  })

  const intervalId = setInterval(() => { now.value = Date.now() }, 60000)

  onScopeDispose(() => clearInterval(intervalId))

  async function leaveImpersonation() {
    leaveLoading.value = true
    try {
      const { data } = await axios.post('/api/impersonate/leave')

      auth.setToken(data.access_token)
      window.location.reload()
    } catch (e: any) {
      error('Gagal', e.response?.data?.message || 'Gagal kembali ke akun admin')
    } finally {
      leaveLoading.value = false
    }
  }

  return { isImpersonating, adminName, remainingLabel, leaveLoading, leaveImpersonation }
}
