<?php

namespace App\Mail;

use App\Models\Penawaran;
use App\Models\PenawaranProenergi;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penawaran|PenawaranProenergi $penawaran;
    public string $detailUrl;
    public string $alasan;   // mis: "Ditolak BM" / "Ditolak OM"
    public ?string $catatan; // optional

    public function __construct(Penawaran|PenawaranProenergi $penawaran, string $detailUrl, string $alasan, ?string $catatan = null)
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
