{{-- resources/views/vendor_pos/penawaran_pdf_letter.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Penawaran {{ $penawaran->nomor_penawaran }}</title>
  <style>
    @page{
      size: A4 portrait;
      margin-top: 44mm;
      margin-bottom: 44mm;
      margin-left: 42mm;
      margin-right: 42mm;
    }

    *{ box-sizing:border-box; margin:0; padding:0 }
    body{
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size:10.1px;
      color:#222;
      line-height:1.45;
    }

    .content{ width:82%; margin:0 auto }
    .t-right{ text-align:right }
    .t-center{ text-align:center }

    /* Header */
    .hdr{ width:100%; border-collapse:collapse; margin-bottom:6px }
    .hdr td{ vertical-align:top }
    .hdr .logo img{
      height:18mm;
      width:auto;
    }
    .hdr .right{ text-align:right }

    .refrow{ width:100%; border-collapse:collapse; margin-bottom:10px }
    .refrow td{ vertical-align:top; font-size:11.5px }
    .refleft{ color:#222 }
    .refright{ text-align:right; color:#555 }

    .attnwrap{ width:100%; border-collapse:collapse; margin-bottom:8px }
    .attnwrap td{ vertical-align:top }
    .attn{ font-size:11.8px }
    .attn strong{ font-weight:700 }

    .subject{
      text-align:center;
      font-weight:700;
      font-size:13.8px;
      margin:12px 0 9px;
    }

    .p{ margin:4px 0 8px; text-align:justify }

    /* Bagian isi */
    .box{ width:94%; margin:8px auto 10px; padding:0 }
    .kv{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      border-top: 1px solid #cfd8dc;
      border-bottom: 1px solid #cfd8dc;
      margin-top: 6px;
      margin-bottom: 6px;
    }
    .kv tr:first-child td{ padding-top: 8px; }
    .kv tr:last-child td{ padding-bottom: 8px; }
    .kv tr + tr td{ border-top: 0.5px dashed #e6e6e6; }

    .no{ width:20px; font-weight:700 }
    .label{ width:215px; color:#555; font-size:9.8px }
    .colon{ width:8px }
    .value{ width:auto; line-height:1.32; font-size:9.8px }
    .value b{ font-weight:700 }

    /* Signature + contact */
    .sigrow{ width:100%; border-collapse:collapse; margin-top:8px }
    .sigrow td{ vertical-align:top; padding-right:10px }
    .sigrow td:last-child{ padding-right:0 }

    .contact{
      border: 1px solid #cfd8dc;
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 11.2px;
      background-clip: padding-box;
    }
    .contact b{
      display:block;
      margin-bottom:6px;
    }

    .qrwrap{ text-align:left; margin-left:4mm; margin-top:6mm; }

    /* Footer halaman 1 */
    .brand-band{
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      height: 18mm;
      border: none;
    }

    .footer{
      position: fixed;
      left: 42mm;
      right: 42mm;
      bottom: 6mm;
      text-align: center;
      font-size: 10.8px;
      padding: 0;
      border: none;
    }

    /* ================= HALAMAN 2 ================= */
    .page-break{
      page-break-before: always;
    }

    .terms-wrap{
      width:82%;
      margin:0 auto;
      padding-top:8mm;
    }

    .terms-title{
      text-align:center;
      font-weight:700;
      margin:8mm 0 5mm;
      font-size:13px;
      letter-spacing:.3px;
    }

    .terms{
      margin-top:2mm;
      text-align:justify;
      line-height:1.6;
      font-size:10.3px;
    }

    .terms-line{
      margin-bottom:3mm;
      text-align:justify;
      width:100%;
      display:block;
    }

    .sig-table{
      width:100%;
      border-collapse:collapse;
      margin-top:14mm;
    }
    .sig-table td{
      vertical-align:bottom;
      padding:0;
    }
    .sig-label{
      font-weight:700;
      padding-bottom:8mm;
    }
    .signbox{
      height:28mm;
    }
    .qr{
      width:20mm;
      height:auto;
    }
    .sig-name{
      font-weight:700;
      padding-top:4mm;
    }
    .sig-role{
      font-size:10px;
    }

    .tiny-note{
      margin-top:10mm;
      text-align:center;
      font-size:9px;
      color:#666;
      font-style:italic;
    }
  </style>
</head>
<body>
@php
  $nowID = \Carbon\Carbon::now()->translatedFormat('d F Y');
  $cust  = optional($penawaran->customer);

  $produkLines = $penawaran->items
    ->map(function($it){
      $p = $it->produk;
      if (!$p) return null;

      $uk = optional($p->ukuran);
      $st = optional($uk->satuan);

      $ukTxt = trim(implode(' ', array_filter([
        $uk->nama_ukuran ?? null,
        $st->nama_satuan ?? null,
      ])));

      $persen = $it->persen !== null
        ? rtrim(rtrim(number_format($it->persen, 2, '.', ''), '0'), '.')
        : '0';

      return trim(
        $p->nama_produk
        . ($ukTxt ? ' — ' . $ukTxt : '')
        . ' (' . $persen . '%)'
      );
    })
    ->filter()
    ->unique()
    ->values();

  $rupiah = fn($n) => 'Rp '.number_format((float)$n, 0, ',', '.');

  $defaultProduct   = 'Batu Pecah 2-3 (50%), 3-5 (50%) — Blending (Agregat)';
  $defaultTolerance = '1% dari total jumlah pengiriman';

  $hasTerms = !empty(trim($penawaran->syarat_ketentuan ?? ''));

  $acuanPembayaranMap = [
    'After loading'          => 'After loading',
    'Before loading'         => 'Before loading',
    'After unloading'        => 'After unloading',
    'Before unloading'       => 'Before unloading',
    'After invoice received' => 'After invoice received',
  ];

  $acuanPembayaran = $penawaran->acuan_pembayaran ?? null;
@endphp

{{-- ======================== HALAMAN 1 ======================== --}}
<div class="content">
  <br>

  <table class="hdr">
    <tr>
      <td class="logo left" style="width:50%">
        @if($logoLeft)
          <img src="{{ $logoLeft }}" alt="Logo Kiri">
        @endif
      </td>
      <td class="logo right" style="width:50%">
        @if($logoRight)
          <img src="{{ $logoRight }}" alt="Logo Kanan">
        @endif
      </td>
    </tr>
  </table>

  <table class="refrow">
    <tr>
      <td class="refleft" style="width:60%">
        No. Ref {{ $penawaran->nomor_penawaran }}
      </td>
      <td class="refright" style="width:40%">
        Jakarta, {{ $nowID }}
      </td>
    </tr>
  </table>

  <table class="attnwrap">
    <tr>
      <td style="width:100%">
        <div class="attn">
          <strong>Kepada Yth :</strong><br>
          {{ $cust->nama_perusahaan ?? '-' }}<br>
          {{ $cust->alamat_perusahaan ?? 'Alamat belum diisi' }}<br><br>

          <strong>UP. <u>{{ $penawaran->nama ?? '-' }}</u></strong><br>
          {{ $penawaran->jabatan ?? '-' }}
        </div>
      </td>
    </tr>
  </table>

  <div class="subject">Penawaran Harga Batu Pecah</div>

  <p class="p">Yth. Bapak/Ibu,</p>
  <p class="p">
    Bersama surat ini, perkenankan kami memperkenalkan bahwa kami dari PT Pro Energi sebagai Badan Hukum yang memiliki
    Izin Usaha Penjualan &amp; Jasa Pertambangan dari ESDM, bergerak di bidang pertambangan.
  </p>
  <p class="p">
    Dengan pengalaman, jaminan kualitas produk, sumber daya, dan fasilitas yang kami miliki, kami percaya dapat memenuhi
    kebutuhan Batu Pecah untuk <strong>{{ $cust->nama_perusahaan ?? '—' }}.</strong> Sehubungan dengan itu, berikut kami sampaikan penawaran:
  </p>

  <div class="box">
    <table class="kv">
      @if(($produkLines ?? collect())->isEmpty())
        <tr>
          <td class="no">1.</td>
          <td class="label"><b>Produk</b></td>
          <td class="colon">:</td>
          <td class="value"><b>{!! $defaultProduct !!}</b></td>
        </tr>
      @else
        @foreach($produkLines as $i => $line)
          <tr>
            @if($i === 0)
              <td class="no">1.</td>
              <td class="label"><b>Produk</b></td>
              <td class="colon">:</td>
            @else
              <td class="no"></td>
              <td class="label"></td>
              <td class="colon"></td>
            @endif
            <td class="value"><b>{{ $line }}</b></td>
          </tr>
        @endforeach
      @endif

      <tr>
        <td class="no">2.</td>
        <td class="label"><b>Abrasi</b></td>
        <td class="colon">:</td>
        <td class="value"><b>{{ $penawaran->abrasi ?? '0' }}</b></td>
      </tr>

      <tr>
        <td class="no">3.</td>
        <td class="label"><b>Harga per m&sup3;</b></td>
        <td class="colon">:</td>
        <td class="value">
          {{ $rupiah(($penawaran->harga_dasar ?? 0) + ($penawaran->oat ?? 0)) }}
          <span style="color:#666">(Harga belum termasuk PPN 11%)</span>
        </td>
      </tr>

      <tr>
        <td class="no">4.</td>
        <td class="label"><b>Metode Pembayaran</b></td>
        <td class="colon">:</td>
        <td class="value">
          @if($penawaran->tipe_pembayaran === 'CUSTOM')
            <div style="margin-top:4px">
              DP {{ number_format($penawaran->dp_persen, 0) }}%
              Repayment {{ number_format($penawaran->repayment_persen, 0) }}%
              after {{ number_format($penawaran->repayment_hari, 0) }} days
            </div>

          @elseif($penawaran->tipe_pembayaran === 'TOP')
            <div style="margin-top:4px">
              <b>
                TOP {{ $penawaran->top_hari }} Hari
                @if($acuanPembayaran)
                  - {{ $acuanPembayaranMap[$acuanPembayaran] ?? $acuanPembayaran }}
                @endif
              </b>
            </div>

          @else
            <div style="margin-top:4px">
              <b>{{ $penawaran->tipe_pembayaran }}</b>
              @if($acuanPembayaran)
                - {{ $acuanPembayaranMap[$acuanPembayaran] ?? $acuanPembayaran }}
              @endif
            </div>
          @endif
        </td>
      </tr>

      <tr>
        <td class="no">5.</td>
        <td class="label"><b>Metode Pemesanan</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->order_method }}</td>
      </tr>

      <tr>
        <td class="no">6.</td>
        <td class="label"><b>Metode Pengiriman</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->metode }}</td>
      </tr>

      <tr>
        <td class="no">7.</td>
        <td class="label"><b>Titik Serah Terima &amp; T&amp;C Bongkar</b></td>
        <td class="colon">:</td>
        <td class="value">{!! $penawaran->keterangan !!}</td>
      </tr>

      <tr>
        <td class="no">8.</td>
        <td class="label"><b>Toleransi</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->toleransi_penyusutan ?? $defaultTolerance }} %</td>
      </tr>

      <tr>
        <td class="no">9.</td>
        <td class="label"><b>Periode</b></td>
        <td class="colon">:</td>
        <td class="value">
          {{ \Carbon\Carbon::parse($penawaran->masa_berlaku)->translatedFormat('d F Y') }}
          -
          {{ \Carbon\Carbon::parse($penawaran->sampai_dengan)->translatedFormat('d F Y') }}
        </td>
      </tr>
    </table>
  </div>

  <p class="p">
    Besar harapan kami untuk dapat memperoleh kesempatan dan kepercayaan dari Bapak/Ibu guna menjalin kerja sama yang baik
    dengan perusahaan Bapak/Ibu. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
  </p>

  {{-- Kalau tidak ada terms, tanda tangan tampil di halaman 1 --}}
 
    <table class="sigrow">
      <tr>
        <td style="width:55%;">
          Best Regards,<br>
          <strong>PT. Pro Energi</strong>

          <div class="qrwrap">
            @if(!empty($qrBase64) && (int)$penawaran->disposisi_penawaran === 4)
              <img src="{{ $qrBase64 }}" style="width:20mm;height:20mm" alt="QR">
            @endif
          </div>

          <strong><u>Vica Krisdianatha</u></strong><br>
          Chief Executive Officer
        </td>

        <td style="width:45%;">
          <div class="contact">
            <b>Kontak :</b>
            <strong>{{ $contact['name'] }}</strong><br>
            {{ $contact['role'] }}<br>
            {{ $contact['phone'] }}<br>
            <a href="mailto:{{ e($contact['email']) }}">
              {{ $contact['email'] }}
            </a>
          </div>
        </td>
      </tr>
    </table>
 
