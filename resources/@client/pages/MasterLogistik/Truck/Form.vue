<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, minValue, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import { FormInput, FormLabel, FormSelect, FormSwitch } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import LogisticDocumentsSection from "../components/LogisticDocumentsSection.vue";
import type { EnumOption, FormMode, StagedDocument, TransporterRef } from "../types";
import type { Truck } from "./types";

const DOCUMENT_TYPE_OPTIONS: EnumOption[] = [
  { value: "STNK", label: "STNK" },
  { value: "KIR", label: "KIR" },
];

const truckApi = createResourceApi("/trucks");
const transporterApi = createResourceApi("/transporters");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: Truck | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: Truck, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);
const transporters = ref<TransporterRef[]>([]);
const stagedDocuments = ref<StagedDocument[]>([]);

const form = reactive({
  transporter_id: "" as number | "",
  license_plate: "",
  type: "",
  name: "",
  is_active: true,
  max_capacity: 0,
});

const serverErrors = reactive({
  transporter_id: "",
  license_plate: "",
  type: "",
  name: "",
  is_active: "",
  max_capacity: "",
});

const rules = {
  transporter_id: {
    required: helpers.withMessage("Transporter wajib dipilih", required),
  },
  license_plate: {
    required: helpers.withMessage("Plat Nomor wajib diisi", required),
  },
  type: {
    required: helpers.withMessage("Jenis Truck wajib diisi", required),
  },
  max_capacity: {
    required: helpers.withMessage("Kapasitas maksimal wajib diisi", required),
    minValue: helpers.withMessage("Kapasitas maksimal wajib diisi", minValue(1)),
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
    form.license_plate = item.license_plate;
    form.type = item.type;
    form.name = item.name ?? "";
    form.max_capacity = item.max_capacity;
    form.is_active = item.is_active;
    return;
  }

  form.transporter_id = "";
  form.license_plate = "";
  form.type = "";
  form.name = "";
  form.max_capacity = 0;
  form.is_active = true;
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

function buildPayload() {
  return {
    transporter_id: form.transporter_id,
    license_plate: form.license_plate.trim(),
    type: form.type.trim(),
    name: form.name.trim() === "" ? null : form.name.trim(),
    max_capacity: Number(form.max_capacity),
    is_active: form.is_active,
  };
}

function appendIfFilled(fd: FormData, key: string, value: string) {
  if (value) fd.append(key, value);
}

function buildFormData(): FormData {
  const fd = new FormData();

  fd.append("data_truck[transporter_id]", String(form.transporter_id));
  fd.append("data_truck[license_plate]", form.license_plate.trim());
  fd.append("data_truck[type]", form.type.trim());
  fd.append("data_truck[max_capacity]", String(Number(form.max_capacity)));
  fd.append("data_truck[is_active]", form.is_active ? "1" : "0");
  appendIfFilled(fd, "data_truck[name]", form.name.trim());

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
        ? await truckApi.store(buildFormData())
        : await truckApi.update(props.item!.id, buildPayload());

    success("Berhasil", props.mode === "create" ? "Truck berhasil ditambahkan" : "Truck berhasil diperbarui");

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
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Truck' : 'Edit Truck'" size="xl" :loading="loading"
    :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" :cancel-text="cancelText" @close="$emit('close')"
    @submit="submitForm">
    <div class="space-y-4">
      <div class="p-4 border border-slate-200 rounded-lg">
        <h3 class="mb-3 text-overline">Identitas</h3>
        <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
          <div>
            <FormLabel htmlFor="truck-transporter">
              Transporter
              <RequiredAsterisk />
            </FormLabel>
            <FormSelect id="truck-transporter" v-model="form.transporter_id"
              :class="getFieldError('transporter_id') ? 'border-rose-500' : ''">
              <option disabled value="">-- Pilih Transporter --</option>
              <option v-for="t in transporters" :key="t.id" :value="t.id">{{ t.company_name }}</option>
            </FormSelect>
            <small v-if="getFieldError('transporter_id')" class="text-caption !text-rose-600">{{
              getFieldError("transporter_id") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="truck-license-plate">
              Plat Nomor
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="truck-license-plate" v-model="form.license_plate" placeholder="Plat Nomor"
              :class="getFieldError('license_plate') ? 'border-rose-500' : ''" />
            <small v-if="getFieldError('license_plate')" class="text-caption !text-rose-600">{{
              getFieldError("license_plate") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="truck-type">
              Jenis Truck
              <RequiredAsterisk />
            </FormLabel>
            <FormInput id="truck-type" v-model="form.type" placeholder="Jenis Truck"
              :class="getFieldError('type') ? 'border-rose-500' : ''" />
            <small v-if="getFieldError('type')" class="text-caption !text-rose-600">{{ getFieldError("type") }}</small>
          </div>

          <div>
            <FormLabel htmlFor="truck-name">Nama Truck</FormLabel>
            <FormInput id="truck-name" v-model="form.name" placeholder="Nama Truck" />
          </div>

          <div>
            <FormLabel htmlFor="truck-max-capacity">Kapasitas Maks (KL)</FormLabel>
            <FormInput id="truck-max-capacity" v-model="form.max_capacity" placeholder="Kapasitas Maks"
              :class="getFieldError('max_capacity') ? 'border-rose-500' : ''" />
            <small v-if="getFieldError('max_capacity')" class="text-caption !text-rose-600">{{
              getFieldError("max_capacity") }}</small>
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

      <LogisticDocumentsSection parent-type="trucks" :parent-id="documentParentId"
        :allowed-types="DOCUMENT_TYPE_OPTIONS" v-model="stagedDocuments" />
    </div>
  </FormModal>
</template>
