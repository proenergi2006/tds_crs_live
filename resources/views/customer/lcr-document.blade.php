<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LCR {{ $customer->company_name ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin-top: 2.7cm;
            margin-right: 1cm;
            margin-bottom: 1cm;
            margin-left: 1cm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #2b2b2b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: -1.7cm;
            left: 0;
            right: 0;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        table.logo-pair {
            width: auto;
            border-collapse: collapse;
        }
        .logo-cell {
            padding: 0 4px 0 0;
            vertical-align: middle;
        }
        .logo-cell img {
            height: 44px;
            width: auto;
        }
        .form-title {
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
            color: #022c22;
            margin: 0;
        }

        .site-banner {
            background-color: #022c22;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            padding: 5px 8px;
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .site-banner .site-index {
            font-weight: normal;
            font-size: 8pt;
            color: #cbd5e1;
        }

        .section-title {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 3px 6px;
            margin-top: 8px;
            border-left: 3px solid #022c22;
            border-right: 1px solid #cbd5e1;
            border-top: 1px solid #cbd5e1;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .section-content {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        table.data-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 22%;
        }
        .value {
            color: #0f172a;
            width: 28%;
        }

        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        table.grid-table th, table.grid-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            text-align: left;
            font-size: 8pt;
            vertical-align: top;
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }

        .muted {
            color: #666;
            font-style: italic;
        }

        .sub-heading {
            font-weight: bold;
            color: #022c22;
            font-size: 8pt;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 2px;
        }

        .photo-category {
            margin-bottom: 8px;
        }
        .photo-category-title {
            font-weight: bold;
            color: #1e293b;
            font-size: 8pt;
            margin-bottom: 4px;
        }
        .photo-card {
            display: inline-block;
            width: 150px;
            vertical-align: top;
            margin: 0 8px 8px 0;
            border: 1px solid #cbd5e1;
            padding: 4px;
        }
        .photo-card img {
            width: 142px;
            height: 106px;
            object-fit: cover;
        }
        .photo-card .photo-caption {
            font-size: 7pt;
            color: #475569;
            margin-top: 3px;
        }

        .signature-section {
            page-break-inside: avoid;
        }
        .sig-box {
            border: 1px solid #cbd5e1;
            padding: 6px;
            background-color: #fafafa;
        }
        .sig-title {
            font-weight: bold;
            font-size: 8.5pt;
            color: #1e293b;
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
        }
        .sig-line {
            border-bottom: 1px dotted #94a3b8;
            margin-bottom: 3px;
        }
        .sig-area {
            display: block;
            min-height: 50px;
        }
    </style>
</head>
<body>

@php
    $dash = fn ($v) => $v === null || $v === '' ? '' : $v;
    $regionName = fn ($r) => $r?->name ?? null;
    $formatAddressLine = function ($address) use ($regionName) {
        if (!$address) {
            return '';
        }

        $provincePostal = trim(collect([$regionName($address->province), $address->postal_code])->filter()->implode(' '));

        return collect([
            $address->address_line,
            $regionName($address->village),
            $regionName($address->district),
            $regionName($address->regency),
            $provincePostal !== '' ? $provincePostal : null,
        ])->filter()->implode(', ');
    };
    $rupiah = fn ($n) => $n === null ? '' : 'Rp ' . number_format((float) $n, 0, ',', '.');
    $codesToLabels = function (?array $codes, string $enumClass, ?string $otherText = null) {
        if (empty($codes)) {
            return '';
        }

        $labels = collect($codes)->map(function ($code) use ($enumClass, $otherText) {
            $label = $enumClass::tryFrom($code)?->label() ?? $code;
            if ($code === 'other' && $otherText) {
                $label .= ": {$otherText}";
            }
            return $label;
        });

        return $labels->implode(', ');
    };

    $photoCategories = [
        'road_condition_photos'      => 'Foto Kondisi Jalan Menuju Lokasi',
        'site_layout_photos'         => 'Foto Layout Site/Pabrik',
        'unloading_layout_photos'    => 'Foto Layout Area Unloading',
        'storage_facility_photos'    => 'Foto Fasilitas Penyimpanan',
        'measurement_evidence_photos' => 'Foto Alat Ukur',
        'vessel_layout_photos'       => 'Foto Layout Vessel/Jetty',
        'company_office_photos'      => 'Foto Kantor & Gerbang Perusahaan',
        'additional_photos'          => 'Foto Tambahan',
    ];
@endphp

<div class="header">
    <table>
        <tr>
            <td>
                <table class="logo-pair">
                    <tr>
                        <td class="logo-cell">
                            @if ($logoLeft)
                                <img src="{{ $logoLeft }}" alt="Logo TDS">
                            @endif
                        </td>
                        <td class="logo-cell">
                            @if ($logoRight)
                                <img src="{{ $logoRight }}" alt="Logo Crushed Stone">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="text-align: right;">
                <div class="form-title">LCR (LOKASI SURVEY) FORM</div>
            </td>
        </tr>
    </table>
</div>

@forelse ($lcrSites as $index => $site)
    <div class="site-banner" @if($index > 0) style="page-break-before: always;" @endif>
        {{ $index + 1 }}. {{ $site->site_name ?: 'Site LCR' }}
        <span class="site-index">&mdash; {{ $site->latestDocumentApproval?->status?->label() ?? 'Belum diajukan' }}</span>
    </div>

    <div class="section-title">Identitas &amp; Lokasi</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label" width="20%">Nama Lokasi:</td>
                <td class="value" colspan="3"><b>{{ $dash($site->site_name) }}</b></td>
            </tr>
            <tr>
                <td class="label">Alamat Site:</td>
                <td class="value" colspan="3">{{ $formatAddressLine($site->address) }}</td>
            </tr>
            <tr>
                <td class="label">Wilayah OA:</td>
                <td class="value">
                    @if ($site->wilayahAngkut)
                        {{ $regionName($site->wilayahAngkut->province) }} - {{ $regionName($site->wilayahAngkut->regency) }} - {{ $dash($site->wilayahAngkut->destinasi) }}
                    @endif
                </td>
                <td class="label">Link Google Maps:</td>
                <td class="value">{{ $dash($site->google_maps_link) }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal Survey:</td>
                <td class="value">{{ $site->survey_date?->format('d M Y') ?? '' }}</td>
                <td class="label">Nama Surveyor:</td>
                <td class="value">{{ $dash($site->surveyor_names) }}</td>
            </tr>
        </table>

        <div class="sub-heading">Penanggung Jawab (PIC)</div>
        <table class="grid-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Nama</th>
                    <th style="width: 25%;">Posisi</th>
                    <th style="width: 25%;">No. HP</th>
                    <th style="width: 25%;">Email</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($site->contacts as $c)
                    <tr>
                        <td>{{ $dash($c->full_name) }}</td>
                        <td>{{ $dash($c->position) }}</td>
                        <td>{{ $dash($c->mobile) }}</td>
                        <td>{{ $dash($c->email) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Belum ada PIC tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Profil Bisnis &amp; Operasional</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label" width="20%">Jenis Usaha Site:</td>
                <td class="value" colspan="3">{{ $dash($site->site_business_type === 'other' ? $site->site_business_type_other : $site->site_business_type) }}</td>
            </tr>
            <tr>
                <td class="label">Lingkungan Site:</td>
                <td class="value">{{ $dash($site->site_environment?->value === 'other' ? $site->site_environment_other : $site->site_environment?->label()) }}</td>
                <td class="label">Jam Operasional:</td>
                <td class="value">{{ $dash($site->operating_hours) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Lingkungan:</td>
                <td class="value" colspan="3">{{ $dash($site->site_environment_notes) }}</td>
            </tr>
            <tr>
                <td class="label">Kompetitor:</td>
                <td class="value" colspan="3">{{ $dash($site->competitors) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Survey:</td>
                <td class="value" colspan="3">{{ $dash($site->survey_notes) }}</td>
            </tr>
        </table>

        <div class="sub-heading">Produk &amp; Volume/Bulan</div>
        <table class="grid-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Produk</th>
                    <th style="width: 50%;">Volume/Bulan</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($site->product_volume ?? []) as $p)
                    <tr>
                        <td>{{ $dash($p['produk'] ?? '') }}</td>
                        <td>{{ $dash($p['volume_bulan'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="muted">Belum ada data produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Akses &amp; Rute</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label" width="20%">Kapasitas Truk (Ton):</td>
                <td class="value">Min {{ $dash($site->max_truck_capacity_min) }} &ndash; Max {{ $dash($site->max_truck_capacity_max) }}</td>
                <td class="label">Jarak dari Depot:</td>
                <td class="value">{{ $dash($site->distance_from_depot) }}</td>
            </tr>
            <tr>
                <td class="label">Minimal Volume Kirim:</td>
                <td class="value">{{ $dash($site->min_vol_kirim) }}</td>
                <td class="label"></td>
                <td class="value"></td>
            </tr>
            <tr>
                <td class="label">Catatan Akses:</td>
                <td class="value" colspan="3">{{ $dash($site->access_notes) }}</td>
            </tr>
            <tr>
                <td class="label">Rute Lokasi:</td>
                <td class="value" colspan="3">{{ $dash($site->rute_lokasi) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Lokasi:</td>
                <td class="value" colspan="3">{{ $dash($site->note_lokasi) }}</td>
            </tr>
        </table>

        <div class="sub-heading">Biaya Rute</div>
        <table class="grid-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Jenis Biaya</th>
                    <th style="width: 25%;">Nominal</th>
                    <th style="width: 45%;">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($site->route_costs ?? []) as $r)
                    <tr>
                        <td>{{ $dash($r['cost_type'] ?? '') }}</td>
                        <td>{{ $rupiah($r['amount'] ?? null) }}</td>
                        <td>{{ $dash($r['notes'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Belum ada data biaya rute.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Unloading &amp; Storage</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label" width="20%">Metode Unloading:</td>
                <td class="value">{{ $dash($site->unloading_method) }}</td>
                <td class="label">Maks Truk/Hari:</td>
                <td class="value">{{ $dash($site->max_trucks_per_day) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Unloading:</td>
                <td class="value" colspan="3">{{ $dash($site->unloading_notes) }}</td>
            </tr>
            <tr>
                <td class="label">Tipe Penyimpanan:</td>
                <td class="value">{{ $dash($site->storage_type?->value === 'other' ? $site->storage_type_other : $site->storage_type?->label()) }}</td>
                <td class="label">Kapasitas Penyimpanan:</td>
                <td class="value">{{ $dash($site->storage_capacity) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Penyimpanan:</td>
                <td class="value" colspan="3">{{ $dash($site->storage_notes) }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Verifikasi Quality &amp; Quantity</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label" width="20%">Metode Pemeriksaan Kualitas:</td>
                <td class="value" colspan="3">{{ $codesToLabels($site->quality_checking_method, \App\Enums\QualityCheckingMethod::class, $site->quality_checking_method_other) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Kualitas:</td>
                <td class="value" colspan="3">{{ $dash($site->quality_checking_notes) }}</td>
            </tr>
            <tr>
                <td class="label">Metode Pemeriksaan Kuantitas:</td>
                <td class="value" colspan="3">{{ $codesToLabels($site->quantity_checking_method, \App\Enums\QuantityCheckingMethod::class, $site->quantity_checking_method_other) }}</td>
            </tr>
            <tr>
                <td class="label">Catatan Kuantitas:</td>
                <td class="value" colspan="3">{{ $dash($site->quantity_checking_notes) }}</td>
            </tr>
        </table>
    </div>

    @if ($site->supports_vessel_delivery)
        <div class="section-title">Vessel &amp; Jetty Info</div>
        <div class="section-content">
            <table class="data-table">
                <tr>
                    <td class="label" width="20%">Jenis Kapal:</td>
                    <td class="value">{{ $dash($site->vessel_type?->value === 'other' ? $site->vessel_type_other : $site->vessel_type?->label()) }}</td>
                    <td class="label">Kapasitas Kargo:</td>
                    <td class="value">{{ $dash($site->vessel_cargo_capacity) }}</td>
                </tr>
                <tr>
                    <td class="label">Metode Bongkar Kapal:</td>
                    <td class="value" colspan="3">{{ $dash($site->vessel_unloading_method?->value === 'other' ? $site->vessel_unloading_method_other : $site->vessel_unloading_method?->label()) }}</td>
                </tr>
                <tr>
                    <td class="label">Metode Kuantitas Kapal:</td>
                    <td class="value" colspan="3">{{ $codesToLabels($site->vessel_quantity_checking_method, \App\Enums\CustomerLcrVesselQuantityCheckingMethod::class, $site->vessel_quantity_checking_method_other) }}</td>
                </tr>
                <tr>
                    <td class="label">Catatan Kuantitas Kapal:</td>
                    <td class="value" colspan="3">{{ $dash($site->vessel_quantity_checking_notes) }}</td>
                </tr>
                <tr>
                    <td class="label">Metode Kualitas Kapal:</td>
                    <td class="value" colspan="3">{{ $codesToLabels($site->vessel_quality_checking_method, \App\Enums\VesselQualityCheckingMethod::class, $site->vessel_quality_checking_method_other) }}</td>
                </tr>
                <tr>
                    <td class="label">Catatan Kualitas Kapal:</td>
                    <td class="value" colspan="3">{{ $dash($site->vessel_quality_checking_notes) }}</td>
                </tr>
                <tr>
                    <td class="label">Tipe Jetty:</td>
                    <td class="value">{{ $dash($site->jetty_type) }}</td>
                    <td class="label">Kapasitas Jetty (DWT):</td>
                    <td class="value">{{ $dash($site->jetty_capacity_dwt) }}</td>
                </tr>
                <tr>
                    <td class="label">Max LOA:</td>
                    <td class="value">{{ $dash($site->max_loa) }}</td>
                    <td class="label">Min PBL:</td>
                    <td class="value">{{ $dash($site->min_pbl) }}</td>
                </tr>
                <tr>
                    <td class="label">Draft (LWS):</td>
                    <td class="value">{{ $dash($site->draft_lws) }}</td>
                    <td class="label"></td>
                    <td class="value"></td>
                </tr>
                <tr>
                    <td class="label">Info Izin Jetty:</td>
                    <td class="value" colspan="3">{{ $dash($site->jetty_permit_info) }}</td>
                </tr>
                <tr>
                    <td class="label">Persyaratan Dokumen:</td>
                    <td class="value" colspan="3">{{ $dash($site->document_requirements) }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="section-title">Dokumentasi Foto</div>
    <div class="section-content">
        @php
            $siteHasAnyPhoto = collect($photosBySite[$site->id_lcr] ?? [])->flatten(1)->isNotEmpty();
        @endphp
        @if (!$siteHasAnyPhoto)
            <div class="muted">Belum ada foto tercatat untuk site ini.</div>
        @endif
        @foreach ($photoCategories as $field => $label)
            @php $photos = $photosBySite[$site->id_lcr][$field] ?? []; @endphp
            @if (($field !== 'vessel_layout_photos' || $site->supports_vessel_delivery) && count($photos) > 0)
                <div class="photo-category">
                    <div class="photo-category-title">{{ $label }}</div>
                    @foreach ($photos as $photo)
                        <div class="photo-card">
                            @if ($photo['data_uri'])
                                <img src="{{ $photo['data_uri'] }}" alt="{{ $photo['file_name'] }}">
                            @else
                                <div class="muted" style="width: 142px; height: 106px;">Gambar tidak tersedia</div>
                            @endif
                            @if ($photo['notes'])
                                <div class="photo-caption">{{ $photo['notes'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>

    <div class="signature-section">
        <div class="section-title">Signatures</div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                    <div style="font-size: 7.5pt; color: #64748b;">Fill by,</div>
                    <div class="sig-title">Marketing</div>
                    <table class="data-table" style="border-spacing: 0;">
                        <tr><td class="sig-area"></td></tr>
                        <tr><td class="label" style="width: 30%;">Name:</td></tr>
                        <tr><td class="label">Date:</td></tr>
                    </table>
                    <div class="sig-line"></div>
                </td>
                <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                    <div style="font-size: 7.5pt; color: #64748b;">Review by,</div>
                    <div class="sig-title">Logistik</div>
                    <table class="data-table" style="border-spacing: 0;">
                        <tr><td class="sig-area"></td></tr>
                        <tr><td class="label" style="width: 30%;">Name:</td></tr>
                        <tr><td class="label">Date:</td></tr>
                    </table>
                    <div class="sig-line"></div>
                </td>
                <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                    <div style="font-size: 7.5pt; color: #64748b;">&nbsp;</div>
                    <div class="sig-title">Sales Area Manager</div>
                    <table class="data-table" style="border-spacing: 0;">
                        <tr><td class="sig-area"></td></tr>
                        <tr><td class="label" style="width: 30%;">Name: Robby Pratama P</td></tr>
                        <tr><td class="label">Date:</td></tr>
                    </table>
                    <div class="sig-line"></div>
                </td>
            </tr>
        </table>
    </div>
@empty
    <div class="section-title">LCR</div>
    <div class="section-content">
        <div class="muted">Belum ada site LCR tercatat.</div>
    </div>
@endforelse

</body>
</html>
