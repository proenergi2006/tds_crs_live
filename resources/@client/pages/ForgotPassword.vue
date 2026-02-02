<!-- src/pages/ForgotPassword.vue -->
<template>
    <div class="py-3 sm:px-8 relative h-screen bg-primary xl:bg-white dark:bg-darkmode-800">
      <div class="container mx-auto flex items-center justify-center h-full">
        <div class="w-full max-w-md p-8 bg-white rounded shadow-md dark:bg-darkmode-600">
          <h2 class="text-2xl font-bold mb-4 text-center">Forgot Password</h2>
          <p v-if="message" class="mb-4 text-green-600">{{ message }}</p>
          <form @submit.prevent="onSubmit" class="space-y-4">
            <div>
              <label for="email" class="block mb-1 font-medium">Email</label>
              <input
                id="email"
                v-model="email"
                type="email"
                required
                class="w-full px-4 py-2 border rounded focus:outline-none focus:ring"
                placeholder="you@example.com"
              />
            </div>
            <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>
            <button
              type="submit"
              :disabled="loading"
              class="w-full px-4 py-2 font-medium text-white bg-primary rounded hover:bg-primary-dark disabled:opacity-50"
            >
              {{ loading ? 'Sending...' : 'Send Reset Link' }}
            </button>
          </form>
          <p class="mt-4 text-center text-sm">
            <router-link to="{ name: 'login' }" class="text-primary hover:underline">
              Back to login
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRouter } from 'vue-router'
  
  const email = ref('')
  const loading = ref(false)
  const error = ref('')
  const message = ref('')
  const router = useRouter()
  
  async function onSubmit() {
    error.value = ''
    message.value = ''
    loading.value = true
    try {
      const res = await axios.post('/api/forgot-password', { email: email.value })
      message.value = res.data.message || 'Reset link sent! Check your inbox.'
    } catch (e: any) {
      error.value = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'Failed to send reset link'
    } finally {
      loading.value = false
    }
  }
  </script>
  
  <style scoped>
  /* sesuaikan styling jika perlu */
  </style>
  