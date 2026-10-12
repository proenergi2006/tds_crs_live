<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quotation $quotation;
    public string $detailUrl;
    public string $level;

    public function __construct(Quotation $quotation, string $detailUrl, string $level)
    {
        $this->quotation = $quotation;
        $this->detailUrl = $detailUrl;
        $this->level = $level;
    }

    public function build()
    {
        $subjectSuffix = $this->level === 'om'
            ? 'Disetujui Penuh'
            : 'Disetujui BM, menunggu OM/CEO';

        return $this->subject("[Update] Penawaran {$this->quotation->nomor_penawaran} — {$subjectSuffix}")
            ->view('emails.quotation.approved')
            ->with([
                'quotation' => $this->quotation,
                'detailUrl' => $this->detailUrl,
                'level' => $this->level,
            ]);
    }
}
