import { type Router, type RouteLocationNormalizedLoaded } from "vue-router";
import { type Menu } from "@/stores/menu";
import { slideUp, slideDown } from "@/utils/helper";

export interface Route extends RouteLocationNormalizedLoaded {
  forceActiveMenu?: string;
}

export interface FormattedMenu extends Menu {
  active?: boolean;
  activeDropdown?: boolean;
  subMenu?: FormattedMenu[];
}

export type ProvideForceActiveMenu = (pageName: string) => void;

const forceActiveMenu = (route: Route, pageName: string) => {
  route.forceActiveMenu = pageName;
};

const isActiveMenuItem = (item: Menu, route: Route): boolean => {
  const activeRouteName = String(route.forceActiveMenu ?? route.name ?? "");

  return item.pageName === activeRouteName || !!item.activePageNames?.includes(activeRouteName);
};

// Setup side menu
const findActiveMenu = (subMenu: Menu[], route: Route): boolean => {
  let match = false;
  subMenu.forEach((item) => {
    if (isActiveMenuItem(item, route) && !item.ignore) {
      match = true;
    } else if (!match && item.subMenu) {
      match = findActiveMenu(item.subMenu, route);
    }
  });
  return match;
};

const nestedMenu = (menu: Array<Menu | "divider">, route: Route) => {
  const formattedMenu: Array<FormattedMenu | "divider"> = [];
  menu.forEach((item) => {
    if (typeof item !== "string") {
      const menuItem: FormattedMenu = {
        icon: item.icon,
        title: item.title,
        pageName: item.pageName,
        activePageNames: item.activePageNames,
        subMenu: item.subMenu,
        ignore: item.ignore,
        badgeKey: item.badgeKey,
      };
      menuItem.active =
        (isActiveMenuItem(menuItem, route) ||
          (menuItem.subMenu && findActiveMenu(menuItem.subMenu, route))) &&
        !menuItem.ignore;

      if (menuItem.subMenu) {
        menuItem.activeDropdown = findActiveMenu(menuItem.subMenu, route);

        // Nested menu
        const subMenu: Array<FormattedMenu> = [];
        nestedMenu(menuItem.subMenu, route).map(
          (menu) => typeof menu !== "string" && subMenu.push(menu)
        );
        menuItem.subMenu = subMenu;
      }

      formattedMenu.push(menuItem);
    } else {
      formattedMenu.push(item);
    }
  });

  return formattedMenu;
};

const linkTo = (menu: FormattedMenu, router: Router) => {
  if (menu.subMenu) {
    menu.activeDropdown = !menu.activeDropdown;
  } else {
    if (menu.pageName !== undefined) {
      router.push({
        name: menu.pageName,
      });
    }
  }
};

const enter = (el: Element) => {
  slideDown(el as HTMLElement, 300);
};

const leave = (el: Element) => {
  slideUp(el as HTMLElement, 300);
};

export { nestedMenu, linkTo, enter, leave, forceActiveMenu };
