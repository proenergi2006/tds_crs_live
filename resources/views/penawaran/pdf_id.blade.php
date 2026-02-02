{{-- resources/views/vendor_pos/penawaran_pdf_letter.blade.php --}}
<!DOCTYPE html>
<html lang="id">
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
    .logo img{ height:27mm; width:auto }   /* atur ukuran logo */
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
    .box{ width:70%; margin:8px auto 10px; padding:0 } /* border dihilangkan */
    .kv{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      border-top: 1px solid #cfd8dc;     /* garis di atas "1. Produk" */
      border-bottom: 1px solid #cfd8dc;  /* garis di bawah "9. Toleransi" */
      margin-top: 6px;
      margin-bottom: 6px;
    }

    /* Biar jarak atas/bawah enak dilihat */
    .kv tr:first-child td  { padding-top: 8px; }
    .kv tr:last-child  td  { padding-bottom: 8px; }

    /* Pemisah antar baris tetap */
    .kv tr + tr td{ border-top: 0.5px dashed #e6e6e6; }
    .no{ width:20px; font-weight:700 }
    .label{ width:165px; color:#555; font-size:9.8px }
    .colon{ width:8px }
    .value{ width:auto; line-height:1.32; font-size:9.8px }
    .value b{ font-weight:700 }

    /* Signature + contact */
    .sigrow{ width:100%; border-collapse:collapse; margin-top:8px }
    .sigrow td{ vertical-align:top; padding-right:10px }
    .sigrow td:last-child{ padding-right:0 }
    .sp-sign{ height:52px }

    /* Kartu kontak dengan gradasi hijau */
    .contact{
  border: 1px solid;       /* hijau muda */
  border-radius: 10px;
  padding: 12px 14px;
 
  
  font-size: 11.2px;
                /* teks hijau tua agar kontras */
  background-clip: padding-box;     /* jaga sudut rounded rapi */
}
.contact b{
  display:block;
  margin-bottom:6px;
  
}

    /* Pita hijau full width, nempel kanan–kiri & bawah */
    .brand-band{
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      height: 18mm;
    
      border: none;
    }

    /* teks footer di atas pita, rapih sejajar dengan margin konten */
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


  </style>
</head>
<body>
@php
  $nowID = \Carbon\Carbon::now()->translatedFormat('d F Y');
  $cust  = optional($penawaran->customer);

  $produkList = $penawaran->items
      ->map(function($it){
          $p  = $it->produk;
          if (!$p) return null;
          $uk = optional($p->ukuran);
          $st = optional($uk->satuan);
          // gabung ukuran + satuan bila ada
          $ukTxt = trim(implode(' ', array_filter([
              $uk->nama_ukuran ?? null,
              $st->nama_satuan ?? null,
          ])));

          // persen (fallback 0)
        $persen = $it->persen !== null
            ? rtrim(rtrim(number_format($it->persen, 2, '.', ''), '0'), '.') // buang .00
            : '0';
          // "Nama Produk — 2-3 m³" atau hanya "Nama Produk" jika ukuran kosong
          return trim(
            $p->nama_produk
            . ($ukTxt ? ' — ' . $ukTxt : '')
            . ' (' . $persen . '%)'
        );
      })
      ->filter()
      ->unique()
      ->implode(', ');

  $firstItem   = $penawaran->items->first();
  $hargaSatuan = $firstItem?->harga_tebus ?? 0;

  $rupiah = fn($n) => 'Rp '.number_format((float)$n, 0, ',', '.');

  $due = $penawaran->periode_sampai_dengan
      ? \Carbon\Carbon::parse($penawaran->periode_sampai_dengan)->translatedFormat('d F Y')
      : '—';

  // Default teks versi Indonesia
  $defaultProduct   = 'Batu Pecah 2-3 (50%), 3-5 (50%) — Blending (Agregat)';
  $defaultPayment   = '50% setelah tongkang sampai di Jetty MBL; 50% setelah pembongkaran';
  $defaultOrder     = 'PO maksimal 2 hari sebelum pengiriman';
  $defaultShipping  = 'Free On Board (FOB) + Pengaturan Kapal/Tongkang oleh Pro Energi';
  $defaultQC        = 'Pelabuhan Muat (Jetty TDS) — oleh Surveyor dan Perwakilan kedua belah pihak';
  $defaultTolerance = '1% dari total jumlah pengiriman';
@endphp

<div class="content">
  <!-- Header -->
  <br>
  <table class="hdr">
    <tr>
      <td class="logo" style="width:55%">
        <img src="{{ public_path('images/tds.png') }}" alt="Logo">
      </td>
      <td class="right" style="width:45%">
        Jakarta, {{ $nowID }}
      </td>
    </tr>
  </table>

  <table class="refrow">
    <tr>
      <td class="refleft" style="width:60%">
        No. Ref {{ $penawaran->nomor_penawaran }}
      </td>
      <td class="refright" style="width:40%">
        Telp. {{ $penawaran->telepon ?? '-' }}
      </td>
    </tr>
  </table>

  <!-- Attention -->
  <table class="attnwrap">
    <tr>
      <td style="width:100%">
        <div class="attn">
          <strong>Kepada Yth :</strong><br>
          PT {{ $cust->nama_perusahaan ?? '-' }}<br>
          {{ $cust->alamat_perusahaan ?? 'Alamat belum diisi' }}<br><br>

          <strong>UP. <u>{{ $penawaran->nama ?? '-' }}</u></strong><br>
          {{ $penawaran->jabatan ?? '-' }}
        </div>
      </td>
    </tr>
  </table>

  <!-- Subject -->
  <div class="subject">Penawaran Harga Batu Pecah</div>

  <!-- Intro -->
  <p class="p">Yth. Bapak/Ibu,</p>
  <p class="p">
    Bersama surat ini, perkenankan kami memperkenalkan bahwa kami dari PT Tri Daya Selaras (Perusahaan afiliasi dari PT Pro Energi ) sebagai Badan Hukum yang memiliki
    Izin Usaha Penjualan & Jasa Pertambangan dari ESDM, bergerak di bidang pertambangan.
  </p>
  <p class="p">
    Dengan pengalaman, jaminan kualitas produk, sumber daya, dan fasilitas yang kami miliki, kami percaya dapat memenuhi
    kebutuhan Batu Pecah untuk <strong>{{ $cust->nama_perusahaan ?? '—' }}.</strong> Sehubungan dengan itu, berikut kami sampaikan penawaran:
  </p>
  <br>

  <!-- 1–9: tanpa kotak -->
  <div class="box">
    <table class="kv">
      <tr>
        <td class="no">1.</td><td class="label"><b>Produk</b></td><td class="colon">:</td>
        <td class="value"><b>{!! $produkList ?: $defaultProduct !!}</b></td>
      </tr>
      <tr>
        <td class="no">2.</td><td class="label"><b>Abrasi</b></td><td class="colon">:</td>
        <td class="value"><b>{{ $penawaran->abrasi ?? '0' }} </b></td>
      </tr>
      <tr>
        <td class="no">3.</td><td class="label"><b>Harga per m&sup3;</b></td><td class="colon">:</td>
        <td class="value">{{ $rupiah($penawaran->harga_dasar) }} <span style="color:#666">(Harga belum termasuk PPN 11%)</span></td>
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
          @else
            <div style="margin-top:4px">
              <b>{{ $penawaran->tipe_pembayaran }}</b>
            </div>
          @endif
      
        </td>
      </tr>
      
      
      <tr>
        <td class="no">5.</td><td class="label"><b>Metode Pemesanan</b></td><td class="colon">:</td>
        <td class="value">{{ $penawaran->order_method}}</td>
      </tr>
      <tr>
        <td class="no">6.</td><td class="label"><b>Metode Pengiriman</b></td><td class="colon">:</td>
        <td class="value">
          {{ $penawaran->metode}}
        </td>
      </tr>
      <tr>
        <td class="no">7.</td><td class="label"><b>Titik Serah & QC</b></td><td class="colon">:</td>
        <td class="value">{!! $penawaran->keterangan !!}</td>
      </tr>
     
      <tr>
        <td class="no">8.</td><td class="label"><b>Toleransi</b></td><td class="colon">:</td>
        <td class="value">{{ $penawaran->toleransi_penyusutan ?? $defaultTolerance }} %</td>
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
  <br>

  <!-- Closing -->
  <p class="p">
    Besar harapan kami untuk dapat memperoleh kesempatan dan kepercayaan dari Bapak/Ibu guna menjalin kerja sama yang baik
    dengan perusahaan Bapak/Ibu. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
  </p>
  <br>

  <!-- Signature + Contact -->
  <table class="sigrow">
    <tr>
      <td style="width:55%;">
        Best Regards,<br>
        <strong>PT. Tri Daya Selaras</strong>
      
        <div class="qrwrap">
          <span class="qrimg">
            @if(!empty($qrPathForPdf))
              <img src="{{ $qrPathForPdf }}" alt="QR">
            @elseif(!empty($qrInlineSvg))
              {!! $qrInlineSvg !!}
            @endif
          </span>
        </div>
      
        <br>
        <strong><u>Vica Krisdianatha</u></strong><br>
        Operation Manager
      </td>
      <td style="width:45%;">
        <div class="contact">
          <b>Kontak :</b>
          <strong>{{ $contact['name'] }}</strong><br>
          {{ $contact['role'] }}<br>
          {{ $contact['phone'] }}<br>
          {{ $contact['email'] }}
        </div>
      </td>
    </tr>
  </table>
</div>

<!-- Pita & footer -->
<div class="brand-band"></div>
<div class="footer">
  <hr style="border: none; border-top: 1px solid #000;">
  <strong >PT. Tri Daya Selaras, </strong> • Graha Irama Building 6th floor unit G, Jln. HR Rasuna Said Blok X1 Kav 1-2 •
  Telp. +021 5289 2321 • Fax +021 5289 2310 • www.tridayaselaras.com
</div>

</body>
</html>
