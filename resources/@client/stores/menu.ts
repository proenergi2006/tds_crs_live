// src/stores/menu.ts
import { defineStore } from "pinia";
import { type Icon } from "@/components/Base/Lucide/Lucide.vue";
import { type Themes } from "@/stores/theme";
import topMenu from "@/main/top-menu";
import simpleMenu from "@/main/simple-menu";
import sideMenu from "@/main/side-menu";
import { useAuthStore } from "@/stores/auth";
import { canAccessMenu } from "@/stores/roleMenuMapping";
import router from "@/router";

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

const routeByName = () =>
  new Map(router.getRoutes().map((route) => [String(route.name), route]));

const canAccessRoute = (roleId: number | undefined, pageName?: string) => {
  if (!pageName) return true;

  const route = routeByName().get(pageName);
  const allowedRoles = route?.meta.roles;

  if (!Array.isArray(allowedRoles) || !allowedRoles.length) return true;

  return allowedRoles.includes(roleId ?? -1);
};

const filterMenuItemByRole = (item: Menu, roleId: number | undefined): Menu | null => {
  if (!canAccessRoute(roleId, item.pageName)) return null;

  if (!item.subMenu?.length) return item;

  const subMenu = item.subMenu
    .map((subItem) => filterMenuItemByRole(subItem, roleId))
    .filter((subItem): subItem is Menu => subItem !== null);

  if (!subMenu.length && !item.pageName) return null;

  return {
    ...item,
    subMenu,
  };
};

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
      return sideMenu
        .map((item) => {
          if (item === "divider") return item;

          if (!canAccessMenu(roleId, item.title)) return null;

          return filterMenuItemByRole(item, roleId);
        })
        .filter((item): item is Menu | "divider" => item !== null);
    },
  },
});
