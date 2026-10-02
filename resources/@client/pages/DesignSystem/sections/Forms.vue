<script setup lang="ts">
import { ref } from "vue";

import { FormCheck, FormInput, FormLabel, FormSelect, FormSwitch, FormTextarea } from "@/components/Base/Form";
import TomSelect from "@/components/Base/TomSelect";
import DateField from "@/components/SystemDesign/Form/DateField.vue";
import FileUploadField from "@/components/SystemDesign/Form/FileUploadField.vue";
import RadioCard from "@/components/SystemDesign/Form/RadioCard.vue";
import RequiredAsterisk from "@/components/SystemDesign/Form/RequiredAsterisk.vue";
import RichTextField from "@/components/SystemDesign/Form/RichTextField.vue";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const textValue = ref("");
const dateValue = ref("");
const radioValue = ref("A");
const switchValue = ref(true);
const checkboxValue = ref(true);
const file = ref<File | null>(null);
const tomSelectValue = ref<string[]>([]);
const richTextValue = ref("");
</script>

<template>
  <ShowcaseSection id="forms" title="Form Components"
    description="Input dasar dari Base/Form + field komposit dari SystemDesign/Form. State error pakai class .input-error/.input-error-text (theme-aware, dipakai komponen SystemDesign/Form/*Field.vue) — ini yang dijadikan acuan resmi ke depan.">
    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <div>
        <FormLabel>Default
          <RequiredAsterisk />
        </FormLabel>
        <FormInput v-model="textValue" placeholder="Contoh teks..." />
      </div>

      <div>
        <FormLabel>Disabled</FormLabel>
        <FormInput value="Tidak bisa diedit" disabled />
      </div>

      <div>
        <FormLabel>Error (.input-error)</FormLabel>
        <FormInput class="input-error" value="Nilai salah" />
        <small class="input-error-text">Field ini wajib diisi dengan format yang benar</small>
      </div>

      <div>
        <FormLabel>With helper text</FormLabel>
        <FormInput placeholder="nama@perusahaan.com" />
        <small class="text-caption">Digunakan untuk notifikasi sistem</small>
      </div>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <div>
        <h3 class="mb-3 text-overline">Select (native)</h3>
        <FormSelect class="max-w-xs">
          <option value="">-- Pilih --</option>
          <option value="a">Opsi A</option>
          <option value="b">Opsi B</option>
        </FormSelect>
      </div>

      <div>
        <h3 class="mb-3 text-overline">Select — TomSelect (searchable, multi)</h3>
        <TomSelect v-model="tomSelectValue" multiple class="max-w-xs"
          :options="{ placeholder: 'Pilih satu atau lebih...' }">
          <option value="a">Opsi A</option>
          <option value="b">Opsi B</option>
          <option value="c">Opsi C</option>
        </TomSelect>
        <p class="mt-1 text-caption">Dipakai luas untuk select searchable/multi (Users, Customer/Form, Penawaran/Form,
          dll). Mendukung error state lewat class `input-error` juga.</p>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Textarea</h3>
      <FormTextarea class="max-w-md" rows="3" placeholder="Catatan tambahan..." />
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-3">
      <div>
        <h3 class="mb-3 text-overline">Checkbox</h3>
        <FormCheck>
          <FormCheck.Input v-model="checkboxValue" type="checkbox" />
          <FormCheck.Label>Setujui syarat & ketentuan</FormCheck.Label>
        </FormCheck>
      </div>

      <div>
        <h3 class="mb-3 text-overline">Switch</h3>
        <FormSwitch>
          <FormSwitch.Input v-model="switchValue" type="checkbox" />
        </FormSwitch>
      </div>

      <div>
        <h3 class="mb-3 text-overline">Radio (native)</h3>
        <div class="flex gap-4">
          <FormCheck>
            <FormCheck.Input v-model="radioValue" type="radio" name="ds-radio" value="A" />
            <FormCheck.Label>A</FormCheck.Label>
          </FormCheck>
          <FormCheck>
            <FormCheck.Input v-model="radioValue" type="radio" name="ds-radio" value="B" />
            <FormCheck.Label>B</FormCheck.Label>
          </FormCheck>
        </div>
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">RadioCard</h3>
      <div class="flex flex-wrap gap-2 max-w-lg">
        <RadioCard v-model="radioValue" value="A" title="Milik Sendiri" class="flex-1" />
        <RadioCard v-model="radioValue" value="B" title="Thirdparty" class="flex-1" />
      </div>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <div>
        <h3 class="mb-3 text-overline">Date Picker</h3>
        <DateField v-model="dateValue" label="Tanggal" />
      </div>

      <div>
        <h3 class="mb-3 text-overline">File Upload</h3>
        <FileUploadField v-model="file" accept=".pdf,.jpg,.png" :max-size-mb="5" />
      </div>
    </div>

    <div>
      <h3 class="mb-3 text-overline">Rich Text (CKEditor)</h3>
      <RichTextField v-model="richTextValue" label="Catatan" />
      <p class="mt-1 text-caption">Dipakai untuk draft review Marketing (field financial_review di CreditDataTab.vue) dan catatan approval Admin Finance (field notes di Verify.vue).
        Toolbar sengaja dipangkas (bold/italic/list saja), lazy-loaded terpisah karena bundle CKEditor lumayan besar.
      </p>
    </div>
  </ShowcaseSection>
</template>
