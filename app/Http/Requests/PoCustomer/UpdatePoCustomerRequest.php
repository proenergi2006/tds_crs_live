<?php

namespace App\Http\Requests\PoCustomer;

use App\Models\Penawaran;
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
        $po = PoCustomer::with('penawaran')->find($this->route('customer_po'));

        return ! $po || $po->isWritableBy($this->user());
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
    }

    public function rules(): array
    {
        return [
            'id_penawaran' => 'sometimes|integer|exists:penawarans,id_penawaran',
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
            $po = PoCustomer::with(['penawaran', 'customer'])->find($this->route('customer_po'));

            if (! $po) {
                return;
            }

            $penawaran = $po->penawaran;

            if ($this->filled('id_penawaran') && (int) $this->input('id_penawaran') !== (int) $po->id_penawaran) {
                if ($validator->errors()->has('id_penawaran')) {
                    return;
                }

                $replacement = Penawaran::find($this->input('id_penawaran'));
                $replacementError = $this->replacementError($po, $replacement, $this->user());

                if ($replacementError) {
                    $validator->errors()->add('id_penawaran', $replacementError);

                    return;
                }

                $penawaran = $replacement;
            }

            if ($validator->errors()->has('tanggal_poc') || ! $penawaran || ! $this->filled('tanggal_poc')) {
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

    private function replacementError(PoCustomer $po, ?Penawaran $replacement, User $user): ?string
    {
        if (! $replacement || (int) $replacement->id_customer !== (int) $po->id_customer) {
            return 'Penawaran harus milik customer yang sama dengan PO.';
        }

        if ($user->cant('penawaran.viewAny') && (int) $replacement->user_id !== (int) $user->id) {
            return 'Penawaran ini bukan milik Anda.';
        }

        if ($replacement->status !== 'approved_om') {
            return 'PO Customer hanya bisa dibuat dari Penawaran yang sudah disetujui OM.';
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
