<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Credit Application {{ $customer->company_name ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
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
            width: 100%;
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
        }

        .section-content {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
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
            margin-bottom: 8px;
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
            text-align: center;
        }

        .checkbox-mark {
            font-family: 'DejaVu Sans', sans-serif;
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
            background-color: #fafafa;
        }
        .sig-title {
            font-weight: bold;
            font-size: 7.5pt;
            color: #1e293b;
            margin-bottom: 20px;
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
        .sig-role {
            font-size: 7pt;
            color: #64748b;
            text-align: center;
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
            font-size: 7.5pt;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 7.5pt;
            font-style: italic;
            color: #64748b;
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

        $provincePostal = trim(collect([$regionName($address->province), $address->postal_code])->filter()->implode(' '));

        return collect([
            $address->address_line,
            $regionName($address->village),
            $regionName($address->district),
            $regionName($address->regency),
            $provincePostal !== '' ? $provincePostal : null,
        ])->filter()->implode(', ');
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

<div class="section-title">1. Customer Information</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label" width="20%">Company Name:</td>
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

<div class="section-title">2. Financial Review</div>
<div class="rich-text-content section-content" style="min-height: 100px;">
    @if ($verification->financial_review)
        {!! $verification->financial_review !!}
    @else
        <div class="muted">Belum ada Financial Review tercatat.</div>
    @endif
</div>

<div class="section-title">3. Credit Limit &amp; TOP</div>
<div class="section-content">
    @php
        $rupiah = fn ($n) => $n === null ? '' : 'Rp ' . number_format((float) $n, 0, ',', '.');
        $days = fn ($n) => $n === null ? '' : $n . ' days';
    @endphp
    <table class="grid-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 20%;">Product</th>
                <th colspan="2">Credit Limit</th>
                <th colspan="2">TOP</th>
            </tr>
            <tr>
                <th style="width: 20%;">Request</th>
                <th style="width: 20%;">Approval</th>
                <th style="width: 20%;">Request</th>
                <th style="width: 20%;">Approval</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">Crushed Stone</td>
                <td style="text-align: center;">{{ $rupiah($creditRequest?->requested_limit) }}</td>
                <td style="text-align: center;">{{ $rupiah($verification->approved_limit) }}</td>
                <td style="text-align: center;">{{ $days($creditRequest?->requested_top) }}</td>
                <td style="text-align: center;">{{ $days($verification->approved_top) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="section-title">4. Credit Committee Approval</div>
<div class="section-content">
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
                <td class="sig-box" style="vertical-align: top; text-align: center;">
                    <div class="sig-area"></div>
                    <div class="sig-line"></div>
                    <div style="font-weight: bold; font-size: 7.5pt; min-height: 9pt;">{{ $member['name'] }}</div>
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
                <div class="notes-box" style="min-height: 110px;">
                    <div class="notes-label">General Notes:</div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                    <tr>
                        <td class="sig-box" style="vertical-align: top;">
                            <div class="sig-title">Validation by,</div>
                            <table class="data-table" style="border-spacing: 0;">
                                <tr><td class="sig-area"></td></tr>
                            </table>
                            <div class="sig-line"></div>
                            <div class="sig-role">Customer Support</div>
                        </td>
                    </tr>
                </table>

                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="sig-box" style="width: 50%; vertical-align: top;">
                            <div class="sig-title">Proposed by,</div>
                            <table class="data-table" style="border-spacing: 0;">
                                <tr><td class="sig-area"></td></tr>
                            </table>
                            <div class="sig-line"></div>
                            <div class="sig-role">Robby Pratama P<br>KAE/Marketing Administration</div>
                        </td>
                        <td class="sig-box" style="width: 50%; vertical-align: top;">
                            <div class="sig-title">Review by,</div>
                            <table class="data-table" style="border-spacing: 0;">
                                <tr><td class="sig-area"></td></tr>
                            </table>
                            <div class="sig-line"></div>
                            <div class="sig-role">Eka Riyanti<br>Administration</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="footer-note">*To be completed by marketing / Harap dilengkapi oleh marketing</div>

</body>
</html>
