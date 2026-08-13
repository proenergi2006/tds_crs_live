<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Customer\SubmitCustomerOnboardingAction;
use App\Enums\CustomerAddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SubmitCustomerOnboardingRequest;
use App\Models\CustomerVerification;

class CustomerOnboardingController extends Controller
{
    public function show(string $token)
    {
        $cv = CustomerVerification::with(['customer.addresses'])
            ->where('verification_token', $token)
            ->firstOrFail();

        if (!$cv->is_active) {
            $status = 'invalidated';
        } elseif ($cv->expired_at !== null && $cv->expired_at->lte(now())) {
            $status = 'expired';
        } elseif ($cv->is_submitted) {
            $status = 'used';
        } else {
            $status = 'active';
        }

        $customer = $cv->customer;
        $registeredAddress = $customer?->addresses
            ->firstWhere('address_type', CustomerAddressType::RegisteredNpwp);

        // Alamat kantor pusat dibaca dari baris head_office, bukan dari kolom customers yang
        // sudah berhenti diperbarui. Sub-district/village lama sudah menyatu ke address_line,
        // jadi tidak dikirim lagi sebagai field terpisah supaya tidak ada dua versi alamat.
        $headOfficeAddress = $customer?->addresses->firstWhere('address_type', CustomerAddressType::HeadOffice);

        return response()->json([
            'status' => $status,
            'customer' => [
                'company_name'          => $customer?->company_name,
                'company_address'       => $headOfficeAddress?->address_line,
                'phone'                 => $customer?->phone,
                'fax'                   => $customer?->fax,
                'email'                 => $customer?->email,
                'website'               => $customer?->website,
                'business_type'         => $customer?->business_type,
                'business_type_other'   => $customer?->business_type_other,
                'ownership_type'        => $customer?->ownership_type,
                'ownership_type_other'  => $customer?->ownership_type_other,
                'parent_company'        => $customer?->parent_company,
                'province_id'           => $headOfficeAddress?->province_id,
                'regency_id'            => $headOfficeAddress?->regency_id,
                'district_id'           => $headOfficeAddress?->district_id,
                'village_id'            => $headOfficeAddress?->village_id,
                'postal_code'           => $headOfficeAddress?->postal_code,
                'customer_sub_district' => null,
                'customer_village'      => null,
                'inco_terms'            => $customer?->inco_terms?->value,
                'inco_terms_other'      => $customer?->inco_terms_other,
            ],
            'registered_address' => $registeredAddress ? [
                'address_line' => $registeredAddress->address_line,
                'province_id'  => $registeredAddress->province_id,
                'regency_id'   => $registeredAddress->regency_id,
                'district_id'  => $registeredAddress->district_id,
                'village_id'   => $registeredAddress->village_id,
                'postal_code'  => $registeredAddress->postal_code,
            ] : null,
        ]);
    }

    public function update(SubmitCustomerOnboardingRequest $request, string $token)
    {
        $cv = CustomerVerification::where('verification_token', $token)->firstOrFail();

        if (!$cv->is_active) {
            return response()->json(['message' => 'Token onboarding tidak ditemukan atau sudah dinonaktifkan.'], 404);
        }

        if ($cv->is_submitted) {
            return response()->json(['message' => 'Data onboarding ini sudah pernah disubmit sebelumnya.'], 409);
        }

        app(SubmitCustomerOnboardingAction::class)->execute(
            $cv,
            $request->validated(),
            $request->input('agreement.updated_by'),
            $request->ip()
        );

        return response()->json(['message' => 'Data onboarding berhasil disimpan.']);
    }
}
