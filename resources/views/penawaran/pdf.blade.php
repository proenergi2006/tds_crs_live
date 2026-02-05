{{-- resources/views/vendor_pos/penawaran_pdf_letter.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Penawaran {{ $penawaran->nomor_penawaran }}</title>
  <style>
    /* Kertas A4 potrait + ruang bawah besar untuk pita hijau */
    @page{
      size: A4 portrait;
      margin-top: 44mm;
      margin-bottom: 44mm;   /* ruang untuk footer + pita */
      margin-left: 42mm;
      margin-right: 42mm;
    }

    *{ box-sizing:border-box; margin:0; padding:0 }
    body{ font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size:10.1px; color:#222; line-height:1.45 }

    /* Wrapper konten */
    .content{ width:82%; margin:0 auto }

    /* Header */
    .hdr{ width:100%; border-collapse:collapse; margin-bottom:6px }
    .hdr td{ vertical-align:top }
    .logo img{ height:14mm; width:auto }   /* atur ukuran logo */
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

    /* ====== Bagian 1–9 TANPA KOTAK ====== */
    .box{ width:92%; margin:8px auto 10px; padding:0 } /* border dihilangkan */
    .kv{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  border-top: 1px solid #cfd8dc;     /* garis di atas "1. Product" */
  border-bottom: 1px solid #cfd8dc;  /* garis di bawah "9. Tolerance" */
  margin-top: 6px;
  margin-bottom: 6px;
}

/* Biar jarak atas/bawah enak dilihat */
.kv tr:first-child td  { padding-top: 8px; }
.kv tr:last-child  td  { padding-bottom: 8px; }

/* Pemisah antar baris tetap */
.kv tr + tr td{ border-top: 0.5px dashed #e6e6e6; }*/
    .no{ width:20px; font-weight:700 }
    .label{ width:165px; color:#555; font-size:9.8px }
    .colon{ width:8px }
    .value{ width:auto; line-height:1.32; font-size:9.8px }
    .value b{ font-weight:700 }

    /* Signature + contact */
    .sigrow{ width:100%; border-collapse:collapse; margin-top:8px }
    .sigrow td{ vertical-align:top; padding-right:10px }
    .sigrow td:last-child{ padding-right:0 }
    .sp-sign{ height:70px;}
    /* Kartu kontak dengan gradasi hijau */
.contact{
  border: 1px solid #b7d7b7;       /* hijau muda */
  border-radius: 10px;
  padding: 12px 14px;
  background: #e9f7ef;             /* fallback */
  background-image: linear-gradient(
    180deg,                         /* arah vertikal */
    #e9f7ef 0%,
    #d7f0da 40%,
    #c8e6c9 100%
  );
  font-size: 11.2px;
  color: #1b5e20;                   /* teks hijau tua agar kontras */
  background-clip: padding-box;     /* jaga sudut rounded rapi */
}
.contact b{
  display:block;
  margin-bottom:6px;
  color:#1b5e20;
}

/* Watermark CSS-only */
.wm {
  position: fixed;                 /* muncul di semua halaman */
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-30deg);
  width: 100%;
  text-align: center;

  font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
  font-weight: 700;
  font-size: 120pt;                /* sesuaikan besar watermark */
  color: rgba(0, 0, 0, 0.08);      /* transparan */
  letter-spacing: 6px;

  z-index: 0;                      /* di belakang konten */
  pointer-events: none;            /* jaga tidak mengganggu */
}

/* pastikan konten di atas watermark */
.content { position: relative; z-index: 1; }


    /* ====== Footer teks di atas pita hijau ====== */
    /* Sisakan ruang bawah untuk footer */


/* Pita hijau full width di paling bawah */
/* pita hijau full width, nempel kanan–kiri & bawah */
.brand-band{
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 18mm;
  background: #57955a;          /* boleh nanti diganti gradient */
  border: none;
}

/* teks footer di atas pita, rapih sejajar dengan margin konten */
.footer{
  position: fixed;              /* <-- perbaiki: 1 titik saja */
  left: 42mm;                   /* sejajar margin kiri konten */
  right: 42mm;                  /* sejajar margin kanan konten */
  bottom: 6mm;                  /* jarak dari tepi bawah kertas */
  text-align: center;
  font-size: 10.8px;
  color: #fff;
  padding: 0;
  border: none;
}


  .content { position:relative; z-index:3; margin-bottom:28mm; }
  .brand-band{ position:fixed; left:0; right:0; bottom:0; height:18mm; background:#57955a; z-index:1; }
  .footer    { position:fixed; left:42mm; right:42mm; bottom:6mm; text-align:center; color:#fff; z-index:2; }
  .wm        { z-index:0; }

  .qrwrap{ text-align:left; margin-left:4mm; margin-top:6mm; }
.qrimg{ width:18mm; height:18mm; display:inline-block; }
.qrimg img{ width:18mm; height:18mm; display:block; }
.qrimg svg{ width:18mm; height:18mm; display:block; }




  </style>
</head>
<body>
  <div class="wm">DRAFT</div>
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
  ->values();   // penting: reset index

  $firstItem   = $penawaran->items->first();
  $hargaSatuan = $firstItem?->harga_dasar ?? 0;

  $rupiah = fn($n) => 'Rp '.number_format((float)$n, 0, ',', '.');

  $due = $penawaran->periode_sampai_dengan
      ? \Carbon\Carbon::parse($penawaran->periode_sampai_dengan)->translatedFormat('d F Y')
      : '—';

  $defaultProduct   = 'Crushed Stone 2-3 (50%), 3-5 (50%) &mdash; Blending (Aggregate)';
  $defaultPayment   = '50% After Barge Reached Jetty MBL; 50% After Unloading';
  $defaultOrder     = 'PO no later than 2 days before delivery';
  $defaultShipping  = 'Free on Board (FOB) + Vessel/Barge Arrangement by Pro Energi';
  $defaultQC        = 'Loading Port (Jetty Pro Energi) &mdash; by Surveyor and Representatives both sides';
  $defaultTolerance = '1% of the total number of shipments';
@endphp

<div class="content">
  <!-- Header -->

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

  <!-- Attention -->
  <table class="attnwrap">
    <tr>
      <td style="width:100%">
        <div class="attn">
          <strong>Attention to :</strong><br>
          PT {{ $cust->nama_perusahaan ?? '-' }}<br>
          {{ $cust->alamat_perusahaan ?? 'Alamat belum diisi' }}<br><br>

          <strong>UP. <u>{{ $penawaran->nama ?? '-' }}</u></strong><br>
          {{ $penawaran->jabatan ?? '-' }}
        </div>
      </td>
    </tr>
  </table>

  <!-- Subject -->
  <div class="subject">Price Quotation of Crushed Stone</div>

  <!-- Intro -->
  <p class="p">Dear Sir,</p>
  <p class="p">
    Together with this letter, please allow us introduce that we are from PT Tri Daya Selaras as a Legal Entity and have a Sales
    Transportation Mining Business License from ESDM, which is engaged in Mining.
  </p>
  <p class="p">
    With our experience, product assurance and resource, and facilities, we believe we are able to fulfill the needs of
    Crushed Stone for <strong>{{ $cust->nama_perusahaan ?? '—' }}.</strong> Therefore, we would like to offer to your company:
  </p>
 

  <!-- 1–9: tanpa kotak -->
  <div class="box">
    <table class="kv">
      {{-- <tr>
        <td class="no">1.</td><td class="label"><b>Product</b></td><td class="colon">:</td>
        <td class="value"><b>{!! $produkList ?: $defaultProduct !!}</b></td>
      </tr> --}}
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
            {{-- baris lanjutan: kosongkan kiri supaya sejajar --}}
            <td class="no"></td>
            <td class="label"></td>
            <td class="colon"></td>
          @endif
    
          <td class="value"><b>{{ $line }}</b></td>
        </tr>
      @endforeach
    @endif
    
      <tr>
        <td class="no">2.</td><td class="label"><b>Abrasion</b></td><td class="colon">:</td>
        <td class="value"><b>{{ $penawaran->abrasi ?? '0' }} </b></td>
      </tr>
      <tr>
        <td class="no">3.</td><td class="label"><b>Price per m&sup3;</b></td><td class="colon">:</td>
        <td class="value"> {{ $rupiah(($penawaran->harga_dasar ?? 0) + ($penawaran->oat ?? 0)) }} <span style="color:#666">(Harga belum termasuk PPN 11%)</span></td>
      </tr>
      <tr>
        <td class="no">4.</td><td class="label"><b>Payment Method</b></td><td class="colon">:</td>
        <td class="value"><b>{{ $penawaran->tipe_pembayaran  }}</b></td>
      </tr>
      <tr>
        <td class="no">5.</td><td class="label"><b>Ordering Method</b></td><td class="colon">:</td>
        <td class="value">{{ $penawaran->order_method  }}</td>
      </tr>
      <tr>
        <td class="no">6.</td><td class="label"><b>Delivery Method</b></td><td class="colon">:</td>
        <td class="value">
          {{ $penawaran->metode }}
        </td>
      </tr>
      <tr>
        <td class="no">7.</td><td class="label"><b>Receiving Point & QC</b></td><td class="colon">:</td>
        <td class="value">{!! $penawaran->keterangan !!}</td>
      </tr>
      <tr>
        <td class="no">8.</td><td class="label"><b>Tolerance</b></td><td class="colon">:</td>
        <td class="value">{{ $penawaran->toleransi_penyusutan  }} %</td>
      </tr>
      <tr>
        <td class="no">9.</td><td class="label"><b>Periode</b></td><td class="colon">:</td>
        <td class="value">
          {{ \Carbon\Carbon::parse($penawaran->masa_berlaku)->translatedFormat('d F Y') }}
          -
          {{ \Carbon\Carbon::parse($penawaran->sampai_dengan)->translatedFormat('d F Y') }}
        </td>
      </tr>
    </table>
  </div>


  <!-- Closing -->
  <p class="p">
    Hopefully we can get the opportunity and trust from you to do the good business relationship with your company.
    Thank you for your attention and cooperation
  </p>

  <!-- Signature + Contact -->
  <table class="sigrow">
    <tr>
      <td style="width:55%;">
        Best Regards,<br>
        <strong>PT. Tri Daya Selaras</strong>
      
        <div class="qrwrap">
          @if(!empty($qrBase64))
            <img src="{{ $qrBase64 }}" style="width:20mm;height:20mm" alt="QR">
          @endif
        </div>
      
      
        <strong>Vica Krisdianatha</strong><br>
        Chief Executive Officer
      </td>
      
      <td style="width:45%;">
        <div class="contact">
          <b>Contact person :</b>
          <strong>{{ $contact['name'] }}</strong><br>
          {{ $contact['role'] }}<br>
          {{ $contact['phone'] }}<br>
          <a
          href="mailto:{{ e($contact['email']) }}"
          class="text-blue-600 underline hover:text-blue-800"
        >
          {{ $contact['email'] }}
        </a>
        
        </div>
        
        
      </td>
    </tr>
  </table>
  {{-- Di bawah tanda tangan / di mana pun kamu mau --}}


  

  <!-- Footer teks (di atas pita hijau) -->
 
</div>
<div class="brand-band"></div>
<div class="footer">
  PT. Tri Daya Selaras • Graha Irama Building 6th floor unit G, Jln. HR Rasuna Said Blok X1 Kav 1-2 •
  Telp. +021 5289 2321 • Fax +021 5289 2310 • <a
  href="https://www.tridayaselaras.com"
  target="_blank"
  rel="noopener noreferrer"
  class="text-blue-600 underline hover:text-blue-800"
>
  www.tridayaselaras.com
</a>
</div>

</body>
</html>
