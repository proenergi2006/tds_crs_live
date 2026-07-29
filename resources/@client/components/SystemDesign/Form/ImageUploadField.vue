<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'

import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'

// Pengembangan dari FileUploadField.vue (baris ikon generik, tidak ada
// thumbnail) jadi grid thumbnail gambar. File tersimpan pakai
// existingFiles[].url (sudah ada), file baru pakai URL.createObjectURL() --
// WAJIB revoke saat file dihapus atau component unmount supaya tidak leak
// memory.

type ExistingImage = {
  id?: string | number;
  name: string;
  url: string;
  size?: number;
}

type ModelValue = File | File[] | null

const props = withDefaults(
  defineProps<{
    modelValue: ModelValue;
    existingFiles?: ExistingImage[];
    label?: string;
    hint?: string;
    error?: string;
    accept?: string;
    multiple?: boolean;
    maxSizeMb?: number;
    disabled?: boolean;
    chooseText?: string;
    emptyText?: string;
    withCaption?: boolean;
  }>(),
  {
    existingFiles: () => [],
    label: '',
    hint: '',
    error: '',
    accept: 'image/*',
    multiple: true,
    maxSizeMb: 4,
    disabled: false,
    chooseText: 'Pilih gambar',
    emptyText: 'Belum ada gambar dipilih',
    withCaption: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: ModelValue): void;
  (e: 'remove-existing', file: ExistingImage): void;
  (e: 'error', message: string): void;
  (e: 'update:caption', file: File | ExistingImage, caption: string): void;
}>()

const inputRef = ref<HTMLInputElement | null>(null)
const localError = ref('')

const selectedFiles = computed<File[]>(() => {
  if (!props.modelValue) return []

  return Array.isArray(props.modelValue)
    ? props.modelValue
    : [props.modelValue]
})

const visibleExistingFiles = computed(() => {
  if (!props.multiple && selectedFiles.value.length > 0) return []

  return props.existingFiles
})

const hasFiles = computed(() => {
  return selectedFiles.value.length > 0 || visibleExistingFiles.value.length > 0
})

const acceptedText = computed(() => {
  if (!props.accept) return 'Semua tipe file'

  return props.accept
    .split(',')
    .map(item => item.trim().replace('.', '').toUpperCase())
    .filter(Boolean)
    .join(', ')
})

const displayError = computed(() => props.error || localError.value)

/* Object URL lifecycle: dibuat untuk tiap file baru, di-revoke saat file
   dihapus dari selectedFiles atau saat component unmount -- supaya tidak
   menumpuk blob URL (memory leak). */
const objectUrls = ref<Map<File, string>>(new Map())

watch(
  selectedFiles,
  (newFiles) => {
    const newSet = new Set(newFiles)

    for (const [file, url] of objectUrls.value) {
      if (!newSet.has(file)) {
        URL.revokeObjectURL(url)
        objectUrls.value.delete(file)
      }
    }

    for (const file of newFiles) {
      if (!objectUrls.value.has(file)) {
        objectUrls.value.set(file, URL.createObjectURL(file))
      }
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  for (const url of objectUrls.value.values()) {
    URL.revokeObjectURL(url)
  }
})

/* Caption lokal per thumbnail (opsional, withCaption) -- komponen ini tidak
   menyimpan caption di state parent, cuma emit perubahan lewat
   update:caption supaya parent yang menyimpan nilainya. */
const existingCaptions = ref<Map<string | number, string>>(new Map())
const newCaptions = ref<Map<File, string>>(new Map())

function existingCaptionValue(file: ExistingImage) {
  return existingCaptions.value.get(file.id ?? file.name) ?? ''
}

function updateExistingCaption(file: ExistingImage, value: string) {
  existingCaptions.value.set(file.id ?? file.name, value)
  emit('update:caption', file, value)
}

function newCaptionValue(file: File) {
  return newCaptions.value.get(file) ?? ''
}

function updateNewCaption(file: File, value: string) {
  newCaptions.value.set(file, value)
  emit('update:caption', file, value)
}

function openPicker() {
  if (props.disabled) return

  inputRef.value?.click()
}

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files || [])

  localError.value = ''

  if (!files.length) return

  const maxBytes = props.maxSizeMb * 1024 * 1024
  const oversized = files.find(file => file.size > maxBytes)

  if (oversized) {
    const message = `${oversized.name} melebihi ${props.maxSizeMb}MB.`
    localError.value = message
    emit('error', message)
    input.value = ''
    return
  }

  if (props.multiple) {
    emit('update:modelValue', [...selectedFiles.value, ...files])
  } else {
    emit('update:modelValue', files[0])
  }

  input.value = ''
}

