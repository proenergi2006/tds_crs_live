<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue';

import Lucide, { type Icon } from '@/components/Base/Lucide/Lucide.vue';
import { FormInput, FormLabel } from '@/components/Base/Form';
import { Dialog } from '@/components/Base/Headless';
import { useAccount } from '@/composables/useAccount';
import Button from '@/components/Base/Button';
import { useVuelidate } from '@vuelidate/core';
import { helpers, required, minLength, sameAs } from '@vuelidate/validators';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useNotification } from '@/components/SystemDesign/Notification/useNotification';

const props = defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const { userName, userEmail } = useAccount();
const auth = useAuthStore();
const { success, error: notifyError } = useNotification();

type SectionKey = 'profil' | 'akun';

interface SettingsSection {
  key: SectionKey;
  label: string;
  icon: Icon;
}

const sections: SettingsSection[] = [
  { key: 'profil', label: 'Profil', icon: 'User' },
  { key: 'akun', label: 'Akun & Keamanan', icon: 'Lock' },
];

const activeSection = ref<SectionKey>('profil');

const initial = computed(() => (userName.value || '?').trim().charAt(0).toUpperCase());

const profileForm = reactive({ name: '', no_telepon: '' });
const profileLoading = ref(false);
const profileServerErrors = reactive({ name: '', no_telepon: '' });

const profileRules = {
  name: { required: helpers.withMessage('Nama wajib diisi', required) },
  no_telepon: {},
};
const profileV$ = useVuelidate(profileRules, profileForm);

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const passwordLoading = ref(false);
const passwordServerErrors = reactive({ current_password: '', password: '', password_confirmation: '' });

const passwordRules = {
  current_password: { required: helpers.withMessage('Password lama wajib diisi', required) },
  password: {
    required: helpers.withMessage('Password baru wajib diisi', required),
    minLength: helpers.withMessage('Password minimal 8 karakter', minLength(8)),
  },
  password_confirmation: {
    required: helpers.withMessage('Konfirmasi password wajib diisi', required),
    sameAs: helpers.withMessage(
      'Konfirmasi password tidak cocok',
      sameAs(computed(() => passwordForm.password)),
    ),
  },
};
const passwordV$ = useVuelidate(passwordRules, passwordForm);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      profileForm.name = auth.user?.name ?? '';
      profileForm.no_telepon = auth.user?.no_telepon ?? '';
      profileV$.value.$reset();
      Object.assign(profileServerErrors, { name: '', no_telepon: '' });
    } else {
      resetPasswordForm();
    }
  },
  { immediate: true },
);

// password form sengaja direset tiap modal ditutup, gak boleh nyangkut lama-lama di state
function resetPasswordForm() {
  passwordForm.current_password = '';
  passwordForm.password = '';
  passwordForm.password_confirmation = '';
  passwordV$.value.$reset();
  Object.assign(passwordServerErrors, { current_password: '', password: '', password_confirmation: '' });
}

function getProfileFieldError(field: keyof typeof profileServerErrors) {
  return profileServerErrors[field] || profileV$.value[field]?.$errors[0]?.$message?.toString() || '';
}

function getPasswordFieldError(field: keyof typeof passwordServerErrors) {
  return passwordServerErrors[field] || passwordV$.value[field]?.$errors[0]?.$message?.toString() || '';
}

async function submitProfile() {
  const isValid = await profileV$.value.$validate();
  if (!isValid) return;

  profileLoading.value = true;
  try {
    const { data } = await axios.put('/api/user/profile', {
      name: profileForm.name,
      no_telepon: profileForm.no_telepon || null,
    });

    auth.user = { ...auth.user, ...data.user };
    success('Berhasil', data.message);
  } catch (e: any) {
    const errors = e.response?.data?.errors;

    if (e.response?.status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in profileServerErrors) {
          profileServerErrors[key as keyof typeof profileServerErrors] = value?.[0] || '';
        }
      });
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan');
    }
  } finally {
    profileLoading.value = false;
  }
}

async function submitPassword() {
  const isValid = await passwordV$.value.$validate();
  if (!isValid) return;

  passwordLoading.value = true;
  try {
    const { data } = await axios.post('/api/user/password', {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation,
    });

    success('Berhasil', data.message);
    resetPasswordForm();
  } catch (e: any) {
    const errors = e.response?.data?.errors;

    if (e.response?.status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in passwordServerErrors) {
          passwordServerErrors[key as keyof typeof passwordServerErrors] = value?.[0] || '';
        }
      });
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan');
    }
  } finally {
    passwordLoading.value = false;
  }
}

