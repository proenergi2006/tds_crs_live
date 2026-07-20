<template>
    <div class="p-6 bg-white rounded shadow">
      <h2 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit' : 'Tambah' }} Wilayah Angkut</h2>
      <div v-if="hasLegacyAddressOnly" class="mb-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Data lokasi wilayah angkut ini masih pakai skema lama: <strong>{{ legacyAddressLabel }}</strong>.
        Silakan pilih ulang Provinsi &amp; Kabupaten/Kota di bawah berdasarkan daftar wilayah terbaru
        agar tersimpan dengan skema baru.
      </div>
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="provinsi" class="block text-sm font-medium mb-1">Provinsi *</label>
            <FormSelect id="provinsi" v-model="form.province_id">
              <option value="">Pilih Provinsi</option>
              <option v-for="p in region.provinces.value" :key="p.id" :value="p.id">
                {{ p.name }}
              </option>
            </FormSelect>
          </div>

          <div>
            <label for="kabupaten" class="block text-sm font-medium mb-1">Kabupaten/Kota *</label>
            <FormSelect id="kabupaten" v-model="form.regency_id" :disabled="!form.province_id">
              <option value="">{{ form.province_id ? 'Pilih Kabupaten/Kota' : '-- Pilih Provinsi dulu --' }}</option>
              <option v-for="k in region.regencies.value" :key="k.id" :value="k.id">
                {{ k.name }}
              </option>
            </FormSelect>
          </div>

          <div>
            <label for="destinasi" class="block text-sm font-medium mb-1">Destinasi *</label>
            <FormInput id="destinasi" v-model="form.destinasi" placeholder="Nama destinasi" />
          </div>
  
          <div>
            <label for="status" class="block text-sm font-medium mb-1">Status *</label>
            <FormSelect id="status" v-model="form.is_active">
              <option :value="1">Aktif</option>
              <option :value="0">Tidak Aktif</option>
            </FormSelect>
          </div>
        </div>
  
        <div class="mt-4 flex justify-end space-x-2">
          <Button variant="outline-secondary" @click.prevent="router.back()">Batal</Button>
          <Button type="submit" :loading="loading" variant="primary">Simpan</Button>
        </div>
      </form>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect } from '@/components/Base/Form'
  import { useRegionCascade } from '@/composables/useRegionCascade'

  const router = useRouter()
  const route = useRoute()
  const isEdit = !!route.params.id
  const loading = ref(false)

  /* Cascading province -> regency (BPS data) via useRegionCascade — regency
     list now properly scoped to the selected province (fixes pre-existing
     bug where kabupatens were fetched unscoped/all-at-once). */
  const region = useRegionCascade()

  /* Fallback notice untuk record lama yang cuma punya id_provinsi/id_kabupaten
     (skema pra-migrasi BPS), belum punya province_id/regency_id. */
  const hasLegacyAddressOnly = ref(false)
  const legacyAddressLabel = ref('')

  const form = reactive({
    province_id: '',
    regency_id: '',
    destinasi: '',
    is_active: 1,
  })

  watch(
    () => form.province_id,
    async (newProv) => {
      form.regency_id = ''
      await region.fetchRegencies(newProv || null)
    }
  )

  onMounted(async () => {
    await region.fetchProvinces()
    if (isEdit) {
      try {
        const { data } = await axios.get(`/api/wilayah-angkuts/${route.params.id}`)
        form.destinasi = data.destinasi
        form.is_active = Number(data.is_active)

        if (data.province_id) {
          form.province_id = String(data.province_id)
          await region.fetchRegencies(form.province_id)
          form.regency_id = data.regency_id ? String(data.regency_id) : ''
        } else if (data.id_provinsi) {
          // Record lama (sebelum migrasi BPS) cuma punya id_provinsi/id_kabupaten,
          // belum punya province_id/regency_id — tampilkan info dari relasi lama
          // dan minta user pilih ulang dari daftar wilayah BPS baru di bawah.
          hasLegacyAddressOnly.value = true
          legacyAddressLabel.value = [data.provinsi?.nama_provinsi, data.kabupaten?.nama_kabupaten]
            .filter(Boolean)
            .join(', ') || 'Data lokasi lama tidak lengkap'
        }
      } catch {
        Swal.fire('Error', 'Gagal memuat data', 'error')
        router.back()
      }
    }
  })

  async function submit() {
    if (!form.province_id) return Swal.fire('Peringatan', 'Provinsi wajib dipilih', 'warning')
    if (!form.regency_id) return Swal.fire('Peringatan', 'Kabupaten/Kota wajib dipilih', 'warning')
    if (!form.destinasi.trim()) return Swal.fire('Peringatan', 'Destinasi wajib diisi', 'warning')

    loading.value = true
    try {
      if (isEdit) {
        await axios.put(`/api/wilayah-angkuts/${route.params.id}`, form)
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/wilayah-angkuts', form)
        Swal.fire('Berhasil', 'Data ditambahkan', 'success')
      }
      router.push({ name: 'wilayah-angkut-list' })
    } catch (e: any) {
      Swal.fire('Gagal', e.response?.data?.message || JSON.stringify(e.response?.data?.errors), 'error')
    } finally {
      loading.value = false
    }
  }
  </script>
  