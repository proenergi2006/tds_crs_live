<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    public function generate(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $google2fa = new Google2FA();

        // 1. Buat secret baru
        $secret = $google2fa->generateSecretKey();

        // (optional) simpan sementara ke user
        $user->two_factor_secret = $secret;
        $user->save();

        // 2. Buat URL untuk QR code (untuk di–scan Google Authenticator)
        $qrUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // 3. Render QR code ke SVG, lalu encode base64
        $svg = QrCode::format('svg')->size(200)->generate($qrUrl);
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return response()->json([
          'secret' => $secret,
          'qrCode' => $dataUri,
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate([
          'code' => 'required|digits:6',
        ]);
        $user = $request->user();
        $google2fa = new Google2FA();

        // verifikasi kode 6 digit
        if (! $google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            return response()->json(['message'=>'Invalid code'], 422);
        }

        // kode valid → 2FA sudah aktifkan
        return response()->json(['message'=>'2FA enabled']);
    }

    public function disable(Request $request)
    {
        $request->validate([
          'code' => 'required|digits:6',
        ]);
        $user = $request->user();
        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            return response()->json(['message'=>'Invalid code'], 422);
        }

        // reset secret
        $user->two_factor_secret = null;
        $user->save();

        return response()->json(['message'=>'2FA disabled']);
    }
}
