<script setup lang="ts">
import { computed, onMounted, reactive, ref, toRefs } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { helpers, integer, minValue, required } from '@vuelidate/validators'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Table from '@/components/Base/Table'
import TippyContent from '@/components/Base/TippyContent'
import { FormInput, FormLabel } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import DateField from '@/components/SystemDesign/Form/DateField.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { formatCurrency, formatDate, formatNumber } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const { success, error: notifyError } = useNotification()

const idPenawaran = String(route.query.id_penawaran ?? '')

const pageLoading = ref(false)
const submitting = ref(false)
const formError = ref('')
const items = ref<any[]>([])
const hargaDasar = ref(0)
const volumePoc = ref<number>(0)

const form = reactive({
  customer_id: '' as number | string,
  customer_nama: '',
  id_penawaran: idPenawaran as number | string,
  nomor_penawaran: '',
  cabang_nama: '',
  masa_berlaku: '',
  sampai_dengan: '',
  oat: 0,
  nomor_po: '',
  top_poc: '',
  tanggal_po: '',
  tanggal_kirim: '',
  lampiran: null as File | null,
  produk_poc: '' as number | string,
})

const penawaranTotalVolume = computed(() =>
  Math.round(items.value.reduce((sum, it) => sum + Number(it.volume_order ?? 0), 0)),
)
const dppPerM3 = computed(() => hargaDasar.value + Number(form.oat ?? 0))
const totalHarga = computed(() => dppPerM3.value * volumePoc.value)

const rules = {
  nomor_po: { required: helpers.withMessage('Nomor PO Customer wajib diisi', required) },
  top_poc: { required: helpers.withMessage('TOP / termin pembayaran wajib diisi', required) },
  tanggal_po: { required: helpers.withMessage('Tanggal PO wajib diisi', required) },
  tanggal_kirim: {
    required: helpers.withMessage('Tanggal pengiriman wajib diisi', required),
    notBeforeTanggalPo: helpers.withMessage(
      'Tanggal pengiriman tidak boleh sebelum tanggal PO',
      (value: string) => !value || !form.tanggal_po || value >= form.tanggal_po,
    ),
  },
  volumePoc: {
    required: helpers.withMessage('Total Volume PO wajib diisi', required),
    integer: helpers.withMessage('Total Volume PO harus berupa angka bulat', integer),
    minValue: helpers.withMessage('Total Volume PO harus lebih dari 0', minValue(1)),
  },
}

const v$ = useVuelidate(rules, { ...toRefs(form), volumePoc })

onMounted(fetchPenawaran)

async function fetchPenawaran() {
  if (!idPenawaran) {
    formError.value = 'Buka form ini lewat tombol Buat PO Customer di halaman Penawaran.'
    return
  }

  pageLoading.value = true

  try {
    const { data } = await axios.get(`/api/penawarans/${idPenawaran}`)

    form.customer_id = data.id_customer
    form.customer_nama = data.customer?.company_name || '-'
    form.id_penawaran = data.id_penawaran
    form.nomor_penawaran = data.nomor_penawaran || '-'
    form.cabang_nama = data.cabang?.nama_cabang || '-'
    form.masa_berlaku = data.masa_berlaku
    form.sampai_dengan = data.sampai_dengan
    form.oat = Number(data.oat ?? 0)
    hargaDasar.value = Number(data.harga_dasar ?? 0)

    items.value = Array.isArray(data.items) ? data.items : []
    form.produk_poc = items.value[0]?.produk?.id_produk || ''
    volumePoc.value = penawaranTotalVolume.value
  } catch {
    notifyError('Gagal', 'Gagal memuat data penawaran')
  } finally {
    pageLoading.value = false
  }
}

async function submit() {
  formError.value = ''

  const valid = await v$.value.$validate()
  if (!valid) return

  submitting.value = true

  const payload = new FormData()
  payload.append('id_customer', String(form.customer_id))
  payload.append('id_penawaran', String(form.id_penawaran))
  payload.append('nomor_poc', form.nomor_po)
  payload.append('top_poc', form.top_poc)
  payload.append('tanggal_poc', form.tanggal_po)
  payload.append('supply_date', form.tanggal_kirim)
  payload.append('volume_poc', String(Math.round(volumePoc.value)))
  payload.append('produk_poc', String(form.produk_poc || ''))

  if (form.lampiran) {
    payload.append('lampiran_poc', form.lampiran)
  }

  try {
    await axios.post('/api/customer-pos', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    success('Berhasil', 'PO Customer berhasil dibuat')
    router.push({ name: 'po-customers-index' })
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errs = e.response?.data?.errors || {}
      formError.value = Object.values(errs).flat().join('\n')
    } else {
      notifyError('Gagal', e.response?.data?.message ?? 'Terjadi kesalahan saat menyimpan PO')
    }
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'penawarans-list' })
}

