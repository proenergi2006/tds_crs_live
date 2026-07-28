<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $documentTypeIds = DB::table('customer_document_types')->pluck('id', 'code');

        $columnMap = [
            'nib' => 'nib_file',
            'npwp' => 'nomor_npwp_file',
            'sertifikat' => 'nomor_sertifikat_file',
            'dokumen_lainnya' => 'dokumen_lainnya_file',
        ];

        foreach (array_keys($columnMap) as $code) {
            if (!isset($documentTypeIds[$code])) {
                throw new \RuntimeException(
                    "customer_document_types row with code={$code} not found. " .
                        'Run CustomerDocumentTypeSeeder before this migration.'
                );
            }
        }

        $customers = DB::table('customers')
            ->select(array_merge(['id_customer', 'lastupdate_time', 'created_time'], array_values($columnMap)))
            ->get();

        $now = now();
        $rows = [];

        foreach ($customers as $customer) {
            foreach ($columnMap as $code => $fileColumn) {
                $filePath = $customer->{$fileColumn};

                if ($filePath === null || trim($filePath) === '') {
                    continue;
                }

                $rows[] = [
                    'id_customer' => $customer->id_customer,
                    'id_document_type' => $documentTypeIds[$code],
                    'file_path' => $filePath,
                    'file_name' => basename($filePath),
                    'uploaded_at' => $customer->lastupdate_time ?? $customer->created_time ?? $now,
                    'uploaded_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($rows)) {
            DB::table('customer_documents')->insert($rows);
        }
    }

    public function down(): void
    {
        //
    }
};
