<?php

namespace App\Http\Requests\Penawaran;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenawaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $penawaran = \App\Models\Penawaran::findOrFail($this->route('id'));
        $user = $this->user();

        return $user->can('penawaran.manage')
            && ((int) $penawaran->user_id === (int) $user->id || $user->can('penawaran.viewAny'));
    }

    public function rules(): array
    {
        return [
            'id_customer'          => 'required|exists:customers,id_customer',
            'id_cabang'            => 'required|exists:cabangs,id_cabang',
            'masa_berlaku'         => 'required|date',
            'sampai_dengan'        => 'required|date|after_or_equal:masa_berlaku',

            'ongkos'                     => 'nullable|array',
            'ongkos.*.jenis'             => 'required|in:KAPAL,TRUCK',
            'ongkos.*.id_angkut_wilayah' => 'required|exists:wilayah_angkuts,id',
            'ongkos.*.id_transportir'    => 'required|exists:transportirs,id',
            'ongkos.*.id_volume'         => 'required|exists:volumes,id_volume',
            'ongkos.*.ongkos'            => 'required|numeric|min:0',

            'items'                => 'required|array|min:1',
            'items.*.id_produk'    => 'required|exists:produks,id_produk',
            'items.*.persen'       => 'required|numeric|min:0|max:100',
            'items.*.volume_order' => 'required|numeric|min:0',
            'items.*.harga_tebus'  => 'required|numeric|min:0',
            'tipe_pembayaran'      => 'nullable|string|max:100',
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
            'discount'             => 'nullable|numeric|min:0',
            'oat'                  => 'nullable|numeric|min:0',
            'jenis_penawaran'      => 'nullable|string|max:100',
            'kepada'   => 'nullable|string|max:255',
            'nama'     => 'nullable|string|max:255',
            'jabatan'  => 'nullable|string|max:255',
            'telepon'  => 'nullable|string|max:255',
            'alamat'   => 'nullable|string',
            'abrasi'   => 'nullable|string|max:100',

            'harga_dasar'             => 'nullable|numeric|min:0',
            'ppn_harga_dasar'         => 'nullable|numeric|min:0',
            'grand_total_harga_dasar' => 'nullable|numeric|min:0',
        ];
    }
}
