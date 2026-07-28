<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Customer\MigrateCustomerOwnershipAction;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerMigrationController extends Controller
{
    public function byOwner(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $customers = Customer::where('id_user', $request->user_id)
            ->whereNull('deleted_at')
            ->get(['id_customer', 'company_name']);

        return response()->json(['data' => $customers]);
    }

    public function migrateOwnership(Request $request, MigrateCustomerOwnershipAction $action)
    {
        $validated = $request->validate([
            'customer_ids'   => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['exists:customers,id_customer'],
            'to_user_id'     => ['required', 'exists:users,id'],
        ]);

        $migratedCount = $action->execute(
            $validated['customer_ids'],
            (int) $validated['to_user_id'],
            $request->user()->id
        );

        return response()->json(['migrated_count' => $migratedCount]);
    }
}
