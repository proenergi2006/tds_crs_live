<?php

namespace App\Actions\SalesConfirmation;

use App\Enums\SalesConfirmationStatus;
use App\Models\CustomerAdminArnya;
use App\Models\PoCustomer;
use App\Models\SalesConfirmation;
use App\Models\SalesConfirmationApproval;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveSalesConfirmationAction
{
    public function execute(PoCustomer $po, array $data, string $pic, array $newFiles = [], array $removedPaths = []): SalesConfirmation
    {
        $idCustomer = $po->id_customer;
        $removedFromList = [];
        $storedNew = [];

        try {
            $sc = DB::transaction(function () use ($po, $data, $pic, $idCustomer, $newFiles, $removedPaths, &$removedFromList, &$storedNew) {
                $arnya = CustomerAdminArnya::where('id_customer', $idCustomer)->first();
                $limit = (float) ($po->customer?->current_credit_limit ?? 0);

                $adminSummary = $data['admin_summary'] ?? null;

                $sc = SalesConfirmation::updateOrCreate(
                    ['po_customer_id' => $po->id_poc],
                    [
                        'id_customer'     => $idCustomer,
                        'credit_limit'    => $limit,
                        'not_yet'         => (float) ($arnya->outstanding_current ?? 0),
                        'ov_up_07'        => 0,
                        'ov_under_30'     => (float) ($arnya->overdue_1_30 ?? 0),
                        'ov_under_60'     => (float) ($arnya->overdue_31_60 ?? 0),
                        'ov_under_90'     => (float) ($arnya->overdue_61_90 ?? 0),
                        'ov_up_90'        => (float) ($arnya->overdue_90_plus ?? 0),
                        'disposisi'       => SalesConfirmationStatus::PendingBm,
                        'flag_approval'   => 1,
                        'role_approved'   => null,
                        'tgl_approved'    => null,
                        'lastupdate_by'   => $pic,
                        'lastupdate_time' => now(),
                    ]
                );

                if ($sc->wasRecentlyCreated) {
                    $sc->created_time = now();
                    $sc->created_by   = $pic;
                    $sc->save();
                }

                $existingList = SalesConfirmationApproval::where('id_sales', $sc->id)->value('adm_attachments') ?? [];
                [$keptList, $removedFromList] = $this->splitRemoved($existingList, $removedPaths);
                $storedNew = $this->storeFiles($sc, $newFiles);

                SalesConfirmationApproval::updateOrCreate(
                    ['id_sales' => $sc->id],
                    [
                        'adm_result'      => 1,
                        'adm_summary'     => $adminSummary ? nl2br($adminSummary) : null,
                        'adm_attachments' => array_merge($keptList, $storedNew),
                        'adm_result_date' => now(),
                        'adm_pic'         => $pic,
                    ]
                );

                $service = new DocumentApprovalService();
                if (!$service->activeCycle($sc)) {
                    $service->startCycle($sc, 'sales_confirmation');
                }

                return $sc->refresh();
            });
        } catch (\Throwable $e) {
            $this->deleteFiles(array_column($storedNew, 'path'));

            throw $e;
        }

        $this->deleteFiles(array_column($removedFromList, 'path'));

        return $sc;
    }

    private function splitRemoved(array $existingList, array $removedPaths): array
    {
        $kept = [];
        $removed = [];

        foreach ($existingList as $attachment) {
            if (in_array($attachment['path'] ?? null, $removedPaths, true)) {
                $removed[] = $attachment;
            } else {
                $kept[] = $attachment;
            }
        }

        return [$kept, $removed];
    }

    private function storeFiles(SalesConfirmation $sc, array $files): array
    {
        return array_map(function (UploadedFile $file) use ($sc) {
            $path = $file->storeAs(
                "sales-confirmations/{$sc->id}",
                Str::random(20) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            return ['path' => $path, 'original_name' => $file->getClientOriginalName()];
        }, $files);
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
