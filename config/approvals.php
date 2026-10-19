<?php

return [
    'providers' => [
        \App\Support\Approvals\VendorPoPendingApproval::class,
        \App\Support\Approvals\QuotationPendingApproval::class,
        \App\Support\Approvals\PricePeriodPendingApproval::class,
    ],

    // Approver yang dipakai wajib punya permission sales-confirmation.approve (lihat PermissionSeeder).
    'sales_confirmation' => [
        'tds_crushed_stone' => ['bm'],
        'tds_polimer'       => ['bm_polimer'],
        'proenergi'         => ['bm_proenergi'],
    ],
];
