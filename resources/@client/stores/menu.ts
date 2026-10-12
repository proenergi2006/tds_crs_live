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

export interface NavChild {
  icon: Icon;
  title: string;
  pageName: string;
  activePageNames?: string[];
  permission?: string | string[];
  roles?: number[];
  excludeRoles?: number[];
  badgeKey?: string;
}

export interface NavItem extends Omit<NavChild, "pageName"> {
  pageName?: string;
  children?: NavChild[];
}

export interface NavSection {
  key: string;
  title: string;
  icon: Icon;
  badgeKey?: string;
  items: NavItem[];
}

export interface VisibleNavSection extends NavSection {
  singleDestination: boolean;
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

const isSingleDestination = (section: NavSection): boolean =>
  section.items.length === 1 && !!section.items[0].pageName && !section.items[0].children?.length;

export const isSingleDestinationSection = (section: VisibleNavSection): boolean => section.singleDestination;

const navNodeToMenu = (node: NavItem | NavChild): Menu => {
  const { children, ...fields } = node as NavItem;
  const menu: Menu = { ...fields };

  if (children?.length) menu.subMenu = children.map(navNodeToMenu);

  return menu;
};

const menuToNavItem = (menu: Menu): NavItem => {
  const { subMenu, ...fields } = menu;
  const item: NavItem = { ...fields };

  if (subMenu?.length) item.children = subMenu as NavChild[];

  return item;
};

const filterNavItem = (
  item: NavItem,
  canFn: (permission: string) => boolean,
  hasRoleFn: (roleId: number) => boolean,
): NavItem | null => {
  const filtered = filterMenuItem(navNodeToMenu(item), canFn, hasRoleFn);

  if (filtered) return menuToNavItem(filtered);

  if (item.pageName && item.children?.length) {
    const leaf = filterMenuItem({ ...navNodeToMenu(item), subMenu: undefined }, canFn, hasRoleFn);

    if (leaf) return menuToNavItem(leaf);
  }

  return null;
};

const filterSection = (
  section: NavSection,
  canFn: (permission: string) => boolean,
  hasRoleFn: (roleId: number) => boolean,
): VisibleNavSection | null => {
  const items = section.items
    .map((item) => filterNavItem(item, canFn, hasRoleFn))
    .filter((item): item is NavItem => item !== null);

  if (!items.length) return null;

  return { ...section, items, singleDestination: isSingleDestination(section) };
};

const resolveVisibleSections = (
  canFn: (permission: string) => boolean,
  hasRoleFn: (roleId: number) => boolean,
): VisibleNavSection[] =>
  navigation
    .map((section) => filterSection(section, canFn, hasRoleFn))
    .filter((section): section is VisibleNavSection => section !== null);

const navItemToLegacyMenu = (item: NavItem): Menu => {
  if (!item.pageName || !item.children?.length) return navNodeToMenu(item);

  const { pageName, activePageNames, children, ...groupFields } = item;

  return {
    ...groupFields,
    subMenu: [
      { icon: "CornerDownRight", title: item.title, pageName, activePageNames, permission: item.permission },
      ...children.map(navNodeToMenu),
    ],
  };
};

const sectionsToLegacyMenu = (sections: VisibleNavSection[]): Menu[] =>
  sections.flatMap((section): Menu[] => {
    if (isSingleDestinationSection(section)) return [navItemToLegacyMenu(section.items[0])];

    const group: Menu = {
      icon: section.icon,
      title: section.title,
      subMenu: section.items.map(navItemToLegacyMenu),
    };

    if (section.badgeKey) group.badgeKey = section.badgeKey;

    return [group];
  });

export const useMenuStore = defineStore("menu", {
  state: (): MenuState => ({
    menuValue: [],
  }),
  getters: {
    sections: (): VisibleNavSection[] => {
      const auth = useAuthStore();

      return resolveVisibleSections(auth.can, auth.hasRole);
    },
    menu: (state) => (layout: Themes["layout"]): Array<Menu | "divider"> => {
      const auth = useAuthStore();

      if (layout === "top-menu") return topMenu;
      if (layout === "simple-menu") return simpleMenu;

      return sectionsToLegacyMenu(resolveVisibleSections(auth.can, auth.hasRole));
    },
  },
});
