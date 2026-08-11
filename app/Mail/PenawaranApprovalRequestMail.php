<?php

namespace App\Mail;

use App\Models\Penawaran;
use App\Models\PenawaranProenergi;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penawaran|PenawaranProenergi $penawaran;
    public string $verificationUrl;
    // $stage menentukan approver yang sedang diminta approve: 'bm' atau 'om'.
    public string $stage;

    public function __construct(Penawaran|PenawaranProenergi $penawaran, string $verificationUrl, string $stage)
    {
        $this->penawaran = $penawaran;
        $this->verificationUrl = $verificationUrl;
        $this->stage = $stage;
    }

    public function build()
    {
        $label = $this->stage === 'bm' ? 'BM' : 'OM';

        return $this->subject("[Approval {$label}] Penawaran {$this->penawaran->nomor_penawaran} — Menunggu persetujuan Anda")
            ->view('emails.penawaran.approval-request')
            ->with([
                'penawaran' => $this->penawaran,
                'verificationUrl' => $this->verificationUrl,
                'stage' => $this->stage,
            ]);
    }
}
