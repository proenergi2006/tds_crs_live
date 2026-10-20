<!DOCTYPE html>
<html lang="{{ $lang }}">
@php
  $isEn = $lang === 'en';
  $customer = $quotation->customer;
  $contactPerson = $quotation->customerContact;

  $t = $isEn ? [
    'title'        => 'Price Quotation',
    'no'           => 'No.',
    'valid'        => 'Price Period',
    'attention'    => 'Attention to',
    'up'           => 'Attn.',
    'colNo'        => 'No',
    'colProduct'   => 'Product & Specification',
    'colQty'       => 'Quantity',
    'size'         => 'Size',
    'colPrice'     => 'Unit Price',
    'colTotal'     => 'Total',
    'dpp'          => 'Total DPP',
    'vat'          => 'VAT',
    'grand'        => 'Grand Total',
    'words'        => 'In words',
    'terms'        => 'Terms of Offer',
    'payment'      => 'Payment Method',
    'ordering'     => 'Ordering Method',
    'delivery'     => 'Delivery Method',
    'handover'     => 'Handover Point',
    'tolerance'    => 'Shrinkage Tolerance',
    'toleranceDefault' => '1% of total delivered quantity',
    'issuedBy'     => 'Issued by',
    'contact'      => 'Contact person',
    'appendix'     => 'APPENDIX',
    'termsTitle'   => 'Terms & Conditions',
    'greeting'     => 'Dear Sir/Madam,',
    'closing'      => 'We hereby submit this quotation. Thank you for your attention and cooperation.',
    'phone'        => 'Tel.',
    'abrasion'     => 'Abrasion',
  ] : [
    'title'        => 'Penawaran Harga',
    'no'           => 'No.',
    'valid'        => 'Periode Harga',
    'attention'    => 'Kepada Yth.',
    'up'           => 'u.p.',
    'colNo'        => 'No',
    'colProduct'   => 'Produk & Spesifikasi',
    'colQty'       => 'Quantity',
    'size'         => 'Ukuran',
    'colPrice'     => 'Harga Satuan',
    'colTotal'     => 'Total',
    'dpp'          => 'Total DPP',
    'vat'          => 'PPN',
    'grand'        => 'Grand Total',
    'words'        => 'Terbilang',
    'terms'        => 'Ketentuan Penawaran',
    'payment'      => 'Metode Pembayaran',
    'ordering'     => 'Metode Pemesanan',
    'delivery'     => 'Metode Pengiriman',
    'handover'     => 'Titik Serah Terima',
    'tolerance'    => 'Toleransi Penyusutan',
    'toleranceDefault' => '1% dari total jumlah pengiriman',
    'issuedBy'     => 'Diterbitkan oleh',
    'contact'      => 'Narahubung',
    'appendix'     => 'LAMPIRAN',
    'termsTitle'   => 'Syarat & Ketentuan',
    'greeting'     => 'Dengan hormat,',
    'closing'      => 'Demikian penawaran harga ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.',
    'phone'        => 'Telp.',
    'abrasion'     => 'Abrasi',
  ];

  $subject = $isEn ? 'Price Quotation' : 'Penawaran Harga';

  $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $qty = fn ($n) => number_format((float) $n, 2, ',', '.');

  $items = $quotation->items->filter(fn ($item) => $item->product);

  $paymentText = null;
  if (filled($quotation->tipe_pembayaran)) {
    $paymentText = $quotation->tipe_pembayaran === 'CUSTOM'
      ? 'DP ' . number_format((float) $quotation->dp_persen, 0)
        . '% Repayment ' . number_format((float) $quotation->repayment_persen, 0)
        . '% after ' . number_format((float) $quotation->repayment_hari, 0) . ' days'
      : $quotation->tipe_pembayaran;
    if (filled($quotation->acuan_pembayaran)) {
      $paymentText .= ' - ' . $quotation->acuan_pembayaran;
    }
  }

  $deliveryText = filled($quotation->metode) ? $quotation->metode : null;

  $termRows = [];
  if (! $isPolimer && filled($quotation->abrasi)) {
    $abrasion = trim($quotation->abrasi);
    $needsPercent = ! str_contains($abrasion, '%') && preg_match('/^[0-9\s.,\-–<>=~]+$/u', $abrasion) === 1;
    $termRows[] = [$t['abrasion'], $needsPercent ? $abrasion . ' %' : $abrasion];
  }
  if ($paymentText !== null) {
    $termRows[] = [$t['payment'], $paymentText];
  }
  if (filled($quotation->order_method)) {
    $termRows[] = [$t['ordering'], $quotation->order_method];
  }
  if ($deliveryText !== null) {
    $termRows[] = [$t['delivery'], $deliveryText];
  }
  if (filled(trim((string) $quotation->keterangan))) {
    $termRows[] = [$t['handover'], new \Illuminate\Support\HtmlString(nl2br(e($quotation->keterangan)))];
  }
  $decimalSeparator = $isEn ? '.' : ',';
  $toleranceValue = (float) $quotation->toleransi_penyusutan > 0
    ? rtrim(rtrim(number_format((float) $quotation->toleransi_penyusutan, 2, $decimalSeparator, ''), '0'), $decimalSeparator) . ' %'
    : $t['toleranceDefault'];
  $termRows[] = [$t['tolerance'], $toleranceValue];

  $showLogoRight = $logoRight && ! $isPolimer;
