<script setup lang="ts">
import { ref } from 'vue'

import Button from '@/components/Base/Button'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'
import { useNotification } from '@/components/SystemDesign/Notification/useNotification'

import type { OnboardingExistingDocument, OnboardingExistingDocuments, OnboardingForm } from '../types'

const props = defineProps<{
  form: OnboardingForm
  errors: Record<string, string>
  existingDocuments?: OnboardingExistingDocuments
}>()

const { error: notifyError } = useNotification()

const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024

type FixedDocumentCode = 'nib' | 'npwp'

const fixedDocuments: { code: FixedDocumentCode; label: string; required: boolean }[] = [
  { code: 'nib', label: 'NIB', required: true },
  { code: 'npwp', label: 'NPWP', required: true },
]

const fixedDocInputRef = ref<HTMLInputElement | null>(null)
const activeFixedCode = ref<FixedDocumentCode | null>(null)
const fixedDocSizeErrors = ref<Partial<Record<FixedDocumentCode, string>>>({})

const dokumenLainnyaInputRef = ref<HTMLInputElement | null>(null)

function openFixedDocPicker(code: FixedDocumentCode) {
  activeFixedCode.value = code
  fixedDocInputRef.value?.click()
}

function fixedDocFileError(code: FixedDocumentCode) {
  return props.errors[`documents.${code}.file`] || fixedDocSizeErrors.value[code] || ''
}

function handleFixedDocFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  const code = activeFixedCode.value

  if (file && code) {
    if (file.size > MAX_FILE_SIZE_BYTES) {
      fixedDocSizeErrors.value[code] = `Ukuran file "${file.name}" melebihi 5MB.`
    } else {
      fixedDocSizeErrors.value[code] = ''
      props.form.documents[code].file = file
    }
  }

  input.value = ''
  activeFixedCode.value = null
}

function addDokumenLainnyaRow() {
  props.form.documents.dokumen_lainnya.push({ file: null, label: '' })
}

const activeDokumenLainnyaIndex = ref<number | null>(null)
function openDokumenLainnyaFilePicker(idx: number) {
  activeDokumenLainnyaIndex.value = idx
  dokumenLainnyaInputRef.value?.click()
}

function handleDokumenLainnyaFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  const idx = activeDokumenLainnyaIndex.value

  if (file && idx !== null) {
    if (file.size > MAX_FILE_SIZE_BYTES) {
      notifyError('Ukuran File Terlalu Besar', `File "${file.name}" melebihi 5MB.`)
    } else {
      props.form.documents.dokumen_lainnya[idx].file = file
    }
  }

  input.value = ''
  activeDokumenLainnyaIndex.value = null
}

function removeDokumenLainnya(idx: number) {
  props.form.documents.dokumen_lainnya.splice(idx, 1)
}

function removeExistingDokumenLainnya(idx: number, id?: string | number) {
  if (id !== undefined) props.form.documents.remove_document_ids.push(Number(id))
  props.existingDocuments!.dokumen_lainnya.splice(idx, 1)
}

function replaceExistingDokumenLainnya(idx: number, doc: OnboardingExistingDocument) {
  if (doc.id !== undefined) props.form.documents.remove_document_ids.push(Number(doc.id))
  props.existingDocuments!.dokumen_lainnya.splice(idx, 1)
  props.form.documents.dokumen_lainnya.push({ file: null, label: doc.label ?? doc.name })
  openDokumenLainnyaFilePicker(props.form.documents.dokumen_lainnya.length - 1)
}
</script>

