<template>
  <section class="space-y-2 bg-primary/10 p-5 border border-primary/20 rounded-lg" aria-label="Keputusan">
    <div class="!text-primary text-overline">Perlu keputusan Anda</div>
    <h2 class="text-card-title">Keputusan {{ stepName }}</h2>

    <dl class="gap-x-2 gap-y-1 grid grid-cols-[auto_minmax(0,1fr)] text-body">
      <dt class="font-semibold">Setuju</dt>
      <dd>{{ approveOutcome }}</dd>
      <dt class="font-semibold">Tolak</dt>
      <dd>Kembali ke Admin Finance.</dd>
    </dl>

    <div class="flex gap-2 pt-1">
      <Button type="button" variant="outline-danger" class="flex-1 justify-center" :disabled="submitting"
        @click="openDialog('reject')">
        Tolak
      </Button>
      <Button type="button" variant="primary" class="flex-1 justify-center" :disabled="submitting"
        @click="openDialog('approve')">
        Setuju
      </Button>
    </div>
  </section>

  <ConfirmDialog :open="dialogKind !== null" :title="dialogTitle" :description="dialogOutcome"
    :confirm-text="dialogKind === 'reject' ? 'Ya, tolak' : 'Ya, setujui'"
    :icon="dialogKind === 'reject' ? 'X' : 'Check'"
    :icon-class="dialogKind === 'reject' ? 'bg-danger/10 text-danger' : 'bg-primary/10 text-primary'"
    :variant="dialogKind === 'reject' ? 'danger' : 'primary'" :loading="submitting" :confirm-disabled="confirmDisabled"
    @close="closeDialog" @confirm="confirm">
    <div class="flex justify-between items-center mb-1">
      <FormLabel htmlFor="sc-decision-note">
        Catatan keputusan
        <RequiredAsterisk v-if="dialogKind === 'reject'" />
      </FormLabel>
      <span v-if="dialogKind === 'approve'" class="text-caption">Opsional</span>
    </div>
    <FormTextarea id="sc-decision-note" v-model="note" :rows="4" :disabled="submitting"
      :placeholder="dialogKind === 'reject' ? 'Apa yang perlu diperbaiki Admin Finance' : 'Catatan untuk Admin Finance dan approver berikutnya'" />
  </ConfirmDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import Button from '@/components/Base/Button'
import { FormLabel, FormTextarea } from '@/components/Base/Form'
import ConfirmDialog from '@/components/SystemDesign/Dialog/ConfirmDialog.vue'
import RequiredAsterisk from '@/components/SystemDesign/Form/RequiredAsterisk.vue'

import { decisionOutcome } from '../workflow'

type DecisionKind = 'approve' | 'reject'

const props = defineProps<{
  stepName: string
  nextStepName: string | null
  submitting: boolean
}>()

const emit = defineEmits<{
  (e: 'submit', payload: { decision: DecisionKind; note: string }): void
}>()

const dialogKind = ref<DecisionKind | null>(null)
const note = ref('')

const approveOutcome = computed<string>(() => decisionOutcome('approve', props.nextStepName))

const dialogOutcome = computed<string>(() =>
  dialogKind.value ? decisionOutcome(dialogKind.value, props.nextStepName) : '',
)

const dialogTitle = computed<string>(() =>
  dialogKind.value === 'reject' ? 'Tolak Sales Confirmation ini?' : 'Setujui Sales Confirmation ini?',
)

const confirmDisabled = computed<boolean>(() => dialogKind.value === 'reject' && note.value.trim() === '')

watch(
  () => props.submitting,
  (now, before) => {
    if (before && !now) dialogKind.value = null
  },
)

function openDialog(kind: DecisionKind): void {
  note.value = ''
  dialogKind.value = kind
}

function closeDialog(): void {
  if (!props.submitting) dialogKind.value = null
}

function confirm(): void {
  if (!dialogKind.value || confirmDisabled.value) return
  emit('submit', { decision: dialogKind.value, note: note.value })
}
</script>
