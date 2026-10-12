<?php

namespace App\Http\Requests\Quotation;

use App\Actions\Quotation\UpdateQuotationNonCommercialDetailsAction;
use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Quotation::where('brand', $this->route('brand'))
            ->findOrFail($this->route('id'))
            ->isManagedBy($this->user());
    }

    public function rules(): array
    {
        $customerId = Quotation::where('brand', $this->route('brand'))->whereKey($this->route('id'))->value('customer_id');

        return [
            'customer_contact_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('customer_contacts', 'id_contact')->where(
                    fn ($query) => $query->where('id_customer', $customerId)
                ),
            ],
            'lokasi_pengiriman' => 'sometimes|nullable|string|max:255',
            'keterangan'        => 'sometimes|nullable|string',
            'catatan'           => 'sometimes|nullable|string',
            'syarat_ketentuan'  => 'sometimes|nullable|string',
            'lampiran_tambahan' => 'sometimes|nullable|string',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $submitted = array_keys($this->all());

            foreach (array_diff($submitted, UpdateQuotationNonCommercialDetailsAction::EDITABLE_FIELDS) as $field) {
                $validator->errors()->add($field, 'Field ini tidak boleh diubah setelah penawaran diajukan.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_contact_id.exists' => 'Kontak tidak termasuk customer penawaran ini.',
        ];
    }
}
