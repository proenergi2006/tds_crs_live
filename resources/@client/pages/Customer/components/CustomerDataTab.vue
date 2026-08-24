<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import DeleteRecordDialog from '@/components/SystemDesign/Dialog/DeleteRecordDialog.vue'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { useAuthStore } from '@/stores/auth'
import { copyToClipboard } from '@/utils/clipboard'
import { createResourceApi } from '@/utils/resourceApi'

import AddressSection from './AddressSection.vue'
import CorporateDetailSection from './CorporateDetailSection.vue'
import PaymentSection from './PaymentSection.vue'

/* Type: dokumen customer (customer_document_types + customer_documents) */
interface CustomerDocumentType {
  id: number
  code: string
  name: string
  is_active: boolean
  category: string | null
}
interface CustomerDocumentRecord {
  id: number
  id_customer: number
  id_document_type: number | null
  document_type: { id: number; code: string; name: string } | null
  document_name: string | null
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
  uploading: boolean
  error: string
}
interface NewFreeFormRow {
  label: string
  file: File | null
  error: string
}

/* Type: kontak customer (customer_contacts) */
interface CustomerContactRecord {
  id: number
  id_customer: number
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
}>()

const emit = defineEmits<{ (e: 'updated'): void }>()

const { success, error: notifyError } = useNotification()
const auth = useAuthStore()

/* State: generate link onboarding, dan result dialog */
const generatingLink = ref(false)
const linkResultOpen = ref(false)
const linkResult = reactive({ token: '', link: '', expiresAt: '', alreadyExists: false })
const activeToken = ref(props.customer?.onboarding_token ?? '')
const activeTokenExpiresAt = ref(props.customer?.token_expired_at ?? '')

const canGenerateLink = computed(() =>
  auth.can('customer.manage') &&
  (auth.can('customer.viewAny') || Number(props.customer?.id_user) === Number(auth.user?.id))
)
const hasActiveToken = computed(() => {
  if (!activeToken.value) return false
  if (!activeTokenExpiresAt.value) return true
  return new Date(activeTokenExpiresAt.value) > new Date()
})
const activeLink = computed(() =>
  activeToken.value ? `${window.location.origin}/customer-onboarding/${activeToken.value}` : ''
)
const linkResultTitle = computed(() => (linkResult.alreadyExists ? 'Token Sudah Ada' : 'Token Dibuat'))
const linkResultDescription = computed(() =>
  linkResult.alreadyExists
    ? 'Link onboarding untuk customer ini masih aktif dan belum kedaluwarsa.'
    : 'Link onboarding baru berhasil dibuat untuk customer ini.'
)

function formatExpiresAt(value: string) {
  if (!value) return '-'
  try {
    return new Date(value).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return value
  }
}

async function generateLink() {
  if (generatingLink.value) return

  generatingLink.value = true
  try {
    const { data } = await axios.post(`/api/customers/${props.idCustomer}/onboarding-link`)
    linkResult.token = data?.token ?? '-'
    linkResult.link = data?.link ?? ''
    linkResult.expiresAt = data?.expires_at ?? ''
    linkResult.alreadyExists = !!data?.already_exists
    linkResultOpen.value = true
    activeToken.value = data?.token ?? ''
    activeTokenExpiresAt.value = data?.expires_at ?? ''
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal membuat link onboarding.')
  } finally {
    generatingLink.value = false
  }
}

function visitLink() {
  if (!activeLink.value) return
  window.open(activeLink.value, '_blank')
}

async function copyActiveLink() {
  const copied = await copyToClipboard(activeLink.value)
  if (copied) {
    success('Link disalin', 'Link onboarding berhasil disalin ke clipboard.')
  } else {
    notifyError('Gagal menyalin', 'Link tidak berhasil disalin otomatis. Silakan salin manual.')
  }
}

async function copyLinkResult() {
  const copied = await copyToClipboard(linkResult.link)
  if (copied) {
    linkResultOpen.value = false
    success('Link disalin', 'Link onboarding berhasil disalin ke clipboard.')
  } else {
    notifyError('Gagal menyalin', 'Link tidak berhasil disalin otomatis. Silakan salin manual dari kotak di atas.')
  }
}

function closeLinkResult() {
  linkResultOpen.value = false
}

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

