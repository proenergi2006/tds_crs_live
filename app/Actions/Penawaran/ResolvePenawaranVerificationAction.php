<?php

namespace App\Actions\Penawaran;

use App\Models\Penawaran;

class ResolvePenawaranVerificationAction
{
    public function execute(string $token): ?array
    {
        $penawaran = Penawaran::where('token_verifikasi', $token)->with('latestDocumentApproval.steps')->first();

        return $penawaran ? $this->buildResult($penawaran) : null;
    }

    private function buildResult(Penawaran $penawaran): array
    {
        if ($penawaran->status !== 'approved_om') {
            return ['verified' => false];
        }

        return [
            'verified' => true,
            'nomor_penawaran' => $penawaran->nomor_penawaran,
            'status_label' => 'Terverifikasi',
            'tanggal_approval' => $penawaran->actedAtForStep(2),
        ];
    }
}
