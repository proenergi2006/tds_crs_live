<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CustomerMigrationController;
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
use App\Http\Controllers\ProvinsiController;
use App\Http\Controllers\KabupatenController;
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


// use Illuminate\Support\Facades\Mail;

// Route::get('/debug-test-mail', function () {
//     try {
//         Mail::raw('Test kirim email dari Laravel (API).', function ($m) {
//             $m->to('iwan.hermawan@proenergi.co.id')->subject('Test SMTP (API)');
//         });
//         return response()->json(['ok' => true, 'message' => 'Email test dikirim. Cek inbox/spam.']);
//     } catch (\Throwable $e) {
//         // Lihat juga storage/logs/laravel.log
//         return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
//     }
// });


// 1. Login
Route::post('login', [AuthController::class, 'login']);

// 2. Verifikasi 2FA
Route::post('two-factor', [AuthController::class, 'twoFactor']);

Route::get('produk-hargas/check', [ProdukHargaController::class, 'check']);

// 3. Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // a) Get current user
    Route::get('user', fn(Request $req) => $req->user());
    Route::get('/dashboard/agent-summary', [DashboardController::class, 'agentSummary']);
    Route::get('/dashboard/marketing-summary', [DashboardController::class, 'marketingSummary']);

    // b) Roles CRUD + permission matrix, c) Users CRUD — admin-only. Pakai middleware `can`
    // (alias existing di app/Http/Kernel.php -> Illuminate\Auth\Middleware\Authorize, sudah
    // terhubung ke Spatie Gate::before + admin bypass id_role=1 di AuthServiceProvider) supaya
    // endpoint role/permission/user benar-benar digerbangi backend, bukan cuma disembunyikan
    // di menu FE. Mirrors the `can:approval-template.manage` block below.
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

    // d) 2FA management
    Route::post('2fa/generate', [TwoFactorController::class, 'generate']);
    Route::post('2fa/enable',   [TwoFactorController::class, 'enable']);
    Route::post('2fa/disable',  [TwoFactorController::class, 'disable']);

    // e) Update password & profile
    Route::post('user/password', [ProfileController::class, 'updatePassword']);
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

    // Approval Template master data (admin-only, Prioritas H4 -
    // approval-system-customer-verification.md). Pakai middleware `can` (alias
    // existing di app/Http/Kernel.php -> Illuminate\Auth\Middleware\Authorize,
    // sudah terhubung ke Spatie Gate::before + admin bypass id_role=1 di
    // AuthServiceProvider) supaya endpoint ini benar-benar digerbangi backend,
    // bukan cuma disembunyikan di menu FE.
    Route::middleware('can:approval-template.manage')->group(function () {
        Route::apiResource('approval-templates', ApprovalTemplateController::class);
    });

    // Customer ownership migration (Administrator-only) -- reassign customers.id_user
    // from one owner to another, bulk, with audit trail (customer_ownership_migrations).
    // penawarans.user_id is intentionally untouched by this feature.
    Route::middleware('can:admin.customer-migration.manage')->group(function () {
        Route::get('admin/customers/by-owner', [CustomerMigrationController::class, 'byOwner']);
        Route::post('admin/customers/migrate-ownership', [CustomerMigrationController::class, 'migrateOwnership']);
    });

    // Customer Document Type master data. READ (index/show) dan WRITE
    // (store/update/destroy) digerbangi TERPISAH -- read dikonsumsi juga
    // oleh Customer/Detail.vue Tab 1 untuk role yang jauh lebih luas dari
    // Administrator (Key Account dkk, lihat gate customer.viewOwn/
    // customer.viewAny di CustomerController), bukan cuma admin.
    //
    // READ: cukup authenticated, TIDAK digerbangi permission tambahan --
    // pola sama dengan lookup master data read-only lain di project (lihat
    // apiResource('satuans'/'ukurans'/'produks'/'vendors'/'terminals', ...)
    // di bawah, semuanya TANPA middleware `can:` sama sekali untuk index/show).
    Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
        ->only(['index', 'show']);

    // WRITE: admin-only -- pola sama dengan approval-templates di atas
    // (`can` middleware, alias existing di app/Http/Kernel.php ->
    // Illuminate\Auth\Middleware\Authorize, sudah terhubung ke Spatie
    // Gate::before + admin bypass id_role=1 di AuthServiceProvider).
    Route::middleware('can:master-data.customer-document-type.manage')->group(function () {
        Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
            ->only(['store', 'update', 'destroy']);
    });

    // Customer Contact Type master data. Read/write digerbangi terpisah dengan
    // pola sama seperti customer-document-types di atas -- read akan dikonsumsi
    // juga oleh Customer/Detail.vue untuk role yang lebih luas dari
    // Administrator.
    //
    // READ: cukup authenticated, TIDAK digerbangi permission tambahan.
    Route::apiResource('customer-contact-types', CustomerContactTypeController::class)
        ->only(['index', 'show']);

    // WRITE: admin-only.
    Route::middleware('can:master-data.customer-contact-type.manage')->group(function () {
        Route::apiResource('customer-contact-types', CustomerContactTypeController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::apiResource('attachment-harga-dasar', AttachmentHargaDasarController::class);
    Route::apiResource('provinsis', ProvinsiController::class);
    Route::apiResource('kabupatens', KabupatenController::class);
    Route::get('customers/check-company-name', [CustomerController::class, 'checkCompanyName']);
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/onboarding-link', [CustomerController::class, 'generateOnboardingLink']);

    // customer_documents, scoped id_customer -- menggantikan kolom flat
    // nib/nomor_npwp/nomor_sertifikat/dokumen_lainnya (+ pasangan _file)
    // di customers. Authorization dilakukan di dalam controller (pola sama dengan
    // CustomerController::show/update/destroy), bukan lewat middleware can:,
    // karena butuh ownership check per-row (customer.id_user), bukan cek
    // permission statis.
    Route::get('customers/{customer}/documents', [CustomerDocumentController::class, 'index']);
    Route::post('customers/{customer}/documents', [CustomerDocumentController::class, 'store']);
    Route::delete('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy']);

    // customer_addresses, scoped id_customer -- alamat berbasis sistem BPS
    // (province_id/regency_id/district_id/village_id), bukan legacy
    // id_provinsi/id_kabupaten. Authorization sama pola dengan
    // customer_documents di atas (ownership check di dalam controller).
    Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index']);
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store']);
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update']);
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy']);

    // customer_contacts, scoped id_customer -- multi-row PIC per customer
    // (restrukturisasi dari kolom fixed pic_decision/pic_ordering/pic_billing/
    // pic_invoice lama). Authorization sama pola dengan customer_addresses.
    Route::get('customers/{customer}/contacts', [CustomerContactController::class, 'index']);
    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store']);
    Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update']);
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy']);

    // customer_credit_submissions + customer_credit_items, scoped id_customer
    // -- pengajuan kredit (header + detail per produk), approval siklusnya
    // sendiri lewat document_approvals (code=customer_credit), independen
    // dari siklus customer_verifications. Authorization sama pola dengan
    // customer_addresses/customer_contacts di atas (ownership check di dalam
    // controller).
    Route::get('customers/{customer}/credit-submissions', [CustomerCreditSubmissionController::class, 'index']);
    Route::post('customers/{customer}/credit-submissions', [CustomerCreditSubmissionController::class, 'store']);
    Route::get('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'show']);
    Route::put('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'update']);
    Route::delete('customers/{customer}/credit-submissions/{submission}', [CustomerCreditSubmissionController::class, 'destroy']);

    Route::post('customers/{customer}/credit-submissions/{submission}/items', [CustomerCreditItemController::class, 'store']);
    Route::put('customers/{customer}/credit-submissions/{submission}/items/{item}', [CustomerCreditItemController::class, 'update']);
    Route::delete('customers/{customer}/credit-submissions/{submission}/items/{item}', [CustomerCreditItemController::class, 'destroy']);

    // customer_lcr (site LCR, 1 customer bisa multi-site), approval siklusnya
    // sendiri lewat document_approvals (code=customer_lcr_survey). Authorization
    // sama pola dengan blok Customer di atas (ownership check di dalam controller).
    Route::get('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'index']);
    Route::post('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'store']);
    Route::get('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'show']);
    Route::put('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'update']);
    Route::delete('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'destroy']);
    Route::get('lcr-sites/{lcrSite}/approval-timeline', [CustomerLcrController::class, 'approvalTimeline']);

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
    // routes/web.php (atau api.php kalau kamu expose via API)
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

    Route::post('uploads/lcr-image', [CustomerLcrController::class, 'uploadImage']);

    // Antrean review Logistik lintas-customer untuk site LCR (role 6, permission
    // logistik.lcr.verify) -- menggantikan flag_disposisi/flag_approval mentah lama.
    Route::get('review/lcr-sites', [CustomerLcrController::class, 'reviewIndex']);
    Route::get('review/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'reviewShow']);
    Route::patch('review/lcr-sites/{lcrSite}/decision', [CustomerLcrController::class, 'decide']);
    Route::patch('review/lcr-sites/{lcrSite}/reset-decision', [CustomerLcrController::class, 'resetDecision']);

    // Public form (pakai token) - upload internal (auth)
    Route::post('customer-verifications/{customerVerification}/upload', [CustomerVerificationController::class, 'upload']);

    // Resource CustomerVerification (internal)
    Route::apiResource('customer-verifications', CustomerVerificationController::class);

    // ===== Review (Marketing/Finance) – umum =====
    Route::get('review/customer-verifications/stats', [CustomerVerificationController::class, 'reviewStats']);
    Route::get('review/customer-verifications',       [CustomerVerificationController::class, 'reviewIndex']);
    Route::get('review/customer-verifications/{id}',               [CustomerVerificationController::class, 'reviewShow'])->whereNumber('id');
    Route::get('review/customer-verifications/{id}/approval-timeline', [CustomerVerificationController::class, 'approvalTimeline'])->whereNumber('id');
    Route::patch('review/customer-verifications/{id}/review-data',   [CustomerVerificationController::class, 'saveReviewData'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review-upload', [CustomerVerificationController::class, 'uploadReviewFile'])->whereNumber('id');

    Route::patch('customer-verifications/{customerVerification}/set-reviewed', [CustomerVerificationController::class, 'setReviewed']);

    Route::get('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'getReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'saveReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review-attachment', [CustomerVerificationController::class, 'uploadReviewAttachment'])->whereNumber('id');
    Route::delete('review/customer-verifications/{id}/review-attachment/{no}', [CustomerVerificationController::class, 'deleteReviewAttachment'])->whereNumber('id');

    // ====== ⬇⬇⬇ TAMBAHAN: EVALUATION (COCOK DENGAN FE) ⬇⬇⬇ ======
    Route::get('review/customer-verifications/{id}/evaluation',            [CustomerVerificationController::class, 'getEvaluation'])->whereNumber('id');
    Route::get('/review/customer-verifications/{id}/admin-evaluation', [CustomerVerificationController::class, 'getAdminEvaluation']);


    Route::post('review/customer-verifications/{id}/evaluation',            [CustomerVerificationController::class, 'saveEvaluation'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/evaluation-attachment', [CustomerVerificationController::class, 'evaluationUploadFile'])->whereNumber('id');
    // ====== ⬆⬆⬆ TAMBAHAN: EVALUATION (COCOK DENGAN FE) ⬆⬆⬆ ======

    // ===== Admin (biarkan seperti semula) =====
    Route::prefix('review/admin')->group(function () {
        Route::get('/customer-verifications', [CustomerVerificationController::class, 'reviewAdminIndex']);
        Route::get('/customer-verifications/stats', [CustomerVerificationController::class, 'reviewAdminStats']);

        // (yang ini biarkan — URL-nya menjadi /api/review/admin/review/customer-verifications/{id}/evaluation)
        Route::prefix('review/customer-verifications')->group(function () {
            Route::get('{id}/evaluation',  [CustomerVerificationController::class, 'getEvaluation'])->whereNumber('id');
            Route::post('{id}/evaluation', [CustomerVerificationController::class, 'saveEvaluation'])->whereNumber('id');
        });

        // Dan alias lain yang sudah ada sebelumnya (tetap dibiarkan)
        Route::get('/review/customer-verifications/{id}/evaluation',  [CustomerVerificationController::class, 'evaluationShow'])->whereNumber('id');
        Route::post('/review/customer-verifications/{id}/evaluation-file', [CustomerVerificationController::class, 'evaluationUploadFile'])->whereNumber('id');
    });

    Route::prefix('review/bm')->group(function () {
        Route::get('customer-verifications',       [CustomerVerificationController::class, 'reviewBmIndex']);
        Route::get('customer-verifications/stats', [CustomerVerificationController::class, 'reviewBmStats']);
        // simpan verifikasi BM
        Route::patch('customer-verifications/{id}/verify', [CustomerVerificationController::class, 'bmVerify']);
    });

    Route::get('/sales-confirmations', [PoCustomerController::class, 'salesConfirmation']);

    Route::get('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'showSalesConfirmation']);
    Route::post('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'saveSalesConfirmation']);

    // simpan keputusan ADMIN (sudah ada): POST /api/sales-confirmations/po/{poc}
    Route::post('/sales-confirmations/po/{poc}/bm', [PoCustomerController::class, 'saveSalesConfirmationBM']); // ⬅️ baru

    Route::put('/po-customers/{poc}/nomor', [PoCustomerController::class, 'updateNomorPo']);
    Route::post('/po-customers/{poc}/close', [PoCustomerController::class, 'closePo']);

    Route::get('/po-customers/{poc}/plan', [PoCustomerController::class, 'getPoPlan']);
    Route::post('/po-customers/{poc}/plan', [PoCustomerController::class, 'createPoPlan']); // ⬅️ baru
    Route::delete('/po-customers/{poc}/plan/{id}', [PoCustomerController::class, 'deletePoPlan']); // opsional

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
    // routes/web.php (atau api.php kalau kamu expose via API)
    Route::get('/penawarans-proenergi/{id}/preview', [\App\Http\Controllers\PenawaranProenergiController::class, 'previewPdfMultiLang']);
    Route::patch('penawarans-proenergi/{id}/ajukan', [PenawaranProenergiController::class, 'ajukan']);
    Route::patch('penawarans-proenergi/{id}/verifikasi',   [PenawaranProenergiController::class, 'verifikasi']);
    Route::patch('penawarans-proenergi/{id}/tolak-bm',     [PenawaranProenergiController::class, 'tolakbm']);

    // Monitoring — hanya Administrator (id_role=1)
    Route::get('/logs/files', [LogViewerController::class, 'files'])->middleware('throttle:30,1');
    Route::get('/logs', [LogViewerController::class, 'index'])->middleware('throttle:30,1');







    // Logout
    Route::post('logout', [AuthController::class, 'logout']);
});

// ====== Public (tanpa auth) ======
Route::get('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'show']);
Route::put('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'update']);
Route::post('/verify/{token}/upload', [CustomerVerificationController::class, 'uploadByToken'])
    ->where('token', '[A-Za-z0-9\-]{10,}');
Route::get('/masters/provinsis',  [ProvinsiController::class,  'publicIndex']);
Route::get('/masters/kabupatens', [KabupatenController::class, 'publicIndex']);

// Lookup alamat BPS 4 level (laravel-nusa-address-full-migration Task 7) —
// pengganti fungsi /masters/provinsis + /masters/kabupatens lama di atas,
// diperluas ke province/regency/district/village. Publik (tanpa auth) —
// dipakai juga oleh portal onboarding /verify/:token yang tidak punya token
// Bearer. Route lama TIDAK dihapus.
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

// ====== Dev only: testing kirim email (nonaktif di production) ======
Route::get('dev/test-email', [\App\Http\Controllers\Dev\MailTestController::class, 'send']);
