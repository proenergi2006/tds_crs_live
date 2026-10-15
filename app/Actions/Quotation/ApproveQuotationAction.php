<?php

namespace App\Actions\Quotation;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Enums\QuotationStatus;
use App\Enums\QuotationVerifierRole;
use App\Exceptions\BusinessRuleException;
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
    public function execute(Quotation $quotation, User $actor, ?string $note, string $detailUrl, string $verificationUrl): string
    {
        return DB::transaction(function () use ($quotation, $actor, $note, $detailUrl, $verificationUrl) {
            $quotation = Quotation::lockForUpdate()->findOrFail($quotation->id);
            $step      = $quotation->activeVerificationStep();

            if (! $step) {
                throw new BusinessRuleException('Penawaran tidak sedang menunggu verifikasi.');
            }

            if (! $quotation->isValidOn(now())) {
                throw new BusinessRuleException('Masa berlaku penawaran sudah lewat, penawaran tidak bisa disetujui. Tolak agar Marketing membuat penawaran baru.');
            }

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
                $this->notifyNextVerifier($quotation, $nextStep?->id_role, $verificationUrl);
            }

            return $isFinal
                ? 'Penawaran disetujui dan langsung final'
                : 'Penawaran disetujui & notifikasi terkirim';
        });
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

    private function notifyNextVerifier(Quotation $quotation, ?int $roleId, string $verificationUrl): void
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
                $verificationUrl,
                $nextRole->label()
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
