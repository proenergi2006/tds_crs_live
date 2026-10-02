<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { helpers, minValue, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import NumberField from "@/components/SystemDesign/Form/NumberField.vue";
import { FormLabel, FormSelect, FormSwitch } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import type { FormMode } from "../types";
import type { SatuanRef, Volume } from "./types";

const volumeApi = createResourceApi("/volumes");
const satuanApi = createResourceApi("/satuans");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: Volume | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: Volume, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);
const satuans = ref<SatuanRef[]>([]);

const form = reactive({
  volume: 0,
  id_satuan: "" as number | "",
  is_active: true,
});

const serverErrors = reactive({
  volume: "",
  id_satuan: "",
  is_active: "",
});

const rules = {
  volume: {
    required: helpers.withMessage("Volume wajib diisi", required),
    minValue: helpers.withMessage("Volume wajib diisi", minValue(1)),
  },
  id_satuan: {
    required: helpers.withMessage("Satuan wajib dipilih", required),
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
  fetchSatuans();
});

async function fetchSatuans() {
  try {
    const { data } = await satuanApi.getAll({ as_list: true });
    satuans.value = data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data satuan");
  }
}

function resetForm() {
  resetErrors();

  if (props.mode === "edit" && props.item) {
    form.volume = props.item.volume;
    form.id_satuan = props.item.id_satuan;
    form.is_active = props.item.is_active;
    return;
  }

  form.volume = 0;
  form.id_satuan = "";
  form.is_active = true;
}

function resetErrors() {
  formError.value = null;
  v$.value.$reset();
  serverErrors.volume = "";
  serverErrors.id_satuan = "";
  serverErrors.is_active = "";
}

function getFieldError(field: keyof typeof serverErrors) {
  return serverErrors[field] || (v$.value as any)[field]?.$errors[0]?.$message?.toString() || "";
}

function buildPayload() {
  return {
    volume: Number(form.volume),
    id_satuan: form.id_satuan,
    is_active: form.is_active,
  };
}

function attachSatuan(row: Omit<Volume, "satuan">): Volume {
  return {
    ...row,
    satuan: satuans.value.find((s) => s.id_satuan === row.id_satuan) ?? null,
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
      props.mode === "create" ? await volumeApi.store(payload) : await volumeApi.update(props.item!.id, payload);

    success("Berhasil", props.mode === "create" ? "Volume berhasil ditambahkan" : "Volume berhasil diperbarui");
    emit("success", attachSatuan(data), props.mode);
  } catch (e: any) {
    handleSubmitError(e);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Volume' : 'Edit Volume'" :loading="loading"
    :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" @close="$emit('close')" @submit="submitForm">
    <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
      <div>
        <NumberField id="volume-volume" v-model="form.volume" label="Volume" required :decimals="0"
          :error="getFieldError('volume')" />
      </div>

      <div>
        <FormLabel htmlFor="volume-satuan">
          Satuan
          <RequiredAsterisk />
        </FormLabel>
        <FormSelect id="volume-satuan" v-model="form.id_satuan"
          :class="getFieldError('id_satuan') ? 'border-rose-500' : ''">
          <option disabled value="">-- Pilih Satuan --</option>
          <option v-for="s in satuans" :key="s.id_satuan" :value="s.id_satuan">
            {{ s.nama_satuan }}
          </option>
        </FormSelect>
        <small v-if="getFieldError('id_satuan')" class="text-caption !text-rose-600">{{ getFieldError("id_satuan")
          }}</small>
      </div>

      <div class="md:col-span-2">
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
