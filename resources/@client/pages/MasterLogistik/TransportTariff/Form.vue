<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import CurrencyField from "@/components/SystemDesign/Form/CurrencyField.vue";
import { FormLabel, FormSelect, FormSwitch, FormTextarea } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";
import { formatNumber } from "@/utils/format";

import type { EnumOption, FormMode, TransporterRef } from "../types";
import type { TransportAreaRef, TransportTariff, TransportType, VolumeRef } from "./types";

const TRANSPORT_TYPE_OPTIONS: EnumOption<TransportType>[] = [
  { value: "VESSEL", label: "Kapal" },
  { value: "TRUCK", label: "Truck" },
];

const transportTariffApi = createResourceApi("/transport-tariffs");
const transporterApi = createResourceApi("/transporters");
const transportAreaApi = createResourceApi("/transport-areas");
const volumeApi = createResourceApi("/volumes");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: TransportTariff | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: TransportTariff, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);

const options = {
  transporters: ref<TransporterRef[]>([]),
  transportAreas: ref<TransportAreaRef[]>([]),
  volumes: ref<VolumeRef[]>([]),
};

const form = reactive({
  transporter_id: "" as number | "",
  transport_type: "" as TransportType | "",
  transport_area_id: "" as number | "",
  volume_id: "" as number | "",
  rate: 0,
  note: "",
  is_active: true,
});

const serverErrors = reactive({
  transporter_id: "",
  transport_type: "",
  transport_area_id: "",
  volume_id: "",
  rate: "",
  note: "",
  is_active: "",
});

const rules = {
  transporter_id: {
    required: helpers.withMessage("Transporter wajib dipilih", required),
  },
  transport_type: {
    required: helpers.withMessage("Jenis Angkutan wajib dipilih", required),
  },
  transport_area_id: {
    required: helpers.withMessage("Transport Area wajib dipilih", required),
  },
  volume_id: {
    required: helpers.withMessage("Volume wajib dipilih", required),
  },
  rate: {
    required: helpers.withMessage("Tarif wajib diisi", required),
  },
};

const v$ = useVuelidate(rules, form);

watch(
  () => [props.open, props.mode, props.item],
  () => {
    if (props.open) resetForm();
  },
  { immediate: true },
);

onMounted(() => {
  fetchOptions();
});

async function fetchOptions() {
  try {
    const [t, w, v] = await Promise.all([
      transporterApi.getAll({ as_list: true }),
      transportAreaApi.getAll({ as_list: true }),
      volumeApi.getAll({ as_list: true }),
    ]);

    options.transporters.value = t.data.data;
    options.transportAreas.value = w.data.data;
    options.volumes.value = v.data.data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data pendukung");
  }
}

function resetForm() {
  resetErrors();

  if (props.mode === "edit" && props.item) {
    const item = props.item;
    form.transporter_id = item.transporter?.id ?? "";
    form.transport_type = item.transport_type?.value ?? "";
    form.transport_area_id = item.transport_area?.id ?? "";
    form.volume_id = item.volume?.id ?? "";
    form.rate = Number(item.rate);
    form.note = item.note ?? "";
    form.is_active = item.is_active;
    return;
  }

  form.transporter_id = "";
  form.transport_type = "";
  form.transport_area_id = "";
  form.volume_id = "";
  form.rate = 0;
  form.note = "";
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
    transport_area_id: form.transport_area_id,
    volume_id: form.volume_id,
    transport_type: form.transport_type,
    rate: Number(form.rate),
    note: form.note.trim() === "" ? null : form.note.trim(),
    is_active: form.is_active,
  };
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
    const payload = buildPayload();
    const { data } =
      props.mode === "create" ? await transportTariffApi.store(payload) : await transportTariffApi.update(props.item!.id, payload);

    success(
      "Berhasil",
      props.mode === "create" ? "Transport Tariff berhasil ditambahkan" : "Transport Tariff berhasil diperbarui",
    );
    emit("success", data.data, props.mode);
  } catch (e: any) {
    handleSubmitError(e);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Transport Tariff' : 'Edit Transport Tariff'"
    :loading="loading" :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" @close="$emit('close')" @submit="submitForm">
    <div class="space-y-4">
      <div>
        <FormLabel htmlFor="tariff-transporter">
          Transporter
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="tariff-transporter" v-model="form.transporter_id"
          :class="getFieldError('transporter_id') ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Transporter --</option>
          <option v-for="t in options.transporters.value" :key="t.id" :value="t.id">{{ t.company_name }}</option>
        </FormSelect>
        <small v-if="getFieldError('transporter_id')" class="text-caption !text-rose-600">{{
          getFieldError("transporter_id") }}</small>
      </div>

      <div>
        <FormLabel htmlFor="tariff-transport-type">
          Jenis Angkutan
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="tariff-transport-type" v-model="form.transport_type"
          :class="getFieldError('transport_type') ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Jenis Angkutan --</option>
          <option v-for="opt in TRANSPORT_TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </FormSelect>
        <small v-if="getFieldError('transport_type')" class="text-caption !text-rose-600">{{
          getFieldError("transport_type") }}</small>
      </div>

      <div>
        <FormLabel htmlFor="tariff-transport-area">
          Transport Area
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="tariff-transport-area" v-model="form.transport_area_id"
          :class="getFieldError('transport_area_id') ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Transport Area --</option>
          <option v-for="w in options.transportAreas.value" :key="w.id" :value="w.id">{{ w.name }}</option>
        </FormSelect>
        <small v-if="getFieldError('transport_area_id')" class="text-caption !text-rose-600">{{
          getFieldError("transport_area_id") }}</small>
      </div>

      <div>
        <FormLabel htmlFor="tariff-volume">
          Volume
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="tariff-volume" v-model="form.volume_id"
          :class="getFieldError('volume_id') ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Volume --</option>
          <option v-for="v in options.volumes.value" :key="v.id" :value="v.id">{{ formatNumber(v.volume) }}</option>
        </FormSelect>
        <small v-if="getFieldError('volume_id')" class="text-caption !text-rose-600">{{ getFieldError("volume_id")
          }}</small>
      </div>

      <CurrencyField id="tariff-rate" v-model="form.rate" label="Tarif" required :error="getFieldError('rate')" />

      <div>
        <FormLabel htmlFor="tariff-note">Catatan</FormLabel>
        <FormTextarea id="tariff-note" v-model="form.note" rows="3" placeholder="Catatan" />
      </div>

      <div>
        <FormLabel>Status</FormLabel>
        <div class="flex items-center gap-3 mt-2">
          <FormSwitch>
            <FormSwitch.Input v-model="form.is_active" type="checkbox" />
          </FormSwitch>
          <span class="text-body">{{ form.is_active ? "Active" : "Inactive" }}</span>
        </div>
        <small v-if="getFieldError('is_active')" class="text-caption !text-rose-600">{{ getFieldError("is_active")
          }}</small>
      </div>
    </div>
  </FormModal>
</template>
