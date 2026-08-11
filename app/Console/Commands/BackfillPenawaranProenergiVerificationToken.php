<?php

namespace App\Console\Commands;

use App\Models\PenawaranProenergi;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillPenawaranProenergiVerificationToken extends Command
{
    protected $signature = 'penawaran-proenergi:backfill-verification-token';

    protected $description = 'Generate token_verifikasi untuk penawaran Proenergi approved_om yang belum punya token (backfill data lama).';

    // backfill manual buat penawaran Proenergi approved_om lama yang belum punya token -- idempotent, cuma nyentuh yang null
    public function handle(): int
    {
        $penawarans = PenawaranProenergi::where('status', 'approved_om')
            ->whereNull('token_verifikasi')
            ->get();

        foreach ($penawarans as $penawaran) {
            $penawaran->update(['token_verifikasi' => strtoupper(Str::random(17))]);
        }

        $this->info("{$penawarans->count()} penawaran diperbarui dengan token_verifikasi baru.");

        return self::SUCCESS;
    }
}
