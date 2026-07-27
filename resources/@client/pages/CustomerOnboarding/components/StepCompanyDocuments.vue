<script setup lang="ts">
import { FormInput, FormLabel } from '@/components/Base/Form'
import FileUploadField from '@/components/SystemDesign/Form/FileUploadField.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

import type { OnboardingForm } from '../types'

defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
}>()
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Document Attachments</h2>
    <p class="font-caption">Upload dokumen legalitas perusahaan berikut untuk kelengkapan proses verifikasi KYC.</p>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- NIB -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <FormLabel class="font-label !mb-1 block">NIB
          <RequiredAsterisk />
        </FormLabel>
        <FormInput v-model="form.documents.nib.number" type="text" class="mb-1" placeholder="e.g. 1234567890123"
          :class="errors['documents.nib.number'] ? 'input-error' : ''" />
        <small v-if="errors['documents.nib.number']" class="block input-error-text">{{ errors['documents.nib.number']
          }}</small>
        <p class="font-caption mb-2">Wajib diisi.</p>
        <FileUploadField v-model="form.documents.nib.file" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10"
          :error="errors['documents.nib.file']" />
      </div>

      <!-- NPWP -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <FormLabel class="font-label !mb-1 block">NPWP
          <RequiredAsterisk />
        </FormLabel>
        <FormInput v-model="form.documents.npwp.number" type="text" class="mb-1" placeholder="e.g. 01.234.567.8-901.000"
          :class="errors['documents.npwp.number'] ? 'input-error' : ''" />
        <small v-if="errors['documents.npwp.number']" class="block input-error-text">{{ errors['documents.npwp.number']
          }}</small>
        <p class="font-caption mb-2">Wajib diisi.</p>
        <FileUploadField v-model="form.documents.npwp.file" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10"
          :error="errors['documents.npwp.file']" />
      </div>

      <!-- Akta Pendirian -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <FormLabel class="font-label !mb-1 block">Akta Pendirian</FormLabel>
        <FormInput v-model="form.documents.sertifikat.number" type="text" class="mb-1"
          placeholder="Nomor akta pendirian" :class="errors['documents.sertifikat.number'] ? 'input-error' : ''" />
        <small v-if="errors['documents.sertifikat.number']" class="block input-error-text">{{
          errors['documents.sertifikat.number'] }}</small>
        <!-- <p class="font-caption mb-2">Wajib diisi kalau file dipilih.</p> -->
        <FileUploadField v-model="form.documents.sertifikat.file" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
          :max-size-mb="10" :error="errors['documents.sertifikat.file']" />
      </div>

      <!-- Dokumen Lainnya -->
      <div class="rounded-lg bg-white p-6 shadow-sm">
        <FormLabel class="font-label !mb-1 block">Dokumen Lainnya</FormLabel>
        <p class="font-caption mb-2">Opsional, bisa lebih dari satu file.</p>
        <FileUploadField :model-value="form.documents.dokumen_lainnya"
          @update:model-value="(v) => (form.documents.dokumen_lainnya = Array.isArray(v) ? v : v ? [v] : [])"
          accept=".jpg,.jpeg,.png,.pdf,.zip,.rar" :max-size-mb="10" :multiple="true" />
      </div>
    </div>

    <p class="font-caption">
      * Max size 10MB per file. Ekstensi yang diizinkan: jpg, jpeg, png, pdf, zip, rar.
    </p>
  </div>
</template>
