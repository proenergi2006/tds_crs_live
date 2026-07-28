<?php

namespace App\Enums;

/**
 * Kode pertanyaan untuk entry `review_answers` (`{question_code, question,
 * answer}`) di `customer_review`. Mengikuti nomor pertanyaan form asli yang
 * sebelumnya dipetakan ke kolom bernama (`jenis_asset`, dst) di migration
 * lama -- hanya kode yang sudah punya nomor pertanyaan jelas yang masuk di
 * sini, `review1`-`review16` generik tidak pernah punya penomoran form yang
 * dikonfirmasi sehingga tidak dipaksakan ke enum ini.
 */
enum CustomerReviewQuestionCode: string
{
    case JenisAsset             = '2.5';
    case KelengkapanDokTagihan  = '2.6';
    case AlurProsesPeriksaan    = '2.7';
    case JadwalPenerimaan       = '2.8';
    case BackgroundBisnis       = '2.11';
    case LokasiDepo             = '2.12';
    case OpportunityBisnis      = '2.13';

    public function question(): string
    {
        return match ($this) {
            self::JenisAsset            => 'Jenis Aset',
            self::KelengkapanDokTagihan => 'Kelengkapan Dokumen Tagihan',
            self::AlurProsesPeriksaan   => 'Alur Proses Pemeriksaan',
            self::JadwalPenerimaan      => 'Jadwal Penerimaan',
            self::BackgroundBisnis      => 'Background Bisnis',
            self::LokasiDepo            => 'Lokasi Depo',
            self::OpportunityBisnis     => 'Opportunity Bisnis',
        };
    }
}
