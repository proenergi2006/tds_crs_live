<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreApprovalTemplateRequest;
use App\Http\Requests\MasterData\UpdateApprovalTemplateRequest;
use App\Models\ApprovalTemplate;
use App\Models\ApprovalTemplateStep;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalTemplateController extends Controller
{
    public function index(Request $request)
    {
        $q = ApprovalTemplate::withCount('steps');

        if ($search = $request->query('search')) {
            $q->where(function ($q2) use ($search) {
                $q2->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $q->orderBy('name');

        if ($request->boolean('as_list')) {
            return response()->json($q->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return response()->json($q->paginate($perPage));
    }

    public function store(StoreApprovalTemplateRequest $request)
    {
        $data = $request->validated();

        $template = DB::transaction(function () use ($data) {
            $template = ApprovalTemplate::create([
                'code'      => $data['code'],
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['steps'] as $step) {
                $template->steps()->create([
                    'step_order' => $step['step_order'],
                    'step_name'  => $step['step_name'],
                    'id_role'    => $step['id_role'],
                ]);
            }

            return $template;
        });

        return response()->json($template->fresh(['steps.role']), 201);
    }

    public function show(ApprovalTemplate $approvalTemplate)
    {
        $approvalTemplate->load(['steps.role']);

        return response()->json($approvalTemplate);
    }

    public function update(UpdateApprovalTemplateRequest $request, ApprovalTemplate $approvalTemplate)
    {
        $template = $approvalTemplate;
        $data = $request->validated();

        $incomingOrders = collect($data['steps'])->pluck('step_order')->all();

        $stepsToRemove = $template->steps()
            ->whereNotIn('step_order', $incomingOrders)
            ->get();

        $blocked = $stepsToRemove->first(
            fn (ApprovalTemplateStep $step) => $step->documentApprovalSteps()->exists()
        );

        if ($blocked) {
            return response()->json([
                'message' => "Tidak bisa menghapus step \"{$blocked->step_name}\" (step_order={$blocked->step_order}): "
                    . 'step ini sudah pernah dipakai dalam siklus approval (document_approval_steps). '
                    . 'Ubah step yang sudah ada, jangan menghapusnya.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($template, $data, $stepsToRemove) {
                $template->update([
                    'code'      => $data['code'],
                    'name'      => $data['name'],
                    'is_active' => $data['is_active'] ?? $template->is_active,
                ]);

                foreach ($stepsToRemove as $step) {
                    $step->delete();
                }

                foreach ($data['steps'] as $step) {
                    ApprovalTemplateStep::updateOrCreate(
                        [
                            'id_template' => $template->id_template,
                            'step_order'  => $step['step_order'],
                        ],
                        [
                            'step_name' => $step['step_name'],
                            'id_role'   => $step['id_role'],
                        ]
                    );
                }
            });
        } catch (QueryException $e) {
            // 23503 = foreign_key_violation (Postgres)
            if ($e->getCode() === '23503') {
                Log::warning('ApprovalTemplateController@update: FK restrict saat sync steps', [
                    'id_template' => $template->id_template,
                    'error'       => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Tidak bisa menyimpan perubahan step: salah satu step yang dihapus/diubah '
                        . 'ternyata sudah dipakai dalam siklus approval yang berjalan. Muat ulang data dan coba lagi.',
                ], 409);
            }

            throw $e;
        }

        return response()->json($template->fresh(['steps.role']));
    }

    public function destroy(ApprovalTemplate $approvalTemplate)
    {
        $template = $approvalTemplate;

        if ($template->documentApprovals()->exists()) {
            return response()->json([
                'message' => 'Tidak bisa menghapus: template ini masih memiliki riwayat siklus persetujuan '
                    . '(document_approvals) yang mereferensikannya.',
            ], 409);
        }

        $template->delete();

        return response()->json(null, 204);
    }
}
