<script setup lang="ts">
import { defineAsyncComponent, h } from 'vue'

import { FormLabel } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

// CKEditor5 classic build lumayan berat (~500KB+), makanya Base/Ckeditor
// gak di-static-import langsung dari sini. defineAsyncComponent bikin Vite
// motong jadi chunk terpisah -- baru di-fetch browser kalau field ini
// bener-bener kepake (gak ikut nempel ke chunk halaman pemanggilnya), dan
// browser otomatis nge-cache-nya, jadi mount berikutnya di sesi yang sama
// gak fetch ulang.
const ClassicEditor = defineAsyncComponent({
  loader: () => import('@/components/Base/Ckeditor/ClassicEditor.vue'),
  loadingComponent: {
    render: () => h('div', { class: 'h-40 animate-pulse rounded-lg border border-slate-200 bg-slate-50' }),
  },
  delay: 0,
})

// Toolbar sengaja dipangkas -- field ini cuma butuh word formatting + list,
// bukan editor dokumen lengkap. bold/italic/bulletedList/numberedList itu
// plugin standar bawaan classic build, aman dipakai. underline/strikethrough
// sengaja gak diikutkan soalnya butuh plugin tambahan yang gak ada di paket
// classic build bawaan ini.
const editorConfig = {
  toolbar: ['bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
}

withDefaults(
  defineProps<{
    modelValue: string;
    label?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
  }>(),
  {
    label: '',
    required: false,
    disabled: false,
    error: '',
  },
)

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()
</script>

<template>
  <div>
    <FormLabel v-if="label">
      {{ label }}
      <RequiredAsterisk v-if="required" />
    </FormLabel>

    <ClassicEditor :model-value="modelValue" :disabled="disabled" :config="editorConfig"
      @update:model-value="(v: string) => $emit('update:modelValue', v)" />

    <small v-if="error && error.trim()" class="block input-error-text">
      {{ error }}
    </small>
  </div>
</template>
