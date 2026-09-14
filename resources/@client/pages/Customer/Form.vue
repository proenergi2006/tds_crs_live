<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useVuelidate } from "@vuelidate/core";
import { helpers, required } from "@vuelidate/validators";
import { debounce } from "lodash";
import axios from "axios";

import Alert from "@/components/Base/Alert";
import Button from "@/components/Base/Button";
import Lucide from "@/components/Base/Lucide";
import TomSelect from "@/components/Base/TomSelect";
import { Dialog } from "@/components/Base/Headless";
import { FormInput, FormLabel, FormTextarea } from "@/components/Base/Form";
import CardSection from "@/components/SystemDesign/Page/CardSection.vue";
import FormPage from "@/components/SystemDesign/Form/FormPage.vue";
import RadioCard from "@/components/SystemDesign/Form/RadioCard.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import { useNotification } from "@/components/SystemDesign/Notification/useNotification";
import { useRegionCascade } from "@/composables/useRegionCascade";
import { useAuthStore } from "@/stores/auth";
import { createResourceApi } from "@/utils/resourceApi.js";

const router = useRouter();
const auth = useAuthStore();
const { success, error: notifyError } = useNotification();
const customerApi = createResourceApi("/customers");

const isProenergi = computed(() => [13, 14].includes(Number(auth.user?.id_role)));
const indexRoute = "customers-list";

const loading = ref(false);
const formError = ref<string | null>(null);

/* State: lookups (cascading province -> regency, BPS data via useRegionCascade) */
const region = useRegionCascade();

/* nama alamat di-derive dari list region yang sudah ke-fetch, region cascade cuma nyimpen id */
const fullAddressSummary = computed(() => {
  const villageName = region.villages.value.find((v) => v.id === form.village_id)?.name;
  const districtName = region.districts.value.find((d) => d.id === form.district_id)?.name;
  const regencyName = region.regencies.value.find((r) => r.id === form.regency_id)?.name;
  const provinceName = region.provinces.value.find((p) => p.id === form.province_id)?.name;

  const parts = [
    form.company_address.trim(),
    [villageName, districtName].filter(Boolean).join(", "),
    [regencyName, provinceName].filter(Boolean).join(", "),
    form.postal_code.trim(),
  ].filter(Boolean);

  return parts.length ? parts.join(", ") : "-";
});

const companyInitial = computed(() => (form.company_name.trim().charAt(0) || "C").toUpperCase());

const form = reactive({
  email: "",
  customer_type: "",
  company_name: "",
  company_address: "",
  province_id: "",
  regency_id: "",
  district_id: "",
  village_id: "",
  postal_code: "",
  phone: "",
});

/* State: cek ketersediaan nama perusahaan (informational, tidak menahan submit) */
const nameCheckStatus = ref<"idle" | "checking" | "available" | "taken">("idle");
const nameMatches = ref<
  { id_customer: number; company_name: string; marketing: { id: number | null; name: string | null } }[]
>([]);
const showDuplicatePopup = ref(false);

const rules = {
  company_name: { required: helpers.withMessage("Nama perusahaan wajib diisi", required) },
  province_id: { required: helpers.withMessage("Provinsi wajib dipilih", required) },
  regency_id: { required: helpers.withMessage("Kabupaten/Kota wajib dipilih", required) },
  customer_type: { required: helpers.withMessage("Jenis customer wajib dipilih", required) },
  company_address: { required: helpers.withMessage("Alamat perusahaan wajib diisi", required) },
  district_id: { required: helpers.withMessage("Kecamatan wajib dipilih", required) },
  village_id: { required: helpers.withMessage("Kelurahan/Desa wajib dipilih", required) },
  postal_code: { required: helpers.withMessage("Kode pos wajib diisi", required) },
};

const v$ = useVuelidate(rules, form);

const pageTitle = computed(() => {
  const suffix = isProenergi.value ? " Proenergi" : "";
  return `Tambah Customer${suffix}`;
});

const pageDescription = "Tambahkan data customer baru untuk proses penawaran.";

const submitText = "Simpan Customer";

watch(
  () => form.province_id,
  async (newProv) => {
    form.regency_id = "";
    await region.fetchRegencies(newProv || null);
  },
);

watch(
  () => form.regency_id,
  async (newRegency) => {
    form.district_id = "";
    await region.fetchDistricts(newRegency || null);
  },
);

watch(
  () => form.district_id,
  async (newDistrict) => {
    form.village_id = "";
    await region.fetchVillages(newDistrict || null);
  },
);

