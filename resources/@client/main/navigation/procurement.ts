import type { NavItem } from './types'

export const procurementNavigation: NavItem[] = [
  {
    icon: 'Receipt',
    title: 'Procurement',
    subMenu: [
      {
        icon: 'Inbox',
        pageName: 'vendor-pos-list',
        title: 'PO Supplier',
        activePageNames: [
          'vendor-pos-create',
          'vendor-pos-edit',
          'vendor-pos-detail',
          'vendor-pos-receive',
        ],
        permission: 'po-supplier.manage',
      },
      {
        icon: 'FileCheck',
        pageName: 'good-receipt-list',
        title: 'Good Receipts',
        permission: 'good-receipt.manage',
      },
    ],
  },
  {
    icon: 'Boxes',
    pageName: 'StockInventory',
    title: 'Inventory',
    permission: 'inventory.view',
  },
]
