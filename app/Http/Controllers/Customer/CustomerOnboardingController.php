<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\SubmitCustomerOnboardingAction;
use App\Enums\CustomerAddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SubmitCustomerOnboardingRequest;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use Illuminate\Support\Facades\Storage;

class CustomerOnboardingController extends Controller
{
    private const REQUIRED_ONBOARDING_DOCUMENT_CODES = ['nib', 'npwp'];

    public function show(string $token)
    {
        $customer = Customer::with(['addresses', 'contacts', 'payment', 'logistik', 'documents.documentType', 'latestVerification'])
            ->where('onboarding_token', $token)
            ->firstOrFail();

        $onboardingDocumentTypes = CustomerDocumentType::where('category', 'onboarding')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerDocumentType $type) => [
                'code'     => $type->code,
                'name'     => $type->name,
                'required' => in_array($type->code, self::REQUIRED_ONBOARDING_DOCUMENT_CODES, true),
            ])
            ->values()
            ->all();

        $isLocked = $customer->latestVerification?->is_submitted === true;
        $isExpired = $customer->token_expired_at !== null && $customer->token_expired_at->lte(now());
        $status = $isLocked ? 'used' : ($isExpired ? 'expired' : 'active');

        $registeredAddress = $customer->addresses
            ->firstWhere('address_type', CustomerAddressType::RegisteredNpwp);

        // head office address dibaca dari baris head_office, bukan kolom customers yang udah gak diupdate lagi
        $headOfficeAddress = $customer->addresses->firstWhere('address_type', CustomerAddressType::HeadOffice);

        return response()->json([
            'status' => $status,
            'customer' => [
                'company_name'          => $customer->company_name,
                'company_address'       => $headOfficeAddress?->address_line,
                'phone'                 => $customer->phone,
                'fax'                   => $customer->fax,
                'email'                 => $customer->email,
                'website'               => $customer->website,
                'business_type'         => $customer->business_type,
                'business_type_other'   => $customer->business_type_other,
                'ownership_type'        => $customer->ownership_type,
                'ownership_type_other'  => $customer->ownership_type_other,
                'parent_company'        => $customer->parent_company,
                'province_id'           => $headOfficeAddress?->province_id,
                'regency_id'            => $headOfficeAddress?->regency_id,
                'district_id'           => $headOfficeAddress?->district_id,
                'village_id'            => $headOfficeAddress?->village_id,
                'postal_code'           => $headOfficeAddress?->postal_code,
                'customer_sub_district' => null,
                'customer_village'      => null,
                'inco_terms'            => $customer->inco_terms?->value,
                'inco_terms_other'      => $customer->inco_terms_other,
            ],
            'registered_address' => $registeredAddress ? [
                'address_line' => $registeredAddress->address_line,
                'province_id'  => $registeredAddress->province_id,
                'regency_id'   => $registeredAddress->regency_id,
                'district_id'  => $registeredAddress->district_id,
                'village_id'   => $registeredAddress->village_id,
                'postal_code'  => $registeredAddress->postal_code,
            ] : null,
            'contacts' => $customer->contacts->map(fn (CustomerContact $c) => [
                'id'        => $c->id_contact,
                'full_name' => $c->full_name,
                'position'  => $c->position,
                'phone'     => $c->phone,
                'mobile'    => $c->mobile,
                'email'     => $c->email,
            ])->values()->all(),
            'payment' => $customer->payment ? [
                'schedule'       => $customer->payment->payment_schedule,
                'schedule_other' => $customer->payment->payment_schedule_other,
                'method'         => $customer->payment->payment_method,
                'method_other'   => $customer->payment->payment_method_other,
                'invoice_tax'    => $customer->payment->invoice,
                'note'           => $customer->payment->extra_notes,
                'pricing_method' => $customer->payment->calculate_method,
                'bank_name'      => $customer->payment->bank_name,
                'currency'       => $customer->payment->currency,
                'bank_address'   => $customer->payment->bank_address,
                'account_number' => $customer->payment->account_number,
                'has_credit'     => $customer->payment->credit_facility,
                'creditor_name'  => $customer->payment->creditor,
                'term'           => $customer->payment->payment_term,
                'term_days'      => $customer->payment->payment_term_days,
                'term_basis'     => $customer->payment->payment_term_basis,
            ] : null,
            'logistics' => $customer->logistik ? [
                'site_environment'          => $customer->logistik->site_environment,
                'site_environment_other'    => $customer->logistik->site_environment_other,
                'site_environment_notes'    => $customer->logistik->site_environment_notes,
                'storage_type'              => $customer->logistik->storage_type,
                'storage_type_other'        => $customer->logistik->storage_type_other,
                'storage_notes'             => $customer->logistik->storage_notes,
                'operating_hours'           => $customer->logistik->operating_hours,
                'operating_hours_other'     => $customer->logistik->operating_hours_other,
                'quality_checking_method'   => $customer->logistik->quality_checking_method,
                'quality_checking_notes'    => $customer->logistik->quality_checking_notes,
                'quantity_checking_method'  => $customer->logistik->quantity_checking_method,
                'quantity_checking_notes'   => $customer->logistik->quantity_checking_notes,
                'max_truck_capacity_min'    => $customer->logistik->max_truck_capacity_min,
                'max_truck_capacity_max'    => $customer->logistik->max_truck_capacity_max,
                'supports_vessel_delivery'  => $customer->logistik->supports_vessel_delivery,
                'product_notes'             => $customer->logistik->product_notes,
                'estimated_monthly_volume'  => $customer->logistik->estimated_monthly_volume,
                'operational_hour_from'     => $customer->logistik->operational_hour_from,
                'operational_hour_to'       => $customer->logistik->operational_hour_to,
            ] : null,
            'documents' => [
                'nib'             => $this->formatDocuments($customer, 'nib'),
                'npwp'            => $this->formatDocuments($customer, 'npwp'),
                'dokumen_lainnya' => $this->formatFreeFormDocuments($customer),
            ],
            'document_types' => $onboardingDocumentTypes,
        ]);
    }

    public function update(SubmitCustomerOnboardingRequest $request, string $token)
    {
        $customer = Customer::where('onboarding_token', $token)->firstOrFail();

        if ($customer->latestVerification?->is_submitted === true) {
            return response()->json(['message' => 'Data onboarding ini sudah masuk proses verifikasi dan tidak bisa diubah lagi.'], 409);
        }

        $isExpired = $customer->token_expired_at !== null && $customer->token_expired_at->lte(now());

        if ($isExpired) {
            return response()->json(['message' => 'Token onboarding sudah kedaluwarsa.'], 404);
        }

        app(SubmitCustomerOnboardingAction::class)->execute(
            $customer,
            $request->validated(),
            $request->input('agreement.updated_by'),
            $request->ip()
        );

        return response()->json(['message' => 'Data onboarding berhasil disimpan.']);
    }

    private function formatDocuments(Customer $customer, string $code): array
    {
        return $customer->documents
            ->filter(fn (CustomerDocument $document) => $document->documentType?->code === $code)
            ->map(fn (CustomerDocument $document) => [
                'id'              => $document->id_document,
                'name'            => $document->file_name,
                'url'             => Storage::disk('public')->url($document->file_path),
                'document_number' => $document->document_number,
            ])
            ->values()
            ->all();
    }

    // dokumen bebas Onboarding gak punya CustomerDocumentType, satu-satunya penanda saat ini id_document_type NULL
    private function formatFreeFormDocuments(Customer $customer): array
    {
        return $customer->documents
            ->filter(fn (CustomerDocument $document) => $document->id_document_type === null)
            ->map(fn (CustomerDocument $document) => [
                'id'    => $document->id_document,
                'name'  => $document->file_name,
                'url'   => Storage::disk('public')->url($document->file_path),
                'label' => $document->document_name,
            ])
            ->values()
            ->all();
    }
}
