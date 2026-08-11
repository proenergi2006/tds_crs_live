<?php

namespace App\Actions\Penawaran\Concerns;

use App\Models\PenawaranProenergi;
use Illuminate\Database\Eloquent\Model;

trait ResolvesApprovalTemplate
{
    private function templateCodeFor(Model $penawaran): string
    {
        return $penawaran instanceof PenawaranProenergi ? 'penawaran_proenergi' : 'penawaran_tds';
    }
}
