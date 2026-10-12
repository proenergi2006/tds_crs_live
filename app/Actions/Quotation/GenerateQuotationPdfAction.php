<?php

namespace App\Actions\Quotation;

use App\Models\Quotation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class GenerateQuotationPdfAction
{
    public function execute(
        string $brand,
        int $id,
        string $lang,
        bool $priceDetail,
        string $viewPrefix,
        string $logoLeftPath
    ) {
        $with = ['customer.headOfficeAddress', 'customerContact', 'branch', 'items.product.ukuran', 'user.primaryRole'];
        $quotation = Quotation::where('brand', $brand)->with($with)->findOrFail($id);

        $isPolimer = $quotation->items()->whereHas('product', fn ($q) => $q->where('id_jenis', 11))->exists();

        $u = $quotation->user;
        if (!$u && !empty($quotation->created_by)) {
            $u = \App\Models\User::with('primaryRole')->where('name', $quotation->created_by)->first();
        }

        $contact = [
            'name'  => $u?->name ?? ($quotation->kontak_nama ?? 'Robby Pratama Putra'),
            'role'  => $u?->primaryRole?->name ?? 'Project Manager',
            'phone' => $u?->no_telepon ?? ($quotation->kontak_telepon ?? '-'),
            'email' => $u?->email ?? ($quotation->kontak_email ?? '-'),
        ];

        $company = [
            'nama_perusahaan' => config('app.name'),
            'alamat'          => 'Alamat Perusahaan Anda',
            'telepon'         => '021-xxxxxxx',
            'fax'             => '021-xxxxxxx',
            'logo_path'       => null,
        ];

        $view = $lang === 'en' ? "{$viewPrefix}_en" : "{$viewPrefix}_id";

        $qrBase64 = null;

        if (!empty($quotation->token_verifikasi)) {
            $qrPayload = url('/verifikasi-penawaran/' . $quotation->token_verifikasi);

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

        $pdf = \PDF::loadView($view, compact('quotation', 'company', 'contact', 'qrBase64', 'logoLeft', 'logoRight', 'priceDetail', 'isPolimer'))
            ->setPaper('A4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $safeNomor = str_replace(['/', '\\'], '-', $quotation->nomor_penawaran);
        $suffix = $lang === 'en' ? 'EN' : 'ID';
        $fileName = "Quotation-{$safeNomor}-{$suffix}.pdf";

        return $pdf->stream($fileName);
    }
}
