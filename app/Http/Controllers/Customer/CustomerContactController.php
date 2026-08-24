<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerContactRequest;
use App\Http\Requests\Customer\UpdateCustomerContactRequest;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Http\Request;

class CustomerContactController extends Controller
{
    public function index(Request $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $customer->id_user === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $contacts = $customer->contacts()
            ->orderBy('id_contact')
            ->get();

        return response()->json(
            $contacts->map(fn (CustomerContact $contact) => $this->formatContact($contact))->values()
        );
    }

    public function store(StoreCustomerContactRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $contact = CustomerContact::create([
            'id_customer' => $customer->id_customer,
            'id_lcr'      => $data['id_lcr'] ?? null,
            'full_name'   => $data['full_name'],
            'position'    => $data['position'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'mobile'      => $data['mobile'] ?? null,
            'email'       => $data['email'] ?? null,
        ]);

        return response()->json($this->formatContact($contact), 201);
    }

    public function update(UpdateCustomerContactRequest $request, Customer $customer, CustomerContact $contact)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($contact->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Kontak tidak ditemukan untuk customer ini.'], 404);
        }

        $data = $request->validated();

        $contact->update([
            'id_lcr'    => $data['id_lcr'] ?? null,
            'full_name' => $data['full_name'],
            'position'  => $data['position'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'mobile'    => $data['mobile'] ?? null,
            'email'     => $data['email'] ?? null,
        ]);

        return response()->json($this->formatContact($contact->fresh()));
    }

    public function destroy(Request $request, Customer $customer, CustomerContact $contact)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($contact->id_customer !== $customer->id_customer) {
            return response()->json(['message' => 'Kontak tidak ditemukan untuk customer ini.'], 404);
        }

        $contact->delete();

        return response()->json(null, 204);
    }

    private function formatContact(CustomerContact $contact): array
    {
        // PK tabel customer_contacts adalah id_contact, jadi $contact->id selalu null.
        return [
            'id'          => $contact->id_contact,
            'id_customer' => $contact->id_customer,
            'id_lcr'      => $contact->id_lcr,
            'full_name'   => $contact->full_name,
            'position'    => $contact->position,
            'phone'       => $contact->phone,
            'mobile'      => $contact->mobile,
            'email'       => $contact->email,
            'created_at' => optional($contact->created_at)->toISOString(),
            'updated_at' => optional($contact->updated_at)->toISOString(),
        ];
    }
}
