<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerLcrVesselQuantityCheckingMethod;
use App\Enums\CustomerLcrVesselType;
use App\Enums\CustomerLcrVesselUnloadingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * `authorize()` selalu true -- ownership check per-row (customer.id_user)
 * dilakukan manual di `CustomerLcrController::store()`, bukan di sini, karena
 * butuh route param `customer` yang FormRequest ini tidak punya akses sebelum
 * route resolve.
 */
class StoreCustomerLcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /* Grup 1: Identitas & info umum */
            'site_name'                 => 'nullable|string|max:255',
            'survey_address'            => 'nullable|string',
            'prov_survey'               => 'nullable|integer',
            'kab_survey'                => 'nullable|integer',
            'survey_date'               => 'nullable|date',
            'surveyor_names'            => 'nullable|array',
            'site_business_type'        => 'nullable|string|max:100',
            'site_business_type_other'  => 'nullable|string',
            'site_environment'          => 'nullable|string|max:100',
            'site_environment_other'    => 'nullable|string|max:100',
            'site_environment_notes'    => 'nullable|string',
            'competitors'               => 'nullable|array',
            'operating_hours'           => 'nullable|array',
            'product_volume'            => 'nullable|array',
            'survey_notes'              => 'nullable|string',
            'picustomer'                => 'nullable|array',
            'website'                   => 'nullable|string|max:191',
            'telp_survey'               => 'nullable|string|max:50',
            'fax_survey'                => 'nullable|string|max:50',
            'id_wilayah'                => 'nullable|integer',
            'id_wil_oa'                 => 'nullable|integer',

            /* Grup 2: Akses & rute */
            'max_truck_capacity_min' => 'nullable|numeric|min:0',
            'max_truck_capacity_max' => 'nullable|numeric|min:0',
            'access_notes'           => 'nullable|string',
            'route_costs'            => 'nullable|array',
            'route_costs.*.cost_type'=> 'required|string|max:100',
            'route_costs.*.amount'   => 'nullable|numeric',
            'route_costs.*.notes'    => 'nullable|string',
            'distance_from_depot'    => 'nullable|string|max:50',
            'road_condition_photos'  => 'nullable|array',
            ...$this->mediaRules('road_condition_photos'),
            'min_vol_kirim'          => 'nullable|string|max:50',
            'rute_lokasi'            => 'nullable|string',
            'note_lokasi'            => 'nullable|string',

            /* Grup 3: Layout & unloading truk */
            'site_layout_photos'      => 'nullable|array',
            ...$this->mediaRules('site_layout_photos'),
            'unloading_method'        => 'nullable|string|max:100',
            'max_trucks_per_day'      => 'nullable|integer|min:0',
            'unloading_layout_photos' => 'nullable|array',
            ...$this->mediaRules('unloading_layout_photos'),
            'unloading_notes'         => 'nullable|string',

            /* Grup 4: Penyimpanan */
            'storage_type'            => 'nullable|string|max:100',
            'storage_type_other'      => 'nullable|string|max:100',
            'storage_capacity'        => 'nullable|string|max:100',
            'storage_notes'           => 'nullable|string',
            'storage_facility_photos' => 'nullable|array',
            ...$this->mediaRules('storage_facility_photos'),

            /* Grup 5: Verifikasi quality/quantity */
            'quality_checking_method'     => 'nullable|string|max:100',
            'quality_checking_notes'      => 'nullable|string',
            'quantity_checking_method'    => 'nullable|string|max:100',
            'quantity_checking_notes'     => 'nullable|string',
            'measurement_evidence_photos' => 'nullable|array',
            ...$this->mediaRules('measurement_evidence_photos'),

            /* Grup 6: Vessel/Jetty */
            'supports_vessel_delivery'        => 'nullable|boolean',
            'vessel_type'                     => ['nullable', new Enum(CustomerLcrVesselType::class)],
            'vessel_cargo_capacity'           => 'nullable|string|max:100',
            'vessel_unloading_method'         => ['nullable', new Enum(CustomerLcrVesselUnloadingMethod::class)],
            'vessel_quantity_checking_method' => ['nullable', new Enum(CustomerLcrVesselQuantityCheckingMethod::class)],
            'vessel_quantity_checking_notes'  => 'nullable|string',
            'vessel_quality_checking_method'  => 'nullable|string|max:100',
            'vessel_quality_checking_notes'   => 'nullable|string',
            'vessel_layout_photos'            => 'nullable|array',
            ...$this->mediaRules('vessel_layout_photos'),
            'jetty_type'             => 'nullable|string|max:100',
            'max_loa'                => 'nullable|numeric|min:0',
            'min_pbl'                => 'nullable|numeric|min:0',
            'draft_lws'              => 'nullable|numeric|min:0',
            'jetty_capacity_dwt'     => 'nullable|numeric|min:0',
            'jetty_permit_info'      => 'nullable|string',
            'document_requirements'  => 'nullable|string',

            /* Grup 7: Foto lain & lokasi */
            'company_office_photos' => 'nullable|array',
            ...$this->mediaRules('company_office_photos'),
            'additional_photos'     => 'nullable|array',
            ...$this->mediaRules('additional_photos'),
            'latitude_lokasi'       => 'nullable|numeric',
            'longitude_lokasi'      => 'nullable|numeric',
            'link_google_maps'      => 'nullable|string',
        ];
    }

    /**
     * Rules seragam untuk kolom media (json array of {path,url,caption}).
     */
    private function mediaRules(string $field): array
    {
        return [
            "{$field}.*.path"    => 'required|string',
            "{$field}.*.url"     => 'required|string',
            "{$field}.*.caption" => 'nullable|string|max:255',
        ];
    }
}
