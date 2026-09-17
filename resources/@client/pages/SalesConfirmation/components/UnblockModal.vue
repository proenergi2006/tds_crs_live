<script setup lang="ts">
import { ref, computed, watch } from "vue";
import axios from "axios";

import Lucide from "@/components/Base/Lucide";
import { Icon } from "@/components/Base/Lucide/Lucide.vue";
import { FormTextarea } from "@/components/Base/Form";
import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import FileUploadField from "@/components/SystemDesign/Form/FileUploadField.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { useAuthStore } from "@/stores/auth";
import { formatCurrency, formatDateTime } from "@/utils/format";

import { poCustomerStatusBadgeClass } from "@/pages/PoCustomer/status";
import {
  unblockStatusBadgeClass,
  unblockStepBadgeClass,
  unblockStepStatusLabel,
  UNBLOCK_ROLE_ADMIN_FINANCE,
} from "../status";
import type { UnblockContext, UnblockRequest } from "../types";

const props = defineProps<{ open: boolean; idPoc: number | null }>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "saved"): void;
}>();

const auth = useAuthStore();
const { success, error: notifyError } = useNotification();

const loading = ref(false);
const context = ref<UnblockContext | null>(null);
const formError = ref<string | null>(null);
const submitForm = {
  reason: ref(""),
  attachments: ref<File[]>([]),
  uploadError: ref(""),
  submitting: ref(false),
};

const activeRequest = computed<UnblockRequest | null>(() => context.value?.active_request ?? null);

const mode = computed<"submit" | "readonly">(() => {
  if (!activeRequest.value && auth.hasRole(UNBLOCK_ROLE_ADMIN_FINANCE) && auth.can("sales-confirmation.manage"))
    return "submit";
  return "readonly";
});

const creditExposureStats = computed<{
  deficit: number;
  exposurePercent: number;
  coveredPercent: number;
  exceededPercent: number;
} | null>(() => {
  const po = context.value?.po;
  if (!po) return null;

  const limit = po.current_credit_limit;
  const deficit = Math.max(0, po.nilai_order - po.headroom);

  return {
    deficit,
    exposurePercent: limit > 0 ? (po.exposure / limit) * 100 : 0,
    coveredPercent: limit > 0 ? (Math.min(po.nilai_order, Math.max(0, po.headroom)) / limit) * 100 : 0,
    exceededPercent: limit > 0 ? (deficit / limit) * 100 : 0,
  };
});

const modalTitle = computed<string>(() => {
  if (mode.value === "submit") return "Ajukan Unblock Kredit";
  return "Status Pengajuan Unblock";
});

const modalSubmitText = computed<string>(() => {
  if (mode.value === "submit") return "Ajukan";
  return "Tutup";
});

const modalSubmitIcon = computed<Icon>(() => {
  if (mode.value === "submit") return "Send";
  return "X";
});

const submitting = computed<boolean>(() => submitForm.submitting.value);

const readonlyMessage = computed<string>(() => activeRequest.value?.status_label ?? "...");

watch(
  () => props.open,
  (open) => {
    if (open) {
      resetForms();
      fetchContext();
    } else {
      context.value = null;
      resetForms();
    }
  },
);

async function fetchContext(): Promise<void> {
  if (!props.idPoc) return;

  loading.value = true;
  try {
    const { data } = await axios.get(`/api/po-customers/${props.idPoc}/unblock-context`);
    context.value = data;
  } catch (e: any) {
    context.value = null;
    notifyError("Gagal", e.response?.data?.message ?? "Gagal memuat konteks Unblock.");
  } finally {
    loading.value = false;
  }
}

function resetForms(): void {
  formError.value = null;
  submitForm.reason.value = "";
  submitForm.attachments.value = [];
  submitForm.uploadError.value = "";
}

function handleSubmit(): void {
  if (mode.value === "submit") {
    submitUnblockRequest();
  } else {
    emit("close");
  }
}

