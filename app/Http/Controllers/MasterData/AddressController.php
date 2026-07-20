<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * GET /provinces
     * Daftar seluruh provinsi (38 baris), diurutkan nama.
     */
    public function provinces(Request $request): JsonResponse
    {
        $q = Province::query();

        if ($search = trim((string) $request->query('search', ''))) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $rows = $q->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /provinces/{province}
     * Detail satu provinsi (dipakai FE untuk resolve nama dari id yang
     * sudah tersimpan, mis. saat prefill form edit).
     */
    public function showProvince(Province $province): JsonResponse
    {
        return response()->json(['data' => $province]);
    }

    /**
     * GET /provinces/{province}/regencies
     * Daftar kabupaten/kota dalam satu provinsi.
     */
    public function regencies(Request $request, Province $province): JsonResponse
    {
        $q = $province->regencies();

        if ($search = trim((string) $request->query('search', ''))) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $rows = $q->orderBy('name')->get(['id', 'province_id', 'name']);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /regencies/{regency}
     * Detail satu kabupaten/kota beserta provinsi induknya.
     */
    public function showRegency(Regency $regency): JsonResponse
    {
        return response()->json(['data' => $regency->load('province')]);
    }

    /**
     * GET /regencies/{regency}/districts
     * Daftar kecamatan dalam satu kabupaten/kota.
     */
    public function districts(Request $request, Regency $regency): JsonResponse
    {
        $q = $regency->districts();

        if ($search = trim((string) $request->query('search', ''))) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $rows = $q->orderBy('name')->get(['id', 'regency_id', 'province_id', 'name']);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /districts/{district}
     * Detail satu kecamatan beserta kabupaten/kota + provinsi induknya.
     */
    public function showDistrict(District $district): JsonResponse
    {
        return response()->json(['data' => $district->load(['regency', 'province'])]);
    }

    /**
     * GET /districts/{district}/villages
     * Daftar kelurahan/desa dalam satu kecamatan (termasuk postal_code).
     */
    public function villages(Request $request, District $district): JsonResponse
    {
        $q = $district->villages();

        if ($search = trim((string) $request->query('search', ''))) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $rows = $q->orderBy('name')->get(['id', 'district_id', 'regency_id', 'province_id', 'name', 'postal_code']);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /villages/{village}
     * Detail satu kelurahan/desa beserta kecamatan + kabupaten/kota +
     * provinsi induknya — berguna untuk resolve seluruh rantai (province
     * -> regency -> district -> village) dari satu village_id tersimpan,
     * tanpa perlu memanggil 3 endpoint cascading terpisah.
     */
    public function showVillage(Village $village): JsonResponse
    {
        return response()->json(['data' => $village->load(['district', 'regency', 'province'])]);
    }
}
