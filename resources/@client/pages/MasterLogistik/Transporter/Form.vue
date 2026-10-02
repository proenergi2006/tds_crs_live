<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useVuelidate } from "@vuelidate/core";
import { email, helpers, required } from "@vuelidate/validators";

import FormModal from "@/components/SystemDesign/Form/FormModal.vue";
import RadioCard from "@/components/SystemDesign/Form/RadioCard.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import { FormInput, FormLabel, FormSelect, FormSwitch, FormTextarea } from "@/components/Base/Form";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { createResourceApi } from "@/utils/resourceApi";

import LogisticDocumentsSection from "../components/LogisticDocumentsSection.vue";
import type { EnumOption, FormMode, StagedDocument } from "../types";
import type { Transporter, TransportCapability, TransporterOwnership } from "./types";

const OWNERSHIP_OPTIONS: EnumOption<TransporterOwnership>[] = [
  { value: "OWN", label: "Milik Sendiri" },
  { value: "THIRDPARTY", label: "Thirdparty" },
];

const CAPABILITY_OPTIONS: EnumOption<TransportCapability>[] = [
  { value: "VESSEL", label: "Kapal" },
  { value: "TRUCK", label: "Truck" },
  { value: "VESSEL_TRUCK", label: "Truck & Kapal" },
];

const DOCUMENT_TYPE_OPTIONS: EnumOption[] = [
  { value: "NIB", label: "NIB" },
  { value: "SIUP", label: "SIUP" },
  { value: "SIUPAL", label: "SIUPAL" },
];

const transporterApi = createResourceApi("/transporters");
const cabangApi = createResourceApi("/cabangs");
const { success, error } = useNotification();

const props = withDefaults(
  defineProps<{
    open: boolean;
    mode: FormMode;
    item?: Transporter | null;
  }>(),
  {
    item: null,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "success", data: Transporter, mode: FormMode): void;
}>();

const loading = ref(false);
const formError = ref<string | null>(null);
const cabangs = ref<{ id_cabang: number; nama_cabang: string }[]>([]);
const stagedDocuments = ref<StagedDocument[]>([]);

const form = reactive({
  company_name: "",
  short_name: "",
  id_cabang: "" as number | "",
  ownership: "" as TransporterOwnership | "",
  transport_capability: "" as TransportCapability | "",
  is_active: true,
  address: "",
  phone: "",
  email: "",
  mobile_phone: "",
  terms: "",
  note: "",
});

const serverErrors = reactive({
  company_name: "",
  short_name: "",
  id_cabang: "",
  ownership: "",
  transport_capability: "",
  is_active: "",
  address: "",
  phone: "",
  email: "",
  mobile_phone: "",
  terms: "",
  note: "",
});

const rules = {
  company_name: {
    required: helpers.withMessage("Nama Perusahaan wajib diisi", required),
  },
  ownership: {
    required: helpers.withMessage("Kepemilikan wajib dipilih", required),
  },
  transport_capability: {
    required: helpers.withMessage("Kemampuan Angkut wajib dipilih", required),
  },
  email: {
    email: helpers.withMessage("Format email tidak valid", email),
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
  fetchCabangs();
});

async function fetchCabangs() {
  try {
    const { data } = await cabangApi.getAll({ as_list: true });
    cabangs.value = data;
  } catch (e: any) {
    error("Gagal", e.response?.data?.message ?? "Gagal memuat data cabang");
  }
}

function resetForm() {
  resetErrors();
  stagedDocuments.value = [];

  if (props.mode === "edit" && props.item) {
    const item = props.item;
    form.company_name = item.company_name;
    form.short_name = item.short_name ?? "";
    form.id_cabang = item.cabang?.id ?? "";
    form.ownership = item.ownership?.value ?? "";
    form.transport_capability = item.transport_capability?.value ?? "";
    form.is_active = item.is_active;
    form.address = item.address ?? "";
    form.phone = item.phone ?? "";
    form.email = item.email ?? "";
    form.mobile_phone = item.mobile_phone ?? "";
    form.terms = item.terms ?? "";
    form.note = item.note ?? "";
    return;
  }

  form.company_name = "";
  form.short_name = "";
  form.id_cabang = "";
  form.ownership = "";
  form.transport_capability = "";
  form.is_active = true;
  form.address = "";
  form.phone = "";
  form.email = "";
  form.mobile_phone = "";
  form.terms = "";
  form.note = "";
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
    company_name: form.company_name.trim(),
    short_name: optionalOrNull(form.short_name),
    id_cabang: form.id_cabang === "" ? null : form.id_cabang,
    ownership: form.ownership,
    transport_capability: form.transport_capability,
    is_active: form.is_active,
    address: optionalOrNull(form.address),
    phone: optionalOrNull(form.phone),
    email: optionalOrNull(form.email),
    mobile_phone: optionalOrNull(form.mobile_phone),
    terms: optionalOrNull(form.terms),
    note: optionalOrNull(form.note),
  };
}

function appendIfFilled(fd: FormData, key: string, value: string) {
  if (value) fd.append(key, value);
}