</div>

<div class="brand-band"></div>
<div class="footer">
  <hr style="border:none; border-top:1px solid #000;">
  <strong>PT. Pro Energi, </strong> • Graha Irama Building 6th floor unit G, Jln. HR Rasuna Said Blok X1 Kav 1-2 •
  Telp. +021 5289 2321 • Fax +021 5289 2310 •
  <a href="https://www.proenergi.com" target="_blank" rel="noopener noreferrer">
    www.proenergi.com
  </a>
</div>

{{-- ======================== HALAMAN 2: SYARAT & KETENTUAN ======================== --}}
@if($hasTerms)
  <div class="page-break"></div>

  <div class="terms-wrap">
    <table class="hdr">
      <tr>
        <td class="logo left" style="width:50%">
          @if($logoLeft)
            <img src="{{ $logoLeft }}" alt="Logo Kiri">
          @endif
        </td>
        <td class="logo right" style="width:50%">
          @if($logoRight)
            <img src="{{ $logoRight }}" alt="Logo Kanan">
          @endif
        </td>
      </tr>
    </table>

    <div class="terms-title">Syarat &amp; Ketentuan</div>

    <div class="terms">
      @php
        $terms = $penawaran->syarat_ketentuan ?? '';
        $lines = preg_split("/\r\n|\n|\r/", trim($terms));
      @endphp

      @foreach($lines as $line)
        @if(trim($line) !== '')
          <div class="terms-line">
            {{ ltrim($line) }}
          </div>
        @endif
      @endforeach
    </div>

    <table class="sig-table">
      <tr>
        <td class="sig-label">Best Regards,<br>PT. Pro Energi</td>
        <td class="sig-label t-right">{{ strtoupper($cust->nama_perusahaan ?? '-') }}</td>
      </tr>

      <tr>
        <td class="signbox">
          @if(!empty($qrBase64) && (int)$penawaran->disposisi_penawaran === 4)
            <img src="{{ $qrBase64 }}" class="qr" alt="QR">
          @endif
        </td>
        <td class="signbox t-right"></td>
      </tr>

      <tr>
        <td class="sig-name">
          Vica Krisdianatha
          <div class="sig-role">Chief Executive Officer</div>
        </td>
        <td class="sig-name t-right">
          {{ $penawaran->nama ?? 'Customer' }}
          <div class="sig-role">{{ $penawaran->jabatan ?? '' }}</div>
        </td>
      </tr>
    </table>

    <div class="tiny-note">
      (This form is valid with sign by computerized system)<br>
      Printed by {{ auth()->user()->name ?? 'system' }} {{ now()->format('d/m/Y H:i:s') }} WIB
    </div>
  </div>
@endif
</body>
</html>