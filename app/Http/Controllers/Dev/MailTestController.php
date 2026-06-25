<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailTestController extends Controller
{
    /**
     * GET /api/dev/test-email
     *
     * Pakai ini kalo mau test kirim email
     */
    public function send(Request $request)
    {
        if (app()->environment('production')) {
            abort(404);
        }

        $to = $request->query('to', config('mail.dev_redirect') ?: config('mail.from.address'));

        $subject = '[TESTING - ABAIKAN] Email Uji Coba Sistem TDS CRS';

        $body = implode("\n", [
            'Halo,',
            '',
            'Ini adalah email TESTING yang dikirim otomatis untuk uji coba sistem.',
            'Mohon ABAIKAN email ini. Tidak ada tindakan yang perlu dilakukan.',
            '',
            'Dikirim pada : ' . now()->toDateTimeString(),
            'Environment  : ' . app()->environment(),
            '',
            '-- Pesan ini dibuat oleh sistem untuk keperluan pengujian --',
        ]);

        Mail::raw($body, function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });

        return response()->json([
            'message'           => 'Email testing berhasil dikirim.',
            'requested_to'      => $to,
            'redirected_to'     => config('mail.dev_redirect'),
            'environment'       => app()->environment(),
            'note'              => 'Di local/development email dialihkan ke MAIL_DEV_REDIRECT.',
        ]);
    }
}