function buildFormData(): FormData {
  const fd = new FormData();

  fd.append("data_transporter[company_name]", form.company_name.trim());
  appendIfFilled(fd, "data_transporter[short_name]", form.short_name.trim());
  if (form.id_cabang !== "") fd.append("data_transporter[id_cabang]", String(form.id_cabang));
  fd.append("data_transporter[ownership]", form.ownership);
  fd.append("data_transporter[transport_capability]", form.transport_capability);
  fd.append("data_transporter[is_active]", form.is_active ? "1" : "0");
  appendIfFilled(fd, "data_transporter[address]", form.address.trim());
  appendIfFilled(fd, "data_transporter[phone]", form.phone.trim());
  appendIfFilled(fd, "data_transporter[email]", form.email.trim());
  appendIfFilled(fd, "data_transporter[mobile_phone]", form.mobile_phone.trim());
  appendIfFilled(fd, "data_transporter[terms]", form.terms.trim());
  appendIfFilled(fd, "data_transporter[note]", form.note.trim());

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
        ? await transporterApi.store(buildFormData())
        : await transporterApi.update(props.item!.id, buildPayload());

    success("Berhasil", props.mode === "create" ? "Transporter berhasil ditambahkan" : "Transporter berhasil diperbarui");

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
  <FormModal :open="open" :title="mode === 'create' ? 'Tambah Transporter' : 'Edit Transporter'" size="xxl"
    :loading="loading" :error="formError" :submit-text="mode === 'create' ? 'Tambah' : 'Simpan'"
    :submit-icon="mode === 'create' ? 'PlusCircle' : 'Save'" :cancel-text="cancelText" @close="$emit('close')"
    @submit="submitForm">
    <div class="space-y-4">
      <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
        <div class="md:row-span-2 p-4 border border-slate-200 rounded-lg">
          <h3 class="mb-3 text-overline">Informasi Umum</h3>
          <div class="space-y-4">
            <div>
              <FormLabel htmlFor="transporter-company-name">
                Nama Perusahaan
                <RequiredAsterisk />
              </FormLabel>
              <FormInput id="transporter-company-name" v-model="form.company_name"
                placeholder="Contoh: PT Sinergi Logistik Indonesia"
                :class="getFieldError('company_name') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('company_name')" class="text-caption !text-rose-600">{{
                getFieldError("company_name") }}</small>
            </div>

            <div>
              <FormLabel htmlFor="transporter-short-name">Singkatan</FormLabel>
              <FormInput id="transporter-short-name" v-model="form.short_name" placeholder="Contoh: SLI" />
            </div>

            <div>
              <FormLabel htmlFor="transporter-cabang">Lokasi (Cabang)</FormLabel>
              <FormSelect id="transporter-cabang" v-model="form.id_cabang">
                <option value="">-- Pilih Cabang --</option>
                <option v-for="c in cabangs" :key="c.id_cabang" :value="c.id_cabang">{{ c.nama_cabang }}</option>
              </FormSelect>
            </div>

            <div>
              <FormLabel>
                Kepemilikan
                <RequiredAsterisk />
              </FormLabel>
              <div class="flex flex-wrap gap-2">
                <RadioCard v-for="opt in OWNERSHIP_OPTIONS" :key="opt.value" v-model="form.ownership" :value="opt.value"
                  :title="opt.label" class="flex-1" />
              </div>
              <small v-if="getFieldError('ownership')" class="text-caption !text-rose-600">{{ getFieldError("ownership")
              }}</small>
            </div>

            <div>
              <FormLabel>
                Kemampuan Angkutan
                <RequiredAsterisk />
              </FormLabel>
              <div class="flex flex-wrap gap-2">
                <RadioCard v-for="opt in CAPABILITY_OPTIONS" :key="opt.value" v-model="form.transport_capability"
                  :value="opt.value" :title="opt.label" class="flex-1" />
              </div>
              <small v-if="getFieldError('transport_capability')" class="text-caption !text-rose-600">
                {{ getFieldError("transport_capability") }}
              </small>
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
          <h3 class="mb-3 text-overline">Informasi Kontak</h3>
          <div class="space-y-4">
            <div class="gap-4 grid grid-cols-2">
              <div>
                <FormLabel htmlFor="transporter-phone">Telepon</FormLabel>
                <FormInput id="transporter-phone" v-model="form.phone" placeholder="Contoh: 021-5551234" />
              </div>

              <div>
                <FormLabel htmlFor="transporter-mobile-phone">Nomor HP</FormLabel>
                <FormInput id="transporter-mobile-phone" v-model="form.mobile_phone"
                  placeholder="Contoh: 0812-3456-7890" />
              </div>
            </div>

            <div>
              <FormLabel htmlFor="transporter-email">Email</FormLabel>
              <FormInput id="transporter-email" v-model="form.email" placeholder="Contoh: admin@perusahaan.com"
                :class="getFieldError('email') ? 'border-rose-500' : ''" />
              <small v-if="getFieldError('email')" class="text-caption !text-rose-600">{{ getFieldError("email")
              }}</small>
            </div>

            <div>
              <FormLabel htmlFor="transporter-address">Alamat</FormLabel>
              <FormTextarea id="transporter-address" v-model="form.address" rows="3"
                placeholder="Contoh: Jl. Industri Raya No. 10, Cikarang, Bekasi 17530" />
            </div>
          </div>
        </div>

        <div class="p-4 border border-slate-200 rounded-lg">
          <h3 class="mb-3 text-overline">Informasi Tambahan (Opsional)</h3>
          <div class="space-y-4">
            <div>
              <FormLabel htmlFor="transporter-terms">Terms</FormLabel>
              <FormInput id="transporter-terms" v-model="form.terms" placeholder="Contoh: Net 30 / COD / TOP 14 Hari" />
            </div>

            <div>
              <FormLabel htmlFor="transporter-note">Catatan</FormLabel>
              <FormTextarea id="transporter-note" v-model="form.note" rows="3"
                placeholder="Catatan tambahan mengenai transporter ini, misalnya kondisi khusus kerja sama (opsional)" />
            </div>
          </div>
        </div>
      </div>

      <LogisticDocumentsSection parent-type="transporters" :parent-id="documentParentId"
        :allowed-types="DOCUMENT_TYPE_OPTIONS" v-model="stagedDocuments" />
    </div>
  </FormModal>
</template>
