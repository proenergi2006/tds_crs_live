<?php

namespace App\Http\Requests\Penawaran;

use App\Models\Penawaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePenawaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $brand = $this->route('brand');
        $penawaran = Penawaran::where('brand', $brand)->findOrFail($this->route('id'));
        $user = $this->user();
        $permission = $brand === 'proenergi' ? 'penawaran.proenergi.manage' : 'penawaran.manage';

        return $user->can($permission)
            && (int) $penawaran->user_id === (int) $user->id;
    }

    public function rules(): array
    {
        $rules = [
            'id_customer'          => 'required|exists:customers,id_customer',
            'customer_contact_id' => [
                'required',
                'integer',
                Rule::exists('customer_contacts', 'id_contact')->where(
                    fn($query) => $query->where('id_customer', $this->input('id_customer'))
                ),
            ],
            'id_cabang'            => 'required|exists:cabangs,id_cabang',
            'price_period_id'      => 'required|integer|exists:price_periods,id',

            'ongkos'                     => 'nullable|array',
            'ongkos.*.jenis'             => 'required|in:KAPAL,TRUCK',
            'ongkos.*.id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
            'ongkos.*.id_transportir'    => 'required|exists:transportirs,id',
            'ongkos.*.id_volume'         => 'required|exists:volumes,id_volume',
            'ongkos.*.ongkos'            => 'required|numeric|min:0',

            'items'                    => 'required|array|min:1',
            'items.*.id_produk'        => 'required|exists:produks,id_produk',
            'items.*.source_branch_id' => 'required|integer|exists:cabangs,id_cabang',
            'items.*.product_price_id' => 'required|integer',
            'items.*.persen'           => 'required|numeric|min:0|max:100',
            'items.*.volume_order'     => 'required|numeric|min:0',
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
            'perhitungan'          => 'nullable|string',
            'keterangan'           => 'nullable|string',
            'catatan'              => 'nullable|string',
            'syarat_ketentuan'     => 'nullable|string',
            'lampiran_tambahan'    => 'nullable|string',
            'discount'             => 'nullable|numeric|min:0',
            'oat'                  => 'nullable|numeric|min:0',
            'jenis_penawaran'      => 'nullable|string|max:100',
            'abrasi'   => 'nullable|string|max:100',

            'user_id'                 => 'nullable|exists:users,id',
            'harga_dasar'             => 'nullable|numeric|min:0',
            'ppn_harga_dasar'         => 'nullable|numeric|min:0',
            'grand_total_harga_dasar' => 'nullable|numeric|min:0',
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
                        ->where('product_id', $item['id_produk'] ?? null)
                        ->where('branch_id', $item['source_branch_id'] ?? null)
                        ->where('price_period_id', $this->input('price_period_id'))
                ),
            ];
        }

        return $rules;
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $seen = [];

            foreach ((array) $this->input('items', []) as $index => $item) {
                $pairKey = ($item['id_produk'] ?? '') . '-' . ($item['source_branch_id'] ?? '');

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
        ];
    }
}
