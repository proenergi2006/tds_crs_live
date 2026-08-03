<?php

namespace App\Enums;

/**
 * Kode pertanyaan untuk entry `review_answers` (`{question_code, question,
 * answer, order, field_type}`) di `customer_review`. Value disimpan sebagai
 * kode string sederhana (`q1`-`q14`), bukan nomor form -- enum ini source of
 * truth untuk pertanyaan, urutan tampil, dan tipe field-nya.
 */
enum CustomerReviewQuestionCode: string
{
    case BisnisSejak                 = 'q1';
    case JumlahCabang                = 'q2';
    case JumlahKaryawan               = 'q3';
    case MitraBisnis                 = 'q4';
    case RataRataHariPembayaran       = 'q5';
    case JenisAset                    = 'q6';
    case KelengkapanDokumenTagihan    = 'q7';
    case AlurProsesPemeriksaan        = 'q8';
    case JadwalPenerimaanPembayaran   = 'q9';
    case AuthorityPembayaran          = 'q10';
    case VendorExisting               = 'q11';
    case HistoricalBisnis             = 'q12';
    case LokasiDepo                   = 'q13';
    case OpportunityBisnis            = 'q14';

    public function question(): string
    {
        return match ($this) {
            self::BisnisSejak                 => 'Sejak kapan perusahaan itu menjalankan bisnisnya?',
            self::JumlahCabang                => 'Jumlah cabang yang dimiliki (sebutkan lokasi kabupaten/kota saja jika ada)',
            self::JumlahKaryawan               => 'Jumlah karyawan saat ini',
            self::MitraBisnis                 => 'Perusahaan tersebut berbisnis/kerjasama dengan siapa saja? (Vendor / customer)',
            self::RataRataHariPembayaran       => 'Berapa lama rata-rata hari penerimaan pembayaran dari pemberi kerja / hasil transaksi tersebut?',
            self::JenisAset                    => 'Jenis Assets yang dimiliki oleh perusahaan? (sebutkan jenis, jumlah, status kepemilikannya dan bukti kepemilikan) — milik/sewa/leasing',
            self::KelengkapanDokumenTagihan    => 'Kelengkapan dokumen tagihan yang dibutuhkan untuk proses pembayaran',
            self::AlurProsesPemeriksaan        => 'Alur proses pemeriksaan/review kelengkapan dokumen dan rata-rata waktu yang dibutuhkan sampai proses pembayaran dilakukan (gambarkan)',
            self::JadwalPenerimaanPembayaran   => 'Apakah customer memiliki jadwal penerimaan invoice & jadwal pembayaran tagihan? (Jika ada, mohon diinformasikan detailnya)',
            self::AuthorityPembayaran          => 'Siapa yang memiliki authority terkait pembayaran yang harus dilakukan? (Nama, Posisi & No. HP)',
            self::VendorExisting               => 'Existing fuel/crushed stone vendor yang melakukan bisnis dengan perusahaan? (Nama, Credit term)',
            self::HistoricalBisnis             => 'Historical/background bisnis yang pernah dimiliki oleh perusahaan/Group tersebut dengan PT. Tri Daya Selaras? (Jika ada)',
            self::LokasiDepo                   => 'Lokasi depo sumber produk (terminal)',
            self::OpportunityBisnis            => 'Opportunity business apa saja yang bisa dilakukan dengan perusahaan itu',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::BisnisSejak                 => 1,
            self::JumlahCabang                => 2,
            self::JumlahKaryawan               => 3,
            self::MitraBisnis                 => 4,
            self::RataRataHariPembayaran       => 5,
            self::JenisAset                    => 6,
            self::KelengkapanDokumenTagihan    => 7,
            self::AlurProsesPemeriksaan        => 8,
            self::JadwalPenerimaanPembayaran   => 9,
            self::AuthorityPembayaran          => 10,
            self::VendorExisting               => 11,
            self::HistoricalBisnis             => 12,
            self::LokasiDepo                   => 13,
            self::OpportunityBisnis            => 14,
        };
    }

    public function fieldType(): string
    {
        return match ($this) {
            self::BisnisSejak,
            self::JumlahCabang,
            self::JumlahKaryawan,
            self::MitraBisnis,
            self::AuthorityPembayaran,
            self::VendorExisting,
            self::LokasiDepo => 'shorttext',
            self::RataRataHariPembayaran,
            self::JenisAset,
            self::KelengkapanDokumenTagihan,
            self::AlurProsesPemeriksaan,
            self::JadwalPenerimaanPembayaran,
            self::HistoricalBisnis,
            self::OpportunityBisnis => 'longtext',
        };
    }
}
