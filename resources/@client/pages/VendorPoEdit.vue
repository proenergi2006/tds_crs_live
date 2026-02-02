<template>
    <div v-if="initialized" class="py-6 bg-slate-100 min-h-screen">
      <div class="intro-y box p-5 mx-auto max-w-3xl mt-8">
        <h2 class="text-lg font-medium mb-4">Edit Vendor PO</h2>
        <p v-if="error" class="text-red-500 mb-3">{{ error }}</p>
  
        <!-- HEADER PO -->
        <div class="grid grid-cols-12 gap-4 mb-6">
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="vendor">Vendor</FormLabel>
            <FormSelect id="vendor" v-model="form.id_vendor">
              <option disabled value="">-- Pilih Vendor --</option>
              <option v-for="v in vendors" :key="v.id_vendor" :value="v.id_vendor">
                {{ v.nama_vendor }}
              </option>
            </FormSelect>
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="terminal">Terminal</FormLabel>
            <FormSelect id="terminal" v-model="form.id_terminal">
              <option disabled value="">-- Pilih Terminal --</option>
              <option v-for="t in terminals" :key="t.id_terminal" :value="t.id_terminal">
                {{ t.nama_terminal }}
              </option>
            </FormSelect>
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="nomor_po">Nomor PO</FormLabel>
            <FormInput id="nomor_po" v-model="form.nomor_po" placeholder="Nomor PO" />
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="tanggal_inven">Tanggal Inven</FormLabel>
            <FormInput id="tanggal_inven" type="date" v-model="form.tanggal_inven" />
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="kd_tax">Kode Tax</FormLabel>
            <FormSelect id="kd_tax" v-model="form.kd_tax">
              <option disabled value="">-- Pilih Kode Tax --</option>
              <option value="E">E</option>
              <option value="EC">EC</option>
            </FormSelect>
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="terms">Terms</FormLabel>
            <FormSelect id="terms" v-model="form.terms">
              <option disabled value="">-- Pilih Terms --</option>
              <option value="CBD">CBD</option>
              <option value="COD">COD</option>
              <option value="TOP">TOP</option>
            </FormSelect>
          </div>
          <div class="col-span-12 sm:col-span-4">
            <FormLabel for="terms_day">Terms Day</FormLabel>
            <FormInput id="terms_day" type="number" v-model.number="form.terms_day" />
          </div>
        </div>
  
        <!-- DETAIL PO -->
        <div class="flex justify-end mb-4">
          <Button variant="outline-secondary" @click="addRow">+ Tambah Baris</Button>
        </div>
        <div class="p-4 mb-6 bg-white border rounded-lg shadow-sm">
          <div
            v-for="(item, idx) in form.items"
            :key="idx"
            class="grid grid-cols-12 gap-4 mb-6 items-end"
          >
            <!-- Produk + Satuan/Ukuran -->
            <div class="col-span-12 md:col-span-3">
              <FormLabel :for="`produk_${idx}`">Produk</FormLabel>
              <FormSelect :id="`produk_${idx}`" v-model="item.id_produk">
                <option disabled value="">-- Pilih Produk --</option>
                <option
                  v-for="p in produks"
                  :key="p.id_produk"
                  :value="p.id_produk"
                >
                  {{ p.nama_produk }}
                  ({{ p.ukuran?.nama_ukuran }} • {{ p.ukuran?.satuan?.nama_satuan }})
                </option>
              </FormSelect>
            </div>
  
            <!-- Volume PO -->
            <div class="col-span-12 md:col-span-2">
              <FormLabel :for="`volume_po_${idx}`">Volume PO</FormLabel>
              <FormInput
                :id="`volume_po_${idx}`"
                type="text"
                :value="formatNumber(item.volume_po)"
                @input="onVolumeInput($event, idx)"
              />
            </div>
  
            <!-- Harga Tebus -->
            <div class="col-span-12 md:col-span-2">
              <FormLabel :for="`harga_tebus_${idx}`">Harga Tebus</FormLabel>
              <FormInput
                :id="`harga_tebus_${idx}`"
                type="text"
                :value="formatNumber(item.harga_tebus)"
                @input="onHargaInput($event, idx)"
              />
            </div>
  
            <!-- Total Harga -->
            <div class="col-span-12 md:col-span-3">
              <FormLabel :for="`total_harga_${idx}`">Total Harga</FormLabel>
              <FormInput
                :id="`total_harga_${idx}`"
                :value="formatNumber(item.total_harga)"
                readonly
                class="bg-gray-100 cursor-not-allowed"
              />
            </div>
  
            <!-- Hapus Baris -->
            <div class="col-span-12 md:col-span-2 text-right">
              <Button
                v-if="form.items.length > 1"
                variant="danger"
                size="sm"
                @click="removeRow(idx)"
              >
                Hapus
              </Button>
            </div>
          </div>
        </div>
  
        <!-- Subtotal / PPN / Total -->
        <div class="grid grid-cols-12 gap-4 mb-6">
          <div class="col-span-12 md:col-span-4">
            <FormLabel for="subtotal">Subtotal</FormLabel>
            <FormInput id="subtotal" :value="formatNumber(calcSubtotal)" readonly />
          </div>
          <div class="col-span-12 md:col-span-4">
            <FormLabel for="ppn11">PPN 11%</FormLabel>
            <FormInput id="ppn11" :value="formatNumber(calcPPN)" readonly />
          </div>
          <div class="col-span-12 md:col-span-4">
            <FormLabel for="total_order">Total Order</FormLabel>
            <FormInput id="total_order" :value="formatNumber(calcTotalOrder)" readonly />
          </div>
          <div class="col-span-12">
            <FormLabel for="keterangan">Catatan</FormLabel>
            <textarea
              id="keterangan"
              v-model="form.keterangan"
              rows="2"
              class="w-full border rounded px-3 py-2"
              placeholder="(opsional)"
            />
          </div>
          <div class="col-span-12">
            <FormLabel for="terms_condition">Terms Condition</FormLabel>
            <textarea
              id="terms_condition"
              v-model="form.terms_condition"
              rows="2"
              class="w-full border rounded px-3 py-2"
              placeholder="(opsional)"
            />
          </div>
        </div>
  
        <!-- Simpan -->
        <div class="flex justify-end space-x-2">
          <Button variant="outline-secondary" @click="cancel">Batal</Button>
          <Button variant="primary" :loading="loading" @click="submit">Simpan</Button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { reactive, ref, onMounted, computed } from 'vue'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  import { useRouter, useRoute } from 'vue-router'
  import Button from '@/components/Base/Button'
  import { FormLabel, FormSelect, FormInput } from '@/components/Base/Form'
  
  interface Item {
    id_produk:   number|null
    volume_po:   number
    harga_tebus: number
    total_harga: number
  }
  
  const router    = useRouter()
  const route     = useRoute()
  const id        = Number(route.params.id)
  const loading   = ref(false)
  const error     = ref('')
  const initialized = ref(false)
  
  const vendors   = ref<any[]>([])
  const terminals = ref<any[]>([])
  const produks   = ref<any[]>([])
  
  // ambil semua dropdown
  async function fetchAll() {
    const [vRes, tRes, pRes] = await Promise.all([
      axios.get('/api/vendors',{ params:{ per_page:100 }}),
      axios.get('/api/terminals',{ params:{ per_page:100 }}),
      axios.get('/api/produks',{ params:{ per_page:100 }}),
    ])
    vendors.value   = vRes.data.data
    terminals.value = tRes.data.data
    produks.value   = pRes.data.data
  }
  
  const form = reactive({
    id_vendor:     null as number|null,
    id_terminal:   null as number|null,
    nomor_po:      '',
    tanggal_inven: '',
    kd_tax:        '',
    terms:         '',
    terms_day:     0,
    items:         [] as Item[],
    keterangan:    '',
    terms_condition: '',
    created_by:    '',
    lastupdate_by: ''
  })
  
  // hitung total tiap baris
  function computeTotal(i: number) {
    const it = form.items[i]
    it.total_harga = it.volume_po * it.harga_tebus
  }
  function computeAllTotals() {
    form.items.forEach((_, i) => computeTotal(i))
  }
  
  // kalkulasi summary
  const calcSubtotal   = computed(() => form.items.reduce((sum, it) => sum + it.total_harga, 0))
  const calcPPN        = computed(() => Math.round(calcSubtotal.value * 0.11))
  const calcTotalOrder = computed(() => calcSubtotal.value + calcPPN.value)
  
  // formatting dan input handler
  function formatNumber(v: number) {
    return v.toLocaleString('id-ID')
  }
  function onVolumeInput(e: Event, idx: number) {
    const str = (e.target as HTMLInputElement).value.replace(/\D/g, '')
    form.items[idx].volume_po = parseInt(str || '0', 10)
    computeTotal(idx)
  }
  function onHargaInput(e: Event, idx: number) {
    const str = (e.target as HTMLInputElement).value.replace(/\D/g, '')
    form.items[idx].harga_tebus = parseInt(str || '0', 10)
    computeTotal(idx)
  }
  
  // tambah/hapus baris
  function addRow()    { form.items.push({ id_produk:null, volume_po:0, harga_tebus:0, total_harga:0 }) }
  function removeRow(i:number) { form.items.splice(i,1) }
  
  // inisialisasi data
  async function init() {
    await fetchAll()
    // ambil user
    const { data:u } = await axios.get('/api/user')
    form.created_by    = u.name
    form.lastupdate_by = u.name
  
    // header
    const { data: p } = await axios.get(`/api/vendor-pos/${id}`)
    Object.assign(form, {
      id_vendor:     p.id_vendor,
      id_terminal:   p.id_terminal,
      nomor_po:      p.nomor_po,
      tanggal_inven: p.tanggal_inven,
      kd_tax:        p.kd_tax,
      terms:         p.terms,
      terms_day:     p.terms_day,
      keterangan:    p.keterangan || '',
      terms_condition: p.terms_condition || ''
    })
  
    // detail
    const { data: items } = await axios.get('/api/vendor-pos-produk', { params:{ id_po: id } })
    form.items = items.map((it:any) => ({
      id_produk:   it.id_produk,
      volume_po:   it.volume_po,
      harga_tebus: it.harga_tebus,
      total_harga: it.jumlah_harga
    }))
    computeAllTotals()
    initialized.value = true
  }
  
  onMounted(init)
  
  // kirim perubahan
  async function submit() {
    if (!form.id_vendor)   return Swal.fire('Error','Vendor wajib dipilih','error')
    if (!form.id_terminal) return Swal.fire('Error','Terminal wajib dipilih','error')
    if (!form.nomor_po)    return Swal.fire('Error','Nomor PO wajib diisi','error')
  
    loading.value = true
    error.value   = ''
    try {
      // 1) update header
      await axios.put(`/api/vendor-pos/${id}`, {
        id_vendor:     Number(form.id_vendor),
        id_terminal:   Number(form.id_terminal),
        nomor_po:      form.nomor_po,
        tanggal_inven: form.tanggal_inven,
        kd_tax:        form.kd_tax,
        terms:         form.terms,
        terms_day:     Number(form.terms_day),
        subtotal:      calcSubtotal.value,
        ppn11:         calcPPN.value,
        total_order:   calcTotalOrder.value,
        keterangan:    form.keterangan,
        terms_condition:    form.terms_condition,
        disposisi_po:  0,                // reset jika perlu
        lastupdate_by: form.lastupdate_by
      })
  
      // 2) hapus semua detail lama via endpoint batch-delete
      await axios.delete('/api/vendor-pos-produk/batch', { params: { id_po: id } })
  
      // 3) simpan detail baru via batch-create
      await axios.post('/api/vendor-pos-produk/batch', {
        items: form.items.map(it => ({
          id_po:        id,
          id_produk:    Number(it.id_produk),
          volume_po:    it.volume_po,
          harga_tebus:  it.harga_tebus,
          jumlah_harga: it.total_harga,
          created_by:   form.lastupdate_by
        }))
      })
  
      Swal.fire({ icon:'success', title:'PO diperbarui', toast:true, position:'top-end', timer:1500 })
      router.push({ name:'vendor-pos-list' })
    } catch (e:any) {
      error.value = e.response?.data?.message || 'Gagal menyimpan'
    } finally {
      loading.value = false
    }
  }
  
  function cancel() {
    router.push({ name:'vendor-pos-list' })
  }
  </script>
  