async function submitUnblockRequest(): Promise<void> {
  if (submitForm.attachments.value.length < 1) {
    submitForm.uploadError.value = "Minimal 1 dokumen pendukung.";
    return;
  }
  submitForm.uploadError.value = "";
  formError.value = null;
  submitForm.submitting.value = true;
  try {
    const fd = new FormData();
    fd.append("reason", submitForm.reason.value);
    submitForm.attachments.value.forEach((file) => fd.append("attachments[]", file));
    await axios.post(`/api/po-customers/${props.idPoc}/unblock-requests`, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    success("Berhasil", "Pengajuan Unblock terkirim.");
    emit("saved");
    emit("close");
  } catch (e: any) {
    const status = e.response?.status;
    if (status === 422) {
      formError.value =
        Object.values(e.response?.data?.errors ?? {}).flat().join("\n") ||
        (e.response?.data?.message ?? "Data yang dikirim tidak valid.");
    } else if (status === 409) {
      formError.value = e.response?.data?.message ?? "Status pengajuan Unblock sudah berubah.";
      await fetchContext();
      emit("saved");
    } else {
      formError.value = e.response?.data?.message ?? "Gagal mengirim pengajuan Unblock.";
    }
  } finally {
    submitForm.submitting.value = false;
  }
}

function formatFileSize(bytes?: number | null): string {
  if (!bytes) return "";

  const units = ["B", "KB", "MB", "GB"];
  let size = bytes;
  let unitIndex = 0;

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }

  return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function unblockStepDotClass(status?: string | null): string {
  if (status === "approved") return "border-success bg-success text-white";
  if (status === "rejected") return "border-rose-500 bg-rose-500 text-white";
  return "border-slate-200 bg-white text-slate-400";
}

function unblockStepConnectorClass(status?: string | null): string {
  return status === "approved" ? "bg-success" : "bg-slate-200";
}

function unblockStepNoteClass(status?: string | null): string {
  return status === "rejected"
    ? "bg-rose-50 border border-rose-100 text-rose-700"
    : "bg-emerald-50 border border-emerald-100 text-emerald-700";
}
</script>

<template>
  <FormModal :open="open" :title="modalTitle" size="lg" :loading="submitting" :error="formError"
    :submit-text="modalSubmitText" :submit-icon="modalSubmitIcon" @close="emit('close')" @submit="handleSubmit">
    <div v-if="loading" class="flex justify-center items-center gap-3 min-h-[220px] text-slate-500">
      <Lucide icon="Loader2" class="w-6 h-6 animate-spin" />
      <span class="font-body">Memuat konteks Unblock...</span>
    </div>

    <div v-else-if="!context" class="flex flex-col justify-center items-center gap-2 min-h-[220px] text-center">
      <div class="flex justify-center items-center bg-rose-50 rounded-full w-14 h-14">
        <Lucide icon="AlertTriangle" class="w-7 h-7 text-rose-500" />
      </div>
      <p class="font-body">Konteks Unblock tidak dapat dimuat.</p>
    </div>

    <div v-else class="space-y-5">
      <div class="flex flex-wrap justify-between items-center gap-3">
        <div>
          <div class="font-strong">{{ context.po.nomor_poc || "-" }}</div>
          <div class="font-caption text-slate-500">
            {{ context.po.customer?.company_name || "-" }} · {{ context.po.customer?.customer_code || "-" }}
          </div>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full font-label"
          :class="poCustomerStatusBadgeClass(context.po.status_key)">
          {{ context.po.status_label || "-" }}
        </span>
      </div>

      <div class="bg-slate-900 shadow-lg p-6 rounded-lg text-white">
        <div class="mb-4">
          <p class="font-caption !text-slate-400">Penyebab PO Terblokir</p>
          <h3 class="font-header !text-white">Ringkasan Paparan Risiko Kredit</h3>
        </div>

        <div class="gap-4 grid grid-cols-2 sm:grid-cols-5">
          <div>
            <div class="font-label !text-slate-400">Credit Limit</div>
            <div class="mt-1 font-num !text-white">{{ formatCurrency(context.po.current_credit_limit) }}</div>
          </div>
          <div>
            <div class="font-label !text-slate-400">Outstanding AR</div>
            <div class="mt-1 font-num !text-white">{{ formatCurrency(context.po.exposure) }}</div>
          </div>
          <div>
            <div class="font-label !text-slate-400">Sisa Headroom</div>
            <div class="mt-1 font-num !text-white">{{ formatCurrency(context.po.headroom) }}</div>
          </div>
          <div>
            <div class="font-label !text-slate-400">Nilai PO Ini</div>
            <div class="mt-1 font-num !text-white">{{ formatCurrency(context.po.nilai_order) }}</div>
          </div>
          <div v-if="creditExposureStats && creditExposureStats.deficit > 0">
            <div class="font-label !text-rose-400">Defisit/Exceeded</div>
            <div class="mt-1 font-num !text-rose-400">{{ formatCurrency(creditExposureStats.deficit) }}</div>
          </div>
        </div>

        <div class="mt-5">
          <div class="flex bg-slate-700 rounded-full w-full h-2.5 overflow-hidden">
            <div class="bg-amber-500 h-full" :style="{ width: `${creditExposureStats?.exposurePercent ?? 0}%` }" />
            <div class="bg-blue-500 h-full" :style="{ width: `${creditExposureStats?.coveredPercent ?? 0}%` }" />
            <div class="bg-rose-500 h-full" :style="{ width: `${creditExposureStats?.exceededPercent ?? 0}%` }" />
          </div>
          <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 font-caption !text-slate-400">
            <span class="inline-flex items-center gap-1.5">
              <span class="bg-amber-500 rounded-full w-2 h-2" />
              Outstanding AR
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span class="bg-blue-500 rounded-full w-2 h-2" />
              Tertutup Headroom
            </span>
            <span class="inline-flex items-center gap-1.5">
              <span class="bg-rose-500 rounded-full w-2 h-2" />
              Melebihi Limit
            </span>
          </div>
        </div>
      </div>

      <div v-if="mode === 'submit'" class="space-y-4">
        <p class="font-body text-slate-600">
          Headroom kredit customer lebih kecil dari nilai order. Ajukan pelepasan blokir dengan melampirkan
          dokumen pendukung.
        </p>

        <div>
          <div class="mb-1 font-label">Alasan (opsional)</div>
          <FormTextarea id="unblock-reason" v-model="submitForm.reason.value" :rows="3"
            placeholder="Alasan pengajuan Unblock" :disabled="submitForm.submitting.value" />
        </div>

        <FileUploadField :multiple="true" v-model="submitForm.attachments.value" label="Dokumen Pendukung"
          accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="2" :error="submitForm.uploadError.value"
          hint="Minimal 1 file. PDF/JPG/PNG, maks 2MB per file." :disabled="submitForm.submitting.value" />
      </div>

      <div v-else class="space-y-5">
        <div class="space-y-3">
          <div class="flex justify-between items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-label"
              :class="unblockStatusBadgeClass(activeRequest?.status)">
              <span class="bg-current rounded-full w-1.5 h-1.5" />
              {{ activeRequest?.status_label || "-" }}
            </span>
            <span class="font-caption text-slate-500">{{ formatDateTime(activeRequest?.created_at) ?? "-" }}</span>
          </div>

          <div v-if="activeRequest" class="font-strong">
            {{ activeRequest.requested_by?.name ?? "-" }}
            <span v-if="activeRequest.requested_by?.role_name">({{ activeRequest.requested_by.role_name }})</span>
          </div>

          <div v-if="activeRequest?.reason" class="space-y-1">
            <div class="font-label">Alasan Pengajuan</div>
            <div class="bg-slate-50 px-3 py-2 border border-slate-200 rounded-lg font-body text-slate-600 italic whitespace-pre-line">
              {{ activeRequest.reason }}
            </div>
          </div>

          <div v-if="activeRequest?.attachments?.length" class="space-y-1">
            <div class="font-label">Dokumen Pendukung</div>
            <div class="space-y-1">
              <div v-for="att in activeRequest.attachments" :key="att.path"
                class="flex justify-between items-center gap-2">
                <a :href="`/storage/${att.path}`" target="_blank" class="flex items-center gap-1.5 font-strong text-primary">
                  <Lucide icon="Paperclip" class="w-4 h-4 text-slate-400" />
                  {{ att.original_name }}
                </a>
                <span v-if="att.size_bytes != null" class="font-caption text-slate-500 shrink-0">
                  {{ formatFileSize(att.size_bytes) }}
                </span>
              </div>
            </div>
          </div>

          <div v-if="activeRequest" class="pt-4 border-slate-100 border-t">
            <div class="mb-3 font-label">Alur Approval</div>
            <div class="flex flex-col">
              <div v-for="(step, index) in activeRequest.approval?.steps ?? []" :key="step.step_order" class="flex gap-3">
                <div class="flex flex-col items-center">
                  <div class="flex justify-center items-center border-2 rounded-full w-8 h-8 shrink-0"
                    :class="unblockStepDotClass(step.status)">
                    <Lucide v-if="step.status === 'approved'" icon="Check" class="w-4 h-4" />
                    <Lucide v-else-if="step.status === 'rejected'" icon="X" class="w-4 h-4" />
                    <span v-else class="font-label">{{ index + 1 }}</span>
                  </div>
                  <div v-if="index < (activeRequest.approval?.steps?.length ?? 0) - 1" class="flex-1 w-1.5 min-h-8"
                    :class="unblockStepConnectorClass(step.status)" />
                </div>

                <div class="flex flex-col flex-1 gap-2 pb-4 last:pb-0">
                  <div class="flex justify-between items-start gap-2">
                    <div>
                      <div class="font-strong">{{ step.step_name || `Langkah ${step.step_order}` }}</div>
                      <div v-if="step.actor_name" class="font-caption text-slate-500">
                        {{ step.actor_name }} · {{ formatDateTime(step.acted_at) ?? "-" }}
                      </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label shrink-0"
                      :class="unblockStepBadgeClass(step.status)">
                      {{ unblockStepStatusLabel(step.status) }}
                    </span>
                  </div>

                  <div v-if="step.decision_note" class="px-3 py-2 rounded-lg font-body" :class="unblockStepNoteClass(step.status)">
                    Catatan: {{ step.decision_note }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-center gap-2 px-2 py-6 text-center bg-slate-50 rounded-lg">
          <div class="flex justify-center items-center bg-slate-100 rounded-full w-14 h-14">
            <Lucide icon="Lock" class="w-7 h-7 text-slate-400" />
          </div>
          <p class="font-body text-slate-600">{{ readonlyMessage }}</p>
        </div>
      </div>
    </div>
  </FormModal>
</template>
