<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Credit Application {{ $customer->company_name ?? '-' }}</title>
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
            font-size: 10pt;
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
            font-size: 13pt;
            font-weight: bold;
            color: #022c22;
            margin: 0;
        }

        .section-title {
            background-color: #2a6a3c;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            padding: 6px;
            margin-top: 10px;
            border-right: 1px solid #2a6a3c;
            border-top: 1px solid #2a6a3c;
            text-transform: uppercase;
        }

        .section-content {
            padding: 6px 12px;
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
            border-left: 1px solid #cbd5e1;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        table.data-table td {
            padding: 3px 4px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 34mm;
        }
        .value {
            color: #0f172a;
        }

        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 8px;
        }
        table.grid-table th, table.grid-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            text-align: left;
            font-size: 10pt;
            vertical-align: top;
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
            text-align: center;
        }

        .checkbox-mark {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1;
            color: #0f172a;
            margin-right: 2px;
        }

        .muted {
            color: #666;
            font-style: italic;
        }

        .rich-text-content ul { list-style: disc; padding-left: 1.4em; margin: 4px 0; }
        .rich-text-content ol { list-style: decimal; padding-left: 1.4em; margin: 4px 0; }
        .rich-text-content li { margin: 2px 0; }
        .rich-text-content p { margin: 4px 0; }

        .sig-box {
            border: 1px solid #cbd5e1;
            padding: 6px;
        }
        .sig-title {
            font-weight: bold;
            font-size: 10pt;
            color: #1e293b;
        }
        .sig-caption {
            font-size: 9pt;
            color: #475569;
        }
        .sig-area {
            height: 70px;
        }
        table.sig-pair {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        table.sig-pair td {
            padding: 0;
            vertical-align: bottom;
            font-size: 8.5pt;
        }
        table.sig-pair td.sp-head {
            width: 50%;
            padding: 6px 6px 0;
            vertical-align: top;
            font-size: 10pt;
        }
        table.sig-pair td.sp-label {
            width: 10mm;
            white-space: nowrap;
            padding-left: 6px;
            color: #334155;
        }
        table.sig-pair td.sp-value {
            width: 25.5mm;
            padding-right: 6px;
            font-weight: bold;
        }
        table.sig-pair td.sp-divider {
            border-left: 1px solid #cbd5e1;
        }
        table.sig-pair tr.sp-name td {
            height: 36px;
        }
        table.sig-pair tr.sp-last td {
            padding-bottom: 6px;
        }
        .sp-line {
            border-bottom: 1px dashed #94a3b8;
            padding: 4px 2px 2px;
        }
        .sig-line {
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 3px;
        }
        .sig-role {
            font-size: 8pt;
            color: #475569;
            text-align: center;
        }

        .section-table table.grid-table {
            margin: 0;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        .committee-section {
            page-break-inside: avoid;
        }


        .notes-box {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            margin-bottom: 6px;
            min-height: 50px;
        }
        .notes-box .notes-label {
            font-weight: bold;
            color: #475569;
            font-size: 9pt;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 9pt;
            font-style: italic;
            color: #475569;
        }
    </style>
</head>
<body>

@php
    $regionName = fn ($r) => $r?->name ?? null;
    $formatAddressLine = function ($address) use ($regionName) {
        if (!$address) {
            return null;
        }

        $line = trim((string) $address->address_line);
        $alreadyWritten = function (?string $name) use ($line) {
            if (blank($name)) {
                return true;
            }
            $core = trim(preg_replace('/^(kabupaten|kab\.|kota administrasi|kota adm\.|kota|provinsi|daerah khusus ibukota)\s+/i', '', $name));

            return $line !== '' && stripos($line, $core) !== false;
        };

        $parts = [$line !== '' ? $line : null];
        foreach ([$address->village, $address->district, $address->regency, $address->province] as $region) {
            $name = $regionName($region);
            if (!$alreadyWritten($name)) {
                $parts[] = $name;
            }
        }
        if (filled($address->postal_code) && !preg_match('/\b\d{5}\b/', $line)) {
            $parts[] = $address->postal_code;
        }

        return collect($parts)->filter()->implode(', ');
    };
    $submissionTypes = ['New Customer', 'Re-Activated', 'Add TOP', 'Add CL'];
    $committeeMembers = [
        ['name' => 'Robby Pratama P', 'role' => 'Project Leader'],
        ['name' => 'Reni Yulianty', 'role' => 'Collection'],
        ['name' => 'Achmad Andryan', 'role' => 'Finance Manager'],
        ['name' => 'Irwanti', 'role' => 'CFO'],
        ['name' => 'Vica Krisdianatha', 'role' => 'CEO'],
        ['name' => null, 'role' => 'Commissioner'],
        ['name' => 'Daniel Tedy Djaja', 'role' => 'PTI'],
    ];
    $leftNotes = ['Controller & Audit Internal Notes', 'Finance Manager Notes', 'COO Notes', 'CFO Notes', 'CEO Notes', 'Commissioner Notes'];
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
                <div class="form-title">CREDIT APPROVAL FORM</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Customer Information</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label">Company Name:</td>
            <td class="value" colspan="3"><b>{{ $customer->company_name ?? '' }}</b></td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td class="value" colspan="3">{{ $formatAddressLine($headOfficeAddress) }}</td>
        </tr>
        <tr>
            <td class="label">Type of Submission:</td>
            <td class="value" colspan="3">
                @foreach ($submissionTypes as $type)
                    @php $checked = $type === 'New Customer'; @endphp
                    <span class="checkbox-mark">{!! $checked ? '&#9745;' : '&#9744;' !!}</span>
                    <span style="font-weight: {{ $checked ? 'bold' : 'normal' }};">{{ $type }}</span>&nbsp;&nbsp;&nbsp;
                @endforeach
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Financial Review</div>
<div class="rich-text-content section-content" style="min-height: 100px;">
    @if ($verification->financial_review)
        {!! $verification->financial_review !!}
    @else
        <div class="muted">Belum ada Financial Review tercatat.</div>
    @endif
</div>

<div class="keep-together">
<div class="section-title">Credit Limit &amp; TOP</div>
<div class="section-table">
    @php
        $rupiah = fn ($n) => $n === null ? '' : 'Rp ' . number_format((float) $n, 0, ',', '.');
        $days = fn ($n) => $n === null ? '' : $n . ' days';
        $volume = function ($n, $unit) {
            if ($n === null) {
                return '';
            }

            $formatted = rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');

            return $formatted . ' ' . $unit;
        };
    @endphp
    <table class="grid-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 16%;">Product</th>
                <th rowspan="2" style="width: 16%;">Volume per PO</th>
                <th colspan="2">Credit Limit</th>
                <th colspan="2">TOP</th>
            </tr>
            <tr>
                <th style="width: 17%;">Request</th>
                <th style="width: 17%;">Approval</th>
                <th style="width: 17%;">Request</th>
                <th style="width: 17%;">Approval</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">{{ $verification->product_category_snapshot?->label() ?? '-' }}</td>
                <td style="text-align: center;">{{ $volume($verification->requested_qty_snapshot, $verification->product_category_snapshot?->unit()) }}</td>
                <td style="text-align: center;">{{ $rupiah($verification->requested_limit_snapshot) }}</td>
                <td style="text-align: center;">{{ $rupiah($verification->approved_limit) }}</td>
                <td style="text-align: center;">{{ $days($verification->requested_top_snapshot) }}</td>
                <td style="text-align: center;">{{ $days($verification->approved_top) }}</td>
            </tr>
        </tbody>
    </table>
</div>
</div>

<div class="committee-section">
<div class="section-title">Credit Committee Approval</div>
<div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <td class="sig-box" style="width: 14.28%; text-align: center; font-weight: bold; background-color: #f8fafc;">Presented by,</td>
            <td class="sig-box" colspan="2" style="width: 28.56%; text-align: center; font-weight: bold; background-color: #f8fafc;">Checked by,</td>
            <td class="sig-box" style="width: 14.28%; text-align: center; font-weight: bold; background-color: #f8fafc;">Approval 1</td>
            <td class="sig-box" style="width: 14.28%; text-align: center; font-weight: bold; background-color: #f8fafc;">Approval 2</td>
            <td class="sig-box" style="width: 14.28%; text-align: center; font-weight: bold; background-color: #f8fafc;">Approval 3</td>
            <td class="sig-box" style="width: 14.28%; text-align: center; font-weight: bold; background-color: #f8fafc;">Approval 4</td>
        </tr>
        <tr>
            @foreach ($committeeMembers as $member)
                <td class="sig-box" style="vertical-align: top; text-align: center; padding: 6px 3px;">
                    <div class="sig-area"></div>
                    <div class="sig-line"></div>
                    <div style="font-weight: bold; font-size: 8pt; min-height: 10pt;">{{ $member['name'] }}</div>
                    <div class="sig-role">{{ $member['role'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 8px;">
                @foreach ($leftNotes as $noteLabel)
                    <div class="notes-box">
                        <div class="notes-label">{{ $noteLabel }}:</div>
                    </div>
                @endforeach
            </td>
            <td style="width: 42%; vertical-align: top;">
                <div class="notes-box" style="min-height: 200px;">
                    <div class="notes-label">General Notes:</div>
                </div>

                <table class="sig-pair">
                    <tr>
                        <td class="sp-head" colspan="2">
                            <div class="sig-title">KAE/Marketing</div>
                            <div class="sig-caption">Proposed by</div>
                            <div class="sig-area"></div>
                        </td>
                        <td class="sp-head sp-divider" colspan="2">
                            <div class="sig-title">Administration</div>
                            <div class="sig-caption">Review by</div>
                            <div class="sig-area"></div>
                        </td>
                    </tr>
                    <tr class="sp-name">
                        <td class="sp-label"><div class="sp-line">Name:</div></td>
                        <td class="sp-value"><div class="sp-line">{{ $marketingName }}</div></td>
                        <td class="sp-label sp-divider"><div class="sp-line">Name:</div></td>
                        <td class="sp-value"><div class="sp-line">Eka Riyanti</div></td>
                    </tr>
                    <tr class="sp-last">
                        <td class="sp-label"><div class="sp-line">Date:</div></td>
                        <td class="sp-value"><div class="sp-line">&nbsp;</div></td>
                        <td class="sp-label sp-divider"><div class="sp-line">Date:</div></td>
                        <td class="sp-value"><div class="sp-line">&nbsp;</div></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="footer-note">*To be completed by marketing / Harap dilengkapi oleh marketing</div>
</div>

</body>
</html>
