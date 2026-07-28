<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { createResourceApi } from '@/utils/resourceApi'

/* Type: dokumen customer (customer_document_types + customer_documents) */
interface CustomerDocumentType {
  id: number
  code: string
  name: string
  is_active: boolean
  requires_number: boolean
}
interface CustomerDocumentRecord {
  id: number
  id_customer: number
  id_document_type: number
  document_type: { id: number; code: string; name: string; requires_number: boolean } | null
  document_number: string | null
  file_name: string
  file_path: string
  url: string | null
  uploaded_at: string | null
  uploaded_by: { id: number; name: string } | null
}
interface DocumentRowState {
  file: File | null
  documentNumber: string
  editing: boolean
  uploading: boolean
  error: string
}

/* Type: kontak customer (customer_contact_types + customer_contacts) */
interface CustomerContactType {
  id: number
  code: string
  name: string
  is_active: boolean
}
interface CustomerContactRecord {
  id: number
  id_customer: number
  id_contact_type: number
  contact_type: { id: number; code: string; name: string } | null
  id_lcr: number | null
  full_name: string
  position: string | null
  phone: string | null
  mobile: string | null
  email: string | null
  created_at: string | null
  updated_at: string | null
}

const props = defineProps<{
  idCustomer: number
  customer: any
  hasOnboardingData: boolean
}>()

const { success, error: notifyError } = useNotification()

/* Helper umum */
function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}
function humanize(value: unknown): string | null {
  if (value === null || value === undefined || value === '') return null
  return String(value)
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}
function regionName(item: { name?: string } | null | undefined): string | null {
  return item?.name ?? null
}

/* Computed: ringkasan Data Customer, sumber dari data hasil submit form
   onboarding publik (customers, customer_addresses [registered_npwp],
   customer_payment, customer_logistic_claims) -- dipisah per kartu supaya
   tiap kartu bisa punya layout sendiri di template. */
const corporateRows = computed(() => {
  const cust = props.customer || {}
  return [
    { label: 'Nama Perusahaan', value: dash(cust.company_name) },
    { label: 'Holding', value: dash(cust.parent_company) },
    { label: 'Alamat Head Office', value: dash(cust.company_address) },
    { label: 'Provinsi', value: dash(regionName(cust.province)) },
    { label: 'Kota/Kabupaten', value: dash(regionName(cust.regency)) },
    { label: 'Kecamatan', value: dash(regionName(cust.district)) },
    { label: 'Kelurahan', value: dash(regionName(cust.village)) },
    { label: 'Kode Pos', value: dash(cust.postal_code) },
    { label: 'Telepon', value: dash(cust.phone) },
    { label: 'Fax', value: dash(cust.fax) },
    { label: 'Email', value: dash(cust.email) },
    { label: 'Website', value: dash(cust.website) },
    {
      label: 'Jenis Usaha',
      value: dash(cust.business_type === 'Other' ? cust.business_type_other : cust.business_type),
    },
    {
      label: 'Kepemilikan',
      value: dash(cust.ownership_type === 'Other' ? cust.ownership_type_other : cust.ownership_type),
    },
    {
      label: 'Incoterms',
      value: dash(cust.inco_terms === 'Other' ? cust.inco_terms_other : cust.inco_terms),
    },
  ]
})

const npwpAddressRows = computed(() => {
  const cust = props.customer || {}
  const npwpAddress = (cust.addresses || []).find((a: any) => a.address_type === 'registered_npwp') || {}
  return [
    { label: 'Alamat NPWP', value: dash(npwpAddress.address_line) },
    { label: 'Provinsi', value: dash(regionName(npwpAddress.province)) },
    { label: 'Kota/Kabupaten', value: dash(regionName(npwpAddress.regency)) },
    { label: 'Kecamatan', value: dash(regionName(npwpAddress.district)) },
    { label: 'Kelurahan', value: dash(regionName(npwpAddress.village)) },
    { label: 'Kode Pos', value: dash(npwpAddress.postal_code) },
  ]
})

