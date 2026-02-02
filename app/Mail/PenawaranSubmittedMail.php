<?php

// app/Mail/PenawaranSubmittedMail.php

namespace App\Mail;

use App\Models\Penawaran;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PenawaranSubmittedMail extends Mailable
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
        return $this->subject('Penawaran baru menunggu persetujuan (BM)')
            ->view('emails.penawaran.submitted')
            ->with([
                'penawaran' => $this->penawaran,
                'detailUrl' => $this->detailUrl,
            ]);
    }
}
