<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useVuelidate } from '@vuelidate/core'
import { helpers, required, requiredIf } from '@vuelidate/validators'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Tippy from '@/components/Base/Tippy'
import TomSelect from '@/components/Base/TomSelect'
import { FormCheck, FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import CurrencyField from '@/components/SystemDesign/Form/CurrencyField.vue'
import NumberField from '@/components/SystemDesign/Form/NumberField.vue'
import FormPage from '@/components/SystemDesign/Form/FormPage.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import RadioCard from '@/components/SystemDesign/Form/RadioCard.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { actionErrorMessage, redirectToErrorPage } from '@/utils/http-error-route'
import { defaultCreateBrand } from '@/utils/quotation-brand'
import { createResourceApi, http } from "@/utils/resourceApi";
import type {
  ApiResponse,
  QuotationBrand,
  QuotationItemLine,
  TransportBasis,
  TransportLegType,
  TransportMode,
  TransportTariffCheck,
} from '@/types'
import ItemsGeneratorModal from './components/ItemsGeneratorModal.vue'
import { transportBasisLabel, transportLegLabel } from './status'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { success, warning, error: notifyError } = useNotification()
const transporterApi = createResourceApi("/transporters");
const transportAreaApi = createResourceApi("/transport-areas");
const volumeApi = createResourceApi("/volumes");

const QUOTATION_API_BASE = '/api/quotations'

type QuotationUpdateResponse = ApiResponse<unknown> & { meta: { transport_recalculated: boolean } }
const TRANSPORT_LEG_TYPES = ['VESSEL', 'TRUCK'] as const satisfies readonly TransportLegType[]

const METODE_LABELS = {
  FOB: 'Free On Board',
  CIF: 'Cost Insurance & Freight',
  DAP: 'Delivery At Place',
  FOT: 'Free On Truck',
  FRANCO: '',
} as const

const TYPE_PENGIRIMAN_OPTIONS = [
  { value: 'PROJECT', label: 'Project' },
  { value: 'RETAIL', label: 'Retail' },
] as const

type ItemsBasis = TransportBasis | 'mixed' | 'missing' | null
type TransportLegStatus = 'idle' | 'loading' | 'ready' | 'unavailable' | 'not_found' | 'error'

interface TransportLegState {
  checked: boolean
  transporter_id: string
  transport_area_id: string
  volume_id: string
  check: TransportTariffCheck | null
  status: TransportLegStatus
  requestId: number
}

interface LoadedTransportLeg {
  transporter_id: string
  transport_area_id: string
  volume_id: string
  transport_tariff_id: number
}

interface LoadedTransportState {
  basis: TransportBasis | null
  mode: TransportMode | null
  compositionKeys: string[]
  tariffKeys: string[]
  legs: Record<TransportLegType, LoadedTransportLeg | null>
}

const customers = ref<any[]>([])
const cabangs = ref<any[]>([])
const produks = ref<any[]>([])
const transportirs = ref<any[]>([])
const wilayahs = ref<any[]>([])
const volumes = ref<any[]>([])

const activeBrand = ref<QuotationBrand | null>(null)

const customerContacts = ref<any[]>([])
const customerContactsLoading = ref(false)
const newContactOpen = ref(false)
const newContactSaving = ref(false)
const newContactError = ref<string | null>(null)
const newContactForm = reactive({
  full_name: '',
  position: '',
  phone: '',
  mobile: '',
  email: '',
})

const transportLegs = reactive<Record<TransportLegType, TransportLegState>>({
  VESSEL: emptyLeg(),
  TRUCK: emptyLeg(),
})
const loadedTransport = ref<LoadedTransportState | null>(null)
const legSelectKey = ref(0)

const serverErrors = ref<Record<string, string>>({})
const lastPayloadLegTypes = ref<TransportLegType[]>([])
const recalcConfirmOpen = ref(false)

const loading = ref(false)
const pricePeriods = ref<any[]>([])
const selectedPeriodId = ref<string>('')
const clonedFromNomor = ref<string | null>(null)

const isEditingExpiredPeriod = ref(false)

const itemsModalOpen = ref(false)

function createDefaultForm() {
  return {
    nomor_penawaran: '',
    customer_id: '',
    customer_contact_id: '',
    branch_id: '' as number | '',
    masa_berlaku: '',
    sampai_dengan: '',

    type_pengiriman: '',
    metode: '',
    transport_mode: '' as TransportMode | '',
    items: [] as QuotationItemLine[],
    lokasi_pengiriman: '',
    keterangan: '',

    tipe_pembayaran: '',
    acuan_pembayaran: '',
    dp_persen: '',
    dp_keterangan: '',
    repayment_persen: '',
    repayment_hari: '',
    order_method: '',
    toleransi_penyusutan: '',
    abrasi: '',
    refund: 0,
    other_cost: 0,

    catatan: '',
    syarat_ketentuan: '',
    lampiran_tambahan: '',
  }
}

const form = reactive(createDefaultForm())

const idParam = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => Boolean(idParam.value))
const isProenergi = computed<boolean>(() => activeBrand.value === 'proenergi')

function round2(n: number): number {
  return Math.round(n * 100) / 100
}

const totalQuantity = computed<number>(() =>
  form.items.reduce((sum, it) => sum + Number(it.quantity || 0), 0),
)

const itemsBasis = computed<ItemsBasis>(() => {
  if (!form.items.length) return null
  if (form.items.some(it => it.cogs_basis === null)) return 'missing'
  const first = form.items[0].cogs_basis
  return form.items.every(it => it.cogs_basis === first) ? first : 'mixed'
})

const isMetodeLocked = computed<boolean>(() => itemsBasis.value === 'franco')

const itemsEditable = computed<boolean>(() => !isEditingExpiredPeriod.value)

const metodeOptions = computed<Array<{ value: string; code: string; label: string }>>(() => {
  if (itemsBasis.value === 'franco') return [metodeOption('FRANCO')]
  if (form.type_pengiriman === 'PROJECT') return (['FOB', 'CIF', 'DAP'] as const).map(metodeOption)
  if (form.type_pengiriman === 'RETAIL') {
    const values = itemsBasis.value === 'loco' ? (['FOT'] as const) : (['FOT', 'FRANCO'] as const)
    return values.map(metodeOption)
  }
  return []
})

const isLegacyDraft = computed<boolean>(
  () => isEdit.value && loadedTransport.value !== null && loadedTransport.value.basis === null,
)

const payloadTransportMode = computed<TransportMode | null>(() =>
  itemsBasis.value === 'loco' ? (form.transport_mode || null) : null,
)

const checkedLegTypes = computed<TransportLegType[]>(() =>
  TRANSPORT_LEG_TYPES.filter(type => transportLegs[type].checked),
)

const payloadTransports = computed<Array<{ transport_type: TransportLegType; transport_tariff_id: number }>>(() => {
  if (payloadTransportMode.value !== 'tariff') return []
  const rows: Array<{ transport_type: TransportLegType; transport_tariff_id: number }> = []
  for (const type of checkedLegTypes.value) {
    const tariffId = legTariffId(type)
    if (tariffId !== null) rows.push({ transport_type: type, transport_tariff_id: tariffId })
  }
  return rows
})

const compositionChanged = computed<boolean>(() => {
  if (!loadedTransport.value) return false
  return sortedKeys(form.items.map(itemCompositionKey)).join(',') !== loadedTransport.value.compositionKeys.join(',')
})

const tariffSelectionChanged = computed<boolean>(() => {
  if (!loadedTransport.value) return false
  const keys = sortedKeys(payloadTransports.value.map(row => `${row.transport_type}|${row.transport_tariff_id}`))
  if (keys.join(',') !== loadedTransport.value.tariffKeys.join(',')) return true
  return payloadTransportMode.value === 'tariff' && checkedLegTypes.value.some(type => legTariffId(type) === null)
})

const modeChanged = computed<boolean>(() => {
  const loaded = loadedTransport.value
  return loaded !== null && loaded.mode !== null && loaded.mode !== payloadTransportMode.value
})

const willRecalculate = computed<boolean>(
  () => isEdit.value && (compositionChanged.value || tariffSelectionChanged.value || modeChanged.value),
)

const requiresFreshPricing = computed<boolean>(() => !isEdit.value || willRecalculate.value)

const freshLocoTransportPrice = computed<number | null>(() => {
  if (form.transport_mode === 'pickup') return 0
  if (form.transport_mode !== 'tariff' || !checkedLegTypes.value.length) return null
  let sum = 0
  for (const type of checkedLegTypes.value) {
    const leg = transportLegs[type]
    if (leg.status !== 'ready' || !leg.check) return null
    sum += Number(leg.check.price ?? 0)
  }
  return round2(sum)
})

