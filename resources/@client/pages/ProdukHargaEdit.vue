<script setup lang="ts">
  import { ref, reactive, onMounted, computed, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'
  
  import Button from '@/components/Base/Button'
  import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
  import Lucide from '@/components/Base/Lucide'
  
  const route = useRoute()
  const router = useRouter()
  const id = Number(route.params.id)
  
  // =====================
  // HELPERS (INT RUPIAH)
  // =====================
  function toIntMoney(v: any): number {
  if (v == null || v === '') return 0
  if (typeof v === 'number') return Math.trunc(v)

  let s = String(v).trim()

  // kalau format Indonesia: 900.000,00
  if (s.includes(',') && s.includes('.')) {
    s = s.split(',')[0]          // ambil sebelum koma
    s = s.replace(/\./g, '')     // hapus titik ribuan
    return s ? parseInt(s, 10) : 0
  }

  // kalau format: 900000,00
  if (s.includes(',') && !s.includes('.')) {
    s = s.split(',')[0]
    s = s.replace(/[^\d]/g, '')
    return s ? parseInt(s, 10) : 0
  }

  // kalau format: 900000.00 (decimal point)
  if (s.includes('.') && !s.includes(',')) {
    s = s.split('.')[0]          // ambil sebelum titik desimal
    s = s.replace(/[^\d]/g, '')
    return s ? parseInt(s, 10) : 0
  }

  // fallback: digit only
  s = s.replace(/[^\d]/g, '')
  return s ? parseInt(s, 10) : 0
}

  function formatID(n: number): string {
    return (n || 0).toLocaleString('id-ID')
  }
  function parseMoney(e: Event) {
    // return digit only string
    return (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  }
  
  // === STATE FORM ===
  const form = reactive({
    id_produk_harga: id,
    periode_awal: '',
    periode_akhir: '',
    id_cabang: '',
    id_produk: '',
    harga_price_list: 0,
    harga_bm: 0,
    harga_cogs: 0,
    harga_margin: 0,
    harga_om: 0,
    harga_ceo: 0,
    catatan: '',
    lastupdate_by: ''
  })
  
  // === DISPLAY FORMAT (STRING YANG TAMPIL DI INPUT) ===
  const displayPriceList = ref('0')
  const displayBm = ref('0')
  const displayCogs = ref('0')
  const displayMargin = ref('0')
  const displayOm = ref('0')
  const displayCeo = ref('0')
  
  // === DROPDOWN DATA ===
  const cabangs = ref<any[]>([])
  const produks = ref<any[]>([])
  
  // === USER LOGIN ===
  const currentUser = ref<any>({ name: '', id_role: 0 })
  const isRole2 = computed(() => currentUser.value.id_role === 2)
  const isRole5 = computed(() => currentUser.value.id_role === 5)
  const isRole8 = computed(() => currentUser.value.id_role === 8)
  
  // =====================
  // READONLY RULES
  // =====================
  function isReadonly(field: string) {
    // role 5 hanya bisa edit harga_cogs
    if (isRole5.value && field !== 'harga_cogs') return true
  
    // role 8 hanya bisa edit harga_bm
    if (isRole8.value && field !== 'harga_bm') return true
  
    // role 2 tidak bisa edit harga_cogs
    if (isRole2.value && field === 'harga_cogs') return true
  
    // role lain bisa edit semua
    return false
  }
  
  // =====================
  // FETCH DROPDOWNS
  // =====================
  async function fetchDropdowns() {
    try {
      const resC = await axios.get('/api/cabangs', { params: { per_page: 100 } })
      cabangs.value = resC.data.data || resC.data
    } catch {}
    try {
      const resP = await axios.get('/api/produks', { params: { per_page: 100 } })
      produks.value = resP.data.data || resP.data
    } catch {}
  }
  
  // =====================
  // INPUT HANDLERS (ALL INT)
  // =====================
  function onPriceListInput(e: Event) {
    if (isReadonly('harga_price_list')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_price_list = n
    displayPriceList.value = formatID(n)
  }
  function onBmInput(e: Event) {
    if (isReadonly('harga_bm')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_bm = n
    displayBm.value = formatID(n)
  }
  function onCogsInput(e: Event) {
    if (isReadonly('harga_cogs')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_cogs = n
    displayCogs.value = formatID(n)
  }
  function onMarginInput(e: Event) {
    if (isReadonly('harga_margin')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_margin = n
    displayMargin.value = formatID(n)
  }
  function onOmInput(e: Event) {
    if (isReadonly('harga_om')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_om = n
    displayOm.value = formatID(n)
  }
  function onCeoInput(e: Event) {
    if (isReadonly('harga_ceo')) return
    const raw = parseMoney(e)
    const n = raw ? parseInt(raw, 10) : 0
    form.harga_ceo = n
    displayCeo.value = formatID(n)
  }
  
  // =====================
  // AUTO HITUNG PRICE LIST
  // Price List = COGS + Margin
  // =====================
  watch(
    () => [form.harga_cogs, form.harga_margin],
    ([cogs, margin]) => {
      // hanya auto isi jika field price list boleh diedit
      if (isReadonly('harga_price_list')) return
  
      const total = toIntMoney(cogs) + toIntMoney(margin)
      form.harga_price_list = total
      displayPriceList.value = formatID(total)
    },
    { immediate: true }
  )
  
  // =====================
  // VALIDASI
  // =====================
  function validate(): { ok: boolean; message?: string } {
    if (!form.periode_awal) return { ok: false, message: 'Periode Awal wajib diisi' }
    if (!form.periode_akhir) return { ok: false, message: 'Periode Akhir wajib diisi' }
    if (new Date(form.periode_akhir) < new Date(form.periode_awal))
      return { ok: false, message: 'Periode Akhir tidak boleh lebih awal dari Periode Awal' }
    if (!form.id_cabang) return { ok: false, message: 'Cabang wajib dipilih' }
    if (!form.id_produk) return { ok: false, message: 'Produk wajib dipilih' }
    return { ok: true }
  }
  
  // =====================
  // SUBMIT
  // =====================
  async function submit() {
    const v = validate()
    if (!v.ok) {
      return Swal.fire({ icon: 'error', title: 'Validasi Gagal', text: v.message })
    }
  
    try {
      const payload = {
        periode_awal: form.periode_awal,
        periode_akhir: form.periode_akhir,
        id_cabang: form.id_cabang,
        id_produk: form.id_produk,
  
        // kirim angka integer (rupiah)
        harga_price_list: isReadonly('harga_price_list') ? 0 : toIntMoney(form.harga_price_list),
        harga_bm: isReadonly('harga_bm') ? 0 : toIntMoney(form.harga_bm),
        harga_cogs: toIntMoney(form.harga_cogs), // role5 memang boleh edit ini
        harga_margin: isReadonly('harga_margin') ? 0 : toIntMoney(form.harga_margin),
        harga_om: isReadonly('harga_om') ? 0 : toIntMoney(form.harga_om),
        harga_ceo: isReadonly('harga_ceo') ? 0 : toIntMoney(form.harga_ceo),
  
        catatan: form.catatan,
        lastupdate_by: currentUser.value.name,
      }
  
      await axios.put(`/api/produk-hargas/${id}`, payload, {
        headers: { 'Content-Type': 'application/json' }
      })
  
      Swal.fire({
        icon: 'success',
        title: 'Harga diperbarui',
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false
      })
      router.push({ name: 'produk-hargas' })
    } catch (e: any) {
      Swal.fire('Error', e.response?.data?.message || 'Gagal update', 'error')
    }
  }
  
  // =====================
  // INIT
  // =====================
  onMounted(async () => {
    await fetchDropdowns()
  
    // user login
    const { data: u } = await axios.get('/api/user')
    currentUser.value = u
    form.lastupdate_by = u.name
  
    // data produk harga
    const { data } = await axios.get(`/api/produk-hargas/${id}`)
    Object.assign(form, data)
  
    // normalisasi semua numeric jadi int rupiah
    form.harga_price_list = toIntMoney(data.harga_price_list)
    form.harga_bm         = toIntMoney(data.harga_bm)
    form.harga_cogs       = toIntMoney(data.harga_cogs)
    form.harga_margin     = toIntMoney(data.harga_margin)
    form.harga_om         = toIntMoney(data.harga_om)
    form.harga_ceo        = toIntMoney(data.harga_ceo)
  
    // isi display
    displayPriceList.value = formatID(form.harga_price_list)
    displayBm.value        = formatID(form.harga_bm)
    displayCogs.value      = formatID(form.harga_cogs)
    displayMargin.value    = formatID(form.harga_margin)
    displayOm.value        = formatID(form.harga_om)
    displayCeo.value       = formatID(form.harga_ceo)
  })
  </script>
  
  <template>
    <div class="py-6 bg-slate-100 min-h-screen">
      <div class="intro-y grid grid-cols-12 gap-6 mt-8">
        <div class="col-span-12">
          <div class="p-6 box">
            <h2 class="text-lg font-medium mb-4">Edit Harga</h2>
  
            <div class="space-y-4">
              <div>
                <FormLabel htmlFor="periode_awal">Periode Awal</FormLabel>
                <FormInput id="periode_awal" type="date" v-model="form.periode_awal" />
              </div>
  
              <div>
                <FormLabel htmlFor="periode_akhir">Periode Akhir</FormLabel>
                <FormInput id="periode_akhir" type="date" v-model="form.periode_akhir" />
              </div>
  
              <div>
                <FormLabel htmlFor="cabang">Cabang</FormLabel>
                <FormSelect id="cabang" v-model="form.id_cabang">
                  <option disabled value="">-- Pilih Cabang --</option>
                  <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                    {{ c.nama_cabang }}
                  </option>
                </FormSelect>
              </div>
  
              <div>
                <FormLabel htmlFor="produk">Produk</FormLabel>
                <FormSelect id="produk" v-model="form.id_produk">
                  <option disabled value="">-- Pilih Produk --</option>
                  <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
                    {{ p.nama_produk }} – {{ p.ukuran?.nama_ukuran }} ({{ p.ukuran?.satuan?.nama_satuan }})
                  </option>
                </FormSelect>
              </div>
  
              <!-- Harga -->
              <div>
                <FormLabel>Harga Price List (Auto: COGS + Margin)</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayPriceList"
                  @input="onPriceListInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_price_list')"
                />
              </div>
  
              <div>
                <FormLabel>Harga BM</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayBm"
                  @input="onBmInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_bm')"
                />
              </div>
  
              <div>
                <FormLabel>Harga COGS</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayCogs"
                  @input="onCogsInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_cogs')"
                />
              </div>
  
              <div>
                <FormLabel>Harga Margin</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayMargin"
                  @input="onMarginInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_margin')"
                />
              </div>
  
              <div>
                <FormLabel>Harga OM</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayOm"
                  @input="onOmInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_om')"
                />
              </div>
  
              <div>
                <FormLabel>Harga CEO</FormLabel>
                <FormInput
                  type="text"
                  v-model="displayCeo"
                  @input="onCeoInput"
                  class="text-right"
                  placeholder="0"
                  :readonly="isReadonly('harga_ceo')"
                />
              </div>
  
              <div>
                <FormLabel htmlFor="catatan">Catatan</FormLabel>
                <textarea
                  id="catatan"
                  v-model="form.catatan"
                  rows="3"
                  class="w-full border rounded px-3 py-2"
                  placeholder="(opsional)"
                ></textarea>
              </div>
            </div>
  
            <!-- Tombol -->
            <div class="mt-6 flex justify-end space-x-2">
              <Button variant="outline-secondary" @click="() => router.back()" class="inline-flex items-center gap-2">
                <Lucide icon="X" class="w-4 h-4" />
                <span>Batal</span>
              </Button>
              <Button variant="primary" @click="submit" class="inline-flex items-center gap-2">
                <Lucide icon="Save" class="w-4 h-4" />
                <span>Simpan</span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  