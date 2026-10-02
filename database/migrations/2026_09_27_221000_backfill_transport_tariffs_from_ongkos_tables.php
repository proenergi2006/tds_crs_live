<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $vesselRows = DB::table('ongkos_kapals as h')
            ->join('ongkos_details as d', 'd.id_ongkos_kapal', '=', 'h.id')
            ->select(
                'h.id as header_id',
                'h.id_transportir',
                'h.id_angkut_wilayah',
                'h.catatan',
                'h.created_at',
                'h.updated_at',
                'h.created_by',
                'h.updated_by',
                'd.id_volume',
                'd.oa'
            )
            ->get();

        $vesselTariffs = $vesselRows->map(fn($row) => [
            'transporter_id' => $row->id_transportir,
            'transport_type' => 'VESSEL',
            'transport_area_id' => $row->id_angkut_wilayah,
            'volume_id' => $row->id_volume,
            'rate' => $row->oa,
            'note' => $row->catatan,
            'is_active' => $row->header_id === 10 ? false : true,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'created_by' => $row->created_by,
            'updated_by' => $row->updated_by,
        ])->all();

        $truckRows = DB::table('ongkos_trucks as h')
            ->join('ongkos_truck_details as d', 'd.id_ongkos_truck', '=', 'h.id')
            ->select(
                'h.id_transportir',
                'h.id_angkut_wilayah',
                'h.created_at',
                'h.updated_at',
                'h.created_by',
                'h.updated_by',
                'd.id_volume',
                'd.oa'
            )
            ->get();

        $truckTariffs = $truckRows->map(fn($row) => [
            'transporter_id' => $row->id_transportir,
            'transport_type' => 'TRUCK',
            'transport_area_id' => $row->id_angkut_wilayah,
            'volume_id' => $row->id_volume,
            'rate' => $row->oa,
            'note' => null,
            'is_active' => true,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'created_by' => $row->created_by,
            'updated_by' => $row->updated_by,
        ])->all();

        if (! empty($vesselTariffs)) {
            DB::table('transport_tariffs')->insert($vesselTariffs);
        }

        if (! empty($truckTariffs)) {
            DB::table('transport_tariffs')->insert($truckTariffs);
        }

        $expectedCount = DB::table('ongkos_details')->count() + DB::table('ongkos_truck_details')->count();
        $actualCount = DB::table('transport_tariffs')->count();

        if ($actualCount !== $expectedCount) {
            throw new RuntimeException("transport_tariffs count mismatch: expected {$expectedCount}, got {$actualCount}.");
        }
    }

    public function down(): void
    {
        DB::table('transport_tariffs')->delete();
    }
};
