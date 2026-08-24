<script setup lang="ts">
import { reactive, ref, computed, watch } from 'vue'
import axios from 'axios'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormCheck, FormInput, FormSelect, FormTextarea } from '@/components/Base/Form'
import CardSection from '@/components/SystemDesign/Page/CardSection.vue'
import FormModal from '@/components/SystemDesign/Form/FormModal.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'
import { paymentMethodOptions, paymentTermBasisOptions, paymentTermOptions } from '@/pages/CustomerOnboarding/optionSets'

const props = defineProps<{
  customer: any
  idCustomer: number
}>()

const emit = defineEmits<{ (e: 'updated'): void }>()

const { success, error: notifyError } = useNotification()

const paymentRows = computed(() => {
  const pay = props.customer?.payment || {}
  return [
    {
      label: 'Payment Schedule',
      value: dash(pay.payment_schedule === 'Other' ? pay.payment_schedule_other : pay.payment_schedule),
    },
    {
      label: 'Payment Method',
      value: dash(pay.payment_method === 'Other' ? pay.payment_method_other : pay.payment_method),
    },
    { label: 'Term of Payment', value: dash(pay.payment_term) },
    // Term Days & Basis cuma relevan buat term CREDIT -- baris ini dihilangkan total (bukan '-') kalau bukan CREDIT
    ...(pay.payment_term === 'CREDIT'
      ? [{ label: 'Term Days & Basis', value: `${dash(pay.payment_term_days)} ${dash(humanize(pay.payment_term_basis))}` }]
      : []),
    { label: 'Bank Name', value: dash(pay.bank_name) },
    { label: 'Bank Account Number', value: dash(pay.account_number) },
    { label: 'Bank Address', value: dash(pay.bank_address) },
    {
      label: 'Credit Facility',
      value: pay.credit_facility ? `Ya${pay.creditor ? ` (${pay.creditor})` : ''}` : 'Tidak',
    },
    { label: 'Tax Invoice', value: pay.invoice ? 'Ya' : 'Tidak' },
    { label: 'Catatan', value: dash(pay.extra_notes) },
  ]
})

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
function resolveErrorMessage(e: any, fallback: string): string {
  if (e.response?.status === 422) {
    const errors = e.response?.data?.errors || {}
    return (Object.values(errors)[0] as string[] | undefined)?.[0] ?? 'Periksa kembali input Anda.'
  }
  return e.response?.data?.message ?? fallback
}

/* State: edit modal Payment & Banking -- paymentScheduleOptions bukan dari optionSets.ts karena field ini gak dipakai onboarding publik, nilai valid cuma 'Every Day' + 'Other' */
const paymentScheduleOptions = ['Every Day']

const editingPayment = ref(false)
const savingPayment = ref(false)
const paymentForm = reactive({
  schedule: '',
  schedule_other: '',
  method: '',
  method_other: '',
  invoice_tax: false,
  note: '',
  bank_name: '',
  currency: '',
  bank_address: '',
  account_number: '',
  has_credit: false,
  creditor_name: '',
  term: '',
  term_days: null as number | null,
  term_basis: '',
})

watch(() => paymentForm.term, (val) => {
  if (val !== 'CREDIT') {
    paymentForm.term_days = null
    paymentForm.term_basis = ''
  }
})

function startEditPayment() {
  const pay = props.customer?.payment || {}
  Object.assign(paymentForm, {
    schedule: pay.payment_schedule ?? '',
    schedule_other: pay.payment_schedule_other ?? '',
    method: pay.payment_method ?? '',
    method_other: pay.payment_method_other ?? '',
    invoice_tax: !!pay.invoice,
    note: pay.extra_notes ?? '',
    bank_name: pay.bank_name ?? '',
    currency: pay.currency ?? '',
    bank_address: pay.bank_address ?? '',
    account_number: pay.account_number ?? '',
    has_credit: !!pay.credit_facility,
    creditor_name: pay.creditor ?? '',
    term: pay.payment_term ?? '',
    term_days: pay.payment_term_days ?? null,
    term_basis: pay.payment_term_basis ?? '',
  })
  editingPayment.value = true
}

function cancelEditPayment() {
  editingPayment.value = false
}

async function submitPaymentForm() {
  savingPayment.value = true
  try {
    await axios.put(`/api/customers/${props.idCustomer}/payment`, paymentForm)
    editingPayment.value = false
    success('Berhasil', 'Payment & Banking berhasil diperbarui.')
    emit('updated')
  } catch (e: any) {
    notifyError('Gagal', resolveErrorMessage(e, 'Gagal memperbarui Payment & Banking.'))
  } finally {
    savingPayment.value = false
  }
}
</script>

