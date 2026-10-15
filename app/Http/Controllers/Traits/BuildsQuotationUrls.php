<?php

namespace App\Http\Controllers\Traits;

trait BuildsQuotationUrls
{
    protected function detailUrl(int $quotationId): string
    {
        return url("/quotations/{$quotationId}");
    }

    protected function verificationUrl(int $quotationId): string
    {
        return url("/verification/quotations/{$quotationId}");
    }
}
