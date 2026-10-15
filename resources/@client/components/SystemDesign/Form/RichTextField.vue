<script setup lang="ts">
import { defineAsyncComponent, h } from 'vue'

import { FormLabel } from '@/components/Base/Form'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

const ClassicEditor = defineAsyncComponent({
  loader: () => import('@/components/Base/Ckeditor/ClassicEditor.vue'),
  loadingComponent: {
    render: () => h('div', { class: 'h-40 animate-pulse rounded-lg border border-slate-200 bg-slate-50' }),
  },
  delay: 0,
})

const editorConfig = {
  toolbar: ['bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
}

const props = withDefaults(
  defineProps<{
    modelValue: string;
    label?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    maxHeight?: string;
  }>(),
  {
    label: '',
    required: false,
    disabled: false,
    error: '',
    maxHeight: '400px',
  },
)

defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>()
</script>

<template>
  <div class="rich-text-field" :style="{ '--rich-text-max-height': props.maxHeight }">
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

<style scoped>
.rich-text-field :deep(.ck-editor__editable),
.rich-text-field :deep(.ck-editor__editable.ck-focused) {
  max-height: var(--rich-text-max-height);
  overflow-y: auto;
}
</style>
