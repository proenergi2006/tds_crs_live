<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerDocument;
use App\Models\CustomerDocumentType;
use App\Models\CustomerLogistik;
use App\Services\CustomerCodeGenerator;
use App\Services\CustomerFileNamingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// head office address ditulis lewat jalur yang sama dengan create/update customer manual, bukan kolom identitas customers
class SubmitCustomerOnboardingAction
{
    private const CUSTOMERS_IDENTITY_COLUMNS = [
        'phone', 'fax', 'email', 'website',
        'business_type', 'business_type_other', 'ownership_type', 'ownership_type_other',
        'parent_company',
        'inco_terms', 'inco_terms_other',
    ];

    public function __construct(private readonly UpsertCustomerAddressAction $upsertAddress)
    {
    }

    public function execute(Customer $customer, array $data, string $actorName, ?string $ip): void
    {
        DB::transaction(function () use ($customer, $data, $actorName) {
            $this->assignCustomerCode($customer);
            $this->updateCustomer($customer, $data['identity'] ?? [], $actorName);
            $this->syncHeadOfficeAddress($customer, $data['identity'] ?? []);
            $this->saveRegisteredAddress($customer, $data['identity'] ?? [], $data['registered_address'] ?? []);
            $this->saveContacts($customer, $data['contacts'] ?? [], $data['remove_contact_ids'] ?? []);
            $this->savePayment($customer, $data['payment'] ?? []);
            $this->saveLogistics($customer, $data['logistics'] ?? [], $actorName);
            $this->saveDocuments($customer, $data['documents'] ?? []);
        });
    }

    // generate customer_code di sini (bukan pas create) biar gak perlu backfill data live yang udah ada tanpa onboarding; guard idempotent
    private function assignCustomerCode(Customer $customer): void
    {
        if (!empty($customer->customer_code)) {
            return;
        }

        $customer->customer_code = CustomerCodeGenerator::generate();
        $customer->save();
    }

    private function updateCustomer(Customer $customer, array $identity, string $actorName): void
    {
        $update = array_filter(
            array_intersect_key($identity, array_flip(self::CUSTOMERS_IDENTITY_COLUMNS)),
            fn ($value) => $value !== null
        );

        $update['updated_at'] = now();
        $update['updated_by'] = $actorName;
        $update['update_count'] = DB::raw('COALESCE(update_count,0)+1');

        DB::table('customers')->where('id_customer', $customer->id_customer)->update($update);
    }

    private function syncHeadOfficeAddress(Customer $customer, array $identity): void
    {
        $address = $identity;
        $address['address_line'] = $identity['company_address'] ?? null;

        $this->upsertAddress->execute($customer->id_customer, CustomerAddressType::HeadOffice, $address);
    }

