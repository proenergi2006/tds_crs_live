<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toRaw } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idParam = route.params.id as string | undefined
const isEdit = Boolean(idParam)

/* State: lookups */
const customers = ref<any[]>([])
const cabangs = ref<any[]>([])
const produks = ref<any[]>([])
const transportirs = ref<any[]>([])
const wilayahs = ref<any[]>([])
const volumes = ref<any[]>([])

/* State: ongkos angkut */
const oaKapal = ref(0)
const oaTruck = ref(0)
const oaKapalInput = reactive({ id_transportir: '', id_angkut_wilayah: '', id_volume: '' })
const oaTruckInput = reactive({ id_transportir: '', id_angkut_wilayah: '', id_volume: '' })
const oaSelectKey = ref(0)

/* State: misc */
const loading = ref(false)
const canSeeHarga = ref(false)
const disposisiPenawaran = ref<number | null>(null)

interface ItemLine {
  id_produk: number | ''
  volume_order: string
  harga_tebus: string
  persen: string
  harga_price_list?: number
}

const form = reactive({
  id_customer: '' as number | '',
  id_cabang: '' as number | '',
  nomor_penawaran: '',
  masa_berlaku: '',
  sampai_dengan: '',
  items: [] as ItemLine[],

  tipe_pembayaran: '',
  dp_persen: '',
  dp_keterangan: '',
  repayment_persen: '',
  repayment_hari: '',
  order_method: '',
  toleransi_penyusutan: '',
  lokasi_pengiriman: '',
  type_pengiriman: '',
  metode: '',
  refund: 0,
  other_cost: 0,
  perhitungan: '',
  keterangan: '',
  catatan: '',
  syarat_ketentuan: '',
  pengiriman_via: 'truck+kapal',
  ukuran_dasar: '',
  // TODO: discount input — tersembunyi, akan diimplementasi di task terpisah
  discount: 0,
  oat: 0,
  jenis_penawaran: '1',
  kepada: '',
  nama: '',
  jabatan: '',
  telepon: '',
  alamat: '',
  abrasi: '',
  harga_dasar: 0,
})

/* Validation state */
const errors = reactive<Record<string, boolean>>({})
const invalidProdukIdx = ref<Set<number>>(new Set())
const invalidPersenIdx = ref<Set<number>>(new Set())

/* Computed: totals */
const oatPerVolumeManual = computed(() => toNum(form.oat))
const hargaDasarNumber = computed(() => toNum(form.harga_dasar || 0))
const oatPerVolume = computed(() => toNum(form.oat || 0))

const dppHargaDasar = computed(() => hargaDasarNumber.value + oatPerVolume.value)
const ppnHargaDasar = computed(() => Math.round(dppHargaDasar.value * 0.11))
const grandTotalHargaDasar = computed(() => dppHargaDasar.value + ppnHargaDasar.value)

const avgHargaPriceList = computed(() => {
  const totalPersen = form.items.reduce((s, it) => s + toFloat(it.persen || '0'), 0)
  if (totalPersen <= 0) return 0
  const totalWeighted = form.items.reduce((sum, it) => {
    return sum + (toFloat(it.persen || '0') * Number(it.harga_price_list || 0))
  }, 0)
  return totalWeighted / totalPersen
})

const totalPersenNumber = computed(() =>
  Math.round(form.items.reduce((sum, it) => sum + (parseFloat((it.persen || '0').replace(',', '.')) || 0), 0) * 100) / 100
)
const totalPersenDisplay = computed(() => totalPersenNumber.value.toLocaleString('id-ID'))

const totalVolumePO = computed(() =>
  form.items.reduce((sum, it) => sum + (parseInt((it.volume_order || '').replace(/\./g, ''), 10) || 0), 0)
)
const totalVolume = computed(() => totalVolumePO.value.toLocaleString('id-ID'))

const subtotal = computed(() => form.items.reduce((sum, it) => sum + lineTotal(it), 0))
const grandTotalHargaTebus = computed(() => subtotal.value)

const totalDiskon = computed(() => Math.min(Math.max(form.discount, 0), subtotal.value))
const grandTotalHargaTebusSetelahDiskon = computed(() => subtotal.value - totalDiskon.value)
const totalOAT = computed(() => oatPerVolume.value * totalVolumePO.value)
const ppn11 = computed(() => Math.round(grandTotalHargaTebusSetelahDiskon.value * 0.11))
const grandTotalWithOAT = computed(() => grandTotalHargaTebusSetelahDiskon.value + ppn11.value + totalOAT.value)

onMounted(async () => {
  await Promise.all([fetchSelects(), fetchTransportirWilayahVolume()])
  if (!isEdit) {
    form.items.push({ id_produk: '', volume_order: '', harga_tebus: '', persen: '' })
  } else {
    await fetchPenawaran()
  }
})

watch(() => oaKapalInput.id_volume, (val) => {
  const vol = volumes.value.find(v => String(v.id_volume) === String(val))
  if (vol) form.ukuran_dasar = String(vol.volume ?? '')
})