const dppPreview = computed<number | null>(() => {
  let sum = 0
  for (const it of form.items) {
    const total = itemFinalTotal(it)
    if (total === null) return null
    sum += total
  }
  return round2(sum)
})
const ppnPreview = computed<number | null>(() => (dppPreview.value === null ? null : round2(dppPreview.value * 0.11)))
const totalPreview = computed<number | null>(() =>
  dppPreview.value === null || ppnPreview.value === null ? null : round2(dppPreview.value + ppnPreview.value),
)

const pricingBlockers = computed<string[]>(() => {
  const blockers: string[] = []
  if (!requiresFreshPricing.value) return blockers
  if (itemsBasis.value === 'mixed') blockers.push('Produk loco dan franco tidak boleh dalam satu penawaran.')
  if (itemsBasis.value === 'missing') {
    blockers.push('Sebagian produk belum memiliki basis harga (loco/franco). Lengkapi di Periode Harga.')
  }
  if (itemsBasis.value === 'loco') {
    if (!form.transport_mode) {
      blockers.push('Pilih moda angkutan (Kapal/Truck) atau Tanpa biaya transport.')
    } else if (form.transport_mode === 'tariff') {
      if (!checkedLegTypes.value.length) blockers.push('Pilih minimal satu moda angkutan (Kapal/Truck).')
      for (const type of checkedLegTypes.value) {
        const leg = transportLegs[type]
        const label = transportLegLabel(type)
        if (!leg.transporter_id || !leg.transport_area_id || !leg.volume_id) {
          blockers.push(`Lengkapi transportir, wilayah, dan volume untuk ${label}.`)
        } else if (leg.status === 'unavailable') {
          blockers.push(`Tarif ${label} belum siap. Margin belum diisi Logistik.`)
        } else if (leg.status === 'not_found') {
          blockers.push(`Tarif ${label} tidak ditemukan.`)
        } else if (leg.status !== 'ready') {
          blockers.push(`Harga angkut ${label} belum termuat.`)
        }
      }
    }
  }
  if (itemsBasis.value === 'franco') {
    const missing = form.items.filter(it => it.transport_price === null).map(it => produkLabel(it.product_id))
    if (missing.length) blockers.push(`Margin transport belum diisi Logistik untuk: ${missing.join(', ')}.`)
  }
  return blockers
})

const periodeOptions = computed(() => {
  const rank = (c: string) => (c === 'active' ? 0 : c === 'upcoming' ? 1 : 2)
  return [...pricePeriods.value]
    .filter(p => p.category === 'active' || p.category === 'upcoming')
    .sort((a, b) =>
      rank(a.category) - rank(b.category) ||
      String(a.start_date).localeCompare(String(b.start_date)),
    )
})

const periodeSelectList = computed(() => {
  const list = periodeOptions.value
  const stored = form.masa_berlaku && form.sampai_dengan
  const matched = list.some(
    p => p.start_date === form.masa_berlaku && p.end_date === form.sampai_dengan,
  )
  if (stored && !matched) {
    return [
      {
        id: 'custom',
        category: 'inactive',
        start_date: form.masa_berlaku,
        end_date: form.sampai_dengan,
        label: `${form.masa_berlaku} – ${form.sampai_dengan} (di luar daftar periode)`,
      },
      ...list,
    ]
  }
  return list
})

const periodeIsChoice = computed(() => periodeSelectList.value.length > 1)

const selectedPeriodLabel = computed(() => {
  const p = periodeSelectList.value.find(x => String(x.id) === String(selectedPeriodId.value))
  if (p) return p.label + (p.category === 'upcoming' ? ' · akan datang' : '')
  if (form.masa_berlaku) return `${form.masa_berlaku} s/d ${form.sampai_dengan}`
  return ''
})

const selectedCustomer = computed(() =>
  customers.value.find(c => String(c.id_customer) === String(form.customer_id)) ?? null
)
const typePengirimanTouched = ref(false)

function applyCustomerTypeDefault() {
  if (isEdit.value || typePengirimanTouched.value) return
  const type = String(selectedCustomer.value?.customer_type ?? '').toUpperCase()
  if (type === 'PROJECT' || type === 'RETAIL') form.type_pengiriman = type
}

const selectedContact = computed(() =>
  customerContacts.value.find(c => String(c.id) === String(form.customer_contact_id)) ?? null
)

const produkById = computed<Record<string, any>>(() => {
  const m: Record<string, any> = {}
  for (const p of produks.value) m[String(p.id_produk)] = p
  return m
})

const selectedPeriodLabelText = computed(() => selectedPeriodLabel.value || form.masa_berlaku)

const validationRules = computed(() => ({
  customer_id: { required: helpers.withMessage('Customer wajib diisi.', required) },
  customer_contact_id: { required: helpers.withMessage('Kontak tujuan wajib dipilih.', required) },
  branch_id: { required: helpers.withMessage('Cabang wajib dipilih.', required) },
  type_pengiriman: { required: helpers.withMessage('Type Pengiriman wajib dipilih.', required) },
  masa_berlaku: { required: helpers.withMessage('Periode harga wajib dipilih.', required) },
  sampai_dengan: {
    required: helpers.withMessage('Sampai dengan wajib diisi.', required),
    afterStart: helpers.withMessage(
      'Tanggal "Sampai Dengan" tidak boleh lebih awal dari "Masa Berlaku".',
      (value: string) => {
        if (!value || !form.masa_berlaku) return true
        const mb = new Date(form.masa_berlaku).getTime()
        const sd = new Date(value).getTime()
        if (Number.isNaN(mb) || Number.isNaN(sd)) return true
        return sd >= mb
      },
    ),
  },
  metode: { required: helpers.withMessage('Metode wajib dipilih.', required) },
  tipe_pembayaran: { required: helpers.withMessage('Tipe pembayaran wajib dipilih.', required) },
  dp_persen: {
    requiredIfCustom: helpers.withMessage(
      'Persentase DP wajib diisi untuk tipe Custom.',
      requiredIf(() => form.tipe_pembayaran === 'CUSTOM'),
    ),
  },
  repayment_persen: {
    requiredIfCustom: helpers.withMessage(
      'Persentase Repayment wajib diisi untuk tipe Custom.',
      requiredIf(() => form.tipe_pembayaran === 'CUSTOM'),
    ),
  },
  items: {
    minLength: helpers.withMessage(
      'Minimal 1 item produk.',
      (val: QuotationItemLine[]) => Array.isArray(val) && val.length > 0,
    ),
    $each: helpers.forEach({
      product_id: { required: helpers.withMessage('Produk wajib dipilih.', required) },
      quantity: {
        positive: helpers.withMessage(
          'Quantity tiap item wajib lebih dari 0.',
          (v: number | string) => Number(v) > 0,
        ),
      },
      unit_price: {
        positive: helpers.withMessage(
          'Harga per unit tiap item wajib lebih dari 0.',
          (v: number | string) => Number(v) > 0,
        ),
      },
    }),
  },
}))

const v$ = useVuelidate(validationRules, form)

watch(
  () => route.fullPath,
  () => {
    if (route.name === 'forbidden' || route.name === 'not-found') return
    initializeForm()
  },
  { immediate: true },
)

watch(selectedPeriodId, (id) => {
  if (id === 'custom') return
  const p = pricePeriods.value.find(x => String(x.id) === String(id))
  form.masa_berlaku = p?.start_date ?? ''
  form.sampai_dengan = p?.end_date ?? ''
})

watch(() => form.customer_id, async (val) => {
  applyCustomerTypeDefault()
  await fetchCustomerContacts(val)
  if (form.customer_contact_id && !customerContacts.value.some(c => String(c.id) === String(form.customer_contact_id))) {
    form.customer_contact_id = ''
  }
})

watch(itemsBasis, (basis) => {
  if (basis === 'franco') form.metode = 'FRANCO'
  else if (basis === 'loco' && form.metode === 'FRANCO') form.metode = ''
})

watch(() => form.type_pengiriman, () => {
  if (form.metode && !metodeOptions.value.some(opt => opt.value === form.metode)) form.metode = ''
})

for (const type of TRANSPORT_LEG_TYPES) {
  watch(
    () => [
      transportLegs[type].checked,
      transportLegs[type].transporter_id,
      transportLegs[type].transport_area_id,
      transportLegs[type].volume_id,
    ],
    () => fetchLegTariff(type),
  )
}

function resetLocalState() {
  Object.assign(form, createDefaultForm())
  typePengirimanTouched.value = false
  selectedPeriodId.value = ''
  clonedFromNomor.value = null
  isEditingExpiredPeriod.value = false
  for (const type of TRANSPORT_LEG_TYPES) resetLeg(type)
  loadedTransport.value = null
  serverErrors.value = {}
  lastPayloadLegTypes.value = []
  recalcConfirmOpen.value = false
  legSelectKey.value++
  v$.value.$reset()
}

