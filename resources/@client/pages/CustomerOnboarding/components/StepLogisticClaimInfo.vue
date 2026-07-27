<script setup lang="ts">
import { FormCheck, FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'

import type { OnboardingForm } from '../types'
import {
  operatingHoursOptions,
  qualityCheckingOptions,
  quantityCheckingOptions,
  siteEnvironmentOptions,
  storageTypeOptions,
} from '../optionSets'

defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
}>()
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Logistic Info</h2>
    <p class="font-caption">
      Informasi ini membantu tim logistik kami mempersiapkan armada dan proses pengiriman yang sesuai dengan lokasi
      dan kondisi site Anda.
    </p>

    <div class="grid grid-cols-3 gap-6">
      <!-- Site Environment -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">SITE ENVIRONMENT</div>
        <p class="font-caption mb-3">Kondisi lingkungan sekitar lokasi penerimaan barang.</p>
        <div class="space-y-2">
          <FormCheck v-for="opt in siteEnvironmentOptions" :key="opt.value">
            <FormCheck.Input :id="'site-env-' + opt.value" type="radio" :value="opt.value"
              v-model="form.logistics.site_environment" />
            <FormCheck.Label :htmlFor="'site-env-' + opt.value">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="site-env-other" type="radio" value="other" v-model="form.logistics.site_environment" />
            <FormCheck.Label htmlFor="site-env-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.logistics.site_environment === 'other'" v-model="form.logistics.site_environment_other"
            type="text" placeholder="Specify" />
          <small v-if="errors['logistics.site_environment']" class="block input-error-text">{{
            errors['logistics.site_environment'] }}</small>
        </div>

        <div class="mt-4">
          <FormLabel class="font-label !mb-1 block">Site Environment Notes</FormLabel>
          <FormTextarea v-model="form.logistics.site_environment_notes" rows="3"
            placeholder="Catatan tambahan mengenai lokasi (optional)" />
        </div>
      </div>

      <!-- Storage Type -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">STORAGE TYPE</div>
        <p class="font-caption mb-3">Cara barang disimpan sebelum digunakan.</p>
        <div class="space-y-2">
          <FormCheck v-for="opt in storageTypeOptions" :key="opt.value">
            <FormCheck.Input :id="'storage-type-' + opt.value" type="radio" :value="opt.value"
              v-model="form.logistics.storage_type" />
            <FormCheck.Label :htmlFor="'storage-type-' + opt.value">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="storage-type-other" type="radio" value="other" v-model="form.logistics.storage_type" />
            <FormCheck.Label htmlFor="storage-type-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.logistics.storage_type === 'other'" v-model="form.logistics.storage_type_other"
            type="text" placeholder="Specify" />
          <small v-if="errors['logistics.storage_type']" class="block input-error-text">{{
            errors['logistics.storage_type'] }}</small>
        </div>

        <div class="mt-4">
          <FormLabel class="font-label !mb-1 block">Storage Notes</FormLabel>
          <FormTextarea v-model="form.logistics.storage_notes" rows="3"
            placeholder="Catatan tambahan mengenai penyimpanan (optional)" />
        </div>
      </div>

      <!-- Operating Hours -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">OPERATING HOURS</div>
        <p class="font-caption mb-3">Jam operasional site untuk penerimaan pengiriman barang.</p>
        <div class="space-y-2">
          <FormCheck v-for="opt in operatingHoursOptions" :key="opt.value">
            <FormCheck.Input :id="'operating-hours-' + opt.value" type="radio" :value="opt.value"
              v-model="form.logistics.operating_hours" />
            <FormCheck.Label :htmlFor="'operating-hours-' + opt.value">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="operating-hours-other" type="radio" value="other"
              v-model="form.logistics.operating_hours" />
            <FormCheck.Label htmlFor="operating-hours-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.logistics.operating_hours === 'other'" v-model="form.logistics.operating_hours_other"
            type="text" placeholder="Specify" />
          <small v-if="errors['logistics.operating_hours']" class="block input-error-text">{{
            errors['logistics.operating_hours'] }}</small>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
      <!-- Quality Checking -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">QUALITY CHECKING</div>
        <p class="font-caption mb-3">Metode pengecekan kualitas barang yang berlaku di site Anda.</p>
        <div class="space-y-2">
          <FormCheck v-for="opt in qualityCheckingOptions" :key="opt.value">
            <FormCheck.Input :id="'quality-checking-' + opt.value" type="radio" :value="opt.value"
              v-model="form.logistics.quality_checking_method" />
            <FormCheck.Label :htmlFor="'quality-checking-' + opt.value">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="quality-checking-other" type="radio" value="other"
              v-model="form.logistics.quality_checking_method" />
            <FormCheck.Label htmlFor="quality-checking-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.logistics.quality_checking_method === 'other'"
            v-model="form.logistics.quality_checking_notes" type="text" placeholder="Specify" />
          <small v-if="errors['logistics.quality_checking_method']" class="block input-error-text">{{
            errors['logistics.quality_checking_method'] }}</small>
        </div>
      </div>

      <!-- Quantity Checking -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">QUANTITY CHECKING</div>
        <p class="font-caption mb-3">Metode pengecekan kuantitas/jumlah barang yang berlaku di site Anda.</p>
        <div class="space-y-2">
          <FormCheck v-for="opt in quantityCheckingOptions" :key="opt.value">
            <FormCheck.Input :id="'quantity-checking-' + opt.value" type="radio" :value="opt.value"
              v-model="form.logistics.quantity_checking_method" />
            <FormCheck.Label :htmlFor="'quantity-checking-' + opt.value">{{ opt.label }}</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input id="quantity-checking-other" type="radio" value="other"
              v-model="form.logistics.quantity_checking_method" />
            <FormCheck.Label htmlFor="quantity-checking-other">Other,</FormCheck.Label>
          </FormCheck>
          <FormInput v-if="form.logistics.quantity_checking_method === 'other'"
            v-model="form.logistics.quantity_checking_notes" type="text" placeholder="Specify" />
          <small v-if="errors['logistics.quantity_checking_method']" class="block input-error-text">{{
            errors['logistics.quantity_checking_method'] }}</small>
        </div>
      </div>
    </div>

    <!-- Max Truck Capacity + Vessel -->
    <div class="grid gap-6 md:grid-cols-2">
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">TRUCK CAPACITY (m³)</div>
        <p class="font-caption mb-3">Kapasitas volume truk yang bisa diakomodasi site Anda untuk keperluan bongkar muat.
        </p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <FormLabel class="font-label !mb-1 block">Min</FormLabel>
            <FormInput v-model="form.logistics.max_truck_capacity_min" type="number" min="0" placeholder="e.g. 8" />
          </div>
          <div>
            <FormLabel class="font-label !mb-1 block">Max</FormLabel>
            <FormInput v-model="form.logistics.max_truck_capacity_max" type="number" min="0" placeholder="e.g. 20" />
          </div>
        </div>
      </div>

      <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="font-section mb-1 border-b border-slate-100 pb-2">SUPPORTS VESSEL DELIVERY?</div>
        <p class="font-caption mb-3">Apakah site Anda bisa menerima pengiriman lewat kapal/vessel selain jalur darat.
        </p>
        <div class="grid max-w-xs grid-cols-2 gap-3">
          <div class="cursor-pointer rounded-lg border py-2 text-center transition-colors"
            :class="form.logistics.supports_vessel_delivery === true ? 'border-2 border-primary bg-primary/5 font-semibold text-primary' : 'border-gray-200 hover:border-primary/30'"
            @click="form.logistics.supports_vessel_delivery = true">
            Yes
          </div>
          <div class="cursor-pointer rounded-lg border py-2 text-center transition-colors"
            :class="form.logistics.supports_vessel_delivery === false ? 'border-2 border-primary bg-primary/5 font-semibold text-primary' : 'border-gray-200 hover:border-primary/30'"
            @click="form.logistics.supports_vessel_delivery = false">
            No
          </div>
        </div>
      </div>
    </div>

    <!-- Product Notes, Volume -->
    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel class="font-label !mb-1 block">Product Notes</FormLabel>
          <FormTextarea v-model="form.logistics.product_notes" rows="3"
            placeholder="Catatan tambahan terkait produk (optional)" />
        </div>
        <div>
          <FormLabel class="font-label !mb-1 block">Est. Monthly Volume (m³)</FormLabel>
          <FormInput v-model="form.logistics.estimated_monthly_volume" type="number" min="0" placeholder="e.g. 500" />
        </div>
      </div>
    </div>
  </div>
</template>
