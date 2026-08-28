<?php

namespace App\Actions\Penawaran\Concerns;

use App\Enums\PenawaranBrand;
use Illuminate\Database\Eloquent\Model;

trait ResolvesApprovalTemplate
{
    private function templateCodeFor(Model $penawaran): string
    {
        return $penawaran->brand === PenawaranBrand::Proenergi ? 'penawaran_proenergi' : 'penawaran_tds';
    }
}
