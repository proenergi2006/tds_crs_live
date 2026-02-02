<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'
import { Tab } from '@/components/Base/Headless'
import { Tab as HeadlessTab } from '@headlessui/vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'

const userName = computed(() => auth.user?.name || 'Guest')
const userEmail = computed(() => auth.user?.email || '-')

// -------- Update Password --------
const passwordForm = reactive({
  current_password: '',
  new_password: '',
  confirm_password: ''
})
const passwordLoading = ref(false)

async function updatePassword() {
  if (passwordForm.new_password !== passwordForm.confirm_password) {
    return Swal.fire('Error','Password baru dan konfirmasi tidak sama','error')
  }
  passwordLoading.value = true
  try {
    await axios.post('/api/user/password', {
      current_password:       passwordForm.current_password,
      password:               passwordForm.new_password,
      password_confirmation:  passwordForm.confirm_password,
    })
    Swal.fire({
      icon: 'success',
      title: 'Password berhasil diubah',
      toast: true,
      position: 'top-end',
      timer: 2000
    })
    // reset form
    passwordForm.current_password = ''
    passwordForm.new_password     = ''
    passwordForm.confirm_password = ''
  } catch (e: any) {
    const msg = e.response?.data?.errors?.password
      ? e.response.data.errors.password[0]
      : e.response?.data?.message || 'Gagal mengubah password'
    Swal.fire('Error', msg, 'error')
  } finally {
    passwordLoading.value = false
  }
}

// store auth
const auth = useAuthStore()

// cek status 2FA
const is2FAEnabled = computed(() => !!auth.user?.two_factor_secret)

// state 2FA
const qrCodeInline = ref<string|null>(null)
const secret       = ref<string>('')
const step         = ref<'idle'|'generated'|'verified'>('idle')
const loading      = ref(false)
const codeInput    = ref('')

// fetch user & atur step
onMounted(async () => {
  if (!auth.user) {
    await auth.fetchUser()
  }
  if (is2FAEnabled.value) {
    step.value = 'verified'
  }
})

// generate QR + secret
async function generate2FA() {
  loading.value = true
  try {
    const { data } = await axios.post('/api/2fa/generate')
    qrCodeInline.value = data.qrCode
    secret.value       = data.secret
    step.value         = 'generated'
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Gagal generate 2FA', 'error')
  } finally {
    loading.value = false
  }
}

// enable 2FA
async function enable2FA() {
  if (!/^\d{6}$/.test(codeInput.value)) {
    return Swal.fire('Error','Kode harus 6 digit','error')
  }
  loading.value = true
  try {
    const { data } = await axios.post('/api/2fa/enable', { code: codeInput.value })
    Swal.fire('Sukses', data.message, 'success')
    step.value = 'verified'
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Kode salah', 'error')
  } finally {
    loading.value = false
  }
}

