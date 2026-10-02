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
    <Dialog.Panel class="flex flex-col p-0 w-[95%] lg:w-[860px] h-[85vh] max-h-[620px] overflow-hidden">
      <button type="button"
        class="top-0 right-0 z-10 absolute hover:bg-slate-100 dark:hover:bg-darkmode-400 mt-4 mr-4 p-1 rounded-lg text-slate-400 hover:text-slate-600 transition"
        @click="handleClose">
        <Lucide icon="X" class="w-5 h-5" />
      </button>

      <div class="flex flex-1 min-h-0">
        <!-- Sidebar -->
        <div class="flex flex-col py-5 border-slate-200 dark:border-darkmode-400 border-r w-56 shrink-0">
          <div class="px-5 font-medium text-slate-400 text-xs uppercase tracking-wide">
            Settings
          </div>
          <nav class="flex flex-col gap-0.5 mt-3 px-3">
            <button v-for="section in sections" :key="section.key" type="button"
              class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-left transition" :class="activeSection === section.key
                ? 'bg-primary/10 font-medium text-primary'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-darkmode-400'"
              @click="activeSection = section.key">
              <Lucide :icon="section.icon" class="w-4 h-4 shrink-0" />
              {{ section.label }}
            </button>
          </nav>
        </div>

        <!-- Content -->
        <div class="flex-1 px-8 py-6 min-w-0 overflow-y-auto">
          <!-- Profil -->
          <div v-if="activeSection === 'profil'">
            <h3 class="font-medium text-slate-700 dark:text-slate-200 text-base">Profil</h3>

            <div class="flex items-center gap-4 mt-5">
              <div
                class="flex justify-center items-center bg-primary/10 rounded-full w-14 h-14 font-medium text-primary text-lg shrink-0">
                {{ initial }}
              </div>
              <div>
                <div class="font-medium text-slate-700 dark:text-slate-200 text-sm">{{ userName }}</div>
                <div class="text-slate-400 text-xs">{{ userEmail }}</div>
              </div>
            </div>

            <div class="space-y-4 mt-6">
              <div>
                <FormLabel htmlFor="settings-profil-nama">Nama Lengkap</FormLabel>
                <FormInput id="settings-profil-nama" type="text" v-model="profileForm.name"
                  :class="getProfileFieldError('name') ? 'border-rose-500' : ''" />
                <small v-if="getProfileFieldError('name')" class="!text-rose-600 text-caption">{{
                  getProfileFieldError('name') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-profil-telepon">No Telepon</FormLabel>
                <FormInput id="settings-profil-telepon" type="text" v-model="profileForm.no_telepon"
                  :class="getProfileFieldError('no_telepon') ? 'border-rose-500' : ''" />
                <small v-if="getProfileFieldError('no_telepon')" class="!text-rose-600 text-caption">{{
                  getProfileFieldError('no_telepon') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-profil-email">Email</FormLabel>
                <FormInput id="settings-profil-email" type="text" :value="userEmail" disabled />
              </div>
            </div>

            <Button type="button" variant="primary" class="inline-flex items-center gap-2 mt-6"
              :disabled="profileLoading" @click="submitProfile">
              <Lucide v-if="profileLoading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Save" class="w-4 h-4" />
              Simpan
            </Button>
          </div>

          <!-- Akun & Keamanan -->
          <div v-else-if="activeSection === 'akun'">
            <h3 class="font-medium text-slate-700 dark:text-slate-200 text-base">Akun & Keamanan</h3>

            <div class="space-y-4 mt-5">
              <div>
                <FormLabel htmlFor="settings-akun-old-password">Password Lama</FormLabel>
                <FormInput id="settings-akun-old-password" type="password" placeholder="Masukkan password lama"
                  v-model="passwordForm.current_password"
                  :class="getPasswordFieldError('current_password') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('current_password')" class="!text-rose-600 text-caption">{{
                  getPasswordFieldError('current_password') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-akun-new-password">Password Baru</FormLabel>
                <FormInput id="settings-akun-new-password" type="password" placeholder="Masukkan password baru"
                  v-model="passwordForm.password" :class="getPasswordFieldError('password') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('password')" class="!text-rose-600 text-caption">{{
                  getPasswordFieldError('password') }}</small>
              </div>
              <div>
                <FormLabel htmlFor="settings-akun-confirm-password">Konfirmasi Password Baru</FormLabel>
                <FormInput id="settings-akun-confirm-password" type="password" placeholder="Ulangi password baru"
                  v-model="passwordForm.password_confirmation"
                  :class="getPasswordFieldError('password_confirmation') ? 'border-rose-500' : ''" />
                <small v-if="getPasswordFieldError('password_confirmation')" class="!text-rose-600 text-caption">{{
                  getPasswordFieldError('password_confirmation') }}</small>
              </div>
            </div>

            <Button type="button" variant="primary" class="inline-flex items-center gap-2 mt-6"
              :disabled="passwordLoading" @click="submitPassword">
              <Lucide v-if="passwordLoading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Save" class="w-4 h-4" />
              Simpan
            </Button>
          </div>
        </div>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