watch(() => [oaKapalInput.id_transportir, oaKapalInput.id_angkut_wilayah, oaKapalInput.id_volume], async () => {
  if (form.metode !== 'CIF' && form.metode !== 'DAP') return
  if (!oaKapalInput.id_transportir || !oaKapalInput.id_angkut_wilayah || !oaKapalInput.id_volume) return
  try {
    const { data } = await axios.get('/api/ongkos-kapal/check', { params: toRaw(oaKapalInput) })
    oaKapal.value = data.oa || 0
  } catch {
    oaKapal.value = 0
  }
})

watch(() => [oaTruckInput.id_transportir, oaTruckInput.id_angkut_wilayah, oaTruckInput.id_volume], async () => {
  if (form.metode !== 'DAP') return
  if (!oaTruckInput.id_transportir || !oaTruckInput.id_angkut_wilayah || !oaTruckInput.id_volume) return
  try {
    const { data } = await axios.get('/api/ongkos-trucks/check', { params: toRaw(oaTruckInput) })
    oaTruck.value = data.oa || 0
  } catch {
    oaTruck.value = 0
  }
})

/* Data fetchers */
async function fetchSelects() {
  try {
    const [cData, caData, pData] = await Promise.all([
      axios.get('/api/customers', { params: { per_page: 100 } }),
      axios.get('/api/cabangs'),
      axios.get('/api/produks?with=ukuran'),
    ])
    customers.value = cData.data.data || cData.data
    cabangs.value = caData.data.data || caData.data
    produks.value = pData.data.data || pData.data
  } catch {
    notifyError('Gagal', 'Gagal memuat data master')
  }
}

async function fetchTransportirWilayahVolume() {
  const [t, w, v] = await Promise.all([
    axios.get('/api/transportirs'),
    axios.get('/api/wilayah-angkuts'),
    axios.get('/api/volumes'),
  ])
  transportirs.value = t.data.data || t.data
  wilayahs.value = w.data.data || w.data
  volumes.value = v.data.data || v.data
}

async function fetchPenawaran() {
  try {
    const { data } = await axios.get(`/api/penawarans/${idParam}`)
    disposisiPenawaran.value = data.disposisi_penawaran != null ? Number(data.disposisi_penawaran) : null

    Object.assign(form, {
      id_customer: data.id_customer,
      id_cabang: data.id_cabang,
      nomor_penawaran: data.nomor_penawaran,
      masa_berlaku: data.masa_berlaku,
      sampai_dengan: data.sampai_dengan,
      tipe_pembayaran: data.tipe_pembayaran || '',
      order_method: data.order_method || '',
      dp_persen: formatInt(data.dp_persen),
      dp_keterangan: data.dp_keterangan || '',
      repayment_persen: formatInt(data.repayment_persen),
      repayment_hari: formatInt(data.repayment_hari),
      toleransi_penyusutan: data.toleransi_penyusutan ? String(Number(data.toleransi_penyusutan)) : '',
      refund: data.refund != null ? Number(data.refund) : 0,
      other_cost: data.other_cost != null ? Number(data.other_cost) : 0,
      lokasi_pengiriman: data.lokasi_pengiriman || '',
      type_pengiriman: data.type_pengiriman || '',
      metode: data.metode || '',
      perhitungan: data.perhitungan || '',
      keterangan: data.keterangan || '',
      catatan: data.catatan || '',
      syarat_ketentuan: data.syarat_ketentuan || '',
      pengiriman_via: data.pengiriman_via || 'truck+kapal',
      kepada: data.kepada || '',
      nama: data.nama || '',
      jabatan: data.jabatan || '',
      telepon: data.telepon || '',
      alamat: data.alamat || '',
      abrasi: data.abrasi || '',
      harga_dasar: data.harga_dasar != null ? Number(data.harga_dasar) : 0,
    })

    const ongkosList = Array.isArray(data.ongkos) ? data.ongkos : []
    const kapal = ongkosList.find((o: any) => (o.jenis || '').toUpperCase() === 'KAPAL')
    const truck = ongkosList.find((o: any) => (o.jenis || '').toUpperCase() === 'TRUCK')

    if (kapal) {
      oaKapalInput.id_transportir = String(kapal.transportir_id ?? '')
      oaKapalInput.id_angkut_wilayah = String(kapal.wilayah_id ?? '')
      oaKapalInput.id_volume = String(kapal.volume_id ?? '')
      oaKapal.value = Number(kapal.ongkos ?? 0)
    }
    if (truck) {
      oaTruckInput.id_transportir = String(truck.transportir_id ?? '')
      oaTruckInput.id_angkut_wilayah = String(truck.wilayah_id ?? '')
      oaTruckInput.id_volume = String(truck.volume_id ?? '')
      oaTruck.value = Number(truck.ongkos ?? 0)
    }
    oaSelectKey.value++

    form.oat = Number(data.oat) || 0
    form.items = data.items.map((it: any) => ({
      id_produk: it.id_produk,
      volume_order: it.volume_order?.toLocaleString('id-ID') || '',
      harga_tebus: it.harga_tebus?.toLocaleString('id-ID') || '',
      persen: it.persen != null ? String(it.persen) : '',
    }))
  } catch {
    notifyError('Gagal', 'Gagal memuat data penawaran')
  }
}

/* Item actions */
function addItem() {
  form.items.push({ id_produk: '', volume_order: '', harga_tebus: '', persen: '' })
}

