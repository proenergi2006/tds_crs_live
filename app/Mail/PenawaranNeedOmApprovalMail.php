<?php

namespace App\Mail;

use App\Models\Penawaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranNeedOmApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penawaran $penawaran;
    public string $detailUrl;

    public function __construct(Penawaran $penawaran, string $detailUrl)
    {
        $this->penawaran = $penawaran;
        $this->detailUrl = $detailUrl;
    }

    public function build()
    {
        return $this->subject('Penawaran menunggu persetujuan (OM)')
            ->view('emails.penawaran.need-om-approval') // resources/views/emails/penawaran/need-om-approval.blade.php
            ->with([
                'penawaran' => $this->penawaran,
                'detailUrl' => $this->detailUrl,
            ]);
    }
}
