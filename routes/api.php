<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController; 
use App\Http\Controllers\TwoFactorController; 
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\CabangController;
use App\Http\Controllers\SatuanController; 
use App\Http\Controllers\UkuranController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProdukHargaController;
use App\Http\Controllers\AttachmentHargaDasarController;
use App\Http\Controllers\ProvinsiController;
use App\Http\Controllers\KabupatenController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\VendorPoController;
use App\Http\Controllers\VendorPoProdukController;
use App\Http\Controllers\PoVerificationController;
use App\Http\Controllers\ReceiveItemController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\JenisProdukController;
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
use App\Http\Controllers\CustomerVerificationController;
use App\Http\Controllers\LinkCustomerController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DeliveryPlanController;
use App\Http\Controllers\PrController;
use App\Http\Controllers\DeliveryRequestController;


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

    // b) Roles CRUD
    Route::apiResource('roles', RoleController::class);

    // c) Users CRUD
    Route::apiResource('users', UserController::class);

    // d) 2FA management
    Route::post('2fa/generate', [TwoFactorController::class,'generate']);
    Route::post('2fa/enable',   [TwoFactorController::class,'enable']);
    Route::post('2fa/disable',  [TwoFactorController::class,'disable']);

    // e) Update password & profile
    Route::post('user/password', [ProfileController::class, 'updatePassword']);
    Route::post('/user/face', [ProfileController::class, 'updateFace']);

    // f) Master data
    Route::apiResource('cabangs', CabangController::class);
    Route::get('/cabangs/suggest', [CabangController::class, 'suggest']);
    Route::apiResource('satuans', SatuanController::class);
    Route::apiResource('ukurans', UkuranController::class);
    Route::apiResource('produks', ProdukController::class);
    Route::apiResource('produk-hargas', ProdukHargaController::class);
    Route::post('/produk-hargas/add-margin', [ProdukHargaController::class, 'addMargin']);

    Route::apiResource('attachment-harga-dasar', AttachmentHargaDasarController::class);
    Route::apiResource('provinsis', ProvinsiController::class);
    Route::apiResource('kabupatens', KabupatenController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('terminals', TerminalController::class);

    // g) Vendor PO + detail
    Route::delete('vendor-pos-produk/batch', [VendorPoProdukController::class, 'destroyByPo'])
        ->name('vendor-pos-produk.batch-destroy');
    Route::post('vendor-pos-produk/batch', [VendorPoProdukController::class, 'storeBatch'])
        ->name('vendor-pos-produk.batch-store');

    Route::apiResource('vendor-pos-produk', VendorPoProdukController::class)
         ->only(['index','store','show','update','destroy'])
         ->where(['vendor_pos_produk' => '[0-9]+']);

    Route::apiResource('vendor-pos', VendorPoController::class)->except(['create','edit']);
    Route::patch('vendor-pos/{id}/approve', [VendorPoController::class, 'approve'])
         ->name('vendor-pos.approve');

    // Verifikasi PO (CFO/CEO)
    Route::get('po-verification', [PoVerificationController::class, 'index']);
    Route::post('po-verification/{id}', [PoVerificationController::class, 'verify']);

    // Receive Item
    Route::get('vendor-pos/{poId}/receives',  [ReceiveItemController::class, 'index'])->whereNumber('poId');
    Route::post('vendor-pos/{poId}/receives', [ReceiveItemController::class, 'store'])->whereNumber('poId');

    // Stock, Penawaran, dsb.
    Route::get('stocks', [StockController::class, 'index']);

    Route::get('penawarans', [PenawaranController::class, 'index']);
    Route::get('penawarans/bm', [PenawaranController::class, 'indexForBranchManager']);
    Route::patch('penawarans/{id}/verifikasi',   [PenawaranController::class, 'verifikasi']);
    Route::patch('penawarans/{id}/tolak-bm',     [PenawaranController::class, 'tolakbm']);
    Route::get('penawarans/om',                  [PenawaranController::class, 'indexForOperationalManager']);
    Route::patch('penawarans/{id}/verifikasi-om',[PenawaranController::class, 'verifikasiOm']);
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

    Route::apiResource('customer-lcrs', CustomerLcrController::class);
    Route::post('uploads/lcr-image', [CustomerLcrController::class, 'uploadImage']);
    Route::get('customer-lcrs', [CustomerLcrController::class, 'indexLogistik'])->name('lcr.index');
    Route::patch('customer-lcrs/{customerLcr}/set-flag',  [CustomerLcrController::class, 'setFlag']);
    Route::patch('customer-lcrs/{customerLcr}/reset-flag', [CustomerLcrController::class, 'resetFlag']);
    Route::get('logistik/customer-lcrs/{id}', [CustomerLcrController::class, 'showLogistik']);

    // Public form (pakai token) - upload internal (auth)
    Route::post('customer-verifications/{customerVerification}/upload', [CustomerVerificationController::class, 'upload']);

    // Resource CustomerVerification (internal)
    Route::apiResource('customer-verifications', CustomerVerificationController::class);

    // ===== Review (Marketing/Finance) – umum =====
    Route::get('review/customer-verifications/stats', [CustomerVerificationController::class, 'reviewStats']);
    Route::get('review/customer-verifications',       [CustomerVerificationController::class, 'reviewIndex']);
    Route::get   ('review/customer-verifications/{id}',               [CustomerVerificationController::class, 'reviewShow'])->whereNumber('id');
    Route::patch ('review/customer-verifications/{id}/review-data',   [CustomerVerificationController::class, 'saveReviewData'])->whereNumber('id');
    Route::post  ('review/customer-verifications/{id}/review-upload', [CustomerVerificationController::class, 'uploadReviewFile'])->whereNumber('id');

    Route::patch('customer-verifications/{customerVerification}/set-reviewed', [CustomerVerificationController::class, 'setReviewed']);

    Route::get ('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'getReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review',  [CustomerVerificationController::class, 'saveReview'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/review-attachment', [CustomerVerificationController::class, 'uploadReviewAttachment'])->whereNumber('id');
    Route::delete('review/customer-verifications/{id}/review-attachment/{no}', [CustomerVerificationController::class, 'deleteReviewAttachment'])->whereNumber('id');
    Route::patch('review/customer-verifications/{id}/approve', [CustomerVerificationController::class, 'approve'])->whereNumber('id');

    // ====== ⬇⬇⬇ TAMBAHAN: EVALUATION (COCOK DENGAN FE) ⬇⬇⬇ ======
    Route::get ('review/customer-verifications/{id}/evaluation',            [CustomerVerificationController::class, 'getEvaluation'])->whereNumber('id');
    Route::get('/review/customer-verifications/{id}/admin-evaluation', [CustomerVerificationController::class, 'getAdminEvaluation']);


    Route::post('review/customer-verifications/{id}/evaluation',            [CustomerVerificationController::class, 'saveEvaluation'])->whereNumber('id');
    Route::post('review/customer-verifications/{id}/evaluation-attachment', [CustomerVerificationController::class, 'evaluationUploadFile'])->whereNumber('id');
    // ====== ⬆⬆⬆ TAMBAHAN: EVALUATION (COCOK DENGAN FE) ⬆⬆⬆ ======

    // ===== Admin (biarkan seperti semula) =====
    Route::prefix('review/admin')->group(function () {
        Route::get('/customer-verifications', [CustomerVerificationController::class, 'reviewAdminIndex']);
        Route::get('/customer-verifications/stats', [CustomerVerificationController::class, 'reviewAdminStats']);
        Route::patch('/customer-verifications/{id}/set-disposisi', [CustomerVerificationController::class, 'setDisposisi'])->whereNumber('id');

        // (yang ini biarkan — URL-nya menjadi /api/review/admin/review/customer-verifications/{id}/evaluation)
        Route::prefix('review/customer-verifications')->group(function () {
            Route::get('{id}/evaluation',  [CustomerVerificationController::class, 'getEvaluation'])->whereNumber('id');
            Route::post('{id}/evaluation', [CustomerVerificationController::class, 'saveEvaluation'])->whereNumber('id');
        });

        // Dan alias lain yang sudah ada sebelumnya (tetap dibiarkan)
        Route::get ('/review/customer-verifications/{id}/evaluation',  [CustomerVerificationController::class, 'evaluationShow'])->whereNumber('id');
        Route::post('/review/customer-verifications/{id}/evaluation',  [CustomerVerificationController::class, 'evaluationSave'])->whereNumber('id');
        Route::post('/review/customer-verifications/{id}/evaluation-file', [CustomerVerificationController::class, 'evaluationUploadFile'])->whereNumber('id');
    });

    Route::prefix('review/logistik')->group(function () {
        Route::get('/customer-verifications', [CustomerVerificationController::class, 'reviewLogistikIndex']);
        Route::get('/customer-verifications/stats', [CustomerVerificationController::class, 'reviewLogistikStats']);
        // reuse method setDisposisi yang sudah ada
        Route::patch('/customer-verifications/{id}/set-disposisi', [CustomerVerificationController::class, 'setDisposisi']);
        Route::get('/customer-verifications/{id}',    [CustomerVerificationController::class, 'logistikShow'])->whereNumber('id');
        Route::patch('/customer-verifications/{id}',  [CustomerVerificationController::class, 'logistikSave'])->whereNumber('id');
        Route::patch('customer-verifications/{id}/verify', [CustomerVerificationController::class, 'logistikVerify']);
    });

    Route::prefix('review/bm')->group(function () {
        Route::get   ('customer-verifications',       [CustomerVerificationController::class, 'reviewBmIndex']);
        Route::get   ('customer-verifications/stats', [CustomerVerificationController::class, 'reviewBmStats']);
        Route::patch ('customer-verifications/{id}/set-disposisi', [CustomerVerificationController::class, 'setDisposisi']);
        // simpan verifikasi BM
        Route::patch ('customer-verifications/{id}/verify', [CustomerVerificationController::class, 'bmVerify']);
    });

    Route::prefix('review/om')->group(function () {
        Route::get ('/customer-verifications',        [CustomerVerificationController::class, 'reviewOmIndex']);
        Route::get ('/customer-verifications/stats',  [CustomerVerificationController::class, 'reviewOmStats']);
        Route::patch('/customer-verifications/{id}/verify', [CustomerVerificationController::class, 'omVerify'])->whereNumber('id');
    
        // opsional: kirim balik ke BM atau finalize → gunakan method setDisposisi yang sudah ada
        Route::patch('/customer-verifications/{id}/set-disposisi', [CustomerVerificationController::class, 'setDisposisi'])->whereNumber('id');
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
    Route::get   ('/delivery-plans',              [DeliveryPlanController::class, 'index']);
    Route::get   ('/delivery-plans/{id}',         [DeliveryPlanController::class, 'show']);
    Route::patch ('/delivery-plans/{id}',         [DeliveryPlanController::class, 'update']);
    Route::patch ('/delivery-plans/{id}/volume',  [DeliveryPlanController::class, 'updateVolume']);
    Route::post  ('/delivery-plans/{id}/split',   [DeliveryPlanController::class, 'split']);

    // Simpan ke PR (header + detail)
    Route::post  ('/pr', [PrController::class, 'store']);
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







    // Logout
    Route::post('logout',[AuthController::class,'logout']);
});

// ====== Public (tanpa auth) ======
Route::get('/verify/{token}', [CustomerVerificationController::class, 'showByToken']);
Route::put('/verify/{token}', [CustomerVerificationController::class, 'updateByToken']);
Route::post('/verify/{token}/upload', [CustomerVerificationController::class, 'uploadByToken'])
    ->where('token', '[A-Za-z0-9\-]{10,}');
Route::get('/masters/provinsis',  [ProvinsiController::class,  'publicIndex']);
Route::get('/masters/kabupatens', [KabupatenController::class, 'publicIndex']);
Route::get('/link-customers', [LinkCustomerController::class, 'index']);
Route::post('/link-customers/{customer}/generate', [LinkCustomerController::class, 'generate']);
Route::get('/captcha', [CaptchaController::class, 'generate'])->middleware('throttle:30,1');

// Public PO detail (contoh)
Route::get('public/vendor-pos/{id}', [VendorPoController::class, 'publicShow'])->whereNumber('id');
