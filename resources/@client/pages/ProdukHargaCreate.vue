<template>
  <div class="py-6 bg-slate-100 min-h-screen">
    <div class="intro-y grid grid-cols-12 gap-6 mt-8">
      <div class="col-span-12">
        <div class="p-6 box">
          <h2 class="text-lg font-medium mb-4">Tambah Harga</h2>
          <p v-if="error" class="text-red-500 mb-4">{{ error }}</p>

          <!-- Add Row Button -->
          <div class="mb-4 flex justify-end">
            <Button variant="outline-secondary" @click="addRow" class="inline-flex items-center gap-2">
              <Lucide icon="Plus" class="w-4 h-4" />
              <span>Add Row</span>
            </Button>
          </div>

          <!-- Rows -->
          <div
            v-for="(row, i) in formRows"
            :key="i"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-6 border-b pb-4"
          >
            <div class="col-span-full flex justify-between items-center">
              <span class="font-semibold">Row {{ i + 1 }}</span>
              <Button
                v-if="formRows.length > 1"
                variant="danger"
                size="sm"
                @click="removeRow(i)"
                class="inline-flex items-center gap-2"
              >
                <Lucide icon="Trash2" class="w-4 h-4" />
                <span>Remove</span>
              </Button>
            </div>

            <!-- Periode -->
            <div>
              <FormLabel :for="`periode_awal_${i}`">Periode Awal</FormLabel>
              <FormInput type="date" v-model="row.periode_awal" class="w-full" />
            </div>
            <div>
              <FormLabel :for="`periode_akhir_${i}`">Periode Akhir</FormLabel>
              <FormInput type="date" v-model="row.periode_akhir" class="w-full" />
            </div>

            <!-- Cabang & Produk -->
            <div>
              <FormLabel :for="`cabang_${i}`">Cabang</FormLabel>
              <FormSelect v-model="row.id_cabang" class="w-full">
                <option disabled value="">-- Pilih Cabang --</option>
                <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                  {{ c.nama_cabang }}
                </option>
              </FormSelect>
            </div>
            <div>
              <FormLabel :for="`produk_${i}`">Produk</FormLabel>
              <FormSelect v-model="row.id_produk" class="w-full">
                <option disabled value="">-- Pilih Produk --</option>
                <option
                  v-for="p in produks"
                  :key="p.id_produk"
                  :value="p.id_produk"
                >
                  {{ p.nama_produk }} — Uk: {{ p.ukuran?.nama_ukuran || '-' }} — Sat: {{ p.ukuran?.satuan?.nama_satuan || '-' }}
                </option>
              </FormSelect>
            </div>

            <!-- Harga -->
            <div>
              <FormLabel :for="`hpl_${i}`">Harga Price List</FormLabel>
              <FormInput
                type="text"
                :value="row.displayPriceList"
                @input="onPriceListInput(i, $event)"
                placeholder="0"
                class="w-full"
                :readonly="isReadonly('harga_price_list')"
              />
            </div>

            <div>
              <FormLabel :for="`hbm_${i}`">Harga BM</FormLabel>
              <FormInput
                type="text"
                :value="row.displayBm"
                @input="onBmInput(i, $event)"
                placeholder="0"
                class="w-full"
                :readonly="isReadonly('harga_bm')"
              />
            </div>

            <div>
              <FormLabel :for="`cogs_${i}`">Harga COGS</FormLabel>
              <FormInput
                type="text"
                :value="row.displayCogs"
                @input="onCogsInput(i, $event)"
                placeholder="0"
                class="w-full"
                :readonly="isReadonly('harga_cogs')"
              />
            </div>

            <div>
              <FormLabel :for="`margin_${i}`">Harga Margin</FormLabel>
              <FormInput
                type="text"
                :value="row.displayMargin"
                @input="onMarginInput(i, $event)"
                placeholder="0"
                class="w-full"
                :readonly="isReadonly('harga_margin')"
              />
            </div>

            <div>
              <FormLabel :for="`om_${i}`">Harga OM</FormLabel>
              <FormInput
                type="text"
                :value="row.displayOm"
                @input="onOmInput(i, $event)"
                placeholder="0"
                class="w-full"
                :readonly="isReadonly('harga_om')"
              />
            </div>

            <div>
  <FormLabel :for="`ceo_${i}`">Harga CEO</FormLabel>
  <FormInput
    type="text"
    :value="row.displayCeo"
    @input="onCeoInput(i, $event)"
    placeholder="0"
    class="w-full"
    :readonly="isReadonly('harga_ceo')"
  />
</div>


            <!-- Catatan -->
            <div class="col-span-full">
              <FormLabel :for="`catatan_${i}`">Catatan</FormLabel>
              <textarea
                v-model="row.catatan"
                rows="2"
                class="w-full border rounded px-3 py-2"
                placeholder="Tambahkan catatan (opsional)"
              ></textarea>
            </div>
          </div>

          <!-- Tombol -->
          <div class="mt-6 flex justify-end space-x-2">
            <Button variant="outline-secondary" @click="cancel" class="inline-flex items-center gap-2">
              <Lucide icon="X" class="w-4 h-4" />
              <span>Batal</span>
            </Button>
            <Button variant="primary" :loading="loading" @click="submitAll" class="inline-flex items-center gap-2">
              <Lucide v-if="!loading" icon="Save" class="w-4 h-4" />
              <span>Simpan</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import Button from '@/components/Base/Button'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { useRouter } from 'vue-router'

// router
const router = useRouter()

// dropdown data
const cabangs = ref<any[]>([])
const produks = ref<any[]>([])

onMounted(async () => {
  const [c, p] = await Promise.all([
    axios.get('/api/cabangs', { params: { per_page: 100 } }),
    axios.get('/api/produks', { params: { per_page: 100 } })
  ])
  cabangs.value = c.data.data
  produks.value = p.data.data
})