function fieldError(field: 'nomor_po' | 'top_poc' | 'tanggal_po' | 'tanggal_kirim' | 'volumePoc') {
  return v$.value[field].$errors[0]?.$message?.toString() ?? ''
}

function poVolumeForItem(it: any) {
  return Math.round(volumePoc.value * Number(it.persen ?? 0) / 100)
}
</script>

<template>
  <FormPage title="Buat PO Customer"
    description="Sumber harga dari Penawaran terpilih. Lengkapi detail PO untuk memproses." size="full" layout="sidebar"
    surface="plain" footer-placement="sidebar" :loading="pageLoading || submitting" :error="formError"
    submit-text="Simpan" submit-icon="Save" cancel-icon="ArrowLeft" @submit="submit" @cancel="goBack">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <CardSection title="Sumber Penawaran" description="Data penawaran yang menjadi dasar PO Customer." icon="FileText">
      <div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
        <div>
          <div class="font-label">Nama Customer</div>
          <div class="font-strong">{{ form.customer_nama || '-' }}</div>
        </div>
        <div>
          <div class="font-label">Nomor Penawaran</div>
          <div class="font-strong">{{ form.nomor_penawaran || '-' }}</div>
        </div>
        <div>
          <div class="font-label">Masa Berlaku Harga</div>
          <div class="font-strong">{{ formatDate(form.masa_berlaku) }} – {{ formatDate(form.sampai_dengan) }}</div>
        </div>
        <div>
          <div class="font-label">Cabang</div>
          <div class="font-strong">{{ form.cabang_nama || '-' }}</div>
        </div>
        <!-- <div class="sm:col-span-2">
          <span
            class="inline-flex items-center gap-1 bg-slate-100 px-2.5 py-1 rounded-full font-caption text-slate-600">
            <Lucide icon="BadgeCheck" class="w-3.5 h-3.5" />
            Penawaran Aktif
          </span>
        </div> -->
      </div>
    </CardSection>

    <CardSection title="Rincian Item Penawaran" description="Produk, rasio, dan volume dari penawaran." icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <div class="overflow-x-auto">
        <Table bordered sm class="font-body">
          <Table.Thead class="bg-slate-50">
            <Table.Tr>
              <Table.Th class="px-4 py-3 font-label text-left">Produk</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-left">Ukuran</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Rasio</Table.Th>
              <Table.Th class="px-4 py-3 font-label text-right">Volume</Table.Th>
            </Table.Tr>
          </Table.Thead>

          <Table.Tbody class="bg-white">
            <Table.Tr v-for="(it, i) in items" :key="it.id_penawaran_item || i">
              <Table.Td class="px-4 py-3">
                <div class="font-strong">{{ it.produk?.nama_produk || '-' }}</div>
                <div v-if="it.produk?.jenis?.nama" class="font-caption">{{ it.produk?.jenis?.nama }}</div>
              </Table.Td>
              <Table.Td class="px-4 py-3">
                {{ it.produk?.ukuran?.nama_ukuran || '-' }}
                <span class="font-caption">({{ it.produk?.ukuran?.satuan?.nama_satuan || '-' }})</span>
              </Table.Td>
              <Table.Td class="px-4 py-3 font-num text-right">{{ it.persen != null ? `${Math.round(Number(it.persen ??
                0))}%` : '-' }}</Table.Td>
              <Table.Td class="px-4 py-3 font-num text-right">
                <div>{{ formatNumber(poVolumeForItem(it)) }} m³</div>
                <!-- <div class="font-caption text-slate-500">dari Penawaran: {{ formatNumber(it.volume_order) }} m³</div> -->
              </Table.Td>
            </Table.Tr>

            <Table.Tr v-if="!items.length">
              <Table.Td colspan="4" class="px-4 py-6 font-body text-center">Belum ada item penawaran.</Table.Td>
            </Table.Tr>
          </Table.Tbody>

          <Table.Tbody class="bg-slate-50">
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 font-strong text-right">Total Volume</Table.Td>
              <Table.Td class="px-4 py-2 text-right">
                <NumberField v-model.number="volumePoc" class="ml-auto w-40 max-w-[10rem]" suffix="m³" :decimals="0"
                  :error="fieldError('volumePoc')" />
              </Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 font-strong text-right">Harga per m³</Table.Td>
              <Table.Td class="px-4 py-2 text-right">
                <div class="font-num">
                  <span class="font-num text-xl hover:underline cursor-pointer" :data-tooltip="'po-dpp-breakdown'">{{
                    formatCurrency(dppPerM3) }}</span>
                </div>
                <div class="tooltip-content">
                  <TippyContent :to="'po-dpp-breakdown'" :options="{ placement: 'left', interactive: true }">
                    <div class="w-60 max-w-[85vw]">
                      <div class="flex items-center gap-2 mb-3">
                        <span class="bg-primary rounded-full w-2 h-2 shrink-0"></span>
                        <span class="font-label text-slate-500 tracking-wide">Komposisi Harga per m³</span>
                      </div>
                      <div class="flex justify-between items-center gap-4">
                        <span class="font-caption text-slate-500">Harga Dasar</span>
                        <span class="font-strong">{{ formatCurrency(hargaDasar) }}</span>
                      </div>
                      <div class="flex justify-between items-center gap-4">
                        <span class="font-caption text-slate-500">Ongkos Angkut</span>
                        <span class="font-strong">{{ formatCurrency(form.oat) }}</span>
                      </div>
                      <div class="flex justify-between items-center gap-4 mt-2 pt-1 border-slate-100 border-t">
                        <span class="font-caption text-slate-500">Total per m³</span>
                        <span class="font-strong">{{ formatCurrency(dppPerM3) }}</span>
                      </div>
                    </div>
                  </TippyContent>
                </div>
              </Table.Td>
            </Table.Tr>
            <Table.Tr>
              <Table.Td colspan="3" class="px-4 py-2 font-header text-right">Total Harga</Table.Td>
              <Table.Td class="px-4 py-2 font-num-lg text-xl text-right">{{ formatCurrency(totalHarga) }}</Table.Td>
            </Table.Tr>
          </Table.Tbody>
        </Table>
      </div>
    </CardSection>

    <template #sidebar>
      <CardSection title="Detail PO Customer" description="Lengkapi data wajib untuk memproses PO." icon="ClipboardList"
        icon-class="bg-amber-100 text-amber-600">
        <template #action>
          <span class="bg-rose-50 px-2.5 py-1 rounded-full font-caption !text-rose-600">Wajib</span>
        </template>

        <div class="space-y-4">
          <div>
            <FormLabel for="nomor_po">Nomor PO Customer
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="nomor_po" v-model="form.nomor_po" placeholder="mis. PO/2026/001"
              :class="v$.nomor_po.$error ? 'border-rose-500' : ''" />
            <small v-if="v$.nomor_po.$error" class="font-caption !text-rose-600">{{ fieldError('nomor_po') }}</small>
          </div>

          <div>
            <FormLabel for="top_poc">TOP / Termin Pembayaran
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="top_poc" v-model="form.top_poc" placeholder="mis. 30 hari"
              :class="v$.top_poc.$error ? 'border-rose-500' : ''" />
            <small v-if="v$.top_poc.$error" class="font-caption !text-rose-600">{{ fieldError('top_poc') }}</small>
          </div>

          <div class="gap-4 grid grid-cols-2">
            <DateField v-model="form.tanggal_po" label="Tanggal PO" required placeholder="Pilih tanggal PO"
              :error="fieldError('tanggal_po')" />
            <DateField v-model="form.tanggal_kirim" label="Tanggal Pengiriman" required
              placeholder="Pilih tanggal pengiriman" :error="fieldError('tanggal_kirim')" />
          </div>

          <FileUploadField v-model="form.lampiran" label="Lampiran Dokumen PO" accept=".pdf,.jpg,.jpeg,.png"
            :max-size-mb="2" hint="Opsional. PDF, JPG, atau PNG maksimal 2MB." />

          <div class="gap-3 grid grid-cols-3 bg-slate-50 p-3 border border-slate-200 rounded-lg">
            <div>
              <div class="font-caption">Item</div>
              <div class="font-strong">{{ items.length }}</div>
            </div>
            <div>
              <div class="font-caption">Total Volume</div>
              <div class="font-strong">{{ formatNumber(volumePoc) }} m³</div>
            </div>
            <div>
              <div class="font-caption">Total Harga</div>
              <div class="font-strong">{{ formatCurrency(totalHarga) }}</div>
            </div>
          </div>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
