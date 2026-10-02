<?php

namespace App\Actions\PoCustomer;

use App\Enums\PoCustomerScProcessState;
use App\Models\PoCustomer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class UpdatePoCustomerAction
{
    public function execute(PoCustomer $po, array $validated, ?UploadedFile $lampiran, string $updatedBy, string $ip): PoCustomer
    {
        $data = Arr::except($validated, ['lampiran_poc']);

        $data['termin_hari'] = $validated['tipe_bayar'] === 'CREDIT' ? $validated['termin_hari'] : null;

        if ($lampiran) {
            if ($po->lampiran_poc && Storage::disk('public')->exists($po->lampiran_poc)) {
                Storage::disk('public')->delete($po->lampiran_poc);
            }

            $filename = time().'_'.$lampiran->getClientOriginalName();
            $data['lampiran_poc'] = $lampiran->storeAs('lampiran_po', $filename, 'public');
            $data['lampiran_poc_ori'] = $lampiran->getClientOriginalName();
        }

        if ($po->sc_process_state === PoCustomerScProcessState::Blocked) {
            $data['sc_process_state'] = null;
        }

        $data['lastupdate_time'] = now();
        $data['lastupdate_by'] = $updatedBy;
        $data['lastupdate_ip'] = $ip;

        $po->update($data);

        return $po->refresh();
    }
}