function handleClose() {
  emit('close');
}
</script>

<template>
  <Dialog :open="open" size="xl" @close="handleClose">
    <Dialog.Panel class="flex h-[85vh] max-h-[620px] w-[95%] flex-col overflow-hidden p-0 lg:w-[860px]">
      <button type="button"
        class="absolute right-0 top-0 z-10 mr-4 mt-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-darkmode-400"
        @click="handleClose">
        <Lucide icon="X" class="h-5 w-5" />
      </button>

      <div class="flex min-h-0 flex-1">
        <!-- Sidebar -->
        <div class="flex w-56 shrink-0 flex-col border-r border-slate-200 py-5 dark:border-darkmode-400">
          <div class="px-5 text-xs font-medium uppercase tracking-wide text-slate-400">
            Settings
          </div>
          <nav class="mt-3 flex flex-col gap-0.5 px-3">
            <button v-for="section in sections" :key="section.key" type="button"
              class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition" :class="activeSection === section.key
                ? 'bg-primary/10 font-medium text-primary'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-darkmode-400'"
              @click="activeSection = section.key">
              <Lucide :icon="section.icon" class="h-4 w-4 shrink-0" />
              {{ section.label }}
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1 overflow-y-auto px-8 py-6">
          <!-- Profil -->
          <div v-if="activeSection === 'profil'">
            <h3 class="text-base font-medium text-slate-700 dark:text-slate-200">Profil</h3>

            <div class="mt-5 flex items-center gap-4">
              <div
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-medium text-primary">
                {{ initial }}
              </div>
              <div>
                <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ userName }}</div>
                <div class="text-xs text-slate-400">{{ userEmail }}</div>
              </div>
            </div>

            <div class="mt-6 space-y-4">
              <div>
                <FormLabel htmlFor="settings-profil-nama">Nama Lengkap</FormLabel>
                <FormInput id="settings-profil-nama" type="text" v-model="profileForm.name"
                  :class="getProfileFieldError('name') ? 'border-rose-500' : ''" />
                <small v-if="getProfileFieldError('name')" class="font-caption !text-rose-600">{{
                  getProfileFieldError('name') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-profil-telepon">No Telepon</FormLabel>
                <FormInput id="settings-profil-telepon" type="text" v-model="profileForm.no_telepon"
                  :class="getProfileFieldError('no_telepon') ? 'border-rose-500' : ''" />
                <small v-if="getProfileFieldError('no_telepon')" class="font-caption !text-rose-600">{{
                  getProfileFieldError('no_telepon') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-profil-email">Email</FormLabel>
                <FormInput id="settings-profil-email" type="text" :value="userEmail" disabled />
              </div>
            </div>

            <Button type="button" variant="primary" class="mt-6 inline-flex items-center gap-2"
              :disabled="profileLoading" @click="submitProfile">
              <Lucide v-if="profileLoading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Save" class="h-4 w-4" />
              Simpan
            </Button>
          </div>

          <!-- Akun & Keamanan -->
          <div v-else-if="activeSection === 'akun'">
            <h3 class="text-base font-medium text-slate-700 dark:text-slate-200">Akun &amp; Keamanan</h3>

            <div class="mt-5 space-y-4">
              <div>
                <FormLabel htmlFor="settings-akun-old-password">Password Lama</FormLabel>
                <FormInput id="settings-akun-old-password" type="password" placeholder="Masukkan password lama"
                  v-model="passwordForm.current_password"
                  :class="getPasswordFieldError('current_password') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('current_password')" class="font-caption !text-rose-600">{{
                  getPasswordFieldError('current_password') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-akun-new-password">Password Baru</FormLabel>
                <FormInput id="settings-akun-new-password" type="password" placeholder="Masukkan password baru"
                  v-model="passwordForm.password"
                  :class="getPasswordFieldError('password') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('password')" class="font-caption !text-rose-600">{{
                  getPasswordFieldError('password') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-akun-confirm-password">Konfirmasi Password Baru</FormLabel>
                <FormInput id="settings-akun-confirm-password" type="password" placeholder="Ulangi password baru"
                  v-model="passwordForm.password_confirmation"
                  :class="getPasswordFieldError('password_confirmation') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('password_confirmation')" class="font-caption !text-rose-600">{{
                  getPasswordFieldError('password_confirmation') }}</small>
              </div>
            </div>

            <Button type="button" variant="primary" class="mt-6 inline-flex items-center gap-2"
              :disabled="passwordLoading" @click="submitPassword">
              <Lucide v-if="passwordLoading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Save" class="h-4 w-4" />
              Simpan
            </Button>
          </div>
        </div>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
