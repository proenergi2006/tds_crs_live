<?php

namespace App\Actions\Customer;

use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use App\Models\CustomerLcr;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\Storage;

class GenerateCustomerLcrDocumentAction
{
    private const PHOTO_CATEGORIES = [
        'road_condition_photos'      => 'lcr_road_condition',
        'site_layout_photos'         => 'lcr_site_layout',
        'unloading_layout_photos'    => 'lcr_unloading_layout',
        'storage_facility_photos'    => 'lcr_storage_facility',
        'measurement_evidence_photos' => 'lcr_measurement_evidence',
        'vessel_layout_photos'       => 'lcr_vessel_layout',
        'company_office_photos'      => 'lcr_company_office',
        'additional_photos'          => 'lcr_additional',
    ];

    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer;

        $lcrSites = CustomerLcr::where('id_customer', $verification->id_customer)
            ->with([
                'address.province', 'address.regency', 'address.district', 'address.village',
                'contacts',
                'wilayahAngkut.province', 'wilayahAngkut.regency',
                'latestDocumentApproval',
            ])
            ->orderBy('id_lcr')
            ->get();

        $photosBySite = $this->buildPhotosBySite($lcrSites);

        return compact('customer', 'lcrSites', 'photosBySite');
    }

    private function buildPhotosBySite($lcrSites): array
    {
        $photosBySite = [];
        foreach ($lcrSites as $site) {
            $photosBySite[$site->id_lcr] = array_fill_keys(array_keys(self::PHOTO_CATEGORIES), []);
        }

        if ($lcrSites->isEmpty()) {
            return $photosBySite;
        }

        $documentTypeCodeById = CustomerDocumentType::whereIn('code', array_values(self::PHOTO_CATEGORIES))
            ->pluck('code', 'id_document_type');

        if ($documentTypeCodeById->isEmpty()) {
            return $photosBySite;
        }

        $documents = CustomerDocument::whereIn('id_lcr', $lcrSites->pluck('id_lcr'))
            ->whereIn('id_document_type', $documentTypeCodeById->keys())
            ->orderBy('id_document')
            ->get();

        foreach ($documents as $doc) {
            $code = $documentTypeCodeById[$doc->id_document_type] ?? null;
            $field = $code ? array_search($code, self::PHOTO_CATEGORIES, true) : false;

            if ($field === false || !isset($photosBySite[$doc->id_lcr])) {
                continue;
            }

            $photosBySite[$doc->id_lcr][$field][] = [
                'data_uri'  => $this->resolveImageDataUri($doc->file_path),
                'file_name' => $doc->file_name,
                'notes'     => $doc->notes,
            ];
        }

        return $photosBySite;
    }

    private function resolveImageDataUri(?string $filePath): ?string
    {
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($filePath);
        $mime = mime_content_type($absolutePath) ?: null;

        if (!$mime || !str_starts_with($mime, 'image/')) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }
}