// user login
const currentUser = ref<any>({ name: '', id_role: 0 })
onMounted(async () => {
  const { data } = await axios.get('/api/user')
  currentUser.value = data
})

// role checks
const isRole5 = computed(() => currentUser.value.id_role === 5)
const isRole8 = computed(() => currentUser.value.id_role === 8)
const isRole10 = computed(() => currentUser.value.id_role === 10)


// helper: field readonly rules
function isReadonly(field: string) {
  // role 5: hanya bisa isi harga_cogs
  if (isRole5.value && field !== 'harga_cogs') return true
  // role 8: hanya bisa isi harga_bm
  if (isRole8.value && field !== 'harga_bm') return true
  if (isRole10.value && field !== 'harga_om') return true
 
  return false
}

// tipe data
interface Row {
  periode_awal: string
  periode_akhir: string
  id_cabang: number | ''
  id_produk: number | ''
  harga_price_list: number | null
  displayPriceList: string
  harga_bm: number | null
  displayBm: string
  harga_cogs: number | null
  displayCogs: string
  harga_margin: number | null
  displayMargin: string
  harga_om: number | null
  displayOm: string
  harga_ceo: number | null
  displayCeo: string
  catatan: string
  created_by: string
}

const formRows = ref<Row[]>([
  {
    periode_awal: '',
    periode_akhir: '',
    id_cabang: '',
    id_produk: '',
    harga_price_list: 0,
    displayPriceList: '0',
    harga_bm: 0,
    displayBm: '0',
    harga_cogs: null,
    displayCogs: '',
    harga_margin: 0,
    displayMargin: '0',
    harga_om: 0,
    displayOm: '0',
    harga_ceo: 0,
    displayCeo: '0',
    catatan: '',
    created_by: currentUser.value.name,
  },
])

function addRow() {
  formRows.value.push({ ...formRows.value[0] })
}
function removeRow(i: number) {
  if (formRows.value.length > 1) formRows.value.splice(i, 1)
}

// format ribuan
function formatThousand(v: string) {
  return v.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}
function parseMoney(e: Event) {
  return (e.target as HTMLInputElement).value.replace(/\D/g, '')
}

function onPriceListInput(i: number, e: Event) {
  if (isReadonly('harga_price_list')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_price_list = raw ? parseInt(raw) : 0
  formRows.value[i].displayPriceList = formatThousand(raw)
}

function onBmInput(i: number, e: Event) {
  if (isReadonly('harga_bm')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_bm = raw ? parseInt(raw) : 0
  formRows.value[i].displayBm = formatThousand(raw)
}

function onCogsInput(i: number, e: Event) {
  if (isReadonly('harga_cogs')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_cogs = raw ? parseInt(raw) : null
  formRows.value[i].displayCogs = formatThousand(raw)
}

function onMarginInput(i: number, e: Event) {
  if (isReadonly('harga_margin')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_margin = raw ? parseInt(raw) : 0
  formRows.value[i].displayMargin = formatThousand(raw)
}

function onOmInput(i: number, e: Event) {
  if (isReadonly('harga_om')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_om = raw ? parseInt(raw) : 0
  formRows.value[i].displayOm = formatThousand(raw)
}

function onCeoInput(i: number, e: Event) {
  if (isReadonly('harga_ceo')) return
  const raw = parseMoney(e)
  formRows.value[i].harga_ceo = raw ? parseInt(raw) : 0
  formRows.value[i].displayCeo = formatThousand(raw)
}

// validasi sederhana
const error = ref('')
function validateRows(): { ok: boolean; message?: string } {
  for (let i = 0; i < formRows.value.length; i++) {
    const r = formRows.value[i]
    const rowNo = i + 1

    if (!r.periode_awal) return { ok: false, message: `Row ${rowNo}: Periode Awal wajib diisi` }
    if (!r.periode_akhir) return { ok: false, message: `Row ${rowNo}: Periode Akhir wajib diisi` }
    if (new Date(r.periode_akhir) < new Date(r.periode_awal))
      return { ok: false, message: `Row ${rowNo}: Periode Akhir tidak boleh lebih awal dari Periode Awal` }

    if (!r.id_cabang) return { ok: false, message: `Row ${rowNo}: Cabang wajib dipilih` }
    if (!r.id_produk) return { ok: false, message: `Row ${rowNo}: Produk wajib dipilih` }
  }
  return { ok: true }
}

// submit semua
const loading = ref(false)
async function submitAll() {
  const v = validateRows()
  if (!v.ok) {
    Swal.fire({ icon: 'error', title: 'Validasi Gagal', text: v.message })
    return
  }

  loading.value = true
  try {
    await Promise.all(
      formRows.value.map(r => {
        const payload = {
          periode_awal: r.periode_awal,
          periode_akhir: r.periode_akhir,
          id_cabang: r.id_cabang,
          id_produk: r.id_produk,
          harga_price_list: r.harga_price_list ?? 0,
          harga_bm: r.harga_bm ?? 0,
          harga_cogs: r.harga_cogs ?? 0,
          harga_margin: r.harga_margin ?? 0,
          harga_om: r.harga_om ?? 0,
          harga_ceo: r.harga_ceo ?? 0, 
          catatan: r.catatan,
          created_by: currentUser.value.name,
        }
        return axios.post('/api/produk-hargas', payload)
      })
    )

    Swal.fire({ icon: 'success', title: 'Data tersimpan', toast: true, timer: 1500, showConfirmButton: false })
    router.push({ name: 'produk-hargas' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: error.value })
  } finally {
    loading.value = false
  }
}

function cancel() {
  router.back()
}
</script>
