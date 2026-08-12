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
            'periode_awal'         => $this->periode_awal,
            'periode_akhir'        => $this->periode_akhir,
            'label'                => $awal->locale('id')->translatedFormat('j M Y') . ' – ' . $akhir->locale('id')->translatedFormat('j M Y'),
            'masa_aktif'           => $today->between($awal, $akhir) ? 'aktif' : 'berakhir',
            'status'               => (int) $this->jumlah_belum_lengkap > 0 ? 'belum_lengkap' : 'lengkap',
            'jumlah_data'          => (int) $this->jumlah_data,
            'jumlah_cabang'        => (int) $this->jumlah_cabang,
            'jumlah_belum_lengkap' => (int) $this->jumlah_belum_lengkap,
            'terakhir_diupdate'    => $this->terakhir_diupdate,
        ];
    }
}
