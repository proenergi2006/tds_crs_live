import type { NavItem } from './types'

export const logistikNavigation: NavItem[] = [
  {
    icon: 'Truck',
    title: 'Logistik',
    subMenu: [
      {
        icon: 'MapPin',
        pageName: 'logistik-lcrs',
        title: 'Verifikasi LCR',
        permission: 'logistik.lcr.verify',
      },
      {
        icon: 'ClipboardList',
        pageName: 'logistics-delivery-plan',
        title: 'Delivery Plan',
        permission: 'logistik.delivery-plan.view',
      },
      {
        icon: 'Database',
        title: 'Master Logistik',
        // Grup parent tanpa permission — filter rekursif di stores/menu.ts
        // akan menampilkan grup ini jika user punya akses ke minimal satu child.
        subMenu: [
          { icon: 'Users',   pageName: 'transportir-list',    title: 'Transportir',   permission: 'logistik.master.manage' },
          { icon: 'User',    pageName: 'personnel-list',       title: 'Personnel',     permission: 'logistik.master.manage' },
          { icon: 'Package', pageName: 'volumes-list',         title: 'Volume',        permission: 'logistik.master.manage' },
          { icon: 'Pin',     pageName: 'wilayah-angkut-list',  title: 'Wilayah Angkut',permission: 'logistik.master.manage' },
          { icon: 'Ship',    pageName: 'kapals-list',          title: 'Master Kapal',  permission: 'logistik.master.manage' },
          { icon: 'Ship',    pageName: 'ongkos-kapal-list',    title: 'OA Kapal',      permission: 'logistik.master.manage' },
          { icon: 'Truck',   pageName: 'trucks-list',          title: 'Master Truck',  permission: 'logistik.master.manage' },
          { icon: 'Truck',   pageName: 'oa-trucks-list',       title: 'OA Truck',      permission: 'logistik.master.manage' },
        ],
      },
    ],
  },
]
