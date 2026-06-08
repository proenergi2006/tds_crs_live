<template>
  <div class="p-4">
    <div class="mb-6 mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-800">
          Tambah Vendor PO
        </h2>

        <p class="mt-1 text-sm text-slate-500">
          Lengkapi informasi purchase order vendor, rincian produk, nilai transaksi, dan terms.
        </p>
      </div>

      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </div>

    <div v-if="error" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="space-y-6 xl:col-span-2">
        <CardSection title="Informasi PO" description="Data utama purchase order vendor" icon="FileText">
          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-4">
              <FormLabel for="vendor">Vendor</FormLabel>
              <FormSelect id="vendor" v-model="form.id_vendor">
                <option disabled value="">-- Pilih Vendor --</option>
                <option v-for="v in vendors" :key="v.id_vendor" :value="v.id_vendor">
                  {{ v.nama_vendor }}
                </option>
              </FormSelect>
            </div>

            <div class="col-span-12 md:col-span-4">
              <FormLabel for="terminal">Terminal</FormLabel>
              <FormSelect id="terminal" v-model="form.id_terminal">
                <option disabled value="">-- Pilih Terminal --</option>
                <option v-for="t in terminals" :key="t.id_terminal" :value="t.id_terminal">
                  {{ t.nama_terminal }}
                </option>
              </FormSelect>
            </div>

            <div class="col-span-12 md:col-span-4">
              <FormLabel for="nomor_po">Nomor PO</FormLabel>
              <FormInput id="nomor_po" v-model="form.nomor_po" placeholder="Nomor PO" />
            </div>

            <div class="col-span-12 md:col-span-3">
              <FormLabel for="tanggal_inven">Tanggal</FormLabel>
              <FormInput id="tanggal_inven" v-model="form.tanggal_inven" type="date" />
            </div>

            <div class="col-span-12 md:col-span-3">
              <FormLabel for="kd_tax">Kode Tax</FormLabel>
              <FormSelect id="kd_tax" v-model="form.kd_tax">
                <option disabled value="">-- Pilih Kode Tax --</option>
                <option value="E">E</option>
                <option value="EC">EC</option>
              </FormSelect>
            </div>

            <div class="col-span-12 md:col-span-3">
              <FormLabel for="terms">Terms</FormLabel>
              <FormSelect id="terms" v-model="form.terms">
                <option disabled value="">-- Pilih Terms --</option>
                <option value="CBD">CBD</option>
                <option value="COD">COD</option>
                <option value="TOP">TOP</option>
              </FormSelect>
            </div>

            <div class="col-span-12 md:col-span-3">
              <FormLabel for="terms_day">Terms Day</FormLabel>
              <FormInput id="terms_day" v-model.number="form.terms_day" type="number" />
            </div>
          </div>
        </CardSection>

        <CardSection title="Rincian Produk" description="Produk, volume, dan harga tebus" icon="Boxes"
          icon-class="bg-indigo-100 text-indigo-600">
          <template #action>
            <Button variant="outline-primary" class="inline-flex items-center gap-2" @click="addRow">
              <Lucide icon="Plus" class="h-4 w-4" />
              Tambah Baris
            </Button>
          </template>

          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[920px] divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">No</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Produk</th>
                  <th class="w-36 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Volume PO</th>
                  <th class="w-44 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Harga Tebus</th>
                  <th class="w-44 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Total Harga</th>
                  <th class="w-16 px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">Aksi</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-200">
                <tr v-for="(item, idx) in form.items" :key="idx" class="transition hover:bg-slate-50">
                  <td class="px-4 py-3 text-center font-medium text-slate-700">
                    {{ idx + 1 }}.
                  </td>
                  <td class="px-4 py-3">
                    <FormSelect :id="`produk_${idx}`" v-model="item.id_produk" class="min-w-64">
                      <option disabled value="">-- Pilih Produk --</option>
                      <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
                        {{ p.nama_produk }} ({{ p.ukuran?.nama_ukuran }} - {{ p.ukuran?.satuan?.nama_satuan }})
                      </option>
                    </FormSelect>
                  </td>
                  <td class="px-4 py-3">
                    <FormInput :id="`volume_po_${idx}`" type="text" :value="formatNumber(item.volume_po)"
                      class="text-right" @input="onVolumeInput($event, idx)" />
                  </td>
                  <td class="px-4 py-3">
                    <FormInput :id="`harga_tebus_${idx}`" type="text" :value="formatNumber(item.harga_tebus)"
                      class="text-right" @input="onHargaInput($event, idx)" />
                  </td>
                  <td class="px-4 py-3">
                    <FormInput :id="`total_harga_${idx}`" :value="formatNumber(item.total_harga)" readonly
                      class="bg-slate-100 text-right font-semibold text-slate-700" />
                  </td>
                  <td class="px-4 py-3 text-center">
                    <Button v-if="form.items.length > 1" variant="soft-danger" rounded
                      class="!h-9 !w-9 !p-0 !shadow-none" title="Hapus" @click="removeRow(idx)">
                      <Lucide icon="Trash2" class="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              </tbody>

              <tfoot class="border-t border-slate-200 bg-slate-50">
                <tr>
                  <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-slate-600">Subtotal</td>
                  <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">{{ formatNumber(calcSubtotal) }}</td>
                  <td></td>
                </tr>
                <tr>
                  <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-slate-600">PPN 11%</td>
                  <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">{{ formatNumber(calcPPN) }}</td>
                  <td></td>
                </tr>
                <tr>
                  <td colspan="4" class="px-4 py-4 text-right text-base font-semibold text-slate-800">Total Order</td>
                  <td class="px-4 py-4 text-right text-base font-bold text-emerald-700">{{ formatNumber(calcTotalOrder) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </CardSection>
      </div>

      <div class="xl:col-span-1">
        <div class="sticky top-4 space-y-6">
          <CardSection title="Catatan & Terms" description="Informasi tambahan untuk purchase order" icon="StickyNote"
            icon-class="bg-amber-100 text-amber-600">
            <div class="space-y-4">
              <div>
                <FormLabel for="keterangan">Catatan</FormLabel>
                <FormTextarea id="keterangan" v-model="form.keterangan" rows="4" placeholder="(opsional)" />
              </div>

              <div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                  <input v-model="termsChecked" type="checkbox"
                    class="rounded border-slate-300 text-primary focus:ring-primary" />
                  Terms & Condition
                </label>
              </div>

              <div>
                <FormTextarea id="terms_condition" v-model="form.terms_condition" rows="6"
                  placeholder="Isi terms & condition" :disabled="!termsChecked"
                  :class="!termsChecked ? 'bg-slate-100 text-slate-400' : ''" />
              </div>
            </div>
          </CardSection>

          <div class="flex flex-col gap-3 rounded-2xl bg-white p-4 shadow-sm">
            <Button variant="primary" :loading="loading" @click="submit">
              <Lucide v-if="!loading" icon="Save" class="mr-2 h-4 w-4" />
              Simpan Purchase Order
            </Button>
            <Button variant="outline-secondary" @click="cancel">
              <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" />
              Batal
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormLabel, FormSelect, FormInput, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'

const termsChecked = ref(false)

interface Item {
  id_produk: number | null
  volume_po: number
  harga_tebus: number
  total_harga: number
}

const router = useRouter()
const loading = ref(false)
const error = ref('')

const vendors = ref<any[]>([])
const terminals = ref<any[]>([])
const produks = ref<any[]>([])

async function fetchAll() {
  const [vRes, tRes, pRes] = await Promise.all([
    axios.get('/api/vendors', { params: { per_page: 100 } }),
    axios.get('/api/terminals', { params: { per_page: 100 } }),
    axios.get('/api/produks', { params: { per_page: 100 } }),
  ])
  vendors.value = vRes.data.data
  terminals.value = tRes.data.data
  produks.value = pRes.data.data
}

const form = reactive({
  id_vendor: null as number | null,
  id_terminal: null as number | null,
  nomor_po: '',
  tanggal_inven: '',
  kd_tax: '',
  terms: '',
  terms_day: 0,
  items: [{
    id_produk: null,
    volume_po: 0,
    harga_tebus: 0,
    total_harga: 0,
  }] as Item[],
  keterangan: '',
  terms_condition: '',
  created_by: '',
})

function addRow() {
  form.items.push({ id_produk: null, volume_po: 0, harga_tebus: 0, total_harga: 0 })
}

function removeRow(i: number) {
  form.items.splice(i, 1)
}

function computeTotal(i: number) {
  const it = form.items[i]
  it.total_harga = it.volume_po * it.harga_tebus
}

const calcSubtotal = computed(() => form.items.reduce((s, it) => s + it.total_harga, 0))
const calcPPN = computed(() => Math.round(calcSubtotal.value * 0.11))
const calcTotalOrder = computed(() => calcSubtotal.value + calcPPN.value)

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

function formatNumber(v: number) {
  return v.toLocaleString('id-ID')
}

function cancel() {
  router.back()
}

async function submit() {
  if (!form.id_vendor) return Swal.fire('Error', 'Vendor wajib dipilih', 'error')
  if (!form.id_terminal) return Swal.fire('Error', 'Terminal wajib dipilih', 'error')

  loading.value = true
  error.value = ''
  try {
    const header = {
      id_vendor: Number(form.id_vendor),
      id_terminal: Number(form.id_terminal),
      nomor_po: form.nomor_po,
      tanggal_inven: form.tanggal_inven,
      kd_tax: form.kd_tax,
      terms: form.terms,
      terms_day: Number(form.terms_day),
      subtotal: calcSubtotal.value,
      ppn11: calcPPN.value,
      total_order: calcTotalOrder.value,
      keterangan: form.keterangan,
      terms_condition: termsChecked.value ? form.terms_condition : null,
      created_by: form.created_by,
    }
    const { data: savedPo } = await axios.post('/api/vendor-pos', header)

    await axios.post('/api/vendor-pos-produk/batch', {
      items: form.items.map(it => ({
        id_po: savedPo.id_po,
        id_produk: Number(it.id_produk),
        volume_po: it.volume_po,
        harga_tebus: it.harga_tebus,
        jumlah_harga: it.total_harga,
        created_by: form.created_by,
      })),
    })

    Swal.fire({ icon: 'success', title: 'PO berhasil disimpan', toast: true, position: 'top-end', timer: 1500 })
    router.push({ name: 'vendor-pos-list' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const { data: u } = await axios.get('/api/user')
    form.created_by = u.name
  } catch { }
  await fetchAll()
})
</script>