function removeSelected(index: number) {
  if (props.disabled) return

  if (!props.multiple) {
    emit('update:modelValue', null)
    return
  }

  emit(
    'update:modelValue',
    selectedFiles.value.filter((_, fileIndex) => fileIndex !== index),
  )
}

function removeExisting(file: ExistingImage) {
  if (props.disabled) return

  emit('remove-existing', file)
}

function formatSize(bytes?: number) {
  if (!bytes) return ''

  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unitIndex = 0

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }

  return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`
}
</script>

<template>
  <div class="space-y-2">
    <div v-if="label || hint" class="flex flex-col gap-1">
      <label v-if="label" class="font-strong">
        {{ label }}
      </label>

      <p v-if="hint" class="font-caption">
        {{ hint }}
      </p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-3 transition"
      :class="[
        displayError ? 'input-error' : 'hover:border-slate-300',
        disabled ? 'opacity-70' : '',
      ]">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2"
            :disabled="disabled" @click="openPicker">
            <Lucide icon="Upload" class="h-4 w-4" />
            {{ chooseText }}
          </Button>

          <input ref="inputRef" type="file" class="hidden" :accept="accept" :multiple="multiple"
            :disabled="disabled" @change="onFileChange" />
        </div>

        <div class="font-caption">
          {{ acceptedText }} - Maks {{ maxSizeMb }}MB
        </div>
      </div>

      <div v-if="hasFiles" class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4">
        <div v-for="file in visibleExistingFiles" :key="file.id ?? file.name" class="flex flex-col gap-1">
          <div class="relative">
            <img :src="file.url" :alt="file.name" class="h-24 w-full rounded-md object-cover" />

            <button type="button"
              class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/70 text-white transition hover:bg-rose-600"
              :disabled="disabled" @click="removeExisting(file)">
              <Lucide icon="X" class="h-3.5 w-3.5" />
            </button>
          </div>

          <p class="truncate font-caption" :title="file.name">
            {{ file.name }}
          </p>

          <FormInput v-if="withCaption" :model-value="existingCaptionValue(file)" form-input-size="sm"
            placeholder="Keterangan" :disabled="disabled"
            @update:model-value="(value) => updateExistingCaption(file, String(value ?? ''))" />
        </div>

        <div v-for="(file, index) in selectedFiles" :key="`${file.name}-${file.lastModified}-${index}`"
          class="flex flex-col gap-1">
          <div class="relative">
            <img :src="objectUrls.get(file)" :alt="file.name" class="h-24 w-full rounded-md object-cover" />

            <button type="button"
              class="absolute right-1 top-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/70 text-white transition hover:bg-rose-600"
              :disabled="disabled" @click="removeSelected(index)">
              <Lucide icon="X" class="h-3.5 w-3.5" />
            </button>
          </div>

          <p class="truncate font-caption" :title="file.name">
            {{ file.name }} <span v-if="formatSize(file.size)">- {{ formatSize(file.size) }}</span>
          </p>

          <FormInput v-if="withCaption" :model-value="newCaptionValue(file)" form-input-size="sm"
            placeholder="Keterangan" :disabled="disabled"
            @update:model-value="(value) => updateNewCaption(file, String(value ?? ''))" />
        </div>
      </div>

      <p v-else
        class="font-body mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3">
        {{ emptyText }}
      </p>
    </div>

    <p v-if="displayError" class="input-error-text">
      {{ displayError }}
    </p>
  </div>
</template>
