<script setup lang="ts">
import { computed } from 'vue'

import { FormInput, FormLabel } from '@/components/Base/Form'
import type { RegionOption, useRegionCascade } from '@/composables/useRegionCascade'

import type { OnboardingForm } from '../types'
import {
  incoTermsOptions,
  operatingHoursOptions,
  paymentMethodOptions,
  paymentTermBasisOptions,
  paymentTermOptions,
  qualityCheckingOptions,
  quantityCheckingOptions,
  resolveOptionLabel,
  siteEnvironmentOptions,
  storageTypeOptions,
} from '../optionSets'

const props = defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
  headOfficeRegion: ReturnType<typeof useRegionCascade>
  npwpRegion: ReturnType<typeof useRegionCascade>
}>()

function resolveRegionName(id: string | null | undefined, options: RegionOption[]): string {
  if (!id) return '-'
  return options.find((o) => o.id === id)?.name || '-'
}

function withOther(value: string, otherValue: string, options: Array<{ code?: string; value?: string; label: string }>): string {
  if (!value) return '-'
  if (value === 'Other' || value === 'other') return otherValue || 'Other'
  return resolveOptionLabel(value, options)
}

const summarySections = computed(() => [
  {
    title: 'Company Information',
    rows: [
      { label: 'Company Name', value: props.form.identity.company_name || '-' },
      { label: 'Holding', value: props.form.identity.parent_company || '-' },
      { label: 'Head Office Address', value: props.form.identity.company_address || '-' },
      {
        label: 'Head Office Province',
        value: resolveRegionName(props.form.identity.province_id, props.headOfficeRegion.provinces.value),
      },
      {
        label: 'Head Office Regency',
        value: resolveRegionName(props.form.identity.regency_id, props.headOfficeRegion.regencies.value),
      },
      {
        label: 'Head Office District',
        value: resolveRegionName(props.form.identity.district_id, props.headOfficeRegion.districts.value),
      },
      {
        label: 'Head Office Village',
        value: resolveRegionName(props.form.identity.village_id, props.headOfficeRegion.villages.value),
      },
      { label: 'Postal Code', value: props.form.identity.postal_code || '-' },
      { label: 'Phone', value: props.form.identity.phone || '-' },
      { label: 'Fax', value: props.form.identity.fax || '-' },
      { label: 'Email', value: props.form.identity.email || '-' },
      { label: 'Website', value: props.form.identity.website || '-' },
      { label: 'NPWP Address', value: props.form.registered_address.address_line || '-' },
      {
        label: 'NPWP Province',
        value: resolveRegionName(props.form.registered_address.province_id, props.npwpRegion.provinces.value),
      },
      {
        label: 'NPWP Regency',
        value: resolveRegionName(props.form.registered_address.regency_id, props.npwpRegion.regencies.value),
      },
      {
        label: 'NPWP District',
        value: resolveRegionName(props.form.registered_address.district_id, props.npwpRegion.districts.value),
      },
      {
        label: 'NPWP Village',
        value: resolveRegionName(props.form.registered_address.village_id, props.npwpRegion.villages.value),
      },
      {
        label: 'Type of Business',
        value: props.form.identity.business_type === 'Other'
          ? (props.form.identity.business_type_other || '-')
          : (props.form.identity.business_type || '-'),
      },
      {
        label: 'Ownership',
        value: props.form.identity.ownership_type === 'Other'
          ? (props.form.identity.ownership_type_other || '-')
          : (props.form.identity.ownership_type || '-'),
      },
      {
        label: 'Incoterms',
        value: withOther(props.form.identity.inco_terms, props.form.identity.inco_terms_other, incoTermsOptions),
      },
    ],
  },
  ...props.form.contacts.map((contact, idx) => ({
    title: contact.full_name ? `Kontak #${idx + 1} - ${contact.full_name}` : `Kontak #${idx + 1}`,
    rows: [
      { label: 'Name', value: contact.full_name || '-' },
      { label: 'Division/Bagian', value: contact.position || '-' },
      { label: 'Phone', value: contact.phone || '-' },
      { label: 'Mobile', value: contact.mobile || '-' },
      { label: 'Email', value: contact.email || '-' },
    ],
  })),
  {
    title: 'Document Attachments',
    rows: [
      { label: 'NIB', value: props.form.documents.nib.number || '-' },
      { label: 'NPWP', value: props.form.documents.npwp.number || '-' },
      {
        label: 'Dokumen Lainnya',
        value: props.form.documents.dokumen_lainnya.length > 0
          ? `${props.form.documents.dokumen_lainnya.length} file(s) uploaded`
          : '-',
      },
    ],
  },
  {
    title: 'Payment Info',
    rows: [
      { label: 'Pricing Method Calculation', value: props.form.payment.pricing_method || '-' },
      {
        label: 'Payment Method',
        value: withOther(props.form.payment.method, props.form.payment.method_other, paymentMethodOptions.map((v) => ({ value: v, label: v }))),
      },
      { label: 'Payment Term', value: props.form.payment.term ? resolveOptionLabel(props.form.payment.term, paymentTermOptions) : '-' },
      {
        label: 'Term Days',
        value: props.form.payment.term === 'CREDIT' && props.form.payment.term_days !== null
          ? String(props.form.payment.term_days)
          : '-',
      },
      {
        label: 'Term Basis',
        value: props.form.payment.term === 'CREDIT' && props.form.payment.term_basis
          ? resolveOptionLabel(props.form.payment.term_basis, paymentTermBasisOptions)
          : '-',
      },
      { label: 'Bank Name', value: props.form.payment.bank_name || '-' },
      { label: 'Bank Address', value: props.form.payment.bank_address || '-' },
      { label: 'Account Number', value: props.form.payment.account_number || '-' },
      { label: 'Currency', value: props.form.payment.currency || '-' },
      {
        label: 'Have Credit Facility',
        value: props.form.payment.has_credit
          ? (props.form.payment.creditor_name ? `Yes (${props.form.payment.creditor_name})` : 'Yes')
          : 'No',
      },
      { label: 'Tax Invoice', value: props.form.payment.invoice_tax ? 'Yes' : 'No' },
      { label: 'Remarks', value: props.form.payment.note || '-' },
    ],
  },
  {
    title: 'Logistic Info',
    rows: [
      {
        label: 'Site Environment',
        value: withOther(props.form.logistics.site_environment, props.form.logistics.site_environment_other, siteEnvironmentOptions),
      },
      { label: 'Site Environment Notes', value: props.form.logistics.site_environment_notes || '-' },
      {
        label: 'Storage Type',
        value: withOther(props.form.logistics.storage_type, props.form.logistics.storage_type_other, storageTypeOptions),
      },
      { label: 'Storage Notes', value: props.form.logistics.storage_notes || '-' },
      {
        label: 'Operating Hours',
        value: withOther(props.form.logistics.operating_hours, props.form.logistics.operating_hours_other, operatingHoursOptions),
      },
      {
        label: 'Quality Checking',
        value: props.form.logistics.quality_checking_method === 'other'
          ? (props.form.logistics.quality_checking_notes || 'Other')
          : props.form.logistics.quality_checking_method
            ? resolveOptionLabel(props.form.logistics.quality_checking_method, qualityCheckingOptions)
            : '-',
      },
      {
        label: 'Quantity Checking',
        value: props.form.logistics.quantity_checking_method === 'other'
          ? (props.form.logistics.quantity_checking_notes || 'Other')
          : props.form.logistics.quantity_checking_method
            ? resolveOptionLabel(props.form.logistics.quantity_checking_method, quantityCheckingOptions)
            : '-',
      },
      {
        label: 'Max Truck Capacity (m³)',
        value: (props.form.logistics.max_truck_capacity_min !== null || props.form.logistics.max_truck_capacity_max !== null)
          ? `${props.form.logistics.max_truck_capacity_min ?? '-'} - ${props.form.logistics.max_truck_capacity_max ?? '-'}`
          : '-',
      },
      { label: 'Supports Vessel Delivery', value: props.form.logistics.supports_vessel_delivery ? 'Yes' : 'No' },
      { label: 'Product Notes', value: props.form.logistics.product_notes || '-' },
      {
        label: 'Est. Monthly Volume',
        value: props.form.logistics.estimated_monthly_volume !== null ? String(props.form.logistics.estimated_monthly_volume) : '-',
      },
    ],
  },
])
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Summary & Agreement</h2>

    <div class="space-y-5">
      <div v-for="section in summarySections" :key="section.title" class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-3 border-b border-slate-100 pb-2">{{ section.title }}</div>
        <div class="grid gap-3 rounded bg-slate-50 p-4 md:grid-cols-2">
          <div v-for="row in section.rows" :key="row.label"
            class="flex justify-between gap-4 border-b border-slate-200 pb-1">
            <span class="font-label">{{ row.label }}</span>
            <span class="font-strong text-right">{{ row.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="font-section mb-3 border-b border-slate-100 pb-2">AGREEMENT</div>

      <div class="space-y-4">
        <div>
          <FormLabel class="font-label !mb-1 block">Updated By
            <span class="ml-0.5 text-danger" aria-hidden="true">*</span>
          </FormLabel>
          <FormInput v-model="form.agreement.updated_by" type="text" placeholder="Nama pengisi form"
            :class="errors['agreement.updated_by'] ? 'input-error' : ''" />
          <small v-if="errors['agreement.updated_by']" class="block input-error-text">{{ errors['agreement.updated_by']
          }}</small>
        </div>

        <div>
          <label class="inline-flex items-center gap-3">
            <input type="checkbox" v-model="form.agreement.agree" />
            <span class="font-body cursor-pointer hover:underline">
              Setujui Terms &amp; Conditions: saya menyatakan bahwa data di atas benar adanya.
            </span>
          </label>
          <small v-if="errors['agreement.agree']" class="block input-error-text">{{ errors['agreement.agree'] }}</small>
        </div>
      </div>
    </div>
  </div>
</template>