    private function saveRegisteredAddress(Customer $customer, array $identity, array $registeredAddress): void
    {
        if (empty($registeredAddress['address_line'])) {
            return;
        }

        CustomerAddress::updateOrCreate(
            ['id_customer' => $customer->id_customer, 'address_type' => CustomerAddressType::RegisteredNpwp],
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

    // Delete dulu baru upsert, dua-duanya di-scope id_customer -- payload onboarding datang dari token publik tanpa login.
    private function saveContacts(Customer $customer, array $contacts, array $removeIds): void
    {
        if (!empty($removeIds)) {
            CustomerContact::where('id_customer', $customer->id_customer)
                ->whereIn('id_contact', $removeIds)
                ->delete();
        }

        foreach ($contacts as $item) {
            $attributes = [
                'full_name' => $item['full_name'],
                'position'  => $item['position'] ?? null,
                'phone'     => $item['phone'] ?? null,
                'mobile'    => $item['mobile'] ?? null,
                'email'     => $item['email'] ?? null,
            ];

            if (!empty($item['id'])) {
                CustomerContact::where('id_contact', $item['id'])
                    ->where('id_customer', $customer->id_customer)
                    ->update($attributes);
                continue;
            }

            CustomerContact::create([...$attributes, 'id_customer' => $customer->id_customer]);
        }
    }

    private function savePayment(Customer $customer, array $payment): void
    {
        DB::table('customer_payment')->updateOrInsert(
            ['id_customer' => $customer->id_customer],
            [
                'payment_schedule'       => $payment['schedule'] ?? null,
                'payment_schedule_other' => $payment['schedule_other'] ?? null,
                'payment_method'         => $payment['method'] ?? null,
                'payment_method_other'   => $payment['method_other'] ?? null,
                'invoice'                => $payment['invoice_tax'] ?? false,
                'extra_notes'            => $payment['note'] ?? '',
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

    private function saveLogistics(Customer $customer, array $logistics, string $actorName): void
    {
        $logistik = CustomerLogistik::firstOrNew(['id_customer' => $customer->id_customer]);
        $logistik->fill($logistics);

        if (!$logistik->exists) {
            $logistik->created_at = now();
            $logistik->created_by = $actorName;
        }

        $logistik->updated_at = now();
        $logistik->updated_by = $actorName;
        $logistik->save();
    }

    private function saveDocuments(Customer $customer, array $documents): void
    {
        $this->removeDocuments($customer, $documents['remove_document_ids'] ?? []);

        foreach (['nib', 'npwp'] as $code) {
            $file = $documents[$code]['file'] ?? null;

            if ($file instanceof UploadedFile) {
                $this->storeDocument($customer, $code, $file, $documents[$code]['number'] ?? null);
            }
        }

        foreach ($documents['dokumen_lainnya'] ?? [] as $item) {
            $file = $item['file'] ?? null;
            $label = $item['label'] ?? null;

            if ($file instanceof UploadedFile && $label) {
                $this->storeFreeFormDocument($customer, $label, $file);
            }
        }
    }

    private function storeDocument(Customer $customer, string $code, UploadedFile $file, ?string $documentNumber): void
    {
        $type = CustomerDocumentType::where('code', $code)->first();

        if (!$type) {
            Log::warning("SubmitCustomerOnboardingAction: CustomerDocumentType not found for code '{$code}'.");

            return;
        }

        // ganti dokumen fixed = replace, bukan histori -- hapus row+file lama dulu
        CustomerDocument::where('id_customer', $customer->id_customer)
            ->where('id_document_type', $type->id_document_type)
            ->get()
            ->each(fn (CustomerDocument $old) => $this->deleteDocumentFile($old));

        [$folder, $fileName] = CustomerFileNamingService::build($customer, $type, null, $file->getClientOriginalExtension());
        $path = $file->storeAs($folder, $fileName, 'public');

        CustomerDocument::create([
            'id_customer'      => $customer->id_customer,
            'id_document_type' => $type->id_document_type,
            'document_number'  => $documentNumber,
            'file_path'        => $path,
            'file_name'        => $fileName,
            'uploaded_at'      => now(),
            'uploaded_by'      => null,
        ]);
    }

    private function storeFreeFormDocument(Customer $customer, string $label, UploadedFile $file): void
    {
        [$folder, $fileName] = CustomerFileNamingService::buildFreeForm($customer, $label, $file->getClientOriginalExtension());
        $path = $file->storeAs($folder, $fileName, 'public');

        CustomerDocument::create([
            'id_customer'      => $customer->id_customer,
            'id_document_type' => null,
            'document_name'    => $label,
            'file_path'        => $path,
            'file_name'        => $fileName,
            'uploaded_at'      => now(),
            'uploaded_by'      => null,
        ]);
    }

    private function removeDocuments(Customer $customer, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        // whereNull id_document_type -- cuma dokumen bebas yang boleh dihapus lewat jalur ini, defense in depth dari validasi
        CustomerDocument::where('id_customer', $customer->id_customer)
            ->whereNull('id_document_type')
            ->whereIn('id_document', $ids)
            ->get()
            ->each(fn (CustomerDocument $document) => $this->deleteDocumentFile($document));
    }

    private function deleteDocumentFile(CustomerDocument $document): void
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
    }
}
