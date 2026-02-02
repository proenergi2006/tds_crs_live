<script setup lang="ts">
import { Slideover } from "@/components/Base/Headless";
import Lucide from "@/components/Base/Lucide";
import { useThemeStore, type Themes } from "@/stores/theme";
import { useColorSchemeStore, type ColorSchemes } from "@/stores/color-scheme";
import { useDarkModeStore } from "@/stores/dark-mode";
import { ref } from "vue";

const themeSwitcherSlideover = ref(false);
const setThemeSwitcherSlideover = (value: boolean) => {
  themeSwitcherSlideover.value = value;
};

// ====== HIDDEN OPTIONS ======
const HIDDEN_THEMES: Array<Themes["name"]> = ["icewall", "enigma"];
const HIDDEN_LAYOUTS: Array<Themes["layout"]> = ["top-menu"];

// ====== ONLY GREEN ======
const ALLOWED_COLOR_SCHEMES = ["theme-1"] as const; // <- HIJAU

// ====== ALL OPTIONS ======
const ALL_THEMES: Array<Themes["name"]> = ["rubick", "icewall", "tinker", "enigma"];
const ALL_LAYOUTS: Array<Themes["layout"]> = ["side-menu", "simple-menu", "top-menu"];

// ====== FILTERED (UI) ======
const themes: Array<Themes["name"]> = ALL_THEMES.filter(
  (t): t is Themes["name"] => !HIDDEN_THEMES.includes(t)
);
const layouts: Array<Themes["layout"]> = ALL_LAYOUTS.filter(
  (l): l is Themes["layout"] => !HIDDEN_LAYOUTS.includes(l)
);

// ====== STORES ======
const themeStore = useThemeStore();
const colorSchemeStore = useColorSchemeStore();
const darkModeStore = useDarkModeStore();

// ====== SWITCH (guard) ======
const switchTheme = (theme: Themes["name"]) => {
  if (HIDDEN_THEMES.includes(theme)) return;
  useThemeStore().setTheme(theme);
};
const switchLayout = (layout: Themes["layout"]) => {
  if (HIDDEN_LAYOUTS.includes(layout)) return;
  useThemeStore().setLayout(layout);
};

// ====== COLOR SCHEME ======
const SCHEME_CLASSES = ["default", "theme-1", "theme-2", "theme-3", "theme-4"];

const setColorSchemeClass = () => {
  const el = document.documentElement; // <html>
  // buang semua kelas skema dulu
  SCHEME_CLASSES.forEach((c) => el.classList.remove(c));
  // pasang skema terpilih
  el.classList.add(useColorSchemeStore().colorSchemeValue);
  // pertahankan dark mode jika aktif
  useDarkModeStore().darkModeValue && el.classList.add("dark");
};

const switchColorScheme = (colorScheme: ColorSchemes) => {
  if (!ALLOWED_COLOR_SCHEMES.includes(colorScheme as any)) return;
  useColorSchemeStore().setColorScheme(colorScheme);
  setColorSchemeClass();
};
setColorSchemeClass();

// ====== DARK MODE ======
const setDarkModeClass = () => {
  const el = document.documentElement;
  useDarkModeStore().darkModeValue
    ? el.classList.add("dark")
    : el.classList.remove("dark");
};
const switchDarkMode = (darkMode: boolean) => {
  useDarkModeStore().setDarkMode(darkMode);
  setDarkModeClass();
};
setDarkModeClass();

// ====== OPTIONS ======
const colorSchemes: Array<ColorSchemes> =
  [...ALLOWED_COLOR_SCHEMES] as unknown as Array<ColorSchemes>;

// ====== ASSET IMPORTS ======
const themeImages = import.meta.glob<{ default: string }>(
  "/resources/@client/assets/images/themes/*.{jpg,jpeg,png,svg}",
  { eager: true }
);
const layoutImages = import.meta.glob<{ default: string }>(
  "/resources/@client/assets/images/layouts/*.{jpg,jpeg,png,svg}",
  { eager: true }
);

// ====== ENFORCE CURRENT STATE ======
const ensureAllowedThemeLayoutColor = () => {
  const currentTheme = themeStore.theme.name as Themes["name"];
  const currentLayout = themeStore.theme.layout as Themes["layout"];
  const currentScheme = colorSchemeStore.colorSchemeValue as ColorSchemes;

  if (HIDDEN_THEMES.includes(currentTheme)) {
    useThemeStore().setTheme("rubick");
  }
  if (HIDDEN_LAYOUTS.includes(currentLayout)) {
    useThemeStore().setLayout("side-menu");
  }
  if (!ALLOWED_COLOR_SCHEMES.includes(currentScheme as any)) {
    useColorSchemeStore().setColorScheme(ALLOWED_COLOR_SCHEMES[0] as any); // theme-1 (hijau)
    setColorSchemeClass();
  }
};
ensureAllowedThemeLayoutColor();
</script>

