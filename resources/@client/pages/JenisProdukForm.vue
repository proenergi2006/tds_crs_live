<!-- resources/js/pages/JenisProdukForm.vue -->
<template>
    <div class="p-6 intro-y">
      <div class="flex items-center mb-4">
        <h2 class="text-xl font-semibold">
          {{ isEdit ? 'Edit Jenis Produk' : 'Tambah Jenis Produk' }}
        </h2>
        <button
          class="ml-auto px-4 py-2 border rounded hover:bg-gray-100"
          @click="goBack"
        >
          Batal
        </button>
      </div>
  
      <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="submitForm" class="space-y-4">
          <!-- Nama -->
          <div>
            <label class="block mb-1 text-sm font-medium">Nama Jenis</label>
            <input
              v-model="form.nama"
              type="text"
              class="w-full border rounded p-2"
              placeholder="Masukkan nama..."
              required
            />
          </div>
  
          <!-- Deskripsi -->
          <div>
            <label class="block mb-1 text-sm font-medium">Deskripsi</label>
            <textarea
              v-model="form.deskripsi"
              rows="3"
              class="w-full border rounded p-2"
              placeholder="Keterangan singkat..."
            ></textarea>
          </div>
  
          <!-- Is Active -->
          <div>
            <label class="inline-flex items-center">
              <input
                type="checkbox"
                v-model="form.is_active"
                class="form-checkbox"
              />
              <span class="ml-2">Aktif?</span>
            </label>
          </div>
  
          <!-- Aksi Simpan -->
          <div class="flex justify-end space-x-2 mt-6">
            <button
              type="button"
              class="px-4 py-2 border rounded"
              @click="goBack"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              :disabled="loading"
            >
              {{ loading ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted } from 'vue';
  import axios from 'axios';
  import Swal from 'sweetalert2';
  import { useRoute, useRouter } from 'vue-router';
  
  const route = useRoute();
  const router = useRouter();
  const idParam = Number(route.params.id);
  const isEdit = Boolean(idParam);
  
  const loading = ref(false);
  
  // Struktur form
  const form = reactive({
    nama: '' as string,
    deskripsi: '' as string,
    is_active: true as boolean,
  });
  
  // Jika edit, ambil data dulu
  async function fetchData() {
    try {
      const { data } = await axios.get(`/api/jenis-produks/${idParam}`);
      form.nama = data.nama;
      form.deskripsi = data.deskripsi || '';
      form.is_active = data.is_active;
    } catch (e: any) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal memuat data', 'error');
      goBack();
    }
  }
  
  function goBack() {
    router.push({ name: 'jenis-produk-list' });
  }
  
  async function submitForm() {
    loading.value = true;
    try {
      const payload = {
        nama: form.nama,
        deskripsi: form.deskripsi,
        is_active: form.is_active,
      };
  
      if (isEdit) {
        await axios.put(`/api/jenis-produks/${idParam}`, payload);
        await Swal.fire({ icon: 'success', title: 'Data berhasil diupdate', toast: true, position:'top-end', timer: 1500 });
      } else {
        await axios.post('/api/jenis-produks', payload);
        await Swal.fire({ icon: 'success', title: 'Data berhasil disimpan', toast: true, position:'top-end', timer: 1500 });
      }
      goBack();
    } catch (e: any) {
      if (e.response?.status === 422 && e.response.data.errors) {
        const msgs = Object.values(e.response.data.errors).flat().join('<br/>');
        Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: msgs });
      } else {
        Swal.fire('Error', e.response?.data?.message || 'Gagal menyimpan', 'error');
      }
    } finally {
      loading.value = false;
    }
  }
  
  onMounted(() => {
    if (isEdit) {
      fetchData();
    }
  });
  </script>
  