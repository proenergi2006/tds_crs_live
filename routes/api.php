<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CustomerMigrationController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\MasterData\ApprovalTemplateController;
use App\Http\Controllers\MasterData\CustomerContactTypeController;
use App\Http\Controllers\MasterData\CustomerDocumentTypeController;
use App\Http\Controllers\MasterData\JenisProdukController;
use App\Http\Controllers\MasterData\ProdukController;
use App\Http\Controllers\MasterData\ProdukHargaController;
use App\Http\Controllers\MasterData\SatuanController;
use App\Http\Controllers\MasterData\TerminalController;
use App\Http\Controllers\MasterData\UkuranController;
use App\Http\Controllers\MasterData\VendorController;
use App\Http\Controllers\AttachmentHargaDasarController;
use App\Http\Controllers\MasterData\AddressController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerContactController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerCreditItemController;
use App\Http\Controllers\Customer\CustomerCreditSubmissionController;
use App\Http\Controllers\Customer\CustomerDocumentController;
use App\Http\Controllers\Customer\CustomerOnboardingController;
use App\Http\Controllers\Customer\CustomerVerificationController;
use App\Http\Controllers\VendorPoController;
use App\Http\Controllers\VendorPoProdukController;
use App\Http\Controllers\PoVerificationController;
use App\Http\Controllers\ReceiveItemController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\PenawaranVerificationController;
use App\Http\Controllers\TransportirController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\VolumeController;
use App\Http\Controllers\WilayahAngkutController;
use App\Http\Controllers\MasterKapalController;
use App\Http\Controllers\OngkosKapalController;
use App\Http\Controllers\MasterTruckController;
use App\Http\Controllers\OngkosTruckController;
use App\Http\Controllers\PoCustomerController;
use App\Http\Controllers\SalesConfirmationController;
use App\Http\Controllers\CustomerLcrController;
use App\Http\Controllers\MapsLinkController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DeliveryPlanController;
use App\Http\Controllers\PrController;
use App\Http\Controllers\DeliveryRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovalPendingCountController;
use App\Http\Controllers\Monitoring\LogViewerController;
// Controller Proenergi
use App\Http\Controllers\PenawaranProenergiController;
// End

// 1. Login
Route::post('login', [AuthController::class, 'login']);

// 2. Verifikasi 2FA
Route::post('two-factor', [AuthController::class, 'twoFactor']);

Route::get('produk-hargas/check', [ProdukHargaController::class, 'check']);

