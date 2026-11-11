<template>
  <dl>
    <div v-for="(item, index) in items" :key="index" class="items-baseline gap-x-3 grid py-[5px]"
      :style="{ gridTemplateColumns: `${labelWidth} minmax(0, 1fr)` }">
      <dt class="text-body !text-slate-500">{{ item.label }}</dt>
      <dd class="min-w-0 text-body-strong break-words">
        <slot :name="`value-${item.key ?? index}`" :item="item">{{ item.value || '-' }}</slot>
      </dd>
    </div>
  </dl>
</template>

<script setup lang="ts">
export interface ScDetailItem {
  key?: string
  label: string
  value?: string | null
}

withDefaults(
  defineProps<{
    items: ScDetailItem[]
    labelWidth?: string
  }>(),
  { labelWidth: '120px' },
)
</script>
