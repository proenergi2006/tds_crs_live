<script setup lang="ts">
import { reactive, ref, computed, watch, nextTick } from 'vue'
import { debounce } from 'lodash'
import axios from 'axios'

import Alert from '@/components/Base/Alert'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import { FormInput, FormSelect } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import {
  incoTermsOptions,
  ownershipOptions,
  typeBusinessOptions,
} from '@/pages/CustomerOnboarding/optionSets'

const props = defineProps<{
  customer: any
  idCustomer: number
}>()

const emit = defineEmits<{ (e: 'updated'): void }>()

const { success, error: notifyError } = useNotification()

function dash(v: unknown) {
  return v === null || v === undefined || v === '' ? '-' : v
}
function resolveErrorMessage(e: any, fallback: string): string {
  if (e.response?.status === 422) {
    const errors = e.response?.data?.errors || {}
    return (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.'
  }
  return e.response?.data?.message ?? fallback
}

const corporateRows = computed(() => {
  const cust = props.customer || {}
  return [
    { label: 'Nama Perusahaan', value: dash(cust.company_name) },
    { label: 'Holding', value: dash(cust.parent_company) },
    { label: 'Telepon', value: dash(cust.phone) },
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

/* State: edit modal -- Corporate Details (PUT /customers/{id}). */
const editingCorporate = ref(false)
const savingCorporate = ref(false)
const corporateForm = reactive({
  company_name: '',
  parent_company: '',
  phone: '',
  email: '',
  website: '',
  business_type: '',
  business_type_other: '',
  ownership_type: '',
  ownership_type_other: '',
  inco_terms: '',
  inco_terms_other: '',
})

/* State: cek ketersediaan nama perusahaan (informational, tidak menahan submit) */
const nameCheckStatus = ref<'idle' | 'checking' | 'available' | 'taken'>('idle')
const nameMatches = ref<{
  id_customer: number
  company_name: string
  marketing: { id: number | null; name: string | null }
}[]>([])
const showDuplicatePopup = ref(false)

async function checkCompanyName() {
  const companyName = corporateForm.company_name.trim()
  if (!companyName) {
    nameCheckStatus.value = 'idle'
    nameMatches.value = []
    return
  }

  nameCheckStatus.value = 'checking'
  try {
    const { data } = await axios.get('/api/customers/check-company-name', {
      params: { company_name: companyName, exclude_id: props.idCustomer },
    })
    nameMatches.value = data.matches || []
    nameCheckStatus.value = data.available ? 'available' : 'taken'
  } catch {
    // cuma fitur informational, gagal cek jangan sampai ganggu pengisian form
    nameCheckStatus.value = 'idle'
    nameMatches.value = []
  }
}

// cek ketersediaan nama perusahaan, debounced biar gak request tiap keystroke
watch(() => corporateForm.company_name, debounce(checkCompanyName, 400))

function startEditCorporate() {
  const cust = props.customer || {}
  Object.assign(corporateForm, {
    company_name: cust.company_name ?? '',
    parent_company: cust.parent_company ?? '',
    phone: cust.phone ?? '',
    email: cust.email ?? '',
    website: cust.website ?? '',
    business_type: cust.business_type ?? '',
    business_type_other: cust.business_type_other ?? '',
    ownership_type: cust.ownership_type ?? '',
    ownership_type_other: cust.ownership_type_other ?? '',
    inco_terms: cust.inco_terms ?? '',
    inco_terms_other: cust.inco_terms_other ?? '',
  })
  editingCorporate.value = true
}

function cancelEditCorporate() {
  editingCorporate.value = false
}

// binding manual (bukan v-model) biar cursor gak lompat ke akhir pas uppercase transform
function onCorporateUppercaseInput(field: 'company_name' | 'parent_company', event: Event) {
  const target = event.target as HTMLInputElement
  const cursorPos = target.selectionStart
  corporateForm[field] = target.value.toUpperCase()
  nextTick(() => {
    target.setSelectionRange(cursorPos, cursorPos)
  })
}

async function submitCorporateForm() {
  savingCorporate.value = true
  try {
    await axios.put(`/api/customers/${props.idCustomer}`, { ...corporateForm })
    editingCorporate.value = false
    success('Berhasil', 'Corporate Details berhasil diperbarui.')
    emit('updated')
  } catch (e: any) {
    notifyError('Gagal', resolveErrorMessage(e, 'Gagal memperbarui Corporate Details.'))
  } finally {
    savingCorporate.value = false
  }
}
</script>

<template>
  <CardSection title="Corporate Details" description="Identitas perusahaan customer." icon="Building2"
    icon-class="bg-violet-100 text-violet-600">
    <template #action>
      <Button size="sm" variant="outline-secondary" class="inline-flex items-center gap-2" @click="startEditCorporate">
        <Lucide icon="Edit" class="w-4 h-4" /> Edit
      </Button>
    </template>
    <div class="gap-x-8 gap-y-3 grid sm:grid-cols-1">
      <div v-for="row in corporateRows" :key="row.label"
        class="flex justify-between gap-4 py-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">{{ row.label }}</span>
        <span class="font-strong text-right">{{ row.value }}</span>
      </div>
    </div>
  </CardSection>

  <FormModal :open="editingCorporate" title="Edit Corporate Details" description="Identitas perusahaan customer."
    :loading="savingCorporate" submit-text="Simpan" submit-icon="Save" @close="cancelEditCorporate"
    @submit="submitCorporateForm">
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Nama Perusahaan</span>
      <div class="mt-0.5 w-2/3">
        <FormInput :value="corporateForm.company_name" placeholder="cth. PT Contoh Sejahtera"
          @input="onCorporateUppercaseInput('company_name', $event)" />
        <div v-if="nameCheckStatus === 'available'" class="mt-1 font-caption !text-emerald-600">
          Nama tersedia
        </div>
        <div v-else-if="nameCheckStatus === 'taken'" class="flex items-center gap-2 mt-1 font-caption !text-amber-600">
          <span>Sudah terdaftar, {{ nameMatches.length }} kecocokan ditemukan</span>
          <button type="button" class="font-semibold underline underline-offset-2" @click="showDuplicatePopup = true">
            Lihat daftar
          </button>
        </div>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Holding</span>
      <div class="mt-0.5 w-2/3">
        <FormInput :value="corporateForm.parent_company" placeholder="cth. PT Induk Group (opsional)"
          @input="onCorporateUppercaseInput('parent_company', $event)" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Telepon</span>
      <div class="mt-0.5 w-2/3">
        <FormInput v-model="corporateForm.phone" placeholder="cth. (021) 5551234" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Email</span>
      <div class="mt-0.5 w-2/3">
        <FormInput v-model="corporateForm.email" type="email" placeholder="nama@email.com" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Website</span>
      <div class="mt-0.5 w-2/3">
        <FormInput v-model="corporateForm.website" placeholder="https://www.contoh.com" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Jenis Usaha</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormSelect v-model="corporateForm.business_type"
          :class="corporateForm.business_type === 'Other' ? 'w-1/3' : ''">
          <option value="">- Pilihan -</option>
          <option v-for="opt in typeBusinessOptions" :key="opt" :value="opt">{{ opt }}</option>
          <option value="Other">Other</option>
        </FormSelect>
        <FormInput v-if="corporateForm.business_type === 'Other'" v-model="corporateForm.business_type_other"
          class="w-2/3" placeholder="Specify" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Kepemilikan</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormSelect v-model="corporateForm.ownership_type"
          :class="corporateForm.ownership_type === 'Other' ? 'w-1/3' : ''">
          <option value="">- Pilihan -</option>
          <option v-for="opt in ownershipOptions" :key="opt" :value="opt">{{ opt }}</option>
          <option value="Other">Other</option>
        </FormSelect>
        <FormInput v-if="corporateForm.ownership_type === 'Other'" v-model="corporateForm.ownership_type_other"
          class="w-2/3" placeholder="Specify" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Incoterms</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormSelect v-model="corporateForm.inco_terms" :class="corporateForm.inco_terms === 'Other' ? 'w-1/3' : ''">
          <option value="">- Pilihan -</option>
          <option v-for="opt in incoTermsOptions" :key="opt.code" :value="opt.code">{{ opt.code }} - {{ opt.label
            }}</option>
          <option value="Other">Other</option>
        </FormSelect>
        <FormInput v-if="corporateForm.inco_terms === 'Other'" v-model="corporateForm.inco_terms_other" class="w-2/3"
          placeholder="Specify" />
      </div>
    </div>
  </FormModal>

  <Dialog :open="showDuplicatePopup" size="lg" @close="showDuplicatePopup = false">
    <Dialog.Panel>
      <div class="p-6">
        <div class="pb-4 border-slate-200 border-b">
          <h3 class="font-header">Nama Perusahaan Sudah Terdaftar</h3>
          <p class="mt-1 font-caption text-slate-500">
            {{ nameMatches.length }} customer lain memakai nama yang sama/mirip
          </p>
        </div>

        <div class="space-y-3 mt-4">
          <div v-for="match in nameMatches" :key="match.id_customer"
            class="px-4 py-3 border border-slate-200 rounded-md">
            <p class="font-body font-semibold">{{ match.company_name }}</p>
            <p class="mt-0.5 font-caption text-slate-500">
              Marketing: {{ match.marketing?.name || '-' }}
            </p>
          </div>
        </div>

        <Alert variant="soft-warning" class="mt-4">
          Hubungi tim Key Account untuk verifikasi sebelum melanjutkan.
        </Alert>
      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-slate-200 border-t">
        <Button variant="outline-secondary" @click="showDuplicatePopup = false">Tutup</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
