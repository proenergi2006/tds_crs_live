<?php

namespace App\Http\Requests\PoCustomer;

use App\Models\PoCustomer;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePoCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_poc'    => 'required|string|max:50',
            'tanggal_poc'  => 'required|date',
            'supply_date'  => 'required|date',
            'volume_poc'   => 'required|integer',
            'produk_poc'   => 'nullable|integer',
            'lampiran_poc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'tipe_bayar'   => ['required', Rule::in(['CBD', 'COD', 'CREDIT'])],
            'termin_hari'  => ['required_if:tipe_bayar,CREDIT', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('tanggal_poc')) {
                return;
            }

            $penawaran = PoCustomer::with('penawaran')->find($this->route('customer_po'))?->penawaran;

            if (! $penawaran || ! $this->filled('tanggal_poc')) {
                return;
            }

            if (! $penawaran->isValidOn(Carbon::parse($this->input('tanggal_poc')))) {
                $validator->errors()->add(
                    'tanggal_poc',
                    "Tanggal PO harus berada dalam masa berlaku penawaran ({$penawaran->validityPeriodLabel()})."
                );
            }
        });
    }
}
