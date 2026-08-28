<?php

namespace App\Mail;

use App\Models\Penawaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penawaran $penawaran;
    public string $detailUrl;
    public string $level;

    public function __construct(Penawaran $penawaran, string $detailUrl, string $level)
    {
        $this->penawaran = $penawaran;
        $this->detailUrl = $detailUrl;
        $this->level = $level;
    }

    public function build()
    {
        $subjectSuffix = $this->level === 'om'
            ? 'Disetujui Penuh'
            : 'Disetujui BM, menunggu OM/CEO';

        return $this->subject("[Update] Penawaran {$this->penawaran->nomor_penawaran} — {$subjectSuffix}")
            ->view('emails.penawaran.approved')
            ->with([
                'penawaran' => $this->penawaran,
                'detailUrl' => $this->detailUrl,
                'level' => $this->level,
            ]);
    }
}