async function initializeForm() {
  resetLocalState()
  activeBrand.value = isEdit.value || route.query.clone_from ? null : defaultCreateBrand(auth.can)
  await Promise.all([fetchSelects(), fetchTransportirWilayahVolume()])
  if (!isEdit.value) {
    const jakarta = cabangs.value.find(
      c => String(c.nama_cabang ?? '').toLowerCase().includes('jakarta'),
    )
    if (jakarta) form.branch_id = jakarta.id_cabang

    const defaultPeriod = periodeOptions.value[0]
    if (defaultPeriod) selectedPeriodId.value = String(defaultPeriod.id)

    if (route.query.clone_from) {
      await fetchCloneSource(String(route.query.clone_from))
    } else if (route.query.customer_id) {
      form.customer_id = String(route.query.customer_id)
    }
  } else {
    await fetchQuotation()
  }
}

async function fetchSelects() {
  try {
    const [cusData, cabData, prdData, ppData] = await Promise.all([
      axios.get('/api/customers', { params: { as_list: true } }),
      axios.get('/api/cabangs'),
      axios.get('/api/produks', { params: { with: 'ukuran', per_page: 1000 } }),
      axios.get('/api/price-periods'),
    ])
    customers.value = cusData.data.data || cusData.data
    cabangs.value = cabData.data.data || cabData.data
    produks.value = prdData.data.data || prdData.data
    pricePeriods.value = ppData.data.data || ppData.data
  } catch {
    notifyError('Gagal', 'Gagal memuat data master')
  }
}

async function fetchCustomerContacts(customerId: number | string) {
  if (!customerId) {
    customerContacts.value = []
    return
  }
  customerContactsLoading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${customerId}/contacts`)
    customerContacts.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch {
    customerContacts.value = []
    notifyError('Gagal', 'Gagal memuat kontak customer')
  } finally {
    customerContactsLoading.value = false
  }
}

async function fetchTransportirWilayahVolume() {
  const [t, w, v] = await Promise.all([
    transporterApi.getAll({ as_list: true }),
    transportAreaApi.getAll({ as_list: true }),
    volumeApi.getAll({ as_list: true }),
  ]);
  transportirs.value = t.data.data;
  wilayahs.value = w.data.data;
  volumes.value = v.data.data;
}

async function fetchPeriodPriceRows(periodId: string | number): Promise<any[]> {
  const { data } = await axios.get('/api/product-prices', {
    params: { price_period_id: periodId, as_list: 1 },
  })
  return data.data || data || []
}

async function fetchLegTariff(type: TransportLegType): Promise<void> {
  const leg = transportLegs[type]
  if (!leg.checked || !leg.transporter_id || !leg.transport_area_id || !leg.volume_id) {
    leg.requestId++
    leg.check = null
    leg.status = 'idle'
    return
  }
  const requestId = ++leg.requestId
  leg.status = 'loading'
  try {
    const { data } = await http.get('/transport-tariffs/check', {
      params: {
        transporter_id: leg.transporter_id,
        transport_type: type,
        transport_area_id: leg.transport_area_id,
        volume_id: leg.volume_id,
      },
    })
    if (requestId !== leg.requestId) return
    const check: TransportTariffCheck | null = data.data ?? null
    leg.check = check
    leg.status = check === null ? 'not_found' : check.available ? 'ready' : 'unavailable'
  } catch {
    if (requestId !== leg.requestId) return
    leg.check = null
    leg.status = 'error'
  }
}

async function fetchQuotation() {
  try {
    const { data: body } = await axios.get<ApiResponse<any>>(`${QUOTATION_API_BASE}/${idParam.value}`)
    const data = body.data
    activeBrand.value = data.brand

    isEditingExpiredPeriod.value = isDateExpired(data.sampai_dengan)
    selectedPeriodId.value = data.price_period_id != null ? String(data.price_period_id) : ''

    Object.assign(form, {
      nomor_penawaran: data.nomor_penawaran,
      customer_id: data.customer_id ? String(data.customer_id) : '',
      customer_contact_id: data.customer_contact?.id_contact ? String(data.customer_contact.id_contact) : '',
      branch_id: data.branch_id,
      masa_berlaku: data.masa_berlaku,
      sampai_dengan: data.sampai_dengan,

      type_pengiriman: data.type_pengiriman || '',
      metode: data.metode || '',
      lokasi_pengiriman: data.lokasi_pengiriman || '',
      keterangan: data.keterangan || '',

      tipe_pembayaran: data.tipe_pembayaran || '',
      acuan_pembayaran: data.acuan_pembayaran || '',
      dp_persen: formatInt(data.dp_persen),
      dp_keterangan: data.dp_keterangan || '',
      repayment_persen: formatInt(data.repayment_persen),
      repayment_hari: formatInt(data.repayment_hari),
      order_method: data.order_method || '',
      toleransi_penyusutan: data.toleransi_penyusutan ? String(Number(data.toleransi_penyusutan)) : '',
      abrasi: data.abrasi || '',
      refund: data.refund != null ? Number(data.refund) : 0,
      other_cost: data.other_cost != null ? Number(data.other_cost) : 0,

      catatan: data.catatan || '',
      syarat_ketentuan: data.syarat_ketentuan || '',
      lampiran_tambahan: data.lampiran_tambahan || '',
    })

    form.items = data.items.map((it: any) => ({
      product_id: it.product_id ? String(it.product_id) : '',
      source_branch_id: it.source_branch_id != null ? String(it.source_branch_id) : '',
      product_price_id: it.product_price_id ?? null,
      source_name: it.source_branch?.nama_cabang ?? '',
      quantity: Number(it.quantity ?? 0),
      unit_price: Number(it.unit_price ?? 0),
      harga_price_list: Number(it.price_list ?? 0),
      cogs_basis: it.cogs_basis ?? null,
      transport_price: null,
      saved_transport_price: Number(it.transport_price ?? 0),
    }))
    applySourceTransports(data, true)

    if (data.price_period_id != null) {
      const rows = await fetchPeriodPriceRows(data.price_period_id).catch(() => [] as any[])
      for (const item of form.items) {
        const row = rows.find(r => r.id === item.product_price_id)
        if (row && row.transport_price != null) item.transport_price = Number(row.transport_price)
      }
    }
  } catch (e: any) {
    if (redirectToErrorPage(router, e.response?.status)) return
    notifyError('Gagal', 'Gagal memuat data penawaran')
  }
}

async function fetchCloneSource(cloneFromId: string) {
  try {
    const { data: body } = await axios.get<ApiResponse<any>>(`${QUOTATION_API_BASE}/${cloneFromId}`)
    const data = body.data
    activeBrand.value = data.brand
    clonedFromNomor.value = data.nomor_penawaran || null

    form.customer_id = data.customer_id ? String(data.customer_id) : ''
    form.customer_contact_id = data.customer_contact?.id_contact ? String(data.customer_contact.id_contact) : ''
    form.branch_id = data.branch_id

    form.type_pengiriman = data.type_pengiriman || ''
    typePengirimanTouched.value = true
    form.metode = data.metode || ''
    form.lokasi_pengiriman = data.lokasi_pengiriman || ''
    form.keterangan = data.keterangan || ''

    form.tipe_pembayaran = data.tipe_pembayaran || ''
    form.acuan_pembayaran = data.acuan_pembayaran || ''
    form.dp_persen = formatInt(data.dp_persen)
    form.dp_keterangan = data.dp_keterangan || ''
    form.repayment_persen = formatInt(data.repayment_persen)
    form.repayment_hari = formatInt(data.repayment_hari)
    form.order_method = data.order_method || ''
    form.toleransi_penyusutan = data.toleransi_penyusutan ? String(Number(data.toleransi_penyusutan)) : ''
    form.abrasi = data.abrasi || ''
    form.refund = data.refund != null ? Number(data.refund) : 0
    form.other_cost = data.other_cost != null ? Number(data.other_cost) : 0

    form.catatan = data.catatan || ''
    form.syarat_ketentuan = data.syarat_ketentuan || ''
    form.lampiran_tambahan = data.lampiran_tambahan || ''

    await remapClonedItems(Array.isArray(data.items) ? data.items : [])
    applySourceTransports(data, false)

    success('Data Dimuat', `Data dari penawaran ${data.nomor_penawaran} berhasil disalin ke form ini. Periksa kembali item & periode harga sebelum menyimpan.`)
  } catch {
    activeBrand.value = defaultCreateBrand(auth.can)
    notifyError('Gagal', 'Gagal memuat data penawaran sumber untuk disalin.')
  }
}

async function remapClonedItems(oldItems: any[]) {
  const targetPeriodId = selectedPeriodId.value
  if (!targetPeriodId || targetPeriodId === 'custom') {
    form.items = []
    return
  }

  try {
    const rows = await fetchPeriodPriceRows(targetPeriodId)
    const rowByKey = new Map<string, any>()
    for (const r of rows) rowByKey.set(`${r.product_id}:${r.branch_id}`, r)

    const remapped: QuotationItemLine[] = []
    const skipped: string[] = []

    for (const it of oldItems) {
      const row = rowByKey.get(`${it.product_id}:${it.source_branch_id}`)
      if (!row) {
        skipped.push(it.product?.nama_produk || `#${it.product_id}`)
        continue
      }
      const harga = isProenergi.value && Number(row.price_list_pe) > 0 ? Number(row.price_list_pe) : Number(row.price_list)
      remapped.push({
        product_id: String(it.product_id ?? ''),
        source_branch_id: it.source_branch_id != null ? String(it.source_branch_id) : '',
        product_price_id: row.id,
        source_name: it.source_branch?.nama_cabang ?? '',
        quantity: Number(it.quantity ?? 0),
        unit_price: Number(it.unit_price ?? 0) || harga,
        harga_price_list: harga || 0,
        cogs_basis: row.cogs_basis ?? null,
        transport_price: row.transport_price == null ? null : Number(row.transport_price),
        saved_transport_price: null,
      })
    }

    form.items = remapped

    if (skipped.length) {
      notifyError(
        'Sebagian Item Tidak Bisa Dibawa',
        `Produk berikut tidak tersedia di periode harga aktif, silakan tambahkan manual lewat "Susun Item Penawaran": ${skipped.join(', ')}.`,
      )
    }
  } catch {
    form.items = []
    notifyError('Gagal', 'Gagal mencocokkan item penawaran ke periode harga aktif.')
  }
}

