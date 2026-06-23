<script setup lang="ts">
import { computed, ref } from 'vue'

import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'

type ExistingFile = {
  id?: string | number;
  name: string;
  url?: string;
  size?: number;
}

type ModelValue = File | File[] | null

const props = withDefaults(
  defineProps<{
    modelValue: ModelValue;
    existingFiles?: ExistingFile[];
    label?: string;
    hint?: string;
    error?: string;
    accept?: string;
    multiple?: boolean;
    maxSizeMb?: number;
    disabled?: boolean;
    chooseText?: string;
    emptyText?: string;
  }>(),
  {
    existingFiles: () => [],
    label: '',
    hint: '',
    error: '',
    accept: '',
    multiple: false,
    maxSizeMb: 4,
    disabled: false,
    chooseText: 'Choose file',
    emptyText: 'Belum ada file dipilih',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: ModelValue): void;
  (e: 'remove-existing', file: ExistingFile): void;
  (e: 'error', message: string): void;
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

const previewClass = computed(() => {
  return props.multiple
    ? 'grid grid-cols-1 gap-3 sm:grid-cols-2'
    : 'grid grid-cols-1 gap-3'
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

function removeExisting(file: ExistingFile) {
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
        displayError ? 'border-rose-300 bg-rose-50/40' : 'hover:border-slate-300',
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

      <div v-if="hasFiles" :class="['mt-3', previewClass]">
        <div v-for="file in visibleExistingFiles" :key="file.id ?? file.name"
          class="flex min-h-[64px] items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
            <Lucide icon="FileText" class="h-5 w-5" />
          </div>

          <div class="min-w-0 flex-1">
            <a v-if="file.url" :href="file.url" target="_blank"
              class="block truncate font-body !text-primary underline">
              {{ file.name }}
            </a>
            <p v-else class="truncate font-strong">
              {{ file.name }}
            </p>
            <p class="font-caption">
              File tersimpan <span v-if="formatSize(file.size)">- {{ formatSize(file.size) }}</span>
            </p>
          </div>

          <button type="button"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-white hover:text-rose-600"
            :disabled="disabled" @click="removeExisting(file)">
            <Lucide icon="X" class="h-4 w-4" />
          </button>
        </div>

        <div v-for="(file, index) in selectedFiles" :key="`${file.name}-${file.lastModified}-${index}`"
          class="flex min-h-[64px] items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
            <Lucide icon="File" class="h-5 w-5" />
          </div>

          <div class="min-w-0 flex-1">
            <p class="truncate font-strong">
              {{ file.name }}
            </p>
            <p class="font-caption">
              File baru <span v-if="formatSize(file.size)">- {{ formatSize(file.size) }}</span>
            </p>
          </div>

          <button type="button"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-white hover:text-rose-600"
            :disabled="disabled" @click="removeSelected(index)">
            <Lucide icon="X" class="h-4 w-4" />
          </button>
        </div>
      </div>

      <p v-else
        class="font-body mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-3">
        {{ emptyText }}
      </p>
    </div>

    <p v-if="displayError" class="font-caption !text-rose-600">
      {{ displayError }}
    </p>
  </div>
</template>
