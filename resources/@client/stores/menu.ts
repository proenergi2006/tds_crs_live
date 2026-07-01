// src/stores/menu.ts
import { defineStore } from "pinia";
import { type Icon } from "@/components/Base/Lucide/Lucide.vue";
import { type Themes } from "@/stores/theme";
import topMenu from "@/main/top-menu";
import simpleMenu from "@/main/simple-menu";
import navigation from "@/main/navigation";
import { useAuthStore } from "@/stores/auth";

export interface Menu {
  icon: Icon;
  title: string;
  pageName?: string;
  activePageNames?: string[];
  subMenu?: Menu[];
  ignore?: boolean;
  permission?: string;
  badgeKey?: string;
  badge?: {
    counter: "pendingCfo" | "pendingCeo";
    role?: number; // kalau diisi, badge hanya tampil untuk role ini
  };
}

export interface MenuState {
  menuValue: Array<Menu | "divider">;
}

/**
 * Filter satu item navigasi secara rekursif berdasarkan permission.
 *
 * Aturan:
 *   - Leaf item tanpa `permission`  → selalu tampil (universal: Dashboard, Profile)
 *   - Leaf item dengan `permission` → tampil jika auth.can(permission)
 *   - Grup tanpa `permission`       → tampil jika ≥ 1 child lolos filter
 *   - Grup dengan `permission`      → tampil jika auth.can(permission) && ≥ 1 child lolos
 */
const filterByPermission = (
  item: Menu,
  canFn: (permission: string) => boolean,
): Menu | null => {
  if (item.subMenu?.length) {
    const filteredSub = item.subMenu
      .map((sub) => filterByPermission(sub, canFn))
      .filter((sub): sub is Menu => sub !== null);

    if (!filteredSub.length) return null;

    if (item.permission && !canFn(item.permission)) return null;

    return { ...item, subMenu: filteredSub };
  }

  // Leaf item
  if (item.permission) {
    return canFn(item.permission) ? item : null;
  }

  return item; // tanpa permission → universal, selalu tampil
};

export const useMenuStore = defineStore("menu", {
  state: (): MenuState => ({
    menuValue: [],
  }),
  getters: {
    menu: (state) => (layout: Themes["layout"]) => {
      const auth = useAuthStore();

      if (layout === "top-menu") return topMenu;
      if (layout === "simple-menu") return simpleMenu;

      return (navigation as Menu[])
        .map((item) => filterByPermission(item, (p) => auth.can(p)))
        .filter((item): item is Menu => item !== null);
    },
  },
});
