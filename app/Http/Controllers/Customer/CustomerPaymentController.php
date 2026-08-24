<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateCustomerPaymentRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;

class CustomerPaymentController extends Controller
{
    public function update(UpdateCustomerPaymentRequest $request, Customer $customer)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($customer->id_user === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $payment = CustomerPayment::updateOrCreate(
            ['id_customer' => $customer->id_customer],
            [
                'payment_schedule'       => $data['schedule'] ?? null,
                'payment_schedule_other' => $data['schedule_other'] ?? null,
                'payment_method'         => $data['method'] ?? null,
                'payment_method_other'   => $data['method_other'] ?? null,
                'invoice'                => $data['invoice_tax'] ?? false,
                'extra_notes'            => $data['note'] ?? '',
                // Operasional saat ini selalu Quotation -- belum bisa diedit user, tunggu kebijakan baru.
                'calculate_method'       => 'Quotation',
                'bank_name'              => $data['bank_name'] ?? null,
                'currency'               => $data['currency'] ?? null,
                'bank_address'           => $data['bank_address'] ?? null,
                'account_number'         => $data['account_number'] ?? null,
                'credit_facility'        => $data['has_credit'] ?? false,
                'creditor'               => $data['creditor_name'] ?? null,
                'payment_term'           => $data['term'] ?? null,
                'payment_term_days'      => $data['term_days'] ?? null,
                'payment_term_basis'     => $data['term_basis'] ?? null,
            ]
        );

        return response()->json($payment);
    }
}