watch(
  () => form.village_id,
  (newVillage) => {
    if (!newVillage) return;
    const matched = region.villages.value.find((v) => v.id === newVillage);
    if (matched && matched.postal_code) {
      form.postal_code = matched.postal_code;
    }
  },
);

// cek ketersediaan nama perusahaan, debounced biar gak request tiap keystroke
watch(() => form.company_name, debounce(checkCompanyName, 400));

onMounted(async () => {
  await region.fetchProvinces();
});

async function checkCompanyName() {
  const companyName = form.company_name.trim();
  if (!companyName) {
    nameCheckStatus.value = "idle";
    nameMatches.value = [];
    return;
  }

  nameCheckStatus.value = "checking";
  try {
    const { data } = await axios.get("/api/customers/check-company-name", { params: { company_name: companyName } });
    nameMatches.value = data.matches || [];
    nameCheckStatus.value = data.available ? "available" : "taken";
  } catch {
    // cuma fitur informational, gagal cek jangan sampai ganggu pengisian form
    nameCheckStatus.value = "idle";
    nameMatches.value = [];
  }
}

// binding manual (bukan v-model) biar cursor gak lompat ke akhir pas uppercase transform
function onCompanyNameInput(event: Event) {
  const target = event.target as HTMLInputElement;
  const cursorPos = target.selectionStart;
  form.company_name = target.value.toUpperCase();
  nextTick(() => {
    target.setSelectionRange(cursorPos, cursorPos);
  });
}

function getFieldError(field: keyof typeof form) {
  return v$.value[field]?.$errors[0]?.$message?.toString() || "";
}

async function submit() {
  formError.value = null;
  const isValid = await v$.value.$validate();
  if (!isValid) {
    notifyError("Gagal", "Periksa kembali data yang wajib diisi");
    return;
  }

  loading.value = true;
  try {
    const createPayload = {
      corporate_detail: {
        email: form.email,
        customer_type: form.customer_type,
        company_name: form.company_name,
        phone: form.phone,
      },
      head_office_address: {
        address_line: form.company_address,
        province_id: form.province_id,
        regency_id: form.regency_id,
        district_id: form.district_id,
        village_id: form.village_id,
        postal_code: form.postal_code,
      },
    };
    await customerApi.store(createPayload);
    success("Berhasil", "Customer berhasil ditambahkan");

    router.push({ name: indexRoute });
  } catch (e: any) {
    const errors = e.response?.data?.errors;
    if (e.response?.status === 422 && errors) {
      formError.value = Object.values(errors)
        .map((v: any) => v?.[0])
        .filter(Boolean)
        .join("\n");
    } else {
      formError.value = e.response?.data?.message ?? "Terjadi kesalahan";
    }
  } finally {
    loading.value = false;
  }
}

function cancel() {
  if (loading.value) return;
  router.push({ name: indexRoute });
}
</script>

