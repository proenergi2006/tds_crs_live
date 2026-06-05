// src/stores/menu.ts
import { defineStore } from "pinia";
import { type Icon } from "@/components/Base/Lucide/Lucide.vue";
import { type Themes } from "@/stores/theme";
import topMenu from "@/main/top-menu";
import simpleMenu from "@/main/simple-menu";
import sideMenu from "@/main/side-menu";
import { useAuthStore } from "@/stores/auth";
import { canAccessMenu } from "@/stores/roleMenuMapping";

export interface Menu {
  icon: Icon;
  title: string;
  pageName?: string;
  activePageNames?: string[];
  subMenu?: Menu[];
  ignore?: boolean;
  badge?: {
    counter: "pendingCfo" | "pendingCeo";
    role?: number; // kalau diisi, badge hanya tampil untuk role ini
  };
}

export interface MenuState {
  menuValue: Array<Menu | "divider">;
}

export const useMenuStore = defineStore("menu", {
  state: (): MenuState => ({
    menuValue: [],
  }),
  getters: {
    menu: (state) => (layout: Themes["layout"]) => {
      const auth = useAuthStore();
      const roleId = auth.user?.id_role;

      if (layout === "top-menu") {
        return topMenu;
      }
      if (layout === "simple-menu") {
        return simpleMenu;
      }

      // Filter side-menu berdasarkan role permissions
      return sideMenu.filter((item) => {
        if (item === "divider") return true;
        if (typeof item !== "string") {
          return canAccessMenu(roleId, item.title);
        }
        return true;
      });
    },
  },
});
