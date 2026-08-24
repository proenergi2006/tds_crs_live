<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerIncoterm;
use App\Enums\CustomerLogistikOperatingHours;
use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerPaymentTermBasis;
use App\Enums\QualityCheckingMethod;
use App\Enums\QuantityCheckingMethod;
use App\Enums\SiteEnvironment;
use App\Enums\StorageType;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SubmitCustomerOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = Customer::where('onboarding_token', $this->route('token'))->value('id_customer');

        $hasExistingDocument = function (string $code) use ($customerId) {
            if (!$customerId) {
                return false;
            }

            return CustomerDocument::where('id_customer', $customerId)
                ->whereHas('documentType', fn ($q) => $q->where('code', $code))
                ->exists();
        };

        return [
            'agreement'            => ['required', 'array'],
            'agreement.agree'      => ['required', 'accepted'],
            'agreement.updated_by' => ['required', 'string', 'max:100'],

            'identity'                        => ['required', 'array'],
            'identity.company_name'           => ['required', 'string', 'max:255'],
            'identity.company_address'        => ['required', 'string'],
            'identity.phone'                  => ['nullable', 'string', 'max:50'],
            'identity.fax'                    => ['nullable', 'string', 'max:50'],
            'identity.email'                  => [
                'nullable',
                'email',
                Rule::unique('customers', 'email')->ignore($customerId, 'id_customer'),
            ],
            'identity.website'                => ['nullable', 'string', 'max:255'],
            'identity.business_type'          => ['nullable', 'string', 'max:100'],
            'identity.business_type_other'    => ['nullable', 'string', 'max:255'],
            'identity.ownership_type'         => ['nullable', 'string', 'max:100'],
            'identity.ownership_type_other'   => ['nullable', 'string', 'max:255'],
            'identity.parent_company'         => ['nullable', 'string', 'max:255'],
            'identity.province_id'            => ['nullable', 'string', 'exists:provinces,id'],
            'identity.regency_id'             => ['nullable', 'string', 'exists:regencies,id'],
            'identity.district_id'            => ['nullable', 'string', 'exists:districts,id'],
            'identity.village_id'             => ['nullable', 'string', 'exists:villages,id'],
            'identity.postal_code'            => ['nullable', 'string', 'max:20'],
            'identity.customer_sub_district'  => ['nullable', 'string', 'max:255'],
            'identity.customer_village'       => ['nullable', 'string', 'max:255'],
            'identity.inco_terms'             => ['nullable', new Enum(CustomerIncoterm::class)],
            'identity.inco_terms_other'       => ['nullable', 'string', 'max:255'],

            'registered_address'               => ['nullable', 'array'],
            'registered_address.address_line'  => ['required', 'string'],
            'registered_address.province_id'   => ['nullable', 'string', 'exists:provinces,id'],
            'registered_address.regency_id'    => ['nullable', 'string', 'exists:regencies,id'],
            'registered_address.district_id'   => ['nullable', 'string', 'exists:districts,id'],
            'registered_address.village_id'    => ['nullable', 'string', 'exists:villages,id'],
            'registered_address.postal_code'   => ['nullable', 'string', 'max:20'],

            'contacts'             => ['required', 'array', 'min:1'],
            'contacts.*.id'        => [
                'nullable', 'integer',
                Rule::exists('customer_contacts', 'id_contact')->where('id_customer', $customerId),
            ],
            'contacts.*.full_name' => ['required', 'string', 'max:150'],
            'contacts.*.position'  => ['required', 'string', 'max:100'],
            'contacts.*.phone'     => ['nullable', 'string', 'max:50', 'required_without_all:contacts.*.mobile,contacts.*.email'],
            'contacts.*.mobile'    => ['nullable', 'string', 'max:50', 'required_without_all:contacts.*.phone,contacts.*.email'],
            'contacts.*.email'     => ['nullable', 'email', 'required_without_all:contacts.*.phone,contacts.*.mobile'],
            'remove_contact_ids'   => ['nullable', 'array'],
            'remove_contact_ids.*' => [
                'integer',
                Rule::exists('customer_contacts', 'id_contact')->where('id_customer', $customerId),
            ],

            'payment'                 => ['nullable', 'array'],
            'payment.schedule'        => ['nullable', 'string', 'max:50'],
            'payment.schedule_other'  => ['nullable', 'string', 'max:255'],
            'payment.method'          => ['nullable', 'string', 'max:50'],
            'payment.method_other'    => ['nullable', 'string', 'max:255'],
            'payment.invoice_tax'     => ['nullable', 'boolean'],
            'payment.note'            => ['nullable', 'string'],
            'payment.pricing_method'  => ['nullable', 'string', 'max:100'],
            'payment.bank_name'       => ['nullable', 'string', 'max:150'],
            'payment.currency'        => ['nullable', 'string', 'max:10'],
            'payment.bank_address'    => ['nullable', 'string'],
            'payment.account_number'  => ['nullable', 'string', 'max:50'],
            'payment.has_credit'      => ['nullable', 'boolean'],
            'payment.creditor_name'   => ['nullable', 'string', 'max:150'],
            'payment.term'            => ['nullable', new Enum(CustomerPaymentTerm::class)],
            'payment.term_days'       => ['nullable', 'integer', 'min:0'],
            'payment.term_basis'      => ['nullable', new Enum(CustomerPaymentTermBasis::class)],

            'logistics'                          => ['nullable', 'array'],
            'logistics.site_environment'         => ['nullable', new Enum(SiteEnvironment::class)],
            'logistics.site_environment_other'   => ['nullable', 'string', 'max:255'],
            'logistics.site_environment_notes'   => ['nullable', 'string'],
            'logistics.storage_type'             => ['nullable', new Enum(StorageType::class)],
            'logistics.storage_type_other'       => ['nullable', 'string', 'max:255'],
            'logistics.storage_notes'            => ['nullable', 'string'],
            'logistics.operating_hours'          => ['nullable', new Enum(CustomerLogistikOperatingHours::class)],
            'logistics.operating_hours_other'    => ['nullable', 'string', 'max:255'],
            'logistics.quality_checking_method'  => ['nullable', new Enum(QualityCheckingMethod::class)],
            'logistics.quality_checking_notes'   => ['nullable', 'string'],
            'logistics.quantity_checking_method' => ['nullable', new Enum(QuantityCheckingMethod::class)],
            'logistics.quantity_checking_notes'  => ['nullable', 'string'],
            'logistics.max_truck_capacity_min'   => ['nullable', 'numeric'],
            'logistics.max_truck_capacity_max'   => ['nullable', 'numeric'],
            'logistics.supports_vessel_delivery' => ['nullable', 'boolean'],
            'logistics.product_notes'            => ['nullable', 'string'],
            'logistics.estimated_monthly_volume' => ['nullable', 'numeric'],
            'logistics.operational_hour_from'    => ['nullable', 'string'],
            'logistics.operational_hour_to'      => ['nullable', 'string'],

            'documents'                         => ['nullable', 'array'],
            'documents.nib.file'                => [Rule::requiredIf(!$hasExistingDocument('nib')), 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.nib.number'              => ['required', 'string', 'max:255'],
            'documents.npwp.file'               => [Rule::requiredIf(!$hasExistingDocument('npwp')), 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.npwp.number'             => ['required', 'string', 'max:255'],
            'documents.dokumen_lainnya'         => ['nullable', 'array'],
            'documents.dokumen_lainnya.*.file'  => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.dokumen_lainnya.*.label' => ['required', 'string', 'max:255'],
            'documents.remove_document_ids'     => ['nullable', 'array'],
            'documents.remove_document_ids.*'   => [
                'integer',
                Rule::exists('customer_documents', 'id_document')
                    ->where('id_customer', $customerId)
                    ->whereNull('id_document_type'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'identity.email.unique' => 'Email ini sudah terdaftar untuk customer lain. Gunakan email yang berbeda.',
            'contacts.*.phone.required_without_all'  => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
            'contacts.*.mobile.required_without_all' => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
            'contacts.*.email.required_without_all'  => 'Minimal salah satu dari Telepon, Mobile, atau Email wajib diisi.',
        ];
    }
}
