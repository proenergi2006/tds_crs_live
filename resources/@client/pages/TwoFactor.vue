<template>
  <div
    class="p-3 sm:px-8 relative h-screen bg-primary xl:bg-white dark:bg-darkmode-800"
  >
    <ThemeSwitcher />
    <div class="container z-10 sm:px-10 mx-auto flex items-center justify-center h-full">
      <div class="bg-white p-8 rounded shadow-md w-full sm:w-3/4 lg:w-1/2">
        <h2 class="text-3xl mb-6 text-center">Two-Factor Authentication</h2>
        <!-- 6-digit input -->
        <div class="flex justify-center space-x-2 mb-6">
          <input
            v-for="(_, i) in digits"
            :key="i"
            v-model="digits[i]"
            @input="onDigitInput(i, $event)"
            @keydown="onKeydown(i, $event)"
            ref="inputs"
            type="text"
            inputmode="numeric"
            pattern="\d*"
            maxlength="1"
            class="w-12 h-12 text-center text-xl border rounded focus:outline-none focus:ring"
          />
        </div>
        <Button
          type="button"
          variant="primary"
          class="w-full"
          :loading="loading"
          @click="onVerify"
        >
          Verify
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

// komponen UI
import ThemeSwitcher from '@/components/ThemeSwitcher'
import Button from '@/components/Base/Button'

// ambil router & route
const router = useRouter()
const route  = useRoute()

// baca user_id dari query
const userId = Number(route.query.user_id || 0)
if (!userId) {
  router.replace({ name: 'login' })
}

// state untuk 6 digit & ref elemen
const digits = ref<string[]>(Array(6).fill(''))
const inputs = ref<HTMLInputElement[]>([])
const loading = ref(false)

// langsung fokus ke kotak pertama setelah mount
onMounted(() => {
  nextTick(() => inputs.value[0]?.focus())
})

function onDigitInput(i: number, e: Event) {
  const el = e.target as HTMLInputElement
  const v = el.value.replace(/\D/g, '')
  digits.value[i] = v.slice(-1)

  // pindah fokus kalau ada isi dan bukan di kotak terakhir
  if (v && i < digits.value.length - 1) {
    nextTick(() => inputs.value[i + 1]?.focus())
  }

  // kalau semua kotak sudah terisi, langsung verifikasi
  if (digits.value.every(d => d !== '')) {
    onVerify()
  }
}

// backspace: jika kotak kosong, kembali ke kotak sebelumnya
function onKeydown(i: number, e: KeyboardEvent) {
  if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
    nextTick(() => {
      digits.value[i - 1] = ''
      inputs.value[i - 1]?.focus()
    })
  }
}

// kirim 6-digit ke API
async function onVerify() {
  const code = digits.value.join('')
  if (code.length !== digits.value.length) {
    return Swal.fire('Error', 'Masukkan semua 6 digit kode', 'error')
  }
  loading.value = true 
  try {
    const { data } = await axios.post('/api/two-factor', {
      user_id: userId,
      code,
    })
    localStorage.setItem('access_token', data.access_token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`
    router.push({ name: 'dashboard-overview-1' })
    digits.value = Array(6).fill('')
    nextTick(() => inputs.value[0]?.focus())
  } catch (err: any) {
    Swal.fire('Error', err.response?.data?.message || 'Kode 2FA salah', 'error')
    // reset semua kotak
      digits.value = Array(6).fill('')
      // kembalikan fokus ke kotak pertama
      nextTick(() => inputs.value[0]?.focus())
  }finally {
    loading.value = false   // <-- stop spinner
  }
}
</script>

<style scoped>
/* kalau perlu override styling tambahan */
</style>