// sengaja allow-list category='onboarding' (bukan exclude 'lcr') -- biar kategori baru di masa depan gak otomatis nongol di sini
const activeDocumentTypes = computed(() =>
  documentTypes.value.filter(t => t.is_active && t.category === 'onboarding'),
)
const documentRows = computed(() =>
  activeDocumentTypes.value.map(type => ({
    type,
    document: customerDocuments.value.find(d => d.id_document_type === type.id) ?? null,
  })),
)
// Dokumen bebas (dari Onboarding "Dokumen Lainnya") -- id_document_type null, read+delete only di sini.
const freeFormDocumentRows = computed(() =>
  customerDocuments.value.filter(d => d.id_document_type === null),
)

const documentFileInputRef = ref<HTMLInputElement | null>(null)
const activeDocumentTypeId = ref<number | null>(null)

async function fetchDocumentTypes() {
  documentTypesLoading.value = true
  try {
    const { data } = await documentTypesApi.getAll({ as_list: true })
    documentTypes.value = Array.isArray(data) ? data : []
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
    const existingNumber = documentRows.value.find(r => r.type.id === typeId)?.document?.document_number ?? ''
    documentRowState[typeId] = { file: null, documentNumber: existingNumber, uploading: false, error: '' }
  }
  return documentRowState[typeId]
}

function openDocumentPicker(typeId: number) {
  activeDocumentTypeId.value = typeId
  documentFileInputRef.value?.click()
}

function handleDocumentFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  const typeId = activeDocumentTypeId.value

  if (file && typeId) {
    const state = rowState(typeId)
    state.file = file
    state.error = ''
  }

  input.value = ''
  activeDocumentTypeId.value = null
}

/* State: baris dokumen bebas baru -- picker-nya terpisah dari documentFileInputRef yang khusus baris fixed (keyed by typeId) */
const newFreeFormRows = ref<NewFreeFormRow[]>([])

function addFreeFormRow() {
  newFreeFormRows.value.push({ label: '', file: null, error: '' })
}

function removeNewFreeFormRow(idx: number) {
  newFreeFormRows.value.splice(idx, 1)
}

const freeFormFileInputRef = ref<HTMLInputElement | null>(null)
const activeNewFreeFormIndex = ref<number | null>(null)

function openFreeFormFilePicker(idx: number) {
  activeNewFreeFormIndex.value = idx
  freeFormFileInputRef.value?.click()
}

function handleFreeFormFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  const idx = activeNewFreeFormIndex.value

  if (file && idx !== null) {
    newFreeFormRows.value[idx].file = file
    newFreeFormRows.value[idx].error = ''
  }

  input.value = ''
  activeNewFreeFormIndex.value = null
}

const bulkUploading = ref(false)

// peek langsung ke documentRowState, jangan panggil rowState() di sini -- slot #action render sebelum data fetch selesai, bisa keburu ngunci cache documentNumber ke ''
const hasDirtyDocuments = computed(() =>
  documentRows.value.some(row => !!documentRowState[row.type.id]?.file)
  || newFreeFormRows.value.some(row => row.label.trim() || row.file),
)

/* backend cuma punya create+delete (gak ada replace) -- upload baru dulu, baru hapus lama, cleanup gagal gak boleh gagalin upload utama. refetch/reset state ditangani submitAllDocuments biar cuma sekali */
async function uploadDocumentType(row: { type: CustomerDocumentType; document: CustomerDocumentRecord | null }): Promise<boolean> {
  const state = rowState(row.type.id)
  if (!state.file) return true

  if (['nib', 'npwp'].includes(row.type.code) && !state.documentNumber.trim()) {
    state.error = 'Nomor dokumen wajib diisi untuk jenis dokumen ini.'
    return false
  }

  state.uploading = true
  state.error = ''
  try {
    const formData = new FormData()
    formData.append('id_document_type', String(row.type.id))
    formData.append('file', state.file)
    if (state.documentNumber.trim()) formData.append('document_number', state.documentNumber.trim())

    await customerDocumentsApi.store(formData)

    if (row.document) {
      await customerDocumentsApi.destroy(row.document.id).catch(() => null)
    }
    return true
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      state.error = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input Anda.'
    } else {
      state.error = e.response?.data?.message ?? 'Gagal mengunggah dokumen.'
    }
    return false
  } finally {
    state.uploading = false
  }
}