<template>
  <FormPage
    :title="pageTitle"
    :description="pageDescription"
    surface="plain"
    size="full"
    layout="sidebar"
    footer-placement="sidebar"
    :loading="loading"
    :error="formError"
    :submit-text="submitText"
    submit-icon="Save"
    cancel-icon="ArrowLeft"
    @cancel="cancel"
    @submit="submit"
  >
    <template #action>
      <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2" @click="cancel">
        <Lucide icon="ArrowLeft" class="w-4 h-4" />
        Kembali
      </Button>
    </template>

    <!-- Section: Informasi Dasar -->
    <CardSection title="Informasi Dasar" description="Data utama customer dalam sistem">
      <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
        <div>
          <FormLabel for="company_name">
            Nama Perusahaan
            <RequiredAsterisk />
          </FormLabel>
          <FormInput
            id="company_name"
            :value="form.company_name"
            placeholder="cth. PT Sinar Jaya Abadi"
            :class="getFieldError('company_name') ? 'border-rose-500' : ''"
            @input="onCompanyNameInput"
            @blur="v$.company_name.$touch()"
          />
          <small v-if="getFieldError('company_name')" class="mt-1 font-caption !text-rose-600">
            {{ getFieldError("company_name") }}
          </small>
          <div v-else-if="nameCheckStatus === 'available'" class="mt-1 font-caption !text-emerald-600">
            Nama tersedia
          </div>
          <div
            v-else-if="nameCheckStatus === 'taken'"
            class="flex items-center gap-2 mt-1 font-caption !text-amber-600"
          >
            <span>Sudah terdaftar, {{ nameMatches.length }} kecocokan ditemukan</span>
            <button type="button" class="font-semibold underline underline-offset-2" @click="showDuplicatePopup = true">
              Lihat daftar
            </button>
          </div>
        </div>

        <div>
          <FormLabel for="customer_type">
            Jenis Customer
            <RequiredAsterisk />
          </FormLabel>
          <div class="gap-3 grid grid-cols-2 mt-1">
            <RadioCard
              v-model="form.customer_type"
              value="Retail"
              title="Retail"
              @update:model-value="v$.customer_type.$touch()"
            />
            <RadioCard
              v-model="form.customer_type"
              value="Project"
              title="Project"
              @update:model-value="v$.customer_type.$touch()"
            />
          </div>
          <small v-if="getFieldError('customer_type')" class="font-caption !text-rose-600">
            {{ getFieldError("customer_type") }}
          </small>
        </div>

        <div>
          <FormLabel for="phone">Telepon Perusahaan</FormLabel>
          <FormInput id="phone" v-model="form.phone" placeholder="cth. (021) 5551234" />
        </div>

        <div>
          <FormLabel for="email">Email Perusahaan</FormLabel>
          <FormInput
            id="email"
            v-model="form.email"
            type="email"
            placeholder="cth. purchasing@sinarjaya.co.id (opsional)"
            autocomplete="off"
          />
        </div>
      </div>
    </CardSection>

    <!-- Section: Detail Alamat -->
    <CardSection title="Detail Alamat" description="Alamat lengkap customer">
      <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
        <div class="md:col-span-2">
          <FormLabel for="company_address">
            Alamat Perusahaan
            <RequiredAsterisk />
          </FormLabel>
          <FormTextarea
            id="company_address"
            v-model="form.company_address"
            placeholder="cth. Jl. Gatot Subroto No. 12, RT 004/RW 007, Gedung ABC Lt. 5"
            :rows="3"
            :class="getFieldError('company_address') ? 'border-rose-500' : ''"
            @blur="v$.company_address.$touch()"
          />
          <small v-if="getFieldError('company_address')" class="font-caption !text-rose-600">
            {{ getFieldError("company_address") }}
          </small>
        </div>

        <div>
          <FormLabel for="province_id">
            Provinsi
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            id="province_id"
            v-model="form.province_id"
            class="w-full"
            :class="getFieldError('province_id') ? 'border-rose-500' : ''"
            @change="v$.province_id.$touch()"
          >
            <option value="">Cari Provinsi</option>
            <option v-for="p in region.provinces.value" :key="p.id" :value="p.id">
              {{ p.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('province_id')" class="font-caption !text-rose-600">
            {{ getFieldError("province_id") }}
          </small>
        </div>

        <div>
          <FormLabel for="regency_id">
            Kabupaten/Kota
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            :key="String(!!form.province_id)"
            id="regency_id"
            v-model="form.regency_id"
            class="w-full"
            :class="getFieldError('regency_id') ? 'border-rose-500' : ''"
            @change="v$.regency_id.$touch()"
          >
            <option value="">
              {{ form.province_id ? "Cari Kabupaten/Kota" : "-- Pilih Provinsi dulu --" }}
            </option>
            <option v-for="k in region.regencies.value" :key="k.id" :value="k.id">
              {{ k.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('regency_id')" class="font-caption !text-rose-600">
            {{ getFieldError("regency_id") }}
          </small>
        </div>

        <div>
          <FormLabel for="district_id">
            Kecamatan
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            :key="String(!!form.regency_id)"
            id="district_id"
            v-model="form.district_id"
            class="w-full"
            :class="getFieldError('district_id') ? 'border-rose-500' : ''"
            @change="v$.district_id.$touch()"
          >
            <option value="">
              {{ form.regency_id ? "Cari Kecamatan" : "-- Pilih Kabupaten/Kota dulu --" }}
            </option>
            <option v-for="d in region.districts.value" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('district_id')" class="font-caption !text-rose-600">
            {{ getFieldError("district_id") }}
          </small>
        </div>

        <div>
          <FormLabel for="village_id">
            Kelurahan/Desa
            <RequiredAsterisk />
          </FormLabel>
          <TomSelect
            :key="String(!!form.district_id)"
            id="village_id"
            v-model="form.village_id"
            class="w-full"
            :class="getFieldError('village_id') ? 'border-rose-500' : ''"
            @change="v$.village_id.$touch()"
          >
            <option value="">
              {{ form.district_id ? "Cari Kelurahan/Desa" : "-- Pilih Kecamatan dulu --" }}
            </option>
            <option v-for="v in region.villages.value" :key="v.id" :value="v.id">
              {{ v.name }}
            </option>
          </TomSelect>
          <small v-if="getFieldError('village_id')" class="font-caption !text-rose-600">
            {{ getFieldError("village_id") }}
          </small>
        </div>

        <div>
          <FormLabel for="postal_code">
            Kode Pos
            <RequiredAsterisk />
          </FormLabel>
          <FormInput
            id="postal_code"
            v-model="form.postal_code"
            placeholder="Kode Pos"
            :class="getFieldError('postal_code') ? 'border-rose-500' : ''"
            @blur="v$.postal_code.$touch()"
          />
          <small v-if="getFieldError('postal_code')" class="font-caption !text-rose-600">
            {{ getFieldError("postal_code") }}
          </small>
        </div>
      </div>
    </CardSection>

    <!-- Sidebar: Ringkasan & Aksi -->
    <template #sidebar>
      <section
        class="relative bg-gradient-to-br from-theme-1 via-emerald-800 to-green-600 shadow-sm p-6 rounded-lg overflow-hidden text-white"
      >
        <!-- Aksen dekoratif, murni visual -->
        <div class="-top-14 -right-10 absolute bg-white/10 rounded-full w-40 h-40 pointer-events-none" />
        <div class="-right-6 -bottom-16 absolute bg-white/5 rounded-full w-32 h-32 pointer-events-none" />

        <div class="relative">
          <div class="flex items-center gap-3">
            <div
              class="flex justify-center items-center bg-white/15 rounded-lg w-12 h-12 font-header !text-white text-xl shrink-0"
            >
              {{ companyInitial }}
            </div>

            <div class="min-w-0">
              <h2 class="font-header !text-white text-base truncate leading-snug">
                {{ form.company_name || "Nama Perusahaan" }}
              </h2>
              <div class="flex items-center gap-1.5 mt-1">
                <span
                  v-if="form.customer_type"
                  class="inline-flex items-center bg-white/15 px-2 py-0.5 rounded-full font-label !text-white"
                >
                  {{ form.customer_type }}
                </span>
                <span class="font-caption !text-white/60">• Ringkasan Profil</span>
              </div>
            </div>
          </div>

          <hr class="my-5 border-white/20" />

          <div class="space-y-3.5">
            <div class="flex items-center gap-3">
              <div class="flex justify-center items-center bg-white/15 rounded-full w-8 h-8 shrink-0">
                <Lucide icon="Phone" class="w-4 h-4 !text-white" />
              </div>
              <p class="font-body !text-white truncate">{{ form.phone || "-" }}</p>
            </div>

            <div v-if="form.email" class="flex items-center gap-3">
              <div class="flex justify-center items-center bg-white/15 rounded-full w-8 h-8 shrink-0">
                <Lucide icon="Mail" class="w-4 h-4 !text-white" />
              </div>
              <p class="font-body !text-white truncate">{{ form.email }}</p>
            </div>

            <div class="flex items-start gap-3">
              <div class="flex justify-center items-center bg-white/15 mt-0.5 rounded-full w-8 h-8 shrink-0">
                <Lucide icon="MapPin" class="w-4 h-4 !text-white" />
              </div>
              <p class="font-body !text-white">{{ fullAddressSummary }}</p>
            </div>
          </div>
        </div>
      </section>
    </template>
  </FormPage>

  <Dialog :open="showDuplicatePopup" size="lg" @close="showDuplicatePopup = false">
    <Dialog.Panel>
      <div class="p-6">
        <div class="pb-4 border-slate-200 border-b">
          <h3 class="font-header">Nama Perusahaan Sudah Terdaftar</h3>
          <p class="mt-1 font-caption text-slate-500">
            {{ nameMatches.length }} customer lain memakai nama yang sama/mirip
          </p>
        </div>

        <div class="space-y-3 mt-4">
          <div
            v-for="match in nameMatches"
            :key="match.id_customer"
            class="px-4 py-3 border border-slate-200 rounded-md"
          >
            <p class="font-body font-semibold">{{ match.company_name }}</p>
            <p class="mt-0.5 font-caption text-slate-500">Marketing: {{ match.marketing?.name || "-" }}</p>
          </div>
        </div>

        <Alert variant="soft-warning" class="mt-4">
          Hubungi tim Key Account untuk verifikasi sebelum melanjutkan.
        </Alert>
      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-slate-200 border-t">
        <Button variant="outline-secondary" @click="showDuplicatePopup = false">Tutup</Button>
      </div>
    </Dialog.Panel>
  </Dialog>
</template>
