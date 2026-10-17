<?php

namespace App\Http\Requests\PoCustomer;

use App\Enums\QuotationStatus;
use App\Http\Requests\PoCustomer\Traits\ValidatesPoCustomerItems;
use App\Models\Customer;
use App\Models\PoCustomer;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePoCustomerRequest extends FormRequest
{
    use ValidatesPoCustomerItems;

    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user->can('create', PoCustomer::class)) {
            return false;
        }

        $quotation = is_numeric($this->input('quotation_id')) ? Quotation::find($this->input('quotation_id')) : null;

        return $quotation === null || $user->can('createFrom', [PoCustomer::class, $quotation]);
    }

    public function rules(): array
    {
        return [
            'id_customer'  => 'required|exists:customers,id_customer',
            'quotation_id' => 'required|integer|exists:quotations,id',
            'nomor_poc'    => 'required|string|max:50',
            'tanggal_poc'  => 'required|date',
            'supply_date'  => 'required|date',
            'tipe_bayar'   => ['required', Rule::in(['CBD', 'COD', 'CREDIT'])],
            'termin_hari'  => ['required_if:tipe_bayar,CREDIT', 'nullable', 'integer', 'min:1'],
            'lampiran_poc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ...$this->itemRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['id_customer', 'quotation_id'])) {
                return;
            }

            $quotation = Quotation::with('items')->find($this->input('quotation_id'));

            if (! $quotation) {
                return;
            }

            $error = $this->firstQuotationError($quotation, ! $validator->errors()->has('tanggal_poc'));

            if ($error) {
                $validator->errors()->add($error[0], $error[1]);

                return;
            }

            if (! $this->hasItemErrors($validator)) {
                $this->validateItems($validator, $quotation);
            }
        });
    }

    private function firstQuotationError(Quotation $quotation, bool $checkPoDate): ?array
    {
        if ((int) $quotation->customer_id !== (int) $this->input('id_customer')) {
            return ['id_customer', 'Customer tidak sesuai dengan penawaran.'];
        }

        if ($this->user()->cannot('view', $quotation)) {
            return ['quotation_id', 'Penawaran ini bukan milik Anda.'];
        }

        if ($quotation->status !== QuotationStatus::Approved) {
            return ['quotation_id', 'PO Customer hanya bisa dibuat dari Penawaran yang sudah disetujui.'];
        }

        if (! $quotation->isValidOn(Carbon::today())) {
            return [
                'quotation_id',
                'Penawaran tidak berlaku hari ini (masa berlaku ' . $quotation->validityPeriodLabel()
                    . '). Gunakan penawaran periode berjalan.'
            ];
        }

        if (! Customer::find($this->input('id_customer'))?->is_verified) {
            return [
                'id_customer',
                'PO Customer hanya bisa dibuat untuk customer yang sudah terverifikasi (KYC).'
            ];
        }

        if ($checkPoDate && $this->filled('tanggal_poc') && ! $quotation->isValidOn(Carbon::parse($this->input('tanggal_poc')))) {
            return [
                'tanggal_poc',
                "Tanggal PO harus berada dalam masa berlaku penawaran ({$quotation->validityPeriodLabel()})."
            ];
        }

        return null;
    }
}
