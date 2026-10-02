<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import NumberField from "@/components/SystemDesign/Form/NumberField.vue";
import { FormInput, FormLabel, FormSelect, FormSwitch } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import LogisticDocumentsSection from "../components/LogisticDocumentsSection.vue";
import type { EnumOption, FormMode, StagedDocument, TransporterRef } from "../types";
import type { Vessel } from "./types";

const DOCUMENT_TYPE_OPTIONS: EnumOption[] = [
  { value: "SHIP_PARTICULAR", label: "Ship Particular" },
  { value: "SIOPSUS", label: "SIOPSUS" },
];

const vesselApi = createResourceApi("/vessels");
const transporterApi = createResourceApi("/transporters");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: Vessel | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: Vessel, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);
const transporters = ref<TransporterRef[]>([]);
const stagedDocuments = ref<StagedDocument[]>([]);

const form = reactive({
  transporter_id: "" as number | "",
  name: "",
  type: "",
  classification: "",
  is_active: true,
  max_capacity: 0,
  length: 0,
  width: 0,
  origin: "",
});

const serverErrors = reactive({
  transporter_id: "",
  name: "",
  type: "",
  classification: "",
  is_active: "",
  max_capacity: "",
  length: "",
  width: "",
  origin: "",
});

const rules = {
  transporter_id: {
    required: helpers.withMessage("Transporter wajib dipilih", required),
  },
  name: {
    required: helpers.withMessage("Nama Kapal wajib diisi", required),
  },
};

const v$ = useVuelidate(rules, form);

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
  stagedDocuments.value = [];

  if (props.mode === "edit" && props.item) {
    const item = props.item;
    form.transporter_id = item.transporter?.id ?? "";
    form.name = item.name;
    form.type = item.type ?? "";
    form.classification = item.classification ?? "";
    form.is_active = item.is_active;
    form.max_capacity = item.max_capacity ?? 0;
    form.length = item.length ? Number(item.length) : 0;
    form.width = item.width ? Number(item.width) : 0;
    form.origin = item.origin ?? "";
    return;
  }

  form.transporter_id = "";
  form.name = "";
  form.type = "";
  form.classification = "";
  form.is_active = true;
  form.max_capacity = 0;
  form.length = 0;
  form.width = 0;
  form.origin = "";
}

function resetErrors() {
  formError.value = null;
  v$.value.$reset();
  Object.keys(serverErrors).forEach((key) => {
    serverErrors[key as keyof typeof serverErrors] = "";
  });
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || (v$.value as any)[field]?.$errors[0]?.$message?.toString() || "";
}

function optionalOrNull(value: string) {
  return value.trim() === "" ? null : value.trim();
}

function buildPayload() {
  return {
    transporter_id: form.transporter_id,
    name: form.name.trim(),
    is_active: form.is_active,
    type: optionalOrNull(form.type),
    classification: optionalOrNull(form.classification),
    origin: optionalOrNull(form.origin),
    max_capacity: form.max_capacity > 0 ? form.max_capacity : null,
    length: form.length > 0 ? form.length : null,
    width: form.width > 0 ? form.width : null,
  };
}

function appendIfFilled(fd: FormData, key: string, value: string) {
  if (value) fd.append(key, value);
}

function buildFormData(): FormData {
  const fd = new FormData();

  fd.append("data_vessel[transporter_id]", String(form.transporter_id));
  fd.append("data_vessel[name]", form.name.trim());
  fd.append("data_vessel[is_active]", form.is_active ? "1" : "0");
  appendIfFilled(fd, "data_vessel[type]", form.type.trim());
  appendIfFilled(fd, "data_vessel[classification]", form.classification.trim());
  appendIfFilled(fd, "data_vessel[origin]", form.origin.trim());
  if (form.max_capacity > 0) fd.append("data_vessel[max_capacity]", String(form.max_capacity));
  if (form.length > 0) fd.append("data_vessel[length]", String(form.length));
  if (form.width > 0) fd.append("data_vessel[width]", String(form.width));

  stagedDocuments.value.forEach((doc, index) => {
    fd.append(`data_dokumen[${index}][document_type]`, doc.document_type);
    fd.append(`data_dokumen[${index}][file]`, doc.file);
    appendIfFilled(fd, `data_dokumen[${index}][valid_until]`, doc.valid_until);
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
    const { data } =
      props.mode === "create"
        ? await vesselApi.store(buildFormData())
        : await vesselApi.update(props.item!.id, buildPayload());

    success("Berhasil", props.mode === "create" ? "Vessel berhasil ditambahkan" : "Vessel berhasil diperbarui");

    if (props.mode === "create") stagedDocuments.value = [];

    emit("success", data.data, props.mode);
  } catch (e: any) {
    handleSubmitError(e);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Vessel' : 'Edit Vessel'" size="xl" :loading="loading"
    :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" :cancel-text="cancelText" @close="$emit('close')"
    @submit="submitForm">
    <div class="space-y-4">
      <div class="p-4 border border-slate-200 rounded-lg">
        <h3 class="mb-3 text-overline">Identitas</h3>
        <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
          <div>
            <FormLabel htmlFor="vessel-transporter">
              Transporter
              <RequiredAsterisk />
            </FormLabel>
            <FormSelect id="vessel-transporter" v-model="form.transporter_id"
              :class="getFieldError('transporter_id') ? 'border-rose-500' : ''">
              <option disabled value="">-- Pilih Transporter --</option>
              <option v-for="t in transporters" :key="t.id" :value="t.id">{{ t.company_name }}</option>
            </FormSelect>
            <small v-if="getFieldError('transporter_id')" class="text-caption !text-rose-600">{{
              getFieldError("transporter_id") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="vessel-name">
              Nama Kapal
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="vessel-name" v-model="form.name" placeholder="Nama Kapal"
              :class="getFieldError('name') ? 'border-rose-500' : ''" />
            <small v-if="getFieldError('name')" class="text-caption !text-rose-600">{{ getFieldError("name") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="vessel-type">Tipe Kapal</FormLabel>
            <FormInput id="vessel-type" v-model="form.type" placeholder="Tipe Kapal" />
          </div>

          <div>
            <FormLabel htmlFor="vessel-classification">Klasifikasi</FormLabel>
            <FormInput id="vessel-classification" v-model="form.classification" placeholder="Klasifikasi" />
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
        </div>
      </div>

      <div class="p-4 border border-slate-200 rounded-lg">
        <h3 class="mb-3 text-overline">Dimensi</h3>
        <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
          <NumberField id="vessel-max-capacity" v-model="form.max_capacity" label="Kapasitas Maks" suffix="m³"
            :decimals="0" />
          <NumberField id="vessel-length" v-model="form.length" label="Panjang" suffix="m" :decimals="2" />
          <NumberField id="vessel-width" v-model="form.width" label="Lebar" suffix="m" :decimals="2" />

          <div>
            <FormLabel htmlFor="vessel-origin">Asal Kapal</FormLabel>
            <FormInput id="vessel-origin" v-model="form.origin" placeholder="Asal Kapal" />
          </div>
        </div>
      </div>

      <LogisticDocumentsSection parent-type="vessels" :parent-id="documentParentId"
        :allowed-types="DOCUMENT_TYPE_OPTIONS" v-model="stagedDocuments" />
    </div>
  </FormModal>
</template>
