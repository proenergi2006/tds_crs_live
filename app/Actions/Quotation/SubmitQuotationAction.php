<?php

namespace App\Actions\Quotation;

use App\Enums\ProductPriceCogsBasis;
use App\Enums\QuotationStatus;
use App\Enums\QuotationTransportSource;
use App\Enums\QuotationVerifierRole;
use App\Exceptions\BusinessRuleException;
use App\Mail\QuotationApprovalRequestMail;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Approval\DocumentApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SubmitQuotationAction
{
    public function __construct(private readonly ResolveQuotationVerifierAction $resolveVerifier)
    {
    }

    public function execute(Quotation $quotation, string $verificationUrl): string
    {
        if ($quotation->status !== QuotationStatus::Draft) {
            throw new BusinessRuleException('Penawaran sudah diajukan sebelumnya');
        }

        if (! $quotation->isValidOn(now())) {
            throw new BusinessRuleException('Masa berlaku penawaran tidak mencakup hari ini. Ubah periode ke periode aktif sebelum diajukan.');
        }

        $this->assertTransportPricingReady($quotation);

        $resolution = $this->resolveVerifier->execute($quotation);
        $verifier   = $resolution['verifier'];

        return DB::transaction(function () use ($quotation, $resolution, $verifier, $verificationUrl) {
            foreach ($quotation->items as $item) {
                $item->update(['price_tier' => $resolution['item_tiers'][$item->id]]);
            }

            $quotation->update([
                'status'     => QuotationStatus::WaitingVerification,
                'price_tier' => $resolution['tier'],
                'updated_at' => now(),
            ]);

            (new DocumentApprovalService())->startSingleStepCycle($quotation, $verifier->value);

            $recipients = $this->recipientsFor($verifier);

            if (empty($recipients)) {
                return "Penawaran berhasil diajukan, tetapi email {$verifier->label()} tidak ditemukan.";
            }

            try {
                Mail::to($recipients)->send(new QuotationApprovalRequestMail(
                    $quotation->load('items', 'customer', 'branch', 'user'),
                    $verificationUrl,
                    $verifier->label()
                ));
            } catch (\Throwable $mailErr) {
                Log::error('AJUKAN PENAWARAN - GAGAL KIRIM EMAIL VERIFIKATOR', [
                    'quotation_id' => $quotation->id,
                    'verifier'     => $verifier->label(),
                    'error'        => $mailErr->getMessage(),
                ]);

                throw new BusinessRuleException(
                    "Penawaran gagal diajukan karena email {$verifier->label()} gagal dikirim.",
                    500,
                    $mailErr
                );
            }

            return "Penawaran berhasil diajukan dan email berhasil dikirim ke {$verifier->label()}.";
        });
    }

    private function assertTransportPricingReady(Quotation $quotation): void
    {
        $quotation->loadMissing('items');

        $withSnapshot = $quotation->items->filter(fn ($item) => $item->transport_snapshot !== null);

        if ($withSnapshot->isEmpty()) {
            return;
        }

        if ($withSnapshot->count() !== $quotation->items->count()) {
            throw ValidationException::withMessages(['items' => 'Harga transport sebagian item belum terkunci. Simpan ulang penawaran.']);
        }

        if ($quotation->transport_basis === null) {
            throw ValidationException::withMessages(['items' => 'Basis transport penawaran belum ditentukan. Simpan ulang penawaran.']);
        }

        $inconsistent = ValidationException::withMessages(['items' => 'Rincian transport item tidak konsisten dengan basis penawaran.']);
        $isFranco     = $quotation->transport_basis === ProductPriceCogsBasis::Franco;

        foreach ($quotation->items as $item) {
            $source = QuotationTransportSource::tryFrom((string) ($item->transport_snapshot['source'] ?? ''));

            if ($source === null || ($source === QuotationTransportSource::ProductPrice) !== $isFranco || (float) $item->transport_price < 0) {
                throw $inconsistent;
            }
        }

        if (! $isFranco && $quotation->items->pluck('transport_price')->unique()->count() > 1) {
            throw $inconsistent;
        }
    }

    private function recipientsFor(QuotationVerifierRole $verifier): array
    {
        return User::role($verifier->value)
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
