<?php

namespace App\Http\Requests\Quotation;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\ProductPrice;
use App\Enums\QuotationTransportMode;
use App\Enums\TransportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $brand = $this->route('brand');
        $quotation = Quotation::where('brand', $brand)->findOrFail($this->route('id'));
        $user = $this->user();
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.manage' : 'penawaran.manage';

        return $user->can($permission)
            && (int) $quotation->user_id === (int) $user->id;
    }

    public function rules(): array
    {
        $rules = [
            'customer_id'          => 'required|exists:customers,id_customer',
            'customer_contact_id' => [
                'required',
                'integer',
                Rule::exists('customer_contacts', 'id_contact')->where(
                    fn($query) => $query->where('id_customer', $this->input('customer_id'))
                ),
            ],
            'branch_id'            => 'required|exists:cabangs,id_cabang',
            'price_period_id'      => 'required|integer|exists:price_periods,id',

            'transport_mode'       => ['nullable', Rule::enum(QuotationTransportMode::class)],
            'transports'           => 'nullable|array|max:2',
            'transports.*.transport_type'      => ['required', Rule::enum(TransportType::class)],
            'transports.*.transport_tariff_id' => 'required|integer|exists:transport_tariffs,id',

            'items'                    => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:produks,id_produk',
            'items.*.source_branch_id' => 'required|integer|exists:cabangs,id_cabang',
            'items.*.product_price_id' => 'required|integer',
            'items.*.quantity'         => 'required|numeric|min:0',
            'items.*.unit_price'       => 'required|numeric|gt:0',
            'tipe_pembayaran'      => 'nullable|string|max:100',
            'acuan_pembayaran'     => 'nullable|in:After loading,Before loading,After unloading,Before unloading,After invoice received',
            'dp_persen'            => 'nullable|numeric|min:0|max:100',
            'dp_keterangan'        => 'nullable|string|max:100',
            'repayment_persen'     => 'nullable|numeric|min:0|max:100',
            'repayment_hari'       => 'nullable|numeric|min:0',

            'order_method'         => 'nullable|string|max:100',
            'toleransi_penyusutan' => 'nullable|numeric|min:0',
            'lokasi_pengiriman'    => 'nullable|string|max:255',
            'type_pengiriman'      => 'nullable|in:PROJECT,RETAIL',
            'metode'               => 'nullable|string|max:100',
            'refund'               => 'nullable|numeric|min:0',
            'other_cost'           => 'nullable|numeric|min:0',
            'keterangan'           => 'nullable|string',
            'catatan'              => 'nullable|string',
            'syarat_ketentuan'     => 'nullable|string',
            'lampiran_tambahan'    => 'nullable|string',
            'oat'                  => 'prohibited',
            'abrasi'   => 'nullable|string|max:100',

            'user_id'                 => 'nullable|exists:users,id',
        ];

        return array_merge($rules, $this->itemPriceIntegrityRules());
    }

    private function itemPriceIntegrityRules(): array
    {
        $rules = [];

        foreach ((array) $this->input('items', []) as $index => $item) {
            $rules["items.{$index}.product_price_id"] = [
                'required',
                'integer',
                Rule::exists('product_prices', 'id')->where(
                    fn($query) => $query
                        ->where('product_id', $item['product_id'] ?? null)
                        ->where('branch_id', $item['source_branch_id'] ?? null)
                        ->where('price_period_id', $this->input('price_period_id'))
                ),
            ];
        }

        return $rules;
    }

    private function alreadySavedPriceIds(): array
    {
        return QuotationItem::where('quotation_id', $this->route('id'))
            ->whereNotNull('product_price_id')
            ->pluck('product_price_id')
            ->all();
    }

    private function rejectUnconfirmedPrices(\Illuminate\Validation\Validator $validator, array $allowedIds = []): void
    {
        $items = (array) $this->input('items', []);

        $candidateIds = collect($items)
            ->map(fn($item) => $item['product_price_id'] ?? null)
            ->filter(fn($id) => is_numeric($id))
            ->diff($allowedIds)
            ->all();

        $flaggedIds = ProductPrice::whereIn('id', $candidateIds)
            ->where('needs_reverification', true)
            ->pluck('id')
            ->all();

        foreach ($items as $index => $item) {
            $key = "items.{$index}.product_price_id";

            if (!$validator->errors()->has($key) && in_array((int) ($item['product_price_id'] ?? 0), $flaggedIds, true)) {
                $validator->errors()->add($key, 'Harga produk ini di periode tersebut belum dikonfirmasi CEO.');
            }
        }
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $this->rejectUnconfirmedPrices($validator, $this->alreadySavedPriceIds());

            $seen = [];

            foreach ((array) $this->input('items', []) as $index => $item) {
                $pairKey = ($item['product_id'] ?? '') . '-' . ($item['source_branch_id'] ?? '');

                if (isset($seen[$pairKey])) {
                    $validator->errors()->add(
                        "items.{$index}.source_branch_id",
                        'Kombinasi produk dan cabang sumber tidak boleh sama dengan baris lain.'
                    );
                    continue;
                }

                $seen[$pairKey] = true;
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.product_price_id.exists' => 'Baris harga yang dipilih tidak cocok dengan produk, cabang sumber, dan periode harga.',
            'price_period_id.required'        => 'Periode harga wajib dipilih.',
            'oat.prohibited'                  => 'Ongkos angkut tidak lagi diisi manual. Harga transport dihitung dari tarif atau harga produk.',
            'transports.max'                  => 'Maksimal dua tarif (kapal dan truck).',
        ];
    }
}
