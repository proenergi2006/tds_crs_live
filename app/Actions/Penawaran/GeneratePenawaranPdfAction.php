<?php

namespace App\Actions\Penawaran;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class GeneratePenawaranPdfAction
{
    public function execute(
        string $modelClass,
        int $id,
        string $lang,
        bool $priceDetail,
        string $viewPrefix,
        string $logoLeftPath
    ) {
        // alamat customer di surat penawaran diambil dari headOfficeAddress, bukan kolom di tabel customers
        $penawaran = $modelClass::with(['customer.headOfficeAddress', 'cabang', 'items.produk.ukuran', 'user.role'])
            ->findOrFail($id);

        if ($penawaran->status !== 'approved_om') {
            abort(403, 'Preview PDF hanya tersedia setelah penawaran disetujui OM.');
        }

        $u = $penawaran->user;
        if (!$u && !empty($penawaran->created_by)) {
            $u = \App\Models\User::with('role')->where('name', $penawaran->created_by)->first();
        }

        $contact = [
            'name'  => $u?->name ?? ($penawaran->kontak_nama ?? 'Robby Pratama Putra'),
            'role'  => $u?->role?->role_name ?? 'Project Manager',
            'phone' => $u?->no_telepon ?? ($penawaran->kontak_telepon ?? '-'),
            'email' => $u?->email ?? ($penawaran->kontak_email ?? '-'),
        ];

        $company = [
            'nama_perusahaan' => config('app.name'),
            'alamat'          => 'Alamat Perusahaan Anda',
            'telepon'         => '021-xxxxxxx',
            'fax'             => '021-xxxxxxx',
            'logo_path'       => null,
        ];

        $jenis = (int) ($penawaran->jenis_penawaran ?? 1);
        if ($jenis === 2) {
            $view = $lang === 'en' ? 'penawaran.pdf_lub_en' : 'penawaran.pdf_lub_id';
        } else {
            $view = $lang === 'en' ? "{$viewPrefix}_en" : "{$viewPrefix}_id";
        }

        $qrBase64 = null;

        if (!empty($penawaran->token_verifikasi)) {
            $qrPayload = url('/verifikasi-penawaran/' . $penawaran->token_verifikasi);

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrPayload)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(220)
                ->margin(5)
                ->build();

            $qrBase64 = 'data:image/png;base64,' . base64_encode($result->getString());
        }

        $logoRightPath = public_path('images/logo-crs.png');

        $logoLeft = is_file($logoLeftPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoLeftPath))
            : null;

        $logoRight = is_file($logoRightPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoRightPath))
            : null;

        $pdf = \PDF::loadView($view, compact('penawaran', 'company', 'contact', 'qrBase64', 'logoLeft', 'logoRight', 'priceDetail'))
            ->setPaper('A4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $safeNomor = str_replace(['/', '\\'], '-', $penawaran->nomor_penawaran);
        $suffix = $lang === 'en' ? 'EN' : 'ID';

        return $pdf->stream("Quotation-{$safeNomor}-{$suffix}.pdf");
    }
}