@endphp
<head>
  <meta charset="UTF-8">
  <title>{{ $t['title'] }} {{ $quotation->nomor_penawaran }}</title>
  <style>
    @page{
      margin: 10mm 0 28mm 0;
    }

    *{ box-sizing:border-box; padding:0 }
    body, div, p, table, hr{ margin:0 }
    body{ font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:10px; color:#222; line-height:1.45 }

    .content{ width:82%; margin:0 auto }

    .header{ width:100%; border-bottom:2px solid #0f172a; padding-bottom:6px; margin-bottom:8px }
    .header-table{ width:100%; border-collapse:collapse }
    .header-table td{ vertical-align:middle }
    .logo-pair{ width:auto; border-collapse:collapse }
    .logo-cell{ padding:0 4px 0 0; vertical-align:middle }
    .logo-cell img{ height:44px; width:auto }
    .form-title{ text-align:right; font-size:11pt; font-weight:bold; color:#022c22 }


    .refrow{ width:100%; border-collapse:collapse; margin-bottom:8px }
    .refrow td{ vertical-align:top; font-size:10px }
    .refright{ text-align:right; color:#555 }
    .validrow{ font-size:10px; color:#333; font-weight:700; margin:6px 0 4px }
    .attn-table{ width:100%; border-collapse:collapse; margin-bottom:8px }
    .attn-table td{ vertical-align:top; padding-right:10px }

    .attn{ font-size:10px }
    .attn strong{ font-weight:700 }

    .p{ margin:4px 0 8px; text-align:justify }

    .items{ width:100%; border-collapse:collapse; margin:6px 0 4px }
    .items th{ background:#eceff1; border:1px solid #cfd8dc; padding:4px 5px; font-size:9px; text-align:left }
    .items td{ border:1px solid #cfd8dc; padding:4px 5px; vertical-align:top; font-size:10px }
    .items .num{ text-align:right; white-space:nowrap }
    .items .center{ text-align:center }
    .items .spec{ font-size:9px; color:#555 }

    .summary{ width:100%; border-collapse:collapse; margin:8px 0 6px }
    .summary td{ padding:4px 5px; font-size:10px }
    .summary .lbl{ width:16%; text-align:left; border:1px solid #cfd8dc }
    .summary .val{ width:18%; text-align:right; white-space:nowrap; border:1px solid #cfd8dc }
    .summary .grand td.lbl, .summary .grand td.val{ font-weight:700; background:#eceff1 }

    .summary td.words{ width:66%; vertical-align:bottom; padding:0 14px 4px 0; line-height:1.45 }
    .words-box{ border-left:3px solid #022c22; padding:2px 0 2px 8px }

    .terms-head{ font-weight:700; font-size:10px; margin:8px 0 3px }
    .kv{ width:100%; border-collapse:collapse; border-top:1px solid #cfd8dc; border-bottom:1px solid #cfd8dc; margin-bottom:8px }
    .kv td{ padding:3px 3px; vertical-align:top; font-size:10px }
    .kv .no{ width:18px; font-weight:700 }
    .kv .label{ width:115px; color:#555 }
    .kv .colon{ width:8px }

    .sigrow{ width:100%; border-collapse:collapse; margin-top:16px }
    .sigrow td{ vertical-align:top; padding-right:10px }
    .sigrow td:last-child{ padding-right:0 }
    .sig-name{ font-weight:700; text-decoration:underline }
    .qr{ width:17mm; height:17mm; margin-top:1mm }
    .signspace{ height:16mm }

    .contact{ font-size:10px }
    .contact .label{ display:block }

    .watermark{
      position: fixed;
      top: 70mm;
      left: 10mm;
      width: 100%;
      text-align: center;
      font-size: 110px;
      font-weight: 700;
      color: #eceff1;
      transform: rotate(-35deg);
      z-index: -1;
    }

    .footer{
      position: fixed;
      left: 19mm;
      right: 19mm;
      bottom: -22mm;
      text-align: center;
      font-size: 9px;
    }

    .page-break{ page-break-before: always }

    .terms-wrap{ width:82%; margin:0 auto }
    .terms-heading{ text-align:center; font-weight:700; font-size:10px; margin:3mm 0 5mm }
    .terms{ margin-top:2mm; text-align:justify; line-height:1.6; font-size:10px }
    .terms-line{ margin-bottom:3mm; text-align:justify; width:100%; display:block }
  </style>
</head>
<body>
<div class="footer">
  <hr style="border:none; border-top:1px solid #000;">
  <div><strong>{{ $brand->companyName() }}</strong> · {{ $brand->footerAddress() }}</div>
  <div>{{ $t['phone'] }} {{ $brand->phone() }} · Fax {{ $brand->fax() }} · <a href="https://{{ $brand->website() }}" target="_blank" rel="noopener noreferrer">{{ $brand->website() }}</a></div>
</div>
@unless($isApproved)
  <div class="watermark">DRAFT</div>
@endunless
<div class="content">

  <div class="header">
    <table class="header-table">
      <tr>
        <td>
          <table class="logo-pair">
            <tr>
              <td class="logo-cell">
                @if($logoLeft)
                  <img src="{{ $logoLeft }}" alt="Logo">
                @endif
              </td>
              <td class="logo-cell">
                @if($showLogoRight)
                  <img src="{{ $logoRight }}" alt="Logo">
                @endif
              </td>
            </tr>
          </table>
        </td>
        <td style="text-align:right">
          <div class="form-title">{{ mb_strtoupper($subject) }}</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="refrow">
    <tr>
      <td style="width:60%">{{ $t['no'] }} {{ $quotation->nomor_penawaran }}</td>
      <td class="refright" style="width:40%">{{ $city ? $city . ', ' : '' }}{{ $issuedDate }}</td>
    </tr>
  </table>

  <table class="attn-table">
    <tr>
      <td class="attn" style="width:60%">
        <strong>{{ $t['attention'] }}</strong><br>
        <strong>{{ $customer->company_name }}</strong>
        @if(filled($contactPerson?->full_name))
          <br>{{ $t['up'] }} {{ $contactPerson->full_name }}{{ filled($contactPerson->position) ? ' – ' . $contactPerson->position : '' }}
        @endif
        @if(filled($customer->headOfficeAddress?->address_line))
          <br>{{ $customer->headOfficeAddress->address_line }}
        @endif
      </td>
      <td style="width:40%"></td>
    </tr>
  </table>
  <p class="p">{{ $t['greeting'] }}</p>
  @if($isEn)
    <p class="p">
      Together with this letter, please allow us to introduce ourselves as {{ $brand->companyName() }}, a legal entity holding
      a Sales Transportation Mining Business License from ESDM, engaged in the mining sector.
    </p>
    <p class="p">
      With our experience, product quality assurance, resources, and facilities, we believe that we are able to fulfill
      the needs of <strong>{{ $customer->company_name }}.</strong>
      Therefore, we are pleased to submit our offer as follows:
    </p>
  @else
    <p class="p">
      Bersama surat ini, perkenankan kami memperkenalkan bahwa kami dari {{ $brand->companyName() }} sebagai Badan Hukum yang memiliki
      Izin Usaha Penjualan &amp; Jasa Pertambangan dari ESDM, bergerak di bidang pertambangan.
    </p>
    <p class="p">
      Dengan pengalaman, jaminan kualitas produk, sumber daya, dan fasilitas yang kami miliki, kami percaya dapat memenuhi
      kebutuhan perusahaan <strong>{{ $customer->company_name }}.</strong>
      Sehubungan dengan itu, berikut kami sampaikan penawaran:
    </p>
  @endif

  @if($validFrom && $validUntil)
    <div class="validrow">{{ $t['valid'] }}: {{ $validFrom }} – {{ $validUntil }}</div>
  @endif

  <table class="items">
    <tr>
      <th class="center" style="width:6%">{{ $t['colNo'] }}</th>
      <th>{{ $t['colProduct'] }}</th>
      <th class="num" style="width:13%">{{ $t['colQty'] }}</th>
      <th class="num" style="width:16%">{{ $t['colPrice'] }}</th>
      <th class="num" style="width:18%">{{ $t['colTotal'] }}</th>
    </tr>
    @foreach($items->values() as $index => $item)
      @php
        $product = $item->product;
        $spec = filled($product->ukuran?->nama_ukuran)
          ? trim($product->ukuran->nama_ukuran . ' ' . ($product->ukuran->satuan?->nama_satuan ?? ''))
          : '';
      @endphp
      <tr>
        <td class="center">{{ $index + 1 }}</td>
        <td>
          <strong>{{ $product->nama_produk }}</strong>
          @if($spec !== '')
            <div class="spec">{{ $t['size'] }}: {{ $spec }}</div>
          @endif
        </td>
        <td class="num">{{ $qty($item->quantity) }}</td>
        <td class="num">{{ $rupiah($item->finalPrice()) }}</td>
        <td class="num">{{ $rupiah($item->finalTotal()) }}</td>
      </tr>
    @endforeach
  </table>

  <table class="summary">
    <tr>
      <td class="words" rowspan="3">
        @if($amountInWords !== '')
          <div class="words-box"><strong>{{ $t['words'] }}:</strong><br><em>{{ $amountInWords }}</em></div>
        @endif
      </td>
      <td class="lbl">{{ $t['dpp'] }}</td>
      <td class="val">{{ $rupiah($quotation->dpp()) }}</td>
    </tr>
    <tr>
      <td class="lbl">{{ $t['vat'] }} {{ $quotation->ppnPercentLabel() }}</td>
      <td class="val">{{ $rupiah($quotation->ppnAmount()) }}</td>
    </tr>
    <tr class="grand">
      <td class="lbl">{{ $t['grand'] }}</td>
      <td class="val">{{ $rupiah($quotation->grandTotal()) }}</td>
    </tr>
  </table>

  @if($termRows !== [])
    <div class="terms-head">{{ $t['terms'] }}</div>
    <table class="kv">
      @foreach($termRows as [$label, $value])
        <tr>
          <td class="no">{{ $loop->iteration }}.</td>
          <td class="label">{{ $label }}</td>
          <td class="colon">:</td>
          <td>{{ $value }}</td>
        </tr>
      @endforeach
    </table>
  @endif

  <p class="p">{{ $t['closing'] }}</p>

  <table class="sigrow">
    <tr>
      <td>
        {{ $t['issuedBy'] }},<br>
        <strong>{{ $brand->companyName() }}</strong>
        @if($qrBase64)
          <div>
            <img src="{{ $qrBase64 }}" class="qr" alt="QR">
          </div>
        @else
          <div class="signspace"></div>
        @endif
        @if($signatory)
          <span class="sig-name">{{ $signatory['name'] }}</span><br>
          {{ $signatory['title'] }}
        @endif
      </td>
      <td style="width:1%; white-space:nowrap">
        @if($contact !== null && array_filter($contact))
          <div class="contact">
            <span class="label">{{ $t['contact'] }},</span>
            @if(filled($contact['name']))
              <strong>{{ $contact['name'] }}</strong><br>
            @endif
            @if(filled($contact['role']))
              {{ $contact['role'] }}<br>
            @endif
            @if(filled($contact['phone']))
              {{ $contact['phone'] }}<br>
            @endif
            @if(filled($contact['email']))
              <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
            @endif
          </div>
        @endif
      </td>
    </tr>
  </table>
</div>


@if($termsLines !== [])
  <div class="page-break"></div>

  <div class="terms-wrap">
    <div class="header">
      <table class="header-table">
        <tr>
          <td>
            <table class="logo-pair">
              <tr>
                <td class="logo-cell">
                  @if($logoLeft)
                    <img src="{{ $logoLeft }}" alt="Logo">
                  @endif
                </td>
                <td class="logo-cell">
                  @if($showLogoRight)
                    <img src="{{ $logoRight }}" alt="Logo">
                  @endif
                </td>
              </tr>
            </table>
          </td>
          <td style="text-align:right">
            <div class="form-title">{{ $t['appendix'] }}</div>
          </td>
        </tr>
      </table>
    </div>

    <div class="terms-heading">{{ $t['termsTitle'] }}</div>
    <div class="terms">
      @foreach($termsLines as $line)
        <div class="terms-line">{{ $line }}</div>
      @endforeach
    </div>
  </div>
@endif

</body>
</html>
