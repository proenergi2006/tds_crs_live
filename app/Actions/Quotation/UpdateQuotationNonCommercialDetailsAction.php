<?php

namespace App\Actions\Quotation;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateQuotationNonCommercialDetailsAction
{
    public const EDITABLE_FIELDS = [
        'customer_contact_id',
        'lokasi_pengiriman',
        'keterangan',
        'catatan',
        'syarat_ketentuan',
        'lampiran_tambahan',
    ];

    public function execute(Quotation $quotation, array $data, User $user): Quotation
    {
        $quotation->update([
            ...Arr::only($data, self::EDITABLE_FIELDS),
            'updated_at' => now(),
            'updated_by' => $user->name,
        ]);

        return $quotation;
    }
}
