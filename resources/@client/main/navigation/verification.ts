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
        title: 'Quotation (BM)',
        permission: 'penawaran.verify-bm',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-om',
        title: 'Quotation (OM)',
        activePageNames: ['penawarans-verifikasi-om-detail'],
        permission: 'penawaran.verify-om',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-proenergi',
        title: 'Quotation (Proenergi BM)',
        permission: 'penawaran.proenergi.verify-bm',
      },
      {
        icon: 'File',
        pageName: 'penawarans-verifikasi-om-proenergi',
        title: 'Quotation (Proenergi OM)',
        permission: 'penawaran.proenergi.verify-om',
      },
      {
        icon: 'ClipboardCheck',
        pageName: 'sales-confirmations-bm',
        title: 'Sales Confirmation (BM)',
        activePageNames: ['sales-confirmations-bm-detail', 'sales-confirmations-bm-detail-po'],
        permission: 'sales-confirmation.manage',
      },
      {
        icon: 'File',
        pageName: 'verify-data-customer-bm',
        title: 'Review Data Customer (BM)',
        // Sementara tanpa permission gate (keputusan sadar, lihat
        // .claude/plans/rbac-phase-2-customer-verification-review.md).
        // Route belum punya meta.permission — entry ini akan tampil
        // untuk semua role sampai fase RBAC dedicated menambahkan gate-nya.
      },
      {
        icon: 'File',
        pageName: 'verify-data-customer-om',
        title: 'Review Data Customer (OM)',
        // Sementara tanpa permission gate — lihat catatan di atas.
      },
    ],
  },
]
