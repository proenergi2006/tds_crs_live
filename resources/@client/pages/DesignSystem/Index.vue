<script setup lang="ts">
import { computed } from "vue";

import Button from "@/components/Base/Button";
import Lucide from "@/components/Base/Lucide";
import PageHeader from "@/components/SystemDesign/Page/PageHeader.vue";
import { useDarkModeStore } from "@/stores/dark-mode";

import Badges from "./sections/Badges.vue";
import Buttons from "./sections/Buttons.vue";
import Colors from "./sections/Colors.vue";
import DataDisplay from "./sections/DataDisplay.vue";
import Feedback from "./sections/Feedback.vue";
import Forms from "./sections/Forms.vue";
import Icons from "./sections/Icons.vue";
import Spacing from "./sections/Spacing.vue";
import States from "./sections/States.vue";
import Typography from "./sections/Typography.vue";

const darkModeStore = useDarkModeStore();

const isDark = computed(() => darkModeStore.darkMode);

function toggleDarkMode() {
  const next = !darkModeStore.darkMode;
  darkModeStore.setDarkMode(next);

  const el = document.documentElement;
  next ? el.classList.add("dark") : el.classList.remove("dark");
}

const navGroups = [
  {
    title: "Foundations",
    items: [
      { id: "colors", label: "Colors" },
      { id: "typography", label: "Typography" },
      { id: "spacing", label: "Spacing, Radius & Shadow" },
      { id: "icons", label: "Icons" },
    ],
  },
  {
    title: "Components",
    items: [
      { id: "buttons", label: "Button" },
      { id: "badges", label: "Badge" },
      { id: "forms", label: "Form Components" },
      { id: "feedback", label: "Feedback" },
      { id: "data-display", label: "Data Display" },
    ],
  },
  {
    title: "States",
    items: [{ id: "states", label: "States" }],
  },
];

function scrollTo(id: string) {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
}
</script>

<template>
  <div class="space-y-4 p-4">
    <PageHeader title="Design System"
      description="Referensi visual komponen & design token yang benar-benar dipakai aplikasi ini." variant="flat">
      <template #action>
        <Button variant="outline-secondary" class="inline-flex items-center gap-2" @click="toggleDarkMode">
          <Lucide :icon="isDark ? 'Sun' : 'Moon'" class="w-4 h-4" />
          {{ isDark ? "Light Mode" : "Dark Mode" }}
        </Button>
      </template>
    </PageHeader>

    <div class="items-start gap-6 grid grid-cols-1 lg:grid-cols-[220px_1fr]">
      <nav class="top-24 lg:sticky p-4 box">
        <div v-for="group in navGroups" :key="group.title" class="mb-4 last:mb-0">
          <p class="mb-2 text-overline">{{ group.title }}</p>
          <ul class="space-y-1">
            <li v-for="item in group.items" :key="item.id">
              <button type="button" class="hover:bg-slate-50 px-2 py-1 rounded-md w-full text-body text-left"
                @click="scrollTo(item.id)">
                {{ item.label }}
              </button>
            </li>
          </ul>
        </div>
      </nav>

      <div class="space-y-6">
        <Colors />
        <Typography />
        <Spacing />
        <Icons />
        <Buttons />
        <Badges />
        <Forms />
        <Feedback />
        <DataDisplay />
        <States />
      </div>
    </div>
  </div>
</template>
