<?php

namespace App\Http\Resources\MasterData;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdukHargaPeriodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $awal   = Carbon::parse($this->periode_awal);
        $akhir  = Carbon::parse($this->periode_akhir);
        $today  = Carbon::today();

        return [
            'periode_awal'      => $this->periode_awal,
            'periode_akhir'     => $this->periode_akhir,
            'label'             => $awal->locale('id')->translatedFormat('j M Y') . ' – ' . $akhir->locale('id')->translatedFormat('j M Y'),
            'status'            => $today->between($awal, $akhir) ? 'aktif' : 'berakhir',
            'jumlah_data'       => (int) $this->jumlah_data,
            'jumlah_cabang'     => (int) $this->jumlah_cabang,
            'terakhir_diupdate' => $this->terakhir_diupdate,
        ];
    }
}