// disable 2FA
async function disable2FA() {
  if (!/^\d{6}$/.test(codeInput.value)) {
    return Swal.fire('Error','Kode harus 6 digit','error')
  }
  loading.value = true
  try {
    const { data } = await axios.post('/api/2fa/disable', { code: codeInput.value })
    Swal.fire('Sukses', data.message, 'success')
    qrCodeInline.value = null
    secret.value       = ''
    codeInput.value    = ''
    step.value         = 'idle'
  } catch (e: any) {
    Swal.fire('Error', e.response?.data?.message || 'Kode salah', 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Tab.Group>
    <Tab.List variant="link-tabs" class="mt-5">
      <Tab as="button"><Tab.Button>Account & Profile</Tab.Button></Tab>
      <Tab as="button"><Tab.Button>Update Password</Tab.Button></Tab>
      <Tab as="button"><Tab.Button>Security 2FA</Tab.Button></Tab>
      
    </Tab.List>

    <Tab.Panels class="mt-5">

      <Tab.Panel>
  <div class="bg-white dark:bg-darkmode-600 rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center mb-6 border-b border-slate-200 dark:border-darkmode-400 pb-2">
      <Lucide icon="User" class="w-6 h-6 text-primary mr-2"/>
      <h3 class="text-2xl font-bold text-slate-700 dark:text-slate-200">Account & Profile</h3>
    </div>

    <!-- Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Avatar & Name -->
      <div class="flex items-center space-x-4">
        <img
          src="https://via.placeholder.com/80"
          alt="Avatar"
          class="w-20 h-20 rounded-full object-cover shadow"
        />
        <div>
          <div class="text-lg font-semibold text-slate-800 dark:text-slate-100">
            {{ userName }}
          </div>
          <div class="text-sm text-slate-500 dark:text-slate-400">
            {{ userEmail }}
          </div>
        </div>
      </div>

      <!-- Contact Details -->
      <div class="space-y-4">
        <div class="flex items-center space-x-2">
          <Lucide icon="Mail" class="w-5 h-5 text-primary dark:text-primary/80"/>
          <span class="text-sm text-slate-700 dark:text-slate-300">{{ userEmail }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <Lucide icon="Phone" class="w-5 h-5 text-primary dark:text-primary/80"/>
          <span class="text-sm text-slate-700 dark:text-slate-300">-</span>
        </div>
        <div class="flex items-center space-x-2">
          <Lucide icon="MapPin" class="w-5 h-5 text-primary dark:text-primary/80"/>
          <span class="text-sm text-slate-700 dark:text-slate-300">-</span>
        </div>
      </div>
    </div>
  </div>
</Tab.Panel>

      <!-- … panel 1 & 2 … -->
    <!-- Update Password -->
    <Tab.Panel>
  <div class="bg-white dark:bg-darkmode-600 rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 border-b border-slate-200 dark:border-darkmode-400 pb-2">
      <h3 class="text-2xl font-bold text-slate-700 dark:text-slate-200">
        <Lucide icon="Key" class="w-5 h-5 inline-block mr-2" />
        Update Password
      </h3>
    </div>
    <!-- Form -->
    <form @submit.prevent="updatePassword" class="space-y-6">
      <!-- Password Sekarang -->
      <div>
        <label class="block mb-1 text-sm font-medium text-slate-600 dark:text-slate-400">
          Password Sekarang
        </label>
        <FormInput
          v-model="passwordForm.current_password"
          type="password"
          placeholder="••••••••"
          class="w-full !box"
        />
      </div>
      <!-- Password Baru -->
      <div>
        <label class="block mb-1 text-sm font-medium text-slate-600 dark:text-slate-400">
          Password Baru
        </label>
        <FormInput
          v-model="passwordForm.new_password"
          type="password"
          placeholder="••••••••"
          class="w-full !box"
        />
      </div>
      <!-- Konfirmasi Password -->
      <div>
        <label class="block mb-1 text-sm font-medium text-slate-600 dark:text-slate-400">
          Konfirmasi Password
        </label>
        <FormInput
          v-model="passwordForm.confirm_password"
          type="password"
          placeholder="••••••••"
          class="w-full !box"
        />
      </div>
      <!-- Button -->
      <div class="pt-4 border-t border-slate-200 dark:border-darkmode-400 flex justify-end">
        <Button type="submit" variant="primary" :loading="passwordLoading">
          Ubah Password
        </Button>
      </div>
    </form>
  </div>
</Tab.Panel>

      <!-- Security 2FA Panel -->
      <Tab.Panel>
        <div class="p-6 bg-white rounded-lg shadow">
          <h3 class="text-xl font-semibold mb-4">Two-Factor Authentication</h3>
          <div class="grid md:grid-cols-2 gap-6">
            <!-- instruksi & tombol -->
            <div class="space-y-4">
              <ol class="list-decimal list-inside">
                <li>Tekan tombol <b>Enable 2FA</b>.</li>
                <li>Scan QR code dengan Authenticator.</li>
                <li>Masukkan kode 6 digit untuk verifikasi.</li>
              </ol>

              <!-- idle: belum aktif -->
              <div v-if="step === 'idle'">
                <Button variant="primary" :loading="loading" @click="generate2FA">
                  Enable 2FA
                </Button>
              </div>

              <!-- setelah generate -->
              <div v-else-if="step === 'generated'">
                <label class="block">Kode 6 digit:</label>
                <input
                  v-model="codeInput"
                  type="text"
                  maxlength="6"
                  class="border px-2 py-1 rounded w-20"
                />
                <div class="flex space-x-2 mt-2">
                  <Button variant="primary" :loading="loading" @click="enable2FA">
                    Verifikasi & Aktifkan
                  </Button>
                  <Button variant="outline-secondary" :loading="loading" @click="disable2FA">
                    Batalkan
                  </Button>
                </div>
              </div>

              <!-- sudah verified -->
              <div v-else-if="step === 'verified'">
                <p class="text-green-600">2FA sudah aktif!</p>
                <label class="block">Masukkan kode untuk disable:</label>
                <div class="flex items-center">
                  <input
                    v-model="codeInput"
                    type="text"
                    maxlength="6"
                    class="border px-2 py-1 rounded w-40"
                  />
                  <Button
                    variant="danger"
                    :loading="loading"
                    @click="disable2FA"
                    class="ml-2"
                  >
                    Disable 2FA
                  </Button>
                </div>
              </div>
            </div>

            <!-- QR & secret -->
            <div class="flex flex-col items-center justify-center">
              <div v-if="step === 'generated'">
                <img :src="qrCodeInline!" alt="QR Code" class="w-40 h-40 mx-auto" />
                <p class="mt-2 text-sm">Secret: <code>{{ secret }}</code></p>
              </div>
              <div v-else class="text-gray-400 italic">
                QR code akan muncul di sini setelah klik “Enable 2FA”.
              </div>
            </div>
          </div>
        </div>
      </Tab.Panel>
    </Tab.Panels>
  </Tab.Group>
</template>
