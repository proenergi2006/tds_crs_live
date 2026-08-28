<?php

namespace App\Actions\Penawaran;

use App\Actions\Penawaran\Concerns\ManagesPenawaranLineItems;
use App\Models\Cabang;
use App\Models\Penawaran;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;

class CreatePenawaranAction
{
    use ManagesPenawaranLineItems;

    public function execute(array $data, string $brand, User $user): array
    {
        $data['user_id'] = $user->id ?? ($data['user_id'] ?? null);
        $data['brand'] = $brand;

        $cabang = Cabang::findOrFail($data['id_cabang']);
        $data = array_merge($data, $this->calculateTotals($data['items'], $data['discount'] ?? 0, $data['oat'] ?? 0));
        $data['nomor_penawaran'] = $this->generateNomor($cabang, $brand);
        $data['status'] = 'draft';
        $data['disposisi_penawaran'] = '1';
        $data['type_pengiriman'] = $data['type_pengiriman'] ?? null;
        $data['created_at'] = now();
        $data['created_by'] = optional($user)->name;

        DB::beginTransaction();

        try {
            $penawaran = Penawaran::create($data);

            $this->replaceOngkos($penawaran, $data['ongkos'] ?? []);
            $this->replaceItems($penawaran, $data['items']);

            $penawaran->refresh();
            $this->attachQrCode($penawaran);

            DB::table('customers')->where('id_customer', $data['id_customer'])->update([
                'id_cabang'  => $data['id_cabang'],
                'updated_at' => now(),
                'updated_by' => optional($user)->name,
            ]);

            DB::commit();

            $penawaran->load(['customer', 'cabang', 'items.produk']);

            return ['penawaran' => $penawaran, 'status' => 201];
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return [
                'message' => 'Gagal menyimpan penawaran',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ];
        }
    }

    private function attachQrCode(Penawaran $penawaran): void
    {
        $qrService = new QrCodeService();
        $payloadNumber = $qrService->generateNumericCode(8);

        try {
            $saved = $qrService->generatePng($payloadNumber, $penawaran->id_penawaran);
        } catch (\Throwable $e) {
            report($e);
            $saved = $qrService->generateSvg($payloadNumber, $penawaran->id_penawaran);
        }

        $penawaran->forceFill(['qr_code' => $saved['url']])->save();
    }

    private function generateNomor(Cabang $cabang, string $brand): string
    {
        $urut = (int) $cabang->urut_penawaran + 1;
        $prefix = $brand === 'proenergi' ? 'PE-PN' : 'TDS-PN';
        $nomor = str_pad($urut, 5, '0', STR_PAD_LEFT)
            . "/{$prefix}/" . $cabang->inisial_cabang . '/' . $this->getRomanMonth(date('m')) . '/' . substr(date('Y'), -2);

        $cabang->urut_penawaran = $urut;
        $cabang->save();

        return $nomor;
    }

    private function getRomanMonth($month)
    {
        $months = [
            '01' => 'I',
            '02' => 'II',
            '03' => 'III',
            '04' => 'IV',
            '05' => 'V',
            '06' => 'VI',
            '07' => 'VII',
            '08' => 'VIII',
            '09' => 'IX',
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
        ];

        return $months[$month] ?? '';
    }
}
