import type { NavItem } from './types'

export const customerNavigation: NavItem[] = [
  {
    icon: 'Users',
    title: 'Customer & Penawaran',
    subMenu: [
      {
        icon: 'User',
        pageName: 'customers-list',
        title: 'Customer',
        activePageNames: ['customers-create', 'customers-edit'],
        permission: 'penawaran.tds.manage',
      },
      {
        icon: 'User',
        pageName: 'customers-list-proenergi',
        title: 'Customer (Proenergi)',
        activePageNames: ['customers-create-proenergi', 'customers-edit-proenergi'],
        permission: 'penawaran.proenergi.manage',
      },
      {
        icon: 'File',
        pageName: 'penawarans-list',
        title: 'Penawaran',
        activePageNames: ['penawarans-create', 'penawarans-edit', 'penawarans-detail'],
        permission: 'penawaran.tds.manage',
      },
      {
        icon: 'File',
        pageName: 'penawarans-list-proenergi',
        title: 'Penawaran (Proenergi)',
        activePageNames: [
          'penawarans-create-proenergi',
          'penawarans-edit-proenergi',
          'penawarans-detail-proenergi',
        ],
        permission: 'penawaran.proenergi.manage',
      },
      {
        icon: 'ClipboardCheck',
        pageName: 'sales-confirmations',
        title: 'Sales Confirmation',
        permission: 'sales-confirmation.manage',
      },
    ],
  },
]
