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
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SubmitCustomerOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agreement'            => ['required', 'array'],
            'agreement.agree'      => ['required', 'accepted'],
            'agreement.updated_by' => ['required', 'string', 'max:100'],

            'identity'                        => ['required', 'array'],
            'identity.company_name'           => ['required', 'string', 'max:255'],
            'identity.company_address'        => ['required', 'string'],
            'identity.phone'                  => ['nullable', 'string', 'max:50'],
            'identity.fax'                    => ['nullable', 'string', 'max:50'],
            'identity.email'                  => ['nullable', 'email'],
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
            'registered_address.address_line'  => ['nullable', 'string'],
            'registered_address.province_id'   => ['nullable', 'string', 'exists:provinces,id'],
            'registered_address.regency_id'    => ['nullable', 'string', 'exists:regencies,id'],
            'registered_address.district_id'   => ['nullable', 'string', 'exists:districts,id'],
            'registered_address.village_id'    => ['nullable', 'string', 'exists:villages,id'],
            'registered_address.postal_code'   => ['nullable', 'string', 'max:20'],

            'invoice_contact'          => ['nullable', 'array'],
            'invoice_contact.name'     => ['nullable', 'string', 'max:150'],
            'invoice_contact.position' => ['nullable', 'string', 'max:100'],
            'invoice_contact.phone'    => ['nullable', 'string', 'max:50'],
            'invoice_contact.mobile'   => ['nullable', 'string', 'max:50'],
            'invoice_contact.email'    => ['nullable', 'email'],

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

            'documents'                       => ['nullable', 'array'],
            'documents.nib.file'              => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.nib.number'            => ['required', 'string', 'max:255'],
            'documents.npwp.file'             => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.npwp.number'           => ['required', 'string', 'max:255'],
            'documents.sertifikat.file'       => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
            'documents.sertifikat.number'     => ['required_with:documents.sertifikat.file', 'nullable', 'string', 'max:255'],
            'documents.dokumen_lainnya'       => ['nullable', 'array'],
            'documents.dokumen_lainnya.*.file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,zip,rar'],
        ];
    }
}