<template>
  <div>
    <Slideover
      :open="themeSwitcherSlideover"
      @close="() => { setThemeSwitcherSlideover(false); }"
    >
      <Slideover.Panel>
        <a
          class="absolute inset-y-0 left-0 right-auto my-auto -ml-[60px] flex h-8 w-8 items-center justify-center rounded-full border border-white/90 bg-white/5 text-white/90 transition-all hover:rotate-180 hover:scale-105 hover:bg-white/10 focus:outline-none sm:-ml-[105px] sm:h-14 sm:w-14"
          @click="(e: MouseEvent) => { e.preventDefault(); setThemeSwitcherSlideover(false); }"
        >
          <Lucide class="h-3 w-3 stroke-[1] sm:h-8 sm:w-8" icon="X" />
        </a>

        <Slideover.Description class="p-0">
          <div class="flex flex-col">
            <!-- THEMES -->
            <div class="px-8 pt-6 pb-8">
              <div class="text-base font-medium">Themes</div>
              <div class="mt-0.5 text-slate-500">Choose your theme</div>
              <div class="mt-5 grid grid-cols-2 gap-x-5 gap-y-3.5">
                <div v-for="theme in themes" :key="theme">
                  <a
                    @click="(e: MouseEvent) => { e.preventDefault(); switchTheme(theme); }"
                    :class="[
                      'h-28 cursor-pointer bg-slate-50 box p-1 block',
                      themeStore.theme.name == theme ? 'border-2 border-theme-1/60' : '',
                    ]"
                  >
                    <div class="w-full h-full overflow-hidden rounded-md image-fit">
                      <img
                        class="w-full h-full"
                        :src="themeImages['/resources/@client/assets/images/themes/' + theme + '.png'].default"
                        alt="Midone - Admin Dashboard Template"
                      />
                    </div>
                  </a>
                  <div class="mt-2.5 text-center text-xs capitalize">{{ theme }}</div>
                </div>
              </div>
            </div>

            <div class="border-b border-dashed"></div>

            <!-- LAYOUTS -->
            <div class="px-8 pt-6 pb-8">
              <div class="text-base font-medium">Layouts</div>
              <div class="mt-0.5 text-slate-500">Choose your layout</div>
              <div class="mt-5 grid grid-cols-3 gap-x-5 gap-y-3.5">
                <div v-for="layout in layouts" :key="layout">
                  <a
                    @click="(e: MouseEvent) => { e.preventDefault(); switchLayout(layout); }"
                    :class="[
                      'h-24 cursor-pointer bg-slate-50 box p-1 block',
                      themeStore.theme.layout == layout ? 'border-2 border-theme-1/60' : '',
                    ]"
                  >
                    <div class="w-full h-full overflow-hidden rounded-md">
                      <img
                        class="w-full h-full"
                        :src="layoutImages['/resources/@client/assets/images/layouts/' + layout + '.png'].default"
                        alt="Midone - Admin Dashboard Template"
                      />
                    </div>
                  </a>
                  <div class="mt-2.5 text-center text-xs capitalize">
                    {{ layout.replace('-', ' ') }}
                  </div>
                </div>
              </div>
            </div>

            <div class="border-b border-dashed"></div>

            <!-- ACCENT COLORS: only theme-1 (green) -->
            <div class="px-8 pt-6 pb-8">
              <div class="text-base font-medium">Accent Colors</div>
              <div class="mt-0.5 text-slate-500">Choose your accent color</div>
              <div class="mt-5 grid grid-cols-1 gap-3.5">
                <div v-for="colorScheme in colorSchemes" :key="colorScheme">
                  <a
                    @click="(e: MouseEvent) => { e.preventDefault(); switchColorScheme(colorScheme); }"
                    :class="[
                      'h-14 cursor-pointer bg-slate-50 box p-1 border-slate-300/80 block',
                      '[&.active]:border-2 [&.active]:border-theme-1/60',
                      colorSchemeStore.colorSchemeValue == colorScheme ? 'active' : '',
                    ]"
                  >
                    <div class="h-full overflow-hidden rounded-md">
                      <div class="flex items-center h-full gap-1 -mx-2">
                        <div :class="['w-1/2 h-[200%] bg-theme-1 rotate-12', colorScheme]"></div>
                        <div :class="['w-1/2 h-[200%] bg-theme-2 rotate-12', colorScheme]"></div>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
              <div class="mt-2 text-xs text-slate-500">
                Accent lain dinonaktifkan untuk konsistensi brand.
              </div>
            </div>

            <div class="border-b border-dashed"></div>

            <!-- APPEARANCE -->
            <div class="px-8 pt-6 pb-8">
              <div class="text-base font-medium">Appearance</div>
              <div class="mt-0.5 text-slate-500">Choose your appearance</div>
              <div class="mt-5 grid grid-cols-2 gap-3.5">
                <div>
                  <a
                    @click="(e: MouseEvent) => { e.preventDefault(); switchDarkMode(false); }"
                    :class="[
                      'h-12 cursor-pointer bg-slate-50 box p-1 border-slate-300/80 block',
                      '[&.active]:border-2 [&.active]:border-theme-1/60',
                      !darkModeStore.darkModeValue ? 'active' : '',
                    ]"
                  >
                    <div class="h-full overflow-hidden rounded-md bg-slate-200"></div>
                  </a>
                  <div class="mt-2.5 text-center text-xs capitalize">Light</div>
                </div>
                <div>
                  <a
                    @click="(e: MouseEvent) => { e.preventDefault(); switchDarkMode(true); }"
                    :class="[
                      'h-12 cursor-pointer bg-slate-50 box p-1 border-slate-300/80 block',
                      '[&.active]:border-2 [&.active]:border-theme-1/60',
                      darkModeStore.darkModeValue ? 'active' : '',
                    ]"
                  >
                    <div class="h-full overflow-hidden rounded-md bg-slate-900"></div>
                  </a>
                  <div class="mt-2.5 text-center text-xs capitalize">Dark</div>
                </div>
              </div>
            </div>
          </div>
        </Slideover.Description>
      </Slideover.Panel>
    </Slideover>

    <!-- FLOATING BUTTON -->
    <div
      class="fixed bottom-0 right-0 z-50 flex items-center justify-center mb-5 mr-5 text-white rounded-full shadow-lg cursor-pointer h-14 w-14 bg-theme-1"
      @click="(e: MouseEvent) => { e.preventDefault(); setThemeSwitcherSlideover(true); }"
    >
      <Lucide class="w-5 h-5 animate-spin" icon="Settings" />
    </div>
  </div>
</template>
