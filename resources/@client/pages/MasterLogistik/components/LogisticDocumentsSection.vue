<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";

import Button from "@/components/Base/Button";
import Table from "@/components/Base/Table";
import Lucide from "@/components/Base/Lucide";
import { FormLabel, FormSelect } from "@/components/Base/Form";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import DataList from "@/components/SystemDesign/Data/DataList.vue";
import DeleteRecordDialog from "@/components/SystemDesign/Dialog/DeleteRecordDialog.vue";
import DateField from "@/components/SystemDesign/Form/DateField.vue";
import FileUploadField from "@/components/SystemDesign/Form/FileUploadField.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";
import { formatDate, formatDateTime } from "@/utils/format";

import type { EnumOption, LogisticDocument, LogisticParentType, StagedDocument } from "../types";

const PARENT_LABELS: Record<LogisticParentType, string> = {
  transporters: "Transporter",
  personnels: "Personnel",
  vessels: "Vessel",
  trucks: "Truck",
};

const props = withDefaults(
  defineProps<{
    parentType: LogisticParentType;
    parentId: number | null;
    allowedTypes: EnumOption[];
    modelValue?: StagedDocument[];
  }>(),
  {
    modelValue: () => [],
  },
);

const emit = defineEmits<{
  (e: "update:modelValue", value: StagedDocument[]): void;
}>();

const { success, error } = useNotification();

const documentList = {
  rows: ref<LogisticDocument[]>([]),
  loading: ref(false),
};

const documentForm = reactive({
  document_type: "",
  file: null as File | null,
  valid_until: "",
});

const documentFormState = {
  isSubmitting: ref(false),
  error: ref<string | null>(null),
};

const documentServerErrors = reactive({
  document_type: "",
  file: "",
  valid_until: "",
});

const deleteDialog = {
  isOpen: ref(false),
  isLoading: ref(false),
  targetId: ref<number | null>(null),
};

const isParentSaved = computed(() => props.parentId !== null);

const documentApi = computed(() =>
  props.parentId === null ? null : createResourceApi(`/${props.parentType}/${props.parentId}/documents`),
);

const parentLabel = computed(() => PARENT_LABELS[props.parentType]);

const documentRules = {
  document_type: {
    required: helpers.withMessage("Jenis dokumen wajib dipilih", required),
  },
  file: {
    required: helpers.withMessage("File wajib dipilih", (value: File | null) => value instanceof File),
  },
};

const v$ = useVuelidate(documentRules, documentForm, { $scope: false });

watch(() => [props.parentType, props.parentId], fetchDocuments, { immediate: true });

async function fetchDocuments() {
  documentList.rows.value = [];
  resetDocumentForm();

  if (!documentApi.value) return;

  documentList.loading.value = true;

  try {
    const { data } = await documentApi.value.getAll();
    documentList.rows.value = data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat dokumen");
  } finally {
    documentList.loading.value = false;
  }
}

function resetDocumentForm() {
  documentForm.document_type = "";
  documentForm.file = null;
  documentForm.valid_until = "";
  v$.value.$reset();
  documentServerErrors.document_type = "";
  documentServerErrors.file = "";
  documentServerErrors.valid_until = "";
  documentFormState.error.value = null;
}

function getDocumentFieldError(field: keyof typeof documentServerErrors) {
  return documentServerErrors[field] || (v$.value as any)[field]?.$errors[0]?.$message?.toString() || "";
}

function documentTypeLabel(value: string) {
  return props.allowedTypes.find((opt) => opt.value === value)?.label ?? value;
}

async function submitDocumentForm() {
  documentServerErrors.document_type = "";
  documentServerErrors.file = "";
  documentServerErrors.valid_until = "";
  documentFormState.error.value = null;

  const isValid = await v$.value.$validate();
  if (!isValid) return;

  if (!isParentSaved.value) {
    stageDocument();
    return;
  }

  await uploadDocument();
}

function stageDocument() {
  const staged: StagedDocument = {
    document_type: documentForm.document_type,
    file: documentForm.file as File,
    valid_until: documentForm.valid_until,
  };

  emit("update:modelValue", [...props.modelValue, staged]);
  resetDocumentForm();
}

function removeStagedDocument(index: number) {
  const next = [...props.modelValue];
  next.splice(index, 1);
  emit("update:modelValue", next);
}

async function uploadDocument() {
  const fd = new FormData();
  fd.append("document_type", documentForm.document_type);
  fd.append("file", documentForm.file as File);
  if (documentForm.valid_until) fd.append("valid_until", documentForm.valid_until);

  documentFormState.isSubmitting.value = true;

  try {
    const { data } = await documentApi.value!.store(fd);
    documentList.rows.value.unshift(data.data);
    resetDocumentForm();
    success("Berhasil", "Dokumen berhasil diunggah");
  } catch (e: any) {
    const errors = e.response?.data?.errors;

    if (e.response?.status === 422 && errors) {
      Object.entries(errors).forEach(([key, value]: [string, any]) => {
        if (key in documentServerErrors) {
          documentServerErrors[key as keyof typeof documentServerErrors] = value?.[0] || "";
        }
      });
    } else {
      documentFormState.error.value = e.response?.data?.message ?? "Gagal mengunggah dokumen";
    }
  } finally {
    documentFormState.isSubmitting.value = false;
  }
}

function confirmDeleteDocument(id: number) {
  deleteDialog.targetId.value = id;
  deleteDialog.isOpen.value = true;
}

