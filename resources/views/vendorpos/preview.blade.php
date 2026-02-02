<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Purchase Order {{ $po->nomor_po ?? '-' }}</title>
  <style>
    @page { margin: 24mm 26mm 42mm 26mm; }
    *{ box-sizing:border-box; margin:0; padding:0 }
    body{ font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:11px; color:#222; line-height:1.45 }
    .content{ width:92%; margin:0 auto; padding-bottom:44mm }

    .t-right{ text-align:right } .t-center{ text-align:center }
    .hdr{ width:100%; border-collapse:collapse }
    .hdr td{ vertical-align:top }
    .hdr .logo img{ max-height:62px }

    .title{ text-align:center; font-size:16px; font-weight:700; letter-spacing:.5px; margin:8px 0 6px }
    .underline{ width:220px; margin:0 auto 12px; border-bottom:1px solid #333 }

    .meta{ width:100%; border-collapse:collapse; margin:6px 0 14px }
    .meta td{ font-size:11px; vertical-align:top }
    .meta .right{ text-align:right }

    .parties{ width:100%; border-collapse:collapse; margin-bottom:12px }
    .parties td{ vertical-align:top; padding-right:12px }
    .block h4{ font-size:11.2px; font-weight:700; margin:0 0 6px; border-bottom:1px solid #999; padding-bottom:4px }
    .block p{ margin:0 0 2px }
    .mini-box{ border:1px solid #cfcfcf; border-radius:6px; padding:8px 10px; font-size:10.2px; display:inline-block; min-width:180px }
    .mini-kv{ width:100%; border-collapse:collapse }
    .mini-kv td{ padding:3px 4px; vertical-align:top }
    .mini-kv .lbl{ width:92px; color:#555 } .mini-kv .colon{ width:6px }

    .po-table{ width:100%; border-collapse:collapse; margin-top:8px }
    .po-table th, .po-table td{ border:1px solid #333; padding:8px 10px }
    .po-table thead th{ background:#efefef; font-weight:700; text-align:center }

    .row-nt{ width:100%; border-collapse:collapse; margin:14px 0 6px }
    .note{ border:1px solid #333; border-radius:4px; padding:8px 10px; min-height:92px; font-size:10.5px }
    .note h5{ margin:0 0 6px; font-size:11px }

    .totals{ border:1px solid #333; border-radius:4px; padding:8px 10px }
    .totals-kv{ width:100%; border-collapse:collapse }
    .totals-kv td{ padding:6px 4px; vertical-align:top }
    .totals-kv .lbl{ width:65% }
    .totals-kv .colon{ width:12px; text-align:center }
    .totals-kv .val{ text-align:right; white-space:nowrap }
    .totals-kv .grand .lbl,
    .totals-kv .grand .val{ font-weight:800 }

    .saytbl{ width:100%; border-collapse:collapse; margin-top:8px }
    .saytbl td{ padding:6px 8px; vertical-align:middle }
    .saytbl .lbl{ width:40px }
    .saybox{ border:1px solid #333; border-radius:4px; padding:6px 8px; font-size:10.6px }

    .page-footer{ position:fixed; left:0; right:0; bottom:0; height:50mm; font-size:10px; color:#555 }
    .page-footer .inner{ margin:0 26mm; border-top:1px solid #bbb; padding-top:8px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start }
    .page-footer .addr{ max-width:62% }
    .page-footer .brand{ font-weight:700; color:#2C3E50; margin-bottom:1px }
    .page-footer .bottom{ margin-top:5px; color:#888; display:flex; justify-content:space-between }

    /* Halaman 2 */
    .page-break{ page-break-before: always; }
    .terms-wrap{ width:88%; margin:0 auto; }
    .terms-header{ display:flex; justify-content:space-between; align-items:flex-start; margin-top:6mm; }
    .pro-right{ text-align:right }
    .pro-right .brand{ font-size:22px; font-weight:800; color:#2557A7; letter-spacing:.5px }
    .pro-right .addr{ font-size:10px; margin-top:2mm; line-height:1.35 }
    .terms-title{ text-align:center; font-weight:700; margin:10mm 0 6mm; }
    .terms ol{ margin-left: 5mm; }
    .terms li{ margin:0 0 3mm; }
    .sig-row{ display:flex; justify-content:space-between; margin-top:12mm; align-items:flex-end }
    .sig-box{ width:48%; }
    .sig-box .label{ margin-bottom:8mm; font-weight:700 }
    .sig-name{ margin-top:2mm; font-weight:700 }
    .sig-role{ font-size:10px }
    .tiny-note{ margin-top:10mm; text-align:center; font-size:9px; color:#666; font-style:italic }
    .sig-table{ width:100%; border-collapse:collapse; margin-top:12mm }
.sig-table td{ vertical-align:bottom; padding:0 }
.label{ font-weight:700; padding-bottom:8mm }
.signbox{ height:28mm }              /* tinggi ruang tanda tangan sama */
.qr{ width:28mm; height:auto }       /* samakan ukuran QR */
.name{ font-weight:700; padding-top:4mm }
.role{ font-size:10px }
.t-right{ text-align:right }
.terms ol {
  counter-reset: item;
}
.terms li {
  line-height: 1.55;
  text-align: justify;
  margin-bottom: 4mm;
}
.terms-title {
  text-align: center;
  font-weight: 700;
  margin: 8mm 0 5mm;
  font-size: 13px;
  letter-spacing: 0.3px;
}

  </style>
</head>
<body>
@php
  $hasTerms = !empty(trim($po->terms_condition ?? ''));
  $rp0   = fn($n) => 'IDR '.number_format((float)$n, 0, ',', '.');
  $dateS = $po->tanggal_po ?? $po->tanggal_inven ?? $po->tanggal ?? null;
  $date  = $dateS ? \Carbon\Carbon::parse($dateS)->translatedFormat('d F Y') : '-';
  $items = $po->items ?? $po->produks ?? [];

  $subtotal = $po->subtotal ?? (collect($items)->sum(fn($i)=> (float)($i->jumlah_harga ?? $i->amount ?? 0)));
  $dppLain  = $po->dpp_nilai_lain ?? 0;
  $discount = $po->discount_total ?? 0;
  $ppn      = $po->ppn ?? $po->ppn11 ?? 0;
  $pph22    = $po->pph22 ?? 0;
  $pbbkb    = $po->pbbkb ?? 0;
  $grand    = $po->total_order ?? ($subtotal - $discount + $ppn + $pph22 + $pbbkb);

  $amountWords = '-';
  if (class_exists('NumberFormatter')) {
    $fmt = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
    $d   = floor((float)$grand); $c = (int)round(((float)$grand - $d)*100);
    $amountWords = ucfirst($fmt->format($d)).' dollars'.($c>0 ? ' and '.$fmt->format($c).' cents' : '');
  }
@endphp

<!-- ======================== HALAMAN 1 ======================== -->
<div class="content">
  <table class="hdr">
    <tr>
      <td class="logo" style="width:50%">
        @if($logoLeft)
          <img src="{{ $logoLeft }}" alt="Tri Daya Selaras">
        @else
          <img src="{{ public_path('images/tds.png') }}" alt="Tri Daya Selaras">
        @endif
      </td>
      <td class="logo" style="width:70%; text-align:right">
        @if($logoRight)
          <img src="{{ $logoRight }}" style="height:auto; width:100px" alt="Crushed Stone">
        @else
          <img src="{{ public_path('images/crs-1.png') }}" style="height:auto; width:100px" alt="Crushed Stone">
        @endif
      </td>
    </tr>
  </table>

  <div class="title">PURCHASE ORDER</div>
  <div class="underline"></div>

  <table class="meta">
    <tr>
      <td>PO Number : {{ $po->nomor_po ?? '-' }}</td>
      <td class="right">PO Date : {{ $date }}</td>
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
            <tr><td class="lbl">Terms</td><td class="colon">:</td><td>{{ $po->terms ?? 'CBD' }}</td></tr>
            <tr><td class="lbl">Vendor Is Taxable</td><td class="colon">:</td><td>{{ ($po->vendor_taxable ?? true) ? 'YES' : 'NO' }}</td></tr>
            <tr><td class="lbl">Delivery Date</td><td class="colon">:</td><td>{{ $po->delivery_date ?? '' }}</td></tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <table class="po-table">
    <thead>
      <tr>
        <th style="width:12%">Item</th>
        <th>Description</th>
        <th style="width:16%">Quantity (m&sup3;)</th>
        <th style="width:16%">Unit Price (IDR)</th>
        <th style="width:10%">Disc %</th>
        <th style="width:8%">Tax</th>
        <th style="width:18%">Amount (IDR)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $it)
        @php
          $qty   = (float)($it->qty ?? $it->quantity ?? $it->volume_po ?? 0);
          $price = (float)($it->unit_price ?? $it->harga_tebus ?? 0);
          $amt   = (float)($it->amount ?? $it->jumlah_harga ?? ($qty*$price));
          $disc  = isset($it->disc_percent) ? $it->disc_percent : 0;
          $taxcd = $it->tax_code ?? 'E';
        @endphp
        <tr>
          <td class="t-center">{{ $it->item ?? $it->kode ?? 'Crushed Stone' }}</td>
          <td>
            {{ optional($it->produk)->nama_produk ?? '-' }}
            @if(optional($it->produk)->ukuran)
              — {{ optional($it->produk->ukuran)->nama_ukuran }} {{ optional($it->produk->ukuran->satuan)->nama_satuan }}
            @endif
          </td>
          <td class="t-right">{{ number_format($qty, 2, ',', '.') }}</td>
          <td class="t-right">{{ $rp0($price) }}</td>
          <td class="t-center">{{ rtrim(rtrim(number_format($disc,2,'.',''), '0'), '.') }}</td>
          <td class="t-center">{{ $taxcd }}</td>
          <td class="t-right">{{ $rp0($amt) }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="t-center">No items</td></tr>
      @endforelse
    </tbody>
  </table>

  <table class="row-nt">
    <tr>
      <td style="width:60%; padding-right:10px">
        <div class="note">
          <h5>Note</h5>
          <div>{{ $po->note ?? $po->keterangan ?? '—' }}</div>
        </div>
      </td>
      <td style="width:40%">
        <div class="totals">
          <table class="totals-kv">
            <tr><td class="lbl">Sub Total</td><td class="colon">:</td><td class="val">{{ $rp0($subtotal) }}</td></tr>
            <tr><td class="lbl">DPP Nilai Lain</td><td class="colon">:</td><td class="val">{{ $rp0($dppLain) }}</td></tr>
            <tr><td class="lbl">Discount</td><td class="colon">:</td><td class="val">{{ $rp0($discount) }}</td></tr>
            <tr><td class="lbl">PPN</td><td class="colon">:</td><td class="val">{{ $rp0($ppn) }}</td></tr>
            <tr class="grand"><td class="lbl">Total Order</td><td class="colon">:</td><td class="val">{{ $rp0($grand) }}</td></tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  <table class="saytbl">
    <tr>
      <td class="lbl">Say</td>
      <td><div class="saybox">{{ $amountWords }}</div></td>
    </tr>
  </table>

  {{-- TTD JIKA TANPA TERMS --}}
  @if(!$hasTerms)
  <table class="sig-table" style="margin-top:37mm">
    <tr>
      <td class="label">Menyetujui,<br>PT. Tri Daya Selaras</td>
      <td class="label t-right"></td>
    </tr>
    <tr>
      <td class="signbox">
        @if(!empty($qrBase64))
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





{{-- <div class="page-footer">
  <div class="inner">
    <div class="addr">
      <div class="brand">Tri Daya Selaras</div>
      Graha Irama Building Lt. 6 Unit G, Jl. HR Rasuna Said Blok X1 Kav 1–2, Kuningan Timur, Jakarta Selatan 12950
    </div>
    <div class="contact" style="text-align:right">
      <div>Telp. {{ config('company.phone', '+62 21 5289 2321') }}</div>
      <div>Fax {{ config('company.fax', '+62 21 5289 2310') }}</div>
      <div>{{ config('company.email', 'info@tds.com') }}</div>
      <div>{{ config('company.website', 'www.tridayaselaras.com') }}</div>
    </div>
  </div>
</div> --}}

<!-- ======================== HALAMAN 2 (TERMS) ======================== -->
@if($hasTerms)

<div class="page-break"></div>

<div class="terms-wrap">
  <div class="terms-header">
    <div></div>
    <div class="pro-right">
      @if(!empty($proLogo))
        <img src="{{ $proLogo }}" alt="PROENERGI" style="height: 22mm">
      @else
      <img src="{{ public_path('images/crs-1.png') }}" style="height:auto; width:100px" alt="Crushed Stone">
      @endif
      <div class="addr">
        <strong>PT. Tri Daya Selaras</strong><br>
        Graha Irama Building 6 G<br>
        Jl. HR Rasuna Said Blok X-1 Kav. 1-2<br>
        Jakarta, 12950 Indonesia<br>
        Telp : (021) 2152892321<br>
      </div>
    </div>
  </div>

  <h3 class="terms-title">Syarat &amp; Ketentuan Pembelian</h3>

  <div class="terms" style="margin-top: 2mm; text-align: justify; line-height: 1.55;">
    @php
      $terms = $po->terms_condition ?? '';
      $lines = preg_split("/\r\n|\n|\r/", trim($terms));
    @endphp

    @foreach($lines as $line)
      @if(trim($line) !== '')
        <div style="margin-bottom: 3mm; text-align: justify; width:100%; display:block;">
          {{ ltrim($line) }}
        </div>
      @endif
    @endforeach
  </div>

  <table class="sig-table">
    <tr>
      <td class="label">Menyetujui,<br>PT. Tri Daya Selaras</td>
      <td class="label t-right">{{ strtoupper(optional($po->vendor)->nama_vendor) }}</td>
    </tr>
  
    {{-- Baris ruang tanda tangan (tinggi sama di kiri-kanan) --}}
    <tr>
      <td class="signbox">
        @if(!empty($qrBase64))
          <img src="{{ $qrBase64 }}" class="qr">
        @endif
      </td>
      <td class="signbox t-right"></td>
    </tr>
  
    <tr>
      <td class="name">
        Vica Krisdianatha
        <div class="role">Direktur Utama</div>
      </td>
      <td class="name t-right">Direktur</td>
    </tr>
  </table>
  
  </div>

  <div class="tiny-note">
    (This form is valid with sign by computerized system)<br>
    Printed by {{ auth()->user()->name ?? 'system' }} {{ now()->format('d/m/Y H:i:s') }} WIB
  </div>
</div>
@endif
</body>
</html>
