<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quotation $quotation;
    public string $detailUrl;
    public string $alasan;
    public ?string $catatan;

    public function __construct(Quotation $quotation, string $detailUrl, string $alasan, ?string $catatan = null)
    {
        $this->quotation = $quotation;
        $this->detailUrl = $detailUrl;
        $this->alasan    = $alasan;
        $this->catatan   = $catatan;
    }

    public function build()
    {
        return $this->subject("[Ditolak] Penawaran {$this->quotation->nomor_penawaran} — {$this->alasan}")
            ->view('emails.quotation.rejected')
            ->with([
                'quotation' => $this->quotation,
                'detailUrl' => $this->detailUrl,
                'alasan'    => $this->alasan,
                'catatan'   => $this->catatan,
            ]);
    }
}
