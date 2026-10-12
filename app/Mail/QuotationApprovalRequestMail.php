<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quotation $quotation;
    public string $verificationUrl;
    public string $verifierLabel;

    public function __construct(Quotation $quotation, string $verificationUrl, string $verifierLabel)
    {
        $this->quotation = $quotation;
        $this->verificationUrl = $verificationUrl;
        $this->verifierLabel = $verifierLabel;
    }

    public function build()
    {
        return $this->subject("[Approval {$this->verifierLabel}] Penawaran {$this->quotation->nomor_penawaran} — Menunggu persetujuan Anda")
            ->view('emails.quotation.approval-request')
            ->with([
                'quotation' => $this->quotation,
                'verificationUrl' => $this->verificationUrl,
                'verifierLabel' => $this->verifierLabel,
            ]);
    }
}
