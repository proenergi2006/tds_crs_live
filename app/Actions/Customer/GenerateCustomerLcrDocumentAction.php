<?php

namespace App\Actions\Customer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use App\Models\CustomerLcr;
use App\Models\CustomerVerification;
use Illuminate\Support\Facades\Storage;

class GenerateCustomerLcrDocumentAction
{
    public const PHOTO_CATEGORIES = [
        'road_condition_photos'      => 'lcr_road_condition',
        'site_layout_photos'         => 'lcr_site_layout',
        'unloading_layout_photos'    => 'lcr_unloading_layout',
        'storage_facility_photos'    => 'lcr_storage_facility',
        'measurement_evidence_photos' => 'lcr_measurement_evidence',
        'vessel_layout_photos'       => 'lcr_vessel_layout',
        'company_office_photos'      => 'lcr_company_office',
        'additional_photos'          => 'lcr_additional',
    ];

    public function __construct(private ResolveVerificationSupervisorNameAction $resolveSupervisorName)
    {
    }

    public function execute(CustomerVerification $verification): array
    {
        $customer = $verification->customer->loadMissing('user:id,name');
        $marketingName = $customer->user?->name ?? '-';

        $lcrSites = CustomerLcr::where('id_customer', $verification->id_customer)
            ->with([
                'address.province',
                'address.regency',
                'address.district',
                'address.village',
                'contact',
                'wilayahAngkut.province',
                'wilayahAngkut.regency',
                'latestDocumentApproval.steps.actor:id,name',
            ])
            ->orderBy('id_lcr')
            ->get();

        $photosBySite = $this->buildPhotosBySite($lcrSites);

        $supervisorName = $this->resolveSupervisorName->execute($verification);
        $logisticsReviewerBySite = $this->buildLogisticsReviewerBySite($lcrSites);

        return compact('customer', 'lcrSites', 'photosBySite', 'marketingName', 'supervisorName', 'logisticsReviewerBySite');
    }

    private function buildLogisticsReviewerBySite($lcrSites): array
    {
        $reviewerBySite = [];
        foreach ($lcrSites as $site) {
            $approval = $site->latestDocumentApproval;
            $actorStep = $approval?->status === DocumentApprovalStatus::Approved
                ? $approval->steps->last(fn ($step) => $step->status === DocumentApprovalStepStatus::Approved && $step->actor)
                : null;

            $reviewerBySite[$site->id_lcr] = $actorStep?->actor->name ?? '-';
        }

        return $reviewerBySite;
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
