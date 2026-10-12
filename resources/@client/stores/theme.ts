import { defineStore } from "pinia";
import Custom1SideMenu from "@/themes/Custom1/SideMenu";
import RubickSideMenu from "@/themes/Rubick/SideMenu";
import RubickSimpleMenu from "@/themes/Rubick/SimpleMenu";
import RubickTopMenu from "@/themes/Rubick/TopMenu";
import IcewallSideMenu from "@/themes/Icewall/SideMenu";
import IcewallSimpleMenu from "@/themes/Icewall/SimpleMenu";
import IcewallTopMenu from "@/themes/Icewall/TopMenu";
import TinkerSideMenu from "@/themes/Tinker/SideMenu";
import TinkerSimpleMenu from "@/themes/Tinker/SimpleMenu";
import TinkerTopMenu from "@/themes/Tinker/TopMenu";
import EnigmaSideMenu from "@/themes/Enigma/SideMenu";
import EnigmaSimpleMenu from "@/themes/Enigma/SimpleMenu";
import EnigmaTopMenu from "@/themes/Enigma/TopMenu";

export const themes = [
  {
    name: "rubick",
    layout: "side-menu",
    component: RubickSideMenu,
  },
  {
    name: "rubick",
    layout: "simple-menu",
    component: RubickSimpleMenu,
  },
  {
    name: "rubick",
    layout: "top-menu",
    component: RubickTopMenu,
  },
  {
    name: "custom-1",
    layout: "side-menu",
    component: Custom1SideMenu,
  },
  {
    name: "icewall",
    layout: "side-menu",
    component: IcewallSideMenu,
  },
  {
    name: "icewall",
    layout: "simple-menu",
    component: IcewallSimpleMenu,
  },
  {
    name: "icewall",
    layout: "top-menu",
    component: IcewallTopMenu,
  },
  {
    name: "tinker",
    layout: "side-menu",
    component: TinkerSideMenu,
  },
  {
    name: "tinker",
    layout: "simple-menu",
    component: TinkerSimpleMenu,
  },
  {
    name: "tinker",
    layout: "top-menu",
    component: TinkerTopMenu,
  },
  {
    name: "enigma",
    layout: "side-menu",
    component: EnigmaSideMenu,
  },
  {
    name: "enigma",
    layout: "simple-menu",
    component: EnigmaSimpleMenu,
  },
  {
    name: "enigma",
    layout: "top-menu",
    component: EnigmaTopMenu,
  },
] as const;

export type Themes = (typeof themes)[number];

export const THEME_OVERRIDE: Themes["name"] | null = "custom-1";

export const isThemeAllowed = (name: string | null | undefined): boolean =>
  name !== "custom-1" || import.meta.env.DEV || THEME_OVERRIDE === "custom-1";

interface ThemeState {
  themeValue: {
    name: Themes["name"];
    layout: Themes["layout"];
  };
}

export const getTheme = (search?: {
  name: Themes["name"];
  layout: Themes["layout"];
}) => {
  const requested =
    search === undefined
      ? {
          name: localStorage.getItem("theme"),
          layout: localStorage.getItem("layout"),
        }
      : search;
  const searchValues = {
    name: THEME_OVERRIDE ?? (isThemeAllowed(requested.name) ? requested.name : "rubick"),
    layout: requested.layout,
  };
  return (
    themes.find(
      (item) =>
        item.name === searchValues.name && item.layout === searchValues.layout,
    ) ||
    themes.find((item) => item.name === searchValues.name) ||
    themes[0]
  );
};

export const useThemeStore = defineStore("theme", {
  state: (): ThemeState => ({
    themeValue: {
      name:
        localStorage.getItem("theme") === null
          ? themes[0].name
          : getTheme().name,
      layout:
        localStorage.getItem("layout") === null
          ? themes[0].layout
          : getTheme().layout,
    },
  }),
  getters: {
    theme(state) {
      if (localStorage.getItem("theme") === null) {
        localStorage.setItem("theme", "rubick");
      }

      if (localStorage.getItem("layout") === null) {
        localStorage.setItem("layout", "side-menu");
      }

      const resolved = getTheme(state.themeValue);

      return { name: resolved.name, layout: resolved.layout };
    },
  },
  actions: {
    setTheme(theme: Themes["name"]) {
      this.themeValue = {
        name: theme,
        layout: this.themeValue.layout,
      };

      localStorage.setItem("theme", theme);
    },
    setLayout(layout: Themes["layout"]) {
      this.themeValue = {
        name: this.themeValue.name,
        layout: layout,
      };

      localStorage.setItem("layout", layout);
    },
  },
});
