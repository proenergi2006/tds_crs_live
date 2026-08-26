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
  // roles/excludeRoles cuma soal DI MANA item muncul per role (union assignment, bukan primary_role tunggal), permission tetap yang nentuin KEWENANGAN
  roles?: number[]; // kalau diisi, item CUMA tampil untuk user yang punya salah satu role id di list ini
  excludeRoles?: number[]; // kalau diisi, item disembunyikan untuk user yang punya salah satu role id di list ini
}

export interface MenuState {
  menuValue: Array<Menu | "divider">;
}

// filter navigasi rekursif: roles/excludeRoles dicek duluan sebelum permission, grup tampil kalau ada child yang lolos
const filterMenuItem = (
  item: Menu,
  canFn: (permission: string) => boolean,
  hasRoleFn: (roleId: number) => boolean,
): Menu | null => {
  const roleAllowed =
    (!item.roles || item.roles.some(hasRoleFn)) &&
    (!item.excludeRoles || !item.excludeRoles.some(hasRoleFn));

  if (!roleAllowed) return null;

  if (item.subMenu?.length) {
    const filteredSub = item.subMenu
      .map((sub) => filterMenuItem(sub, canFn, hasRoleFn))
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
        .map((item) => filterMenuItem(item, auth.can, auth.hasRole))
        .filter((item): item is Menu => item !== null);
    },
  },
});