const paymentRows = computed(() => {
  const pay = props.customer?.payment || {}
  return [
    { label: 'Pricing Method', value: dash(pay.calculate_method) },
    {
      label: 'Payment Method',
      value: dash(pay.payment_method === 'Other' ? pay.payment_method_other : pay.payment_method),
    },
    { label: 'Term of Payment', value: dash(pay.payment_term) },
    { label: 'Term Days', value: pay.payment_term === 'CREDIT' ? dash(pay.payment_term_days) : '-' },
    {
      label: 'Term Basis',
      value: pay.payment_term === 'CREDIT' ? dash(humanize(pay.payment_term_basis)) : '-',
    },
    { label: 'Bank Name', value: dash(pay.bank_name) },
    { label: 'Currency', value: dash(pay.currency) },
    { label: 'Bank Address', value: dash(pay.bank_address) },
    { label: 'Account Number', value: dash(pay.account_number) },
    {
      label: 'Credit Facility',
      value: pay.credit_facility ? `Ya${pay.creditor ? ` (${pay.creditor})` : ''}` : 'Tidak',
    },
    { label: 'Tax Invoice', value: pay.invoice ? 'Ya' : 'Tidak' },
    { label: 'Catatan', value: dash(pay.extra_notes) },
  ]
})

const logisticsRows = computed(() => {
  const s = props.customer?.logistik || {}
  return [
    {
      label: 'Site Environment',
      value: dash(s.site_environment === 'other' ? s.site_environment_other : humanize(s.site_environment)),
    },
    { label: 'Site Environment Notes', value: dash(s.site_environment_notes) },
    {
      label: 'Storage Type',
      value: dash(s.storage_type === 'other' ? s.storage_type_other : humanize(s.storage_type)),
    },
    { label: 'Storage Notes', value: dash(s.storage_notes) },
    {
      label: 'Operating Hours',
      value: dash(s.operating_hours === 'other' ? s.operating_hours_other : humanize(s.operating_hours)),
    },
    {
      label: 'Quality Checking',
      value: dash(s.quality_checking_method === 'other' ? s.quality_checking_notes : humanize(s.quality_checking_method)),
    },
    {
      label: 'Quantity Checking',
      value: dash(s.quantity_checking_method === 'other' ? s.quantity_checking_notes : humanize(s.quantity_checking_method)),
    },
    {
      label: 'Max Truck Capacity (m³)',
      value: (s.max_truck_capacity_min ?? null) !== null || (s.max_truck_capacity_max ?? null) !== null
        ? `${dash(s.max_truck_capacity_min)} - ${dash(s.max_truck_capacity_max)}`
        : '-',
    },
    { label: 'Supports Vessel Delivery', value: s.supports_vessel_delivery ? 'Ya' : 'Tidak' },
    { label: 'Product Notes', value: dash(s.product_notes) },
    { label: 'Est. Monthly Volume', value: dash(s.estimated_monthly_volume) },
  ]
})

/* State: Dokumen Customer (customer_document_types + customer_documents) */
const documentTypesApi = createResourceApi('/customer-document-types')
const customerDocumentsApi = createResourceApi(`/customers/${props.idCustomer}/documents`)

const documentTypesLoading = ref(true)
const documentsLoading = ref(true)
const documentTypes = ref<CustomerDocumentType[]>([])
const customerDocuments = ref<CustomerDocumentRecord[]>([])
const documentRowState = reactive<Record<number, DocumentRowState>>({})

const deleteDocumentDialogOpen = ref(false)
const deleteDocumentTarget = ref<CustomerDocumentRecord | null>(null)
const deleteDocumentLoading = ref(false)

const activeDocumentTypes = computed(() => documentTypes.value.filter(t => t.is_active))
const documentRows = computed(() =>
  activeDocumentTypes.value.map(type => ({
    type,
    document: customerDocuments.value.find(d => d.id_document_type === type.id) ?? null,
  })),
)

async function fetchDocumentTypes() {
  documentTypesLoading.value = true
  try {
    const { data } = await documentTypesApi.getAll({ as_list: true })
    documentTypes.value = Array.isArray(data) ? data : []
    documentTypes.value.forEach(type => rowState(type.id))
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat jenis dokumen customer.')
  } finally {
    documentTypesLoading.value = false
  }
}

async function fetchCustomerDocuments() {
  documentsLoading.value = true
  try {
    const { data } = await customerDocumentsApi.getAll()
    customerDocuments.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat dokumen customer.')
  } finally {
    documentsLoading.value = false
  }
}

function rowState(typeId: number): DocumentRowState {
  if (!documentRowState[typeId]) {
    documentRowState[typeId] = { file: null, documentNumber: '', editing: false, uploading: false, error: '' }
  }
  return documentRowState[typeId]
}

function startDocumentUpload(typeId: number) {
  const state = rowState(typeId)
  state.editing = true
  state.file = null
  state.documentNumber = ''
  state.error = ''
}

