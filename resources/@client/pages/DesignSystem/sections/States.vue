<script setup lang="ts">
import { ref } from "vue";

import Button from "@/components/Base/Button";
import { Disclosure } from "@/components/Base/Headless";
import LoadingIcon from "@/components/Base/LoadingIcon";
import Lucide from "@/components/Base/Lucide";
import Badge from "@/components/SystemDesign/Data/Badge.vue";
import RadioCard from "@/components/SystemDesign/Form/RadioCard.vue";
import ShowcaseSection from "../components/ShowcaseSection.vue";

const selected = ref("A");
</script>

<template>
  <ShowcaseSection id="states" title="States"
    description="Ringkasan state lintas-komponen. Pola detail per komponen lihat section Button/Badge/Forms/Data Display masing-masing.">
    <div class="gap-6 grid grid-cols-2 md:grid-cols-4">
      <div>
        <p class="mb-2 text-form-label">Loading</p>
        <div class="flex">
          <LoadingIcon icon="three-dots" class="w-8 h-8" />
        </div>
        <p class="mt-1 text-caption">Satu-satunya pola loading di codebase — tidak ada komponen skeleton.</p>
      </div>

      <div>
        <p class="mb-2 text-form-label">Disabled</p>
        <Button variant="primary" disabled>Disabled</Button>
      </div>

      <div>
        <p class="mb-2 text-form-label">Error</p>
        <Badge variant="soft-danger">Error</Badge>
      </div>

      <div>
        <p class="mb-2 text-form-label">Success</p>
        <Badge variant="soft-success">Success</Badge>
      </div>
    </div>

    <div>
      <p class="mb-2 text-form-label">Selected</p>
      <div class="flex gap-2 max-w-sm">
        <RadioCard v-model="selected" value="A" title="Opsi A" class="flex-1" />
        <RadioCard v-model="selected" value="B" title="Opsi B" class="flex-1" />
      </div>
    </div>

    <div>
      <p class="mb-2 text-form-label">Expanded / Collapsed (Disclosure)</p>
      <Disclosure.Group class="max-w-md">
        <Disclosure>
          <Disclosure.Button
            class="flex justify-between items-center bg-slate-50 px-4 py-3 border border-slate-200 rounded-lg w-full text-body-strong">
            Klik untuk membuka
            <Lucide icon="ChevronDown" class="w-4 h-4" />
          </Disclosure.Button>
          <Disclosure.Panel class="p-4 border border-slate-200 border-t-0 rounded-b-lg text-body">
            Konten yang muncul saat expanded.
          </Disclosure.Panel>
        </Disclosure>
      </Disclosure.Group>
    </div>

    <div>
      <p class="mb-2 text-form-label">Empty</p>
      <p class="text-caption">Lihat demo penuh di section Data Display &rarr; DataList empty state.</p>
    </div>
  </ShowcaseSection>
</template>