/* dokumen bebas kirim document_name (bukan id_document_type) -- baris yang "tersentuh" tetap dikirim walau salah satu kosong, biar backend 422 jelas per field */
async function uploadFreeFormRow(row: NewFreeFormRow): Promise<boolean> {
  if (!row.label.trim() && !row.file) return true

  row.error = ''
  try {
    const formData = new FormData()
    formData.append('document_name', row.label.trim())
    if (row.file) formData.append('file', row.file)

    await customerDocumentsApi.store(formData)
    return true
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response?.data?.errors || {}
      row.error = Object.values(errors)[0]?.[0] as string || 'Periksa kembali input.'
    } else {
      row.error = e.response?.data?.message ?? 'Gagal mengunggah dokumen.'
    }
    return false
  }
}

async function submitAllDocuments() {
  const dirtyFixedRows = documentRows.value.filter(row => !!rowState(row.type.id).file)
  const dirtyFreeFormRows = newFreeFormRows.value.filter(row => row.label.trim() || row.file)
  if ((dirtyFixedRows.length === 0 && dirtyFreeFormRows.length === 0) || bulkUploading.value) return

  bulkUploading.value = true
  try {
    const [fixedResults, freeFormResults] = await Promise.all([
      Promise.allSettled(dirtyFixedRows.map(row => uploadDocumentType(row))),
      Promise.allSettled(dirtyFreeFormRows.map(row => uploadFreeFormRow(row))),
    ])

    const fixedSucceeded = dirtyFixedRows.filter((_, i) => fixedResults[i].status === 'fulfilled' && (fixedResults[i] as PromiseFulfilledResult<boolean>).value)
    const freeFormSucceeded = dirtyFreeFormRows.filter((_, i) => freeFormResults[i].status === 'fulfilled' && (freeFormResults[i] as PromiseFulfilledResult<boolean>).value)
    const totalSucceeded = fixedSucceeded.length + freeFormSucceeded.length
    const totalFailed = (dirtyFixedRows.length - fixedSucceeded.length) + (dirtyFreeFormRows.length - freeFormSucceeded.length)

    await fetchCustomerDocuments()

    fixedSucceeded.forEach(row => {
      const state = rowState(row.type.id)
      state.file = null
      state.documentNumber = documentRows.value.find(r => r.type.id === row.type.id)?.document?.document_number ?? state.documentNumber
    })
    // Baris bebas yang sukses dihapus dari staging lokal -- sudah jadi row asli di freeFormDocumentRows setelah refetch.
    newFreeFormRows.value = newFreeFormRows.value.filter(row => !freeFormSucceeded.includes(row))

    if (totalFailed === 0) {
      success('Berhasil', `${totalSucceeded} dokumen berhasil disimpan.`)
    } else if (totalSucceeded === 0) {
      notifyError('Gagal', `${totalFailed} dokumen gagal disimpan. Periksa pesan error di tiap baris.`)
    } else {
      notifyError('Sebagian Gagal', `${totalSucceeded} dokumen tersimpan, ${totalFailed} gagal. Periksa pesan error di baris yang gagal.`)
    }
  } finally {
    bulkUploading.value = false
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

/* State: Kontak Customer -- satu sumber data buat kartu PIC juga, gak dipisah lagi jadi "PIC Details" read-only + CRUD terpisah */
const customerContactsApi = createResourceApi(`/customers/${props.idCustomer}/contacts`)

const contactsLoading = ref(true)
const customerContacts = ref<CustomerContactRecord[]>([])

const contactFormOpen = ref(false)
const contactFormMode = ref<'create' | 'edit'>('create')
const contactFormSaving = ref(false)
const contactFormError = ref<string | null>(null)
const contactFormErrors = ref<Record<string, string[]>>({})
const editingContactRecord = ref<CustomerContactRecord | null>(null)
const contactForm = reactive({
  id: null as number | null,
  full_name: '',
  position: '',
  phone: '',
  mobile: '',
  email: '',
})

const deleteContactDialogOpen = ref(false)
const deleteContactTarget = ref<CustomerContactRecord | null>(null)
const deleteContactLoading = ref(false)

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
    full_name: '',
    position: '',
    phone: '',
    mobile: '',
    email: '',
  })
}

function openCreateContact() {
  contactFormMode.value = 'create'
  editingContactRecord.value = null
  resetContactForm()
  contactFormOpen.value = true
}

