<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

import TomSelect from '@/components/Base/TomSelect'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

const TEXT_FIELDS = ['lokasi_pengiriman', 'keterangan', 'catatan', 'syarat_ketentuan', 'lampiran_tambahan'] as const

type TextField = (typeof TEXT_FIELDS)[number]

const props = defineProps<{
  open: boolean
  apiBase: string
  quotation: {
    id: number
    nomor_penawaran: string | null
    customer_id: number | null
    customer_contact: { id_contact: number } | null
    lokasi_pengiriman: string | null
    keterangan: string | null
    catatan: string | null
    syarat_ketentuan: string | null
    lampiran_tambahan: string | null
  }
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const { success, info, error: notifyError } = useNotification()

const contacts = ref<any[]>([])
const contactsLoading = ref(false)
const saving = ref(false)
const formError = ref<string | null>(null)

const form = reactive({
  customer_contact_id: '',
  lokasi_pengiriman: '',
  keterangan: '',
  catatan: '',
  syarat_ketentuan: '',
  lampiran_tambahan: '',
})

const serverErrors = reactive({
  customer_contact_id: '',
  lokasi_pengiriman: '',
  keterangan: '',
  catatan: '',
  syarat_ketentuan: '',
  lampiran_tambahan: '',
})

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return
    resetForm()
    if (props.quotation.customer_id) fetchContacts(props.quotation.customer_id)
  },
  { immediate: true },
)

async function fetchContacts(customerId: number) {
  contactsLoading.value = true
  try {
    const { data } = await axios.get(`/api/customers/${customerId}/contacts`)
    contacts.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch {
    contacts.value = []
    notifyError('Gagal', 'Gagal memuat kontak customer')
  } finally {
    contactsLoading.value = false
  }
}

function resetForm() {
  formError.value = null
  Object.keys(serverErrors).forEach((key) => {
    serverErrors[key as keyof typeof serverErrors] = ''
  })
  form.customer_contact_id = props.quotation.customer_contact?.id_contact
    ? String(props.quotation.customer_contact.id_contact)
    : ''
  for (const field of TEXT_FIELDS) form[field] = props.quotation[field] ?? ''
}

function changedPayload(): Record<string, string | number | null> {
  const payload: Record<string, string | number | null> = {}
  for (const field of TEXT_FIELDS) {
    const next = form[field] === '' ? null : form[field]
    if (next !== (props.quotation[field as TextField] ?? null)) payload[field] = next
  }
  const initialContact = props.quotation.customer_contact?.id_contact ?? null
  if (form.customer_contact_id !== '' && Number(form.customer_contact_id) !== initialContact) {
    payload.customer_contact_id = Number(form.customer_contact_id)
  }
  return payload
}

async function submitForm() {
  const payload = changedPayload()
  if (!Object.keys(payload).length) {
    info('Tidak Ada Perubahan', 'Belum ada field yang diubah.')
    return
  }

  saving.value = true
  formError.value = null
  Object.keys(serverErrors).forEach((key) => {
    serverErrors[key as keyof typeof serverErrors] = ''
  })
  try {
    await axios.patch(`${props.apiBase}/${props.quotation.id}/details`, payload)
    success('Berhasil', 'Info penawaran diperbarui.')
    emit('saved')
  } catch (e: any) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      for (const [key, messages] of Object.entries<string[]>(e.response.data.errors)) {
        if (key in serverErrors) serverErrors[key as keyof typeof serverErrors] = messages?.[0] || ''
      }
      formError.value = e.response.data.message ?? 'Periksa kembali isian Anda.'
    } else {
      notifyError('Gagal', e.response?.data?.message || 'Gagal memperbarui info penawaran.')
      emit('close')
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <FormModal :open="open" :title="`Edit Info Penawaran ${quotation.nomor_penawaran ?? ''}`" :loading="saving"
    :error="formError" submit-text="Simpan" size="lg" @close="emit('close')" @submit="submitForm">
    <div class="space-y-4">
      <div class="bg-sky-50 px-4 py-3 border border-sky-200 rounded-lg text-body text-sky-800">
        Perubahan ini tidak mengubah harga dan tidak memerlukan verifikasi ulang.
      </div>

      <div>
        <FormLabel>Kontak Tujuan</FormLabel>
        <TomSelect v-model="form.customer_contact_id" :options="{
          placeholder: contactsLoading ? 'Memuat kontak…' : 'Pilih Kontak Tujuan...',
          dropdownParent: 'body' as const,
        }" class="w-full" :class="serverErrors.customer_contact_id ? 'input-error' : ''">
          <option v-for="c in contacts" :key="c.id" :value="String(c.id)">
            {{ c.full_name }}{{ c.position ? ` (${c.position})` : '' }}
          </option>
        </TomSelect>
        <small v-if="serverErrors.customer_contact_id" class="block input-error-text">{{
          serverErrors.customer_contact_id }}</small>
      </div>

      <div>
        <FormLabel>Lokasi Pengiriman</FormLabel>
        <FormTextarea v-model="form.lokasi_pengiriman" :rows="2" maxlength="255" placeholder="Lokasi pengiriman..." />
        <small v-if="serverErrors.lokasi_pengiriman" class="block input-error-text">{{
          serverErrors.lokasi_pengiriman }}</small>
      </div>

      <div>
        <FormLabel>Titik Serah Terima & T&C Bongkar</FormLabel>
        <FormTextarea v-model="form.keterangan" :rows="3" placeholder="Titik serah terima & T&C bongkar..." />
        <small v-if="serverErrors.keterangan" class="block input-error-text">{{ serverErrors.keterangan }}</small>
      </div>

      <div>
        <FormLabel>Catatan</FormLabel>
        <FormTextarea v-model="form.catatan" :rows="3" placeholder="Catatan tambahan…" />
        <small v-if="serverErrors.catatan" class="block input-error-text">{{ serverErrors.catatan }}</small>
      </div>

      <div>
        <FormLabel>Syarat & Ketentuan</FormLabel>
        <FormTextarea v-model="form.syarat_ketentuan" :rows="4" placeholder="Syarat dan ketentuan…" />
        <small v-if="serverErrors.syarat_ketentuan" class="block input-error-text">{{
          serverErrors.syarat_ketentuan }}</small>
      </div>

      <div>
        <FormLabel>Lampiran Tambahan</FormLabel>
        <FormTextarea v-model="form.lampiran_tambahan" :rows="4" placeholder="Lampiran tambahan…" />
        <small v-if="serverErrors.lampiran_tambahan" class="block input-error-text">{{
          serverErrors.lampiran_tambahan }}</small>
      </div>
    </div>
  </FormModal>
</template>