<template>
  <CardSection title="Payment & Banking" description="Metode, term, dan rekening bank customer." icon="CreditCard"
    icon-class="bg-emerald-100 text-emerald-600">
    <template #action>
      <Button size="sm" variant="outline-secondary" class="inline-flex items-center gap-2" @click="startEditPayment">
        <Lucide icon="Edit" class="w-4 h-4" /> Edit
      </Button>
    </template>

    <div class="space-y-1.5">
      <div v-for="row in paymentRows" :key="row.label"
        class="flex justify-between gap-4 py-1.5 border-slate-100 border-b">
        <span class="font-label text-[14px]">{{ row.label }}</span>
        <span class="font-strong text-right">{{ row.value }}</span>
      </div>
    </div>
  </CardSection>

  <FormModal :open="editingPayment" title="Edit Payment & Banking"
    description="Metode, term, dan rekening bank customer." :loading="savingPayment" submit-text="Simpan"
    submit-icon="Save" @close="cancelEditPayment" @submit="submitPaymentForm">
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Payment Schedule</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormSelect v-model="paymentForm.schedule" :class="paymentForm.schedule === 'Other' ? 'w-1/3' : ''">
          <option value="">- Pilihan -</option>
          <option v-for="opt in paymentScheduleOptions" :key="opt" :value="opt">{{ opt }}</option>
          <option value="Other">Other</option>
        </FormSelect>
        <FormInput v-if="paymentForm.schedule === 'Other'" v-model="paymentForm.schedule_other" placeholder="Specify"
          class="w-2/3" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Payment Method</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormSelect v-model="paymentForm.method" :class="paymentForm.method === 'Other' ? 'w-1/3' : ''">
          <option value="">- Pilihan -</option>
          <option v-for="opt in paymentMethodOptions" :key="opt" :value="opt">{{ opt }}</option>
          <option value="Other">Other</option>
        </FormSelect>
        <FormInput v-if="paymentForm.method === 'Other'" v-model="paymentForm.method_other" class="w-2/3"
          placeholder="Specify" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Term of Payment</span>
      <div class="mt-0.5 w-2/3">
        <FormSelect v-model="paymentForm.term">
          <option value="">- Pilihan -</option>
          <option v-for="opt in paymentTermOptions" :key="opt.code" :value="opt.code">{{ opt.label }}</option>
        </FormSelect>
      </div>
    </div>
    <div v-if="paymentForm.term === 'CREDIT'"
      class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Term Days & Basis</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormInput class="w-1/3" v-model="paymentForm.term_days" type="number" min="0" placeholder="cth. 30" />
        <FormSelect class="w-2/3" v-model="paymentForm.term_basis">
          <option value="">- Pilihan -</option>
          <option v-for="opt in paymentTermBasisOptions" :key="opt.code" :value="opt.code">{{ opt.label }}
          </option>
        </FormSelect>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Bank Name</span>
      <div class="mt-0.5 w-2/3">
        <FormInput v-model="paymentForm.bank_name" placeholder="cth. Bank Central Asia" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Bank Account Number</span>
      <div class="mt-0.5 w-2/3">
        <FormInput v-model="paymentForm.account_number" placeholder="cth. 1234567890" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Bank Address</span>
      <div class="mt-0.5 w-2/3">
        <FormTextarea v-model="paymentForm.bank_address" rows="2" placeholder="Alamat cabang bank" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Credit Facility</span>
      <div class="flex gap-2 mt-0.5 w-2/3">
        <FormCheck :class="paymentForm.has_credit ? 'w-1/2' : ''">
          <FormCheck.Input id="payment-has-credit" type="checkbox" v-model="paymentForm.has_credit" />
          <FormCheck.Label class="text-xs" htmlFor="payment-has-credit">Punya Credit Facility</FormCheck.Label>
        </FormCheck>
        <FormInput v-if="paymentForm.has_credit" v-model="paymentForm.creditor_name" class="w-1/2"
          placeholder="Nama Creditor" />
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Tax Invoice</span>
      <div class="mt-0.5 w-2/3">
        <FormCheck>
          <FormCheck.Input id="payment-invoice-tax" type="checkbox" v-model="paymentForm.invoice_tax" />
          <FormCheck.Label class="text-xs" htmlFor="payment-invoice-tax">Tax Invoice (Faktur Pajak)
          </FormCheck.Label>
        </FormCheck>
      </div>
    </div>
    <div class="flex justify-between items-center gap-4 py-1.5 border-slate-100 border-b">
      <span class="font-label text-[14px]">Catatan</span>
      <div class="mt-0.5 w-2/3">
        <FormTextarea v-model="paymentForm.note" rows="2" placeholder="Catatan tambahan (opsional)" />
      </div>
    </div>
  </FormModal>
</template>
