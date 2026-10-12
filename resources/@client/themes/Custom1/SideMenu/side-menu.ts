import { type NavItem, type NavSection, type VisibleNavSection } from "@/stores/menu";
import { slideUp, slideDown } from "@/utils/helper";

export const GRAND_TOTAL_BADGE_KEY = "Verifikasi";

export interface ActivePath {
  itemKey?: string;
}

export interface NavSearchEntry {
  pageName: string;
  title: string;
  sectionKey: string;
  sectionTitle: string;
  parentTitle?: string;
  parentKey?: string;
}

export interface RailEntry {
  item: NavItem;
  sectionKey: string;
  sectionTitle: string;
}

export interface MenuBadgeCounts {
  total: number;
  breakdown: Record<string, number>;
}

export const isNodeCurrent = (
  node: { pageName?: string; activePageNames?: string[] },
  routeName: string,
): boolean => node.pageName === routeName || !!node.activePageNames?.includes(routeName);

export const itemContainsCurrent = (item: NavItem, routeName: string): boolean =>
  !!item.children?.some((child) => isNodeCurrent(child, routeName));

export const navItemKey = (sectionKey: string, item: Pick<NavItem, "pageName" | "title">): string =>
  `${sectionKey}/${item.pageName ?? item.title}`;

export const resolveActivePath = (sections: NavSection[], routeName: string): ActivePath | null => {
  for (const section of sections) {
    for (const item of section.items) {
      if (itemContainsCurrent(item, routeName)) {
        return { itemKey: navItemKey(section.key, item) };
      }

      if (isNodeCurrent(item, routeName)) {
        return {
          itemKey: item.children?.length ? navItemKey(section.key, item) : undefined,
        };
      }
    }
  }

  return null;
};

export const buildRailEntries = (sections: NavSection[]): RailEntry[] =>
  sections.flatMap((section) =>
    section.items.map((item) => ({ item, sectionKey: section.key, sectionTitle: section.title })),
  );

export const unionKeys = (current: Set<string>, keys: Array<string | undefined>): Set<string> => {
  const next = new Set(current);

  keys.forEach((key) => {
    if (key !== undefined) next.add(key);
  });

  return next;
};

export const toggleKey = (current: Set<string>, key: string): Set<string> => {
  const next = new Set(current);

  if (next.has(key)) next.delete(key);
  else next.add(key);

  return next;
};

export const buildSearchIndex = (sections: NavSection[]): NavSearchEntry[] => {
  const entries: NavSearchEntry[] = [];

  sections.forEach((section) => {
    section.items.forEach((item) => {
      if (item.pageName) {
        entries.push({
          pageName: item.pageName,
          title: item.title,
          sectionKey: section.key,
          sectionTitle: section.title,
        });
      }

      item.children?.forEach((child) => {
        entries.push({
          pageName: child.pageName,
          title: child.title,
          sectionKey: section.key,
          sectionTitle: section.title,
          parentTitle: item.title,
          parentKey: navItemKey(section.key, item),
        });
      });
    });
  });

  return entries;
};

export const tokenizeQuery = (query: string): string[] =>
  query.trim().toLowerCase().split(/\s+/).filter(Boolean);

const matchesAllTokens = (parts: Array<string | undefined>, tokens: string[]): boolean => {
  const haystack = parts.filter(Boolean).join(" ").toLowerCase();

  return tokens.every((token) => haystack.includes(token));
};

export const searchEntries = (index: NavSearchEntry[], query: string): NavSearchEntry[] => {
  const tokens = tokenizeQuery(query);

  if (!tokens.length) return [];

  return index.filter((entry) =>
    matchesAllTokens([entry.sectionTitle, entry.parentTitle, entry.title], tokens),
  );
};

export const filterSectionsByQuery = (
  sections: VisibleNavSection[],
  query: string,
): VisibleNavSection[] => {
  const tokens = tokenizeQuery(query);

  if (!tokens.length) return sections;

  const result: VisibleNavSection[] = [];

  sections.forEach((section) => {
    const items: NavItem[] = [];

    section.items.forEach((item) => {
      const selfMatches = !!item.pageName && matchesAllTokens([section.title, item.title], tokens);
      const children = (item.children ?? []).filter((child) =>
        matchesAllTokens([section.title, item.title, child.title], tokens),
      );

      if (!selfMatches && !children.length) return;

      const { children: _children, ...fields } = item;
      const kept: NavItem = { ...fields };

      if (children.length) kept.children = children;

      items.push(kept);
    });

    if (items.length) result.push({ ...section, items });
  });

  return result;
};

export const getMenuBadge = (badgeKey: string | undefined, counts: MenuBadgeCounts): number => {
  if (!badgeKey) return 0;
  if (badgeKey === GRAND_TOTAL_BADGE_KEY) return counts.total;

  return counts.breakdown[badgeKey] ?? 0;
};

const enter = (el: Element) => {
  slideDown(el as HTMLElement, 300);
};

const leave = (el: Element) => {
  slideUp(el as HTMLElement, 300);
};

export { enter, leave };
