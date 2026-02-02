@php
    /** @var \App\Models\Penawaran $penawaran */
    $p = $penawaran;
    $customer = $p->customer->nama_perusahaan ?? '-';
    $cabang   = $p->cabang->nama_cabang ?? '-';
    // variabel tambahan dari Mailable: $alasan (string), $catatan (nullable)
@endphp

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Penawaran Ditolak</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f5f7fb;font-family:Segoe UI,Arial,sans-serif;color:#111;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
      <td align="center" style="padding:24px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,0.06);">
          <tr>
            <td style="background:#1f2937;padding:20px 24px;color:#fff;">
              <h1 style="margin:0;font-size:18px;font-weight:600;">Penawaran Ditolak</h1>
              <p style="margin:4px 0 0;font-size:12px;opacity:.85;">Informasi status terbaru</p>
            </td>
          </tr>

          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 14px;font-size:14px;">Halo,</p>
              <p style="margin:0 0 18px;font-size:14px;line-height:1.6;">
                Penawaran berikut <strong>ditolak</strong>.
              </p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                <tr>
                  <td style="padding:8px 0;color:#6b7280;width:40%;">Nomor Penawaran</td>
                  <td style="padding:8px 0;font-weight:600;color:#111827;">{{ $p->nomor_penawaran }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Customer</td>
                  <td style="padding:8px 0;font-weight:600;color:#111827;">{{ $customer }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Cabang</td>
                  <td style="padding:8px 0;color:#111827;">{{ $cabang }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Status</td>
                  <td style="padding:8px 0;color:#111827;">{{ $p->status }}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Alasan</td>
                  <td style="padding:8px 0;color:#111827;">{{ $alasan }}</td>
                </tr>
                @if(!empty($catatan))
                <tr>
                  <td style="padding:8px 0;color:#6b7280;vertical-align:top;">Catatan</td>
                  <td style="padding:8px 0;color:#111827;white-space:pre-line;">{{ $catatan }}</td>
                </tr>
                @endif
              </table>

              <div style="margin:22px 0;">
                <a href="{{ $detailUrl }}"
                   style="display:inline-block;background:#4f46e5;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:600;">
                  Lihat Detail Penawaran
                </a>
              </div>

              <p style="margin:12px 0 0;font-size:12px;color:#6b7280;">
                *Email ini dikirim otomatis oleh sistem. Mohon tidak membalas email ini.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#f9fafb;padding:14px 24px;text-align:center;font-size:12px;color:#9ca3af;">
              © {{ date('Y') }} — Sistem Penawaran
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
