<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import FileUploadField from "@/components/SystemDesign/Form/FileUploadField.vue";
import { FormInput, FormLabel, FormSelect, FormSwitch } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import LogisticDocumentsSection from "../components/LogisticDocumentsSection.vue";
import type { EnumOption, FormMode, StagedDocument, TransporterRef } from "../types";
import type { Personnel } from "./types";

const DOCUMENT_TYPE_OPTIONS: EnumOption[] = [
  { value: "SIM", label: "SIM" },
  { value: "CERTIFICATE", label: "Sertifikat" },
];

const personnelApi = createResourceApi("/personnels");
const transporterApi = createResourceApi("/transporters");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: Personnel | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: Personnel, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);
const transporters = ref<TransporterRef[]>([]);
const photoFile = ref<File | null>(null);
const stagedDocuments = ref<StagedDocument[]>([]);

const form = reactive({
  transporter_id: "" as number | "",
  name: "",
  is_active: true,
});

const serverErrors = reactive({
  transporter_id: "",
  name: "",
  photo: "",
  is_active: "",
});

const rules = {
  transporter_id: {
    required: helpers.withMessage("Transporter wajib dipilih", required),
  },
  name: {
    required: helpers.withMessage("Nama wajib diisi", required),
  },
};

const v$ = useVuelidate(rules, form);

const existingPhoto = computed(() =>
  props.item?.photo_url ? [{ id: "photo", name: "Foto saat ini", url: props.item.photo_url }] : [],
);

const cancelText = computed(() => (props.mode === "edit" ? "Selesai" : "Batal"));

const documentParentId = computed(() => (props.mode === "edit" && props.item ? props.item.id : null));

watch(
  () => [props.open, props.mode, props.item],
  () => {
    if (props.open) resetForm();
  },
  { immediate: true },
);

onMounted(() => {
  fetchTransporters();
});

async function fetchTransporters() {
  try {
    const { data } = await transporterApi.getAll({ as_list: true });
    transporters.value = data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data transporter");
  }
}

function resetForm() {
  resetErrors();
  photoFile.value = null;
  stagedDocuments.value = [];

  if (props.mode === "edit" && props.item) {
    form.transporter_id = props.item.transporter?.id ?? "";
    form.name = props.item.name;
    form.is_active = props.item.is_active;
    return;
  }

  form.transporter_id = "";
  form.name = "";
  form.is_active = true;
}

function resetErrors() {
  formError.value = null;
  v$.value.$reset();
  serverErrors.transporter_id = "";
  serverErrors.name = "";
  serverErrors.photo = "";
  serverErrors.is_active = "";
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || (v$.value as any)[field]?.$errors[0]?.$message?.toString() || "";
}

function buildMultipartPayload(): FormData {
  const fd = new FormData();
  fd.append("transporter_id", String(form.transporter_id));
  fd.append("name", form.name);
  fd.append("is_active", form.is_active ? "1" : "0");
  if (photoFile.value) fd.append("photo", photoFile.value);
  return fd;
}

function buildJsonPayload() {
  return {
    transporter_id: form.transporter_id,
    name: form.name,
    is_active: form.is_active,
  };
}

function buildFormData(): FormData {
  const fd = new FormData();

  fd.append("data_personnel[transporter_id]", String(form.transporter_id));
  fd.append("data_personnel[name]", form.name);
  fd.append("data_personnel[is_active]", form.is_active ? "1" : "0");
  if (photoFile.value) fd.append("data_personnel[photo]", photoFile.value);

  stagedDocuments.value.forEach((doc, index) => {
    fd.append(`data_dokumen[${index}][document_type]`, doc.document_type);
    fd.append(`data_dokumen[${index}][file]`, doc.file);
    if (doc.valid_until) fd.append(`data_dokumen[${index}][valid_until]`, doc.valid_until);
  });

  return fd;
}

function handleSubmitError(e: any) {
  const errors = e.response?.data?.errors;

  if (e.response?.status === 422 && errors) {
    Object.entries(errors).forEach(([key, value]: [string, any]) => {
      if (key in serverErrors) {
        serverErrors[key as keyof typeof serverErrors] = value?.[0] || "";
      }
    });
    formError.value = e.response.data.message;
    return;
  }

  const message = e.response?.data?.message ?? "Terjadi kesalahan";
  formError.value = message;
  error("Gagal", message);
}

async function submitForm() {
  resetErrors();

  const isValid = await v$.value.$validate();
  if (!isValid) {
    error("Gagal", "Periksa kembali data yang Anda masukkan");
    return;
  }

  loading.value = true;

  try {
    let response;

    if (props.mode === "create") {
      response = await personnelApi.store(buildFormData());
    } else if (photoFile.value) {
      response = await personnelApi.updateMultipart(props.item!.id, buildMultipartPayload());
    } else {
      response = await personnelApi.update(props.item!.id, buildJsonPayload());
    }

    success("Berhasil", props.mode === "create" ? "Personnel berhasil ditambahkan" : "Personnel berhasil diperbarui");

    if (props.mode === "create") stagedDocuments.value = [];

    emit("success", response.data.data, props.mode);
  } catch (e: any) {
    handleSubmitError(e);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Personnel' : 'Edit Personnel'" size="xl"
    :loading="loading" :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" :cancel-text="cancelText" @close="$emit('close')"
    @submit="submitForm">
    <div class="space-y-4">
      <div class="p-4 border border-slate-200 rounded-lg">
        <h3 class="mb-3 text-overline">Identitas</h3>
        <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
          <div>
            <FormLabel htmlFor="personnel-transporter">
              Transporter
              <RequiredAsterisk />
            </FormLabel>
            <FormSelect id="personnel-transporter" v-model="form.transporter_id"
              :class="getFieldError('transporter_id') ? 'border-rose-500' : ''">
              <option disabled value="">-- Pilih Transporter --</option>
              <option v-for="t in transporters" :key="t.id" :value="t.id">{{ t.company_name }}</option>
            </FormSelect>
            <small v-if="getFieldError('transporter_id')" class="text-caption !text-rose-600">{{
              getFieldError("transporter_id") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="personnel-name">
              Nama
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="personnel-name" v-model="form.name" placeholder="Nama"
              :class="getFieldError('name') ? 'border-rose-500' : ''" />
            <small v-if="getFieldError('name')" class="text-caption !text-rose-600">{{ getFieldError("name") }}</small>
          </div>

          <div>
            <FormLabel>Status</FormLabel>
            <div class="flex items-center gap-3 mt-2">
              <FormSwitch>
                <FormSwitch.Input v-model="form.is_active" type="checkbox" />
              </FormSwitch>
              <span class="text-body">{{ form.is_active ? "Active" : "Inactive" }}</span>
            </div>
          </div>

          <div class="md:col-span-2">
            <FileUploadField v-model="photoFile" :existing-files="existingPhoto" label="Foto" accept=".jpg,.jpeg,.png"
              :max-size-mb="2" :error="getFieldError('photo')"
              hint="Pilih file baru untuk mengganti foto. Foto yang tersimpan tidak bisa dihapus dari form ini." />
          </div>
        </div>
      </div>

      <LogisticDocumentsSection parent-type="personnels" :parent-id="documentParentId"
        :allowed-types="DOCUMENT_TYPE_OPTIONS" v-model="stagedDocuments" />
    </div>
  </FormModal>
</template>