function cancelDocumentUpload(typeId: number) {
  const state = rowState(typeId)
  state.editing = false
  state.file = null
  state.documentNumber = ''
  state.error = ''
}

/* Action: upload/replace dokumen. Backend cuma sediakan create+delete (tidak
   ada endpoint replace/update), jadi "Ganti" diimplementasikan sebagai upload
   dokumen baru lalu hapus dokumen lama milik jenis yang sama setelah upload
   sukses (best-effort, tidak memblokir sukses utama kalau cleanup gagal). */
async function submitDocumentUpload(row: { type: CustomerDocumentType; document: CustomerDocumentRecord | null }) {
  const state = rowState(row.type.id)

  if (!state.file) {
    state.error = 'Pilih file terlebih dahulu.'
    return
  }
  if (row.type.requires_number && !state.documentNumber.trim()) {
    state.error = 'Nomor dokumen wajib diisi untuk jenis dokumen ini.'
    return
  }

  state.uploading = true
  state.error = ''
  try {
    const formData = new FormData()
    formData.append('id_document_type', String(row.type.id))
    formData.append('file', state.file)
    if (state.documentNumber.trim()) {
      formData.append('document_number', state.documentNumber.trim())
    }

    await customerDocumentsApi.store(formData)

    const previousDocument = row.document
    if (previousDocument) {
      await customerDocumentsApi.destroy(previousDocument.id).catch(() => null)
    }

    await fetchCustomerDocuments()
    cancelDocumentUpload(row.type.id)
    success('Berhasil', `Dokumen ${row.type.name} berhasil ${previousDocument ? 'diganti' : 'diunggah'}.`)
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      state.error = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      state.error = e.response?.data?.message ?? 'Gagal mengunggah dokumen.'
    }
  } finally {
    state.uploading = false
  }
}

function confirmDeleteDocument(document: CustomerDocumentRecord) {
  deleteDocumentTarget.value = document
  deleteDocumentDialogOpen.value = true
}

async function performDeleteDocument() {
  const target = deleteDocumentTarget.value
  if (!target) return

  deleteDocumentLoading.value = true
  try {
    await customerDocumentsApi.destroy(target.id)
    customerDocuments.value = customerDocuments.value.filter(d => d.id !== target.id)
    success('Berhasil', 'Dokumen berhasil dihapus.')
    deleteDocumentDialogOpen.value = false
    deleteDocumentTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus dokumen.')
  } finally {
    deleteDocumentLoading.value = false
  }
}

