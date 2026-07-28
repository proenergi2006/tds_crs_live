<script setup lang="ts">
import { watch } from 'vue'

import { FormCheck, FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'

import type { OnboardingForm } from '../types'
import { paymentMethodOptions, paymentTermBasisOptions, paymentTermOptions } from '../optionSets'

const props = defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
}>()

watch(() => props.form.payment.term, (val) => {
  if (val !== 'CREDIT') {
    props.form.payment.term_days = null
    props.form.payment.term_basis = ''
  }
})
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Payment Info</h2>
    <p class="font-caption">Lengkapi metode dan termin pembayaran, serta data rekening bank yang digunakan.</p>

    <div class="grid grid-cols-2 gap-6">
      <!-- Payment Method -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">PAYMENT METHOD</div>
        <div class="space-y-2">
          <FormCheck v-for="(opt, idx) in paymentMethodOptions" :key="opt">
            <FormCheck.Input :id="'payment-method-' + idx" type="radio" :value="opt" v-model="form.payment.method" />
            <FormCheck.Label :htmlFor="'payment-method-' + idx">{{ opt }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="payment-method-other" type="radio" value="Other" v-model="form.payment.method" />
            <FormCheck.Label htmlFor="payment-method-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.payment.method === 'Other'" v-model="form.payment.method_other" type="text"
            placeholder="Specify" />
          <small v-if="errors['payment.method']" class="block input-error-text">{{ errors['payment.method'] }}</small>
        </div>
      </div>

      <!-- Payment Term -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">PAYMENT TERM</div>
        <div class="space-y-2">
          <FormCheck v-for="opt in paymentTermOptions" :key="opt.code">
            <FormCheck.Input :id="'payment-term-' + opt.code" type="radio" :value="opt.code"
              v-model="form.payment.term" />
            <FormCheck.Label :htmlFor="'payment-term-' + opt.code">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
        </div>

        <div v-if="form.payment.term === 'CREDIT'" class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <FormLabel class="font-label !mb-1 block">Term Days</FormLabel>
            <FormInput v-model="form.payment.term_days" type="number" min="0"
              :class="errors['payment.term_days'] ? 'input-error' : ''" />
            <small v-if="errors['payment.term_days']" class="block input-error-text">{{ errors['payment.term_days']
            }}</small>
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Term Basis</FormLabel>
            <FormSelect v-model="form.payment.term_basis" :class="errors['payment.term_basis'] ? 'input-error' : ''">
              <option value="">Select one</option>
              <option v-for="opt in paymentTermBasisOptions" :key="opt.code" :value="opt.code">{{ opt.label }}</option>
            </FormSelect>
            <small v-if="errors['payment.term_basis']" class="block input-error-text">{{ errors['payment.term_basis']
            }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Bank -->
    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="font-section mb-1 border-b border-slate-100 pb-2">BANK</div>
      <p class="font-caption mb-3">Rekening bank yang digunakan perusahaan untuk menerima pembayaran/refund.</p>
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <FormLabel class="font-label !mb-1 block">Bank Name</FormLabel>
          <FormInput v-model="form.payment.bank_name" type="text" placeholder="e.g. Bank Mandiri" />
        </div>
        <div>
          <FormLabel class="font-label !mb-1 block">Account Number</FormLabel>
          <FormInput v-model="form.payment.account_number" type="text" placeholder="e.g. 1234567890" />
        </div>
        <div>
          <FormLabel class="font-label !mb-1 block">Currency</FormLabel>
          <FormInput :model-value="form.payment.currency" type="text" disabled />
        </div>
        <div class="md:col-span-3">
          <FormLabel class="font-label !mb-1 block">Bank Address</FormLabel>
          <FormTextarea v-model="form.payment.bank_address" rows="3" placeholder="Alamat kantor cabang bank" />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
      <!-- Credit Facility -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-2">Have Credit Facility or Bank Loan?</div>
        <p class="font-caption mb-3">Informasi ini membantu proses evaluasi kredit oleh tim finance kami.</p>
        <div class="grid max-w-xs grid-cols-2 gap-3">
          <div class="cursor-pointer rounded-lg border py-2 text-center transition-colors"
            :class="form.payment.has_credit === true ? 'border-2 border-primary bg-primary/5 font-semibold text-primary' : 'border-gray-200 hover:border-primary/30'"
            @click="form.payment.has_credit = true">
            Yes
          </div>
          <div class="cursor-pointer rounded-lg border py-2 text-center transition-colors"
            :class="form.payment.has_credit === false ? 'border-2 border-primary bg-primary/5 font-semibold text-primary' : 'border-gray-200 hover:border-primary/30'"
            @click="form.payment.has_credit = false">
            No
          </div>
        </div>
        <FormInput v-if="form.payment.has_credit === true" v-model="form.payment.creditor_name" type="text" class="mt-3"
          placeholder="Credit or Loan Provider Name" />
      </div>

      <!-- Tax & Remarks -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <FormCheck class="mb-4">
          <FormCheck.Input id="invoice-tax" type="checkbox" v-model="form.payment.invoice_tax" />
          <FormCheck.Label htmlFor="invoice-tax">Tax Invoice (Faktur Pajak)</FormCheck.Label>
        </FormCheck>

        <FormLabel class="font-label !mb-1 block">Remarks</FormLabel>
        <FormTextarea v-model="form.payment.note" rows="4"
          placeholder="Catatan tambahan terkait pembayaran (optional)" />
      </div>
    </div>
  </div>
</template>
