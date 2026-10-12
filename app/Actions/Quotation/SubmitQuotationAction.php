<?php

namespace App\Actions\Quotation;

use App\Enums\ProductPriceCogsBasis;
use App\Enums\QuotationStatus;
use App\Enums\QuotationTransportSource;
use App\Enums\QuotationVerifierRole;
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

    public function execute(Quotation $quotation, \Closure $verificationUrlFor): array
    {
        if ($quotation->status !== QuotationStatus::Draft) {
            return [
                'success' => false,
                'status'  => 400,
                'message' => 'Penawaran sudah diajukan sebelumnya',
            ];
        }

        $this->assertTransportPricingReady($quotation);

        $resolution = $this->resolveVerifier->execute($quotation);
        $verifier   = $resolution['verifier'];

        DB::beginTransaction();

        try {
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
                DB::commit();

                return [
                    'message' => "Penawaran berhasil diajukan, tetapi email {$verifier->label()} tidak ditemukan.",
                ];
            }

            try {
                Mail::to($recipients)->send(new QuotationApprovalRequestMail(
                    $quotation->load('items', 'customer', 'branch', 'user'),
                    $verificationUrlFor($verifier),
                    $verifier->label()
                ));
            } catch (\Throwable $mailErr) {
                DB::rollBack();

                Log::error('AJUKAN PENAWARAN - GAGAL KIRIM EMAIL VERIFIKATOR', [
                    'quotation_id' => $quotation->id,
                    'verifier'     => $verifier->label(),
                    'error'        => $mailErr->getMessage(),
                ]);

                return [
                    'success' => false,
                    'status'  => 500,
                    'message' => "Penawaran gagal diajukan karena email {$verifier->label()} gagal dikirim.",
                    'error'   => $mailErr->getMessage(),
                ];
            }

            DB::commit();

            return [
                'message' => "Penawaran berhasil diajukan dan email berhasil dikirim ke {$verifier->label()}.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('AJUKAN PENAWARAN - ERROR', [
                'quotation_id' => $quotation->id ?? null,
                'error'        => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => 500,
                'message' => 'Gagal mengajukan penawaran',
                'error'   => $e->getMessage(),
            ];
        }
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
