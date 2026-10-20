<?php

namespace App\Actions\Quotation;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;
use NumberFormatter;
use PDF;

class GenerateQuotationPdfAction
{
    public function __construct(private readonly ResolveQuotationSignatoryAction $resolveSignatory)
    {
    }

    public function execute(Quotation $quotation, string $lang, bool $priceDetail, string $version = 'v2'): Response
    {
        return $version === 'v2'
            ? $this->renderV2($quotation, $lang)
            : $this->renderV1($quotation, $lang, $priceDetail);
    }

    private function renderV1(Quotation $quotation, string $lang, bool $priceDetail): Response
    {
        $quotation->loadMissing(['customer.headOfficeAddress', 'customerContact', 'branch', 'items.product.ukuran', 'user.primaryRole']);

        $isPolimer = $quotation->isPolimer();
        $viewPrefix = $quotation->brand->pdfViewPrefix();

        $u = $this->resolveCreator($quotation);

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

        $qrBase64 = ! empty($quotation->token_verifikasi)
            ? $this->qrDataUri($quotation->token_verifikasi)
            : null;

        $logoLeft = $this->imageDataUri($quotation->brand->logoPath());
        $logoRight = $this->imageDataUri(public_path('images/logo-crs.png'));

        $pdf = PDF::loadView($view, compact('quotation', 'company', 'contact', 'qrBase64', 'logoLeft', 'logoRight', 'priceDetail', 'isPolimer'))
            ->setPaper('A4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $safeNomor = str_replace(['/', '\\'], '-', $quotation->nomor_penawaran);
        $suffix = $lang === 'en' ? 'EN' : 'ID';
        $fileName = "Quotation-{$safeNomor}-{$suffix}.pdf";

        return $pdf->stream($fileName);
    }

    private function renderV2(Quotation $quotation, string $lang): Response
    {
        $quotation->loadMissing([
            'customer.headOfficeAddress',
            'customerContact',
            'branch',
            'items.product.jenis',
            'items.product.ukuran.satuan',
            'user.primaryRole',
            'latestDocumentApproval.steps.actor.roles',
            'latestDocumentApproval.steps.role',
        ]);

        $isApproved = $quotation->status === QuotationStatus::Approved;
        $issuedAt = $quotation->finalApprovedAt()
            ?? ($quotation->created_at ? Carbon::parse($quotation->created_at) : now());

        $data = [
            'quotation'        => $quotation,
            'lang'             => $lang,
            'brand'            => $quotation->brand,
            'isPolimer'        => $quotation->isPolimer(),
            'isApproved'       => $isApproved,
            'issuedDate'       => $this->formatDate($issuedAt, $lang),
            'validFrom'        => $this->formatDate($quotation->masa_berlaku ? Carbon::parse($quotation->masa_berlaku) : null, $lang),
            'validUntil'       => $this->formatDate($quotation->sampai_dengan ? Carbon::parse($quotation->sampai_dengan) : null, $lang),
            'city'             => $quotation->branch?->nama_cabang ?: null,
            'signatory'        => $this->resolveSignatory->execute($quotation),
            'contact'          => $this->v2Contact($this->resolveCreator($quotation)),
            'qrBase64'         => $isApproved && filled($quotation->token_verifikasi)
                ? $this->qrDataUri($quotation->token_verifikasi)
                : null,
            'logoLeft'         => $this->imageDataUri($quotation->brand->logoPath()),
            'logoRight'        => $this->imageDataUri(public_path('images/logo-crs.png')),
            'amountInWords'    => $this->amountInWords($quotation->grandTotal(), $lang),
            'termsLines'       => $this->termsLines($quotation),
        ];

        $pdf = PDF::loadView('quotation.quotation-document', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $safeNomor = str_replace(['/', '\\'], '-', $quotation->nomor_penawaran);
        $suffix = $lang === 'en' ? 'EN' : 'ID';

        return $pdf->stream("Quotation-{$safeNomor}-{$suffix}.pdf");
    }

    private function resolveCreator(Quotation $quotation): ?User
    {
        $creator = $quotation->user;

        if (! $creator && ! empty($quotation->created_by)) {
            $creator = User::with('primaryRole')->where('name', $quotation->created_by)->first();
        }

        return $creator;
    }

    private function qrDataUri(string $token): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data(url('/verify-quotation/' . $token))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(220)
            ->margin(5)
            ->build();

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    private function imageDataUri(string $path): ?string
    {
        return is_file($path)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($path))
            : null;
    }

    private function formatDate(?CarbonInterface $date, string $lang): ?string
    {
        return $date?->copy()->locale($lang)->translatedFormat('d F Y');
    }

    private function amountInWords(float $amount, string $lang): string
    {
        if (! class_exists(NumberFormatter::class)) {
            return '';
        }

        $formatter = new NumberFormatter($lang === 'en' ? 'en' : 'id', NumberFormatter::SPELLOUT);

        return ucfirst($formatter->format((int) round($amount))) . ' rupiah';
    }

    /**
     * @return string[]
     */
    private function termsLines(Quotation $quotation): array
    {
        return collect(preg_split("/\r\n|\n|\r/", trim((string) $quotation->syarat_ketentuan)))
            ->map(fn (string $line) => trim(preg_replace('/[\t ]+/', ' ', $line)))
            ->filter(fn (string $line) => $line !== '' && $line !== '-')
            ->values()
            ->all();
    }

    /**
     * @return array{name: ?string, role: ?string, phone: ?string, email: ?string}|null
     */
    private function v2Contact(?User $creator): ?array
    {
        if ($creator === null) {
            return null;
        }

        $creator->loadMissing('primaryRole');

        return [
            'name'  => $creator->name,
            'role'  => $creator->primaryRole?->name,
            'phone' => $creator->no_telepon ?: null,
            'email' => $creator->email ?: null,
        ];
    }
}
