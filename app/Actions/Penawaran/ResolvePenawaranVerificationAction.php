<?php

namespace App\Actions\Penawaran;

use App\Models\Penawaran;
use App\Models\PenawaranProenergi;

class ResolvePenawaranVerificationAction
{
    // satu-satunya kode yang tahu soal dua model ini -- kedua controller sengaja gak saling referensi
    public function execute(string $token): ?array
    {
        $penawaran = Penawaran::where('token_verifikasi', $token)->first();

        if ($penawaran) {
            return $this->buildResult($penawaran);
        }

        $penawaranProenergi = PenawaranProenergi::where('token_verifikasi', $token)->first();

        if ($penawaranProenergi) {
            return $this->buildResult($penawaranProenergi);
        }

        return null;
    }

    private function buildResult(Penawaran|PenawaranProenergi $penawaran): array
    {
        if ($penawaran->status !== 'approved_om') {
            return ['verified' => false];
        }

        return [
            'verified' => true,
            'nomor_penawaran' => $penawaran->nomor_penawaran,
            'status_label' => 'Terverifikasi',
            'tanggal_approval' => $penawaran->om_tanggal,
        ];
    }
}