function removeItem(idx: number) {
  form.items.splice(idx, 1)
}

async function checkHarga(item: ItemLine) {
  if (!item.id_produk || !form.masa_berlaku) return
  try {
    const { data } = await axios.get('/api/produk-hargas/check', {
      params: { produk_id: item.id_produk, tanggal: form.masa_berlaku },
    })
    if (data.found) {
      item.harga_price_list = data.harga_price_list
      if (item.persen) updateHargaTebus(item)
      success('Harga ditemukan', '')
    } else {
      item.harga_price_list = 0
      item.harga_tebus = ''
      notifyError('Harga tidak tersedia', 'Harga belum diinput untuk tanggal tersebut.')
    }
  } catch {
    notifyError('Gagal', 'Gagal cek harga produk')
  }
}

function updateHargaTebus(item: ItemLine) {
  const persen = parseFloat(item.persen.replace(',', '.')) || 0
  const harga = item.harga_price_list || 0
  const hasil = harga * persen / 100
  item.harga_tebus = isNaN(hasil) ? '' : hasil.toLocaleString('id-ID')
  const dasar = parseFloat((form.ukuran_dasar || '0').toString().replace(',', '.')) || 0
  const volume = Math.round(dasar * persen / 100)
  item.volume_order = isNaN(volume) ? '' : volume.toLocaleString('id-ID')
}

/* Validation */
function clearErrors() {
  for (const k of Object.keys(errors)) delete errors[k]
  invalidProdukIdx.value.clear()
  invalidPersenIdx.value.clear()
}

function inputClass(field: string) {
  return errors[field] ? 'border-red-500 ring-1 ring-red-500' : ''
}

function itemInputClass(idx: number, field: 'id_produk' | 'persen') {
  const set = field === 'id_produk' ? invalidProdukIdx.value : invalidPersenIdx.value
  return set.has(idx) ? 'border-red-500 ring-1 ring-red-500' : ''
}

function validateForm(): boolean {
  clearErrors()
  const msgs: string[] = []

  if (!form.id_customer) { errors['id_customer'] = true; msgs.push('Customer wajib diisi.') }
  if (!form.id_cabang)   { errors['id_cabang']   = true; msgs.push('Cabang wajib diisi.') }
  if (!form.type_pengiriman) { errors['type_pengiriman'] = true; msgs.push('Type Pengiriman wajib dipilih.') }
  if (!form.masa_berlaku)  { errors['masa_berlaku']  = true; msgs.push('Masa berlaku wajib diisi.') }
  if (!form.sampai_dengan) { errors['sampai_dengan'] = true; msgs.push('Sampai dengan wajib diisi.') }

  if (form.masa_berlaku && form.sampai_dengan) {
    const mb = new Date(form.masa_berlaku).getTime()
    const sd = new Date(form.sampai_dengan).getTime()
    if (!isNaN(mb) && !isNaN(sd) && sd < mb) {
      errors['masa_berlaku'] = true
      errors['sampai_dengan'] = true
      msgs.push('Tanggal "Sampai Dengan" tidak boleh lebih awal dari "Masa Berlaku".')
    }
  }

  if (!form.metode)          { errors['metode']          = true; msgs.push('Metode wajib dipilih.') }
  if (!form.tipe_pembayaran) { errors['tipe_pembayaran'] = true; msgs.push('Tipe pembayaran wajib dipilih.') }

  if (form.tipe_pembayaran === 'CUSTOM' && (!form.dp_persen || !form.repayment_persen)) {
    msgs.push('Persentase DP dan Repayment wajib diisi untuk tipe Custom.')
  }

  if (!form.items.length) {
    msgs.push('Minimal 1 item produk.')
  } else {
    let sumPersen = 0
    form.items.forEach((it, idx) => {
      const persen = toFloat(it.persen || '0')
      if (!it.id_produk) invalidProdukIdx.value.add(idx)
      if (persen <= 0)   invalidPersenIdx.value.add(idx)
      sumPersen += persen
    })
    if (invalidProdukIdx.value.size > 0) msgs.push('Semua baris harus memilih Produk.')
    if (invalidPersenIdx.value.size > 0) msgs.push('Persen per baris harus diisi (> 0).')
    const rounded = Math.round(sumPersen * 100) / 100
    if (rounded !== 100) {
      msgs.push(`Total Persen harus 100%. Saat ini: ${rounded}%`)
      form.items.forEach((_, idx) => invalidPersenIdx.value.add(idx))
    }
  }

  const oatNum = Number(oatPerVolumeManual.value || 0)
  if (form.metode && form.metode !== 'FOB' && oatNum <= 0) {
    errors['oat'] = true
    msgs.push('OAT per volume wajib terisi (> 0) untuk metode selain FOB.')
  }

  if (msgs.length) {
    Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal',
      html: `<div style="text-align:left"><ul style="margin:0;padding-left:18px">${msgs.map(m => `<li>${m}</li>`).join('')}</ul></div>`,
    })
    return false
  }
  return true
}