function openEditContact(contact: CustomerContactRecord) {
  contactFormMode.value = 'edit'
  editingContactRecord.value = contact
  resetContactForm()
  Object.assign(contactForm, {
    id: contact.id,
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

  if (!contactForm.full_name.trim()) {
    contactFormError.value = 'Nama wajib diisi.'
    return
  }

  const payload = {
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
    contactFormOpen.value = false
  } catch (e: any) {
    notifyError('Gagal', e.response?.data?.message ?? 'Gagal menghapus kontak.')
  } finally {
    deleteContactLoading.value = false
  }
}

onMounted(fetchDocumentTypes)
onMounted(fetchCustomerDocuments)
onMounted(fetchCustomerContacts)
</script>

<template>
  <div class="gap-6 grid grid-cols-2">
    <div class="gap-6 grid lg:grid-cols-1">
      <div
        class="flex justify-between items-center gap-3 bg-gradient-to-br from-theme-1 via-emerald-800 to-green-600 shadow-sm p-6 box">
        <div>
          <div class="font-header text-white">Customer Onboarding Form</div>
          <div class="font-body text-white">Buat public link untuk customer onboarding form</div>
        </div>
        <div v-if="canGenerateLink" class="flex items-center gap-2">
          <template v-if="hasActiveToken">
            <Button variant="white" @click="visitLink">
              <Lucide icon="ExternalLink" class="mr-2 w-4 h-4" />
              Visit Link
            </Button>
            <Button variant="white" title="Salin Link" class="!p-0 !w-9 !h-9" @click="copyActiveLink">
              <Lucide icon="Copy" class="w-4 h-4" />
            </Button>
          </template>
          <Button v-else variant="white" :disabled="generatingLink" @click="generateLink">
            <Lucide v-if="generatingLink" icon="Loader2" class="mr-2 w-4 h-4 animate-spin" />
            <Lucide v-else icon="Link" class="mr-2 w-4 h-4" />
            Generate Link
          </Button>
        </div>
      </div>

      <CorporateDetailSection :customer="customer" :id-customer="idCustomer" @updated="emit('updated')" />

      <PaymentSection :customer="customer" :id-customer="idCustomer" @updated="emit('updated')" />
    </div>

    <div class="gap-6 grid lg:grid-cols-1">
      <CardSection title="Kontak Customer" description="Kelola PIC/kontak customer." icon="Users"
        icon-class="bg-cyan-100 text-cyan-600">
        <template #action>
          <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2" @click="openCreateContact">
            <Lucide icon="Plus" class="w-4 h-4" />
            Tambah Kontak
          </Button>
        </template>

        <div v-if="contactsLoading"
          class="flex justify-center items-center gap-3 min-h-[120px] text-slate-500">
          <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
          <span class="font-body">Memuat kontak...</span>
        </div>

        <div v-else-if="customerContacts.length === 0"
          class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
          <Lucide icon="Inbox" class="w-6 h-6 text-slate-400" />
          <div class="font-body">Belum ada kontak yang ditambahkan.</div>
        </div>

        <div v-else class="gap-3 grid sm:grid-cols-2">
          <div v-for="c in customerContacts" :key="c.id" class="p-3 border border-slate-200 rounded-lg">
            <div class="flex items-start gap-3">
              <div
                class="flex justify-center items-center bg-cyan-100 rounded-full w-10 h-10 font-strong text-cyan-700 shrink-0">
                {{ (c.full_name || '?').charAt(0).toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-strong truncate">{{ c.full_name }}</div>
                <div v-if="c.position" class="font-caption truncate">{{ c.position }}</div>
              </div>
              <Button size="sm" variant="soft-pending" title="Edit" class="!shadow-none !p-0 !w-8 !h-8 shrink-0"
                @click="openEditContact(c)">
                <Lucide icon="Edit" class="w-4 h-4" />
              </Button>
            </div>
            <div v-if="c.phone || c.mobile" class="mt-2 font-caption truncate">
              {{ [c.phone, c.mobile].filter(Boolean).join(' | ') }}
            </div>
            <p v-if="c.email" class="mt-1 font-caption truncate">{{ c.email }}</p>
          </div>
        </div>
      </CardSection>

      <AddressSection :customer="customer" :id-customer="idCustomer" @updated="emit('updated')" />

      <CardSection title="Dokumen Lampiran" description="Upload dan kelola dokumen pendukung." icon="FileCheck2"
        icon-class="bg-indigo-100 text-indigo-600">
        <template #action>
          <div class="flex items-center gap-2">
            <Button size="sm" variant="outline-primary" class="inline-flex items-center gap-2" @click="addFreeFormRow">
              <Lucide icon="Plus" class="w-4 h-4" /> Tambah
            </Button>
            <Button size="sm" variant="primary" class="inline-flex items-center gap-2"
              :disabled="!hasDirtyDocuments || bulkUploading" @click="submitAllDocuments">
              <Lucide v-if="bulkUploading" icon="Loader2" class="w-4 h-4 animate-spin" />
              <Lucide v-else icon="Save" class="w-4 h-4" />
              Simpan Perubahan
            </Button>
          </div>
        </template>

        <div v-if="documentTypesLoading || documentsLoading"
          class="flex justify-center items-center gap-3 min-h-[120px] text-slate-500">
          <Lucide icon="Loader2" class="w-5 h-5 animate-spin" />
          <span class="font-body">Memuat dokumen...</span>
        </div>

        <div v-else-if="documentRows.length === 0"
          class="flex flex-col items-center gap-2 bg-slate-50 px-6 py-10 border border-slate-300 border-dashed rounded-lg text-center">
          <Lucide icon="Inbox" class="w-8 h-8 text-slate-400" />
          <div class="font-body">Belum ada jenis dokumen yang aktif.</div>
        </div>

        <div v-else class="border border-slate-200 rounded-xl overflow-x-auto">
          <table class="divide-y divide-slate-200 w-full min-w-[640px]">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-3 py-2 w-12 font-label text-center">No</th>
                <th class="px-3 py-2 font-label text-left">Nama Dokumen</th>
                <th class="px-3 py-2 font-label text-left">Nomor Dokumen</th>
                <th class="px-3 py-2 font-label text-left">File</th>
                <th class="px-3 py-2 w-28 font-label text-center">Aksi</th>
              </tr>
            </thead>

            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="(row, idx) in documentRows" :key="row.type.id" class="hover:bg-slate-50 transition">
                <td class="px-3 py-2 font-num text-center">{{ idx + 1 }}.</td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap items-center gap-1 font-body">
                    {{ row.type.name }}
                    <RequiredAsterisk v-if="['nib', 'npwp'].includes(row.type.code)" />
                  </div>
                </td>
                <td class="px-3 py-2">
                  <FormInput v-model="rowState(row.type.id).documentNumber" type="text" placeholder="Nomor dokumen" />
                </td>
                <td class="px-3 py-2">
                  <span v-if="rowState(row.type.id).file" class="block font-body text-amber-600 break-all italic">
                    {{ rowState(row.type.id).file?.name }}
                  </span>
                  <a v-else-if="row.document" :href="row.document.url ?? undefined" target="_blank"
                    class="block font-body !text-primary underline break-all">
                    {{ row.document.file_name }}
                  </a>
                  <span v-else class="font-body text-slate-400">Belum ada file</span>
                  <small v-if="rowState(row.type.id).error" class="block input-error-text">
                    {{ rowState(row.type.id).error }}
                  </small>
                </td>
                <td class="px-3 py-2 text-center">
                  <Button size="sm" variant="outline-secondary" class="inline-flex items-center gap-1"
                    @click="openDocumentPicker(row.type.id)">
                    <Lucide :icon="rowState(row.type.id).file || row.document ? 'RefreshCw' : 'Upload'"
                      class="w-3.5 h-3.5" />
                    {{ rowState(row.type.id).file || row.document ? 'Ganti' : 'Unggah' }}
                  </Button>
                </td>
              </tr>

              <tr v-for="(doc, idx) in freeFormDocumentRows" :key="doc.id" class="hover:bg-slate-50 transition">
                <td class="px-3 py-2 font-num text-center">{{ documentRows.length + idx + 1 }}.</td>
                <td class="px-3 py-2 font-body">{{ doc.document_name }}</td>
                <td class="px-3 py-2 font-body">-</td>
                <td class="px-3 py-2">
                  <a :href="doc.url ?? undefined" target="_blank" class="font-body !text-primary underline break-all">
                    {{ doc.file_name }}
                  </a>
                </td>
                <td class="px-3 py-2 text-center">
                  <Button size="sm" variant="soft-danger" title="Hapus" class="!shadow-none !p-0 !w-8 !h-8"
                    @click="confirmDeleteDocument(doc)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </Button>
                </td>
              </tr>

              <tr v-for="(row, idx) in newFreeFormRows" :key="`new-${idx}`" class="hover:bg-slate-50 transition">
                <td class="px-3 py-2 font-num text-center">{{ documentRows.length + freeFormDocumentRows.length + idx +
                  1 }}.
                </td>
                <td class="px-3 py-2">
                  <FormInput v-model="row.label" type="text" placeholder="Nama dokumen" />
                </td>
                <td class="px-3 py-2 font-body">-</td>
                <td class="px-3 py-2">
                  <span v-if="row.file" class="font-body">{{ row.file.name }}</span>
                  <span v-else class="font-body text-slate-400">Belum ada file</span>
                  <small v-if="row.error" class="block input-error-text">{{ row.error }}</small>
                </td>
                <td class="px-3 py-2 text-center">
                  <div class="flex justify-center items-center gap-1.5">
                    <Button size="sm" variant="outline-secondary" class="inline-flex items-center gap-1"
                      @click="openFreeFormFilePicker(idx)">
                      <Lucide :icon="row.file ? 'RefreshCw' : 'Upload'" class="w-3.5 h-3.5" /> {{ row.file ? 'Ganti' : 'Unggah' }}
                    </Button>
                    <Button size="sm" variant="soft-danger" class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                      @click="removeNewFreeFormRow(idx)">
                      <Lucide icon="Trash2" class="w-4 h-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <input ref="documentFileInputRef" type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
            @change="handleDocumentFileSelected" />
          <input ref="freeFormFileInputRef" type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
            @change="handleFreeFormFileSelected" />
        </div>
      </CardSection>
    </div>

    <DeleteRecordDialog :open="deleteDocumentDialogOpen" title="Hapus Dokumen"
      :description="`Dokumen ${deleteDocumentTarget?.document_type?.name ?? deleteDocumentTarget?.document_name ?? ''} milik customer ini akan dihapus permanen.`"
      :loading="deleteDocumentLoading" @close="deleteDocumentDialogOpen = false" @confirm="performDeleteDocument" />

    <FormModal :open="contactFormOpen" :title="contactFormMode === 'create' ? 'Tambah Kontak' : 'Edit Kontak'"
      description="Data kontak/PIC customer." :loading="contactFormSaving" :error="contactFormError"
      :submit-text="contactFormMode === 'create' ? 'Tambah' : 'Simpan'"
      :submit-icon="contactFormMode === 'create' ? 'PlusCircle' : 'Save'" @close="closeContactForm"
      @submit="submitContactForm">
      <div class="space-y-3">
        <div v-if="contactFormMode === 'edit'" class="flex justify-end">
          <Button size="sm" variant="soft-danger" class="inline-flex items-center gap-2"
            @click="editingContactRecord && confirmDeleteContact(editingContactRecord)">
            <Lucide icon="Trash2" class="w-4 h-4" />
            Hapus Kontak
          </Button>
        </div>
        <div>
          <FormLabel>Nama Lengkap *</FormLabel>
          <FormInput v-model="contactForm.full_name" placeholder="Nama lengkap" />
        </div>
        <div>
          <FormLabel>Posisi/Jabatan</FormLabel>
          <FormInput v-model="contactForm.position" placeholder="Contoh: Finance Manager" />
        </div>
        <div class="gap-3 grid grid-cols-2">
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

      <DeleteRecordDialog :open="deleteContactDialogOpen" title="Hapus Kontak"
        :description="`Kontak ${deleteContactTarget?.full_name ?? ''} akan dihapus permanen.`"
        :loading="deleteContactLoading" @close="deleteContactDialogOpen = false" @confirm="performDeleteContact" />
    </FormModal>

    <ConfirmDialog :open="linkResultOpen" :title="linkResultTitle" :description="linkResultDescription"
      confirm-text="Salin Link" cancel-text="Tutup" icon="Link" icon-class="bg-primary/10 text-primary"
      variant="primary" @close="closeLinkResult" @confirm="copyLinkResult">
      <div class="mb-1 font-caption">Token</div>
      <div class="bg-slate-50 p-3 rounded-lg font-mono text-slate-700 text-sm break-all">
        {{ linkResult.token }}
      </div>
      <div class="mt-3 mb-1 font-caption">Link Onboarding</div>
      <div class="bg-slate-50 p-3 rounded-lg font-mono text-slate-700 text-sm break-all">
        {{ linkResult.link }}
      </div>
      <div class="mt-2 font-caption text-slate-500">Berlaku sampai {{ formatExpiresAt(linkResult.expiresAt) }}</div>
    </ConfirmDialog>
  </div>
</template>
