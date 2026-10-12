<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Mail\QuotationApprovalRequestMail;
use App\Mail\QuotationApprovedMail;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ApproveQuotationAction
{
    public function execute(Quotation $quotation, User $actor, ?string $note, string $detailUrl, \Closure $verificationUrlFor): array
    {
        DB::beginTransaction();

        try {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            $step      = $quotation->activeVerificationStep();

            $cycle   = (new DocumentApprovalService())->decideStep($quotation, $step->step_order, DocumentApprovalStepStatus::Approved, $actor->id, $note);
            $isFinal = $cycle?->status === DocumentApprovalStatus::Approved;

            if ($isFinal) {
                $quotation->update([
                    'status'           => QuotationStatus::Approved,
                    'token_verifikasi' => strtoupper(Str::random(17)),
                ]);
            }

            $this->notifyCreator($quotation, $detailUrl, $isFinal ? 'om' : 'bm');

            if (! $isFinal) {
                $nextStep = $cycle->steps()->where('step_order', $cycle->current_step_order)->first();
                $this->notifyNextVerifier($quotation, $nextStep?->id_role, $verificationUrlFor);
            }

            DB::commit();

            return ['message' => $isFinal
                ? 'Penawaran disetujui dan langsung final'
                : 'Penawaran disetujui & notifikasi terkirim'];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Gagal verifikasi penawaran',
                'error'   => $e->getMessage(),
            ];
        }
    }

    private function notifyCreator(Quotation $quotation, string $detailUrl, string $level): void
    {
        $creatorEmail = null;

        if (! empty($quotation->user_id)) {
            $creatorEmail = User::where('id', $quotation->user_id)->whereNotNull('email')->value('email');
        }

        if (! $creatorEmail && ! empty($quotation->created_by)) {
            $creatorEmail = User::where('name', $quotation->created_by)->whereNotNull('email')->value('email');
        }

        if (! $creatorEmail) {
            Log::warning('CREATOR EMAIL NOT FOUND', [
                'quotation_id' => $quotation->id,
                'user_id'      => $quotation->user_id,
                'created_by'   => $quotation->created_by,
            ]);

            return;
        }

        try {
            Mail::to($creatorEmail)->send(new QuotationApprovedMail($quotation, $detailUrl, $level));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyNextVerifier(Quotation $quotation, ?int $roleId, \Closure $verificationUrlFor): void
    {
        $nextRole = $roleId === null ? null : QuotationVerifierRole::tryFrom($roleId);

        if (! $nextRole) {
            return;
        }

        $recipients = User::role($nextRole->value)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new QuotationApprovalRequestMail(
                $quotation->load('items', 'customer', 'branch', 'user'),
                $verificationUrlFor($nextRole),
                $nextRole->label()
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
