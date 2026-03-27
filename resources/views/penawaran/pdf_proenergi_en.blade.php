{{-- resources/views/vendor_pos/penawaran_pdf_letter.blade.php --}}
<!DOCTYPE html>
<html lang="en">
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

    .hdr{ width:100%; border-collapse:collapse; margin-bottom:6px }
    .hdr td{ vertical-align:top }
    .logo img{ height:14mm; width:auto }
    .right{ text-align:right; color:#555; font-size:11.5px }

    .refrow{ width:100%; border-collapse:collapse; margin-bottom:10px }
    .refrow td{ vertical-align:top; font-size:11.5px }
    .refleft{ color:#222 }
    .refright{ text-align:right; color:#555 }

    .attnwrap{ width:100%; border-collapse:collapse; margin-bottom:8px }
    .attnwrap td{ vertical-align:top }
    .attn{ font-size:11.8px }
    .attn strong{ font-weight:700 }

    .subject{ text-align:center; font-weight:700; font-size:13.8px; margin:12px 0 9px }
    .p{ margin:4px 0 8px; text-align:justify }

    .box{ width:92%; margin:8px auto 10px; padding:0 }
    .kv{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      border-top: 1px solid #cfd8dc;
      border-bottom: 1px solid #cfd8dc;
      margin-top: 6px;
      margin-bottom: 6px;
    }
    .kv tr:first-child td{ padding-top:8px; }
    .kv tr:last-child td{ padding-bottom:8px; }
    .kv tr + tr td{ border-top: 0.5px dashed #e6e6e6; }

    .no{ width:20px; font-weight:700 }
    .label{ width:165px; color:#555; font-size:9.8px }
    .colon{ width:8px }
    .value{ width:auto; line-height:1.32; font-size:9.8px }
    .value b{ font-weight:700 }

    .sigrow{ width:100%; border-collapse:collapse; margin-top:8px }
    .sigrow td{ vertical-align:top; padding-right:10px }
    .sigrow td:last-child{ padding-right:0 }

    .contact{
      border: 1px solid;
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 11.2px;
      background-clip: padding-box;
    }
    .contact b{
      display:block;
      margin-bottom:6px;
    }

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

    .qrwrap{ text-align:left; margin-left:4mm; margin-top:6mm; }
    .qrimg{ width:18mm; height:18mm; display:inline-block; }
    .qrimg img{ width:18mm; height:18mm; display:block; }
    .qrimg svg{ width:18mm; height:18mm; display:block; }

    /* halaman 2 */
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
    .t-right{ text-align:right }

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
      $p  = $it->produk;
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

  $defaultProduct   = 'Crushed Stone 2-3 (50%), 3-5 (50%) — Blending (Aggregate)';
  $defaultTolerance = '1% of the total number of shipments';

  $hasTerms = !empty(trim($penawaran->syarat_ketentuan ?? ''));

  $acuanPembayaranMap = [
    'After loading'          => 'After loading',
    'Before loading'         => 'Before loading',
    'After unloading'        => 'After unloading',
    'Before unloading'       => 'Before unloading',
    'After invoice received' => 'After invoice received',
  ];
@endphp

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
          Attention to :<br>
          <strong>{{ $cust->nama_perusahaan ?? '-' }}</strong><br>
          {{ $cust->alamat_perusahaan ?? 'Address not available' }}<br><br>

          <strong>UP. <u>{{ $penawaran->nama ?? '-' }}</u></strong><br>
          {{ $penawaran->jabatan ?? '-' }}
        </div>
      </td>
    </tr>
  </table>

  <div class="subject">Price Quotation of Crushed Stone</div>

  <p class="p">Dear Sir,</p>
  <p class="p">
    Together with this letter, please allow us to introduce ourselves as PT Pro Energi, a legal entity holding
    a Sales Transportation Mining Business License from ESDM, engaged in the mining sector.
  </p>
  <p class="p">
    With our experience, product quality assurance, resources, and facilities, we believe that we are able to fulfill
    the needs of Crushed Stone for <strong>{{ $cust->nama_perusahaan ?? '—' }}.</strong>
    Therefore, we are pleased to submit our offer as follows:
  </p>

  <div class="box">
    <table class="kv">
      @if(($produkLines ?? collect())->isEmpty())
        <tr>
          <td class="no">1.</td>
          <td class="label"><b>Product</b></td>
          <td class="colon">:</td>
          <td class="value"><b>{!! $defaultProduct !!}</b></td>
        </tr>
      @else
        @foreach($produkLines as $i => $line)
          <tr>
            @if($i === 0)
              <td class="no">1.</td>
              <td class="label"><b>Product</b></td>
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
        <td class="label"><b>Abrasion</b></td>
        <td class="colon">:</td>
        <td class="value"><b>{{ $penawaran->abrasi ?? '0%' }}</b></td>
      </tr>

      <tr>
        <td class="no">3.</td>
        <td class="label"><b>Price per m&sup3;</b></td>
        <td class="colon">:</td>
        <td class="value">
          {{ $rupiah(($penawaran->harga_dasar ?? 0) + ($penawaran->oat ?? 0)) }}
          <span style="color:#666">(Price excludes 11% VAT)</span>
        </td>
      </tr>

      <tr>
        <td class="no">4.</td>
        <td class="label"><b>Payment Method</b></td>
        <td class="colon">:</td>
        <td class="value">
          @if($penawaran->tipe_pembayaran === 'CUSTOM')
            <div style="margin-top:4px">
              Down Payment {{ number_format($penawaran->dp_persen, 0) }}%
              Repayment {{ number_format($penawaran->repayment_persen, 0) }}%
              after {{ number_format($penawaran->repayment_hari, 0) }} days
            </div>

          @elseif($penawaran->tipe_pembayaran === 'TOP')
            <div style="margin-top:4px">
              <b>
                TOP {{ $penawaran->top_hari }} Days
                @if($penawaran->acuan_pembayaran)
                  - {{ $acuanPembayaranMap[$penawaran->acuan_pembayaran] ?? $penawaran->acuan_pembayaran }}
                @endif
              </b>
            </div>

          @else
            <div style="margin-top:4px">
              <b>{{ $penawaran->tipe_pembayaran }}</b>
              @if($penawaran->acuan_pembayaran)
                - {{ $acuanPembayaranMap[$penawaran->acuan_pembayaran] ?? $penawaran->acuan_pembayaran }}
              @endif
            </div>
          @endif
        </td>
      </tr>

      <tr>
        <td class="no">5.</td>
        <td class="label"><b>Ordering Method</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->order_method }}</td>
      </tr>

      <tr>
        <td class="no">6.</td>
        <td class="label"><b>Delivery Method</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->metode }}</td>
      </tr>

      <tr>
        <td class="no">7.</td>
        <td class="label"><b>Handover Point &amp; Unloading T&amp;C</b></td>
        <td class="colon">:</td>
        <td class="value">{!! $penawaran->keterangan !!}</td>
      </tr>

      <tr>
        <td class="no">8.</td>
        <td class="label"><b>Tolerance</b></td>
        <td class="colon">:</td>
        <td class="value">{{ $penawaran->toleransi_penyusutan ?? $defaultTolerance }} %</td>
      </tr>

      <tr>
        <td class="no">9.</td>
        <td class="label"><b>Price &amp; Terms due to</b></td>
        <td class="colon">:</td>
        <td class="value">
          {{ \Carbon\Carbon::parse($penawaran->masa_berlaku)->translatedFormat('d F Y') }}
          -
          {{ \Carbon\Carbon::parse($penawaran->sampai_dengan)->translatedFormat('d F Y') }}
        </td>
      </tr>
    </table>
  </div>

  <br>

  <p class="p">
    We hope to have the opportunity and trust from you to establish a good business relationship with your company.
    Thank you for your attention and cooperation.
  </p>


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

          <br>
          <strong><u>Vica Krisdianatha</u></strong><br>
          Operation Manager
        </td>

        <td style="width:45%;">
          <div class="contact">
            <b>Contact person :</b>
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

    <div class="terms-title">Terms &amp; Conditions</div>

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
          <div class="sig-role">Operation Manager</div>
        </td>
        <td class="sig-name t-right">
          {{ $penawaran->nama ?? 'Customer' }}
          <div class="sig-role">{{ $penawaran->jabatan ?? '' }}</div>
        </td>
      </tr>
    </table>

    <div class="tiny-note">
      (This document is valid with computerized approval)<br>
      Printed by {{ auth()->user()->name ?? 'system' }} {{ now()->format('d/m/Y H:i:s') }} WIB
    </div>
  </div>
@endif

</body>
</html>