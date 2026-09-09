<?php

namespace App\Http\Resources\MasterData;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricePeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $awal  = Carbon::parse($this->start_date);
        $akhir = Carbon::parse($this->end_date);
        $today = Carbon::today();

        $category = match (true) {
            $today->lt($awal)  => 'upcoming',
            $today->gt($akhir) => 'inactive',
            default            => 'active',
        };

        return [
            'id'                   => $this->id,
            'start_date'           => $awal->format('Y-m-d'),
            'end_date'             => $akhir->format('Y-m-d'),
            'label'                => $awal->locale('id')->translatedFormat('j M Y') . ' – ' . $akhir->locale('id')->translatedFormat('j M Y'),
            'category'             => $category,
            'masa_aktif'           => $category === 'active' ? 'aktif' : 'nonaktif',
            'status'               => (int) ($this->jumlah_belum_lengkap ?? 0) > 0 ? 'belum_lengkap' : 'lengkap',
            'jumlah_data'          => (int) ($this->jumlah_data ?? 0),
            'jumlah_cabang'        => (int) ($this->jumlah_cabang ?? 0),
            'jumlah_belum_lengkap' => (int) ($this->jumlah_belum_lengkap ?? 0),
            'terakhir_diupdate'    => $this->terakhir_diupdate ?? null,
            $this->mergeWhen((bool) $request->user()?->can('price-period.view'), fn() => [
                'attachments' => $this->attachments ?? [],
                'notes'       => $this->notes,
            ]),
        ];
    }
}
