<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerLcrVesselQuantityCheckingMethod;
use App\Enums\CustomerLcrVesselType;
use App\Enums\CustomerLcrVesselUnloadingMethod;
use App\Enums\QualityCheckingMethod;
use App\Enums\QuantityCheckingMethod;
use App\Enums\SiteEnvironment;
use App\Enums\StorageType;
use App\Enums\VesselQualityCheckingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerLcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name'                 => 'nullable|string|max:255',
            'survey_date'               => 'nullable|date',
            'surveyor_names'            => 'nullable|string',
            'site_business_type'        => 'nullable|string|max:100',
            'site_business_type_other'  => 'nullable|string',
            'site_environment'          => ['nullable', new Enum(SiteEnvironment::class)],
            'site_environment_other'    => 'nullable|string|max:100',
            'site_environment_notes'    => 'nullable|string',
            'competitors'               => 'nullable|string',
            'operating_hours'           => 'nullable|string',
            'product_volume'            => 'nullable|array',
            'survey_notes'              => 'nullable|string',
            'id_wil_oa'                 => 'nullable|integer',

            'max_truck_capacity_min' => 'nullable|numeric|min:0',
            'max_truck_capacity_max' => 'nullable|numeric|min:0',
            'access_notes'           => 'nullable|string',
            'route_costs'            => 'nullable|array',
            'route_costs.*.cost_type'=> 'required|string|max:100',
            'route_costs.*.amount'   => 'nullable|numeric',
            'route_costs.*.notes'    => 'nullable|string',
            'distance_from_depot'    => 'nullable|string|max:50',
            'min_vol_kirim'          => 'nullable|string|max:50',
            'rute_lokasi'            => 'nullable|string',
            'note_lokasi'            => 'nullable|string',

            'unloading_method'        => 'nullable|string|max:100',
            'max_trucks_per_day'      => 'nullable|integer|min:0',
            'unloading_notes'         => 'nullable|string',

            'storage_type'            => ['nullable', new Enum(StorageType::class)],
            'storage_type_other'      => 'nullable|string|max:100',
            'storage_capacity'        => 'nullable|string|max:100',
            'storage_notes'           => 'nullable|string',

            'quality_checking_method'       => 'nullable|array',
            'quality_checking_method.*'     => [new Enum(QualityCheckingMethod::class)],
            'quality_checking_method_other' => 'nullable|string|max:255',
            'quality_checking_notes'        => 'nullable|string',

            'quantity_checking_method'       => 'nullable|array',
            'quantity_checking_method.*'     => [new Enum(QuantityCheckingMethod::class)],
            'quantity_checking_method_other' => 'nullable|string|max:255',
            'quantity_checking_notes'        => 'nullable|string',

            'supports_vessel_delivery'        => 'nullable|boolean',
            'vessel_type'                     => ['nullable', new Enum(CustomerLcrVesselType::class)],
            'vessel_type_other'               => 'nullable|string|max:255',
            'vessel_cargo_capacity'           => 'nullable|string|max:100',
            'vessel_unloading_method'         => ['nullable', new Enum(CustomerLcrVesselUnloadingMethod::class)],
            'vessel_unloading_method_other'   => 'nullable|string|max:255',

            'vessel_quantity_checking_method'       => 'nullable|array',
            'vessel_quantity_checking_method.*'     => [new Enum(CustomerLcrVesselQuantityCheckingMethod::class)],
            'vessel_quantity_checking_method_other' => 'nullable|string|max:255',
            'vessel_quantity_checking_notes'        => 'nullable|string',

            'vessel_quality_checking_method'       => 'nullable|array',
            'vessel_quality_checking_method.*'     => [new Enum(VesselQualityCheckingMethod::class)],
            'vessel_quality_checking_method_other' => 'nullable|string|max:255',
            'vessel_quality_checking_notes'        => 'nullable|string',

            'jetty_type'             => 'nullable|string|max:100',
            'max_loa'                => 'nullable|numeric|min:0',
            'min_pbl'                => 'nullable|numeric|min:0',
            'draft_lws'              => 'nullable|numeric|min:0',
            'jetty_capacity_dwt'     => 'nullable|numeric|min:0',
            'jetty_permit_info'      => 'nullable|string',
            'document_requirements'  => 'nullable|string',

            'latitude'              => 'nullable|numeric',
            'longitude'             => 'nullable|numeric',
            'google_maps_link'      => 'nullable|string',

            'address'                => 'nullable|array',
            'address.address_line'   => 'required_with:address|string',
            'address.province_id'    => 'nullable|string|exists:provinces,id',
            'address.regency_id'     => 'nullable|string|exists:regencies,id',
            'address.district_id'    => 'nullable|string|exists:districts,id',
            'address.village_id'     => 'nullable|string|exists:villages,id',
            'address.postal_code'    => 'nullable|string|max:10',

            'contact'             => 'required|array',
            'contact.full_name'   => 'required|string|max:255',
            'contact.position'    => 'nullable|string|max:255',
            'contact.phone'       => 'nullable|string|max:50',
            'contact.mobile'      => 'nullable|string|max:50',
            'contact.email'       => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required'            => 'Penanggung Jawab (PIC) wajib diisi.',
            'contact.full_name.required'  => 'Nama Penanggung Jawab (PIC) wajib diisi.',
        ];
    }
}
