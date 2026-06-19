<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toRaw } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

import Button from '@/components/Base/Button'
import { FormSelect } from '@/components/Base/Form'
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
  refund: '',
  other_cost: '',
  perhitungan: '',
  keterangan: '',
  catatan: '',
  syarat_ketentuan: '',
  pengiriman_via: 'truck+kapal',
  ukuran_dasar: '',
  discount: '',
  oat: '',
  jenis_penawaran: '1',
  kepada: '',
  nama: '',
  jabatan: '',
  telepon: '',
  alamat: '',
  abrasi: '',
  harga_dasar: '',
})

/* Validation state */
const errors = reactive<Record<string, boolean>>({})
const invalidProdukIdx = ref<Set<number>>(new Set())
const invalidPersenIdx = ref<Set<number>>(new Set())

/* Computed: disposisi badge */
const disposisiLabel = computed(() => {
  const d = Number(disposisiPenawaran.value ?? 0)
  if (d === 1) return '1 • Sedang diajukan ke BM'
  if (d === 2) return '2 • Approved Branch Manager'
  if (d === 3) return '3 • Ditolak Branch Manager'
  if (d === 4) return '4 • Approved OM'
  if (!d) return 'Draft / Belum diajukan'
  return `${d} • Status lainnya`
})

const disposisiBadgeClass = computed(() => {
  const d = Number(disposisiPenawaran.value ?? 0)
  if (d === 1) return 'bg-yellow-50 text-yellow-700 border-yellow-200'
  if (d === 2) return 'bg-green-50 text-green-700 border-green-200'
  if (d === 3) return 'bg-red-50 text-red-700 border-red-200'
  if (d === 4) return 'bg-blue-50 text-blue-700 border-blue-200'
  return 'bg-slate-50 text-slate-700 border-slate-200'
})

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

