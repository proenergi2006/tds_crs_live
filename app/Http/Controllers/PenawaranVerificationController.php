<?php

namespace App\Http\Controllers;

use App\Actions\Penawaran\ResolvePenawaranVerificationAction;
use Illuminate\Http\Request;

class PenawaranVerificationController extends Controller
{
    public function __construct(private readonly ResolvePenawaranVerificationAction $action)
    {
    }

    // publik tanpa auth buat scan QR -- whitelist manual aja, jangan return model mentah (ada kolom harga/margin)
    public function show(Request $request, string $token)
    {
        $result = $this->action->execute($token);

        if ($result === null) {
            abort(404);
        }

        return response()->json($result);
    }
}
