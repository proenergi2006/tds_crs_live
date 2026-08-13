<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateAddressToNewSchema extends Command
{
    protected $signature = 'address:migrate-to-new-schema
        {--commit : Tulis hasil matching ke kolom province_id/regency_id/district_id/village_id. Tanpa flag ini, command berjalan dry-run (report only, tidak menulis apapun ke DB).}';

    protected $description = 'Cocokkan alamat lama (provinsis/kabupatens + kecamatan/kelurahan bebas-teks) di customers/wilayah_angkuts ke skema BPS baru (provinces/regencies/districts/villages) dan laporkan/tulis hasilnya.';

    /** minimal skor similar_text biar fuzzy match district/village dianggap cukup yakin buat ditulis */
    private const FUZZY_MATCH_THRESHOLD = 85.0;

    /** jarak minimum skor kandidat #1 vs #2, biar match fuzzy gak dianggap ambigu */
    private const FUZZY_MATCH_MARGIN = 10.0;

    // alias nama provinsi lama -> id BPS buat yang namanya beda dari resmi; key harus hasil normalize()
    private const PROVINCE_ALIASES = [
        'DKI JAKARTA' => '31',
        // belum ketemu di data aktual, tapi aman didaftarkan preventif -- gak bakal ke-hit kalau emang gak ada
        'DIY YOGYAKARTA' => '34',
        'DI YOGYAKARTA' => '34',
        'YOGYAKARTA' => '34',
        'JOGJAKARTA' => '34',
        'JOGJA' => '34',
    ];

    // koreksi eksplisit buat kabupaten yang mislabel di data lama; province_id null berarti berlaku di semua provinsi
    private const REGENCY_CORRECTIONS = [
        'KABUPATEN TANGERANG SELATAN' => ['province_id' => '36', 'regency_id' => '36.74'],
    ];

    private Collection $provinces;

    /** @var Collection<string, Collection> province_id -> Collection of regency rows */
    private Collection $regenciesByProvince;

    /** @var array<string, Collection> regency_id -> Collection of district rows (memoized, cegah query berulang) */
    private array $districtsCache = [];

    /** @var array<string, Collection> cache key -> Collection of village rows (memoized) */
    private array $villagesCache = [];

    public function handle(): int
    {
        $commit = $this->option('commit');

        $this->loadReferenceData();

        $customerRows = $this->processCustomers();
        $wilayahRows = $this->processWilayahAngkuts();

        $tables = [
            ['label' => 'customers', 'table_name' => 'customers', 'pk' => 'id_customer', 'rows' => $customerRows],
            ['label' => 'wilayah_angkuts', 'table_name' => 'wilayah_angkuts', 'pk' => 'id', 'rows' => $wilayahRows],
        ];

        foreach ($tables as $t) {
            $this->newLine();
            $this->line('=================================================================');
            $this->info(strtoupper($t['label']) . ' (' . count($t['rows']) . ' baris)');
            $this->line('=================================================================');
            $this->renderSummary($t['rows']);
            $this->renderDetail($t['rows']);
        }

        $this->newLine();
        $this->line('=================================================================');
        $this->info('DAFTAR EKSPLISIT: gagal / low-confidence di level district atau village');
        $this->line('(tidak termasuk baris "no_source" -- baris itu memang tidak punya data kecamatan/kelurahan sumber)');
        $this->line('=================================================================');
        $this->renderDistrictVillageGaps($tables);

        if (!$commit) {
            $this->newLine();
            $this->comment('Dry-run mode (default). Tidak ada perubahan ditulis ke database. Jalankan ulang dengan --commit untuk benar-benar menulis ke kolom province_id/regency_id/district_id/village_id.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('--commit diberikan, menulis hasil matching ke kolom province_id/regency_id/district_id/village_id...');

        $written = $this->commitResults($tables);

        $this->info("Selesai. {$written} baris ditulis (minimal 1 kolom *_id terisi).");
        Log::info('MigrateAddressToNewSchema: --commit dijalankan', [
            'written_rows' => $written,
            'tables' => array_column($tables, 'label'),
        ]);

        return self::SUCCESS;
    }

    /* Section: reference data loading */

    private function loadReferenceData(): void
    {
        $this->provinces = DB::table('provinces')->get()->keyBy('id');
        $this->regenciesByProvince = DB::table('regencies')->get()->groupBy('province_id');
    }

    private function districtsFor(string $regencyId): Collection
    {
        return $this->districtsCache[$regencyId] ??= DB::table('districts')
            ->where('regency_id', $regencyId)
            ->get();
    }

    private function villagesFor(string $regencyId, ?string $districtId): Collection
    {
        $key = $districtId !== null ? "district:{$districtId}" : "regency:{$regencyId}";

        return $this->villagesCache[$key] ??= $districtId !== null
            ? DB::table('villages')->where('district_id', $districtId)->get()
            : DB::table('villages')->where('regency_id', $regencyId)->get();
    }

    /* Section: per-table row processing */
    // id lama bukan kode BPS (arbitrary autoincrement) -- makanya di-join ke provinsis/kabupatens dulu buat ambil nama teksnya

    private function processCustomers(): array
    {
        $rows = DB::table('customers')
            ->leftJoin('provinsis', 'customers.id_provinsi', '=', 'provinsis.id_provinsi')
            ->leftJoin('kabupatens', 'customers.id_kabupaten', '=', 'kabupatens.id_kabupaten')
            ->select([
                'customers.id_customer as id',
                'provinsis.nama_provinsi as prov_name',
                'kabupatens.nama_kabupaten as kab_name',
                'customers.customer_sub_district as kec_name',
                'customers.customer_village as kel_name',
            ])
            ->orderBy('customers.id_customer')
            ->get();

        return $rows->map(fn($r) => $this->buildRowResult(
            (string) $r->id,
            $r->prov_name,
            $r->kab_name,
            $r->kec_name,
            $r->kel_name
        ))->all();
    }

    private function processWilayahAngkuts(): array
    {
        $rows = DB::table('wilayah_angkuts')
            ->leftJoin('provinsis', 'wilayah_angkuts.id_provinsi', '=', 'provinsis.id_provinsi')
            ->leftJoin('kabupatens', 'wilayah_angkuts.id_kabupaten', '=', 'kabupatens.id_kabupaten')
            ->select([
                'wilayah_angkuts.id as id',
                'provinsis.nama_provinsi as prov_name',
                'kabupatens.nama_kabupaten as kab_name',
            ])
            ->orderBy('wilayah_angkuts.id')
            ->get();

        // wilayah_angkuts gak punya kolom kecamatan/kelurahan sama sekali -- district/village selalu no_source di tabel ini
        return $rows->map(fn($r) => $this->buildRowResult(
            (string) $r->id,
            $r->prov_name,
            $r->kab_name,
            null,
            null
        ))->all();
    }

    /* Section: core matching per baris */

    private function buildRowResult(string $id, ?string $rawProvince, ?string $rawRegency, ?string $rawDistrict, ?string $rawVillage): array
    {
        $province = $this->matchProvince($rawProvince);
        $regency = $this->matchRegency($rawRegency, $province['status'] === 'matched' ? $province['id'] : null);

        $district = $this->matchAdminLevel(
            $rawDistrict,
            $regency['status'] === 'matched' ? $this->districtsFor($regency['id']) : collect(),
            'kecamatan',
            $regency['status'] === 'matched'
        );

        $districtIdForVillageScope = $district['status'] === 'matched' ? $district['id'] : null;
        $village = $this->matchAdminLevel(
            $rawVillage,
            $regency['status'] === 'matched' ? $this->villagesFor($regency['id'], $districtIdForVillageScope) : collect(),
            'kelurahan',
            $regency['status'] === 'matched'
        );

        return [
            'id' => $id,
            'province' => $province,
            'regency' => $regency,
            'district' => $district,
            'village' => $village,
            'level_reached' => $this->computeLevelReached($province, $regency, $district, $village),
        ];
    }

    private function computeLevelReached(array $province, array $regency, array $district, array $village): string
    {
        if ($province['status'] !== 'matched') {
            return 'none';
        }
        if ($regency['status'] !== 'matched') {
            return 'province';
        }
        if ($district['status'] !== 'matched') {
            return 'regency';
        }
        if ($village['status'] !== 'matched') {
            return 'district';
        }

        return 'village';
    }

    private function matchProvince(?string $rawName): array
    {
        if ($rawName === null || trim($rawName) === '') {
            return ['status' => 'no_source', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => 'Tidak ada data provinsi di sumber (id_provinsi null, atau baris provinsis terkait tidak ditemukan).'];
        }

        $norm = $this->normalize($rawName);

        if (isset(self::PROVINCE_ALIASES[$norm])) {
            $id = self::PROVINCE_ALIASES[$norm];
            $name = $this->provinces->get($id)?->name;

            return ['status' => 'matched', 'id' => $id, 'name' => $name, 'confidence' => 'alias', 'reason' => "Alias eksplisit: \"{$rawName}\" -> {$name} ({$id})"];
        }

        $match = $this->provinces->first(fn($p) => $this->normalize($p->name) === $norm);

        if ($match) {
            return ['status' => 'matched', 'id' => $match->id, 'name' => $match->name, 'confidence' => 'exact', 'reason' => "Exact match nama provinsi \"{$rawName}\""];
        }

        return ['status' => 'failed', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => "Tidak ada match untuk nama provinsi \"{$rawName}\" (bukan alias dikenal, bukan exact match ke provinces.name)."];
    }

    private function matchRegency(?string $rawName, ?string $provinceId): array
    {
        if ($rawName === null || trim($rawName) === '') {
            return ['status' => 'no_source', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => 'Tidak ada data kabupaten/kota di sumber (id_kabupaten null, atau baris kabupatens terkait tidak ditemukan).'];
        }

        if ($provinceId === null) {
            return ['status' => 'skipped', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => 'Provinsi tidak match -- regency tidak bisa diskop ke provinsi manapun, tidak dicoba.'];
        }

        $norm = $this->normalize($rawName);

        if (isset(self::REGENCY_CORRECTIONS[$norm])) {
            $correction = self::REGENCY_CORRECTIONS[$norm];
            if ($correction['province_id'] === null || $correction['province_id'] === $provinceId) {
                $id = $correction['regency_id'];
                $name = $this->regenciesByProvince->get($provinceId, collect())->firstWhere('id', $id)?->name;

                return ['status' => 'matched', 'id' => $id, 'name' => $name, 'confidence' => 'explicit-correction', 'reason' => "Koreksi eksplisit mislabel data lama: \"{$rawName}\" -> {$name} ({$id})"];
            }
        }

        $candidates = $this->regenciesByProvince->get($provinceId, collect());

        $exact = $candidates->first(fn($r) => $this->normalize($r->name) === $norm);
        if ($exact) {
            return ['status' => 'matched', 'id' => $exact->id, 'name' => $exact->name, 'confidence' => 'exact', 'reason' => "Exact match nama kabupaten/kota \"{$rawName}\""];
        }

        $oldCore = $this->stripAdminPrefix($norm);
        $coreMatches = $candidates->filter(fn($r) => $this->stripAdminPrefix($this->normalize($r->name)) === $oldCore && $oldCore !== '');

        if ($coreMatches->count() === 1) {
            $m = $coreMatches->first();
            return ['status' => 'matched', 'id' => $m->id, 'name' => $m->name, 'confidence' => 'core-match', 'reason' => "Match setelah prefix Kabupaten/Kota dilepas: \"{$rawName}\" -> {$m->name} ({$m->id})"];
        }
        if ($coreMatches->count() > 1) {
            $names = $coreMatches->map(fn($r) => "{$r->name} ({$r->id})")->implode('; ');
            return ['status' => 'ambiguous', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => "Ambigu, >1 kandidat core-match untuk \"{$rawName}\": {$names}"];
        }

        $substrMatches = $candidates->filter(function ($r) use ($oldCore) {
            $c = $this->stripAdminPrefix($this->normalize($r->name));
            return $c !== '' && $oldCore !== '' && (str_contains($c, $oldCore) || str_contains($oldCore, $c));
        });

        if ($substrMatches->count() === 1) {
            $m = $substrMatches->first();
            return ['status' => 'matched', 'id' => $m->id, 'name' => $m->name, 'confidence' => 'fuzzy-substring', 'reason' => "Match substring (core name) untuk \"{$rawName}\" -> {$m->name} ({$m->id})"];
        }
        if ($substrMatches->count() > 1) {
            $names = $substrMatches->map(fn($r) => "{$r->name} ({$r->id})")->implode('; ');
            return ['status' => 'ambiguous', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => "Ambigu, >1 kandidat substring-match untuk \"{$rawName}\": {$names}"];
        }

        $nearby = $candidates->map(function ($r) use ($norm) {
            similar_text($norm, $this->normalize($r->name), $percent);
            return ['label' => "{$r->name} ({$r->id}, " . round($percent, 1) . '%)', 'score' => $percent];
        })->sortByDesc('score')->take(3)->pluck('label')->implode('; ');

        return ['status' => 'failed', 'id' => null, 'name' => null, 'confidence' => null, 'reason' => "Tidak ada match untuk \"{$rawName}\" di provinsi ini. Kandidat terdekat (bukan match): {$nearby}"];
    }

    // generic matcher buat district & village; $attempted true kalau regency udah match, kalau enggak -> status 'skipped' aja
    private function matchAdminLevel(?string $rawName, Collection $candidates, string $levelLabel, bool $attempted): array
    {
        if (!$attempted) {
            return ['status' => 'skipped', 'id' => null, 'name' => null, 'confidence' => null, 'score' => null, 'reason' => "Regency belum match, {$levelLabel} tidak dicoba."];
        }

        if ($rawName === null || trim($rawName) === '') {
            return ['status' => 'no_source', 'id' => null, 'name' => null, 'confidence' => null, 'score' => null, 'reason' => "Tidak ada data {$levelLabel} di sumber (field null/kosong)."];
        }

        $norm = $this->normalize($rawName);
        $normNoSpace = str_replace(' ', '', $norm);

        $exact = $candidates->first(fn($c) => $this->normalize($c->name) === $norm);
        if ($exact) {
            return ['status' => 'matched', 'id' => $exact->id, 'name' => $exact->name, 'confidence' => 'exact', 'score' => 100.0, 'reason' => "Exact match {$levelLabel} \"{$rawName}\""];
        }

        $exactNoSpace = $candidates->filter(fn($c) => str_replace(' ', '', $this->normalize($c->name)) === $normNoSpace);
        if ($exactNoSpace->count() === 1) {
            $m = $exactNoSpace->first();
            return ['status' => 'matched', 'id' => $m->id, 'name' => $m->name, 'confidence' => 'exact-no-space', 'score' => 98.0, 'reason' => "Match {$levelLabel} setelah spasi dilepas: \"{$rawName}\" -> {$m->name} ({$m->id})"];
        }

        if ($candidates->isEmpty()) {
            return ['status' => 'no_candidate', 'id' => null, 'name' => null, 'confidence' => null, 'score' => null, 'reason' => "Tidak ada kandidat {$levelLabel} sama sekali pada scope ini."];
        }

        $scored = $candidates->map(function ($c) use ($norm) {
            similar_text($norm, $this->normalize($c->name), $percent);
            return ['candidate' => $c, 'score' => $percent];
        })->sortByDesc('score')->values();

        $top = $scored->first();
        $second = $scored->get(1);

        $unambiguous = !$second || ($top['score'] - $second['score']) >= self::FUZZY_MATCH_MARGIN;

        if ($top['score'] >= self::FUZZY_MATCH_THRESHOLD && $unambiguous) {
            $m = $top['candidate'];
            return ['status' => 'matched', 'id' => $m->id, 'name' => $m->name, 'confidence' => 'fuzzy', 'score' => round($top['score'], 1), 'reason' => "Fuzzy match {$levelLabel} \"{$rawName}\" -> {$m->name} ({$m->id}), skor " . round($top['score'], 1) . '%'];
        }

        $topCandidates = $scored->take(3)->map(fn($s) => "{$s['candidate']->name} ({$s['candidate']->id}, " . round($s['score'], 1) . '%)')->implode('; ');

        $status = $top['score'] >= self::FUZZY_MATCH_THRESHOLD ? 'ambiguous' : 'low_confidence';
        $reasonPrefix = $status === 'ambiguous'
            ? "Ambigu, 2 kandidat teratas terlalu berdekatan untuk \"{$rawName}\""
            : "Tidak ada kandidat {$levelLabel} yang cukup yakin untuk \"{$rawName}\"";

        return ['status' => $status, 'id' => null, 'name' => null, 'confidence' => null, 'score' => round($top['score'], 1), 'reason' => "{$reasonPrefix}. Kandidat teratas: {$topCandidates}"];
    }

    /* Section: normalisasi teks */

    private function normalize(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);
        return mb_strtoupper($s);
    }

    // urutan prefix sengaja longest-first, biar "Kota Administrasi X" gak kepotong jadi "Administrasi X"
    private function stripAdminPrefix(string $normalized): string
    {
        $prefixes = [
            'KABUPATEN ADMINISTRASI ',
            'KOTA ADMINISTRASI ',
            'KABUPATEN ',
            'KOTA ',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return substr($normalized, strlen($prefix));
            }
        }

        return $normalized;
    }

    /* Section: rendering laporan */

    private function renderSummary(array $rows): void
    {
        $total = count($rows);
        $byLevel = collect($rows)->countBy('level_reached');

        $rowsOut = [];
        foreach (['none', 'province', 'regency', 'district', 'village'] as $level) {
            $rowsOut[] = [$level, $byLevel->get($level, 0)];
        }

        $this->table(['level_reached', 'jumlah baris'], $rowsOut);
        $this->line("Total baris: {$total}");
    }

    private function renderDetail(array $rows): void
    {
        $rowsOut = [];
        foreach ($rows as $r) {
            $rowsOut[] = [
                $r['id'],
                $r['level_reached'],
                $this->cellFor($r['province']),
                $this->cellFor($r['regency']),
                $this->cellFor($r['district']),
                $this->cellFor($r['village']),
            ];
        }

        $this->newLine();
        $this->table(['id', 'level_reached', 'province', 'regency', 'district', 'village'], $rowsOut);
    }

    private function cellFor(array $field): string
    {
        if ($field['status'] === 'matched') {
            return "{$field['name']} ({$field['id']}) [{$field['confidence']}]";
        }

        return "[{$field['status']}] " . $this->truncate($field['reason'], 90);
    }

    private function truncate(string $s, int $len): string
    {
        return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 3) . '...' : $s;
    }

    private function renderDistrictVillageGaps(array $tables): void
    {
        $gapStatuses = ['failed', 'ambiguous', 'low_confidence', 'no_candidate'];
        $any = false;

        foreach ($tables as $t) {
            $gaps = [];
            foreach ($t['rows'] as $r) {
                foreach (['district' => 'kecamatan', 'village' => 'kelurahan'] as $field => $label) {
                    if (in_array($r[$field]['status'], $gapStatuses, true)) {
                        $gaps[] = [$r['id'], $label, $r[$field]['status'], $this->truncate($r[$field]['reason'], 100)];
                    }
                }
            }

            if (empty($gaps)) {
                continue;
            }

            $any = true;
            $this->newLine();
            $this->warn("Tabel {$t['label']} -- " . count($gaps) . ' gap district/village:');
            $this->table(['id', 'level', 'status', 'alasan'], $gaps);
        }

        if (!$any) {
            $this->line('Tidak ada gap district/village yang perlu perhatian manual (di luar baris "no_source").');
        }
    }

    /* Section: commit (cuma jalan kalau --commit) */

    private function commitResults(array $tables): int
    {
        $written = 0;

        DB::transaction(function () use ($tables, &$written) {
            foreach ($tables as $t) {
                foreach ($t['rows'] as $r) {
                    $update = [];

                    if ($r['province']['status'] === 'matched') {
                        $update['province_id'] = $r['province']['id'];
                    }
                    if ($r['regency']['status'] === 'matched') {
                        $update['regency_id'] = $r['regency']['id'];
                    }
                    if ($r['district']['status'] === 'matched') {
                        $update['district_id'] = $r['district']['id'];
                    }
                    if ($r['village']['status'] === 'matched') {
                        $update['village_id'] = $r['village']['id'];
                    }

                    if (empty($update)) {
                        continue;
                    }

                    DB::table($t['table_name'])
                        ->where($t['pk'], $r['id'])
                        ->update($update);

                    $written++;
                }
            }
        });

        return $written;
    }
}