function openItemsModal() {
  if (!selectedPeriodId.value || selectedPeriodId.value === 'custom') {
    notifyError('Periode belum siap', 'Pilih periode harga dulu sebelum menyusun item.')
    return
  }
  itemsModalOpen.value = true
}

function onItemsSaved(items: QuotationItemLine[]) {
  form.items = items.map(x => ({ ...x }))
  itemsModalOpen.value = false
}

function fieldError(field: string): string {
  const f = (v$.value as any)[field]
  return f?.$error ? (f.$errors[0]?.$message?.toString() ?? '') : ''
}

function inputClass(field: string): string {
  return (v$.value as any)[field]?.$error ? 'input-error' : ''
}

function collectErrorMessages(): string[] {
  const msgs: string[] = []
  const push = (m?: string) => { if (m && !msgs.includes(m)) msgs.push(m) }

  for (const e of v$.value.$errors) push(e.$message?.toString())

  const eachErrors = (v$.value.items as any)?.$each?.$response?.$errors ?? []
  for (const rowError of eachErrors) {
    if (!rowError) continue
    for (const field of Object.keys(rowError)) {
      for (const er of rowError[field]) push(er?.$message?.toString())
    }
  }
  return msgs
}

async function submitForm() {
  serverErrors.value = {}
  if (isEditingExpiredPeriod.value) {
    notifyError(
      'Tidak Bisa Disimpan',
      'Periode harga penawaran ini sudah tidak aktif. Gunakan tombol "Buat Penawaran Baru dari Ini" di atas.',
    )
    return
  }
  if (!selectedPeriodId.value || selectedPeriodId.value === 'custom') {
    notifyError('Periode belum dipilih', 'Pilih periode harga yang aktif untuk penawaran ini.')
    return
  }
  const valid = await v$.value.$validate()
  if (!valid) {
    notifyError('Validasi Gagal', 'Periksa kembali isian berikut:', {
      items: collectErrorMessages(),
    })
    return
  }
  if (pricingBlockers.value.length) {
    notifyError('Harga Transport Belum Siap', 'Periksa kembali isian berikut:', {
      items: pricingBlockers.value,
    })
    return
  }
  if (willRecalculate.value) {
    recalcConfirmOpen.value = true
    return
  }
  await saveQuotation()
}

function confirmRecalculate() {
  recalcConfirmOpen.value = false
  saveQuotation()
}

async function saveQuotation() {
  loading.value = true
  lastPayloadLegTypes.value = payloadTransports.value.map(row => row.transport_type)
  try {
    const payloadItems = form.items.map((it) => ({
      product_id: Number(it.product_id),
      source_branch_id: Number(it.source_branch_id),
      product_price_id: Number(it.product_price_id),
      quantity: Number(it.quantity),
      unit_price: Number(it.unit_price),
    }))

    const payload = {
      customer_id: Number(form.customer_id),
      customer_contact_id: Number(form.customer_contact_id),
      branch_id: form.branch_id,
      price_period_id: Number(selectedPeriodId.value),

      type_pengiriman: form.type_pengiriman,
      metode: form.metode,
      transport_mode: payloadTransportMode.value,
      transports: payloadTransports.value,
      items: payloadItems,
      lokasi_pengiriman: form.lokasi_pengiriman,
      keterangan: form.keterangan,

      tipe_pembayaran: form.tipe_pembayaran,
      ...(isProenergi.value ? {
        acuan_pembayaran: form.acuan_pembayaran,
      } : {}),
      dp_persen: parseInt((form.dp_persen || '0').replace(/\./g, ''), 10) || 0,
      dp_keterangan: form.dp_keterangan,
      repayment_persen: parseInt((form.repayment_persen || '0').replace(/\./g, ''), 10) || 0,
      repayment_hari: parseInt((form.repayment_hari || '0').replace(/\./g, ''), 10) || 0,
      order_method: form.order_method,
      toleransi_penyusutan: parseInt((form.toleransi_penyusutan || '0').replace(/\./g, ''), 10) || 0,
      abrasi: form.abrasi,
      refund: form.refund,
      other_cost: form.other_cost,

      catatan: form.catatan,
      syarat_ketentuan: form.syarat_ketentuan,
      lampiran_tambahan: form.lampiran_tambahan,
      ...(isEdit.value ? {} : { brand: activeBrand.value }),
    }

    if (isEdit.value) {
      const { data: body } = await axios.put<QuotationUpdateResponse>(`${QUOTATION_API_BASE}/${idParam.value}`, payload)
      if (body.meta?.transport_recalculated === true) {
        warning('Penawaran Diupdate', body.message || 'Harga transport dihitung ulang mengikuti tarif terbaru.')
      } else {
        success('Berhasil', body.message || 'Penawaran berhasil diupdate.')
      }
      router.push({ name: 'quotations.show', params: { id: idParam.value } })
    } else {
      const { data: body } = await axios.post<ApiResponse<{ id: number }>>(QUOTATION_API_BASE, payload)
      success('Berhasil', body.message || 'Penawaran berhasil dibuat.')
      router.push({ name: 'quotations.show', params: { id: body.data.id } })
    }
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data.errors) {
      applyServerErrors(e.response.data.errors)
      const items = Object.values(e.response.data.errors).flat() as string[]
      notifyError('Validasi Backend Gagal', 'Perbaiki data berikut:', { items })
    } else if (e.response?.status === 409) {
      notifyError('Gagal', e.response.data?.message || 'Penawaran tidak bisa diubah.')
      router.push({ name: 'quotations.show', params: { id: idParam.value } })
    } else {
      notifyError('Gagal', actionErrorMessage(e, 'Gagal menyimpan penawaran.'))
    }
  } finally {
    loading.value = false
  }
}

function applyServerErrors(errors: Record<string, string[]>) {
  serverErrors.value = Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0] ?? '']))
}

function serverError(key: string): string {
  return serverErrors.value[key] ?? ''
}

function legServerError(type: TransportLegType): string {
  const index = lastPayloadLegTypes.value.indexOf(type)
  if (index === -1) return ''
  return serverError(`transports.${index}.transport_tariff_id`) || serverError(`transports.${index}.transport_type`)
}

function itemServerError(index: number): string {
  return serverError(`items.${index}.product_price_id`)
}

function emptyLeg(): TransportLegState {
  return {
    checked: false,
    transporter_id: '',
    transport_area_id: '',
    volume_id: '',
    check: null,
    status: 'idle',
    requestId: 0,
  }
}

function resetLeg(type: TransportLegType, patch: Partial<TransportLegState> = {}) {
  const leg = transportLegs[type]
  const nextRequestId = leg.requestId + 1
  Object.assign(leg, emptyLeg(), patch, { requestId: nextRequestId })
}

function onLegToggle(type: TransportLegType, checked: boolean) {
  if (!checked) resetLeg(type)
  transportLegs[type].checked = checked
  form.transport_mode = checkedLegTypes.value.length ? 'tariff' : ''
}

function onPickupToggle(checked: boolean) {
  if (checked) {
    for (const type of TRANSPORT_LEG_TYPES) resetLeg(type)
    form.transport_mode = 'pickup'
  } else {
    form.transport_mode = ''
  }
}

function sortedKeys(keys: string[]): string[] {
  return [...keys].sort()
}

function itemCompositionKey(it: QuotationItemLine): string {
  return `${Number(it.product_id)}|${Number(it.source_branch_id)}|${Number(it.product_price_id)}`
}