async function submitDeleteDocument() {
  if (!deleteDialog.targetId.value) return;

  deleteDialog.isLoading.value = true;

  try {
    await documentApi.value!.destroy(deleteDialog.targetId.value);
    documentList.rows.value = documentList.rows.value.filter((row) => row.id !== deleteDialog.targetId.value);
    closeDeleteDialog();
    success("Berhasil", "Dokumen berhasil dihapus");
  } catch (e: any) {
    closeDeleteDialog();
    error("Gagal menghapus", e.response?.data?.message ?? "Terjadi kesalahan saat menghapus dokumen");
  } finally {
    deleteDialog.isLoading.value = false;
  }
}

function closeDeleteDialog() {
  deleteDialog.isOpen.value = false;
  deleteDialog.targetId.value = null;
}
</script>

<template>
  <div class="p-4 border border-slate-200 rounded-lg">
    <h3 class="mb-3 text-overline">Dokumen Pendukung</h3>

    <p v-if="!isParentSaved" class="mb-4 text-body">
      Dokumen yang ditambahkan akan ikut tersimpan bersama data {{ parentLabel }} ini.
    </p>

    <div class="gap-4 grid grid-cols-2">
      <div>
        <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
          <div>
            <FormLabel>Jenis Dokumen</FormLabel>
            <FormSelect v-model="documentForm.document_type"
              :class="getDocumentFieldError('document_type') ? 'border-rose-500' : ''">
              <option disabled value="">-- Pilih Jenis Dokumen --</option>
              <option v-for="opt in allowedTypes" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </FormSelect>
            <small v-if="getDocumentFieldError('document_type')" class="text-caption !text-rose-600">
              {{ getDocumentFieldError("document_type") }}
            </small>
          </div>

          <div>
            <DateField v-model="documentForm.valid_until" label="Berlaku s/d" />
          </div>
        </div>

        <div class="mt-4">
          <FileUploadField v-model="documentForm.file" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="5"
            :error="getDocumentFieldError('file')" />
        </div>

        <div v-if="documentFormState.error.value"
          class="bg-rose-50 mt-4 px-4 py-3 border border-rose-200 rounded-lg text-body !text-rose-700">
          {{ documentFormState.error.value }}
        </div>

        <div class="flex justify-end mt-4">
          <Button type="button" variant="primary" class="inline-flex justify-center items-center gap-2"
            :disabled="documentFormState.isSubmitting.value" @click="submitDocumentForm">
            <Lucide v-if="documentFormState.isSubmitting.value" icon="Loader2" class="w-4 h-4 animate-spin" />
            <Lucide v-else :icon="isParentSaved ? 'Upload' : 'Plus'" class="w-4 h-4" />
            {{ isParentSaved ? "Unggah" : "Tambah ke Daftar" }}
          </Button>
        </div>
      </div>

      <div>
        <DataList v-if="isParentSaved" :colspan="5" :loading="documentList.loading.value"
          :empty="documentList.rows.value.length === 0" empty-description="Belum ada dokumen.">
          <template #head>
            <Table.Th>Jenis</Table.Th>
            <Table.Th>File</Table.Th>
            <Table.Th>Berlaku s/d</Table.Th>
            <Table.Th>Diunggah</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="doc in documentList.rows.value" :key="doc.id" class="hover:bg-slate-50 transition">
              <Table.Td>{{ doc.document_type.label }}</Table.Td>
              <Table.Td>
                <a v-if="doc.file_url" :href="doc.file_url" target="_blank" class="text-body !text-primary underline">
                  {{ doc.file_name }}
                </a>
                <span v-else>-</span>
              </Table.Td>
              <Table.Td>{{ formatDate(doc.valid_until) }}</Table.Td>
              <Table.Td>
                <div>{{ doc.uploader?.name ?? "-" }}</div>
                <div class="text-caption">{{ formatDateTime(doc.uploaded_at) }}</div>
              </Table.Td>
              <Table.Td class="text-center">
                <div class="inline-flex justify-center items-center gap-2">
                  <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                    @click="confirmDeleteDocument(doc.id)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </DataList>

        <DataList v-else :colspan="4" :loading="false" :empty="modelValue.length === 0"
          empty-description="Belum ada dokumen yang ditambahkan.">
          <template #head>
            <Table.Th>Jenis</Table.Th>
            <Table.Th>File</Table.Th>
            <Table.Th>Berlaku s/d</Table.Th>
            <Table.Th class="text-center">Aksi</Table.Th>
          </template>

          <template #body>
            <Table.Tr v-for="(doc, index) in modelValue" :key="index" class="hover:bg-slate-50 transition">
              <Table.Td>{{ documentTypeLabel(doc.document_type) }}</Table.Td>
              <Table.Td>
                <span>{{ doc.file.name }}</span>
                <Badge variant="soft-pending" class="ml-2">Belum diunggah</Badge>
              </Table.Td>
              <Table.Td>{{ doc.valid_until ? formatDate(doc.valid_until) : "-" }}</Table.Td>
              <Table.Td class="text-center">
                <div class="inline-flex justify-center items-center gap-2">
                  <Button variant="soft-danger" rounded class="!shadow-none !p-0 !w-8 !h-8" title="Hapus"
                    @click="removeStagedDocument(index)">
                    <Lucide icon="Trash2" class="w-4 h-4" />
                  </Button>
                </div>
              </Table.Td>
            </Table.Tr>
          </template>
        </DataList>
      </div>
    </div>

    <DeleteRecordDialog :open="deleteDialog.isOpen.value" title="Hapus Dokumen" :loading="deleteDialog.isLoading.value"
      @close="closeDeleteDialog" @confirm="submitDeleteDocument" />
  </div>
</template>
