<!-- resources/js/pages/JenisProdukDetail.vue -->
<template>
    <div class="p-6 intro-y">
      <div v-if="!item.id_jenis" class="text-center py-10">
        <svg class="animate-spin h-8 w-8 mx-auto" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <p class="mt-2 text-gray-500">Memuat detail...</p>
      </div>
  
      <div v-else>
        <div class="flex items-center mb-4">
          <button
            class="px-4 py-2 border rounded hover:bg-gray-100"
            @click="goBack"
          >
            Kembali
          </button>
          <h2 class="text-xl font-semibold ml-4">Detail Jenis Produk</h2>
        </div>
  
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p><strong>Nama Jenis:</strong></p>
              <p class="text-gray-700">{{ item.nama }}</p>
            </div>
            <div>
              <p><strong>Aktif:</strong></p>
              <p :class="item.is_active ? 'text-green-600' : 'text-red-600'">
                {{ item.is_active ? 'Ya' : 'Tidak' }}
              </p>
            </div>
          </div>
  
          <div>
            <p><strong>Deskripsi:</strong></p>
            <p class="text-gray-700">{{ item.deskripsi || '-' }}</p>
          </div>
  
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p><strong>Dibuat pada:</strong></p>
              <p class="text-gray-700">{{ formatDate(item.created_at) }}</p>
              <p class="text-gray-500 text-xs">Oleh: {{ item.created_by || '-' }}</p>
            </div>
            <div>
              <p><strong>Diubah pada:</strong></p>
              <p class="text-gray-700">{{ formatDate(item.updated_at) }}</p>
              <p class="text-gray-500 text-xs">Oleh: {{ item.updated_by || '-' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, onMounted } from 'vue';
  import axios from 'axios';
  import Swal from 'sweetalert2';
  import { useRoute, useRouter } from 'vue-router';
  
  const route = useRoute();
  const router = useRouter();
  const id = Number(route.params.id);
  
  const item = ref<any>({});
  
  function goBack() {
    router.push({ name: 'jenis-produk-list' });
  }
  
  async function fetchDetail() {
    try {
      const { data } = await axios.get(`/api/jenis-produks/${id}`);
      item.value = data;
    } catch (e: any) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal memuat detail', 'error');
      goBack();
    }
  }
  
  function formatDate(d: string) {
    return d
      ? new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' })
      : '-';
  }
  
  onMounted(fetchDetail);
  </script>
  