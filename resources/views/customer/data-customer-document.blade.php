<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Customer {{ $customer->company_name ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
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
        .label-inline {
            width: 29mm;
            white-space: nowrap;
        }
        .value-left {
            padding-right: 12px !important;
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
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }

        .checkbox-mark {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1;
            color: #0f172a;
            margin-right: 2px;
        }

        .creditor-box {
            border: 1px solid #cbd5e1;
            padding: 1px 4px;
            margin-top: 2px;
        }

        .col-container {
            width: 100%;
            margin-bottom: 6px;
        }
        .col-half {
            width: 49%;
            display: inline-block;
            vertical-align: top;
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

        .muted {
            color: #666;
            font-style: italic;
        }
        .field-other-note {
            margin-top: 2px;
            font-size: 9pt;
            font-style: italic;
            color: #64748b;
        }
        table.address-pair {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        td.address-card {
            width: 50%;
            padding: 2px 12px 2px 4px;
            vertical-align: top;
        }
        td.address-card-right {
            border-left: 1px solid #cbd5e1;
            padding-left: 12px;
        }
        .address-head {
            font-weight: bold;
            color: #475569;
            margin-bottom: 2px;
        }
        .section-table table.grid-table {
            margin: 0;
        }
    </style>
</head>
<body>

@php
    $dash = fn ($v) => $v === null ? '' : $v;
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
    $renderCheckboxOption = function (string $label, bool $checked) {
        $mark = $checked ? '&#9745;' : '&#9744;';
        $labelWeight = $checked ? 'bold' : 'normal';

        return '<span class="checkbox-mark">' . $mark . '</span> <span style="font-weight: ' . $labelWeight . ';">' . e($label) . '</span>';
    };
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
                <div class="form-title">CUSTOMER APPLICATION FORM</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Corporate Details</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label">Company Name:</td>
            <td class="value" colspan="3"><b>{{ $dash($customer->company_name) }}</b></td>
        </tr>
        <tr>
            <td class="label">Holding / Parent:</td>
            <td class="value" colspan="3"><b>{{ $dash($customer->parent_company) }}</b></td>
        </tr>
        <tr>
            <td class="label">Website:</td>
            <td class="value value-left" style="overflow-wrap: anywhere;">{{ $dash($customer->website) }}</td>
            <td class="label label-inline">Ownership:</td>
            <td class="value">{{ $dash($customer->ownership_type === 'Other' ? $customer->ownership_type_other : $customer->ownership_type) }}</td>
        </tr>
        <tr>
            <td class="label">Email:</td>
            <td class="value value-left">{{ $dash($customer->email) }}</td>
            <td class="label label-inline">Business Type:</td>
            <td class="value">{{ $dash($customer->business_type === 'Other' ? $customer->business_type_other : $customer->business_type) }}</td>
        </tr>
        <tr>
            <td class="label">Phone:</td>
            <td class="value value-left">{{ $dash($customer->phone) }}</td>
            <td class="label label-inline">Incoterms:</td>
            <td class="value">{{ $customer->inco_terms ? ($customer->inco_terms->value === 'Other' ? $dash($customer->inco_terms_other) : $customer->inco_terms->label()) : '' }}</td>
        </tr>
    </table>
</div>

<div class="section-title">Addresses</div>
<div class="section-content">
    <table class="address-pair">
        <tr>
            <td class="address-card">
                <div class="address-head">Head Office</div>
                <div class="value">{{ $formatAddressLine($headOfficeAddress) }}</div>
            </td>
            <td class="address-card address-card-right">
                <div class="address-head">NPWP Address</div>
                <div class="value">{{ $formatAddressLine($npwpAddress) }}</div>
            </td>
        </tr>
    </table>

    @php
        $otherAddressTypeLabels = [
            \App\Enums\CustomerAddressType::Billing->value => 'Billing',
            \App\Enums\CustomerAddressType::Correspondence->value => 'Correspondence',
        ];
    @endphp
    @if ($otherAddresses->isNotEmpty())
        <table class="grid-table">
            <thead>
                <tr>
                    <th>Address Type</th>
                    <th>Address</th>
                    <th>Province</th>
                    <th>City/Regency</th>
                    <th>District</th>
                    <th>Sub-district</th>
                    <th>Postal Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($otherAddresses as $a)
                    <tr>
                        <td>{{ $otherAddressTypeLabels[$a->address_type->value] ?? $a->address_type->label() }}</td>
                        <td>{{ $dash($a->address_line) }}</td>
                        <td>{{ $dash($regionName($a->province)) }}</td>
                        <td>{{ $dash($regionName($a->regency)) }}</td>
                        <td>{{ $dash($regionName($a->district)) }}</td>
                        <td>{{ $dash($regionName($a->village)) }}</td>
                        <td>{{ $dash($a->postal_code) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="section-title">Person In Charge</div>
<div class="section-table">
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 23%;">Name</th>
                <th style="width: 20%;">Position</th>
                <th style="width: 19%;">Phone / Mobile</th>
                <th style="width: 33%;">Email</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contacts as $c)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>{{ $dash($c->full_name) }}</td>
                    <td>{{ $dash($c->position) }}</td>
                    <td>
                        @php
                            $digits = fn ($v) => preg_replace('/\D/', '', (string) $v);
                            $numbers = collect([$c->phone, $c->mobile])->filter(fn ($v) => filled($v))->unique($digits);
                        @endphp
                        {!! $numbers->map(fn ($v) => e($v))->implode('<br>') !!}
                    </td>
                    <td>{{ $dash($c->email) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada PIC tercatat.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section-title">Payment Term &amp; Banking Detail</div>
@php
    $pay = $customer->payment;
    $pricingOptions = ['Discount Pricelist', 'Quotation'];
    $paymentMethodOptions = ['SKBDN', 'Bank Guarantee', 'Cover Cek-Giro', 'Transfer'];
    $paymentTermOptions = ['CBD' => 'Cash Before Delivery (CBD)', 'COD' => 'Cash on Delivery (COD)', 'CREDIT' => 'Credit'];
    $termBasisOptions = ['days_after_delivery' => 'After Delivery', 'days_after_invoice_received' => 'After Invoice Received'];
    $creditFacilityOptions = [true => 'YES', false => 'NO'];
@endphp
<div class="section-content">
@if (!$pay)
    <div class="muted">No payment data available.</div>
@else
    <div class="col-container">
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label">Pricing Method:</td>
                    <td class="value">
                        @foreach ($pricingOptions as $opt)
                            {!! $renderCheckboxOption($opt, $pay->calculate_method === $opt) !!}<br>
                        @endforeach
                        @if ($pay->calculate_method && !in_array($pay->calculate_method, $pricingOptions, true))
                            <div class="field-other-note">Other: {{ $pay->calculate_method }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Payment Method:</td>
                    <td class="value">
                        @foreach ($paymentMethodOptions as $opt)
                            {!! $renderCheckboxOption($opt, $pay->payment_method === $opt) !!}<br>
                        @endforeach
                        @if ($pay->payment_method === 'Other' && $pay->payment_method_other)
                            <div class="field-other-note">Other: {{ $pay->payment_method_other }}</div>
                        @elseif ($pay->payment_method && !in_array($pay->payment_method, $paymentMethodOptions, true))
                            <div class="field-other-note">Other: {{ $pay->payment_method }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label">Payment Term:</td>
                    <td class="value">
                        @foreach ($paymentTermOptions as $code => $label)
                            {!! $renderCheckboxOption($label, $pay->payment_term?->value === $code) !!}<br>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="label">Term Days:</td>
                    <td class="value">{{ $dash($pay->payment_term_days) }} days</td>
                </tr>
                <tr>
                    <td class="label">Term Basis:</td>
                    <td class="value">
                        @foreach ($termBasisOptions as $code => $label)
                            {!! $renderCheckboxOption($label, $pay->payment_term_basis?->value === $code) !!}<br>
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-container">
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label">Bank Name:</td>
                    <td class="value">{{ $dash($pay->bank_name) }}</td>
                </tr>
                <tr>
                    <td class="label">Bank Account:</td>
                    <td class="value">{{ $dash($pay->account_number) }}</td>
                </tr>
                <tr>
                    <td class="label">Bank Address:</td>
                    <td class="value">{{ $dash($pay->bank_address) }}</td>
                </tr>
                <tr>
                    <td class="label">Currency:</td>
                    <td class="value">{{ $pay->currency ?: 'IDR' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label">Have Credit Facility or Bank Loan?</td>
                    <td class="value">
                        @foreach ($creditFacilityOptions as $value => $label)
                            {!! $renderCheckboxOption($label, $pay->credit_facility === (bool) $value) !!}&nbsp;&nbsp;&nbsp;
                        @endforeach
                        @if ($pay->credit_facility === true)
                            <div class="creditor-box">{{ $pay->creditor ?: '-' }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Notes:</td>
                    <td class="value">{{ $dash($pay->extra_notes) }}</td>
                </tr>
            </table>
        </div>
    </div>
@endif
</div>

<div class="signature-section">
    <div class="section-title">Signatures</div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="sig-box" style="width: 50%; vertical-align: top;">
                <div class="sig-title">Customer Representative</div>
                <div class="sig-caption">Authorized Signature &amp; Stamp</div>
                <div class="sig-area"></div>
                <table class="sig-fields">
                    <tr><td class="sig-field-label">Name:</td><td class="sig-field-value">&nbsp;</td></tr>
                    <tr><td class="sig-field-label">Date:</td><td class="sig-field-value">&nbsp;</td></tr>
                </table>
            </td>
            <td class="sig-box" style="width: 50%; vertical-align: top;">
                <div class="sig-title">Sales Person (PT Tri Daya Selaras)</div>
                <div class="sig-caption">Authorized Signature</div>
                <div class="sig-area"></div>
                <table class="sig-fields">
                    <tr><td class="sig-field-label">Name:</td><td class="sig-field-value">{{ $marketingName }}</td></tr>
                    <tr><td class="sig-field-label">Date:</td><td class="sig-field-value">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
