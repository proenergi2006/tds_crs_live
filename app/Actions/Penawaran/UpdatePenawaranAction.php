<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ManagesPenawaranLineItems;
use App\Models\Penawaran;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;

class UpdatePenawaranAction
{
    use ManagesPenawaranLineItems;

    public function execute(Penawaran $penawaran, array $data, User $user): array
    {
        $data = array_merge($data, $this->calculateTotals($data['items'], $data['discount'] ?? 0, $data['oat'] ?? 0));
        $data['type_pengiriman'] = $data['type_pengiriman'] ?? $penawaran->type_pengiriman;
        $data['updated_at'] = now();
        $data['updated_by'] = optional($user)->name;

        DB::beginTransaction();

        try {
            $penawaran->update($data);

            if ($penawaran->status !== 'draft') {
                (new DocumentApprovalService())->cancelActiveCycle($penawaran);
            }

            $penawaran->forceFill([
                'status'              => 'draft',
                'disposisi_penawaran' => '1',
            ])->save();

            $this->replaceOngkos($penawaran, $data['ongkos'] ?? []);
            $this->replaceItems($penawaran, $data['items']);

            DB::commit();

            $penawaran->load(['customer', 'cabang', 'items.produk']);

            return ['penawaran' => $penawaran];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'message' => 'Gagal update penawaran',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ];
        }
    }
}
