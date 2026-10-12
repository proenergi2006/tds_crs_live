<?php

namespace App\Http\Controllers;

use App\Actions\Quotation\ResolveQuotationVerificationAction;
use Illuminate\Http\Request;

class QuotationVerificationController extends Controller
{
    public function __construct(private readonly ResolveQuotationVerificationAction $action)
    {
    }

    public function show(Request $request, string $token)
    {
        $result = $this->action->execute($token);

        if ($result === null) {
            abort(404);
        }

        return response()->json($result);
    }
}
