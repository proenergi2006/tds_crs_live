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
            font-size: 10pt;
            vertical-align: top;
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }

        table.review-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.review-table td {
            padding: 0 4px;
            vertical-align: top;
        }
        td.review-no {
            width: 16px;
            padding-top: 3px;
        }
        td.review-question {
            width: 50%;
            padding-top: 3px;
            padding-right: 12px;
        }
        td.answer-line {
            border-left: 1px solid #94a3b8;
            border-right: 1px solid #94a3b8;
            padding: 0 5px;
        }
        td.answer-first {
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }
        td.answer-last {
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 1.25em;
        }
        td.review-gap {
            height: 8px;
        }

        .muted {
            color: #666;
            font-style: italic;
        }

        .closing-block {
            page-break-inside: avoid;
        }
        .signature-section {
            page-break-inside: avoid;
        }
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
        table.sig-fields {
            width: 100%;
            border-collapse: collapse;
        }
        table.sig-fields td {
            border-bottom: 1px dashed #94a3b8;
            padding: 4px 2px 2px;
        }
        table.sig-fields td.sig-field-label {
            width: 1%;
            white-space: nowrap;
            padding-right: 6px;
            color: #334155;
        }
        table.sig-fields td.sig-field-value {
            font-weight: bold;
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

<div class="section-title">Rincian Customer</div>
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

<div class="section-title">Sales Review</div>
<div class="section-content">
    {{-- satu baris jawaban = satu <tr>: DomPDF tidak memecah <tr> antar halaman, jadi jawaban panjang hanya bisa terpotong di antara baris --}}
    <table class="review-table">
        @forelse (($review->review_answers ?? []) as $index => $answer)
            @php
                $lines = preg_split('/\R/', trim((string) ($answer['answer'] ?? '')));
                $lastLine = count($lines) - 1;
            @endphp
            @foreach ($lines as $i => $line)
                <tr>
                    @if ($i === 0)
                        <td class="review-no">{{ $index + 1 }}.</td>
                        <td class="label review-question">{{ $answer['question'] ?? ($answer['question_code'] ?? '-') }}</td>
                    @else
                        <td class="review-no"></td>
                        <td class="review-question"></td>
                    @endif
                    <td class="answer-line{{ $i === 0 ? ' answer-first' : '' }}{{ $i === $lastLine ? ' answer-last' : '' }}">{{ $line !== '' ? $line : "\u{00A0}" }}</td>
                </tr>
            @endforeach
            <tr><td class="review-gap" colspan="3"></td></tr>
        @empty
            <tr><td colspan="3" class="muted">Belum ada Sales Review tercatat.</td></tr>
        @endforelse
    </table>
</div>

<div class="closing-block">
<div class="section-title">Detail Informasi</div>
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
                <div class="sig-title">Marketing</div>
                <div class="sig-caption">Proposed by</div>
                <div class="sig-area"></div>
                <table class="sig-fields">
                    <tr><td class="sig-field-label">Name:</td><td class="sig-field-value">{{ $marketingName }}</td></tr>
                    <tr><td class="sig-field-label">Date:</td><td class="sig-field-value">&nbsp;</td></tr>
                </table>
            </td>
            <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                <div class="sig-title">Administration</div>
                <div class="sig-caption">Review by</div>
                <div class="sig-area"></div>
                <table class="sig-fields">
                    <tr><td class="sig-field-label">Name:</td><td class="sig-field-value">Eka Riyanti</td></tr>
                    <tr><td class="sig-field-label">Date:</td><td class="sig-field-value">&nbsp;</td></tr>
                </table>
            </td>
            <td class="sig-box" style="width: 33.33%; vertical-align: top;">
                <div class="sig-title">Supervisor</div>
                <div class="sig-caption">Approved by</div>
                <div class="sig-area"></div>
                <table class="sig-fields">
                    <tr><td class="sig-field-label">Name:</td><td class="sig-field-value">{{ $supervisorName }}</td></tr>
                    <tr><td class="sig-field-label">Date:</td><td class="sig-field-value">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</div>

</body>
</html>
