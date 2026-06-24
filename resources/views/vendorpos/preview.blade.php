<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Purchase Order {{ $po->nomor_po ?? '-' }}</title>
  <style>
    @page { margin: 24mm 26mm 42mm 26mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; color: #222; line-height: 1.45; }
    .content { width: 92%; margin: 0 auto; padding-bottom: 44mm; }

    .t-right  { text-align: right; }
    .t-center { text-align: center; }

    .hdr { width: 100%; border-collapse: collapse; }
    .hdr td { vertical-align: middle; }

    .title     { text-align: center; font-size: 16px; font-weight: 700; letter-spacing: .5px; margin: 8px 0 6px; }
    .underline { width: 220px; margin: 0 auto 12px; border-bottom: 1px solid #333; }

    .meta { width: 100%; border-collapse: collapse; margin: 6px 0 14px; }
    .meta td { font-size: 11px; vertical-align: top; }

    .parties { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .parties td { vertical-align: top; padding-right: 12px; }
    .block h4 { font-size: 11.2px; font-weight: 700; margin: 0 0 6px; border-bottom: 1px solid #999; padding-bottom: 4px; }
    .block p  { margin: 0 0 2px; }

    .mini-box { border: 1px solid #cfcfcf; border-radius: 6px; padding: 8px 10px; font-size: 10.2px; display: inline-block; min-width: 180px; }
    .mini-kv  { width: 100%; border-collapse: collapse; }
    .mini-kv td { padding: 3px 4px; vertical-align: top; }
    .mini-kv .lbl { width: 100px; color: #555; }
    .mini-kv .colon { width: 6px; }

    .po-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .po-table th, .po-table td { border: 1px solid #333; padding: 7px 9px; }
    .po-table thead th { background: #efefef; font-weight: 700; text-align: center; }

    .row-nt { width: 100%; border-collapse: collapse; margin: 14px 0 6px; }
    .note   { border: 1px solid #333; border-radius: 4px; padding: 8px 10px; min-height: 80px; font-size: 10.5px; }
    .note h5 { margin: 0 0 6px; font-size: 11px; }

    .totals    { border: 1px solid #333; border-radius: 4px; padding: 8px 10px; }
    .totals-kv { width: 100%; border-collapse: collapse; }
    .totals-kv td   { padding: 5px 4px; vertical-align: top; }
    .totals-kv .lbl { width: 65%; }
    .totals-kv .colon { width: 12px; text-align: center; }
    .totals-kv .val { text-align: right; white-space: nowrap; }
    .totals-kv .grand .lbl,
    .totals-kv .grand .val { font-weight: 800; }

    .saytbl { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .saytbl td { padding: 6px 8px; vertical-align: middle; }
    .saytbl .lbl { width: 40px; }
    .saybox { border: 1px solid #333; border-radius: 4px; padding: 6px 8px; font-size: 10.6px; }

    .sig-table { width: 100%; border-collapse: collapse; margin-top: 12mm; }
    .sig-table td { vertical-align: bottom; padding: 0; }
    .label   { font-weight: 700; padding-bottom: 8mm; }
    .signbox { height: 28mm; }
    .qr      { width: 28mm; height: auto; }
    .name    { font-weight: 700; padding-top: 4mm; }
    .role    { font-size: 10px; }

    .page-break   { page-break-before: always; }
    .terms-wrap   { width: 88%; margin: 0 auto; }
    .terms-header { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 6mm; }
    .pro-right    { text-align: right; }
    .pro-right .brand { font-size: 22px; font-weight: 800; color: #2557A7; letter-spacing: .5px; }
    .pro-right .addr  { font-size: 10px; margin-top: 2mm; line-height: 1.35; }
    .terms-title  { text-align: center; font-weight: 700; font-size: 13px; letter-spacing: 0.3px; margin: 10mm 0 6mm; }
    .terms li     { line-height: 1.55; text-align: justify; margin-bottom: 4mm; }
    .tiny-note    { margin-top: 10mm; text-align: center; font-size: 9px; color: #666; font-style: italic; }
  </style>
</head>
<body>
@php
  $hasTerms = !empty(trim($po->terms_condition ?? ''));
  $rp0      = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $date     = $po->tanggal_inven
                ? \Carbon\Carbon::parse($po->tanggal_inven)->translatedFormat('d F Y')
                : '-';
  $items    = $po->produks ?? [];

  $subtotal = (float) ($po->subtotal ?? 0);
  $ppn      = (float) ($po->ppn11 ?? 0);
  $grand    = (float) ($po->total_order ?? 0);

  $amountWords = '-';
  if (class_exists('NumberFormatter')) {
    $fmt         = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
    $amountWords = ucfirst($fmt->format((int) $grand)) . ' rupiah';
  }
@endphp

<!-- ======================== HALAMAN 1 ======================== -->
<div class="content">
  <table class="hdr">
    <tr>
      <td style="width:50%">
        @if($logoLeft)
          <img src="{{ $logoLeft }}" width="95px" alt="Tri Daya Selaras">
        @endif
      </td>
      <td style="width:50%; text-align:right">
        @if($logoRight)
          <img src="{{ $logoRight }}" style="height:auto; width:100px" alt="Crushed Stone">
        @endif
      </td>
    </tr>
  </table>

  <div class="title">PURCHASE ORDER</div>
  <div class="underline"></div>

  <table class="meta">
    <tr>
      <td>PO Number : <strong>{{ $po->nomor_po ?? '-' }}</strong></td>
      <td class="t-right">PO Date : {{ $date }}</td>
    </tr>
  </table>

  <table class="parties">
    <tr>
      <td style="width:45%">
        <div class="block">
          <h4>PT TRI DAYA SELARAS</h4>
          <p>GRAHA IRAMA BUILDING LT.6</p>
          <p>UNIT G, Jl. HR Rasuna Said KAV 1-2</p>
          <p>KUNINGAN TIMUR JAKARTA SELATAN</p>
        </div>
      </td>
      <td style="width:35%">
        <div class="block">
          <h4>VENDOR</h4>
          <p>{{ optional($po->vendor)->nama_vendor ?? '-' }}</p>
          @if(!empty(optional($po->vendor)->alamat))
            <p>{{ optional($po->vendor)->alamat }}</p>
          @endif
        </div>
      </td>
      <td style="width:20%; text-align:right">
        <div class="mini-box">
          <table class="mini-kv">
            <tr>
              <td class="lbl">Terms</td>
              <td class="colon">:</td>
              <td>{{ $po->terms ?? '-' }}</td>
            </tr>
            <tr>
              <td class="lbl">Vendor is taxable</td>
              <td class="colon">:</td>
              <td>YES</td>
            </tr>
            <tr>
              <td class="lbl">Delivery Date</td>
              <td class="colon">:</td>
              <td></td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <table class="po-table">
    <thead>
      <tr>
        <th style="width:22%">Item</th>
        <th style="width:14%">Quantity (m&sup3;)</th>
        <th style="width:17%">Unit Price (IDR)</th>
        <th style="width:17%">Total Price (IDR)</th>
        <th style="width:10%">Tax Code</th>
        <th style="width:20%">Tax Amount</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $it)
        @php
          $produk     = optional($it->produk);
          $volume     = (float) ($it->volume_po ?? 0);
          $harga      = (float) ($it->harga_tebus ?? 0);
          $jumlah     = (float) ($it->jumlah_harga ?? 0);
          $taxAmount  = (float) ($it->tax_amount ?? 0);
        @endphp
        <tr>
          <td>
            <strong>{{ $produk->merk_dagang ?? '-' }}</strong><br>
            {{ $produk->nama_produk ?? '-' }}{{ optional($produk->ukuran)->nama_ukuran ? ' | ' . $produk->ukuran->nama_ukuran : '' }}
          </td>
          <td class="t-right">{{ number_format($volume, 2, ',', '.') }}</td>
          <td class="t-right">{{ $rp0($harga) }}</td>
          <td class="t-right">{{ $rp0($jumlah) }}</td>
          <td class="t-center">{{ $it->kd_tax ?? '-' }}</td>
          <td class="t-right">{{ $taxAmount > 0 ? $rp0($taxAmount) : '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="t-center">Tidak ada item</td></tr>
      @endforelse
    </tbody>
  </table>

  <table class="row-nt">
    <tr>
      <td style="width:55%; padding-right:10px; vertical-align:top">
        <div class="note">
          <h5>Notes :</h5>
          <div>{!! nl2br(e($po->keterangan ?? '')) !!}</div>
        </div>
      </td>
      <td style="width:45%; vertical-align:top">
        <div class="totals">
          <table class="totals-kv">
            <tr><td class="lbl">Sub Total</td><td class="colon">:</td><td class="val">{{ $rp0($subtotal) }}</td></tr>
            <tr><td class="lbl">Tax</td><td class="colon">:</td><td class="val">{{ $rp0($ppn) }}</td></tr>
            <tr class="grand"><td class="lbl">Total Order</td><td class="colon">:</td><td class="val">{{ $rp0($grand) }}</td></tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <table class="saytbl">
    <tr>
      <td class="lbl" style="white-space:nowrap">Say :</td>
      <td><div class="saybox">{{ $amountWords }}</div></td>
    </tr>
  </table>

  @if(!$hasTerms)
  <table class="sig-table" style="margin-top:12mm">
    <tr>
      <td class="label">Menyetujui,<br>PT. Tri Daya Selaras</td>
      <td class="label t-right"></td>
    </tr>
    <tr>
      <td class="signbox">
        @if(!empty($qrBase64) && (int) $po->disposisi_po === 4)
          <img src="{{ $qrBase64 }}" class="qr">
        @endif
      </td>
      <td class="signbox"></td>
    </tr>
    <tr>
      <td class="name">Vica Krisdianatha<div class="role">Direktur Utama</div></td>
      <td class="name t-right"></td>
    </tr>
  </table>
  @endif
</div>

<!-- ======================== HALAMAN 2 (TERMS) ======================== -->
@if($hasTerms)
<div class="page-break"></div>

<div class="terms-wrap">
  <div class="terms-header">
    <div></div>
    <div class="pro-right">
      <img src="{{ public_path('images/logo-crs.png') }}" style="height:auto; width:100px" alt="Crushed Stone">
      <div class="addr">
        <strong>PT. Tri Daya Selaras</strong><br>
        Graha Irama Building 6 G<br>
        Jl. HR Rasuna Said Blok X-1 Kav. 1-2<br>
        Jakarta, 12950 Indonesia<br>
        Telp : (021) 2152892321
      </div>
    </div>
  </div>

  <h3 class="terms-title">Syarat &amp; Ketentuan Pembelian</h3>

  <div class="terms" style="margin-top:2mm; text-align:justify; line-height:1.55;">
    @php
      $lines = preg_split("/\r\n|\n|\r/", trim($po->terms_condition ?? ''));
    @endphp
    @foreach($lines as $line)
      @if(trim($line) !== '')
        <div style="margin-bottom:3mm; text-align:justify; width:100%; display:block;">
          {{ ltrim($line) }}
        </div>
      @endif
    @endforeach
  </div>

  <table class="sig-table">
    <tr>
      <td class="label">Menyetujui,<br>PT. Tri Daya Selaras</td>
      <td class="label t-right">{{ strtoupper(optional($po->vendor)->nama_vendor ?? '') }}</td>
    </tr>
    <tr>
      <td class="signbox">
        @if(!empty($qrBase64) && (int) $po->disposisi_po === 4)
          <img src="{{ $qrBase64 }}" class="qr">
        @endif
      </td>
      <td class="signbox t-right"></td>
    </tr>
    <tr>
      <td class="name">Vica Krisdianatha<div class="role">Direktur Utama</div></td>
      <td class="name t-right">Direktur</td>
    </tr>
  </table>

  <div class="tiny-note">
    (This form is valid with sign by computerized system)<br>
    Printed by {{ auth()->user()->name ?? 'system' }} &mdash; {{ now()->format('d/m/Y H:i:s') }} WIB
  </div>
</div>
@endif
</body>
</html>
