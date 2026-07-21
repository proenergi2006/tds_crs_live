import type { NavItem } from './types'

export const verificationNavigation: NavItem[] = [
  {
    icon: 'CheckCircle',
    title: 'Verifikasi',
    badgeKey: 'Verifikasi',
    subMenu: [
      {
        icon: 'CheckSquare',
        pageName: 'po-verification-list',
        title: 'PO Supplier',
        permission: 'po-supplier.verify',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi',
        title: 'Penawaran (BM)',
        permission: 'penawaran.verify-bm',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-om',
        title: 'Penawaran (OM)',
        activePageNames: ['penawarans-verifikasi-om-detail'],
        permission: 'penawaran.verify-om',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-proenergi',
        title: 'Penawaran (Proenergi BM)',
        permission: 'penawaran.proenergi.verify-bm',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-om-proenergi',
        title: 'Penawaran (Proenergi OM)',
        permission: 'penawaran.proenergi.verify-om',
      },
    ],
  },
]
