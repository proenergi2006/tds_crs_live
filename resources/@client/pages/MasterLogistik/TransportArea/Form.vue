<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import { FormInput, FormLabel, FormSelect, FormSwitch } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { useRegionCascade } from "@/composables/useRegionCascade";
import { createResourceApi } from "@/utils/resourceApi";

import type { FormMode } from "../types";
import type { TransportArea } from "./types";

const transportAreaApi = createResourceApi("/transport-areas");
const { success, error } = useNotification();
const region = useRegionCascade();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: TransportArea | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: TransportArea, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);

const form = reactive({
  name: "",
  province_id: "",
  regency_id: "",
  is_active: true,
});

const serverErrors = reactive({
  name: "",
  province_id: "",
  regency_id: "",
  is_active: "",
});

const rules = {
  name: {
    required: helpers.withMessage("Nama Area wajib diisi", required),
  },
  province_id: {
    required: helpers.withMessage("Provinsi wajib dipilih", required),
  },
  regency_id: {
    required: helpers.withMessage("Kabupaten/Kota wajib dipilih", required),
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

watch(
  () => form.province_id,
  async (value) => {
    form.regency_id = "";
    await region.fetchRegencies(value || null);
  },
);

onMounted(() => {
  region.fetchProvinces();
});

async function resetForm() {
  resetErrors();

  if (props.mode === "edit" && props.item) {
    await loadRegionForEdit(props.item);
    return;
  }

  form.name = "";
  form.province_id = "";
  form.regency_id = "";
  form.is_active = true;
}

async function loadRegionForEdit(item: TransportArea) {
  form.name = item.name;
  form.is_active = item.is_active;
  form.province_id = item.province?.id ?? "";
  await region.fetchRegencies(form.province_id || null);
  form.regency_id = item.regency?.id ?? "";
}

function resetErrors() {
  formError.value = null;
  v$.value.$reset();
  serverErrors.name = "";
  serverErrors.province_id = "";
  serverErrors.regency_id = "";
  serverErrors.is_active = "";
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || (v$.value as any)[field]?.$errors[0]?.$message?.toString() || "";
}

function buildPayload() {
  return {
    name: form.name.trim(),
    province_id: form.province_id,
    regency_id: form.regency_id,
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
      props.mode === "create"
        ? await transportAreaApi.store(payload)
        : await transportAreaApi.update(props.item!.id, payload);

    success(
      "Berhasil",
      props.mode === "create" ? "Transport Area berhasil ditambahkan" : "Transport Area berhasil diperbarui",
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
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Transport Area' : 'Edit Transport Area'"
    :loading="loading" :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" @close="$emit('close')" @submit="submitForm">
    <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
      <div>
        <FormLabel htmlFor="transport-area-name">
          Nama Area
          <RequiredAsterisk />
        </FormLabel>
        <FormInput id="transport-area-name" v-model="form.name" placeholder="Nama Area"
          :class="getFieldError('name') ? 'border-rose-500' : ''" />
        <small v-if="getFieldError('name')" class="text-caption !text-rose-600">{{ getFieldError("name") }}</small>
      </div>

      <div>
        <FormLabel htmlFor="transport-area-province">
          Provinsi
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="transport-area-province" v-model="form.province_id"
          :class="getFieldError('province_id') ? 'border-rose-500' : ''">
          <option value="">-- Pilih Provinsi --</option>
          <option v-for="p in region.provinces.value" :key="p.id" :value="p.id">{{ p.name }}</option>
        </FormSelect>
        <small v-if="getFieldError('province_id')" class="text-caption !text-rose-600">{{ getFieldError("province_id")
          }}</small>
      </div>

      <div>
        <FormLabel htmlFor="transport-area-regency">
          Kabupaten/Kota
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="transport-area-regency" v-model="form.regency_id" :disabled="!form.province_id"
          :class="getFieldError('regency_id') ? 'border-rose-500' : ''">
          <option value="">{{ form.province_id ? "Pilih Kabupaten/Kota" : "-- Pilih Provinsi dulu --" }}</option>
          <option v-for="k in region.regencies.value" :key="k.id" :value="k.id">{{ k.name }}</option>
        </FormSelect>
        <small v-if="getFieldError('regency_id')" class="text-caption !text-rose-600">{{ getFieldError("regency_id")
          }}</small>
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
  </FormModal>
</template>
