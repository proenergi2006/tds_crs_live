<script setup lang="ts">
import axios from 'axios'
import Swal from 'sweetalert2'
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ThemeSwitcher from '@/components/ThemeSwitcher'
import { FormInput, FormCheck } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import logoUrl from '@/assets/images/tds-crs-new.png'
import logoUrll from '@/assets/images/putih-tulisan-atas.png'
import bgLogin from '@/assets/images/bg-quary.png'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const remember = ref(false)
const errorMsg = ref('')
const showPassword = ref(false)
const isLoading = ref(false)

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

onMounted(() => {
  if (route.name === 'login' && route.query.logged_out === '1') {
    Swal.fire({
      icon: 'success',
      title: 'You have been logged out',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
    })
    router.replace({ name: 'login', query: {} })
  }

  const savedEmail = localStorage.getItem('remember_email')
  if (savedEmail) {
    email.value = savedEmail
    remember.value = true
  }
})

async function onSubmit() {
  errorMsg.value = ''
  isLoading.value = true

  try {
    const { data } = await axios.post('/api/login', {
      email: email.value,
      password: password.value,
      remember: remember.value,
    })

    if (data.two_factor_required) {
      isLoading.value = false
      return router.push({
        name: 'two-factor',
        query: { user_id: String(data.user_id) }
      })
    }

    if (remember.value) {
      localStorage.setItem('remember_email', email.value)
    } else {
      localStorage.removeItem('remember_email')
    }

    localStorage.setItem('access_token', data.access_token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`

    await auth.fetchUser()
    router.push({ name: 'dashboard-overview-1' })
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'Login gagal, silakan cek email dan password Anda.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div
    :class="[
      'relative min-h-screen overflow-hidden bg-primary xl:bg-white dark:bg-darkmode-800 xl:dark:bg-darkmode-600',
      'before:hidden before:xl:block before:content-[\'\'] before:w-[57%] before:-mt-[28%] before:-mb-[16%] before:-ml-[13%] before:absolute before:inset-y-0 before:left-0 before:transform before:rotate-[-4.5deg] before:bg-primary/20 before:rounded-[100%] before:z-20 before:dark:bg-darkmode-400',
      'after:hidden after:xl:block after:content-[\'\'] after:w-[57%] after:-mt-[20%] after:-mb-[13%] after:-ml-[13%] after:absolute after:inset-y-0 after:left-0 before:transform after:rotate-[-4.5deg] after:bg-primary after:rounded-[100%] after:z-20 after:dark:bg-darkmode-700',
    ]"
  >
    <ThemeSwitcher />

    <!-- Background quarry full -->
    <div
      class="absolute inset-0 hidden xl:block bg-center bg-cover bg-no-repeat"
      :style="{ backgroundImage: `url(${bgLogin})` }"
    ></div>

    <!-- Overlay putih global -->
    <div class="absolute inset-0 hidden xl:block bg-white/80"></div>

    <!-- Content -->
    <div class="relative z-30 grid min-h-screen xl:grid-cols-2">
      <!-- Left Branding -->
      <div class="hidden xl:flex flex-col min-h-screen px-16 relative">
        <a href="#" class="flex items-center pt-10 -intro-x">
          <img class="w-28" :src="logoUrl" alt="Logo" />
        </a>

        <div class="my-auto">
          <img class="w-[60%] max-w-md -mt-16 -intro-x" :src="logoUrll" alt="CRS Logo" />

          <div class="mt-5 text-lg leading-relaxed text-white/80 -intro-x dark:text-slate-300">
            Integrated Operational System
            for Crushed Stone
          </div>

          <div class="mt-10 text-sm text-white/60 -intro-x">
            Secure access for authorized users only.
          </div>
        </div>
      </div>

      <!-- Right Form -->
      <div class="relative flex min-h-screen items-center justify-center px-6 py-10 xl:px-16">
        <div
          class="relative z-10 w-full max-w-[460px] rounded-2xl bg-white/70 px-8 py-10 shadow-xl backdrop-blur-md
                 dark:bg-darkmode-600/80"
        >
          <h2 class="text-2xl font-bold text-center intro-x xl:text-3xl xl:text-left">
            Sign In
          </h2>

          <p v-if="errorMsg" class="mt-3 text-center text-red-500 xl:text-left">
            {{ errorMsg }}
          </p>

          <div class="mt-2 text-center intro-x text-slate-500 xl:hidden">
            Sign in to access the Crushed Stone operational system
          </div>

          <form @submit.prevent="onSubmit" class="mt-8 intro-x">
            <FormInput
              v-model="email"
              type="text"
              name="email"
              autocomplete="username"
              placeholder="Email"
              class="block min-w-full px-4 py-3"
            />

            <div class="relative mt-4">
              <FormInput
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                name="password"
                autocomplete="current-password"
                placeholder="Password"
                class="block min-w-full px-4 py-3 pr-12"
              />

              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
              >
                <Lucide :icon="showPassword ? 'EyeOff' : 'Eye'" class="w-5 h-5" />
              </button>
            </div>

            <div class="mt-4 flex text-xs text-slate-600 dark:text-darkmode-300 sm:text-sm">
              <div class="mr-auto flex items-center">
                <FormCheck.Input
                  v-model="remember"
                  id="remember-me"
                  type="checkbox"
                  class="mr-2 border"
                />
                <label for="remember-me" class="cursor-pointer select-none">
                  Remember me
                </label>
              </div>
            </div>

            <div class="mt-5 text-center xl:mt-8 xl:text-left">
              <Button
                type="submit"
                variant="primary"
                :disabled="isLoading"
                class="w-full px-4 py-3 xl:w-32"
              >
                <span v-if="isLoading">Logging in...</span>
                <span v-else>Login</span>
              </Button>
            </div>
          </form>

          <div class="mt-10 text-center text-slate-600 dark:text-darkmode-500 xl:mt-16 xl:text-left">
            © 2025 Tri Daya Selaras
            <span class="text-primary dark:text-slate-200"> | Version 1.0</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>