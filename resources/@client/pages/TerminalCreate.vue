<template>
    <div class="py-6 bg-slate-100 min-h-screen">
      <div class="intro-y grid grid-cols-12 gap-6 mt-8">
        <div class="col-span-12">
          <div class="p-5 box">
            <h2 class="text-lg font-medium mb-4">Tambah Terminal</h2>
            <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>
  
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <FormLabel for="nama">Nama Terminal</FormLabel>
                <FormInput id="nama" v-model="form.nama_terminal" placeholder="Nama Terminal" />
              </div>
  
              <div>
                <FormLabel for="cabang">Cabang</FormLabel>
                <FormSelect id="cabang" v-model="form.id_cabang">
                  <option disabled value="">-- Pilih Cabang --</option>
                  <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                    {{ c.nama_cabang }}
                  </option>
                </FormSelect>
              </div>
  
              <div>
              <FormLabel for="kategori">Kategori</FormLabel>
              <FormSelect id="kategori" v-model="form.kategori_terminal">
                <option disabled value="">-- Pilih Kategori --</option>
                <option value="Jetty">Jetty</option>
                <option value="StockPile">StockPile</option>
              </FormSelect>
            </div>
  
              <div>
                <FormLabel for="inisial">Inisial</FormLabel>
                <FormInput id="inisial" v-model="form.inisial" placeholder="Inisial (opsional)" />
              </div>
  
              <div>
                <FormLabel for="lokasi">Lokasi</FormLabel>
                <FormInput id="lokasi" v-model="form.lokasi" placeholder="Lokasi (opsional)" />
              </div>
  
              <div>
                <FormLabel for="telp">Telepon</FormLabel>
                <FormInput id="telp" v-model="form.telp_terminal" placeholder="Telepon (opsional)" />
              </div>
  
              <div>
                <FormLabel for="alamat">Alamat</FormLabel>
                <textarea
                  id="alamat"
                  v-model="form.alamat"
                  rows="2"
                  class="w-full border rounded px-3 py-2"
                  placeholder="Alamat (opsional)"
                ></textarea>
              </div>
  
              <div>
                <FormLabel for="fax">Fax</FormLabel>
                <FormInput id="fax" v-model="form.fax" placeholder="Fax (opsional)" />
              </div>
  
              <div>
                <FormLabel for="pic">PIC</FormLabel>
                <FormInput id="pic" v-model="form.pic" placeholder="Person In Charge (opsional)" />
              </div>
            </div>
  
            <div class="mt-6 flex justify-end space-x-2">
              <Button variant="outline-secondary" @click="cancel">Batal</Button>
              <Button variant="primary" :loading="loading" @click="submit">Simpan</Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, reactive, onMounted } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRouter } from 'vue-router'
  import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
  import Button from '@/components/Base/Button'
  
  const router = useRouter()
  const form = reactive({
    nama_terminal: '',
    id_cabang:     '' as number | string,
    kategori_terminal: '',
    inisial:      '',
    lokasi:       '',
    telp_terminal:'',
    alamat:       '',
    fax:          '',
    pic:          '',
    created_by:   ''
  })
  const cabangs = ref<any[]>([])
  const loading  = ref(false)
  const error    = ref('')
  
  // ambil daftar cabang untuk dropdown
  async function fetchCabangs() {
    const res = await axios.get('/api/cabangs', { params:{ per_page:100 }})
    cabangs.value = res.data.data || res.data
  }
  
  // setelah mount
  onMounted(async () => {
    // username untuk created_by
    try {
      const { data: u } = await axios.get('/api/user')
      form.created_by = u.name
    } catch {}
    await fetchCabangs()
  })
  
  async function submit() {
    error.value = ''
    if (!form.nama_terminal.trim())      return Swal.fire('Error','Nama Terminal wajib diisi','error')
    if (!form.id_cabang)                 return Swal.fire('Error','Cabang wajib dipilih','error')
    if (!form.kategori_terminal.trim())  return Swal.fire('Error','Kategori wajib diisi','error')
  
    loading.value = true
    try {
      await axios.post('/api/terminals', {
        ...form,
        id_cabang: Number(form.id_cabang)
      })
      Swal.fire({ icon:'success', title:'Terminal berhasil disimpan', toast:true, position:'top-end', timer:1500 })
      router.push({ name: 'terminals-list' })
    } catch (e:any) {
      error.value = e.response?.data?.message || 'Gagal menyimpan'
    } finally {
      loading.value = false
    }
  }
  
  function cancel() {
    router.back()
  }
  </script>
  