function metodeOption(value: keyof typeof METODE_LABELS): { value: string; code: string; label: string } {
  return { value, code: value === 'FRANCO' ? 'Franco' : value, label: METODE_LABELS[value] }
}

function selectTypePengiriman(value: string) {
  typePengirimanTouched.value = true
  form.type_pengiriman = value
}

function legSelectionChanged(type: TransportLegType): boolean {
  if (!loadedTransport.value) return true
  const loaded = loadedTransport.value.legs[type]
  const leg = transportLegs[type]
  if (!loaded) return leg.checked
  return (
    !leg.checked ||
    leg.transporter_id !== loaded.transporter_id ||
    leg.transport_area_id !== loaded.transport_area_id ||
    leg.volume_id !== loaded.volume_id
  )
}

function legTariffId(type: TransportLegType): number | null {
  if (isEdit.value && !legSelectionChanged(type)) return loadedTransport.value?.legs[type]?.transport_tariff_id ?? null
  const leg = transportLegs[type]
  return leg.status === 'ready' && leg.check ? leg.check.id : null
}

function applySourceTransports(data: any, asLoadedState: boolean) {
  const rows: any[] = Array.isArray(data.transports) ? data.transports : []
  const legs: Record<TransportLegType, LoadedTransportLeg | null> = { VESSEL: null, TRUCK: null }

  for (const type of TRANSPORT_LEG_TYPES) resetLeg(type)
  for (const row of rows) {
    const type = row.transport_type as TransportLegType
    const selection = {
      transporter_id: String(row.transporter_id ?? ''),
      transport_area_id: String(row.transport_area_id ?? ''),
      volume_id: String(row.volume_id ?? ''),
    }
    resetLeg(type, { checked: true, ...selection })
    legs[type] = { ...selection, transport_tariff_id: Number(row.transport_tariff_id) }
  }

  form.transport_mode = itemsBasis.value === 'franco' ? '' : (data.transport_mode ?? (rows.length ? 'tariff' : ''))

  if (asLoadedState) {
    loadedTransport.value = {
      basis: data.transport_basis ?? null,
      mode: data.transport_mode ?? null,
      compositionKeys: sortedKeys(form.items.map(itemCompositionKey)),
      tariffKeys: sortedKeys(rows.map(row => `${row.transport_type}|${row.transport_tariff_id}`)),
      legs,
    }
  }
  legSelectKey.value++
}

function itemTransportPrice(it: QuotationItemLine): number | null {
  if (!requiresFreshPricing.value) return it.saved_transport_price ?? 0
  if (itemsBasis.value === 'franco') return it.transport_price
  if (itemsBasis.value === 'loco') return freshLocoTransportPrice.value
  return null
}

function itemFinalPrice(it: QuotationItemLine): number | null {
  const transport = itemTransportPrice(it)
  return transport === null ? null : round2(Number(it.unit_price) + transport)
}

function itemFinalTotal(it: QuotationItemLine): number | null {
  const finalPrice = itemFinalPrice(it)
  return finalPrice === null ? null : round2(Number(it.quantity) * finalPrice)
}

function goBack() {
  router.push({ name: 'quotations.index' })
}

function openCloneFromCurrentEdit() {
  router.push({ name: 'quotations.create', query: { clone_from: String(idParam.value) } })
}

function openNewContactForm() {
  if (!form.customer_id) return
  newContactError.value = null
  Object.assign(newContactForm, { full_name: '', position: '', phone: '', mobile: '', email: '' })
  newContactOpen.value = true
}

async function submitNewContact() {
  newContactError.value = null
  if (!newContactForm.full_name.trim()) {
    newContactError.value = 'Nama wajib diisi.'
    return
  }
  newContactSaving.value = true
  try {
    const { data } = await axios.post(`/api/customers/${form.customer_id}/contacts`, {
      full_name: newContactForm.full_name.trim(),
      position: newContactForm.position.trim() || null,
      phone: newContactForm.phone.trim() || null,
      mobile: newContactForm.mobile.trim() || null,
      email: newContactForm.email.trim() || null,
    })
    customerContacts.value.push(data)
    form.customer_contact_id = String(data.id)
    newContactOpen.value = false
    success('Berhasil', 'Kontak berhasil ditambahkan.')
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      newContactError.value = (Object.values(errors)[0] as string[])?.[0] || 'Periksa kembali input Anda.'
    } else {
      newContactError.value = e.response?.data?.message ?? 'Gagal menyimpan kontak.'
    }
  } finally {
    newContactSaving.value = false
  }
}

function isDateExpired(dateStr: string | null | undefined): boolean {
  if (!dateStr) return false
  const end = new Date(dateStr)
  const today = new Date(new Date().toDateString())
  return end < today
}

function produkName(id: string | number): string {
  const p = produkById.value[String(id)]
  return p ? p.nama_produk : `#${id}`
}

function produkSize(id: string | number): string {
  const ukuran = produkById.value[String(id)]?.ukuran
  if (!ukuran?.nama_ukuran) return ''
  return `${ukuran.nama_ukuran}${ukuran.satuan?.nama_satuan ? ' ' + ukuran.satuan.nama_satuan : ''}`
}

function itemFieldError(index: number, field: 'quantity' | 'unit_price'): string {
  const items = v$.value.items as any
  if (!items?.$dirty) return ''
  const rowErrors = items.$each?.$response?.$errors?.[index]?.[field] ?? []
  return rowErrors[0]?.$message?.toString() ?? ''
}

