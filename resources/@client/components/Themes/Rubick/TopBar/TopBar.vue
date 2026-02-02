<script setup lang="ts">
import { ref, computed } from 'vue' 
import Lucide from "@/components/Base/Lucide";
import Breadcrumb from "@/components/Base/Breadcrumb";
import { FormInput } from "@/components/Base/Form";
import { Menu, Popover } from "@/components/Base/Headless";
import logoUrll from "@/assets/images/logo-tds-1.png";
import fakerData from "@/utils/faker";
import _ from "lodash";
import { TransitionRoot } from "@headlessui/vue";
import axios from 'axios'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const searchDropdown = ref(false);
const showSearchDropdown = () => {
  searchDropdown.value = true;
};
const hideSearchDropdown = () => {
  searchDropdown.value = false;
};

const router = useRouter()
const auth = useAuthStore()
// --- computed untuk menampilkan nama user ---
const userName = computed(() => auth.user?.name || 'Guest')
const userEmail = computed(() => auth.user?.email || '-')
//const userEmail = computed(() => auth.user?.email || '')

async function onLogout() {
  try {
    const { data } = await axios.post('/api/logout');
    await Swal.fire({
      icon: 'success',
      title: data.message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
  } catch (e) {
    console.error('Logout error', e);
  } finally {
    localStorage.removeItem('access_token');
    delete axios.defaults.headers.common['Authorization'];
    router.push({ name: 'login' });
  }
}
</script>

<template>
  <!-- BEGIN: Top Bar -->
  <div
    class="relative z-[51] flex h-[67px] items-center border-b border-slate-200"
  >
    <!-- BEGIN: Breadcrumb -->
    <Breadcrumb class="hidden mr-auto -intro-x sm:flex">
      <Breadcrumb.Link to="/">Application</Breadcrumb.Link>
      <Breadcrumb.Link to="/" :active="true"> CrushedStone</Breadcrumb.Link>
    </Breadcrumb>
    <!-- END: Breadcrumb -->
    <!-- BEGIN: Search -->
    <div class="relative mr-3 intro-x sm:mr-6">
      <div class="relative hidden sm:block">
        
       
      </div>
     
     
    </div>
    <!-- END: Search  -->
    <!-- BEGIN: Notifications -->
   
    <!-- END: Notifications  -->
    <!-- BEGIN: Account Menu -->
    <Menu>
      <Menu.Button
        class="block w-8 h-8 overflow-hidden rounded-full shadow-lg image-fit zoom-in intro-x"
      >
      <img
            alt="Tinker Tailwind HTML Admin Template"
            class="w-8"
            :src="logoUrll"
          />
      </Menu.Button>
      <Menu.Items class="w-56 mt-px text-white bg-primary">
        <Menu.Header class="font-normal">
          <div class="font-medium">{{ userName }}</div>
          <div class="text-xs text-white/70 mt-0.5 dark:text-slate-500">
            {{ userEmail }}
          </div>
        </Menu.Header>
        <Menu.Divider class="bg-white/[0.08]" />
        <Menu.Item
  as="button"
  class="hover:bg-white/5"
  @click="router.push({ name: 'profile-overview-1' })"
>
  <Lucide icon="User" class="w-4 h-4 mr-2" />
  Profile
</Menu.Item>
<Menu.Item
          v-if="auth.user?.id_role === 1"
          as="button"
          class="hover:bg-white/5"
          @click="() => router.push({ name: 'users' })"
        >
          <Lucide icon="Edit" class="w-4 h-4 mr-2" /> Add Account
   </Menu.Item>
       
   <Menu.Item class="hover:bg-white/5">
          <Lucide icon="HelpCircle" class="w-4 h-4 mr-2" /> Help
        </Menu.Item>
        <Menu.Divider class="bg-white/[0.08]" />
        <Menu.Item
        as="button"
        @click="onLogout"
        class="w-full text-left hover:bg-white/5 flex items-center px-4 py-2"
      >
        <Lucide icon="ToggleRight" class="w-4 h-4 mr-2" /> Logout
      </Menu.Item>
      </Menu.Items>
    </Menu>
  </div>
  <!-- END: Top Bar -->
</template>
