<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeLegacyQuotationQrFiles extends Command
{
    protected $signature = 'quotations:purge-legacy-qr-files {--force : Hapus file; tanpa opsi ini hanya menampilkan daftar}';

    protected $description = 'Menyapu file QR code penawaran lama (qrcodes/penawaran-*) dari disk public';

    public function handle(): int
    {
        $disk  = Storage::disk('public');
        $files = collect($disk->allFiles('qrcodes'))
            ->filter(fn (string $file) => str_starts_with(basename($file), 'penawaran-'))
            ->values();

        $files->each(fn (string $file) => $this->line($file));

        if (! $this->option('force')) {
            $this->info("Dry-run: {$files->count()} file akan dihapus. Jalankan dengan --force untuk menghapus.");

            return self::SUCCESS;
        }

        $files->each(fn (string $file) => $disk->delete($file));

        $this->info("{$files->count()} file dihapus.");

        return self::SUCCESS;
    }
}
