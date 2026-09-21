<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sales Review {{ $customer->company_name ?? '-' }}</title>
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
        }
        .value {
            color: #0f172a;
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
            margin-bottom: 4px;
        }
        .sig-area {
            display: block;
            min-height: 40px;
        }
    </style>
</head>
<body>

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
                <div class="form-title">SALES REVIEW FORM</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">1. Rincian Customer</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label" width="20%">
                <span>Nama Perusahaan:</span>
                <span class="value"><b>{{ $customer->company_name ?? '' }}</b></span>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">2. Sales Review</div>
<div class="section-content">
    <table class="data-table">
        @forelse (($review->review_answers ?? []) as $index => $answer)
            <tr>
                <td width="16px">{{ $index + 1 }}.</td>
                <td class="label" width="50%">
                    <span>{{ $answer['question'] ?? ($answer['question_code'] ?? '-') }}</span>
                </td>
                <td class="value">
                    <textarea rows="2" readonly style="width: 100%; background: transparent; font-family: inherit; font-size: inherit; color: inherit; resize: none;">{{ $answer['answer'] ?? '' }}</textarea>
                </td>
            </tr>
        @empty
            <tr><td colspan="2" class="muted">Belum ada Sales Review tercatat.</td></tr>
        @endforelse
    </table>
</div>

<div class="section-title" style="page-break-before: always;">3. Detail Informasi</div>
<div class="section-content" style="min-height: 140px;">
    @if ($review->notes ?? null)
        <div>{!! nl2br(e($review->notes)) !!}</div>
    @else
        <div class="muted">Belum ada catatan tercatat.</div>
    @endif
</div>

<div class="signature-section">
    <div class="section-title">Signatures</div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                <div style="font-size: 7.5pt; color: #64748b;">Proposed by,</div>
                <div class="sig-title">Marketing</div>
                <table class="data-table" style="border-spacing: 0;">
                    <tr><td class="sig-area"></td></tr>
                    <tr><td class="label" style="width: 30%;">Name: Robby Pratama P</td></tr>
                    <tr><td class="label">Date:</td></tr>
                </table>
                <div class="sig-line"></div>
            </td>
            <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                <div style="font-size: 7.5pt; color: #64748b;">Review by,</div>
                <div class="sig-title">Administration</div>
                <table class="data-table" style="border-spacing: 0;">
                    <tr><td class="sig-area"></td></tr>
                    <tr><td class="label" style="width: 30%;">Name: Eka Riyanti</td></tr>
                    <tr><td class="label">Date:</td></tr>
                </table>
                <div class="sig-line"></div>
            </td>
            <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                <div style="font-size: 7.5pt; color: #64748b;">&nbsp;</div>
                <div class="sig-title">Area Sales Manager</div>
                <table class="data-table" style="border-spacing: 0;">
                    <tr><td class="sig-area"></td></tr>
                    <tr><td class="label" style="width: 30%;">Name: Vica Krisdianatha</td></tr>
                    <tr><td class="label">Date:</td></tr>
                </table>
                <div class="sig-line"></div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