function formatDocumentDate(value: string | null) {
  if (!value) return '-'
  try {
    return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch {
    return value
  }
}

/* State: Kontak Customer (customer_contact_types + customer_contacts) --
   dipakai juga untuk kartu PIC (satu sumber data, satu tampilan, tidak lagi
   dipisah jadi "PIC Details" read-only + "Kontak Customer" tabel CRUD). */
const contactTypesApi = createResourceApi('/customer-contact-types')
const customerContactsApi = createResourceApi(`/customers/${props.idCustomer}/contacts`)

const contactTypesLoading = ref(true)
const contactsLoading = ref(true)
const contactTypes = ref<CustomerContactType[]>([])
const customerContacts = ref<CustomerContactRecord[]>([])

const contactFormOpen = ref(false)
const contactFormMode = ref<'create' | 'edit'>('create')
const contactFormSaving = ref(false)
const contactFormError = ref<string | null>(null)
const contactFormErrors = ref<Record<string, string[]>>({})
const contactForm = reactive({
  id: null as number | null,
  id_contact_type: '' as number | '',
  full_name: '',
  position: '',
  phone: '',
  mobile: '',
  email: '',
})

const deleteContactDialogOpen = ref(false)
const deleteContactTarget = ref<CustomerContactRecord | null>(null)
const deleteContactLoading = ref(false)

const activeContactTypes = computed(() => contactTypes.value.filter(t => t.is_active))

async function fetchContactTypes() {
  contactTypesLoading.value = true
  try {
    const { data } = await contactTypesApi.getAll({ as_list: true })
    contactTypes.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat jenis kontak customer.')
  } finally {
    contactTypesLoading.value = false
  }
}

async function fetchCustomerContacts() {
  contactsLoading.value = true
  try {
    const { data } = await customerContactsApi.getAll()
    customerContacts.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal memuat kontak customer.')
  } finally {
    contactsLoading.value = false
  }
}

function resetContactForm() {
  contactFormError.value = null
  contactFormErrors.value = {}
  Object.assign(contactForm, {
    id: null,
    id_contact_type: '',
    full_name: '',
    position: '',
    phone: '',
    mobile: '',
    email: '',
  })
}

function openCreateContact() {
  contactFormMode.value = 'create'
  resetContactForm()
  contactFormOpen.value = true
}

function openEditContact(contact: CustomerContactRecord) {
  contactFormMode.value = 'edit'
  resetContactForm()
  Object.assign(contactForm, {
    id: contact.id,
    id_contact_type: contact.id_contact_type,
    full_name: contact.full_name,
    position: contact.position ?? '',
    phone: contact.phone ?? '',
    mobile: contact.mobile ?? '',
    email: contact.email ?? '',
  })
  contactFormOpen.value = true
}

function closeContactForm() {
  contactFormOpen.value = false
}

async function submitContactForm() {
  contactFormError.value = null
  contactFormErrors.value = {}

  if (!contactForm.id_contact_type) {
    contactFormError.value = 'Tipe kontak wajib dipilih.'
    return
  }
  if (!contactForm.full_name.trim()) {
    contactFormError.value = 'Nama wajib diisi.'
    return
  }

  const payload = {
    id_contact_type: contactForm.id_contact_type,
    full_name: contactForm.full_name.trim(),
    position: contactForm.position.trim() || null,
    phone: contactForm.phone.trim() || null,
    mobile: contactForm.mobile.trim() || null,
    email: contactForm.email.trim() || null,
  }

  contactFormSaving.value = true
  try {
    if (contactFormMode.value === 'edit' && contactForm.id) {
      const { data } = await customerContactsApi.update(contactForm.id, payload)
      const index = customerContacts.value.findIndex(c => c.id === data.id)
      if (index !== -1) customerContacts.value[index] = data
      success('Berhasil', 'Kontak berhasil diperbarui.')
    } else {
      const { data } = await customerContactsApi.store(payload)
      customerContacts.value.push(data)
      success('Berhasil', 'Kontak berhasil ditambahkan.')
    }
    contactFormOpen.value = false
  } catch (e: any) {
    if (e.response?.status === 422) {
      contactFormErrors.value = e.response?.data?.errors || {}
      contactFormError.value = Object.values(contactFormErrors.value)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      contactFormError.value = e.response?.data?.message ?? 'Gagal menyimpan kontak.'
    }
  } finally {
    contactFormSaving.value = false
  }
}

function confirmDeleteContact(contact: CustomerContactRecord) {
  deleteContactTarget.value = contact
  deleteContactDialogOpen.value = true
}

async function performDeleteContact() {
  const target = deleteContactTarget.value
  if (!target) return

  deleteContactLoading.value = true
  try {
    await customerContactsApi.destroy(target.id)
    customerContacts.value = customerContacts.value.filter(c => c.id !== target.id)
    success('Berhasil', 'Kontak berhasil dihapus.')
    deleteContactDialogOpen.value = false
    deleteContactTarget.value = null
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus kontak.')
  } finally {
    deleteContactLoading.value = false
  }
}

onMounted(fetchDocumentTypes)
onMounted(fetchCustomerDocuments)
onMounted(fetchContactTypes)
onMounted(fetchCustomerContacts)
</script>

<template>
  <div v-if="!hasOnboardingData"
    class="flex flex-col items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
    <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
    <div class="font-strong">Belum ada data onboarding</div>
    <div class="font-body max-w-md">
      Customer ini belum mengisi formulir onboarding. Data corporate, alamat NPWP, payment, dan logistik akan tampil
      di sini setelah customer submit formulir tersebut.
    </div>
  </div>

  <div v-else class="grid grid-cols-2 gap-6">
    <div class="grid gap-6 lg:grid-cols-1">
      <CardSection title="Corporate Details" description="Identitas perusahaan & alamat NPWP terdaftar."
        icon="Building2" icon-class="bg-violet-100 text-violet-600">
        <div class="grid gap-y-3 gap-x-8 sm:grid-cols-2">
          <div v-for="row in corporateRows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">
          <div class="font-section mb-3">Alamat NPWP (Registered Address)</div>
          <div class="grid gap-y-3 gap-x-8 sm:grid-cols-2">
            <div v-for="row in npwpAddressRows" :key="row.label"
              class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
              <span class="font-label">{{ row.label }}</span>
              <span class="font-strong text-right">{{ row.value }}</span>
            </div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Logistic Info" description="Kondisi site, storage, dan kapasitas pengiriman." icon="Truck"
        icon-class="bg-amber-100 text-amber-600">
        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="row in logisticsRows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </div>
      </CardSection>
    </div>

    <div class="grid gap-6 lg:grid-cols-1">
      <CardSection title="Payment & Banking" description="Metode, term, dan rekening bank customer." icon="CreditCard"
        icon-class="bg-emerald-100 text-emerald-600">
        <div class="space-y-1.5">
          <div v-for="row in paymentRows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-100 pb-1.5">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </div>
      </CardSection>

      <CardSection title="Kontak Customer" description="Kelola PIC/kontak customer." icon="Users"
        icon-class="bg-cyan-100 text-cyan-600">
        <template #action>
          <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2" @click="openCreateContact">
            <Lucide icon="Plus" class="h-4 w-4" />
            Tambah Kontak
          </Button>
        </template>

        <div v-if="contactTypesLoading || contactsLoading"
          class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
          <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
          <span class="font-body">Memuat kontak...</span>
        </div>

        <div v-else-if="customerContacts.length === 0"
          class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
          <Lucide icon="Inbox" class="h-6 w-6 text-slate-400" />
          <div class="font-body">Belum ada kontak yang ditambahkan.</div>
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2">
          <div v-for="c in customerContacts" :key="c.id"
            class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-100 font-strong text-cyan-700">
              {{ (c.full_name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="font-strong truncate">{{ c.full_name }}</div>
              <div class="font-caption truncate">
                {{ c.contact_type?.name || '-' }}{{ c.position ? ' · ' + c.position : '' }}
              </div>
              <div class="mt-1 flex flex-wrap gap-3">
                <a v-if="c.phone" :href="`tel:${c.phone}`"
                  class="inline-flex items-center gap-1 font-caption !text-primary">
                  <Lucide icon="Phone" class="h-3.5 w-3.5" /> {{ c.phone }}
                </a>
                <a v-if="c.mobile" :href="`tel:${c.mobile}`"
                  class="inline-flex items-center gap-1 font-caption !text-primary">
                  <Lucide icon="Smartphone" class="h-3.5 w-3.5" /> {{ c.mobile }}
                </a>
              </div>
              <p v-if="c.email" class="font-caption mt-1 truncate">{{ c.email }}</p>
            </div>
            <div class="flex shrink-0 flex-col gap-1">
              <Button size="sm" variant="soft-pending" title="Edit" class="!h-8 !w-8 !p-0 !shadow-none"
                @click="openEditContact(c)">
                <Lucide icon="Edit" class="h-4 w-4" />
              </Button>
              <Button size="sm" variant="soft-danger" title="Hapus" class="!h-8 !w-8 !p-0 !shadow-none"
                @click="confirmDeleteContact(c)">
                <Lucide icon="Trash2" class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </CardSection>

      <CardSection title="Dokumen Lampiran"
        description="Upload dan kelola dokumen legal customer (NIB, NPWP, Akta Pendirian, dst)." icon="FileCheck2"
        icon-class="bg-indigo-100 text-indigo-600">
        <div v-if="documentTypesLoading || documentsLoading"
          class="flex min-h-[120px] items-center justify-center gap-3 text-slate-500">
          <Lucide icon="Loader2" class="h-5 w-5 animate-spin" />
          <span class="font-body">Memuat dokumen...</span>
        </div>

        <div v-else-if="documentRows.length === 0"
          class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
          <Lucide icon="Inbox" class="h-8 w-8 text-slate-400" />
          <div class="font-body">Belum ada jenis dokumen yang aktif.</div>
        </div>

        <div v-else class="space-y-3">
          <div v-for="row in documentRows" :key="row.type.id" class="rounded-lg border border-slate-200 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-strong">{{ row.type.name }}</span>
                  <span v-if="row.document"
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                    <Lucide icon="CheckCircle2" class="h-3 w-3" /> Sudah diunggah
                  </span>
                  <span v-else
                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                    Belum ada file
                  </span>
                </div>

                <div v-if="row.document" class="mt-1.5 space-y-0.5">
                  <a :href="row.document.url ?? undefined" target="_blank"
                    class="font-body !text-primary break-all underline">
                    {{ row.document.file_name }}
                  </a>
                  <p v-if="row.document.document_number" class="font-caption">
                    No. Dokumen: {{ row.document.document_number }}
                  </p>
                  <p class="font-caption">
                    Diunggah {{ formatDocumentDate(row.document.uploaded_at) }}
                    <span v-if="row.document.uploaded_by"> oleh {{ row.document.uploaded_by.name }}</span>
                  </p>
                </div>
              </div>

              <div class="flex shrink-0 gap-2">
                <Button v-if="!rowState(row.type.id).editing && row.document" size="sm" variant="outline-secondary"
                  class="inline-flex items-center gap-2" @click="startDocumentUpload(row.type.id)">
                  <Lucide icon="RefreshCw" class="h-4 w-4" /> Ganti
                </Button>
                <Button v-else-if="!rowState(row.type.id).editing" size="sm" variant="outline-primary"
                  class="inline-flex items-center gap-2" @click="startDocumentUpload(row.type.id)">
                  <Lucide icon="Upload" class="h-4 w-4" /> Upload
                </Button>

                <Button v-if="row.document" size="sm" variant="soft-danger" title="Hapus"
                  class="!h-8 !w-8 !p-0 !shadow-none" @click="confirmDeleteDocument(row.document)">
                  <Lucide icon="Trash2" class="h-4 w-4" />
                </Button>
              </div>
            </div>

            <div v-if="rowState(row.type.id).editing" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
              <div v-if="row.type.requires_number">
                <FormLabel>Nomor Dokumen *</FormLabel>
                <FormInput v-model="rowState(row.type.id).documentNumber" placeholder="Masukkan nomor dokumen" />
              </div>

              <FileUploadField v-model="rowState(row.type.id).file" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
                :max-size-mb="10" :error="rowState(row.type.id).error" choose-text="Pilih file"
                empty-text="Belum ada file dipilih" @error="(msg: string) => (rowState(row.type.id).error = msg)" />

              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline-secondary" :disabled="rowState(row.type.id).uploading"
                  @click="cancelDocumentUpload(row.type.id)">
                  Batal
                </Button>
                <Button size="sm" variant="primary" class="inline-flex items-center gap-2"
                  :disabled="rowState(row.type.id).uploading" @click="submitDocumentUpload(row)">
                  <Lucide v-if="rowState(row.type.id).uploading" icon="Loader2" class="h-4 w-4 animate-spin" />
                  Simpan
                </Button>
              </div>
            </div>
          </div>
        </div>
      </CardSection>
    </div>

    <DeleteRecordDialog :open="deleteDocumentDialogOpen" title="Hapus Dokumen"
      :description="`Dokumen ${deleteDocumentTarget?.document_type?.name ?? ''} milik customer ini akan dihapus permanen.`"
      :loading="deleteDocumentLoading" @close="deleteDocumentDialogOpen = false" @confirm="performDeleteDocument" />

    <FormModal :open="contactFormOpen" :title="contactFormMode === 'create' ? 'Tambah Kontak' : 'Edit Kontak'"
      description="Data kontak/PIC customer." :loading="contactFormSaving" :error="contactFormError"
      :submit-text="contactFormMode === 'create' ? 'Tambah' : 'Simpan'"
      :submit-icon="contactFormMode === 'create' ? 'PlusCircle' : 'Save'" @close="closeContactForm"
      @submit="submitContactForm">
      <div class="space-y-3">
        <div>
          <FormLabel>Tipe Kontak *</FormLabel>
          <FormSelect v-model="contactForm.id_contact_type">
            <option value="">- Pilihan -</option>
            <option v-for="t in activeContactTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
          </FormSelect>
        </div>
        <div>
          <FormLabel>Nama Lengkap *</FormLabel>
          <FormInput v-model="contactForm.full_name" placeholder="Nama lengkap" />
        </div>
        <div>
          <FormLabel>Posisi/Jabatan</FormLabel>
          <FormInput v-model="contactForm.position" placeholder="Contoh: Finance Manager" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <FormLabel>Telepon</FormLabel>
            <FormInput v-model="contactForm.phone" placeholder="021-xxxxxxx" />
          </div>
          <div>
            <FormLabel>Mobile</FormLabel>
            <FormInput v-model="contactForm.mobile" placeholder="08xx-xxxx-xxxx" />
          </div>
        </div>
        <div>
          <FormLabel>Email</FormLabel>
          <FormInput v-model="contactForm.email" type="email" placeholder="nama@email.com" />
        </div>
      </div>
    </FormModal>

    <DeleteRecordDialog :open="deleteContactDialogOpen" title="Hapus Kontak"
      :description="`Kontak ${deleteContactTarget?.full_name ?? ''} akan dihapus permanen.`"
      :loading="deleteContactLoading" @close="deleteContactDialogOpen = false" @confirm="performDeleteContact" />
  </div>
</template>
