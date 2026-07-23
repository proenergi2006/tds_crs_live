<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerAddressRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    private const RELATIONS = ['province', 'regency', 'district', 'village'];

    public function index(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $addresses = $customer->addresses()
            ->with(self::RELATIONS)
            ->orderBy('address_type')
            ->orderByDesc('is_primary')
            ->get();

        return response()->json(
            $addresses->map(fn (CustomerAddress $address) => $this->formatAddress($address))->values()
        );
    }

    public function store(StoreCustomerAddressRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $address = DB::transaction(function () use ($data, $customer) {
            if ($data['is_primary'] ?? false) {
                $this->unsetOtherPrimaries($customer, $data['address_type']);
            }

            return CustomerAddress::create([
                'id_customer'  => $customer->id_customer,
                'address_type' => $data['address_type'],
                'address_line' => $data['address_line'],
                'province_id'  => $data['province_id'] ?? null,
                'regency_id'   => $data['regency_id'] ?? null,
                'district_id'  => $data['district_id'] ?? null,
                'village_id'   => $data['village_id'] ?? null,
                'postal_code'  => $data['postal_code'] ?? null,
                'is_primary'   => $data['is_primary'] ?? false,
            ]);
        });

        $address->load(self::RELATIONS);

        return response()->json($this->formatAddress($address), 201);
    }

    public function update(UpdateCustomerAddressRequest $request, Customer $customer, CustomerAddress $address)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($address->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Alamat tidak ditemukan untuk customer ini.'], 404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $customer, $address) {
            if ($data['is_primary'] ?? false) {
                $this->unsetOtherPrimaries($customer, $data['address_type'], $address->id);
            }

            $address->update([
                'address_type' => $data['address_type'],
                'address_line' => $data['address_line'],
                'province_id'  => $data['province_id'] ?? null,
                'regency_id'   => $data['regency_id'] ?? null,
                'district_id'  => $data['district_id'] ?? null,
                'village_id'   => $data['village_id'] ?? null,
                'postal_code'  => $data['postal_code'] ?? null,
                'is_primary'   => $data['is_primary'] ?? false,
            ]);
        });

        return response()->json($this->formatAddress($address->fresh(self::RELATIONS)));
    }

    public function destroy(Request $request, Customer $customer, CustomerAddress $address)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($address->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Alamat tidak ditemukan untuk customer ini.'], 404);
        }

        $address->delete();

        return response()->json(null, 204);
    }

    /**
     * Pastikan cuma 1 row `is_primary=true` per (id_customer, address_type) --
     * dipanggil sebelum insert/update row yang di-set primary baru.
     */
    private function unsetOtherPrimaries(Customer $customer, string $addressType, ?int $exceptId = null): void
    {
        $customer->addresses()
            ->where('address_type', $addressType)
            ->where('is_primary', true)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_primary' => false]);
    }

    private function formatAddress(CustomerAddress $address): array
    {
        return [
            'id'           => $address->id,
            'id_customer'  => $address->id_customer,
            'address_type' => $address->address_type->value,
            'address_type_label' => $address->address_type->label(),
            'address_line' => $address->address_line,
            'province_id'  => $address->province_id,
            'province'     => $address->province ? [
                'id'   => $address->province->id,
                'name' => $address->province->name,
            ] : null,
            'regency_id' => $address->regency_id,
            'regency'    => $address->regency ? [
                'id'   => $address->regency->id,
                'name' => $address->regency->name,
            ] : null,
            'district_id' => $address->district_id,
            'district'    => $address->district ? [
                'id'   => $address->district->id,
                'name' => $address->district->name,
            ] : null,
            'village_id' => $address->village_id,
            'village'    => $address->village ? [
                'id'   => $address->village->id,
                'name' => $address->village->name,
            ] : null,
            'postal_code' => $address->postal_code,
            'is_primary'  => $address->is_primary,
            'created_at'  => optional($address->created_at)->toISOString(),
            'updated_at'  => optional($address->updated_at)->toISOString(),
        ];
    }
}
