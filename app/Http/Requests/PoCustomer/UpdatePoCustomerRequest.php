<?php

namespace App\Http\Requests\PoCustomer;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\PoCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePoCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $po = $this->boundPoCustomer(['quotation']);

        return ! $po || $po->isWritableBy($this->user());
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
    }

    public function rules(): array
    {
        return [
            'quotation_id' => 'sometimes|integer|exists:quotations,id',
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
            $po = $this->boundPoCustomer(['quotation', 'customer']);

            if (! $po) {
                return;
            }

            $quotation = $po->quotation;

            if ($this->filled('quotation_id') && (int) $this->input('quotation_id') !== (int) $po->quotation_id) {
                if ($validator->errors()->has('quotation_id')) {
                    return;
                }

                $replacement = Quotation::find($this->input('quotation_id'));
                $replacementError = $this->replacementError($po, $replacement, $this->user());

                if ($replacementError) {
                    $validator->errors()->add('quotation_id', $replacementError);

                    return;
                }

                $quotation = $replacement;
            }

            if ($validator->errors()->has('tanggal_poc') || ! $quotation || ! $this->filled('tanggal_poc')) {
                return;
            }

            if (! $quotation->isValidOn(Carbon::parse($this->input('tanggal_poc')))) {
                $validator->errors()->add(
                    'tanggal_poc',
                    "Tanggal PO harus berada dalam masa berlaku penawaran ({$quotation->validityPeriodLabel()})."
                );
            }
        });
    }

    private function boundPoCustomer(array $relations): ?PoCustomer
    {
        $bound = $this->route('customer_po');

        if ($bound instanceof PoCustomer) {
            return $bound->loadMissing($relations);
        }

        return PoCustomer::with($relations)->find($bound);
    }

    private function replacementError(PoCustomer $po, ?Quotation $replacement, User $user): ?string
    {
        if (! $replacement || (int) $replacement->customer_id !== (int) $po->id_customer) {
            return 'Penawaran harus milik customer yang sama dengan PO.';
        }

        if ($replacement->brand !== $po->quotation?->brand) {
            return 'Penawaran pengganti harus dari brand yang sama dengan penawaran PO.';
        }

        if ($user->cant($replacement->brand->viewAnyPermission()) && (int) $replacement->user_id !== (int) $user->id) {
            return 'Penawaran ini bukan milik Anda.';
        }

        if ($replacement->status !== QuotationStatus::Approved) {
            return 'PO Customer hanya bisa dibuat dari Penawaran yang sudah disetujui.';
        }

        if (! $replacement->isValidOn(Carbon::today())) {
            return "Penawaran tidak berlaku hari ini (masa berlaku {$replacement->validityPeriodLabel()}). Gunakan penawaran periode berjalan.";
        }

        if (! $po->customer?->is_verified) {
            return 'PO Customer hanya bisa dibuat untuk customer yang sudah terverifikasi (KYC).';
        }

        return null;
    }
}
