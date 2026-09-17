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
  permission?: string | string[];
  badgeKey?: string;
  roles?: number[];
  excludeRoles?: number[];
}

export interface MenuState {
  menuValue: Array<Menu | "divider">;
}

const hasPermission = (permission: string | string[], canFn: (permission: string) => boolean): boolean =>
  Array.isArray(permission) ? permission.some(canFn) : canFn(permission);

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

    if (item.permission && !hasPermission(item.permission, canFn)) return null;

    return { ...item, subMenu: filteredSub };
  }

  if (item.permission) {
    return hasPermission(item.permission, canFn) ? item : null;
  }

  return item;
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
