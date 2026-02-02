<script setup lang="ts">
import axios from 'axios'
import Swal from 'sweetalert2'
import { ref, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import ThemeSwitcher from '@/components/ThemeSwitcher'
import { FormInput, FormCheck } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import logoUrl from '@/assets/images/logo.png'
import illustrationUrl from '@/assets/images/logo-icon-2.png'
import { useAuthStore } from '@/stores/auth'

const email    = ref('')
const password = ref('')
const remember = ref(false)
const errorMsg = ref('')
const router   = useRouter()
const route    = useRoute()
const auth     = useAuthStore()

// hanya tangani toast logout di halaman login saja
onMounted(() => {
  // toast logout
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

  // 🔑 AMBIL EMAIL TERSIMPAN
  const savedEmail = localStorage.getItem('remember_email')
  if (savedEmail) {
    email.value = savedEmail
    remember.value = true
  }
})



// async function onSubmit() {
//   errorMsg.value = ''
//   try {
//     const { data } = await axios.post('/api/login', {
//       email:    email.value,
//       password: password.value,
//       remember: remember.value,
//     })

//     console.log('Login response:', data)

//     if (data.two_factor_required) {
//       return router.push({
//         name: 'two-factor',
//         query: { user_id: String(data.user_id) }
//       })
//     }

//     // berhasil login
//     localStorage.setItem('access_token', data.access_token)
//     axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`
//     await auth.fetchUser()
//     router.push({ name: 'dashboard-overview-1' })
//   }
//   catch (e: any) {
//     errorMsg.value = e.response?.data?.message || 'Login gagal'
//   }
// }

async function onSubmit() {
  errorMsg.value = ''
  try {
    const { data } = await axios.post('/api/login', {
      email:    email.value,
      password: password.value,
      remember: remember.value,
    })

    if (data.two_factor_required) {
      return router.push({
        name: 'two-factor',
        query: { user_id: String(data.user_id) }
      })
    }

    // 🔑 SIMPAN EMAIL JIKA REMEMBER
    if (remember.value) {
      localStorage.setItem('remember_email', email.value)
    } else {
      localStorage.removeItem('remember_email')
    }

    // simpan token
    localStorage.setItem('access_token', data.access_token)
    axios.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`

    await auth.fetchUser()
    router.push({ name: 'dashboard-overview-1' })
  }
  catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'Login gagal'
  }
}

</script>

<template>
  <div
    :class="[
      'p-3 sm:px-8 relative h-screen lg:overflow-hidden bg-primary xl:bg-white dark:bg-darkmode-800 xl:dark:bg-darkmode-600',
      'before:hidden before:xl:block before:content-[\'\'] before:w-[57%] before:-mt-[28%] before:-mb-[16%] before:-ml-[13%] before:absolute before:inset-y-0 before:left-0 before:transform before:rotate-[-4.5deg] before:bg-primary/20 before:rounded-[100%] before:dark:bg-darkmode-400',
      'after:hidden after:xl:block after:content-[\'\'] after:w-[57%] after:-mt-[20%] after:-mb-[13%] after:-ml-[13%] after:absolute after:inset-y-0 after:left-0 after:transform after:rotate-[-4.5deg] after:bg-primary after:rounded-[100%] after:dark:bg-darkmode-700',
    ]"
  >
    <ThemeSwitcher />
    <div class="container relative z-10 sm:px-10">
      <div class="block grid-cols-2 gap-4 xl:grid">
        <!-- Login Info (desktop only) -->
        <div class="flex-col hidden min-h-screen xl:flex">
          <a href="#" class="flex items-center pt-5 -intro-x">
            <img class="w-14" :src="logoUrl" alt="Logo"/>
            <span class="ml-3 text-lg text-white">Tri Daya Selaras</span>
          </a>
          <div class="my-auto">
            <img class="w-1/2 -mt-16 -intro-x" :src="logoUrl" alt="Illust"/>
            <!-- <div class="mt-10 text-4xl font-medium leading-tight text-white -intro-x">
              sign in to your account.
            </div> -->
            <div class="mt-5 text-lg text-white -intro-x text-opacity-70 dark:text-slate-400">
              Operational application for sales of stones 
            </div>
          </div>
        </div>

        <!-- Login Form -->
        <div class="flex h-screen py-5 my-10 xl:h-auto xl:py-0 xl:my-0">
          <div
            class="w-full px-5 py-8 mx-auto my-auto bg-white rounded-md shadow-md
                   dark:bg-darkmode-600 xl:bg-transparent sm:px-8 xl:p-0 xl:shadow-none
                   sm:w-3/4 lg:w-2/4 xl:w-auto"
          >
            <h2 class="text-2xl font-bold text-center intro-x xl:text-3xl xl:text-left">
              Sign In
            </h2>

            <p v-if="errorMsg" class="text-red-500 mt-2 text-center">{{ errorMsg }}</p>
            <div class="mt-2 text-center intro-x text-slate-400 xl:hidden">
              A few more clicks to sign in to your account. Manage all your
              e-commerce accounts in one place
            </div>

            <form @submit.prevent="onSubmit" class="mt-8 intro-x">
              <FormInput
                v-model="email"
                type="text"
                name="email"
                autocomplete="username"
                placeholder="Email"
                class="block px-4 py-3 min-w-full xl:min-w-[350px]"
              />
              <FormInput
                v-model="password"
                type="password"
                 name="password"
                autocomplete="current-password"
                placeholder="Password"
                class="block px-4 py-3 mt-4 min-w-full xl:min-w-[350px]"
              />

              <div class="flex mt-4 text-xs intro-x text-slate-600 dark:text-darkmode-300 sm:text-sm">
                <div class="flex items-center mr-auto">
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
                <!-- SPA navigation ke halaman forgot-password -->
                <!-- <RouterLink
                  :to="{ name: 'forgot-password' }"
                  class="text-primary hover:underline"
                >
                  Forgot Password?
                </RouterLink> -->
              </div>

              <div class="mt-5 text-center intro-x xl:mt-8 xl:text-left">
                <Button
                  type="submit"
                  variant="primary"
                  class="w-full px-4 py-3 align-top xl:w-32 xl:mr-3"
                >
                  Login
                </Button>
              </div>
            </form>

            <div class="mt-10 text-center intro-x xl:mt-24 text-slate-600 dark:text-darkmode-500 xl:text-left">
              © 2025 Tri Daya Selaras
              <a class="text-primary dark:text-slate-200" href="#">
                || Version 1.0
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
