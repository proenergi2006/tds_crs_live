<?php

namespace App\Console\Commands;

use App\Enums\CustomerAddressType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// alamat head office masih nyangkut di kolom lama `customers`, belum pernah ditulis ke customer_addresses -- backfill sekali dari sini, idempotent
class BackfillHeadOfficeCustomerAddresses extends Command
{
    protected $signature = 'customer:backfill-head-office-addresses
        {--commit : Tulis baris head_office ke customer_addresses. Tanpa flag ini, command berjalan dry-run (report only, tidak menulis apapun ke DB).}';

    protected $description = 'Backfill baris customer_addresses bertipe head_office dari kolom alamat lama di tabel customers (idempotent, one-time).';

    public function handle(): int
    {
        $commit = $this->option('commit');

        $existingIds = $this->existingHeadOfficeCustomerIds();
        $rows = $this->fetchCustomerRows();

        $plans = $rows->map(fn (object $row) => $this->planRow($row, $existingIds))->all();

        $this->renderSummary($plans);
        $this->renderDetail($plans);

        if (!$commit) {
            $this->newLine();
            $this->comment('Dry-run mode (default). Tidak ada perubahan ditulis ke database. Jalankan ulang dengan --commit untuk benar-benar menulis baris head_office.');
            return self::SUCCESS;
        }

        $written = 0;

        // transaction per baris, bukan satu buat semua -- satu customer bermasalah gak boleh gagalin baris lain yang udah benar
        foreach ($plans as $plan) {
            if ($plan['action'] !== 'insert') {
                continue;
            }

            DB::transaction(fn () => $this->insertHeadOfficeAddress($plan));
            $written++;
        }

        $this->info("Selesai. {$written} baris head_office ditulis.");
        Log::info('BackfillHeadOfficeCustomerAddresses: --commit dijalankan', ['written_rows' => $written]);

        return self::SUCCESS;
    }

    private function fetchCustomerRows(): Collection
    {
        return DB::table('customers')
            ->select([
                'id_customer',
                'company_address',
                'customer_sub_district',
                'customer_village',
                'province_id',
                'regency_id',
                'district_id',
                'village_id',
                'postal_code',
            ])
            ->orderBy('id_customer')
            ->get();
    }

    // dicek di depan biar gak nabrak unique index saat insert -- exception di tengah loop bisa hentiin sisa baris yang masih valid
    private function existingHeadOfficeCustomerIds(): array
    {
        $ids = DB::table('customer_addresses')
            ->where('address_type', CustomerAddressType::HeadOffice->value)
            ->pluck('id_customer')
            ->all();

        return array_flip($ids);
    }

    private function planRow(object $row, array $existingIds): array
    {
        if (isset($existingIds[$row->id_customer])) {
            return ['id_customer' => $row->id_customer, 'action' => 'skipped_existing'];
        }

        if ($this->isSourceEmpty($row)) {
            return ['id_customer' => $row->id_customer, 'action' => 'skipped_empty'];
        }

        return [
            'id_customer' => $row->id_customer,
            'action' => 'insert',
            'address_line' => $this->buildAddressLine($row->company_address, $row->customer_sub_district, $row->customer_village),
            'province_id' => $row->province_id,
            'regency_id' => $row->regency_id,
            'district_id' => $row->district_id,
            'village_id' => $row->village_id,
            'postal_code' => $row->postal_code,
        ];
    }

    // customer_sub_district/customer_village string bebas pra-BPS, gak ada kolom padanannya di customer_addresses -- digabung ke address_line biar gak hilang
    private function buildAddressLine(?string $companyAddress, ?string $subDistrict, ?string $village): string
    {
        $parts = [];

        if ($companyAddress !== null && trim($companyAddress) !== '') {
            $parts[] = trim($companyAddress);
        }

        if ($subDistrict !== null && trim($subDistrict) !== '') {
            $parts[] = 'Kec. '.trim($subDistrict);
        }

        if ($village !== null && trim($village) !== '') {
            $parts[] = 'Kel. '.trim($village);
        }

        return implode(', ', $parts);
    }

    private function isSourceEmpty(object $row): bool
    {
        $fields = [
            $row->company_address,
            $row->customer_sub_district,
            $row->customer_village,
            $row->province_id,
            $row->regency_id,
            $row->district_id,
            $row->village_id,
            $row->postal_code,
        ];

        foreach ($fields as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function insertHeadOfficeAddress(array $plan): void
    {
        DB::table('customer_addresses')->insert([
            'id_customer' => $plan['id_customer'],
            'address_type' => CustomerAddressType::HeadOffice->value,
            'address_line' => $plan['address_line'],
            'province_id' => $plan['province_id'],
            'regency_id' => $plan['regency_id'],
            'district_id' => $plan['district_id'],
            'village_id' => $plan['village_id'],
            'postal_code' => $plan['postal_code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function renderSummary(array $plans): void
    {
        $byAction = collect($plans)->countBy('action');

        $rowsOut = [];
        foreach (['insert', 'skipped_existing', 'skipped_empty'] as $action) {
            $rowsOut[] = [$action, $byAction->get($action, 0)];
        }

        $this->table(['action', 'jumlah baris'], $rowsOut);
        $this->line('Total baris: '.count($plans));
    }

    private function renderDetail(array $plans): void
    {
        $rowsOut = [];
        foreach ($plans as $plan) {
            $rowsOut[] = [
                $plan['id_customer'],
                $plan['action'],
                $this->truncate($plan['address_line'] ?? '', 60),
                $plan['province_id'] ?? '',
                $plan['regency_id'] ?? '',
                $plan['district_id'] ?? '',
                $plan['village_id'] ?? '',
                $plan['postal_code'] ?? '',
            ];
        }

        $this->newLine();
        $this->table(['id_customer', 'action', 'address_line', 'province_id', 'regency_id', 'district_id', 'village_id', 'postal_code'], $rowsOut);
    }

    private function truncate(string $s, int $len): string
    {
        return mb_strlen($s) > $len ? mb_substr($s, 0, $len - 3).'...' : $s;
    }
}
