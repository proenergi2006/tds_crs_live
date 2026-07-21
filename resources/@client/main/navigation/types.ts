import type { Menu } from '@/stores/menu'

// Ekstensi dari Menu yang menambahkan field `permission` opsional per item.
// NavItem dipakai di seluruh navigation/* untuk sidebar berbasis permission.
// stores/menu.ts akan membaca field ini di prompt berikutnya untuk menggantikan
// filterMenuItemByRole (role-array) dengan filter berbasis auth.can(permission).
export interface NavItem extends Omit<Menu, 'subMenu'> {
  permission?: string
  badgeKey?: string
  subMenu?: NavItem[]
}
