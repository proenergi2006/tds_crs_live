{{-- resources/views/vendor_pos/penawaran_pdf_lubricant.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penawaran {{ $penawaran->nomor_penawaran }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        .container { width: 700px; margin: 0 auto; padding: 15px; }
        h1 { text-align: center; font-size: 18px; text-decoration: underline; color: #b71c1c; }
        h2 { text-align: center; font-size: 14px; margin-bottom: 10px; color: #b71c1c; }
        .header-info { text-align: right; font-size: 11px; margin-bottom: 10px; }
        .section { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; font-size: 11px; }
        th { background: #b71c1c; color: #fff; }
        .text-right { text-align: right; }
        .footer { font-size: 10px; text-align: center; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>No. {{ $penawaran->nomor_penawaran }}</h2>
        <div class="header-info">
            Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
        </div>

        <div class="section">
            Kepada Yth:<br>
            <strong>PT. {{ $penawaran->customer->nama_perusahaan }}</strong><br>
            {{ $penawaran->customer->alamat_perusahaan ?? '-' }}<br>
            <u>{{ $penawaran->kontak_person ?? 'PIC' }}</u> - Purchasing
        </div>

        <div class="section">
            Dengan Hormat,
        </div>

        <div class="section" style="text-align: center; margin: 10px 0;">
            <strong>Perihal: Penawaran Harga Lubricant ENEOS</strong>
        </div>
       

        <div class="section">
            Bersama surat ini, perkenankan kami memperkenalkan diri bahwa kami dari 
            <strong>PT. Pro Energi</strong> sebagai Badan Usaha Berbadan Hukum dan bergerak sebagai distribusi resmi 
            Lubricant JXTG Nippon Oil & Energy dengan merek ENEOS. Dengan pengalaman, jaminan produk, sumber daya dan 
            fasilitas yang tersedia, kami hadir untuk memberikan solusi kebutuhan Lubricant untuk <strong>{{ $penawaran->customer->nama_perusahaan }}</strong>.<br><br>
            Oleh karena itu, kami ingin menawarkan kepada perusahaan Bapak/Ibu dengan detail sebagai berikut:
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Kemasan</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">PPN 11%</th>
                    <th class="text-right">Harga + PPN</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Jumlah Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penawaran->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->produk->nama_produk }}</td>
                    <td>{{ $item->produk->ukuran->nama_ukuran ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_tebus, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_tebus * 0.11, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_tebus * 1.11, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->volume_order, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->jumlah_harga, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-right" style="font-weight: 600;">Subtotal</td>
                    <td class="text-right">Rp {{ number_format($penawaran->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right" style="font-weight: 600;">PPN 11%</td>
                    <td class="text-right">Rp {{ number_format($penawaran->ppn11, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="7" class="text-right" style="font-weight: 700; background: #f0f0f0;">Grand Total</td>
                    <td class="text-right" style="font-weight: 700; background: #f0f0f0;">Rp {{ number_format($penawaran->total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="section">
            <p><strong>Keterangan:</strong></p>
            <table style="border-collapse: collapse; border: none; margin-left: 20px; font-size: 13px;">
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap; width: 150px;">Terima/FOB</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">{{ $penawaran->customer->nama_perusahaan }} {{ $penawaran->customer->alamat_perusahaan ?? '-' }}</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Quotation Valid</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">25 Juni 2025</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Payment Term</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">Credit 30 hari</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Price</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">Harga sudah termasuk Ppn 11% & Ongkos kirim</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Payment Method</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">Transfer via Bank</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Note</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">Mohon Transfer Bank BCA Cabang Cyber 2 Jakarta</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">Nama Acc.</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">PT. Pro Energi</td>
              </tr>
              <tr>
                <td style="border: none; padding: 2px 4px 2px 0; white-space: nowrap;">No Acc.</td>
                <td style="border: none; padding: 2px 4px;">:</td>
                <td style="border: none; padding: 2px;">6070802889</td>
              </tr>
            </table>
          </div>
          

        <div class="section">
            Demikian surat penawaran ini kami sampaikan, besar harapan kami untuk dapat diberikan kesempatan dan 
            kepercayaan kepada kami untuk dapat berbisnis dengan<strong>{{ $penawaran->customer->nama_perusahaan }}</strong>. Informasi lebih lanjut dapat 
            menghubungi kontak sales kami yang tertera pada akhir surat ini.<br><br>
            Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.
        </div>

        <div class="section">
            <strong><i>Best Regards,</i><br>
            <br><br><br>
            <u>Fransiska Febby Petriani</u><br>
            <i>Commercial Lubricant</i></strong>
        </div>
       
        
        <div class="footer">
            PT. Pro Energi | Gedung Graha Irama Lt. 6 Unit G, Jl. HR Rasuna Said Blok X1 Kav 1-2 Jakarta<br>
            Tel: (021) 5289 2321 | Fax: (021) 5289 2310 | www.proenergi.com
        </div>
    </div>
</body>
</html>
