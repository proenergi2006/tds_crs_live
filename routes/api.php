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
use App\Http\Controllers\MasterData\CustomerDocumentTypeController;
use App\Http\Controllers\MasterData\JenisProdukController;
use App\Http\Controllers\MasterData\ProdukController;
use App\Http\Controllers\MasterData\ProductPriceController;
use App\Http\Controllers\MasterData\SatuanController;
use App\Http\Controllers\MasterData\TerminalController;
use App\Http\Controllers\MasterData\UkuranController;
use App\Http\Controllers\MasterData\VendorController;
use App\Http\Controllers\MasterData\PricePeriodController;
use App\Http\Controllers\MasterData\AddressController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerContactController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerCreditRequestController;
use App\Http\Controllers\Customer\CustomerDocumentController;
use App\Http\Controllers\Customer\CustomerOnboardingController;
use App\Http\Controllers\Customer\CustomerPaymentController;
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
use App\Http\Controllers\CustomerLcrController;
use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\MapsLinkController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DeliveryPlanController;
use App\Http\Controllers\PrController;
use App\Http\Controllers\DeliveryRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ApprovalPendingCountController;
use App\Http\Controllers\Monitoring\LogViewerController;

Route::post('login', [AuthController::class, 'login']);

Route::post('two-factor', [AuthController::class, 'twoFactor']);

