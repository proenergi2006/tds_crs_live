<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sources = [
        'transporters' => ['type' => 'transporter', 'column' => 'perizinan', 'allowed' => ['NIB', 'SIUP', 'SIUPAL']],
        'personnels' => ['type' => 'personnel', 'column' => 'nama_dokumen', 'allowed' => ['SIM', 'CERTIFICATE']],
        'vessels' => ['type' => 'vessel', 'column' => 'dokumen', 'allowed' => ['SHIP_PARTICULAR', 'SIOPSUS']],
        'trucks' => ['type' => 'truck', 'column' => 'dokumen', 'allowed' => ['STNK', 'KIR']],
    ];

    public function up(): void
    {
        foreach ($this->sources as $table => $config) {
            $documents = $this->collectDocuments($table, $config['type'], $config['column'], $config['allowed']);

            if (! empty($documents)) {
                DB::table('logistic_documents')->insert($documents);
            }
        }

        foreach ($this->sources as $table => $config) {
            Schema::table($table, function (Blueprint $blueprint) use ($config) {
                $blueprint->dropColumn([$config['column'], 'masa_berlaku', 'lampiran']);
            });
        }
    }

    public function down(): void
    {
        $types = array_column($this->sources, 'type');

        $duplicates = DB::table('logistic_documents')
            ->whereIn('documentable_type', $types)
            ->select('documentable_type', 'documentable_id')
            ->groupBy('documentable_type', 'documentable_id')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Cannot rollback: multiple logistic_documents rows exist for the same documentable, restore from backup instead.');
        }

        foreach ($this->sources as $table => $config) {
            Schema::table($table, function (Blueprint $blueprint) use ($config) {
                $blueprint->string($config['column'])->nullable();
                $blueprint->date('masa_berlaku')->nullable();
                $blueprint->string('lampiran')->nullable();
            });

            $documents = DB::table('logistic_documents')->where('documentable_type', $config['type'])->get();

            foreach ($documents as $document) {
                DB::table($table)->where('id', $document->documentable_id)->update([
                    $config['column'] => $document->document_type,
                    'masa_berlaku' => $document->valid_until,
                    'lampiran' => $document->file_path,
                ]);
            }
        }

        DB::table('logistic_documents')->whereIn('documentable_type', $types)->delete();
    }

    private function collectDocuments(string $table, string $documentableType, string $typeColumn, array $allowedTypes): array
    {
        $rows = DB::table($table)->select('id', $typeColumn, 'masa_berlaku', 'lampiran', 'created_at', 'updated_at')->get();

        $documents = [];
        $unknownValues = [];

        foreach ($rows as $row) {
            $raw = $row->{$typeColumn};

            if (is_null($raw) || trim($raw) === '' || trim($raw) === '-') {
                continue;
            }

            $normalized = $this->normalizeDocumentType($raw);

            if (! in_array($normalized, $allowedTypes, true)) {
                $unknownValues[$row->id] = $raw;

                continue;
            }

            $documents[] = [
                'documentable_type' => $documentableType,
                'documentable_id' => $row->id,
                'document_type' => $normalized,
                'file_path' => $row->lampiran,
                'file_name' => $row->lampiran ? basename($row->lampiran) : null,
                'valid_until' => $row->masa_berlaku,
                'uploaded_at' => null,
                'uploaded_by' => null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        if (! empty($unknownValues)) {
            throw new RuntimeException("Unknown {$typeColumn} values in {$table}: " . json_encode($unknownValues));
        }

        return $documents;
    }

    private function normalizeDocumentType(string $raw): string
    {
        return str_replace(' ', '_', strtoupper(trim($raw)));
    }
};