<template>
  <div class="space-y-6">
    <h2 class="font-header text-xl">Document Attachments</h2>
    <p class="font-caption">Upload dokumen legalitas perusahaan berikut untuk kelengkapan proses verifikasi KYC.</p>

    <div class="rounded-lg bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between gap-4">
        <FormLabel class="font-label !mb-0 block">Dokumen Perusahaan</FormLabel>
        <Button type="button" size="sm" variant="outline-primary" class="inline-flex items-center gap-2"
          @click="addDokumenLainnyaRow">
          <Lucide icon="Plus" class="h-4 w-4" />
          Tambah
        </Button>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[640px] divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="w-12 px-3 py-2 font-label text-center">No</th>
              <th class="px-3 py-2 font-label text-left">Nama Dokumen</th>
              <th class="px-3 py-2 font-label text-left">Nomor Dokumen</th>
              <th class="px-3 py-2 font-label text-left">File</th>
              <th class="w-28 px-3 py-2 font-label text-center">Aksi</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(doc, idx) in fixedDocuments" :key="doc.code" class="transition hover:bg-slate-50">
              <td class="px-3 py-2 font-num text-center">{{ idx + 1 }}.</td>
              <td class="px-3 py-2 font-body">
                {{ doc.label }}
                <RequiredAsterisk v-if="doc.required" />
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="form.documents[doc.code].number" type="text" placeholder="Nomor dokumen"
                  :class="errors[`documents.${doc.code}.number`] ? 'input-error' : ''" />
                <small v-if="errors[`documents.${doc.code}.number`]" class="block input-error-text">
                  {{ errors[`documents.${doc.code}.number`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <span v-if="form.documents[doc.code].file" class="font-body italic text-amber-600">
                  {{ form.documents[doc.code].file?.name }}
                </span>
                <a v-else-if="existingDocuments?.[doc.code]?.[0]?.url" :href="existingDocuments[doc.code][0].url"
                  target="_blank" class="font-body !text-primary underline">
                  {{ existingDocuments[doc.code][0].name }}
                </a>
                <span v-else-if="existingDocuments?.[doc.code]?.[0]" class="font-body">
                  {{ existingDocuments[doc.code][0].name }}
                </span>
                <span v-else class="font-body text-slate-400">Belum ada file</span>
                <small v-if="fixedDocFileError(doc.code)" class="block input-error-text">
                  {{ fixedDocFileError(doc.code) }}
                </small>
              </td>
              <td class="px-3 py-2 text-center">
                <Button type="button" size="sm" variant="outline-secondary" class="inline-flex items-center gap-1"
                  @click="openFixedDocPicker(doc.code)">
                  <Lucide :icon="existingDocuments?.[doc.code]?.[0] || form.documents[doc.code].file ? 'RefreshCw' : 'Upload'" class="h-3.5 w-3.5" />
                  {{ existingDocuments?.[doc.code]?.[0] || form.documents[doc.code].file ? 'Ganti' : 'Pilih File' }}
                </Button>
              </td>
            </tr>

            <tr v-for="(doc, docIdx) in existingDocuments?.dokumen_lainnya" :key="doc.id ?? doc.name">
              <td class="px-3 py-2 font-num text-center">{{ fixedDocuments.length + docIdx + 1 }}.</td>
              <td class="px-3 py-2 font-body">{{ doc.label || doc.name }}</td>
              <td class="px-3 py-2 font-body">-</td>
              <td class="px-3 py-2">
                <a v-if="doc.url" :href="doc.url" target="_blank" class="font-body !text-primary underline">
                  {{ doc.name }}
                </a>
                <span v-else class="font-body">{{ doc.name }}</span>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-1.5">
                  <Button type="button" size="sm" variant="outline-secondary" class="inline-flex items-center gap-1"
                    @click="replaceExistingDokumenLainnya(docIdx, doc)">
                    <Lucide icon="RefreshCw" class="h-3.5 w-3.5" />
                    Ganti
                  </Button>
                  <Button type="button" variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none" title="Hapus"
                    @click="removeExistingDokumenLainnya(docIdx, doc.id)">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </Button>
                </div>
              </td>
            </tr>

            <tr v-for="(item, idx) in form.documents.dokumen_lainnya" :key="idx" class="transition hover:bg-slate-50">
              <td class="px-3 py-2 font-num text-center">
                {{ fixedDocuments.length + (existingDocuments?.dokumen_lainnya.length ?? 0) + idx + 1 }}.
              </td>
              <td class="px-3 py-2">
                <FormInput v-model="item.label" type="text" placeholder="Nama dokumen"
                  :class="errors[`documents.dokumen_lainnya.${idx}.label`] ? 'input-error' : ''" />
                <small v-if="errors[`documents.dokumen_lainnya.${idx}.label`]" class="block input-error-text">
                  {{ errors[`documents.dokumen_lainnya.${idx}.label`] }}
                </small>
              </td>
              <td class="px-3 py-2 font-body">-</td>
              <td class="px-3 py-2">
                <span v-if="item.file" class="font-body">{{ item.file.name }}</span>
                <span v-else class="font-body text-slate-400">Belum ada file</span>
                <small v-if="errors[`documents.dokumen_lainnya.${idx}.file`]" class="block input-error-text">
                  {{ errors[`documents.dokumen_lainnya.${idx}.file`] }}
                </small>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-1.5">
                  <Button type="button" size="sm" variant="outline-secondary" class="inline-flex items-center gap-1"
                    @click="openDokumenLainnyaFilePicker(idx)">
                    <Lucide :icon="item.file ? 'RefreshCw' : 'Upload'" class="h-3.5 w-3.5" />
                    {{ item.file ? 'Ganti' : 'Pilih File' }}
                  </Button>
                  <Button type="button" variant="soft-danger" rounded class="!h-9 !w-9 !p-0 !shadow-none"
                    title="Hapus" @click="removeDokumenLainnya(idx)">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <input ref="fixedDocInputRef" type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
        @change="handleFixedDocFileSelected" />
      <input ref="dokumenLainnyaInputRef" type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf,.zip,.rar"
        @change="handleDokumenLainnyaFileSelected" />
    </div>

    <p class="font-caption">
      * Max size 5MB per file. Ekstensi yang diizinkan: jpg, jpeg, png, pdf, zip, rar.
    </p>
  </div>
</template>