/* Submit */
async function submitForm() {
  if (!validateForm()) return
  loading.value = true
  try {
    const payloadItems = form.items.map((it) => ({
      id_produk: it.id_produk,
      persen: parseFloat((it.persen || '0').replace(',', '.')) || 0,
      volume_order: parseInt((it.volume_order || '').replace(/\./g, ''), 10) || 0,
      harga_tebus: parseInt((it.harga_tebus || '').replace(/\./g, ''), 10) || 0,
      jumlah_harga: lineTotal(it),
    }))

    const payloadOngkos: any[] = []
    if ((form.metode === 'CIF' || form.metode === 'DAP') && oaKapal.value > 0) {
      payloadOngkos.push({
        jenis: 'KAPAL',
        id_transportir: oaKapalInput.id_transportir,
        id_angkut_wilayah: oaKapalInput.id_angkut_wilayah,
        id_volume: parseInt(oaKapalInput.id_volume || '0', 10),
        ongkos: oaKapal.value,
      })
    }
    if ((form.metode === 'DAP' || form.metode === 'FOT') && oaTruck.value > 0) {
      payloadOngkos.push({
        jenis: 'TRUCK',
        id_transportir: oaTruckInput.id_transportir,
        id_angkut_wilayah: oaTruckInput.id_angkut_wilayah,
        id_volume: parseInt(oaTruckInput.id_volume || '0', 10),
        ongkos: oaTruck.value,
      })
    }

    const payload = {
      id_customer: form.id_customer,
      id_cabang: form.id_cabang,
      masa_berlaku: form.masa_berlaku,
      sampai_dengan: form.sampai_dengan,
      ongkos: payloadOngkos,
      items: payloadItems,
      tipe_pembayaran: form.tipe_pembayaran,
      order_method: form.order_method,
      dp_persen: parseInt((form.dp_persen || '0').replace(/\./g, ''), 10) || 0,
      dp_keterangan: form.dp_keterangan,
      repayment_persen: parseInt((form.repayment_persen || '0').replace(/\./g, ''), 10) || 0,
      repayment_hari: parseInt((form.repayment_hari || '0').replace(/\./g, ''), 10) || 0,
      toleransi_penyusutan: parseInt((form.toleransi_penyusutan || '0').replace(/\./g, ''), 10) || 0,
      lokasi_pengiriman: form.lokasi_pengiriman,
      type_pengiriman: form.type_pengiriman,
      metode: form.metode,
      refund: form.refund,
      other_cost: form.other_cost,
      perhitungan: form.perhitungan,
      keterangan: form.keterangan,
      catatan: form.catatan,
      syarat_ketentuan: form.syarat_ketentuan,
      kepada: form.kepada,
      nama: form.nama,
      jabatan: form.jabatan,
      telepon: form.telepon,
      alamat: form.alamat,
      abrasi: form.abrasi,
      subtotal: subtotal.value,
      ppn11: ppn11.value,
      total: grandTotalHargaTebusSetelahDiskon.value + ppn11.value,
      total_with_oat: grandTotalWithOAT.value,
      discount: form.discount,
      harga_tebus_setelah_diskon: grandTotalHargaTebusSetelahDiskon.value,
      harga_dasar: form.harga_dasar,
      ppn_harga_dasar: ppnHargaDasar.value,
      grand_total_harga_dasar: grandTotalHargaDasar.value,
      oat: form.oat,
      pengiriman_via: form.pengiriman_via,
      jenis_penawaran: form.jenis_penawaran,
    }

    if (isEdit) {
      await axios.put(`/api/penawarans/${idParam}`, payload)
      success('Berhasil', 'Penawaran berhasil diupdate.')
    } else {
      await axios.post('/api/penawarans', payload)
      success('Berhasil', 'Penawaran berhasil dibuat.')
    }
    goBack()
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data.errors) {
      const msgs = Object.values(e.response.data.errors).flat().join('<br/>')
      Swal.fire({ icon: 'error', title: 'Validasi Backend Gagal', html: msgs })
    } else {
      notifyError('Gagal', e.response?.data?.message || 'Gagal menyimpan penawaran.')
    }
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({ name: 'penawarans-list' })
}

/* Helpers */
function lineTotal(item: ItemLine): number {
  const v = parseInt((item.volume_order || '').replace(/\./g, ''), 10) || 0
  const h = parseInt((item.harga_tebus || '').replace(/\./g, ''), 10) || 0
  return v * h
}

function toFloat(v: string | number): number {
  if (typeof v === 'number') return v
  const s = (v || '').toString().replace(/\./g, '').replace(',', '.')
  const n = parseFloat(s)
  return isNaN(n) ? 0 : n
}

function toNum(v: string | number): number {
  if (typeof v === 'number') return v
  const s = (v || '').toString().replace(/\./g, '').replace(',', '.')
  const n = parseFloat(s)
  return Number.isFinite(n) ? n : 0
}

function formatNumeric(obj: any, field: any, e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '')
  const num = parseInt(raw, 10)
  obj[field] = isNaN(num) ? '' : num.toLocaleString('id-ID')
}

function formatDecimalInput(obj: any, field: string, e: Event) {
  let val = (e.target as HTMLInputElement).value.replace(/[^0-9.]/g, '')
  const parts = val.split('.')
  if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('')
  obj[field] = val
}

function formatInt(v: number | string | null | undefined): string {
  const n = typeof v === 'string' ? parseInt(v.replace(/\D/g, ''), 10) : v ?? 0
  return Number.isFinite(n) ? (n as number).toLocaleString('id-ID') : ''
}

