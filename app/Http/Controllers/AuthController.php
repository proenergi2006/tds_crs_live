<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $creds = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (! Auth::attempt($creds)) {
            return response()->json(['message'=>'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Jika user sudah mengaktifkan 2FA
        if ($user->two_factor_secret) {
            return response()->json([
                'two_factor_required' => true,
                'user_id'             => $user->id,
            ]);
        }

        $token = $user->createToken(
            'api_token',
            ['*'],
            now()->addMinutes(5)
        )->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    public function twoFactor(Request $request)
    {
        $request->validate([
            'user_id' => ['required','integer'],
            'code'    => ['required','digits:6'],
        ]);

        $user = \App\Models\User::find($request->user_id);
        if (! $user || ! $user->two_factor_secret) {
            return response()->json(['message'=>'2FA not enabled'], 422);
        }

        $google2fa = new Google2FA();
        if (! $google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            return response()->json(['message'=>'Invalid 2FA code'], 422);
        }

        // Kode valid → buat token
        $token = $user->createToken('api_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'Logged out']);
    }
}