function produkLabel(id: string | number): string {
  const p = produkById.value[String(id)]
  if (!p) return `#${id}`
  const uk = p.ukuran?.nama_ukuran
    ? ` · ${p.ukuran.nama_ukuran}${p.ukuran?.satuan?.nama_satuan ? ' ' + p.ukuran.satuan.nama_satuan : ''}`
    : ''
  return `${p.nama_produk}${uk}`
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
  <FormPage :title="isEdit ? 'Edit Penawaran' : 'Tambah Penawaran'"
    :description="isEdit ? 'Perbarui data penawaran ke customer.' : 'Lengkapi data penawaran baru ke customer.'"
    size="full" layout="sidebar" surface="plain" :show-footer="false" :loading="loading"
    :submit-text="isEdit ? 'Update Penawaran' : 'Simpan Penawaran'" submit-icon="Save" cancel-icon="ArrowLeft"
    :disable-submit="isEditingExpiredPeriod" @cancel="goBack" @submit="submitForm">
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="goBack">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <template v-if="isEdit || clonedFromNomor" #header>
      <div v-if="isLegacyDraft && !isEditingExpiredPeriod"
        class="flex items-start gap-2 bg-amber-50 px-4 py-3 border border-amber-200 rounded-lg text-amber-800">
        <span class="text-body leading-5">
          <b>Penawaran lama.</b> Harga transport dipertahankan selama produk dan tarif tidak diubah.
        </span>
      </div>
      <div v-if="isEdit && isEditingExpiredPeriod"
        class="flex flex-wrap justify-between items-center gap-3 bg-rose-50 px-4 py-3 border border-rose-200 rounded-lg text-rose-800">
        <span class="text-body leading-5">
          <b>Penawaran ini tidak dapat diedit:</b> periode harga yang berlaku saat penawaran ini dibuat sudah
          berubah (tidak aktif lagi), sehingga perubahan apa pun di form ini tidak akan tersimpan — gunakan
          tombol berikut untuk membuat penawaran baru dari data ini.
        </span>
        <Button type="button" variant="danger" class="inline-flex items-center gap-2 shrink-0"
          @click="openCloneFromCurrentEdit">
          <Lucide icon="Copy" class="w-4 h-4" />
          Buat Penawaran Baru dari Ini
        </Button>
      </div>
      <div v-else-if="isEdit"
        class="flex items-start gap-2 bg-amber-50 px-4 py-3 border border-amber-200 rounded-lg text-amber-800">
        <span class="text-body leading-5">
          <b>Info:</b> Penawaran ini masih berstatus <b>Draft</b>. Perubahan baru diverifikasi setelah
          penawaran diajukan.
        </span>
      </div>
      <div v-else-if="clonedFromNomor"
        class="flex items-start gap-2 bg-sky-50 px-4 py-3 border border-sky-200 rounded-lg text-sky-800">
        <span class="text-body leading-5">
          <b>Info:</b> Data ini disalin dari penawaran <b>{{ clonedFromNomor }}</b> (periode harganya sudah
          tidak aktif). Penawaran ini akan mendapat nomor baru, dan item produk sudah dicocokkan ke periode
          harga yang sedang aktif — periksa kembali sebelum disimpan.
        </span>
      </div>
    </template>

    <CardSection title="Informasi Penawaran" description="Customer, source harga dan informasi penerima"
      icon="FileText">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-5">

          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Customer
                <RequiredAsterisk />
              </FormLabel>
              <div>
                <TomSelect v-model="form.customer_id" :options="{
                  placeholder: 'Pilih Customer...',
                  dropdownParent: 'body' as const,
                }" class="w-full" :class="inputClass('customer_id')">
                  <option v-for="c in customers" :key="c.id_customer" :value="String(c.id_customer)">
                    {{ c.company_name }}
                  </option>
                </TomSelect>
              </div>
              <small v-if="fieldError('customer_id')" class="block input-error-text">{{ fieldError('customer_id')
              }}</small>
            </div>

            <div>
              <FormLabel>Periode Harga
                <RequiredAsterisk />
              </FormLabel>

              <FormSelect v-if="periodeIsChoice" v-model="selectedPeriodId" class="w-full"
                :class="inputClass('masa_berlaku')">
                <option value="" disabled>Pilih periode…</option>
                <option v-for="p in periodeSelectList" :key="p.id" :value="String(p.id)">
                  {{ p.label }}{{ p.category === 'upcoming' ? ' · akan datang' : '' }}
                </option>
              </FormSelect>

              <div v-else-if="selectedPeriodLabel"
                class="bg-slate-50 px-3 py-2 border border-slate-200 rounded-md text-body-strong text-slate-700">
                {{ selectedPeriodLabel }}
              </div>

              <div v-else class="bg-amber-50 px-3 py-2 border border-amber-200 rounded-md !text-amber-700 text-body">
                Belum ada periode harga aktif — hubungi Procurement.
              </div>

              <small v-if="fieldError('masa_berlaku') || fieldError('sampai_dengan')" class="block input-error-text">
                {{ fieldError('masa_berlaku') || fieldError('sampai_dengan') }}
              </small>
              <small v-else-if="periodeIsChoice && form.masa_berlaku" class="block mt-1 text-caption text-slate-500">
                {{ form.masa_berlaku }} s/d {{ form.sampai_dengan }}
              </small>
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-7">
          <div class="px-4 py-3 border border-slate-200 rounded-xl">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12 md:col-span-6">
                <FormLabel>Kepada (Perusahaan / Dept.)</FormLabel>
                <div class="mt-1 text-body-strong">{{ selectedCustomer?.company_name || '-' }}</div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Kontak Tujuan
                  <RequiredAsterisk />
                </FormLabel>
                <div v-if="!form.customer_id" class="mt-1 text-body text-slate-500">
                  Pilih Customer terlebih dahulu
                </div>
                <div v-else-if="customerContactsLoading"
                  class="inline-flex items-center gap-2 mt-1 text-body text-slate-500">
                  <Lucide icon="Loader2" class="w-4 h-4 animate-spin" />
                  Memuat kontak…
                </div>
                <div v-else-if="customerContacts.length === 0" class="mt-1 text-body text-slate-500">
                  Belum ada kontak untuk customer ini
                </div>
                <TomSelect v-else v-model="form.customer_contact_id" :options="{
                  placeholder: 'Pilih Kontak Tujuan...',
                  dropdownParent: 'body' as const,
                }" class="w-full" :class="inputClass('customer_contact_id')">
                  <option v-for="c in customerContacts" :key="c.id" :value="String(c.id)">
                    {{ c.full_name }}{{ c.position ? ` (${c.position})` : '' }}
                  </option>
                </TomSelect>
                <small v-if="fieldError('customer_contact_id')" class="block input-error-text">{{
                  fieldError('customer_contact_id') }}</small>
                <Button type="button" size="sm" :variant="customerContacts.length === 0 ? 'primary' : 'outline-primary'"
                  class="inline-flex items-center gap-2 mt-2" :disabled="!form.customer_id || customerContactsLoading"
                  @click="openNewContactForm">
                  <Lucide icon="PlusCircle" class="w-4 h-4" />
                  Tambah Kontak Baru
                </Button>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Jabatan</FormLabel>
                <div class="mt-1 text-body-strong">{{ selectedContact?.position || '-' }}</div>
              </div>

              <div class="col-span-12 md:col-span-6">
                <FormLabel>Telepon</FormLabel>
                <div class="mt-1 text-body-strong">{{ selectedContact?.mobile || '-' }}</div>
              </div>

              <div class="col-span-12">
                <FormLabel>Alamat</FormLabel>
                <div class="mt-1 text-body-strong whitespace-pre-line">{{ selectedCustomer?.company_address || '-' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardSection>

    <CardSection title="Detail Pengiriman & Daftar Produk"
      description="Instrumen pengiriman, tujuan kirim dan daftar produk penawaran" icon="Boxes"
      icon-class="bg-indigo-100 text-indigo-600">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Type Pengiriman
                <RequiredAsterisk />
              </FormLabel>
              <div class="gap-3 grid grid-cols-2">
                <RadioCard v-for="opt in TYPE_PENGIRIMAN_OPTIONS" :key="opt.value" :model-value="form.type_pengiriman"
                  :value="opt.value" :title="opt.label" @update:model-value="selectTypePengiriman" />
              </div>
              <small v-if="fieldError('type_pengiriman')" class="block input-error-text">{{
                fieldError('type_pengiriman')
              }}</small>
            </div>

            <div>
              <FormLabel>Metode
                <RequiredAsterisk />
              </FormLabel>
              <div v-if="metodeOptions.length" class="gap-3 grid grid-cols-1 sm:grid-cols-3">
                <RadioCard v-for="opt in metodeOptions" :key="opt.value" :model-value="form.metode" :value="opt.value"
                  :title="opt.code" :description="opt.label" :disabled="isMetodeLocked"
                  @update:model-value="(v) => (form.metode = v)" />
              </div>
              <p v-else class="text-caption">Pilih Type Pengiriman terlebih dahulu.</p>
              <small v-if="itemsBasis === 'mixed'" class="block input-error-text">
                Produk loco dan franco tidak boleh dalam satu penawaran.
              </small>
              <small v-else-if="itemsBasis === 'missing'" class="block input-error-text">
                Sebagian produk belum memiliki basis harga (loco/franco). Lengkapi di Periode Harga.
              </small>
              <small v-if="fieldError('metode') || serverError('metode')" class="block input-error-text">{{
                fieldError('metode') || serverError('metode') }}</small>
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div>
              <FormLabel>Lokasi Pengiriman</FormLabel>
              <FormTextarea v-model="form.lokasi_pengiriman" :rows="3" :auto-resize="true"
                placeholder="Lokasi pengiriman..." />
            </div>

            <div>
              <FormLabel>Titik Serah Terima & T&C Bongkar</FormLabel>
              <FormTextarea v-model="form.keterangan" :rows="3" :auto-resize="true"
                placeholder="Titik serah terima & T&C bongkar..." />
            </div>
          </div>
        </div>
      </div>

      <div v-if="itemsBasis !== 'franco'" class="gap-4 grid grid-cols-12 mt-4">
        <div class="col-span-12">
          <div class="bg-slate-50 p-4 border border-slate-200 rounded-lg">
            <h4 class="mb-3 text-overline">Transport</h4>

            <p v-if="itemsBasis === null" class="text-caption">Pilih item penawaran untuk menentukan angkutan.</p>
            <p v-else-if="itemsBasis === 'mixed'" class="!text-rose-600 text-caption">
              Produk loco dan franco tidak boleh dalam satu penawaran.
            </p>
            <p v-else-if="itemsBasis === 'missing'" class="!text-rose-600 text-caption">
              Sebagian produk belum memiliki basis harga (loco/franco). Lengkapi di Periode Harga.
            </p>

            <template v-else>
              <div>
                <div class="flex flex-wrap items-center gap-4">
                  <span class="text-form-label">Moda:</span>
                  <FormCheck v-for="type in TRANSPORT_LEG_TYPES" :key="type">
                    <FormCheck.Input :id="'leg-' + type" type="checkbox" :checked="transportLegs[type].checked"
                      @change="onLegToggle(type, ($event.target as HTMLInputElement).checked)" />
                    <FormCheck.Label :htmlFor="'leg-' + type">{{ transportLegLabel(type) }}</FormCheck.Label>
                  </FormCheck>
                  <FormCheck>
                    <FormCheck.Input id="leg-pickup" type="checkbox" :checked="form.transport_mode === 'pickup'"
                      @change="onPickupToggle(($event.target as HTMLInputElement).checked)" />
                    <FormCheck.Label htmlFor="leg-pickup">Tanpa biaya transport</FormCheck.Label>
                  </FormCheck>
                </div>
                <small v-if="serverError('transport_mode')" class="block input-error-text">{{
                  serverError('transport_mode') }}</small>
                <small v-if="serverError('transports')" class="block input-error-text">{{
                  serverError('transports') }}</small>

                <div v-for="type in checkedLegTypes" :key="type"
                  class="bg-white mt-3 p-4 border border-slate-200 rounded-lg">
                  <h5 class="mb-3 text-form-label">{{ transportLegLabel(type) }}</h5>
                  <div class="gap-4 grid grid-cols-12">
                    <div class="col-span-12 md:col-span-4">
                      <FormLabel>Transportir</FormLabel>
                      <TomSelect v-model="transportLegs[type].transporter_id" :key="legSelectKey" :options="{
                        placeholder: 'Pilih Transportir...',
                        dropdownParent: 'body' as const,
                      }" class="w-full">
                        <option v-for="t in transportirs" :key="t.id" :value="String(t.id)">{{ t.company_name }}
                        </option>
                      </TomSelect>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                      <FormLabel>Wilayah Angkut</FormLabel>
                      <TomSelect v-model="transportLegs[type].transport_area_id" :key="legSelectKey" :options="{
                        placeholder: 'Pilih Wilayah...',
                        dropdownParent: 'body' as const,
                      }" class="w-full">
                        <option v-for="w in wilayahs" :key="w.id" :value="String(w.id)">
                          {{ w.province?.name || w.provinsi?.nama_provinsi }} - {{ w.regency?.name ||
                            w.kabupaten?.nama_kabupaten }} - {{ w.name }}
                        </option>
                      </TomSelect>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                      <FormLabel>Volume</FormLabel>
                      <TomSelect v-model="transportLegs[type].volume_id" :key="legSelectKey" :options="{
                        placeholder: 'Pilih Volume...',
                        dropdownParent: 'body' as const,
                      }" class="w-full">
                        <option v-for="v in volumes" :key="v.id" :value="String(v.id)">{{ v.volume }}</option>
                      </TomSelect>
                    </div>

                    <div class="col-span-12">
                      <span class="text-form-label">Harga angkut per m³: </span>
                      <span v-if="transportLegs[type].status === 'ready'" class="text-body-strong">
                        {{ formatCurrency(Number(transportLegs[type].check?.price ?? 0)) }}
                      </span>
                      <span v-else-if="transportLegs[type].status === 'loading'" class="text-body">Memuat tarif…</span>
                      <span v-else-if="transportLegs[type].status === 'unavailable'" class="!text-rose-600 text-body">
                        Tarif belum siap. Margin belum diisi Logistik
                      </span>
                      <span v-else-if="transportLegs[type].status === 'not_found'" class="!text-rose-600 text-body">
                        Tarif tidak ditemukan
                      </span>
                      <span v-else-if="transportLegs[type].status === 'error'" class="!text-rose-600 text-body">
                        Gagal memuat tarif
                      </span>
                      <span v-else class="text-body">-</span>
                      <p v-if="isEdit && !legSelectionChanged(type) && transportLegs[type].status !== 'ready'"
                        class="mt-1 text-caption">
                        Tarif tersimpan tetap dipakai selama pilihan ini tidak diubah.
                      </p>
                      <small v-if="legServerError(type)" class="block input-error-text">{{ legServerError(type)
                      }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <hr class="my-4" />

      <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-2">
        <p class="!text-slate-500 text-body">
          Susun daftar produk penawaran terlebih dahulu.
        </p>
        <Button type="button" variant="primary" class="inline-flex justify-center items-center gap-2 whitespace-nowrap"
          @click="openItemsModal">
          <Lucide icon="Plus" class="w-4 h-4" />
          {{ form.items.length ? 'Ubah Item Penawaran' : 'Susun Item Penawaran' }}
        </Button>
      </div>
      <div class="mt-4 border border-slate-200 rounded-xl overflow-x-auto">
        <table class="divide-y divide-slate-200 w-full min-w-[960px]">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 w-12 text-form-label text-center">No</th>
              <th class="px-4 py-3 text-form-label text-left">Produk</th>
              <th class="px-4 py-3 w-28 text-form-label text-right">Quantity</th>
              <th class="px-4 py-3 w-40 text-form-label text-right">Harga Material/m³</th>
              <th class="px-4 py-3 w-40 text-form-label text-right">Transport/m³</th>
              <th class="px-4 py-3 w-40 text-form-label text-right">Harga Final/m³</th>
              <th class="px-4 py-3 w-44 text-form-label text-right">Subtotal</th>
            </tr>
          </thead>

          <tbody class="bg-white divide-y divide-slate-200">
            <template v-for="(item, idx) in form.items" :key="idx">
              <tr class="hover:bg-slate-50 transition">
                <td class="px-4 py-3 text-center num-sm">{{ idx + 1 }}.</td>
                <td class="px-4 py-3">
                  <span v-if="item.source_name"
                    class="inline-block bg-slate-100 mb-1 px-2 rounded-full text-caption">Source: {{ item.source_name }}</span>
                  <div class="flex items-start gap-1.5">
                    <span class="text-body-strong">{{ produkName(item.product_id) }}</span>
                    <Tippy v-if="itemsBasis === 'mixed' || itemsBasis === 'missing'"
                      :content="item.cogs_basis ? `Basis harga: ${transportBasisLabel(item.cogs_basis)}` : 'Belum ada basis harga (loco/franco)'"
                      as="span" class="inline-flex mt-0.5 text-danger cursor-help shrink-0">
                      <Lucide icon="AlertCircle" class="w-4 h-4" />
                    </Tippy>
                  </div>
                  <div v-if="produkSize(item.product_id)" class="text-caption">{{ produkSize(item.product_id) }}</div>
                </td>
                <td class="px-4 py-3 text-right num-sm">
                  <NumberField v-if="itemsEditable" v-model="item.quantity" :min="0" :decimals="2"
                    :error="itemFieldError(idx, 'quantity')" />
                  <template v-else>{{ Number(item.quantity).toLocaleString('id-ID') }}</template>
                </td>
                <td class="px-4 py-3 text-right num-sm">
                  <Tippy v-if="itemsEditable && Number(item.harga_price_list) > 0"
                    :content="`Harga price list: ${formatCurrency(item.harga_price_list)}`" as="div">
                    <CurrencyField v-model="item.unit_price" :error="itemFieldError(idx, 'unit_price')" />
                  </Tippy>
                  <CurrencyField v-else-if="itemsEditable" v-model="item.unit_price"
                    :error="itemFieldError(idx, 'unit_price')" />
                  <template v-else>{{ formatCurrency(item.unit_price || 0) }}</template>
                </td>
                <td class="px-4 py-3 text-right num-sm">
                  <template v-if="itemTransportPrice(item) !== null">{{ formatCurrency(itemTransportPrice(item) ?? 0)
                  }}</template>
                  <span v-else-if="requiresFreshPricing && itemsBasis === 'franco'" class="!text-rose-600 text-caption">
                    Margin transport belum diisi Logistik
                  </span>
                  <template v-else>—</template>
                </td>
                <td class="px-4 py-3 text-right num-sm">
                  {{ itemFinalPrice(item) === null ? '—' : formatCurrency(itemFinalPrice(item) ?? 0) }}
                </td>
                <td class="px-4 py-3 text-right num-sm">
                  {{ itemFinalTotal(item) === null ? '—' : formatCurrency(itemFinalTotal(item) ?? 0) }}
                </td>
              </tr>
              <tr v-if="itemServerError(idx)">
                <td></td>
                <td colspan="6" class="px-4 pb-3">
                  <small class="block input-error-text">{{ itemServerError(idx) }}</small>
                </td>
              </tr>
            </template>
            <tr v-if="form.items.length === 0">
              <td colspan="7" class="px-4 py-10 !text-slate-400 text-body text-center">
                Belum ada item. Klik "Susun Item Penawaran".
              </td>
            </tr>
          </tbody>

          <tfoot v-if="form.items.length" class="bg-slate-50 border-slate-200 border-t">
            <tr>
              <td class="px-4 py-3 text-body-strong text-right" colspan="2">Total</td>
              <td class="px-4 py-3 text-right num-sm">{{ totalQuantity.toLocaleString('id-ID') }}</td>
              <td class="px-4 py-3" colspan="3"></td>
              <td class="px-4 py-3 text-right num-sm">{{ dppPreview === null ? '—' : formatCurrency(dppPreview) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
      <small v-if="serverError('items')" class="block mt-2 input-error-text">{{ serverError('items') }}</small>
    </CardSection>

    <CardSection title="Pembayaran & Lainnya" description="Metode pembayaran dan detail lainnya" icon="Wallet"
      icon-class="bg-amber-100 text-amber-600">
      <div class="gap-4 grid grid-cols-12">
        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div class="gap-4 grid grid-cols-12">
              <div class="col-span-12" :class="isProenergi ? 'md:col-span-6' : ''">
                <FormLabel>Tipe Pembayaran
                  <RequiredAsterisk />
                </FormLabel>
                <FormSelect v-model="form.tipe_pembayaran" class="w-full" :class="inputClass('tipe_pembayaran')">
                  <option value="" disabled>Pilih…</option>
                  <option value="COD">COD</option>
                  <option value="CBD">CBD</option>
                  <option value="TOP 7">TOP 7</option>
                  <option value="TOP 14">TOP 14</option>
                  <option v-if="activeBrand === 'tds'" value="TOP 30">TOP 30</option>
                  <option value="CUSTOM">CUSTOM</option>
                </FormSelect>
                <small v-if="fieldError('tipe_pembayaran')" class="block input-error-text">{{
                  fieldError('tipe_pembayaran')
                }}</small>
              </div>

              <div v-if="isProenergi" class="col-span-12 md:col-span-6">
                <FormLabel>Acuan Pembayaran</FormLabel>
                <FormSelect v-model="form.acuan_pembayaran" class="w-full">
                  <option value="" disabled>Pilih…</option>
                  <option value="After loading">After loading</option>
                  <option value="Before loading">Before loading</option>
                  <option value="After unloading">After unloading</option>
                  <option value="Before unloading">Before unloading</option>
                  <option value="After invoice received">After invoice received</option>
                </FormSelect>
              </div>
            </div>

            <transition name="fade">
              <div v-if="form.tipe_pembayaran === 'CUSTOM'"
                class="bg-slate-50 mt-4 p-4 border border-slate-200 rounded-lg">
                <h4 class="mb-3 text-overline">Detail Pembayaran Custom</h4>
                <div class="flex flex-col gap-4">
                  <div class="">
                    <FormLabel>Down Payment (%)</FormLabel>
                    <div class="flex flex-wrap items-center gap-2">
                      <NumberField class="w-20" v-model="form.dp_persen" placeholder="100" suffix="%" :min="0"
                        :max="100" :decimals="0" :error="fieldError('dp_persen')" />
                      <span class="text-body">After</span>
                      <FormInput v-model="form.dp_keterangan" type="text" class="flex-1 min-w-40"
                        placeholder="PO / 7 days" />
                    </div>
                    <small v-if="fieldError('dp_persen')" class="block input-error-text">{{
                      fieldError('dp_persen')
                    }}</small>
                  </div>

                  <div class="">
                    <FormLabel>Repayment (%)</FormLabel>
                    <div class="flex flex-wrap items-center gap-2">
                      <FormInput v-model="form.repayment_persen" type="text" inputmode="numeric" placeholder="80"
                        class="w-20 text-right" :class="inputClass('repayment_persen')"
                        @input="formatNumeric(form, 'repayment_persen', $event)" />
                      <span class="text-body">% TOP</span>
                      <FormInput v-model="form.repayment_hari" type="text" inputmode="numeric" placeholder="7"
                        class="w-20 text-right" @input="formatNumeric(form, 'repayment_hari', $event)" />
                      <span class="text-body">days</span>
                    </div>
                    <small v-if="fieldError('repayment_persen')" class="block input-error-text">{{
                      fieldError('repayment_persen')
                    }}</small>
                  </div>
                </div>
                <p class="mt-2 text-caption">Contoh: <b>DP 20% after PO</b>, <b>Repayment 80% TOP 7 days</b>.
                </p>
              </div>
            </transition>

            <div>
              <FormLabel>Metode Pemesanan</FormLabel>
              <FormInput v-model="form.order_method" type="text" placeholder="Metode pemesanan…" />
            </div>
          </div>
        </div>

        <div class="col-span-12 md:col-span-6">
          <div class="space-y-3 p-4 border border-slate-200 rounded-xl">
            <div class="flex flex-col gap-4">
              <div>
                <FormLabel>Toleransi Penyusutan</FormLabel>
                <div class="relative">
                  <FormInput v-model="form.toleransi_penyusutan" type="text" inputmode="decimal" placeholder="0"
                    class="pr-8 text-right" @input="formatDecimalInput(form, 'toleransi_penyusutan', $event)" />
                  <span class="top-2.5 right-3 absolute text-caption">%</span>
                </div>
              </div>

              <div>
                <FormLabel>Abrasi</FormLabel>
                <FormInput v-model="form.abrasi" type="text" placeholder="Contoh isian: 0-5% atau sesuai kondisi" />
              </div>

              <div>
                <CurrencyField v-model="form.refund" label="Refund" />
              </div>

              <div>
                <CurrencyField v-model="form.other_cost" label="Other Cost" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </CardSection>

    <div class="gap-6 grid grid-cols-1 lg:grid-cols-2">
      <CardSection title="Catatan & Syarat" description="Informasi tambahan penawaran" icon="StickyNote"
        icon-class="bg-rose-100 text-rose-600">
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

      <CardSection title="Lampiran Tambahan" description="Rincian tambahan di luar template standar" icon="Paperclip"
        icon-class="bg-rose-100 text-rose-600">
        <div>
          <FormLabel>Lampiran Tambahan</FormLabel>
          <FormTextarea v-model="form.lampiran_tambahan" :rows="9" placeholder="Lampiran tambahan…" />
        </div>
      </CardSection>
    </div>

    <ConfirmDialog :open="recalcConfirmOpen" title="Hitung Ulang Harga Transport?"
      description="Harga transport akan dihitung ulang mengikuti tarif/harga terbaru. Lanjutkan?"
      confirm-text="Ya, Simpan" icon="Calculator" icon-class="bg-primary/10 text-primary" variant="primary"
      :loading="loading" @close="recalcConfirmOpen = false" @confirm="confirmRecalculate" />

    <ItemsGeneratorModal :open="itemsModalOpen" :price-period-id="selectedPeriodId"
      :period-label="selectedPeriodLabelText" :cabangs="cabangs" :produks="produks" :initial-items="form.items"
      :use-pe="isProenergi" @close="itemsModalOpen = false" @save="onItemsSaved" />

    <FormModal :open="newContactOpen" title="Tambah Kontak Baru"
      description="Kontak baru ini langsung tersimpan di master data customer." :loading="newContactSaving"
      :error="newContactError" submit-text="Tambah" submit-icon="PlusCircle" @close="newContactOpen = false"
      @submit="submitNewContact">
      <div class="space-y-3">
        <div>
          <FormLabel>Nama Lengkap
            <RequiredAsterisk />
          </FormLabel>
          <FormInput v-model="newContactForm.full_name" placeholder="Nama lengkap" />
        </div>
        <div>
          <FormLabel>Posisi/Jabatan</FormLabel>
          <FormInput v-model="newContactForm.position" placeholder="Contoh: Purchasing Manager" />
        </div>
        <div class="gap-3 grid grid-cols-2">
          <div>
            <FormLabel>Telepon</FormLabel>
            <FormInput v-model="newContactForm.phone" placeholder="021-xxxxxxx" />
          </div>
          <div>
            <FormLabel>Mobile</FormLabel>
            <FormInput v-model="newContactForm.mobile" placeholder="08xx-xxxx-xxxx" />
          </div>
        </div>
        <div>
          <FormLabel>Email</FormLabel>
          <FormInput v-model="newContactForm.email" type="email" placeholder="nama@email.com" />
        </div>
      </div>
    </FormModal>

    <template #sidebar>
      <CardSection title="Ringkasan Harga" description="DPP, PPN, dan total penawaran" icon="Calculator"
        icon-class="bg-emerald-100 text-emerald-600">
        <dl class="divide-y divide-slate-100">
          <div class="flex justify-between items-baseline gap-3 py-2">
            <dt class="text-body-strong">DPP</dt>
            <dd class="text-right num-md">{{ dppPreview === null ? '-' : formatCurrency(dppPreview) }}</dd>
          </div>
          <div class="flex justify-between items-baseline gap-3 py-2">
            <dt class="text-body-strong">PPN (11%)</dt>
            <dd class="text-right num-md">{{ ppnPreview === null ? '-' : formatCurrency(ppnPreview) }}</dd>
          </div>
          <div class="flex justify-between items-baseline gap-3 py-2">
            <dt class="text-section-title">TOTAL</dt>
            <dd class="!text-emerald-700 text-lg text-right num-md">{{
              totalPreview === null ? '-' : formatCurrency(totalPreview) }}</dd>
          </div>
        </dl>
        <p v-if="isEdit && !willRecalculate" class="mt-2 text-caption">Harga transport mengikuti harga tersimpan.</p>
        <p v-else-if="requiresFreshPricing" class="mt-2 text-caption">
          Pratinjau. Angka final dihitung server saat disimpan.
        </p>

        <div class="flex flex-col gap-3 mt-4 pt-4 border-slate-100 border-t">
          <Button type="submit" variant="primary" class="inline-flex justify-center items-center gap-2"
            :disabled="loading || isEditingExpiredPeriod">
            <Lucide v-if="loading" icon="Loader2" class="w-4 h-4 animate-spin" />
            <Lucide v-else icon="Save" class="w-4 h-4" />
            {{ isEdit ? 'Update Penawaran' : 'Simpan Penawaran' }}
          </Button>
          <Button type="button" variant="outline-secondary" class="inline-flex justify-center items-center gap-2"
            :disabled="loading" @click="goBack">
            <Lucide icon="ArrowLeft" class="w-4 h-4" />
            Batal
          </Button>
        </div>
      </CardSection>
    </template>
  </FormPage>
</template>