// 3. Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // a) Get current user
    Route::get('user', [ImpersonationController::class, 'whoami']);
    Route::get('/dashboard/agent-summary', [DashboardController::class, 'agentSummary']);
    Route::get('/dashboard/marketing-summary', [DashboardController::class, 'marketingSummary']);
    Route::get('/dashboard/ceo-summary', [DashboardController::class, 'ceoSummary']);
    Route::get('/dashboard/ceo-po-monthly-trend', [DashboardController::class, 'ceoPoMonthlyTrend']);
    Route::get('/dashboard/ceo-vendor-value-summary', [DashboardController::class, 'ceoVendorValueSummary']);
    Route::get('/dashboard/om-summary', [DashboardController::class, 'omSummary']);

    // b) Roles CRUD + permission matrix, c) Users CRUD -- admin-only, digerbangi backend beneran (middleware `can`), bukan cuma disembunyikan di FE.
    Route::middleware('can:admin.users.manage')->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('roles/{role}/permissions',  [RoleController::class, 'permissions']);
        Route::put('roles/{role}/permissions',  [RoleController::class, 'syncPermissions']);
        Route::get('permissions',               [PermissionController::class, 'index']);
        Route::post('permissions',              [PermissionController::class, 'store']);
        Route::put('permissions/{id}',          [PermissionController::class, 'update']);
        Route::delete('permissions/{id}',       [PermissionController::class, 'destroy']);

        Route::apiResource('users', UserController::class);
        Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    });

    Route::middleware(['can:admin.users.impersonate', 'throttle:10,1'])->group(function () {
        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start']);
    });

    Route::post('/impersonate/leave', [ImpersonationController::class, 'leave'])
        ->middleware('throttle:30,1');

    // d) 2FA management
    Route::post('2fa/generate', [TwoFactorController::class, 'generate']);
    Route::post('2fa/enable',   [TwoFactorController::class, 'enable']);
    Route::post('2fa/disable',  [TwoFactorController::class, 'disable']);

    // e) Update password & profile
    Route::post('user/password', [ProfileController::class, 'updatePassword']);
    Route::put('user/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/user/face', [ProfileController::class, 'updateFace']);

    // f) Master data
    Route::apiResource('cabangs', CabangController::class);
    Route::get('/cabangs/suggest', [CabangController::class, 'suggest']);
    Route::apiResource('satuans', SatuanController::class);
    Route::apiResource('ukurans', UkuranController::class);
    Route::apiResource('produks', ProdukController::class);
    Route::get('/produk-hargas/periode', [ProdukHargaController::class, 'periode']);
    Route::get('/produk-hargas/by-date', [ProdukHargaController::class, 'byDate']);
    Route::apiResource('produk-hargas', ProdukHargaController::class);
    Route::post('/produk-hargas/add-margin', [ProdukHargaController::class, 'addMargin']);

    // Approval Template master data -- admin-only, sama pola gate kayak Roles/Users di atas.
    Route::middleware('can:approval-template.manage')->group(function () {
        Route::apiResource('approval-templates', ApprovalTemplateController::class);
    });

    // Customer ownership migration (admin-only) -- pindah customers.id_user bulk, ada audit trail di customer_ownership_migrations, penawarans.user_id sengaja gak disentuh.
    Route::middleware('can:admin.customer-migration.manage')->group(function () {
        Route::get('admin/customers/by-owner', [CustomerMigrationController::class, 'byOwner']);
        Route::post('admin/customers/migrate-ownership', [CustomerMigrationController::class, 'migrateOwnership']);
    });

    // Customer Document Type: read dibuka buat semua authenticated user (dipakai role yang lebih luas dari admin), write tetap admin-only lewat gate di bawah.
    Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
        ->only(['index', 'show']);

    // WRITE: admin-only -- sama pola gate-nya kayak blok Roles/Users di atas.
    Route::middleware('can:master-data.customer-document-type.manage')->group(function () {
        Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
            ->only(['store', 'update', 'destroy']);
    });

    // Customer Contact Type: pola read/write sama kayak Customer Document Type di atas.
    Route::apiResource('customer-contact-types', CustomerContactTypeController::class)
        ->only(['index', 'show']);

    // WRITE: admin-only.
    Route::middleware('can:master-data.customer-contact-type.manage')->group(function () {
        Route::apiResource('customer-contact-types', CustomerContactTypeController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::apiResource('attachment-harga-dasar', AttachmentHargaDasarController::class);
    Route::get('customers/check-company-name', [CustomerController::class, 'checkCompanyName']);
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/onboarding-link', [CustomerController::class, 'generateOnboardingLink']);

    // customer_documents, scoped id_customer -- authorization di controller (ownership check per-row), bukan middleware can:.
    Route::get('customers/{customer}/documents', [CustomerDocumentController::class, 'index']);
    Route::post('customers/{customer}/documents', [CustomerDocumentController::class, 'store']);
    Route::put('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'update']);
    Route::delete('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy']);

    // customer_addresses, scoped id_customer -- authorization sama pola kayak customer_documents di atas.
    Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index']);
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store']);
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update']);
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy']);

    // customer_contacts, scoped id_customer -- multi-row PIC per customer, authorization sama pola dengan customer_addresses.
    Route::get('customers/{customer}/contacts', [CustomerContactController::class, 'index']);
    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store']);
    Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update']);
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy']);

    // customer_credit_submissions + items, scoped id_customer -- approval siklus sendiri lewat document_approvals (code=customer_credit), authorization sama pola kayak customer_addresses/contacts.
    Route::get('customers/{customer}/credit-submissions', [CustomerCreditSubmissionController::class, 'index']);
    Route::post('customers/{customer}/credit-submissions', [CustomerCreditSubmissionController::class, 'store']);
    Route::get('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'show']);
    Route::put('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'update']);
    Route::delete('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'destroy']);

    Route::post('customers/{customer}/credit-submissions/{submission}/items', [CustomerCreditItemController::class, 'store']);
    Route::put('customers/{customer}/credit-submissions/{submission}/items/{item}', [CustomerCreditItemController::class, 'update']);
    Route::delete('customers/{customer}/credit-submissions/{submission}/items/{item}', [CustomerCreditItemController::class, 'destroy']);

    // customer_lcr (site LCR, multi-site per customer) -- approval lewat document_approvals (code=customer_lcr_survey), authorization sama pola kayak blok Customer di atas.
    Route::get('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'index']);
    Route::post('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'store']);
    Route::get('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'show']);
    Route::put('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'update']);
    Route::delete('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'destroy']);
    Route::get('lcr-sites/{lcrSite}/approval-timeline', [CustomerLcrController::class, 'approvalTimeline']);
    Route::get('maps-link/resolve', [MapsLinkController::class, 'resolve']);

    // Daftar Penawaran milik customer -- bukti pendukung Admin Finance saat menilai pengajuan credit.
    Route::get('customers/{customer}/penawarans', [PenawaranController::class, 'lookupForCustomer']);

    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('terminals', TerminalController::class);

    // g) Vendor PO + detail
    Route::delete('vendor-pos-produk/batch', [VendorPoProdukController::class, 'destroyByPo'])
        ->name('vendor-pos-produk.batch-destroy');
    Route::post('vendor-pos-produk/batch', [VendorPoProdukController::class, 'storeBatch'])
        ->name('vendor-pos-produk.batch-store');

    Route::apiResource('vendor-pos-produk', VendorPoProdukController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->where(['vendor_pos_produk' => '[0-9]+']);

    Route::apiResource('vendor-pos', VendorPoController::class)->except(['create', 'edit']);
    Route::patch('vendor-pos/{id}/approve', [VendorPoController::class, 'approve'])
        ->name('vendor-pos.approve');

    // Verifikasi PO (CFO/CEO)
    Route::get('po-verification', [PoVerificationController::class, 'index']);
    Route::post('po-verification/{id}', [PoVerificationController::class, 'verify']);

    // Badge counter untuk CEO
    Route::get('approvals/pending-count', ApprovalPendingCountController::class);

    // Receive Item — global list
    Route::get('good-receipts/pending', [ReceiveItemController::class, 'pendingGr']);
    Route::get('good-receipts', [ReceiveItemController::class, 'index']);
    Route::delete('good-receipts/{id}', [ReceiveItemController::class, 'destroy'])->whereNumber('id');
    // Receive Item — per PO
    Route::get('vendor-pos/{poId}/receives',  [ReceiveItemController::class, 'indexByPo'])->whereNumber('poId');
    Route::post('vendor-pos/{poId}/receives', [ReceiveItemController::class, 'store'])->whereNumber('poId');

    // Stock, Penawaran, dsb.
    Route::get('stocks', [StockController::class, 'index']);

    Route::get('penawarans', [PenawaranController::class, 'index']);
    Route::get('penawarans/bm', [PenawaranController::class, 'indexForBranchManager']);
    Route::patch('penawarans/{id}/verifikasi',   [PenawaranController::class, 'verifikasi']);
    Route::patch('penawarans/{id}/tolak-bm',     [PenawaranController::class, 'tolakbm']);
    Route::get('penawarans/om',                  [PenawaranController::class, 'indexForOperationalManager']);
    Route::patch('penawarans/{id}/verifikasi-om', [PenawaranController::class, 'verifikasiOm']);
    Route::patch('penawarans/{id}/tolak-om',     [PenawaranController::class, 'tolakom']);
    Route::get('penawarans/{id}',                [PenawaranController::class, 'show']);
    Route::post('penawarans',                    [PenawaranController::class, 'store']);
    Route::put('penawarans/{id}',                [PenawaranController::class, 'update']);
    Route::delete('penawarans/{id}',             [PenawaranController::class, 'destroy']);
    Route::get('/penawarans/{id}/preview', [\App\Http\Controllers\PenawaranController::class, 'previewPdfMultiLang']);


    Route::apiResource('jenis-produks', JenisProdukController::class);
    Route::apiResource('transportirs', TransportirController::class);
    Route::apiResource('personnels', PersonnelController::class);
    Route::apiResource('volumes', VolumeController::class);
    Route::apiResource('wilayah-angkuts', WilayahAngkutController::class);
    Route::apiResource('master-kapals', MasterKapalController::class);
    Route::get('ongkos-kapal/check', [OngkosKapalController::class, 'checkOA']);
    Route::apiResource('ongkos-kapal', OngkosKapalController::class);
    Route::apiResource('master-trucks', MasterTruckController::class);
    Route::get('ongkos-trucks/check', [OngkosTruckController::class, 'checkOA']);
    Route::apiResource('ongkos-trucks', OngkosTruckController::class);
    Route::patch('penawarans/{id}/ajukan', [PenawaranController::class, 'ajukan']);
    Route::apiResource('customer-pos', PoCustomerController::class);

    // Antrean review Logistik lintas-customer buat site LCR (role 6, permission logistik.lcr.verify) -- gantiin flag_disposisi/flag_approval mentah lama.
    Route::get('review/lcr-sites', [CustomerLcrController::class, 'reviewIndex']);
    Route::get('review/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'reviewShow']);
    Route::patch('review/lcr-sites/{lcrSite}/decision', [CustomerLcrController::class, 'decide']);
    Route::patch('review/lcr-sites/{lcrSite}/reset-decision', [CustomerLcrController::class, 'resetDecision']);

    // Public form (pakai token) - upload internal (auth)
    Route::post('customer-verifications/{customerVerification}/upload', [CustomerVerificationController::class, 'upload']);

    Route::apiResource('customer-verifications', CustomerVerificationController::class);

    /* Section: Review (Marketing/Finance) - umum */
    Route::get('review/customer-verifications/stats', [CustomerVerificationController::class, 'reviewStats']);
    Route::get('review/customer-verifications',       [CustomerVerificationController::class, 'reviewIndex']);
    Route::get('review/customer-verifications/{id}',               [CustomerVerificationController::class, 'reviewShow'])->whereNumber('id');
    Route::get('review/customer-verifications/{id}/approval-timeline', [CustomerVerificationController::class, 'approvalTimeline'])->whereNumber('id');

    Route::patch('customer-verifications/{customerVerification}/set-reviewed', [CustomerVerificationController::class, 'setReviewed']);

    Route::get('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'getReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'saveReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review-attachment', [CustomerVerificationController::class, 'uploadReviewAttachment'])->whereNumber('id');
    Route::delete('review/customer-verifications/{id}/review-attachment/{no}', [CustomerVerificationController::class, 'deleteReviewAttachment'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/forward', [CustomerVerificationController::class, 'forward'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/close',   [CustomerVerificationController::class, 'close'])->whereNumber('id');
    Route::get('review/customer-verifications/{id}/document', [CustomerVerificationController::class, 'document'])->whereNumber('id');
    Route::get('review/customer-verifications/{id}/document/data-customer', [CustomerVerificationController::class, 'dataCustomerDocument'])->whereNumber('id');

    /* Section: Admin */
    Route::prefix('review/admin')->group(function () {
        Route::get('/customer-verifications', [CustomerVerificationController::class, 'reviewAdminIndex']);
        Route::get('/customer-verifications/stats', [CustomerVerificationController::class, 'reviewAdminStats']);
    });

    Route::prefix('review/bm')->group(function () {
        Route::get('customer-verifications',       [CustomerVerificationController::class, 'reviewBmIndex']);
        Route::get('customer-verifications/stats', [CustomerVerificationController::class, 'reviewBmStats']);
    });

    Route::get('/sales-confirmations', [PoCustomerController::class, 'salesConfirmation']);

    Route::get('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'showSalesConfirmation']);
    Route::post('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'saveSalesConfirmation']);

    // simpan keputusan ADMIN (sudah ada): POST /api/sales-confirmations/po/{poc}
    Route::post('/sales-confirmations/po/{poc}/bm', [PoCustomerController::class, 'saveSalesConfirmationBM']);

    Route::put('/po-customers/{poc}/nomor', [PoCustomerController::class, 'updateNomorPo']);
    Route::post('/po-customers/{poc}/close', [PoCustomerController::class, 'closePo']);

    Route::get('/po-customers/{poc}/plan', [PoCustomerController::class, 'getPoPlan']);
    Route::post('/po-customers/{poc}/plan', [PoCustomerController::class, 'createPoPlan']);
    Route::delete('/po-customers/{poc}/plan/{id}', [PoCustomerController::class, 'deletePoPlan']);

    Route::prefix('logistics')->group(function () {
        // Delivery Plan (list / edit volume / split)
        Route::get('/delivery-plans',              [DeliveryPlanController::class, 'index']);
        Route::get('/delivery-plans/{id}',         [DeliveryPlanController::class, 'show']);
        Route::patch('/delivery-plans/{id}',         [DeliveryPlanController::class, 'update']);
        Route::patch('/delivery-plans/{id}/volume',  [DeliveryPlanController::class, 'updateVolume']);
        Route::post('/delivery-plans/{id}/split',   [DeliveryPlanController::class, 'split']);

        // Simpan ke PR (header + detail)
        Route::post('/pr', [PrController::class, 'store']);
    });

    Route::prefix('procurement')->group(function () {
        // Delivery Request (PR + PR Detail)
        Route::get('/delivery-requests',           [DeliveryRequestController::class, 'index']);
        Route::get('/delivery-requests/{id}',      [DeliveryRequestController::class, 'show']);
        Route::post('/delivery-requests/allocate', [DeliveryRequestController::class, 'allocate']);
        Route::post('/delivery-requests/{id}/verify', [DeliveryRequestController::class, 'verify']);

        // Stock lookup (untuk modal pilih stok)
        Route::get('/stocks', [StockController::class, 'index']);
    });

    // PROENERGI

    Route::get('penawarans-proenergi', [PenawaranProenergiController::class, 'index']);
    Route::get('penawarans-proenergi/bm', [PenawaranProenergiController::class, 'indexForBranchManager']);
    Route::patch('penawarans-proenergi/{id}/verifikasi',   [PenawaranProenergiController::class, 'verifikasi']);
    Route::patch('penawarans-proenergi/{id}/tolak-bm',     [PenawaranProenergiController::class, 'tolakbm']);
    Route::get('penawarans-proenergi/om',                  [PenawaranProenergiController::class, 'indexForOperationalManager']);
    Route::patch('penawarans-proenergi/{id}/verifikasi-om', [PenawaranProenergiController::class, 'verifikasiOm']);
    Route::patch('penawarans-proenergi/{id}/tolak-om',     [PenawaranProenergiController::class, 'tolakom']);
    Route::get('penawarans-proenergi/{id}',                [PenawaranProenergiController::class, 'show']);
    Route::post('penawarans-proenergi',                    [PenawaranProenergiController::class, 'store']);
    Route::put('penawarans-proenergi/{id}',                [PenawaranProenergiController::class, 'update']);
    Route::delete('penawarans-proenergi/{id}',             [PenawaranProenergiController::class, 'destroy']);
    Route::get('/penawarans-proenergi/{id}/preview', [\App\Http\Controllers\PenawaranProenergiController::class, 'previewPdfMultiLang']);
    Route::patch('penawarans-proenergi/{id}/ajukan', [PenawaranProenergiController::class, 'ajukan']);

    // Monitoring — hanya Administrator (id_role=1)
    Route::get('/logs/files', [LogViewerController::class, 'files'])->middleware('throttle:30,1');
    Route::get('/logs', [LogViewerController::class, 'index'])->middleware('throttle:30,1');

    // Logout
    Route::post('logout', [AuthController::class, 'logout']);
});

// Public (tanpa auth)
Route::get('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'show']);
Route::put('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'update']);
Route::post('/verify/{token}/upload', [CustomerVerificationController::class, 'uploadByToken'])
    ->where('token', '[A-Za-z0-9\-]{10,}');
Route::get('/verifikasi-penawaran/{token}', [PenawaranVerificationController::class, 'show']);
// Lookup alamat BPS 4 level -- publik (tanpa auth) karena dipakai juga portal onboarding /verify/:token yang gak punya token Bearer.
Route::get('/provinces',                       [AddressController::class, 'provinces']);
Route::get('/provinces/{province}',             [AddressController::class, 'showProvince']);
Route::get('/provinces/{province}/regencies',   [AddressController::class, 'regencies']);
Route::get('/regencies/{regency}',              [AddressController::class, 'showRegency']);
Route::get('/regencies/{regency}/districts',    [AddressController::class, 'districts']);
Route::get('/districts/{district}',             [AddressController::class, 'showDistrict']);
Route::get('/districts/{district}/villages',    [AddressController::class, 'villages']);
Route::get('/villages/{village}',               [AddressController::class, 'showVillage']);
Route::get('/captcha', [CaptchaController::class, 'generate'])->middleware('throttle:30,1');

// Public PO detail (contoh)
Route::get('public/vendor-pos/{id}', [VendorPoController::class, 'publicShow'])->whereNumber('id');

// Dev only: testing kirim email
Route::get('dev/test-email', [\App\Http\Controllers\Dev\MailTestController::class, 'send']);
