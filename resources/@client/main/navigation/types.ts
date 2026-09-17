import type { Menu } from '@/stores/menu'

export interface NavItem extends Omit<Menu, 'subMenu'> {
  permission?: string | string[]
  badgeKey?: string
  subMenu?: NavItem[]
}