Route::get('product-prices/check', [ProductPriceController::class, 'check']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [ImpersonationController::class, 'whoami']);
    Route::get('/dashboard/agent-summary', [DashboardController::class, 'agentSummary']);
    Route::get('/dashboard/marketing-summary', [DashboardController::class, 'marketingSummary']);
    Route::get('/dashboard/ceo-summary', [DashboardController::class, 'ceoSummary']);
    Route::get('/dashboard/ceo-po-monthly-trend', [DashboardController::class, 'ceoPoMonthlyTrend']);
    Route::get('/dashboard/ceo-vendor-value-summary', [DashboardController::class, 'ceoVendorValueSummary']);
    Route::get('/dashboard/om-summary', [DashboardController::class, 'omSummary']);

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

    Route::post('2fa/generate', [TwoFactorController::class, 'generate']);
    Route::post('2fa/enable',   [TwoFactorController::class, 'enable']);
    Route::post('2fa/disable',  [TwoFactorController::class, 'disable']);

    Route::post('user/password', [ProfileController::class, 'updatePassword']);
    Route::put('user/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/user/face', [ProfileController::class, 'updateFace']);

    Route::apiResource('cabangs', CabangController::class);
    Route::get('/cabangs/suggest', [CabangController::class, 'suggest']);
    Route::apiResource('satuans', SatuanController::class);
    Route::apiResource('ukurans', UkuranController::class);
    Route::apiResource('produks', ProdukController::class);
    Route::get('/product-prices/by-date', [ProductPriceController::class, 'byDate']);
    Route::apiResource('product-prices', ProductPriceController::class);
    Route::apiResource('price-periods', PricePeriodController::class);

    Route::middleware('can:approval-template.manage')->group(function () {
        Route::apiResource('approval-templates', ApprovalTemplateController::class);
    });

    Route::middleware('can:admin.customer-migration.manage')->group(function () {
        Route::get('admin/customers/by-owner', [CustomerMigrationController::class, 'byOwner']);
        Route::post('admin/customers/migrate-ownership', [CustomerMigrationController::class, 'migrateOwnership']);
    });

    Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
        ->only(['index', 'show']);

    Route::middleware('can:master-data.customer-document-type.manage')->group(function () {
        Route::apiResource('customer-document-types', CustomerDocumentTypeController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::get('customers/check-company-name', [CustomerController::class, 'checkCompanyName']);
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/{customer}/tab-completeness', [CustomerController::class, 'tabCompleteness']);
    Route::post('customers/{customer}/onboarding-link', [CustomerController::class, 'generateOnboardingLink']);
    Route::post('customers/{customer}/verification', [CustomerVerificationController::class, 'store']);

    // authz per-row di controller (ownership check), bukan middleware can:
    Route::get('customers/{customer}/documents', [CustomerDocumentController::class, 'index']);
    Route::post('customers/{customer}/documents', [CustomerDocumentController::class, 'store']);
    Route::put('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'update']);
    Route::delete('customers/{customer}/documents/{document}', [CustomerDocumentController::class, 'destroy']);

    Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index']);
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store']);
    // {address} dikunci numerik biar gak tabrakan sama addresses/{addressType} (huruf) di bawah
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->whereNumber('address');
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->whereNumber('address');

    Route::put('customers/{customer}/addresses/{addressType}', [CustomerController::class, 'updateAddress'])->where('addressType', '[A-Za-z_]+');

    Route::put('customers/{customer}/payment', [CustomerPaymentController::class, 'update']);

    Route::get('customers/{customer}/contacts', [CustomerContactController::class, 'index']);
    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store']);
    Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update']);
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy']);

    Route::get('customers/{customer}/credit-request', [CustomerCreditRequestController::class, 'show']);
    Route::put('customers/{customer}/credit-request', [CustomerCreditRequestController::class, 'update']);

    Route::get('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'index']);
    Route::post('customers/{customer}/lcr-sites', [CustomerLcrController::class, 'store']);
    Route::get('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'show']);
    Route::put('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'update']);
    Route::delete('customers/{customer}/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'destroy']);
    Route::get('lcr-sites/{lcrSite}/approval-timeline', [CustomerLcrController::class, 'approvalTimeline']);
    Route::get('maps-link/resolve', [MapsLinkController::class, 'resolve']);

    Route::get('customers/{customer}/review', [CustomerReviewController::class, 'getReview']);
    Route::post('customers/{customer}/review', [CustomerReviewController::class, 'saveReview']);
    Route::post('customers/{customer}/review-attachment', [CustomerReviewController::class, 'uploadReviewAttachment']);
    Route::delete('customers/{customer}/review-attachment/{no}', [CustomerReviewController::class, 'deleteReviewAttachment']);

    Route::get('customers/{customer}/penawarans', [CustomerVerificationController::class, 'lookupForCustomer']);

    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('terminals', TerminalController::class);

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

    Route::get('po-verification', [PoVerificationController::class, 'index']);
    Route::post('po-verification/{id}', [PoVerificationController::class, 'verify']);

    Route::get('approvals/pending-count', ApprovalPendingCountController::class);

    Route::get('good-receipts/pending', [ReceiveItemController::class, 'pendingGr']);
    Route::get('good-receipts', [ReceiveItemController::class, 'index']);
    Route::delete('good-receipts/{id}', [ReceiveItemController::class, 'destroy'])->whereNumber('id');
    Route::get('vendor-pos/{poId}/receives',  [ReceiveItemController::class, 'indexByPo'])->whereNumber('poId');
    Route::post('vendor-pos/{poId}/receives', [ReceiveItemController::class, 'store'])->whereNumber('poId');

    Route::get('stocks', [StockController::class, 'index']);

    Route::get('penawarans', [PenawaranController::class, 'index'])->defaults('brand', 'tds');
    Route::get('penawarans/bm', [PenawaranController::class, 'bmVerificationIndex'])->defaults('brand', 'tds');
    Route::patch('penawarans/{id}/verifikasi',   [PenawaranController::class, 'verifikasi'])->defaults('brand', 'tds');
    Route::patch('penawarans/{id}/tolak-bm',     [PenawaranController::class, 'tolakbm'])->defaults('brand', 'tds');
    Route::get('penawarans/om',                  [PenawaranController::class, 'omVerificationIndex'])->defaults('brand', 'tds');
    Route::patch('penawarans/{id}/verifikasi-om', [PenawaranController::class, 'verifikasiOm'])->defaults('brand', 'tds');
    Route::patch('penawarans/{id}/tolak-om',     [PenawaranController::class, 'tolakom'])->defaults('brand', 'tds');
    Route::get('penawarans/{id}',                [PenawaranController::class, 'show'])->defaults('brand', 'tds');
    Route::post('penawarans',                    [PenawaranController::class, 'store'])->defaults('brand', 'tds');
    Route::put('penawarans/{id}',                [PenawaranController::class, 'update'])->defaults('brand', 'tds');
    Route::delete('penawarans/{id}',             [PenawaranController::class, 'destroy'])->defaults('brand', 'tds');
    Route::get('/penawarans/{id}/preview', [PenawaranController::class, 'previewPdfMultiLang'])->defaults('brand', 'tds');

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
    Route::patch('penawarans/{id}/ajukan', [PenawaranController::class, 'ajukan'])->defaults('brand', 'tds');
    Route::apiResource('customer-pos', PoCustomerController::class);

    Route::get('review/lcr-sites', [CustomerLcrController::class, 'reviewIndex']);
    // harus di atas review/lcr-sites/{lcrSite} -- kalau tidak, "stats" ketangkap sebagai {lcrSite} lalu route-model-bind balik 404
    Route::get('review/lcr-sites/stats', [CustomerLcrController::class, 'reviewStats']);
    Route::get('review/lcr-sites/{lcrSite}', [CustomerLcrController::class, 'reviewShow']);
    Route::patch('review/lcr-sites/{lcrSite}/decision', [CustomerLcrController::class, 'decide']);
    Route::patch('review/lcr-sites/{lcrSite}/reset-decision', [CustomerLcrController::class, 'resetDecision']);

    Route::get('review/customer-verifications/stats', [CustomerVerificationController::class, 'reviewStats']);
    Route::get('review/customer-verifications',       [CustomerVerificationController::class, 'reviewIndex']);
    Route::get('review/customer-verifications/{id}',               [CustomerVerificationController::class, 'reviewShow'])->whereNumber('id');
    Route::patch('customer-verifications/{customerVerification}/decision', [CustomerVerificationController::class, 'decision']);

    Route::get('review/customer-verifications/{id}/document', [CustomerVerificationController::class, 'document'])->whereNumber('id');
    Route::get('review/customer-verifications/{id}/document/data-customer', [CustomerVerificationController::class, 'dataCustomerDocument'])->whereNumber('id');

    Route::get('/sales-confirmations', [PoCustomerController::class, 'salesConfirmation']);

    Route::get('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'showSalesConfirmation']);
    Route::post('/sales-confirmations/po/{poc}', [PoCustomerController::class, 'saveSalesConfirmation']);

    Route::post('/sales-confirmations/po/{poc}/bm', [PoCustomerController::class, 'saveSalesConfirmationBM']);

    Route::put('/po-customers/{poc}/nomor', [PoCustomerController::class, 'updateNomorPo']);

    Route::get('/po-customers/{poc}/plan', [PoCustomerController::class, 'getPoPlan']);
    Route::post('/po-customers/{poc}/plan', [PoCustomerController::class, 'createPoPlan']);
    Route::delete('/po-customers/{poc}/plan/{id}', [PoCustomerController::class, 'deletePoPlan']);

    Route::prefix('logistics')->group(function () {
        Route::get('/delivery-plans',              [DeliveryPlanController::class, 'index']);
        Route::get('/delivery-plans/{id}',         [DeliveryPlanController::class, 'show']);
        Route::patch('/delivery-plans/{id}',         [DeliveryPlanController::class, 'update']);
        Route::patch('/delivery-plans/{id}/volume',  [DeliveryPlanController::class, 'updateVolume']);
        Route::post('/delivery-plans/{id}/split',   [DeliveryPlanController::class, 'split']);

        Route::post('/pr', [PrController::class, 'store']);
    });

    Route::prefix('procurement')->group(function () {
        Route::get('/delivery-requests',           [DeliveryRequestController::class, 'index']);
        Route::get('/delivery-requests/{id}',      [DeliveryRequestController::class, 'show']);
        Route::post('/delivery-requests/allocate', [DeliveryRequestController::class, 'allocate']);
        Route::post('/delivery-requests/{id}/verify', [DeliveryRequestController::class, 'verify']);

        Route::get('/stocks', [StockController::class, 'index']);
    });

    Route::get('penawarans-proenergi', [PenawaranController::class, 'index'])->defaults('brand', 'proenergi');
    Route::get('penawarans-proenergi/bm', [PenawaranController::class, 'bmVerificationIndex'])->defaults('brand', 'proenergi');
    Route::patch('penawarans-proenergi/{id}/verifikasi',   [PenawaranController::class, 'verifikasi'])->defaults('brand', 'proenergi');
    Route::patch('penawarans-proenergi/{id}/tolak-bm',     [PenawaranController::class, 'tolakbm'])->defaults('brand', 'proenergi');
    Route::get('penawarans-proenergi/om',                  [PenawaranController::class, 'omVerificationIndex'])->defaults('brand', 'proenergi');
    Route::patch('penawarans-proenergi/{id}/verifikasi-om', [PenawaranController::class, 'verifikasiOm'])->defaults('brand', 'proenergi');
    Route::patch('penawarans-proenergi/{id}/tolak-om',     [PenawaranController::class, 'tolakom'])->defaults('brand', 'proenergi');
    Route::get('penawarans-proenergi/{id}',                [PenawaranController::class, 'show'])->defaults('brand', 'proenergi');
    Route::post('penawarans-proenergi',                    [PenawaranController::class, 'store'])->defaults('brand', 'proenergi');
    Route::put('penawarans-proenergi/{id}',                [PenawaranController::class, 'update'])->defaults('brand', 'proenergi');
    Route::delete('penawarans-proenergi/{id}',             [PenawaranController::class, 'destroy'])->defaults('brand', 'proenergi');
    Route::get('/penawarans-proenergi/{id}/preview', [PenawaranController::class, 'previewPdfMultiLang'])->defaults('brand', 'proenergi');
    Route::patch('penawarans-proenergi/{id}/ajukan', [PenawaranController::class, 'ajukan'])->defaults('brand', 'proenergi');

    Route::get('/logs/files', [LogViewerController::class, 'files'])->middleware('throttle:30,1');
    Route::get('/logs', [LogViewerController::class, 'index'])->middleware('throttle:30,1');

    Route::post('logout', [AuthController::class, 'logout']);
});

// publik, sengaja tanpa auth
Route::get('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'show']);
Route::put('/customer-onboarding/{token}', [CustomerOnboardingController::class, 'update']);
Route::get('/verifikasi-penawaran/{token}', [PenawaranVerificationController::class, 'show']);
// publik: dipakai portal onboarding /verify/:token yang gak punya Bearer token
Route::get('/provinces',                       [AddressController::class, 'provinces']);
Route::get('/provinces/{province}',             [AddressController::class, 'showProvince']);
Route::get('/provinces/{province}/regencies',   [AddressController::class, 'regencies']);
Route::get('/regencies/{regency}',              [AddressController::class, 'showRegency']);
Route::get('/regencies/{regency}/districts',    [AddressController::class, 'districts']);
Route::get('/districts/{district}',             [AddressController::class, 'showDistrict']);
Route::get('/districts/{district}/villages',    [AddressController::class, 'villages']);
Route::get('/villages/{village}',               [AddressController::class, 'showVillage']);
Route::get('/captcha', [CaptchaController::class, 'generate'])->middleware('throttle:30,1');

Route::get('public/vendor-pos/{id}', [VendorPoController::class, 'publicShow'])->whereNumber('id');

Route::get('dev/test-email', [\App\Http\Controllers\Dev\MailTestController::class, 'send']);
