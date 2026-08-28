<?php

namespace App\Mail;

use App\Models\Penawaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penawaran $penawaran;
    public string $detailUrl;
    public string $alasan;
    public ?string $catatan;

    public function __construct(Penawaran $penawaran, string $detailUrl, string $alasan, ?string $catatan = null)
    {
        $this->penawaran = $penawaran;
        $this->detailUrl = $detailUrl;
        $this->alasan    = $alasan;
        $this->catatan   = $catatan;
    }

    public function build()
    {
        return $this->subject("[Ditolak] Penawaran {$this->penawaran->nomor_penawaran} — {$this->alasan}")
            ->view('emails.penawaran.rejected')
            ->with([
                'penawaran' => $this->penawaran,
                'detailUrl' => $this->detailUrl,
                'alasan'    => $this->alasan,
                'catatan'   => $this->catatan,
            ]);
    }
}