function formatCurrency(v: number | string = 0) {
  const n = typeof v === 'string' ? parseFloat(v) : v
  return !isNaN(n) ? `Rp. ${n.toLocaleString('id-ID')}` : '-'
}
</script>

<template>
  <FormPage
    :title="isEdit ? 'Edit Penawaran' : 'Tambah Penawaran'"
    :description="isEdit ? 'Perbarui data penawaran ke customer.' : 'Lengkapi data penawaran baru ke customer.'"
    size="full"
    layout="sidebar"
    surface="plain"
    footer-placement="sidebar"
    :loading="loading"
    :submit-text="isEdit ? 'Update Penawaran' : 'Simpan Penawaran'"
    submit-icon="Save"
    cancel-icon="ArrowLeft"
    @cancel="goBack"
    @submit="submitForm"
  >
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
        <Lucide icon="ArrowLeft" class="h-4 w-4" />
        Kembali
      </Button>
    </template>

    <template v-if="isEdit" #header>
      <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
        <span class="text-sm leading-5">
          <b>Info:</b> Mengubah penawaran akan mengembalikan posisi disposisi ke
          <b>Draft</b> dan proses approval akan dimulai dari awal.
        </span>
      </div>
    </template>

    <!-- Section 1: Informasi Penawaran -->
    <CardSection title="Informasi Penawaran" description="Customer dan cabang yang dituju" icon="FileText">
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
          <FormLabel>Customer <RequiredAsterisk /></FormLabel>
          <FormSelect v-model="form.id_customer" class="w-full" :class="inputClass('id_customer')">
            <option value="" disabled>Pilih Customer…</option>
            <option v-for="c in customers" :key="c.id_customer" :value="c.id_customer">
              {{ c.nama_perusahaan }}
            </option>
          </FormSelect>
        </div>

        <div class="col-span-12 md:col-span-6">
          <FormLabel>Cabang <RequiredAsterisk /></FormLabel>
          <FormSelect v-model="form.id_cabang" class="w-full" :class="inputClass('id_cabang')">
            <option value="" disabled>Pilih Cabang…</option>
            <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
              {{ c.nama_cabang }}
            </option>
          </FormSelect>
        </div>
      </div>
    </CardSection>

    <!-- Section 2: Kontak Tujuan -->
    <CardSection
      title="Kontak Tujuan"
      description="Informasi penerima surat penawaran"
      icon="UserRound"
      icon-class="bg-sky-100 text-sky-600"
    >
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
          <FormLabel>Kepada (Perusahaan / Dept.)</FormLabel>
          <FormInput v-model="form.kepada" type="text" placeholder="PT Contoh / Purchasing" />
        </div>

        <div class="col-span-12 md:col-span-6">
          <FormLabel>Nama (UP.)</FormLabel>
          <FormInput v-model="form.nama" type="text" placeholder="Nama PIC (UP.)" />
        </div>

        <div class="col-span-12 md:col-span-4">
          <FormLabel>Jabatan</FormLabel>
          <FormInput v-model="form.jabatan" type="text" placeholder="Purchasing / Manager" />
        </div>

        <div class="col-span-12 md:col-span-4">
          <FormLabel>Telepon</FormLabel>
          <FormInput v-model="form.telepon" type="text" placeholder="0812xxxx / 021-xxxx" />
        </div>

        <div class="col-span-12">
          <FormLabel>Alamat</FormLabel>
          <FormTextarea v-model="form.alamat" :rows="2" placeholder="Alamat surat / pengiriman" />
        </div>
      </div>
    </CardSection>

    <!-- Section 3: Periode & Pengiriman -->
    <CardSection
      title="Periode & Pengiriman"
      description="Masa berlaku dan metode pengiriman"
      icon="CalendarClock"
      icon-class="bg-violet-100 text-violet-600"
    >
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
          <DateField
            v-model="form.masa_berlaku"
            label="Masa Berlaku"
            :required="true"
            :error="errors['masa_berlaku'] ? 'Wajib diisi' : ''"
          />
        </div>

        <div class="col-span-12 md:col-span-6">
          <DateField
            v-model="form.sampai_dengan"
            label="Sampai Dengan"
            :required="true"
            :error="errors['sampai_dengan'] ? 'Wajib diisi' : ''"
          />
        </div>

        <div class="col-span-12 md:col-span-6">
          <FormLabel>Type Pengiriman <RequiredAsterisk /></FormLabel>
          <FormSelect v-model="form.type_pengiriman" class="w-full" :class="inputClass('type_pengiriman')">
            <option value="" disabled>Pilih Type Pengiriman…</option>
            <option value="PROJECT">Project</option>
            <option value="RETAIL">Retail</option>
          </FormSelect>
        </div>

        <div class="col-span-12 md:col-span-6">
          <FormLabel>Metode <RequiredAsterisk /></FormLabel>
          <FormSelect v-model="form.metode" class="w-full" :class="inputClass('metode')">
            <option value="" disabled>Pilih Metode…</option>
            <template v-if="form.type_pengiriman === 'PROJECT'">
              <option value="FOB">Free On Board (FOB)</option>
              <option value="CIF">Cost Insurance & Freight (CIF)</option>
              <option value="DAP">Delivery At Place (DAP)</option>
            </template>
            <template v-else-if="form.type_pengiriman === 'RETAIL'">
              <option value="FOT">Free On Truck (FOT)</option>
              <option value="FRANCO">Franco</option>
            </template>
          </FormSelect>
        </div>
      </div>

      <!-- OA Kapal (conditional) -->
      <div v-if="form.metode === 'CIF' || form.metode === 'DAP'"
        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
      >
        <h4 class="mb-3 text-sm font-semibold text-slate-700">Ongkos Kapal</h4>
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-4">
            <FormLabel>Transportir</FormLabel>
            <FormSelect v-model="oaKapalInput.id_transportir" :key="oaSelectKey">
              <option value="">Pilih Transportir</option>
              <option v-for="t in transportirs" :key="t.id" :value="String(t.id)">{{ t.nama_perusahaan }}</option>
            </FormSelect>
          </div>

          <div class="col-span-12 md:col-span-4">
            <FormLabel>Wilayah Angkut</FormLabel>
            <FormSelect v-model="oaKapalInput.id_angkut_wilayah" :key="oaSelectKey">
              <option value="">Pilih Wilayah</option>
              <option v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                {{ w.provinsi?.nama_provinsi }} - {{ w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
              </option>
            </FormSelect>
          </div>

          <div class="col-span-12 md:col-span-4">
            <FormLabel>Volume</FormLabel>
            <FormSelect v-model="oaKapalInput.id_volume" :key="oaSelectKey">
              <option value="">Pilih Volume</option>
              <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}</option>
            </FormSelect>
          </div>

          <div class="col-span-12">
            <CurrencyField label="Ongkos Kapal" :model-value="oaKapal" :readonly="true" />
          </div>
        </div>
      </div>

      <!-- OA Truck (conditional) -->
      <div v-if="form.metode === 'DAP' || form.metode === 'FOT'"
        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
      >
        <h4 class="mb-3 text-sm font-semibold text-slate-700">Ongkos Truck</h4>
        <div class="grid grid-cols-12 gap-4">
          <div class="col-span-12 md:col-span-4">
            <FormLabel>Transportir</FormLabel>
            <FormSelect v-model="oaTruckInput.id_transportir" :key="oaSelectKey">
              <option value="">Pilih Transportir</option>
              <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }}</option>
            </FormSelect>
          </div>

          <div class="col-span-12 md:col-span-4">
            <FormLabel>Wilayah Angkut</FormLabel>
            <FormSelect v-model="oaTruckInput.id_angkut_wilayah" :key="oaSelectKey">
              <option value="">Pilih Wilayah</option>
              <option v-for="w in wilayahs" :key="w.id" :value="w.id">
                {{ w.provinsi?.nama_provinsi }} - {{ w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
              </option>
            </FormSelect>
          </div>

          <div class="col-span-12 md:col-span-4">
            <FormLabel>Volume</FormLabel>
            <FormSelect v-model="oaTruckInput.id_volume" :key="oaSelectKey">
              <option value="">Pilih Volume</option>
              <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}</option>
            </FormSelect>
          </div>

          <div class="col-span-12">
            <CurrencyField label="Ongkos Truck" :model-value="oaTruck" :readonly="true" />
          </div>
        </div>
      </div>
    </CardSection>

    <!-- Section 4: Rincian Item -->
    <CardSection
      title="Rincian Item"
      description="Produk, volume, dan persentase order"
      icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600"
    >
      <template #action>
        <Button type="button" variant="outline-primary" class="inline-flex items-center gap-2" @click="addItem">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah Baris
        </Button>
      </template>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[760px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">No</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Produk</th>
              <th class="w-28 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Persen (%)</th>
              <th class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Volume</th>
              <th class="w-44 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Harga Price List</th>
              <th v-if="canSeeHarga" class="w-40 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Harga Tebus</th>
              <th v-if="canSeeHarga" class="w-40 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Jumlah Harga</th>
              <th class="w-16 px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(item, idx) in form.items" :key="idx" class="transition hover:bg-slate-50">
              <td class="px-4 py-3 text-center text-sm font-medium text-slate-700">{{ idx + 1 }}.</td>

              <td class="px-4 py-3">
                <FormSelect
                  v-model="item.id_produk"
                  class="min-w-52"
                  :class="itemInputClass(idx, 'id_produk')"
                  @change="checkHarga(item)"
                >
                  <option value="" disabled>Pilih Produk…</option>
                  <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
                    {{ p.nama_produk }} — {{ p.jenis?.nama || '-' }} / {{ p.ukuran?.nama_ukuran || '-' }} {{ p.ukuran?.satuan?.nama_satuan || '' }}
                  </option>
                </FormSelect>
              </td>

              <td class="px-4 py-3">
                <FormInput
                  v-model="item.persen"
                  type="text"
                  inputmode="numeric"
                  placeholder="100"
                  class="text-right"
                  :class="itemInputClass(idx, 'persen')"
                  @input="updateHargaTebus(item)"
                />
              </td>

              <td class="px-4 py-3">
                <FormInput
                  v-model="item.volume_order"
                  type="text"
                  inputmode="numeric"
                  placeholder="0"
                  class="text-right"
                  @input="formatNumeric(item, 'volume_order', $event)"
                />
              </td>

              <td class="px-4 py-3 text-right text-sm text-slate-700">
                {{ formatCurrency(item.harga_price_list || 0) }}
              </td>

              <td v-if="canSeeHarga" class="px-4 py-3">
                <CurrencyField :model-value="toNum(item.harga_tebus)" :readonly="true" />
              </td>

              <td v-if="canSeeHarga" class="px-4 py-3 text-right text-sm text-slate-700">
                {{ formatCurrency(lineTotal(item)) }}
              </td>

              <td class="px-4 py-3 text-center">
                <Button
                  v-if="form.items.length > 1"
                  type="button"
                  variant="soft-danger"
                  rounded
                  class="!h-9 !w-9 !p-0 !shadow-none"
                  title="Hapus"
                  @click="removeItem(idx)"
                >
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </td>
            </tr>
          </tbody>

          <tfoot class="border-t border-slate-200 bg-slate-50">
            <tr>
              <td class="px-4 py-3 text-right text-sm font-medium text-slate-600" colspan="2">Total</td>
              <td
                class="px-4 py-3 text-right text-sm font-semibold"
                :class="totalPersenNumber !== 100 ? 'text-red-600' : 'text-slate-800'"
              >
                {{ totalPersenDisplay }}%
              </td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">{{ totalVolume }}</td>
              <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">{{ formatCurrency(avgHargaPriceList) }}</td>
              <td v-if="canSeeHarga" colspan="2" class="px-4 py-3"></td>
              <td class="px-4 py-3"></td>
            </tr>

            <template v-if="canSeeHarga">
              <tr class="bg-slate-100">
                <td colspan="5" class="px-4 py-2 text-right text-sm font-medium text-slate-600">Subtotal Harga Tebus</td>
                <td class="px-4 py-2 text-right text-sm font-semibold text-slate-800">{{ formatCurrency(grandTotalHargaTebus) }}</td>
                <td></td>
                <td></td>
              </tr>
              <!-- TODO: discount input (form.discount) — tersembunyi, akan diimplementasi di task terpisah -->
              <tr v-if="totalDiskon > 0" class="bg-yellow-50">
                <td colspan="5" class="px-4 py-2 text-right text-sm font-medium text-yellow-700">Diskon</td>
                <td class="px-4 py-2 text-right text-sm font-semibold text-yellow-800">-{{ formatCurrency(totalDiskon) }}</td>
                <td></td>
                <td></td>
              </tr>
              <tr class="bg-emerald-50">
                <td colspan="5" class="px-4 py-2 text-right text-sm font-semibold text-emerald-700">Setelah Diskon</td>
                <td class="px-4 py-2 text-right text-sm font-bold text-emerald-800">{{ formatCurrency(grandTotalHargaTebusSetelahDiskon) }}</td>
                <td></td>
                <td></td>
              </tr>
            </template>
          </tfoot>
        </table>
      </div>
    </CardSection>

    <!-- Section 5: Syarat & Pembayaran -->
    <CardSection
      title="Syarat & Pembayaran"
      description="Metode pembayaran dan syarat pengiriman"
      icon="CreditCard"
      icon-class="bg-amber-100 text-amber-600"
    >
      <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
          <FormLabel>Tipe Pembayaran <RequiredAsterisk /></FormLabel>
          <FormSelect v-model="form.tipe_pembayaran" class="w-full" :class="inputClass('tipe_pembayaran')">
            <option value="" disabled>Pilih…</option>
            <option value="COD">COD</option>
            <option value="CBD">CBD</option>
            <option value="TOP 7">TOP 7</option>
            <option value="TOP 14">TOP 14</option>
            <option value="TOP 30">TOP 30</option>
            <option value="CUSTOM">Custom</option>
          </FormSelect>
        </div>

        <div class="col-span-12 md:col-span-6">
          <FormLabel>Order Method</FormLabel>
          <FormInput v-model="form.order_method" type="text" placeholder="Order Method…" />
        </div>
      </div>

      <!-- Panel CUSTOM -->
      <transition name="fade">
        <div v-if="form.tipe_pembayaran === 'CUSTOM'"
          class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4"
        >
          <h4 class="mb-3 text-sm font-semibold text-slate-700">Detail Pembayaran Custom</h4>
          <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-6">
              <FormLabel>Down Payment (%)</FormLabel>
              <div class="flex flex-wrap items-center gap-2">
                <FormInput
                  v-model="form.dp_persen"
                  type="text"
                  inputmode="numeric"
                  placeholder="20"
                  class="w-20 text-right"
                  @input="formatNumeric(form, 'dp_persen', $event)"
                />
                <span class="text-sm text-slate-600">% After</span>
                <FormInput
                  v-model="form.dp_keterangan"
                  type="text"
                  class="min-w-40 flex-1"
                  placeholder="Purchase Order / 7 days"
                />
              </div>
            </div>

            <div class="col-span-12 md:col-span-6">
              <FormLabel>Repayment (%)</FormLabel>
              <div class="flex flex-wrap items-center gap-2">
                <FormInput
                  v-model="form.repayment_persen"
                  type="text"
                  inputmode="numeric"
                  placeholder="80"
                  class="w-20 text-right"
                  @input="formatNumeric(form, 'repayment_persen', $event)"
                />
                <span class="text-sm text-slate-600">% TOP</span>
                <FormInput
                  v-model="form.repayment_hari"
                  type="text"
                  inputmode="numeric"
                  placeholder="7"
                  class="w-20 text-right"
                  @input="formatNumeric(form, 'repayment_hari', $event)"
                />
                <span class="text-sm text-slate-600">days</span>
              </div>
            </div>
          </div>
          <p class="mt-2 text-xs text-slate-500">Contoh: <b>DP 20% after PO</b>, <b>Repayment 80% TOP 7 days</b>.</p>
        </div>
      </transition>

      <div class="mt-4 grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-4">
          <FormLabel>Toleransi Penyusutan</FormLabel>
          <div class="relative">
            <FormInput
              v-model="form.toleransi_penyusutan"
              type="text"
              inputmode="decimal"
              placeholder="0"
              class="pr-8 text-right"
              @input="formatDecimalInput(form, 'toleransi_penyusutan', $event)"
            />
            <span class="absolute right-3 top-2.5 text-xs text-slate-400">%</span>
          </div>
        </div>

        <div class="col-span-12 md:col-span-4">
          <FormLabel>Abrasi</FormLabel>
          <FormInput v-model="form.abrasi" type="text" placeholder="Contoh: 0-5% atau sesuai kondisi" />
          <small class="mt-1 block text-xs text-slate-500">
            Isi bebas (misal: <b>0–5%</b> atau <b>sesuai kondisi</b>).
          </small>
        </div>

        <div class="col-span-12 md:col-span-4">
          <FormLabel>Lokasi Pengiriman</FormLabel>
          <FormInput v-model="form.lokasi_pengiriman" type="text" placeholder="Lokasi" />
        </div>

        <div class="col-span-12 md:col-span-6">
          <CurrencyField v-model="form.refund" label="Refund" />
        </div>

        <div class="col-span-12 md:col-span-6">
          <CurrencyField v-model="form.other_cost" label="Other Cost" />
        </div>

        <div class="col-span-12">
          <FormLabel>Keterangan</FormLabel>
          <FormInput v-model="form.keterangan" type="text" placeholder="Keterangan…" />
        </div>
      </div>
    </CardSection>

    <!-- Section 6: Perhitungan Harga Dasar -->
    <CardSection
      title="Perhitungan Harga Dasar"
      description="Komponen harga dan total akhir"
      icon="Calculator"
      icon-class="bg-emerald-100 text-emerald-600"
    >
      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">No</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Rincian</th>
              <th class="w-20 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Nilai</th>
              <th class="w-60 px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Harga (Rp)</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr>
              <td class="px-4 py-3 text-center text-slate-500">1</td>
              <td class="px-4 py-3 text-slate-700">Harga Dasar</td>
              <td class="px-4 py-3"></td>
              <td class="px-4 py-3">
                <CurrencyField v-model="form.harga_dasar" />
              </td>
            </tr>

            <tr>
              <td class="px-4 py-3 text-center text-slate-500">2</td>
              <td class="px-4 py-3 text-slate-700">Ongkos Angkut (OAT per Volume)</td>
              <td class="px-4 py-3"></td>
              <td class="px-4 py-3">
                <CurrencyField
                  v-model="form.oat"
                  :error="errors['oat'] ? 'Wajib diisi (> 0) untuk metode selain FOB' : ''"
                />
              </td>
            </tr>

            <tr class="bg-slate-50">
              <td class="px-4 py-3 text-center font-semibold text-slate-600">3</td>
              <td class="px-4 py-3 font-semibold text-slate-700">Subtotal (Harga Dasar + OA)</td>
              <td class="px-4 py-3"></td>
              <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ formatCurrency(dppHargaDasar) }}</td>
            </tr>

            <tr>
              <td class="px-4 py-3 text-center text-slate-500">4</td>
              <td class="px-4 py-3 text-slate-700">PPN</td>
              <td class="px-4 py-3 text-right text-slate-500">11%</td>
              <td class="px-4 py-3 text-right text-slate-800">{{ formatCurrency(ppnHargaDasar) }}</td>
            </tr>

            <tr class="bg-slate-100">
              <td colspan="3" class="px-4 py-3 text-right text-base font-bold text-slate-700">TOTAL</td>
              <td class="px-4 py-3 text-right text-base font-bold text-emerald-700">{{ formatCurrency(grandTotalHargaDasar) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardSection>

    <!-- Sidebar: Catatan & Syarat Ketentuan -->
    <template #sidebar>
      <CardSection
        title="Catatan & Syarat"
        description="Informasi tambahan penawaran"
        icon="StickyNote"
        icon-class="bg-rose-100 text-rose-600"
      >
        <div class="space-y-4">
          <div>
            <FormLabel>Catatan</FormLabel>
            <FormTextarea v-model="form.catatan" :rows="3" placeholder="Catatan tambahan…" />
          </div>

          <div>
            <FormLabel>Syarat & Ketentuan</FormLabel>
            <FormTextarea v-model="form.syarat_ketentuan" :rows="5" placeholder="Syarat dan ketentuan…" />
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
