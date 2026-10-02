<?php

namespace App\Http\Controllers\Customer\Concerns;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;

trait GuardsCustomerEditLock
{
    protected function blockIfCustomerEditLocked(Customer $customer): ?JsonResponse
    {
        if (!$customer->isEditLocked()) {
            return null;
        }

        return response()->json(['message' => $customer->editLockReason()], 409);
    }
}