const totalDiskon = computed(() => {
  const d = parseInt((form.discount || '0').replace(/\./g, ''), 10) || 0
  return Math.min(Math.max(d, 0), subtotal.value)
})
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
  if (!form.discount) form.discount = '0'
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
      refund: data.refund != null ? String(Number(data.refund)) : '',
      other_cost: data.other_cost != null ? String(Number(data.other_cost)) : '',
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
      harga_dasar: data.harga_dasar != null
        ? Number(data.harga_dasar).toLocaleString('id-ID', { maximumFractionDigits: 0 })
        : '',
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

    form.oat = Number(data.oat).toLocaleString('id-ID')
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
      refund: parseInt((form.refund || '0').replace(/\./g, ''), 10) || 0,
      other_cost: parseInt((form.other_cost || '0').replace(/\./g, ''), 10) || 0,
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
      discount: parseInt((form.discount || '0').replace(/\./g, ''), 10) || 0,
      harga_tebus_setelah_diskon: grandTotalHargaTebusSetelahDiskon.value,
      harga_dasar: Number(toNum(form.harga_dasar || 0)),
      ppn_harga_dasar: ppnHargaDasar.value,
      grand_total_harga_dasar: grandTotalHargaDasar.value,
      oat: Number(oatPerVolumeManual.value),
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
  <div class="p-6 intro-y">
    <div class="flex items-center mb-4 gap-3">
      <div class="flex flex-col gap-2">
        <h2 class="text-lg font-medium">
          {{ isEdit ? 'Edit Penawaran' : 'Tambah Penawaran' }}
        </h2>
        <div v-if="isEdit" class="w-full">
          <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800">
            <span class="text-sm leading-5">
              <b>Info:</b> Mengubah penawaran akan mengembalikan posisi disposisi ke
              <b>Draft</b> dan proses approval akan dimulai dari awal.
            </span>
          </div>
        </div>
      </div>
      <Button variant="outline-secondary" class="ml-auto" @click="goBack">Batal</Button>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
      <form @submit.prevent="submitForm" class="space-y-4">

        <!-- Customer & Cabang -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Customer</label>
            <FormSelect v-model="form.id_customer" class="w-full" :class="inputClass('id_customer')">
              <option value="" disabled>Pilih Customer…</option>
              <option v-for="c in customers" :key="c.id_customer" :value="c.id_customer">
                {{ c.nama_perusahaan }}
              </option>
            </FormSelect>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Cabang</label>
            <FormSelect v-model="form.id_cabang" class="w-full" :class="inputClass('id_cabang')">
              <option value="" disabled>Pilih Cabang…</option>
              <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">
                {{ c.nama_cabang }}
              </option>
            </FormSelect>
          </div>
        </div>

        <!-- Kontak Tujuan -->
        <div class="mb-4">
          <h3 class="text-sm font-semibold mb-2">Kontak Tujuan</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Kepada (Perusahaan / Dept.)</label>
              <input v-model="form.kepada" type="text" class="w-full border rounded p-2" placeholder="PT Contoh / Purchasing" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Nama (UP.)</label>
              <input v-model="form.nama" type="text" class="w-full border rounded p-2" placeholder="Nama PIC (UP.)" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Jabatan</label>
              <input v-model="form.jabatan" type="text" class="w-full border rounded p-2" placeholder="Purchasing / Manager" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Telepon</label>
              <input v-model="form.telepon" type="text" class="w-full border rounded p-2" placeholder="0812xxxx / 021-xxxx" />
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1">Alamat</label>
              <textarea v-model="form.alamat" rows="2" class="w-full border rounded p-2" placeholder="Alamat surat / pengiriman"></textarea>
            </div>
          </div>
        </div>

        <!-- Masa Berlaku -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Masa Berlaku</label>
            <input v-model="form.masa_berlaku" type="date" class="w-full border rounded p-2" :class="inputClass('masa_berlaku')" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Sampai Dengan</label>
            <input v-model="form.sampai_dengan" type="date" class="w-full border rounded p-2" :class="inputClass('sampai_dengan')" />
          </div>
        </div>

        <!-- Type Pengiriman -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Type Pengiriman</label>
          <FormSelect v-model="form.type_pengiriman" class="w-full" :class="inputClass('type_pengiriman')">
            <option value="" disabled>Pilih Type Pengiriman…</option>
            <option value="PROJECT">Project</option>
            <option value="RETAIL">Retail</option>
          </FormSelect>
        </div>

        <!-- Metode -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Metode</label>
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

        <!-- OA Kapal -->
        <div v-if="form.metode === 'CIF' || form.metode === 'DAP'" class="bg-slate-100 p-4 rounded mb-4">
          <h4 class="text-sm font-semibold mb-2">Ongkos Kapal</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="text-sm mb-1 block">Transportir</label>
              <FormSelect v-model="oaKapalInput.id_transportir" :key="oaSelectKey">
                <option value="">Pilih Transportir</option>
                <option v-for="t in transportirs" :key="t.id" :value="String(t.id)">{{ t.nama_perusahaan }}</option>
              </FormSelect>
            </div>
            <div>
              <label class="text-sm mb-1 block">Wilayah Angkut</label>
              <FormSelect v-model="oaKapalInput.id_angkut_wilayah" :key="oaSelectKey">
                <option value="">Pilih Wilayah</option>
                <option v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                  {{ w.provinsi?.nama_provinsi }} - {{ w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
                </option>
              </FormSelect>
            </div>
            <div>
              <label class="text-sm mb-1 block">Volume</label>
              <FormSelect v-model="oaKapalInput.id_volume" :key="oaSelectKey">
                <option value="">Pilih Volume</option>
                <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}</option>
              </FormSelect>
            </div>
          </div>
          <div class="mt-4">
            <label class="text-sm">Ongkos Kapal</label>
            <input type="text" :value="oaKapal.toLocaleString('id-ID')" class="w-full border rounded p-2 bg-gray-100" readonly />
          </div>
        </div>

        <!-- OA Truck -->
        <div v-if="form.metode === 'DAP' || form.metode === 'FOT'" class="bg-slate-100 p-4 rounded mb-4">
          <h4 class="text-sm font-semibold mb-2">Ongkos Truck</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="text-sm mb-1 block">Transportir</label>
              <FormSelect v-model="oaTruckInput.id_transportir" :key="oaSelectKey">
                <option value="">Pilih Transportir</option>
                <option v-for="t in transportirs" :key="t.id" :value="t.id">{{ t.nama_perusahaan }}</option>
              </FormSelect>
            </div>
            <div>
              <label class="text-sm mb-1 block">Wilayah Angkut</label>
              <FormSelect v-model="oaTruckInput.id_angkut_wilayah" :key="oaSelectKey">
                <option value="">Pilih Wilayah</option>
                <option v-for="w in wilayahs" :key="w.id" :value="w.id">
                  {{ w.provinsi?.nama_provinsi }} - {{ w.kabupaten?.nama_kabupaten }} - {{ w.destinasi }}
                </option>
              </FormSelect>
            </div>
            <div>
              <label class="text-sm mb-1 block">Volume</label>
              <FormSelect v-model="oaTruckInput.id_volume" :key="oaSelectKey">
                <option value="">Pilih Volume</option>
                <option v-for="v in volumes" :key="v.id_volume" :value="String(v.id_volume)">{{ v.volume }}</option>
              </FormSelect>
            </div>
          </div>
          <div class="mt-4">
            <label class="text-sm">Ongkos Truck</label>
            <input type="text" :value="oaTruck.toLocaleString('id-ID')" class="w-full border rounded p-2 bg-gray-100" readonly />
          </div>
        </div>

        <!-- Rincian Item -->
        <div>
          <h3 class="text-sm font-medium mb-2">Rincian Item</h3>
          <table class="min-w-full divide-y divide-slate-200 mb-4">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
              <tr>
                <th class="px-4 py-2 text-left">Produk</th>
                <th class="px-2 py-2 text-right">Persen (%)</th>
                <th class="px-4 py-2 text-right">Volume</th>
                <th class="px-4 py-2 text-right">Harga Price List</th>
                <th v-if="canSeeHarga" class="px-4 py-2 text-right">Harga Tebus</th>
                <th v-if="canSeeHarga" class="px-4 py-2 text-right">Jumlah Harga</th>
                <th class="px-4 py-2 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-slate-50">
                <td class="px-4 py-2">
                  <FormSelect v-model="item.id_produk" class="w-full" :class="itemInputClass(idx, 'id_produk')" @change="checkHarga(item)">
                    <option value="" disabled>Pilih Produk…</option>
                    <option v-for="p in produks" :key="p.id_produk" :value="p.id_produk">
                      {{ p.nama_produk }} — {{ p.jenis?.nama || '-' }} / {{ p.ukuran?.nama_ukuran || '-' }} {{ p.ukuran?.satuan?.nama_satuan || '' }}
                    </option>
                  </FormSelect>
                </td>
                <td class="px-2 py-2 text-right">
                  <input v-model="item.persen" @input="updateHargaTebus(item)" type="text" inputmode="numeric"
                    class="w-full border rounded p-2 text-right" :class="itemInputClass(idx, 'persen')" placeholder="100" />
                </td>
                <td class="px-4 py-2 text-right">
                  <input v-model="item.volume_order" @input="formatNumeric(item, 'volume_order', $event)" type="text" inputmode="numeric"
                    class="w-full border rounded p-2 text-right" placeholder="0" />
                </td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.harga_price_list || 0) }}</td>
                <td v-if="canSeeHarga" class="px-4 py-2 text-right">
                  <input v-model="item.harga_tebus" @input="formatNumeric(item, 'harga_tebus', $event)" type="text" inputmode="numeric"
                    class="w-full border rounded p-2 text-right bg-gray-50" placeholder="0" readonly />
                </td>
                <td v-if="canSeeHarga" class="px-4 py-2 text-right">{{ formatCurrency(lineTotal(item)) }}</td>
                <td class="px-4 py-2 text-center">
                  <button type="button" class="text-red-500 hover:underline" @click="removeItem(idx)">Hapus</button>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-slate-100 font-semibold">
                <td class="px-4 py-2 text-right">Total</td>
                <td class="px-2 py-2 text-right" :class="totalPersenNumber !== 100 ? 'text-red-600 font-bold' : ''">
                  {{ totalPersenDisplay }}
                </td>
                <td class="px-4 py-2 text-right">{{ totalVolume }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(avgHargaPriceList || 0) }}</td>
                <td v-if="canSeeHarga" class="px-4 py-2"></td>
                <td v-if="canSeeHarga" class="px-4 py-2"></td>
                <td class="px-4 py-2"></td>
              </tr>
            </tfoot>
          </table>

          <div v-if="canSeeHarga" class="flex justify-end mt-4 text-sm font-semibold flex-col items-end space-y-1">
            <div class="bg-gray-100 rounded px-4 py-2 w-fit">Total Harga Tebus: {{ formatCurrency(grandTotalHargaTebus) }}</div>
            <div v-if="totalDiskon > 0" class="bg-yellow-100 rounded px-4 py-2 w-fit">Diskon: -{{ formatCurrency(totalDiskon) }}</div>
            <div class="bg-green-100 rounded px-4 py-2 w-fit font-bold">Setelah Diskon: {{ formatCurrency(grandTotalHargaTebusSetelahDiskon) }}</div>
          </div>
          <div v-else class="flex justify-end mt-2 text-xs text-slate-500">Ringkasan harga disembunyikan.</div>

          <button type="button" class="px-3 py-1 bg-green-600 text-white rounded text-sm mt-3" @click="addItem">
            + Tambah Item
          </button>
        </div>

        <!-- Abrasi -->
        <div>
          <label class="block text-sm font-medium mb-1">Abrasi</label>
          <input v-model="form.abrasi" type="text" class="w-full border rounded p-2" :class="inputClass('abrasi')"
            placeholder="Contoh: 0-5% atau sesuai kondisi" />
          <p class="text-xs text-slate-500 mt-1">Isi bebas (misal: <b>0–5%</b> atau <b>sesuai kondisi</b>).</p>
        </div>

        <!-- Meta -->
        <div class="grid grid-cols-3 gap-4">
          <!-- Tipe Pembayaran -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Tipe Pembayaran</label>
            <FormSelect v-model="form.tipe_pembayaran" class="w-full" :class="inputClass('tipe_pembayaran')">
              <option value="" disabled>Pilih…</option>
              <option value="COD">COD</option>
              <option value="CBD">CBD</option>
              <option value="TOP 7">TOP 7</option>
              <option value="TOP 14">TOP 14</option>
              <option value="TOP 30">TOP 30</option>
              <option value="CUSTOM">Custom</option>
            </FormSelect>
            <transition name="fade">
              <div v-if="form.tipe_pembayaran === 'CUSTOM'"
                class="mt-3 border border-slate-300 rounded-lg bg-slate-50 p-4 space-y-4">
                <h4 class="text-sm font-semibold text-slate-700 mb-2">Detail Pembayaran Custom</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-1">
                    <label class="text-sm font-medium text-slate-700">Down Payment (%)</label>
                    <div class="flex items-center flex-wrap gap-2">
                      <input v-model="form.dp_persen" @input="formatNumeric(form, 'dp_persen', $event)" type="text" inputmode="numeric"
                        class="w-20 border rounded p-2 text-right" placeholder="20" />
                      <span class="text-sm">%</span>
                      <span class="text-sm text-slate-600">After</span>
                      <input v-model="form.dp_keterangan" type="text" class="flex-1 min-w-[160px] border rounded p-2"
                        placeholder="Purchase Order / 7 days" />
                    </div>
                  </div>
                  <div class="space-y-1">
                    <label class="text-sm font-medium text-slate-700">Repayment (%)</label>
                    <div class="flex items-center flex-wrap gap-2">
                      <input v-model="form.repayment_persen" @input="formatNumeric(form, 'repayment_persen', $event)" type="text" inputmode="numeric"
                        class="w-20 border rounded p-2 text-right" placeholder="80" />
                      <span class="text-sm">%</span>
                      <span class="text-sm text-slate-600">TOP</span>
                      <input v-model="form.repayment_hari" @input="formatNumeric(form, 'repayment_hari', $event)" type="text" inputmode="numeric"
                        class="w-20 border rounded p-2 text-right" placeholder="7" />
                      <span class="text-sm text-slate-600">days</span>
                    </div>
                  </div>
                </div>
                <p class="text-xs text-slate-500 mt-2">Contoh: <b>DP 20% after PO</b>, <b>Repayment 80% TOP 7 days</b>.</p>
              </div>
            </transition>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Order Method</label>
            <input v-model="form.order_method" type="text" class="w-full border rounded p-2" placeholder="Order Method…" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Toleransi Penyusutan (%)</label>
            <div class="relative">
              <input v-model="form.toleransi_penyusutan" @input="formatDecimalInput(form, 'toleransi_penyusutan', $event)"
                type="text" inputmode="decimal" class="w-full border rounded p-2 pr-8 text-right" placeholder="0" />
              <span class="absolute right-3 top-2 text-gray-500">%</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Lokasi Pengiriman</label>
            <input v-model="form.lokasi_pengiriman" type="text" class="w-full border rounded p-2" placeholder="Lokasi" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Refund</label>
            <input v-model="form.refund" @input="formatDecimalInput(form, 'refund', $event)" type="text" inputmode="decimal"
              class="w-full border rounded p-2 text-right" placeholder="0" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Other Cost</label>
            <input v-model="form.other_cost" @input="formatDecimalInput(form, 'other_cost', $event)" type="text" inputmode="decimal"
              class="w-full border rounded p-2 text-right" placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <input v-model="form.keterangan" type="text" class="w-full border rounded p-2" placeholder="Keterangan…" />
          </div>
        </div>

        <!-- Tabel Harga Dasar -->
        <div class="bg-white shadow border rounded-lg mt-6">
          <table class="min-w-full text-sm border-collapse border border-slate-300">
            <thead class="bg-slate-100">
              <tr class="text-left font-semibold">
                <th class="px-3 py-2 border border-slate-300 w-12 text-center">No</th>
                <th class="px-3 py-2 border border-slate-300">Rincian</th>
                <th class="px-3 py-2 border border-slate-300 text-right">Nilai</th>
                <th class="px-3 py-2 border border-slate-300 text-right">Harga (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="px-3 py-2 border text-center">1</td>
                <td class="px-3 py-2 border bg-slate-50">Harga Dasar</td>
                <td class="px-3 py-2 border"></td>
                <td class="px-3 py-2 border text-right">
                  <input v-model="form.harga_dasar" @input="formatNumeric(form, 'harga_dasar', $event)" type="text" inputmode="numeric"
                    class="w-full border rounded p-1 text-right" placeholder="0" />
                </td>
              </tr>
              <tr>
                <td class="px-3 py-2 border text-center">2</td>
                <td class="px-3 py-2 border bg-slate-50">Ongkos Angkut (OAT per Volume)</td>
                <td class="px-3 py-2 border"></td>
                <td class="px-3 py-2 border text-right">
                  <input v-model="form.oat" @input="formatNumeric(form, 'oat', $event)" type="text" inputmode="numeric"
                    class="w-full border rounded p-1 text-right" placeholder="0" />
                </td>
              </tr>
              <tr class="font-semibold bg-slate-50">
                <td class="px-3 py-2 border text-center">3</td>
                <td class="px-3 py-2 border">Subtotal (Harga Dasar + OA)</td>
                <td class="px-3 py-2 border"></td>
                <td class="px-3 py-2 border text-right bg-gray-50">{{ formatCurrency(dppHargaDasar) }}</td>
              </tr>
              <tr>
                <td class="px-3 py-2 border text-center">4</td>
                <td class="px-3 py-2 border bg-slate-50">PPN</td>
                <td class="px-3 py-2 border text-right">11%</td>
                <td class="px-3 py-2 border text-right bg-gray-50">{{ formatCurrency(ppnHargaDasar) }}</td>
              </tr>
              <tr class="font-semibold bg-slate-100">
                <td class="px-3 py-2 border text-center" colspan="3">TOTAL</td>
                <td class="px-3 py-2 border text-right">{{ formatCurrency(grandTotalHargaDasar) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Catatan</label>
          <textarea v-model="form.catatan" rows="2" class="w-full border rounded p-2" placeholder="Catatan tambahan…"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Syarat & Ketentuan</label>
          <textarea v-model="form.syarat_ketentuan" rows="3" class="w-full border rounded p-2" placeholder="Syarat dan ketentuan…"></textarea>
        </div>

        <!-- Aksi -->
        <div class="flex justify-end space-x-2 mt-6">
          <button type="button" class="px-4 py-2 rounded border" @click="goBack">Batal</button>
          <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white" :disabled="loading">
            {{ loading ? 'Menyimpan...' : isEdit ? 'Update' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
