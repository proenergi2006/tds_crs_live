<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerContactType;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use App\Models\CustomerLogistik;
use App\Models\CustomerVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitCustomerOnboardingAction
{
    private const CUSTOMERS_IDENTITY_COLUMNS = [
        'company_name', 'company_address', 'phone', 'fax', 'email', 'website',
        'business_type', 'business_type_other', 'ownership_type', 'ownership_type_other',
        'parent_company', 'province_id', 'regency_id', 'district_id', 'village_id',
        'postal_code', 'customer_sub_district', 'customer_village',
        'inco_terms', 'inco_terms_other',
    ];

    public function execute(CustomerVerification $cv, array $data, string $actorName, ?string $ip): void
    {
        DB::transaction(function () use ($cv, $data, $actorName) {
            $this->updateCustomer($cv, $data['identity'] ?? [], $actorName);
            $this->saveRegisteredAddress($cv, $data['identity'] ?? [], $data['registered_address'] ?? []);
            $this->saveInvoiceContact($cv, $data['invoice_contact'] ?? []);
            $this->savePayment($cv, $data['payment'] ?? []);
            $this->saveLogistics($cv, $data['logistics'] ?? [], $actorName);

            $cv->update(['is_submitted' => true]);

            $this->saveDocuments($cv, $data['documents'] ?? []);
        });
    }

    private function updateCustomer(CustomerVerification $cv, array $identity, string $actorName): void
    {
        $update = array_filter(
            array_intersect_key($identity, array_flip(self::CUSTOMERS_IDENTITY_COLUMNS)),
            fn ($value) => $value !== null
        );

        $update['updated_at'] = now();
        $update['updated_by'] = $actorName;
        $update['update_count'] = DB::raw('COALESCE(update_count,0)+1');

        DB::table('customers')->where('id_customer', $cv->id_customer)->update($update);
    }

    private function saveRegisteredAddress(CustomerVerification $cv, array $identity, array $registeredAddress): void
    {
        if (empty($registeredAddress['address_line'])) {
            return;
        }

        CustomerAddress::updateOrCreate(
            ['id_customer' => $cv->id_customer, 'address_type' => CustomerAddressType::RegisteredNpwp],
            [
                'address_line' => $registeredAddress['address_line'],
                'province_id'  => $registeredAddress['province_id'] ?? null,
                'regency_id'   => $registeredAddress['regency_id'] ?? null,
                'district_id'  => $registeredAddress['district_id'] ?? null,
                'village_id'   => $registeredAddress['village_id'] ?? null,
                'postal_code'  => $registeredAddress['postal_code'] ?? $identity['postal_code'] ?? null,
                'is_primary'   => true,
            ]
        );
    }

    private function saveInvoiceContact(CustomerVerification $cv, array $invoiceContact): void
    {
        if (empty($invoiceContact['name'])) {
            return;
        }

        $financeTypeId = CustomerContactType::where('code', 'finance')->value('id_contact_type');

        if (!$financeTypeId) {
            return;
        }

        CustomerContact::updateOrCreate(
            ['id_customer' => $cv->id_customer, 'id_contact_type' => $financeTypeId],
            [
                'full_name' => $invoiceContact['name'],
                'position'  => $invoiceContact['position'] ?? null,
                'phone'     => $invoiceContact['phone'] ?? null,
                'mobile'    => $invoiceContact['mobile'] ?? null,
                'email'     => $invoiceContact['email'] ?? null,
            ]
        );
    }

    private function savePayment(CustomerVerification $cv, array $payment): void
    {
        DB::table('customer_payment')->updateOrInsert(
            ['id_customer' => $cv->id_customer],
            [
                'payment_schedule'       => $payment['schedule'] ?? null,
                'payment_schedule_other' => $payment['schedule_other'] ?? null,
                'payment_method'         => $payment['method'] ?? null,
                'payment_method_other'   => $payment['method_other'] ?? null,
                'invoice'                => $payment['invoice_tax'] ?? false,
                'extra_notes'            => $payment['note'] ?? null,
                'calculate_method'       => $payment['pricing_method'] ?? null,
                'bank_name'              => $payment['bank_name'] ?? null,
                'currency'               => $payment['currency'] ?? null,
                'bank_address'           => $payment['bank_address'] ?? null,
                'account_number'         => $payment['account_number'] ?? null,
                'credit_facility'        => $payment['has_credit'] ?? false,
                'creditor'               => $payment['creditor_name'] ?? null,
                'payment_term'           => $payment['term'] ?? null,
                'payment_term_days'      => $payment['term_days'] ?? null,
                'payment_term_basis'     => $payment['term_basis'] ?? null,
            ]
        );
    }

    private function saveLogistics(CustomerVerification $cv, array $logistics, string $actorName): void
    {
        $logistik = CustomerLogistik::firstOrNew(['id_customer' => $cv->id_customer]);
        $logistik->fill($logistics);

        if (!$logistik->exists) {
            $logistik->created_at = now();
            $logistik->created_by = $actorName;
        }

        $logistik->updated_at = now();
        $logistik->updated_by = $actorName;
        $logistik->save();
    }

    private function saveDocuments(CustomerVerification $cv, array $documents): void
    {
        foreach (['nib', 'npwp', 'sertifikat'] as $code) {
            $file = $documents[$code]['file'] ?? null;

            if ($file instanceof UploadedFile) {
                $this->storeDocument($cv, $code, $file, $documents[$code]['number'] ?? null);
            }
        }

        foreach ($documents['dokumen_lainnya'] ?? [] as $item) {
            $file = $item['file'] ?? null;

            if ($file instanceof UploadedFile) {
                $this->storeDocument($cv, 'dokumen_lainnya', $file, null);
            }
        }
    }

    private function storeDocument(CustomerVerification $cv, string $code, UploadedFile $file, ?string $documentNumber): void
    {
        $typeId = CustomerDocumentType::where('code', $code)->value('id_document_type');

        if (!$typeId) {
            Log::warning("SubmitCustomerOnboardingAction: CustomerDocumentType not found for code '{$code}'.");

            return;
        }

        $path = $file->store("customer_documents/{$cv->id_customer}", 'public');

        CustomerDocument::create([
            'id_customer'      => $cv->id_customer,
            'id_document_type' => $typeId,
            'document_number'  => $documentNumber,
            'file_path'        => $path,
            'file_name'        => $file->getClientOriginalName(),
            'uploaded_at'      => now(),
            'uploaded_by'      => null,
        ]);
    }
}
