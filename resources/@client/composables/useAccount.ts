import { computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

export function useAccount() {
  const router = useRouter()
  const auth = useAuthStore()
  const notification = useNotification()

  const userName = computed(() => auth.user?.name || 'Guest')
  const userEmail = computed(() => auth.user?.email || '-')

  async function onLogout() {
    try {
      const { data } = await axios.post('/api/logout');
      notification.success(data.message)
    } catch (e) {
      console.error('Logout error', e);
    } finally {
      localStorage.removeItem('access_token');
      delete axios.defaults.headers.common['Authorization'];
      router.push({ name: 'login' });
    }
  }

  return { userName, userEmail, onLogout }
}
